<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Used in creating options for Marketplace config value selection
 *
 */
class Epicom_MHub_Model_Adminhtml_System_Config_Source_Marketplace
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = array ();

        foreach (Mage::getModel ('mhub/marketplace')->getCollection () as $marketplace)
        {
            $result [$marketplace->getCode ()] = $marketplace->getName ();
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

