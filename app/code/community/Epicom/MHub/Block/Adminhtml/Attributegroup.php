<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Attributegroup extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct ()
    {
        $this->_controller = 'adminhtml_attributegroup';
        $this->_blockGroup = 'mhub';

        $this->_headerText     = Mage::helper('mhub')->__('Attribute Groups Manager');
        $this->_addButtonLabel = Mage::helper ('mhub')->__('Add New Attribute Group');

        return parent::__construct ();
    }
}

