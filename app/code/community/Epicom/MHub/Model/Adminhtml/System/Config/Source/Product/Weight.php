<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Used in creating options for Product Weights config value selection
 *
 */
class Epicom_MHub_Model_Adminhtml_System_Config_Source_Product_Weight
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = array(
            Epicom_MHub_Helper_Data::PRODUCT_WEIGHT_GRAM => Mage::helper ('mhub')->__('Gram'),
            Epicom_MHub_Helper_Data::PRODUCT_WEIGHT_KILO => Mage::helper ('mhub')->__('Kilo'),
        );

        return $result;
    }

    public function toOptionArray ()
    {
        $result = array ();

        foreach ($this->toArray () as $code => $value)
        {
            $result [] = array ('value' => $code, 'label' => Mage::helper ('mhub')->__($value));
        }

        return $result;
    }
}

