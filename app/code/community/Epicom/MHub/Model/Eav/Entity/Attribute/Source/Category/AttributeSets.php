<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Eav_Entity_Attribute_Source_Category_AttributeSets extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (is_null($this->_options))
        {
            $this->_options = array(array('label' => null, 'value' => null));

            $entityTypeId = Mage::getModel ('eav/entity')->setType ('catalog_product')->getTypeId ();
            $collection = Mage::getModel ('eav/entity_attribute_set')->getCollection ();
            $collection->setEntityTypeFilter ($entityTypeId);

            foreach ($collection as $item)
            {
                $this->_options [] = array('label' => $item->getAttributeSetName() . ' - ' . $item->getAttributeSetId(), 'value' => $item->getAttributeSetId());
            }
        }

        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = array();
        foreach ($this->getAllOptions() as $option)
        {
            $_options[$option['value']] = $option['label'];
        }

        return $_options;
    }

    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        foreach ($options as $option)
        {
            if ($option['value'] == $value)
            {
                return $option['label'];
            }
        }

        return false;
    }

    /**
     * Retrieve Column(s) for Flat
     *
     * @return array
     */
    public function getFlatColums()
    {
        $columns = array();
        $columns[$this->getAttribute()->getAttributeCode()] = array(
            'type'      => 'tinyint(1)',
            'unsigned'  => false,
            'is_null'   => true,
            'default'   => null,
            'extra'     => null
        );

        return $columns;
    }

    /**
     * Retrieve Indexes(s) for Flat
     *
     * @return array
     */
    public function getFlatIndexes()
    {
        $indexes = array();

        $index = 'IDX_' . strtoupper($this->getAttribute()->getAttributeCode());
        $indexes[$index] = array(
            'type'      => 'index',
            'fields'    => array($this->getAttribute()->getAttributeCode())
        );

        return $indexes;
    }

    /**
     * Retrieve Select For Flat Attribute update
     *
     * @param int $store
     * @return Varien_Db_Select|null
     */
    public function getFlatUpdateSelect($store)
    {
        return Mage::getResourceModel('eav/entity_attribute')
            ->getFlatUpdateSelect($this->getAttribute(), $store);
    }
}

