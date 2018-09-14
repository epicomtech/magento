<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Provider_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	public function __construct()
	{
		parent::__construct();

		$this->setId ('mhub_provider_tabs');
		$this->setDestElementId ('edit_form');
		$this->setTitle (Mage::helper ('mhub')->__('Provider Information'));
	}

	protected function _beforeToHtml ()
	{
		$this->addTab ('form_section', array(
		    'label'   => Mage::helper ('mhub')->__('Provider Information'),
		    'title'   => Mage::helper ('mhub')->__('Provider Information'),
		    'content' => $this->getLayout ()->createBlock ('mhub/adminhtml_provider_edit_tab_form')->toHtml (),
		));

		return parent::_beforeToHtml ();
	}
}

