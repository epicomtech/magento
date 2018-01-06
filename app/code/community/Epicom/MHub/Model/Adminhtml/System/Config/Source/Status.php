<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Used in creating options for Status config value selection
 *
 */
class Epicom_MHub_Model_Adminhtml_System_Config_Source_Status
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = array(
            Epicom_MHub_Helper_Data::STATUS_PENDING => Mage::helper ('mhub')->__('Pending'),
            Epicom_MHub_Helper_Data::STATUS_OKAY    => Mage::helper ('mhub')->__('Okay'),
            Epicom_MHub_Helper_Data::STATUS_ERROR   => Mage::helper ('mhub')->__('Error'),
        );

        return $result;
    }
}

