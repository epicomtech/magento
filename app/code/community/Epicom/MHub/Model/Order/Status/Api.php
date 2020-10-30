<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Order_Status_Api extends Epicom_MHub_Model_Api_Resource_Abstract
{
    const ORDERS_INFO_METHOD = 'pedidos/{orderId}';

    public function __construct ()
    {
        Mage::app ()->setCurrentStore (Mage_Core_Model_App::ADMIN_STORE_ID);
    }

    public function approve ($orderCode, $orderCodeEpicom, $marketplace, $recipient)
    {
        if (/* empty ($orderCode) || */ empty ($orderCodeEpicom) || empty ($marketplace) || empty ($recipient))
        {
            $this->_fault ('invalid_request_param');
        }

        list ($mhubOrderStatus, $mageOrder) = $this->_initMhubOrderStatus ($orderCodeEpicom);

        if (!$mageOrder->canInvoice ())
        {
            return $this->_error ($mhubOrderStatus, Mage::helper ('mhub')->__('Order has invoices'), null /* order_has_invoices */, 200);
        }

        $itemsQty = array ();
        foreach ($mageOrder->getAllItems () as $item)
        {
            $itemsQty [$item->getId ()] = $item->getQtyOrdered ();
            
        }

        $invoice = $mageOrder->prepareInvoice ($itemsQty)->register ();

        $invoice->getOrder ()->setIsInProcess (true);

        try
        {
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject ($invoice)
                ->addObject ($invoice->getOrder())
                ->save ()
            ;
        }
        catch (Mage_Core_Exception $e)
        {
            return $this->_error ($mhubOrderStatus, Mage::helper ('mhub')->__('Order cannot be invoiced: %s', $e->getMessage ()), null /* order_cannot_invoiced */);
        }

        $invoice->sendEmail (true);

        $mhubOrderStatus->setOrderIncrementId ($mageOrder->getIncrementId ())
            ->setOrderId ($mageOrder->getId ())
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (Mage::helper ('mhub')->__('Order was successfully invoiced'))
            ->setSyncedAt (date ('c'))
            ->save ()
        ;

        $cancelChannelOrder = Mage::getStoreConfigFlag ('mhub/order/channel_cancel');

        return array ('confirmado' => true, 'cancelarPedidoCanal' => $cancelChannelOrder);
    }

    public function cancel ($orderCode, $orderCodeEpicom, $marketplace, $recipient)
    {
        if (empty ($orderCode) || empty ($orderCodeEpicom) || empty ($marketplace) || empty ($recipient))
        {
            $this->_fault ('invalid_request_param');
        }

        list ($mhubOrderStatus, $mageOrder) = $this->_initMhubOrderStatus ($orderCodeEpicom);

        $orderStatus   = Mage::getStoreConfig ('mhub/shipment/canceled_status');

        if (!strcmp ($mageOrder->getStatus (), $orderStatus))
        {
            return $this->_error ($mhubOrderStatus, Mage::helper ('mhub')->__('Order has been canceled'), null /* order_has_canceled */);
        }

        if (!$mageOrder->canCancel ())
        {
            return $this->_error ($mhubOrderStatus, Mage::helper ('mhub')->__('Order cannot be canceled'), null /* order_cannot_cancel */);
        }

        $orderComment  = Mage::getStoreConfig ('mhub/shipment/canceled_comment');
        $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/canceled_notified');

        try
        {
            $ordersInfoMethod = str_replace ('{orderId}', $orderCodeEpicom, self::ORDERS_INFO_METHOD);

            $response = $this->getHelper ()->api ($ordersInfoMethod);

            if (empty ($response))
            {
                throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Epicom information is empty!'), 9999);
            }

            $productIdAttribute = Mage::getStoreConfig ('mhub/product/id');

            $itemsQty = 0;

            foreach ($response->itens as $item)
            {
                foreach ($mageOrder->getAllItems as $orderItem)
                {
                    if (!strcmp ($orderItem->getData ($productIdAttribute), $item->id))
                    {
                        $orderItem->cancel ();

                        ++ $itemsQty;

                        continue;
                    }
                }
            }

            if (count ($response->itens) == $itemsQty && $itemsQty > 0)
            {
                $mageOrder->cancel ()->save ();

                $mageOrder->addStatusToHistory ($orderStatus, $orderComment, $orderNotified)->save ();
            }
        }
        catch (Mage_Core_Exception $e)
        {
            return $this->_error ($mhubOrderStatus, Mage::helper ('mhub')->__('Order cannot be canceled'), null /* order_cannot_cancel */);
        }

        $mhubOrderStatus->setOrderIncrementId ($mageOrder->getIncrementId ())
            ->setOrderId ($mageOrder->getId ())
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (Mage::helper ('mhub')->__('Order was successfully canceled'))
            ->setSyncedAt (date ('c'))
            ->save ()
        ;

        return array ('confirmado' => true);
    }

    public function sent ($orderCode, $orderCodeEpicom, $tracking)
    {
        if (empty ($orderCode) || empty ($orderCodeEpicom) || empty ($tracking))
        {
            $this->_fault ('invalid_request_param');
        }

        list ($mhubOrderStatus, $mageOrder) = $this->_initMhubOrderStatus ($orderCodeEpicom);

        if (!$mageOrder->canShip ())
        {
            return $this->_error ($mhubOrderStatus, Mage::helper ('mhub')->__('Order has shipped'), null /* order_has_shipped */);
        }

        $itemsQty = array ();
        foreach ($mageOrder->getAllItems () as $item)
        {
            $itemsQty [$item->getId ()] = $item->getQtyOrdered ();
        }

        $shipment = $mageOrder->prepareShipment ($itemsQty)->register ();

        $shipment->getOrder ()->setIsInProcess (true);

        $orderStatus   = Mage::getStoreConfig ('mhub/shipment/sent_status');
        $orderComment  = Mage::getStoreConfig ('mhub/shipment/sent_comment');
        $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/sent_notified');

        try
        {
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject ($shipment)
                ->addObject ($shipment->getOrder ())
                ->save ();

            $track = Mage::getModel ('sales/order_shipment_track')
                ->setCarrierCode (Epicom_MHub_Model_Shipping_Carrier_Epicom::CODE)
                ->setNumber ($tracking ['tracking'])
                ->setTitle ($tracking ['transportadora'])
                ->setDescription ($tracking ['linkDeRastreio'])
            ;

            $shipment->addTrack ($track);

            $shipment->save ();
            $track->save ();

            $mageOrder->addStatusToHistory ($orderStatus, $orderComment, $orderNotified)->save ();
        }
        catch (Mage_Core_Exception $e)
        {
            return $this->_error ($mhubOrderStatus, Mage::helper ('mhub')->__('Order was not shipped'), null /* order_cannot_shipped */);
        }

        $mhubOrderStatus->setOrderIncrementId ($mageOrder->getIncrementId ())
            ->setOrderId ($mageOrder->getId ())
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (Mage::helper ('mhub')->__('Order was successfully shipped'))
            ->setSyncedAt (date ('c'))
            ->save ()
        ;

        return array ('confirmado' => true);
    }

    public function delivered ($orderCode, $orderCodeEpicom)
    {
        if (empty ($orderCode) || empty ($orderCodeEpicom))
        {
            $this->_fault ('invalid_request_param');
        }

        list ($mhubOrderStatus, $mageOrder) = $this->_initMhubOrderStatus ($orderCodeEpicom);

        $orderStatus   = Mage::getStoreConfig ('mhub/shipment/delivered_status');
        $orderComment  = Mage::getStoreConfig ('mhub/shipment/delivered_comment');
        $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/delivered_notified');
/*
        if (!strcmp ($mageOrder->getStatus (), $orderStatus))
        {
            $this->_fault ('order_has_delivered');
        }
*/
        try
        {
            $mageOrder->addStatusToHistory ($orderStatus, $orderComment, $orderNotified)->save ();
        }
        catch (Mage_Core_Exception $e)
        {
            return $this->_error ($mhubOrderStatus, Mage::helper ('mhub')->__('Order cannot be delivered'), null /* order_cannot_delivered */);
        }

        $mhubOrderStatus->setOrderIncrementId ($mageOrder->getIncrementId ())
            ->setOrderId ($mageOrder->getId ())
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (Mage::helper ('mhub')->__('Order was successfully delivered'))
            ->setSyncedAt (date ('c'))
            ->save ()
        ;

        return array ('confirmado' => true);
    }

    protected function _error ($model, $message, $fault = null, $response = 400)
    {
        parent::_log ($model, $message, $fault, $response);

        $result = array(
            'codigoPedido' => $model->getOrderExternalId (),
            'mensagem'     => $message,
        );

        if ($response == 200)
        {
            $result ['confirmado'] = true;
        }

        return $result;
    }

    protected function _initMhubOrderStatus ($orderCodeEpicom)
    {
        $mhubOrderStatus = Mage::getModel ('mhub/order_status')->setOrderExternalId ($orderCodeEpicom)
            ->setOperation (Epicom_MHub_Helper_Data::OPERATION_IN)
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
            ->setUpdatedAt (date ('c'))
            ->save ()
        ;

        $mageOrder = Mage::getModel ('sales/order')->loadByAttribute (
            Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID, $orderCodeEpicom
        );

        if (!$mageOrder || !$mageOrder->getId ())
        {
            return $this->_error ($mhubOrderStatus, Mage::helper ('mhub')->__('Order not exists'), true /* order_not_exists */);
        }

        return array ($mhubOrderStatus, $mageOrder);
    }
}

