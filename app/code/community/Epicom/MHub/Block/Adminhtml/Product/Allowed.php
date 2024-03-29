<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Product_Allowed extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct ()
    {
        $this->_controller = 'adminhtml_product_allowed';
        $this->_blockGroup = 'mhub';
        $this->_headerText = Mage::helper('mhub')->__('Products Allowed Manager');
        $this->_addButtonLabel = Mage::helper('customer')->__('Upload New CSV');

        parent::__construct();
    }
}

