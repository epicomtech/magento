<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Order extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct ()
    {
        $this->_controller = 'adminhtml_order';
        $this->_blockGroup = 'mhub';
        $this->_headerText = Mage::helper('mhub')->__('Orders Manager');

        parent::__construct();

        $this->_removeButton ('add');
    }
}

