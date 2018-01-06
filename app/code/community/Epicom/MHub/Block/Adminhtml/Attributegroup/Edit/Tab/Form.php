<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Block_Adminhtml_Attributegroup_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm ()
	{
		$form = new Varien_Data_Form ();
		$this->setForm ($form);

		$fieldset = $form->addFieldset ('attributegroup_fieldset', array ('legend' => Mage::helper ('mhub')->__('Attribute Group Settings')));

        $form->addField ('attribute_set_id', 'hidden', array(
            'name' => 'attribute_set_id',
        ));

        $attributeSetId = Mage::app ()->getRequest ()->getParam ('set');
        if (empty ($attributeSetId))
        {
            $attributeGroup = Mage::registry ('current_attributegroup');
            if ($attributeGroup && $attributeGroup->getId ())
            {
                $attributeSetId = $attributeGroup->getAttributeSetId ();
            }
        }

        $attributeSet = Mage::getModel ('eav/entity_attribute_set')->load ($attributeSetId);

        $fieldset->addField ('attribute_set_name', 'note', array(
            'label' => Mage::helper ('mhub')->__('Attribute Set'),
            'text'  => $attributeSet->getAttributeSetName (),
        ));

		$fieldset->addField ('group_name', 'text', array(
		    'label'    => Mage::helper ('mhub')->__('Group Name'),
            'title'    => Mage::helper ('mhub')->__('Group Name'),
		    'name'     => 'group_name',
            'class'    => 'required-entry',
            'required' => true
		));

        $entityType = Mage::getModel ('catalog/product')->getResource ()->getEntityType ();

        $collection = Mage::getResourceModel ('eav/entity_attribute_collection')
            ->setEntityTypeFilter ($entityType->getId ())
            ->setAttributeSetFilter ($attributeSetId)
            ->setFrontendInputTypeFilter (array ('select', 'boolean'))
            ->addHasOptionsFilter ()
        ;

        $collection->getSelect ()->reset (Zend_Db_Select::COLUMNS)
            ->columns (array ('id' => 'attribute_code', 'name' => 'frontend_label'))
            ->order ('frontend_label')
        ;

		$fieldset->addField ('attribute_codes', 'multiselect', array(
		    'label'    => Mage::helper ('mhub')->__('Attributes'),
            'title'    => Mage::helper ('mhub')->__('Attributes'),
		    'name'     => 'attribute_codes',
		    'values'   => $collection->toOptionArray (),
            'class'    => 'validate-select',
            'required' => true,
            'after_element_html' => '<p class="nm"><small>select, boolean</small></p>',
		));

        $attributeGroup = Mage::registry ('current_attributegroup');
		if ($attributeGroup && $attributeGroup->getId ())
		{
		    $form->setValues ($attributeGroup->getData ());
		}
        else
        {
            $form->setValues (array ('attribute_set_id' => $attributeSetId));
        }

		return parent::_prepareForm ();
	}
}

