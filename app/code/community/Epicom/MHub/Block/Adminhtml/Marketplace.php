<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Marketplace extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct ()
	{
	    $this->_blockGroup = 'mhub';
	    $this->_controller = 'adminhtml_marketplace';

	    $this->_headerText     = Mage::helper ('mhub')->__('Marketplaces Manager');
	    $this->_addButtonLabel = Mage::helper ('mhub')->__('Add New Marketplace');

	    parent::__construct();

        $this->removeButton ('add');
	}
}

