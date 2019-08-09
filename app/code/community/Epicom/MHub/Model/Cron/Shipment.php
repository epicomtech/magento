<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Shipment extends Epicom_MHub_Model_Cron_Abstract
{
    const SHIPMENTS_POST_METHOD  = 'pedidos/{orderId}/entregas';
    const SHIPMENTS_PATCH_METHOD = 'pedidos/{orderId}/entregas/{shipmentId}';

    private function readMHubShipmentsMagento ()
    {
        $orderStatus = Mage::getStoreConfig ('mhub/order/sent_filter');

        $collection = Mage::getModel ('sales/order_shipment')->getCollection ();

        $select = $collection->getSelect ()
            ->join(
                array ('sfo' => Mage::getSingleton ('core/resource')->getTableName ('sales_flat_order')),
                'main_table.order_id = sfo.entity_id AND sfo.ext_order_id IS NOT NULL',
                array (
                    'order_increment_id' => 'sfo.increment_id',
                    Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_IS_EPICOM,
                    Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID,
                )
            );

        $collection->addAttributeToFilter ('sfo.status', array ('eq' => $orderStatus));
        /*
        $collection->addAttributeToFilter ('sfo.' . Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_IS_EPICOM, array ('notnull' => true));
        $collection->addAttributeToFilter ('sfo.' . Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID, array ('notnull' => true));
        */

        $select = $collection->getSelect ()
            ->joinLeft(
                array ('mhub' => Epicom_MHub_Helper_Data::SHIPMENT_TABLE),
                'main_table.entity_id = mhub.shipment_id',
                array ('mhub_updated_at' => 'mhub.updated_at', 'mhub_synced_at' => 'mhub.synced_at')
            )->where ('main_table.created_at > mhub.synced_at OR mhub.synced_at IS NULL');

        foreach ($collection as $shipment)
        {
            $shipmentId = $shipment->getId ();

            $websiteId = Mage::app ()->getStore ($shipment->getStoreId ())->getWebsiteId ();

            $mhubShipment = Mage::getModel ('mhub/shipment')->load ($shipmentId, 'shipment_id');
            $mhubShipment->setShipmentId ($shipment->getId ())
                ->setWebsiteId ($websiteId)
                ->setStoreId ($shipment->getStoreId ())
                ->setShipmentIncrementId ($shipment->getIncrementId())
                ->setOrderId ($shipment->getOrderId ())
                ->setOrderIncrementId ($shipment->getOrderIncrementId ())
                ->setExternalOrderId ($shipment->getData (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID))
                ->setExternalShipmentId ($shipment->getData (Epicom_MHub_Helper_Data::SHIPMENT_ATTRIBUTE_EXT_SHIPMENT_ID))
                ->setOperation (Epicom_MHub_Helper_Data::OPERATION_OUT)
                ->setEvent (Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_CREATED)
                ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
                ->setMessage (new Zend_Db_Expr ('NULL'))
                ->setUpdatedAt (date ('c'))
                ->save ();
        }

        return true;
    }

    private function readMHubShipmentsCollection ()
    {
        $collection = Mage::getModel ('mhub/shipment')->getCollection ();
        $select = $collection->getSelect ();
        $select->where ('synced_at < updated_at OR synced_at IS NULL')
               // ->group ('shipment_id')
               // ->shipment ('updated_at DESC')
        ;

        return $collection;
    }

    private function updateShipments ($collection)
    {
        foreach ($collection as $shipment)
        {
            $externalShipmentId = null;

            try
            {
                $externalShipmentId = $this->updateMHubShipment ($shipment);
            }
            catch (Exception $e)
            {
                $this->logMHubShipment ($shipment, $e->getMessage ());

                self::logException ($e);
            }

            if (!empty ($externalShipmentId)) $this->cleanupMHubShipment ($shipment, $externalShipmentId);
        }

        return true;
    }

    private function updateMHubShipment (Epicom_MHub_Model_Shipment $shipment)
    {
        $shipmentId = $shipment->getShipmentId ();

        $mageShipment = Mage::getModel ('sales/order_shipment');
        $loaded = $mageShipment->load ($shipmentId);
        if (!$loaded || !$loaded->getId ())
        {
            return false;
        }
        else
        {
            $mageShipment = $loaded;
        }

        $mageOrder = Mage::getModel ('sales/order')->load ($mageShipment->getOrderId ());

        $shippingDescription = explode (' - ', $mageOrder->getShippingDescription ());

        $post = array(
            'codigo'              => $mageShipment->getIncrementId (),
            'skus'                => array (),
            'nomeTransportadora'  => @ $shippingDescription [1],
            'previsaoEntrega'     => @ $shippingDescription [2],
            'dataEntrega'         => null,
            'tracking'            => null,
            'linkRastreioEntrega' => null,
            'nfSerie'             => null,
            'nfNumero'            => null,
            'nfDataEmissao'       => null,
            'nfChaveAcesso'       => null,
            'nfLink'              => null,
        );

        foreach ($mageShipment->getAllTracks () as $track)
        {
            if (!strcmp ($track->getCarrierCode (), Epicom_MHub_Model_Shipping_Carrier_Epicom::CODE))
            {
                $post ['tracking'] = $track->getTrackNumber ();
                $post ['linkRastreioEntrega'] = Mage::helper ('shipping')->getTrackingPopupUrlBySalesModel ($mageOrder);

                break;
            }
        }

        if (empty ($post ['tracking'] || empty ($post ['linkRastreioEntrega'])))
        {
            throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('No tracking information was found! Shipment: %s', $mageShipment->getIncrementId ()), 9999);
        }

        $mhubNf = Mage::getModel ('mhub/nf')->load ($mageOrder->getIncrementId (), 'order_increment_id');
        if ($mhubNf && $mhubNf->getId ())
        {
            $post = array_merge ($post, array (
                'nfSerie'       => $mhubNf->getSeries (),
                'nfNumero'      => $mhubNf->getNumber (),
                'nfDataEmissao' => $mhubNf->getIssuedAt (),
                'nfChaveAcesso' => $mhubNf->getAccessKey (),
                'NfCFOP'        => $mhubNf->getCfop (),
                'nfLink'        => $mhubNf->getLink (),
            ));
        }
        else
        {
            throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('No N.F. information was found! Order: %s', $mageOrder->getIncrementId ()), 9999);
        }

        $productCodeAttribute = Mage::getStoreConfig ('mhub/product/code');

        $mageShipmentItems = Mage::getResourceModel ('sales/order_shipment_item_collection')
            ->setShipmentFilter ($mageShipment->getId ())
            // ->addFieldToFilter ($productCodeAttribute, array ('notnull' => true))
        ;

        $productCodeAttribute = Mage::getStoreConfig ('mhub/product/code');

        foreach ($mageShipmentItems as $item)
        {
            $mageProduct = Mage::getModel ('catalog/product')->loadByAttribute ('entity_id', $item->getProductId (), $productCodeAttribute);

            if (!$mageProduct || !$mageProduct->getId ())
            {
                throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('No product was found! SKU: %s', $item->getSku ()), 9999);
            }

            $productCode = $mageProduct->getData ($productCodeAttribute);

            $post ['skus'][] = array ('codigo' => $productCode);
        }

        $extOrderId = $mageOrder->getData (Epicom_Mhub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID);

        $extShipmentId = true;

        /**
         * GET
         */
        try
        {
            $shipmentsPostMethod = str_replace ('{orderId}', $extOrderId, self::SHIPMENTS_POST_METHOD);

            $result = $this->getHelper ()->api ($shipmentsPostMethod, null, null, $shipment->getStoreId ());

            $_shippedCount = 0;

            foreach ($result as $_shipment)
            {
                foreach ($_shipment->skus as $_sku)
                {
                    foreach ($post ['skus' ] as $_id => $_item)
                    {
                        if (!strcmp ($_item ['codigo'], $_sku->codigo))
                        {
                            ++ $_shippedCount;

                            break;
                        }
                    }
                }
            }

            if (count ($post ['skus']) == $_shippedCount)
            {
                $post = null; // all_items_shipped
            }
        }
        catch (Exception $e)
        {
            // nothing_here
        }

        /**
         * POST
         */
        try
        {
            $shipmentsPostMethod = str_replace ('{orderId}', $extOrderId, self::SHIPMENTS_POST_METHOD);

            $result = $this->getHelper ()->api ($shipmentsPostMethod, $post, null, $shipment->getStoreId ());

            $extShipmentId = $result [0]->id;

            $mageShipment->setData (Epicom_MHub_Helper_Data::SHIPMENT_ATTRIBUTE_IS_EPICOM, true)
                ->setData (Epicom_MHub_Helper_Data::SHIPMENT_ATTRIBUTE_EXT_SHIPMENT_ID, $extShipmentId)
                ->save ()
            ;
        }
        catch (Exception $e)
        {
            if ($e->getCode () == 409 /* Resource Exists */)
            {
                $shipmentsPatchMethod = str_replace (array ('{orderId}', '{shipmentId}'), array ($extOrderId, $shipment->getExternalShipmentId ()), self::SHIPMENTS_PATCH_METHOD);

                $this->getHelper ()->api ($shipmentsPatchMethod, $post, 'PATCH', $shipment->getStoreId ());
            }
            else
            {
                throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
            }
        }

        return $extShipmentId;
    }

    private function cleanupMHubShipment (Epicom_MHub_Model_Shipment $shipment, $externalShipmentId = null)
    {
        if ($externalShipmentId !== null && $externalShipmentId !== true)
        {
            $shipment->setExternalShipmentId ($externalShipmentId);
        }

        $shipment->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ();

        return true;
    }

    private function logMHubShipment (Epicom_MHub_Model_Shipment $shipment, $message = null)
    {
        $shipment->setStatus (Epicom_MHub_Helper_Data::STATUS_ERROR)->setMessage ($message)->save ();
    }

    public function run ()
    {
        if (!$this->getStoreConfig ('active') || $this->getHelper ()->isMarketplace ())
        {
            return false;
        }

        $result = $this->readMHubShipmentsMagento ();
        if (!$result) return false;

        $collection = $this->readMHubShipmentsCollection ();
        $length = $collection->count ();
        if (!$length) return false;

        $this->updateShipments ($collection);

        return true;
    }
}

