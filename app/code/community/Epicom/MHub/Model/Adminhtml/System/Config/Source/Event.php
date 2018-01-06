<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Used in creating options for Events config value selection
 *
 */
class Epicom_MHub_Model_Adminhtml_System_Config_Source_Event
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = array(
            Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_CREATED   => Mage::helper ('mhub')->__('Created'),
            Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_NF        => Mage::helper ('mhub')->__('NF'),
            Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_SENT      => Mage::helper ('mhub')->__('Sent'),
            Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_DELIVERED => Mage::helper ('mhub')->__('Delivered'),
            Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_FAILED    => Mage::helper ('mhub')->__('Failed'),
            Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_PARCIAL   => Mage::helper ('mhub')->__('Parcial'),
            Epicom_MHub_Helper_Data::API_SHIPMENT_EVENT_CANCELED  => Mage::helper ('mhub')->__('Canceled'),
        );

        return $result;
    }
}

