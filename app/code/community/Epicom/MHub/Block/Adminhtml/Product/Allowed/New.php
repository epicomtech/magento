<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Product_Allowed_New extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct ()
	{
        parent::__construct ();

		$this->_objectId   = 'id';
		$this->_blockGroup = 'mhub';
		$this->_controller = 'adminhtml_product_allowed';
        $this->_mode       = 'new';

        $this->_removeButton ('reset');
	}

    public function getHeaderText ()
    {
        return Mage::helper ('mhub')->__('Upload New CSV');
    }
}

