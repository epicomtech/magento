<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Nf extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct ()
	{
	    $this->_blockGroup = 'mhub';
	    $this->_controller = 'adminhtml_nf';

	    $this->_headerText     = Mage::helper ('mhub')->__('NF Manager');
	    $this->_addButtonLabel = Mage::helper ('mhub')->__('Add New NF');

	    parent::__construct();
	}
}

