<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Attributegroup_New extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct ()
	{
        parent::__construct ();

		$this->_objectId   = 'id';
		$this->_blockGroup = 'mhub';
		$this->_controller = 'adminhtml_attributegroup';
        $this->_mode       = 'new';

        $this->_removeButton ('save');

        $this->_formScripts [] = '
            var productTemplateSyntax = /(^|.|\r|\n)({{(\w+)}})/;

            function setSettings (urlTemplate, setElement) {
                var template = new Template (urlTemplate, productTemplateSyntax);

                setLocation (template.evaluate ({attribute_set:$F(setElement)}));
            }
        ';
	}

    public function getHeaderText ()
    {
        return Mage::helper ('mhub')->__('New Attribute Group');
    }
}

