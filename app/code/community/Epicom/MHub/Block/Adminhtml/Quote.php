<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Quote extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct ()
	{
	    $this->_blockGroup = 'mhub';
	    $this->_controller = 'adminhtml_quote';

	    $this->_headerText = Mage::helper ('mhub')->__('Quotes Manager');

	    parent::__construct ();

        $this->removeButton ('add');
	}
}

