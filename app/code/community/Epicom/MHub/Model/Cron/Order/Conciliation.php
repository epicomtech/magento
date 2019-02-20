<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Order_Conciliation extends Epicom_MHub_Model_Cron_Abstract
{
    const ORDERS_INFO_METHOD = 'pedidos/{orderId}';
    const SHIPMENTS_INFO_METHOD = 'pedidos/{orderId}/entregas';

    protected $_mhubOrderConfig = null;

    protected $_orderStatuses = array ();

    public function _construct ()
    {
        $this->_mhubOrderConfig = Mage::getStoreConfig ('mhub/order');

        $this->_orderStatuses = array(
            Epicom_MHub_Helper_Data::API_ORDER_STATUS_RESERVED => array(
                $this->_mhubOrderConfig ['reserve_filter'],
            ),
            Epicom_MHub_Helper_Data::API_ORDER_STATUS_CONFIRMED => array(
                $this->_mhubOrderConfig ['confirm_filter'],
                $this->_mhubOrderConfig ['erp_filter'],
            ),
            Epicom_MHub_Helper_Data::API_ORDER_STATUS_APPROVED => array(
                $this->_mhubOrderConfig ['confirm_filter'],
                $this->_mhubOrderConfig ['erp_filter'],
            ),
            Epicom_MHub_Helper_Data::API_ORDER_STATUS_CANCELED => array(
                $this->_mhubOrderConfig ['cancel_filter'],
            ),
            Epicom_MHub_Helper_Data::API_ORDER_STATUS_SHIPPED  => array(
                $this->_mhubOrderConfig ['sent_filter'],
                Mage::getStoreConfig ('mhub/shipment/sent_status'),
            ),
            Epicom_MHub_Helper_Data::API_ORDER_STATUS_DELIVERED => array(
                Mage::getStoreConfig ('mhub/shipment/delivered_status'),
                Mage::getStoreConfig ('mhub/complete/delivered_status'),
            ),
        );
    }

    public function run ()
    {
        if (!$this->getHelper ()->isMarketplace ())
        {
            return false;
        }

        $cancelFilter = Mage::getStoreConfig ('mhub/order/cancel_filter');

        $collection = Mage::getModel ('sales/order')->getCollection ()
            /*
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_IS_EPICOM, array ('notnull' => true))
            */
            ->addAttributeToFilter ('main_table.status', array ('neq' => $cancelFilter))
        ;

        $collection->getSelect ()->where ('is_epicom IS NOT NULL OR ext_order_id IS NOT NULL');

        $collection->getSelect ()
            ->reset (Zend_Db_Select::COLUMNS)
            ->columns (array (
                'entity_id',
                'increment_id',
                'ext_order_id',
                'status',
            ))
        ;

        $filename = tempnam ('/tmp', 'epicom_mhub-order_conciliation-');

        $this->_putDatetime ($filename);

        file_put_contents ($filename, Mage::helper ('mhub')->__('Epicom MHub Conciliation Report: %s orders', $collection->getSize ()) . PHP_EOL, FILE_APPEND);

        $productIdAttribute = Mage::getStoreConfig ('mhub/product/id');

        if ($collection->count () > 0)
        {
            Mage::getSingleton ('core/resource_iterator')->walk ($collection->getSelect (), array (function ($args) {
                $filename = $args ['filename'];

                $productIdAttribute = $args ['product_id_attribute'];

                $order = Mage::getModel ('sales/order')
                    ->setData ($args ['row'])
                ;

                try
                {
                    $extOrderId = $order->getData (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_EXT_ORDER_ID);

                    if (empty ($extOrderId))
                    {
                        throw new Exception (Mage::helper ('mhub')->__('Order external number is empty!'));
                    }

                    $ordersInfoMethod = str_replace ('{orderId}', $extOrderId, self::ORDERS_INFO_METHOD);

                    $response = $this->getHelper ()->api ($ordersInfoMethod);

                    if (empty ($response))
                    {
                        throw new Exception (Mage::helper ('mhub')->__('Epicom information is empty!'));
                    }

                    if (strcmp ($response->codigoPedidoMarketplace, $order->getIncrementId ()))
                    {
                        return; // splitted_order

                        throw new Exception (Mage::helper ('mhub')->__('Epicom number is different: %s', $response->codigoPedidoMarketplace));
                    }

                    if (!in_array ($response->status, array_keys ($this->_orderStatuses)))
                    {
                        throw new Exception (Mage::helper ('mhub')->__('Unknown Epicom order status: %s', $response->status));
                    }
                    else
                    {
                        switch ($response->status)
                        {
                            case Epicom_MHub_Helper_Data::API_ORDER_STATUS_RESERVED:
                            {
                                if (in_array ($order->getStatus (), array (
                                    $this->_mhubOrderConfig ['confirm_filter'], $this->_mhubOrderConfig ['erp_filter']
                                )))
                                {
                                    $mhubOrderStatus = Mage::getModel ('mhub/order_status')->load ($order->getId (), 'order_id');

                                    if ($mhubOrderStatus && $mhubOrderStatus->getId ()) $mhubOrderStatus->delete ();
                                }

                                break;
                            }
                            case Epicom_MHub_Helper_Data::API_ORDER_STATUS_DELIVERED:
                            {
                                $shipmentsInfoMethod = str_replace ('{orderId}', $extOrderId, self::SHIPMENTS_INFO_METHOD);

                                $response = $this->getHelper ()->api ($shipmentsInfoMethod);

                                if (empty ($response)) break;

                                foreach ($response as $shipment)
                                {
                                    foreach ($shipment->statusEntrega as $status)
                                    {
                                        if (!strcmp ($status->tipo, Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_DELIVERED))
                                        {
                                            $orderItems = Mage::getResourceModel ('sales/order_item_collection')
                                                ->setOrderFilter ($order)
                                                ->addFieldToFilter ($productIdAttribute, array ('notnull' => true))
                                                ->filterByTypes (array (
                                                    Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                                                    Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
                                                ))
                                                ->addFieldToFilter ('qty_delivered', array ('null' => true))
                                            ;

                                            $orderItems->getSelect ()
                                                ->reset (Zend_Db_Select::COLUMNS)
                                                ->columns (array (
                                                    'item_id',
                                                    'qty_ordered',
                                                    $productIdAttribute,
                                                ))
                                            ;

                                            foreach ($shipment->skus as $sku)
                                            {
                                                foreach ($orderItems as $item)
                                                {
                                                    $productId    = $item->getData ($productIdAttribute);
                                                    $productQty   = $item->getQtyOrdered ();

                                                    if (!strcmp ($productId, $sku->id) && $productQty == $sku->quantidade)
                                                    {
                                                        $item->setData ('qty_delivered', $sku->quantidade)->save ();

                                                        break;
                                                    }
                                                }
                                            }

                                            break;
                                        }
                                    }
                                }

                                break;
                            }
                            default:
                            {
                                // nothing_here

                                break;
                            }
                        }

                    foreach ($this->_orderStatuses as $status => $values)
                    {
                        if (!strcmp ($response->status, $status))
                        {
                            if (!in_array ($order->getStatus (), $values))
                            {
                                throw new Exception (Mage::helper ('mhub')->__('Epicom order status is different: %s <-> %s', $status, $order->getStatusLabel ()));
                            }
                        }
                    }

                    } // in_array

                    $orderItems = Mage::getResourceModel ('sales/order_item_collection')
                        ->setOrderFilter ($order)
                        ->addFieldToFilter ($productIdAttribute, array ('notnull' => true))
                        ->filterByTypes (array (
                            Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                            Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
                        ))
                    ;

                    $orderItems->getSelect ()
                        ->reset (Zend_Db_Select::COLUMNS)
                        ->columns (array (
                            'item_id',
                            'qty_ordered',
                            $productIdAttribute,
                        ))
                    ;

                    foreach ($orderItems as $item)
                    {
                        $productId    = $item->getData ($productIdAttribute);
                        $productQty   = $item->getQtyOrdered ();
                        $productFound = false;

                        foreach ($response->itens as $_item)
                        {
                            if ($productId == $_item->id)
                            {
                                if ($productQty != $_item->quantidade)
                                {
                                    throw new Exception (Mage::helper ('mhub')->__(
                                        'Product SKU %s has wrong quantity! Magento: %s Epicom: %s',
                                        $productId, $productQty, $_item->quantidade
                                    ));
                                }

                                $productFound = true;

                                break;
                            }
                        }

                        if (!$productFound)
                        {
                            /*
                            throw new Exception (Mage::helper ('mhub')->__('Product not found! SKU: %s Qty: %s', $productId, $productQty));
                            */

                            $message = Mage::helper ('mhub')->__('Product not found! SKU: %s Qty: %s', $productId, $productQty);

                            file_put_contents ($filename, Mage::helper ('mhub')->__('Order %s [ %s ] ERROR: %s',
                                $order->getIncrementId (), $extOrderId, $message . PHP_EOL
                            ), FILE_APPEND);
                        }
                    }
                }
                catch (Exception $e)
                {
                    file_put_contents ($filename, Mage::helper ('mhub')->__('Order %s [ %s ] ERROR: %s', $order->getIncrementId (), $extOrderId, $e->getMessage () . PHP_EOL), FILE_APPEND);
                }
            }), array ('filename' => $filename, 'product_id_attribute' => $productIdAttribute));
        }

        if (substr_count (file_get_contents ($filename), PHP_EOL) == 2)
        {
            file_put_contents ($filename, Mage::helper ('mhub')->__('No pending orders/products to send to Epicom.') . PHP_EOL, FILE_APPEND);
        }

        $this->_putDatetime ($filename);

        $emailRecipient = Mage::getStoreConfig ('mhub/report/email_recipient');

        if (!empty ($emailRecipient))
        {
            $emailIdentity = Mage::getStoreConfig ('mhub/report/email_identity');

            $emailTemplate = Mage::getModel ('core/email_template')
                ->loadDefault (Mage::getStoreConfig ('mhub/report/email_template'), 'en_US')
                ->setSenderName (Mage::getStoreConfig ("trans_email/ident_{$emailIdentity}/name"))
                ->setSenderEmail (Mage::getStoreConfig ("trans_email/ident_{$emailIdentity}/email"))
            ;

            $emailTemplate->send ($emailRecipient, null, array (
                'email_subject' => Mage::getStoreConfig ('mhub/report/email_subject'),
                'website_url'   => sprintf ("http://%s%s", $_SERVER ['HTTP_HOST'], $_SERVER ['REQUEST_URI']),
                'error_message' => file_get_contents ($filename),
            ));
        }

        unlink ($filename);
    }

    private function _putDatetime ($filename)
    {
        $now = Mage::getModel ('core/date')->date ('d/m/Y H:i:s');

        file_put_contents ($filename, sprintf ('---------- %s ----------', $now) . PHP_EOL, FILE_APPEND);
    }
}

