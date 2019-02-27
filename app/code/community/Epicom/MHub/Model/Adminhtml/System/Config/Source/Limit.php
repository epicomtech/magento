<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Used in creating options for Limits config value selection
 *
 */
class Epicom_MHub_Model_Adminhtml_System_Config_Source_Limit
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = array(
            Epicom_MHub_Helper_Data::QUEUE_LIMIT_30  => Mage::helper ('mhub')->__('30 items'),
            Epicom_MHub_Helper_Data::QUEUE_LIMIT_60  => Mage::helper ('mhub')->__('60 items'),
            Epicom_MHub_Helper_Data::QUEUE_LIMIT_90  => Mage::helper ('mhub')->__('90 items'),
            Epicom_MHub_Helper_Data::QUEUE_LIMIT_120 => Mage::helper ('mhub')->__('120 items'),
            Epicom_MHub_Helper_Data::QUEUE_LIMIT_150 => Mage::helper ('mhub')->__('150 items'),
            Epicom_MHub_Helper_Data::QUEUE_LIMIT_180 => Mage::helper ('mhub')->__('180 items'),
            Epicom_MHub_Helper_Data::QUEUE_LIMIT_210 => Mage::helper ('mhub')->__('210 items'),
            Epicom_MHub_Helper_Data::QUEUE_LIMIT_240 => Mage::helper ('mhub')->__('240 items'),
            Epicom_MHub_Helper_Data::QUEUE_LIMIT_270 => Mage::helper ('mhub')->__('270 items'),
            Epicom_MHub_Helper_Data::QUEUE_LIMIT_300 => Mage::helper ('mhub')->__('300 items'),
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

