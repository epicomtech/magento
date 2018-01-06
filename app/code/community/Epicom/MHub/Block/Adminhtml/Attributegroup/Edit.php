<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Attributegroup_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct ()
	{
        parent::__construct ();

		$this->_objectId   = 'id';
		$this->_blockGroup = 'mhub';
		$this->_controller = 'adminhtml_attributegroup';
	}

    public function getHeaderText ()
    {
        $attributeGroup = Mage::registry ('current_attributegroup');
        if ($attributeGroup && $attributeGroup->getId ())
        {
            return Mage::helper ('mhub')->__('Edit Attribute Group');
        }
        else
        {
            return Mage::helper ('mhub')->__('New Attribute Group');
        }
    }
}

