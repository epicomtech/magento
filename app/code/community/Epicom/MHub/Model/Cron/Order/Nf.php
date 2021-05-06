<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Order_Nf extends Epicom_MHub_Model_Cron_Abstract
{
    const SHIPMENTS_POST_METHOD  = 'pedidos/{orderId}/entregas';
    const SHIPMENTS_PATCH_METHOD = 'pedidos/{orderId}/entregas/{shipmentId}';

    const SHIPMENTS_EVENTS_POST_METHOD = 'pedidos/{orderId}/entregas/{shipmentId}/eventos';

    private function readMHubNFsCollection ()
    {
        $collection = Mage::getModel ('mhub/nf')->getCollection ()
            ->addFieldToFilter ('operation', array ('eq' => Epicom_MHub_Helper_Data::OPERATION_OUT))
        ;

        $collection->getSelect ()
            ->join(
                array ('sfo' => Mage::getSingleton ('core/resource')->getTableName ('sales_flat_order')),
                'main_table.order_increment_id = sfo.increment_id',
                array (
                    'order_increment_id' => 'sfo.increment_id',
                    Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_IS_EPICOM,
                    Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID,
                )
            )
            ->where ('synced_at < main_table.updated_at OR synced_at IS NULL')
            ->where ('main_table.status <> ?', Epicom_MHub_Helper_Data::STATUS_OKAY)
        ;

        $confirmStatus = Mage::getStoreConfig ('mhub/order/confirm_filter');
        $sentStatus    = Mage::getStoreConfig ('mhub/order/sent_filter');

        $collection->addFieldToFilter ('sfo.status', array ('in' => array ($confirmStatus, $sentStatus)));
        $collection->addFieldToFilter ('sfo.' . Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_IS_EPICOM, array ('notnull' => true));
        $collection->addFieldToFilter ('sfo.' . Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID, array ('notnull' => true));

        return $collection;
    }

    private function updateNFs ($collection)
    {
        foreach ($collection as $nf)
        {
            $externalShipmentId = null;

            try
            {
                $externalShipmentId = $this->updateMHubNf ($nf);
            }
            catch (Exception $e)
            {
                $this->logMHubNf ($nf, $e->getMessage ());

                self::logException ($e);
            }

            if (!empty ($externalShipmentId))
            {
                $this->cleanupMHubNf ($nf, $externalShipmentId);
            }
        }

        return true;
    }

    private function updateMHubNf (Epicom_MHub_Model_Nf $nf)
    {
        $mageOrder = Mage::getModel ('sales/order')->loadByIncrementId ($nf->getOrderIncrementId ());

        if (!$mageOrder || !$mageOrder->getId ())
        {
            return false;
        }

        $shippingDescription = explode (' - ', $mageOrder->getShippingDescription ());

        $post = array(
            'codigo'              => $mageOrder->getIncrementId (),
            'skus'                => array (),
            'nomeTransportadora'  => @ $shippingDescription [1],
            'previsaoEntrega'     => @ $shippingDescription [2],
            'dataEntrega'         => null,
            'tracking'            => null,
            'linkRastreioEntrega' => null,
            'nfSerie'       => $nf->getSeries (),
            'nfNumero'      => $nf->getNumber (),
            'nfDataEmissao' => $nf->getIssuedAt (),
            'nfChaveAcesso' => $nf->getAccessKey (),
            'NfCFOP'        => $nf->getCfop (),
            'nfLink'        => $nf->getLink (),
        );

        $extOrderId = $mageOrder->getData (Epicom_Mhub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID);

        $extShipmentId = true;

        /**
         * POST
         */
        try
        {
            $shipmentsPostMethod = str_replace ('{orderId}', $extOrderId, self::SHIPMENTS_POST_METHOD);

            $result = $this->getHelper ()->api ($shipmentsPostMethod, $post, null, $nf->getStoreId ());

            $extShipmentId = $result->id;

            $mageOrder->setData (Epicom_MHub_Helper_Data::SHIPMENT_ATTRIBUTE_EXT_SHIPMENT_ID, $extShipmentId)
                ->save ()
            ;

            /**
             * NF Event
             */
            $shipmentsEventsPostMethod = str_replace (array ('{orderId}', '{shipmentId}'), array ($extOrderId, $extShipmentId), self::SHIPMENTS_EVENTS_POST_METHOD);

            $post = array (
                'tipo'      => Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_NF,
                'descricao' => null,
                'data'      => date ('c'),
            );

            $result = Mage::helper ('mhub')->api ($shipmentsEventsPostMethod, $post);

            /**
             * Order Status
             */
            $mhubOrder = Mage::getModel ('mhub/order_status')
                ->setOrderId ($mageOrder->getId ())
                ->setOrderIncrementId ($mageOrder->getIncrementId ())
                ->setOrderExternalId ($extOrderId)
                ->setOperation (Epicom_MHub_Helper_Data::OPERATION_OUT)
                ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
                ->setMessage (new Zend_Db_Expr ('NULL'))
                ->setUpdatedAt (date ('c'))
                ->save ()
            ;

            $orderStatus   = Mage::getStoreConfig ('mhub/shipment/nf_status');
            $orderComment  = Mage::getStoreConfig ('mhub/shipment/nf_comment');
            $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/nf_notified');

            $mageOrder->addStatusToHistory ($orderStatus, $orderComment, $orderNotified)->save ();
        }
        catch (Exception $e)
        {
            if ($e->getCode () == 409 /* Resource Exists */)
            {
                $extShipmentId = $mageOrder->getData (Epicom_MHub_Helper_Data::SHIPMENT_ATTRIBUTE_EXT_SHIPMENT_ID);

                $shipmentsPatchMethod = str_replace (array ('{orderId}', '{shipmentId}'), array ($extOrderId, $extShipmentId), self::SHIPMENTS_PATCH_METHOD);

                $this->getHelper ()->api ($shipmentsPatchMethod, $post, 'PATCH', $nf->getStoreId ());
            }
            else
            {
                throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
            }
        }

        return $extShipmentId;
    }

    private function cleanupMHubNf (Epicom_MHub_Model_Nf $nf, $extShipmentId = null)
    {
        $nf->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ()
        ;

        return true;
    }

    private function logMHubNf (Epicom_MHub_Model_Nf $nf, $message = null)
    {
        $nf->setStatus (Epicom_MHub_Helper_Data::STATUS_ERROR)
            ->setMessage ($message)
            ->save ()
        ;
    }

    public function run ()
    {
        if (!$this->getStoreConfig ('active') || $this->getHelper ()->isMarketplace ())
        {
            return false;
        }

        $collection = $this->readMHubNFsCollection ();

        if (!$collection->count ())
        {
            return false;
        }

        $this->updateNFs ($collection);

        return true;
    }
}

