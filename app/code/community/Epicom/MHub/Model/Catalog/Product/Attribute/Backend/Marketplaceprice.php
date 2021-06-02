<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Catalog product group price backend attribute model
 */
class Epicom_MHub_Model_Catalog_Product_Attribute_Backend_Marketplaceprice
    extends Mage_Catalog_Model_Product_Attribute_Backend_Groupprice_Abstract
{
    /**
     * Retrieve resource instance
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Attribute_Backend_Tierprice
     */
    protected function _getResource()
    {
        return Mage::getResourceSingleton('mhub/catalog_product_attribute_backend_marketplaceprice');
    }

    /**
     * Error message when duplicates
     *
     * @return string
     */
    protected function _getDuplicateErrorMessage()
    {
        return Mage::helper('catalog')->__('Duplicate website marketplace price customer group.');
    }

    /**
     * Get additional unique fields
     *
     * @param array $objectArray
     * @return array
     */
    protected function _getAdditionalUniqueFields($objectArray)
    {
        $specialPrice = $objectArray['special'];

        return array(
            'marketplace_id' => $objectArray['mktpl_channel'],
            'special_price'  => is_numeric($specialPrice) ? $specialPrice : -1,
            'is_active'      => $objectArray['is_active'],
        );
    }
}

