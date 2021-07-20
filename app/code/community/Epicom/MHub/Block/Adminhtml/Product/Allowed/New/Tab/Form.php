<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Product_Allowed_New_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm ()
	{
		$form = new Varien_Data_Form ();
		$this->setForm ($form);

		$fieldset = $form->addFieldset ('product_allowed_new_fieldset', array ('legend' => Mage::helper ('mhub')->__('Choose the CSV file')));

		$fieldset->addField ('csv', 'file', array(
		    'label'     => Mage::helper ('mhub')->__('File'),
            'title'     => Mage::helper ('mhub')->__('File'),
		    'name'      => 'csv',
            'class'    => 'required-entry',
            'required'  => true,
		));

		return parent::_prepareForm ();
	}
}

