<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Used in creating options for Product Manufactures config value selection
 *
 */
class Epicom_MHub_Model_Adminhtml_System_Config_Source_Product_Manufacturer
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = array();

        $collection = Mage::getModel ('mhub/provider')->getCollection ();

        foreach ($collection as $provider)
        {
            $result [$provider->getCode()] = $provider->getName();
        }

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

