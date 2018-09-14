<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Provider extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct ()
	{
	    $this->_blockGroup = 'mhub';
	    $this->_controller = 'adminhtml_provider';

	    $this->_headerText     = Mage::helper ('mhub')->__('Provider Manager');
	    $this->_addButtonLabel = Mage::helper ('mhub')->__('Add New Provider');

	    parent::__construct();
	}
}

