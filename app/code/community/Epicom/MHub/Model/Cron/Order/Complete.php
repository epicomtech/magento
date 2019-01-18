<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Order_Complete extends Epicom_MHub_Model_Cron_Abstract
{
    public function run ()
    {
        if (!$this->getHelper ()->isMarketplace ())
        {
            return false;
        }

        $deliveredStatus = Mage::getStoreConfig ('mhub/shipment/delivered_status');

        $collection = Mage::getModel ('sales/order')->getCollection ()
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_IS_EPICOM, array ('notnull' => true))
            ->addAttributeToFilter ('main_table.status', array ('in' => array ($deliveredStatus)))
        ;

        foreach ($collection as $order)
        {
            $itemsQty     = 0;
            $deliveredQty = 0;

            $itemsCollection = Mage::getModel ('sales/order_item')->getCollection ()
                ->setOrderFilter ($order)
                ->filterByTypes (array (Mage_Catalog_Model_Product_Type::TYPE_SIMPLE))
                ->filterByParent (null)
                /*
                ->addFieldToFilter ('qty_delivered', array ('gt' => 0))
                */
            ;

            if (!$itemsCollection->count ()) continue;

            foreach ($itemsCollection as $item)
            {
                if ($item->getParentItemId ()) continue;

                ++ $itemsQty;

                if ($item->getQtyDelivered () > 0) ++ $deliveredQty;
            }

            if ($deliveredQty == $itemsQty)
            {
                $orderStatus   = Mage::getStoreConfig ('mhub/complete/delivered_status');
                $orderComment  = Mage::getStoreConfig ('mhub/complete/delivered_comment');
                $orderNotified = Mage::getStoreConfigFlag ('mhub/complete/delivered_notified');

                $order->addStatusToHistory ($orderStatus, $orderComment, $orderNotified)->save ();

                if ($orderNotified)
                {
                    $order->queueOrderUpdateEmail (true, $orderComment);
                }
            }
        }

        return true;
    }
}

