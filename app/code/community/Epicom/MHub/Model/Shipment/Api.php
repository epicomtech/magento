<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Shipment_Api extends Mage_Api_Model_Resource_Abstract
{
    const ORDER_SHIPMENT_INFO_METHOD  = 'pedidos/{orderId}/entregas/{shipmentId}';
    const ORDER_SHIPMENT_EVENT_METHOD = 'pedidos/{orderId}/entregas/{shipmentId}/eventos/{eventId}';

    public function manage ($type, $send_date, $parameters)
    {
        if (empty ($type) || empty ($send_date) || empty ($parameters))
        {
            $this->_fault ('invalid_request_param');
        }

        /**
         * Transaction
         */
		$orderId    = $parameters ['idPedido'];
        $shipmentId = $parameters ['idEntrega'];
        $eventId    = $parameters ['idEvento'];
        $providerId = $parameters ['idFornecedor'];

        if (empty ($orderId) || empty ($shipmentId) || empty ($eventId) || empty ($providerId))
        {
            $this->_fault ('invalid_request_param');
        }

        // transaction
        $shipment = Mage::getModel ('mhub/shipment')
            ->setMethod ($type)
            ->setSendDate ($send_date)
            ->setParameters (json_encode ($parameters))
            ->setExternalOrderId ($orderId)
            ->setExternalShipmentId ($shipmentId)
            ->setExternalEventId ($eventId)
            ->setExternalProviderId ($providerId)
            ->setOperation (Epicom_MHub_Helper_Data::OPERATION_IN)
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->setUpdatedAt (date ('c'))
            ->save ()
        ;

        /**
         * Order Info
         */
        $mageOrder = Mage::getModel ('sales/order')->load ($orderId, Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID);
        if (!$mageOrder || !$mageOrder->getId ())
        {
            $this->_fault ('order_not_exists');
        }

        /**
         * Shipments Info
         */
        $helper = Mage::Helper ('mhub');

        $shipmentInfoMethod = str_replace (array ('{orderId}', '{shipmentId}'), array ($orderId, $shipmentId), self::ORDER_SHIPMENT_INFO_METHOD);
        $shipmentInfoResult = $helper->api ($shipmentInfoMethod);

        /**
         * Event Info
         */
        $helper = Mage::Helper ('mhub');

        $shipmentEventMethod = str_replace (array ('{orderId}', '{shipmentId}', '{eventId}'), array ($orderId, $shipmentId, $eventId), self::ORDER_SHIPMENT_EVENT_METHOD);
        $shipmentEventResult = $helper->api ($shipmentEventMethod);

        /**
         * Parse
         */
        $orderStatus   = null;
        $orderComment  = null;
        $orderNotified = false;

        switch ($shipmentEventResult->tipo)
        {
            case Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_NF:
            {
                $mhubNf = Mage::getModel ('mhub/nf')
                    ->setOrderIncrementId ($mageOrder->getIncrementId ())
                    ->setNumber ($shipmentInfoResult->nfNumero)
                    ->setSeries ($shipmentInfoResult->nfSerie)
                    ->setAccessKey ($shipmentInfoResult->nfChaveAcesso)
                    ->setLink ($shipmentInfoResult->nfLink)
                    ->setIssuedAt ($shipmentInfoResult->nfDataEmissao)
                    ->setOperation (Epicom_MHub_Helper_Data::OPERATION_IN)
                    ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
                    ->setCreatedAt (date ('c'))
                    ->save ();
                ;

                $orderStatus   = Mage::getStoreConfig ('mhub/shipment/nf_status');
                $orderComment  = Mage::getStoreConfig ('mhub/shipment/nf_comment');
                $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/nf_notified');

                break;
            }
            case Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_SENT:
            {
                $shipmentItemQtys = array ();

                $productCodeAttribute = Mage::getStoreConfig ('mhub/product/code');

                foreach ($shipmentInfoResult->skus as $_sku)
                {
                    foreach ($mageOrder->getAllItems () as $_item)
                    {
                        if (!strcmp ($_item->getData ($productCodeAttribute /* Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_CODE */), $_sku->codigo))
                        {
                            $shipmentItemQtys [$_item->getId ()] = $_sku->quantidade;
                        }
                    }
                }

                if (empty ($shipmentItemQtys))
                {
                    $this->_fault ('order_item_not_exists');
                }

                $mageShipment = Mage::getModel ('sales/service_order', $mageOrder)->prepareShipment ($shipmentItemQtys);
                $mageShipment->register ();

                $mageOrder->setIsInProcess (true);

                Mage::getModel ('core/resource_transaction')
                         ->addObject ($mageShipment)
                         ->addObject ($mageOrder)
                         ->save ();

                $mageTrack = Mage::getModel ('sales/order_shipment_track')
                    ->setOrderId ($mageOrder->getId ())
                    ->setNumber ($shipmentInfoResult->tracking)
                    ->setDescription ($shipmentInfoResult->linkRastreioEntrega)
                    ->setTitle ($shipmentInfoResult->nomeTransportadora)
                    ->setCarrierCode (Epicom_MHub_Model_Shipping_Carrier_Epicom::CODE)
                ;

                $mageShipment->addTrack ($mageTrack);
                $mageShipment->save ();
                $mageTrack->save ();

                $shipment->setShipmentId ($mageShipment->getId ());
                $shipment->setShipmentIncrementId ($mageShipment->getIncrementId ());

                $orderStatus   = Mage::getStoreConfig ('mhub/shipment/sent_status');
                $orderComment  = Mage::getStoreConfig ('mhub/shipment/sent_comment');
                $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/sent_notified');

                break;
            }
            case Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_DELIVERED:
            {
                $orderStatus   = Mage::getStoreConfig ('mhub/shipment/delivered_status');
                $orderComment  = Mage::getStoreConfig ('mhub/shipment/delivered_comment');
                $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/delivered_notified');

                break;
            }
            case Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_FAILED:
            {
                $orderStatus   = Mage::getStoreConfig ('mhub/shipment/failed_status');
                $orderComment  = Mage::getStoreConfig ('mhub/shipment/failed_comment');
                $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/failed_notified');

                break;
            }
            case Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_PARCIAL:
            {
                $orderStatus   = Mage::getStoreConfig ('mhub/shipment/failed_status');
                $orderComment  = Mage::getStoreConfig ('mhub/shipment/failed_comment');
                $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/failed_notified');

                break;
            }
            case Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_CANCELED:
            {
                $orderStatus   = Mage::getStoreConfig ('mhub/shipment/canceled_status');
                $orderComment  = Mage::getStoreConfig ('mhub/shipment/canceled_comment');
                $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/canceled_notified');

                break;
            }
        }

        if (!empty ($orderStatus))
        {
            $mageOrder->addStatusToHistory ($orderStatus, $orderComment, $orderNotified)->save ();
        }

        // transaction
        $shipment->setEvent ($shipmentEventResult->tipo)
            ->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ();

        return true;
    }
}

