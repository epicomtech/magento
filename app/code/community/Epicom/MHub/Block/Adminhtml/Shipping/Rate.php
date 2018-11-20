<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Shipping_Rate extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct ()
    {
        $this->_controller = 'adminhtml_shipping_rate';
        $this->_blockGroup = 'mhub';
        $this->_headerText = Mage::helper('mhub')->__('Shipping Rates Manager');

        parent::__construct();

        $this->_removeButton ('add');
    }
}

