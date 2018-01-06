<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Attributegroup_New_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct ()
    {
        parent::__construct ();

        $this->setId ('attributegroup_tabs');
        $this->setDestElementId ('new_form');
        $this->setTitle (Mage::helper ('mhub')->__('Attribute Group Information'));
    }

    protected function _beforeToHtml ()
    {
        $this->addTab ('attributegroup_section', array(
            'label'   => Mage::helper ('mhub')->__('Settings'),
            'title'   => Mage::helper ('mhub')->__('Settings'),
            'content' => $this->getLayout ()->createBlock ('mhub/adminhtml_attributegroup_new_tab_form')->toHtml (),
        ));

        return parent::_beforeToHtml ();
    }
}

