<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Sales observer
 */
class Epicom_MHub_Model_Observer
{
    public function salesQuoteCollectTotalsAfter ($observer)
    {
        $quote = $observer->getQuote ();

        $productSku = Mage::getStoreConfig ('mhub/product/id');

        $isEpicom = false;

        foreach ($quote->getAllItems () as $item)
        {
            if ($item->getData ($productSku) !== null)
            {
                $quote->setIsEpicom (1);

                return $this;
            }
        }

        $quote->setIsEpicom ($isEpicom);
    }

    public function salesQuoteItemSetProduct ($observer)
    {
        $quoteItem = $observer->getQuoteItem ();
        $product   = $observer->getProduct ();
/*
        $productId   = $product->getData (Mage::getStoreConfig ('mhub/product/id'));
        $productSku  = $product->getData (Mage::getStoreConfig ('mhub/product/sku'));
*/
        $productCode = $product->getData (Mage::getStoreConfig ('mhub/product/code'));
/*
        $quoteItem->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID,   $productId);
        $quoteItem->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_SKU,  $productSku);
*/
        $quoteItem->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_CODE, $productCode);
    }

    public function salesOrderPlaceBefore (Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder ();

        $productIdAttribute = Mage::getStoreConfig ('mhub/product/id');

        $orderItems = Mage::getResourceModel ('sales/order_item_collection')
            ->setOrderFilter ($order)
            ->addFieldToFilter ($productIdAttribute,    array ('notnull' => true))
            ->addFieldToFilter ('base_discount_amount', array ('gt' => 0))
        ;

        if ($orderItems->count() > 0)
        {
            Mage::throwException (Mage::helper ('rule')->__('Invalid discount amount.'));
        }
    }

    public function salesOrderPlaceAfter (Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder ();

        $productIdAttribute = Mage::getStoreConfig ('mhub/product/id');

        $orderItems = Mage::getResourceModel ('sales/order_item_collection')
            ->setOrderFilter ($order)
            ->addFieldToFilter ($productIdAttribute, array ('notnull' => true))
        ;

        if ($orderItems->count() > 0)
        {
            $order->setData (Epicom_MHub_Helper_Data::ORDER_ATTRIBUTE_IS_EPICOM, true)->save ();
        }
    }

    /**
     * Before adminhtml load layout event handler
     *
     * @param Varien_Event_Observer $observer
     */
    public function adminhtmlBlockHtmlBefore ($observer)
    {
        $block = $observer->getEvent ()->getBlock ();

        if ($block instanceof Epicom_MHub_Block_Adminhtml_Shipment_Grid)
        {
            $values = Mage::getModel ('mhub/adminhtml_system_config_source_event')->toArray ();

            unset ($values [Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_CREATED]);

            array_unshift ($values, array ('label' => null, 'value' => null));

            $block->getMassactionBlock ()->addItem ('mhub_shipment_event', array(
                'label' => Mage::helper ('mhub')->__('Shipment Event'),
                'url'   => $block->getUrl ('admin_mhub/adminhtml_shipment/event'),
                'additional' => array(
                    'visibility' => array(
                        'name'   => 'event',
                        'type'   => 'select',
                        'class'  => 'required-entry',
                        'label'  => Mage::helper ('mhub')->__('Status'),
                        'values' => $values
                    )
                )
            ));
        }
    }
}

