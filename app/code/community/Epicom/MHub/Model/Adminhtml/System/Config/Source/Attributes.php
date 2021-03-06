<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Adminhtml_System_Config_Source_Attributes
    extends Epicom_MHub_Model_Adminhtml_System_Config_Source_Abstract
{
    protected $_attributeField = 'attribute_id';

    public function getAttributeCollection ()
    {
        $collection = Mage::getResourceModel ('eav/entity_attribute_collection')
            ->setEntityTypeFilter ($this->getEntityTypeId ())
            ->addFieldToFilter ('is_visible', true)
        ;
        $collection->getSelect()->order('frontend_label');

        return $collection;
    }

    public function getAttributeSetCollection ()
    {
        $collection = Mage::getResourceModel ('eav/entity_attribute_set_collection')
            ->setEntityTypeFilter ($this->getEntityTypeId ())
        ;

        return $collection;
    }

    public function getEntityTypeId ()
    {
        $item = Mage::getModel ($this->_entityType);

        return $item->getResource ()->getTypeId ();
    }

    public function toOptionArray ()
    {
        $result = null;

        $attributeCollection = $this->getAttributeCollection ();
        foreach ($attributeCollection as $attribute)
        {
            $result [] = array (
                'value' => $attribute->getData ($this->_attributeField),
                'label' => $attribute->getFrontendLabel () . ' ( ' . $attribute->getAttributeCode () . ' ) '
            );
        }

        return $result;
    }

    public function toArray ()
    {
        $result = null;

        foreach ($this->toOptionArray () as $option)
        {
            $result [$option ['value']] = $option ['label'];
        }

        return $result;
    }
}

