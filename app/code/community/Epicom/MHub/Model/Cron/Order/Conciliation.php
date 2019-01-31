<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2019 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Order_Conciliation extends Epicom_MHub_Model_Cron_Abstract
{
    const ORDERS_INFO_METHOD = 'pedidos/{orderId}';

    public function run ()
    {
        if (!$this->getHelper ()->isMarketplace ())
        {
            return false;
        }

        // $filterStatus = Mage::getStoreConfig ('mhub/order/reserve_filter');

        $collection = Mage::getModel ('sales/order')->getCollection ()
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_IS_EPICOM, array ('notnull' => true))
            // ->addAttributeToFilter ('main_table.status', array ('in' => array ($reserveFilter)))
        ;

        $filename = tempnam ('/tmp', 'epicom_mhub-order_conciliation-');

        $this->_putDatetime ($filename);

        file_put_contents ($filename, Mage::helper ('mhub')->__('Epicom MHub Conciliation Report: %s orders', $collection->getSize ()) . PHP_EOL, FILE_APPEND);

        if ($collection->count () > 0)
        {
            Mage::getSingleton ('core/resource_iterator')->walk ($collection->getSelect (), array (function ($args) {
                $filename = $args ['filename'];

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
                        throw new Exception (Mage::helper ('mhub')->__('Order info is empty!'));
                    }

                    if (strcmp ($response ['codigoPedidoMarketplace'], $order->getIncrementId ()))
                    {
                        throw new Exception (Mage::helper ('mhub')->__('Order number is different!'));
                    }
                }
                catch (Exception $e)
                {
                    file_put_contents ($filename, Mage::helper ('mhub')->__('Order %s [ %s ] ERROR: %s', $order->getIncrementId (), $extOrderId, $e->getMessage () . PHP_EOL), FILE_APPEND);
                }
            }), array ('filename' => $filename));
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

