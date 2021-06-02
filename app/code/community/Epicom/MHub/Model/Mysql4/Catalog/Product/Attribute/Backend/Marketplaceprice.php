<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Catalog product group price backend attribute model
 */
class Epicom_MHub_Model_Mysql4_Catalog_Product_Attribute_Backend_Marketplaceprice
    extends Mage_Catalog_Model_Resource_Product_Attribute_Backend_Groupprice_Abstract
{
    /**
     * Initialize connection and define main table
     *
     */
    protected function _construct()
    {
        $this->_init('mhub/product_attribute_marketplace_price', 'value_id');
    }

    /**
     * Load Tier Prices for product
     *
     * @param int $productId
     * @param int $websiteId
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Backend_Tierprice
     */
    public function loadPriceData($productId, $websiteId = null)
    {
        $result = parent::loadPriceData($productId, $websiteId);

        foreach($result as $id => $values)
        {
            if($values['special'] < 0)
            {
                $result[$id]['special'] = null;
            }
        }

        return $result;
    }

    /**
     * Load specific sql columns
     *
     * @param array $columns
     * @return array
     */
    protected function _loadPriceDataColumns($columns)
    {
        return array_merge($columns, array(
            'mktpl_channel' => 'marketplace_id',
            'special'       => 'special_price',
            'is_active'     => 'is_active',
        ));
    }
}

