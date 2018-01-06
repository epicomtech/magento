<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Attributegroup_New_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareLayout ()
    {
        $this->setChild ('continue_button',
            $this->getLayout ()->createBlock ('adminhtml/widget_button')
                ->setData (array(
                    'label'   => Mage::helper ('mhub')->__('Continue'),
                    'onclick' => "setSettings('" . $this->getContinueUrl () . "', 'attribute_set_id')",
                    'class'   => 'save'
                    ))
                );

        return parent::_prepareLayout ();
    }

	protected function _prepareForm ()
	{
		$form = new Varien_Data_Form ();
		$this->setForm ($form);

		$fieldset = $form->addFieldset ('attributegroup_fieldset', array ('legend' => Mage::helper ('mhub')->__('Attribute Group Settings')));

        $entityType = Mage::getModel ('catalog/product')->getResource ()->getEntityType ();

		$fieldset->addField ('attribute_set_id', 'select', array(
		    'label'     => Mage::helper ('mhub')->__('Attribute Set'),
            'title'     => Mage::helper ('mhub')->__('Attribute Set'),
		    'name'      => 'set',
            'class'     => 'validate-select',
            'required'  => true,
		    'value'     => $entityType->getDefaultAttributeSetId (),
		    'values'    => Mage::getResourceModel ('eav/entity_attribute_set_collection')
                ->setEntityTypeFilter ($entityType->getId ())
                ->load ()
                ->toOptionArray (),
		));

		$fieldset->addField ('continue_button', 'note', array(
		    'text' => $this->getChildHtml ('continue_button'),
		));

		if (Mage::registry ('current_attributegroup'))
		{
		    $form->setValues (Mage::registry ('current_attributegroup')->getData ());
		}

		return parent::_prepareForm ();
	}

    public function getContinueUrl ()
    {
        $result = $this->getUrl ('*/*/edit', array(
            '_current' => true,
            'set'      => '{{attribute_set}}'
        ));

        return $result;
    }
}

