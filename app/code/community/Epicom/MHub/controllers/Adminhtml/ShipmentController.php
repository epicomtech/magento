<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Adminhtml_ShipmentController extends Mage_Adminhtml_Controller_Action
{
    const SHIPMENTS_EVENTS_POST_METHOD = 'pedidos/{orderId}/entregas/{shipmentId}/eventos';

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('epicom/mhub/shipment');
    }

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('epicom/mhub/shipment')->_addBreadcrumb(Mage::helper('adminhtml')->__('Shipments Manager'),Mage::helper('adminhtml')->__('Shipments Manager'));

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('MHub'));
        $this->_title($this->__('Manage Shipments'));

        $this->_initAction();
        $this->renderLayout();
    }

    public function massRemoveAction()
    {
        try
        {
            $ids = $this->getRequest()->getPost('entity_ids', array());
            foreach ($ids as $id)
            {
                $model = Mage::getModel('mhub/shipment');
                $model->setId($id)->delete();
            }

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item(s) was successfully removed'));
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }

    public function eventAction()
    {
        try
        {
            $ids = $this->getRequest()->getPost('entity_ids', array());
            foreach ($ids as $id)
            {
                $model = Mage::getModel('mhub/shipment')->load($id);
/*
                if ($model->getEvent () != Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_CREATED)
                {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('mhub')->__('Invalid shipment status for ID: %s', $model->getId ()));

                    return $this->_redirect('* / * /');
                }
*/
                $mageOrder = Mage::getModel ('sales/order')->load ($model->getOrderId ());

                $orderStatus   = null;
                $orderComment  = null;
                $orderNotified = false;

                $event = $this->getRequest()->getPost('event');
                switch ($event)
                {
                    case Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_NF:
                    {
                        $mhubNf = Mage::getModel ('mhub/nf')->load ($mageOrder->getIncrementId (), 'order_increment_id');
                        $mhubNf->setStatus (Epicom_Mhub_Helper_Data::STATUS_OKAY)
                            ->setSyncedAt (date ('c'))
                            ->save ()
                        ;

                        $orderStatus   = Mage::getStoreConfig ('mhub/shipment/nf_status');
                        $orderComment  = Mage::getStoreConfig ('mhub/shipment/nf_comment');
                        $orderNotified = Mage::getStoreConfigFlag ('mhub/shipment/nf_notified');

                        break;
                    }
                    case Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_SENT:
                    {
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

                $post = array (
                    'tipo'      => $event,
                    'descricao' => $orderComment,
                    'data'      => date ('c'),
                );

                $extOrderId    = $model->getExternalOrderId ();
                $extShipmentId = $model->getExternalShipmentId ();

                $shipmentsEventsPostMethod = str_replace (array ('{orderId}', '{shipmentId}'), array ($extOrderId, $extShipmentId), self::SHIPMENTS_EVENTS_POST_METHOD);

                $result = Mage::helper ('mhub')->api ($shipmentsEventsPostMethod, $post);

                $shipment = clone $model;
                $shipment->setId (null)
                    ->setEvent ($event)
                    ->setOperation (Epicom_MHub_Helper_Data::OPERATION_OUT)
                    ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
                    ->setMessage (new Zend_Db_Expr ('NULL'))
                    ->setSyncedAt (date ('c'))
                    ->save ()
                ;

                /**
                 * Order Status
                 */
                $mhubOrder = Mage::getModel ('mhub/order_status')
                    ->setOrderId ($shipment->getOrderId ())
                    ->setOrderIncrementId ($shipment->getOrderIncrementId ())
                    ->setOrderExternalId ($extOrderId)
                    ->setOperation (Epicom_MHub_Helper_Data::OPERATION_OUT)
                    ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
                    ->setMessage (new Zend_Db_Expr ('NULL'))
                    ->setUpdatedAt (date ('c'))
                ;

                $mageOrder->addStatusToHistory ($orderStatus, $orderComment, $orderNotified)->save ();

                $mhubOrder->setSyncedAt (date ('c'))->save (); // the_end
            }

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item(s) was successfully updated'));
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }
}

