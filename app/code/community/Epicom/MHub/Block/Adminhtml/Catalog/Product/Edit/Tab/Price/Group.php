<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Adminhtml group price item renderer
 */
class Epicom_MHub_Block_Adminhtml_Catalog_Product_Edit_Tab_Price_Group
    extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Group_Abstract
{
    /**
     * Initialize block
     */
    public function __construct()
    {
        $this->setTemplate('epicom/mhub/catalog/product/edit/price/marketplace.phtml');
    }

    /**
     * Prepare global layout
     *
     * Add "Add Group Price" button to layout
     *
     * @return Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Group
     */
    protected function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'   => Mage::helper('catalog')->__('Add Marketplace Price'),
                'onclick' => 'return marketplacePriceControl.addItem()',
                'class'   => 'add'
            ))
        ;

        $button->setName('add_marketplace_price_item_button');

        $this->setChild('add_button', $button);

        return parent::_prepareLayout();
    }

    public function getMarketplaceChannels()
    {
        $result = array ();

        foreach (Mage::getModel ('mhub/marketplace')->getCollection () as $channel)
        {
            $result [$channel->getExternalId ()] = $channel->getName ();
        }

        return $result;
    }

    public function getYesNo()
    {
        return array(
            1 => Mage::helper('adminhtml')->__('Yes'),
            2 => Mage::helper('adminhtml')->__('No'),
        );
    }

    /**
     * Show website column and switcher for group price table
     *
     * @return bool
     */
    public function isMultiWebsites()
    {
        return true;
    }

    public function getDefaultWebsite()
    {
        return 0;
    }

    public function getDefaultCustomerGroup()
    {
        return 0;
    }

    public function getDefaultMarketplaceChannel()
    {
        return 0;
    }
}

