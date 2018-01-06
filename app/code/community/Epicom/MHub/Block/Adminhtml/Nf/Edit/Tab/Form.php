<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Nf_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm ()
	{
		$form = new Varien_Data_Form ();
		$this->setForm ($form);

		$fieldset = $form->addFieldset ('mhub_form', array ('legend' => Mage::helper ('mhub')->__('NF Information')));

		$fieldset->addField ('order_increment_id', 'text', array(
	        'label'    => Mage::helper ('mhub')->__('Order Increment ID'),
	        'class'    => 'required-entry validate-alphanum',
	        'name'     => 'order_increment_id',
	        'required' => true,
		));
		$fieldset->addField ('number', 'text', array(
	        'label'    => Mage::helper ('mhub')->__('Number'),
	        'class'    => 'required-entry validate-number',
	        'name'     => 'number',
	        'required' => true,
		));
		$fieldset->addField ('series', 'text', array(
	        'label'    => Mage::helper ('mhub')->__('Series'),
	        'class'    => 'required-entry validate-number',
	        'name'     => 'series',
	        'required' => true,
		));
		$fieldset->addField ('access_key', 'text', array(
		    'label'    => Mage::helper ('mhub')->__('Access Key'),
		    'class'    => 'required-entry validate-number',
		    'name'     => 'access_key',
		    'required' => true,
		));
        $fieldset->addField ('link', 'text', array(
            'label'    => Mage::helper ('mhub')->__('Link'),
            'name'     => 'link',
            'class'    => 'required-entry validate-url',
            'required' => true,
        ));
        $fieldset->addField ('issued_at', 'date', array(
            'label'    => Mage::helper ('mhub')->__('Issued At'),
            'name'     => 'issued_at',
            'class'    => 'required-entry validate-date',
            'required' => true,
            'image'    => $this->getSkinUrl ('images/grid-cal.gif'),
            'format'   => Mage::app ()->getLocale ()->getDateFormat (Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
        ));

		if (Mage::getSingleton ('adminhtml/session')->getNFData ())
		{
			$form->setValues (Mage::getSingleton ('adminhtml/session')->getNFData ());

			Mage::getSingleton ('adminhtml/session')->setNFData (null);
		}
		else if (Mage::registry ('nf_data'))
        {
		    $form->setValues (Mage::registry ('nf_data')->getData ());
		}

		return parent::_prepareForm();
	}
}

