<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Used in creating options for Customer Modes config value selection
 *
 */
class Epicom_MHub_Model_Adminhtml_System_Config_Source_Customer_Mode
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = array(
            Mage_Checkout_Model_Type_Onepage::METHOD_GUEST    => Mage::helper ('mhub')->__('Guest'),
            Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER => Mage::helper ('mhub')->__('Register'),
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

