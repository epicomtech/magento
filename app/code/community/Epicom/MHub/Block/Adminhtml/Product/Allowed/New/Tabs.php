<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Product_Allowed_New_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct ()
    {
        parent::__construct ();

        $this->setId ('productAllowedNewTabs');
        $this->setDestElementId ('edit_form');
        $this->setTitle (Mage::helper ('mhub')->__('Upload New CSV'));
    }

    protected function _beforeToHtml ()
    {
        $this->addTab ('product_allowed_new_section', array(
            'label'   => Mage::helper ('mhub')->__('Settings'),
            'title'   => Mage::helper ('mhub')->__('Settings'),
            'content' => $this->getLayout ()->createBlock ('mhub/adminhtml_product_allowed_new_tab_form')->toHtml (),
        ));

        return parent::_beforeToHtml ();
    }
}

