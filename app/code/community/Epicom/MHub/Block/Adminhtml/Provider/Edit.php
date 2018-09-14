<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Provider_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct ()
	{
		parent::__construct ();

		$this->_blockGroup = 'mhub';
		$this->_controller = 'adminhtml_provider';
		$this->_objectId   = 'entity_id';

		$this->_updateButton ('save',   'label', Mage::helper ('mhub')->__('Save Provider'));
		$this->_updateButton ('delete', 'label', Mage::helper ('mhub')->__('Delete Provider'));

		$this->_addButton ('saveandcontinue', array(
			'label'   => Mage::helper ('mhub')->__('Save And Continue Edit'),
			'onclick' => 'saveAndContinueEdit ()',
			'class'   => 'save',
		), -100);

		$this->_formScripts [] = "
			function saveAndContinueEdit () {
				editForm.submit ($('edit_form').action + 'back/edit/');
			}
		";
	}

	public function getHeaderText ()
	{
		if (Mage::registry ('provider_data') && Mage::registry ('provider_data')->getId ())
        {
		    return Mage::helper ('mhub')->__("Edit Provider '%s'", $this->htmlEscape (Mage::registry ('provider_data')->getId ()));
		} 
		else
        {
		     return Mage::helper ('mhub')->__('Add New Provider');
		}
	}
}

