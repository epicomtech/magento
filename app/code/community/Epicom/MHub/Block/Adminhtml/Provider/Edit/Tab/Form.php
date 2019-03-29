<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Provider_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm ()
	{
		$form = new Varien_Data_Form ();
		$this->setForm ($form);

		$fieldset = $form->addFieldset ('mhub_form', array ('legend' => Mage::helper ('mhub')->__('Provider Information')));

		$fieldset->addField ('external_id', 'text', array(
	        'label'    => Mage::helper ('mhub')->__('External ID'),
	        'class'    => 'required-entry validate-digits',
	        'name'     => 'external_id',
	        'required' => true,
		));
		$fieldset->addField ('code', 'text', array(
	        'label'    => Mage::helper ('mhub')->__('Code'),
	        'class'    => 'required-entry validate-alphanum',
	        'name'     => 'code',
	        'required' => true,
		));
		$fieldset->addField ('name', 'text', array(
	        'label'    => Mage::helper ('mhub')->__('Name'),
	        'class'    => 'required-entry', // 'validate-alphanum-with-spaces',
	        'name'     => 'name',
	        'required' => true,
		));
		$fieldset->addField ('use_categories', 'select', array(
		    'label'    => Mage::helper ('mhub')->__('Use Categories'),
		    'class'    => 'required-entry validate-select',
		    'name'     => 'use_categories',
		    'required' => true,
            'type'     => 'options',
            'options'  => Mage::getModel ('adminhtml/system_config_source_yesno')->toArray (),
		));
		$fieldset->addField ('is_service', 'select', array(
		    'label'    => Mage::helper ('mhub')->__('Is Service'),
		    'class'    => 'required-entry validate-select',
		    'name'     => 'is_service',
		    'required' => true,
		    'type'     => 'options',
		    'options'  => Mage::getModel ('adminhtml/system_config_source_yesno')->toArray (),
		));

		if (Mage::getSingleton ('adminhtml/session')->getProviderData ())
		{
			$form->setValues (Mage::getSingleton ('adminhtml/session')->getProviderData ());

			Mage::getSingleton ('adminhtml/session')->setProviderData (null);
		}
		else if (Mage::registry ('provider_data'))
        {
		    $form->setValues (Mage::registry ('provider_data')->getData ());
		}

		return parent::_prepareForm();
	}
}

