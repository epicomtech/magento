<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Used in creating options for Operation config value selection
 *
 */
class Epicom_MHub_Model_Adminhtml_System_Config_Source_Operation
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = array(
            Epicom_MHub_Helper_Data::OPERATION_IN   => Mage::helper ('mhub')->__('In'),
            Epicom_MHub_Helper_Data::OPERATION_OUT  => Mage::helper ('mhub')->__('Out'),
            Epicom_MHub_Helper_Data::OPERATION_BOTH => Mage::helper ('mhub')->__('Both'),
        );

        return $result;
    }
}

