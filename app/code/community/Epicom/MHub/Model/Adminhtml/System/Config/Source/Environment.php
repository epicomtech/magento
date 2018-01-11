<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Used in creating options for Environments config value selection
 *
 */
class Epicom_MHub_Model_Adminhtml_System_Config_Source_Environment
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = array(
            Epicom_MHub_Helper_Data::API_ENVIRONMENT_URL_SANDBOX    => Mage::helper ('mhub')->__('Sandbox'),
            Epicom_MHub_Helper_Data::API_ENVIRONMENT_URL_PRODUCTION => Mage::helper ('mhub')->__('Production'),
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

