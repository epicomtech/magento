<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Order_Shipment extends Epicom_MHub_Model_Cron_Abstract
{
    const ORDER_SHIPMENT_INFO_METHOD  = 'pedidos/{orderId}/entregas/{shipmentId}';
    const ORDER_SHIPMENT_EVENT_METHOD = 'pedidos/{orderId}/entregas/{shipmentId}/eventos/{eventId}';

    const DEFAULT_QUEUE_LIMIT = 60;

    const INVOICE_EMPTY_MESSAGE = 'Não foi possível criar uma entrega em branco.';

    protected $_events = array(
        Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_CREATED,
        Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_NF,
        Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_SENT,
        Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_DELIVERED,
        Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_FAILED,
        Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_PARCIAL,
        Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_CANCELED
    );

    private function readMHubOrderShipmentsCollection ()
    {
        $limit = intval (Mage::getStoreConfig ('mhub/queue/shipment'));

        $collection = Mage::getModel ('mhub/shipment')->getCollection ()
            ->addFieldToFilter ('event', array ('in' => $this->_events))
        ;

        $collection->getSelect ()
            ->where ('updated_at > synced_at OR synced_at IS NULL')
            ->where (sprintf ("operation = '%s' AND status != '%s'",
                Epicom_MHub_Helper_Data::OPERATION_IN,
                Epicom_MHub_Helper_Data::STATUS_OKAY
            ))
            ->order ('order_id DESC')
            ->order ('order_increment_id DESC')
            ->order ('external_order_id')
            ->order ('external_shipment_id')
            ->order (sprintf ("FIELD(event,%s)", implode (',', array_map (
                function ($n) { return "'{$n}'"; }, $this->_events
            ))))
            ->limit ($limit ? $limit : self::DEFAULT_QUEUE_LIMIT)
        ;

        return $collection;
    }

    protected function updateOrderShipments ($collection)
    {
        foreach ($collection as $order)
        {
            $result = null;

            try
            {
                $result = $this->updateMHubOrderShipment ($order);
            }
            catch (Exception $e)
            {
                if (!strcmp ($e->getMessage (), self::INVOICE_EMPTY_MESSAGE))
                {
                    $result = true; // forced
                }

                $this->logMHubOrderShipment ($order, addslashes ($e->getMessage ()));

                self::logException ($e);
            }

            if (!empty ($result)) $this->cleanupMHubOrderShipment ($order, $result);
        }

        return true;
    }

    protected function updateMHubOrderShipment (Epicom_MHub_Model_Shipment $shipment)
    {
        $orderId    = $shipment->getExternalOrderId ();
        $shipmentId = $shipment->getExternalShipmentId ();
        $eventId    = $shipment->getExternalEventId ();
        $providerId = $shipment->getExternalProviderId ();

        $websiteId  = $shipment->getWebsiteId ();
        $storeId    = $shipment->getStoreId ();

        /**
         * Order Info
         */
        $mageOrder = Mage::getModel ('sales/order')->load ($orderId, Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID);

        if (!$mageOrder || !$mageOrder->getId ())
        {
            $this->_fault ('order_not_exists');
        }

        $shipment->setOrderId ($mageOrder->getId ())
            ->setOrderIncrementId ($mageOrder->getIncrementId ())
            ->save ()
        ;

        /**
         * Shipments Info
         */
        $helper = Mage::Helper ('mhub');

        $shipmentInfoMethod = str_replace (array ('{orderId}', '{shipmentId}'), array ($orderId, $shipmentId), self::ORDER_SHIPMENT_INFO_METHOD);
        $shipmentInfoResult = $helper->api ($shipmentInfoMethod, null, null, $storeId);

        /**
         * Event Info
         */
        $helper = Mage::Helper ('mhub');

        $shipmentEventMethod = str_replace (array ('{orderId}', '{shipmentId}', '{eventId}'), array ($orderId, $shipmentId, $eventId), self::ORDER_SHIPMENT_EVENT_METHOD);
        $shipmentEventResult = $helper->api ($shipmentEventMethod, null, null, $storeId);

        /**
         * Transaction
         */
        if (!$shipment->getEvent ())
        {
            $shipment->setEvent ($shipmentEventResult->tipo)
                ->save ()
            ;
        }

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
                $skus = array ();

                foreach ($shipmentInfoResult->skus as $_sku)
                {
                    $skus [] = $_sku->id;
                }
/*
                $websiteId = Mage::app ()->getStore ()->getWebsiteId ();
                $storeId   = Mage::app ()->getStore ()->getId ();
*/
                $mhubNf = Mage::getModel ('mhub/nf')
                    ->setWebsiteId ($websiteId)
                    ->setStoreId ($storeId)
                    ->setOrderIncrementId ($mageOrder->getIncrementId ())
                    ->setSkus (implode (',', $skus))
                    ->setNumber ($shipmentInfoResult->nfNumero)
                    ->setSeries ($shipmentInfoResult->nfSerie)
                    ->setAccessKey ($shipmentInfoResult->nfChaveAcesso)
                    ->setCfop ($shipmentInfoResult->nfCFOP)
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

                $productIdAttribute = Mage::getStoreConfig ('mhub/product/id');

                foreach ($shipmentInfoResult->skus as $_sku)
                {
                    foreach ($mageOrder->getAllItems () as $_item)
                    {
                        if (!strcmp ($_item->getData ($productIdAttribute /* Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID */), $_sku->id))
                        {
                            if ($_item->getParentItemId ())
                            {
                                $_item = Mage::getModel ('sales/order_item')->load ($_item->getParentItemId ());
                            }

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
                $mageShipment->setData (Epicom_MHub_Helper_Data::SHIPMENT_ATTRIBUTE_IS_EPICOM,       true);
                $mageShipment->setData (Epicom_MHub_Helper_Data::SHIPMENT_ATTRIBUTE_EXT_SHIPMENT_ID, $shipmentId);
                $mageShipment->save ();

                $mageTrack->save ();

                $mageShipment->sendEmail (true);

                $shipment->setShipmentId ($mageShipment->getId ());
                $shipment->setShipmentIncrementId ($mageShipment->getIncrementId ());

                $orderStatus   = Mage::getStoreConfig ('mhub/shipment/sent_status');
                $orderComment  = Mage::getStoreConfig ('mhub/shipment/sent_comment');
                $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/sent_notified');

                break;
            }
            case Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_DELIVERED:
            {
                $productIdAttribute = Mage::getStoreConfig ('mhub/product/id');

                foreach ($shipmentInfoResult->skus as $_sku)
                {
                    foreach ($mageOrder->getAllItems () as $_item)
                    {
                        if (!strcmp ($_item->getData ($productIdAttribute /* Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID */), $_sku->id))
                        {
                            /*
                            if ($_item->getParentItemId ())
                            {
                                $_item = Mage::getModel ('sales/order_item')->load ($_item->getParentItemId ());
                            }
                            */
                            $_item->setQtyDelivered ($_sku->quantidade)->save ();
                        }
                    }
                }

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
                $orderStatus   = Mage::getStoreConfig ('mhub/shipment/parcial_status');
                $orderComment  = Mage::getStoreConfig ('mhub/shipment/parcial_comment');
                $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/parcial_notified');

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
        $shipment->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ()
        ;

        return true;
    }

    private function logMHubOrderShipment (Epicom_MHub_Model_Shipment $shipment, $message = null)
    {
        $shipment->setStatus (Epicom_MHub_Helper_Data::STATUS_ERROR)
            ->setMessage ($message)
            ->save ()
        ;
    }

    private function cleanupMHubOrderShipment (Epicom_MHub_Model_Shipment $shipment, $status = null)
    {
        $shipment->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ()
        ;
    }

    public function run ()
    {
        if (!$this->getStoreConfig ('active') || !$this->getHelper ()->isMarketplace ())
        {
            return false;
        }
/*
        $result = $this->readMHubOrderShipmentsMagento ();
        if (!$result) return false;
*/
        $collection = $this->readMHubOrderShipmentsCollection ();
        if (!$collection->getSize ()) return false;

        $this->updateOrderShipments ($collection);

        return true;
    }
}

