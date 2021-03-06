<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Config
{
    const CART_CALCULATE_METHOD = 'calculocarrinho';

    const COLLECTIONS_CACHE_TAG = 'collections';

    public function addAttributeOptionValue ($attributeId, $data)
    {
        $label   = !empty ($data ['label']) ? $data ['label'] : array ();
        $order   = !empty ($data ['order']) ? $data ['order'] : 0;
        $default = !empty ($data ['default']) ? $data ['default'] : null;

        $resource = Mage::getSingleton ('core/resource');
        $write    = $resource->getConnection ('core_write');

        $tableAttribute            = $resource->getTableName ('eav_attribute');
        $tableAttributeOption      = $resource->getTableName ('eav_attribute_option');
        $tableAttributeOptionValue = $resource->getTableName ('eav_attribute_option_value');

        $optionId = -1;

        foreach ($label as $id => $value)
        {
            $storeCode  = $value ['store_code'];
            $storeValue = $value ['value'];

            $store = Mage::getModel ('core/store')->load ($storeCode, 'code');
            if (empty ($store)) continue;

            $storeId = $store->getId ();
            if (!is_numeric ($storeId)) continue;

            if ($storeId == 0)
            {
                $optionId = $this->getAttributeOptionIdByValue ($attributeId, $storeValue, $storeId);
                if ($optionId < 0)
                {
                    $write->insert ($tableAttributeOption, array ('attribute_id' => $attributeId, 'sort_order' => $order));

                    $optionId = $write->lastInsertId ();
                }
                else
                {
                    $write->insertOnDuplicate ($tableAttributeOption, array ('option_id' => $optionId, 'attribute_id' => $attributeId, 'sort_order' => $order));
                }
            }

            $valueId = $this->getAttributeOptionValueId ($optionId, $storeId);
            $tValue = trim ($storeValue);

            $write->insertOnDuplicate ($tableAttributeOptionValue, array ('value_id' => $valueId, 'option_id' => $optionId, 'store_id' => $storeId, 'value' => $tValue));

            if ($default)
            {
                $write->update ($tableAttribute, array ('default_value' => $optionId), "attribute_id = {$attributeId}");
            }
        }

        return $optionId;
    }

    public function getAttribute ($attributeCode, $field = 'attribute_code')
    {
        $model = Mage::getModel ('eav/entity_attribute')
            ->setEntityTypeId (Mage_Catalog_Model_Product::ENTITY)
        ;

        $model->load ($attributeCode, $field);

        return $model; // ->getId ();
    }

    public function getAttributeId ($attributeCode, $field = 'attribute_code')
    {
        $attribute = $this->getAttribute ($attributeCode, $field);

        return $attribute->getId ();
    }

    public function getAttributeOptionIdByValue ($attributeId, $value, $storeId = 0)
    {
        $resource = Mage::getSingleton ('core/resource');
        $read     = $resource->getConnection ('core_read');

        $tableAttributeOption      = $resource->getTableName ('eav_attribute_option');
        $tableAttributeOptionValue = $resource->getTableName ('eav_attribute_option_value');

        $tValue = trim ($value);

        $select = $read->select ()
            ->from (array ('eaov' => $tableAttributeOptionValue), array ('option_id' => 'eaov.option_id'))
            ->join (array ('eao' => $tableAttributeOption), 'eaov.option_id = eao.option_id', null, null)
            ->where ("eao.attribute_id = {$attributeId} AND eaov.store_id = {$storeId} AND BINARY eaov.value = ?", $tValue);

        $children = $read->fetchAll ($select);

        $optionId = count ($children) ? $children [0]['option_id'] : -1;

        return (int) $optionId;
    }

    public function getAttributeOptionValueId ($optionId, $storeId = 0)
    {
        $resource = Mage::getSingleton ('core/resource');
        $read     = $resource->getConnection ('core_read');

        $tableAttributeOptionValue = $resource->getTableName ('eav_attribute_option_value');

        $select = $read->select ()
            ->from (array ('eaov' => $tableAttributeOptionValue), array ('value_id' => 'eaov.value_id'))
            ->where ("eaov.option_id = {$optionId} AND eaov.store_id = {$storeId}");

        $children = $read->fetchAll ($select);

        $valueId = count ($children) ? $children [0]['value_id'] : -1;

        return (int) $valueId;
    }

    public function getEntityTypeId ($entityType)
    {
        $item = Mage::getModel ($entityType);

        return $item->getResource ()->getTypeId ();
    }

    public function getShippingPrices ($request, $postCode, $unique = false, $scopeId = null)
    {
        $productWeightMode  = Mage::getStoreConfig ('mhub/product/weight_mode', $scopeId);

        $result = array ();

        $itemsWeight = 0;
/*
        $items  = Mage::getSingleton ('checkout/session')->getQuote ()->getAllItems ();
*/
        $items = $request->getAllItems();

        foreach ($items as $_item)
        {
            $result [$_item->getProductId ()] = $_item->getQty ();

            /**
             * Minimum Weight
             */
            $_itemWeight = $_item->getWeight ();

            if (!strcmp ($productWeightMode, Epicom_MHub_Helper_Data::PRODUCT_WEIGHT_KILO) && $_itemWeight > 0)
            {
                $_itemWeight = intval ($_itemWeight * 1000);
            }

            $itemsWeight += $_itemWeight;
        }

        if (empty ($result)) return false;

        $minimumWeight = intval (Mage::getStoreConfig ('mhub/cart/minimum_weight', $scopeId));

        if ($itemsWeight < $minimumWeight) return false;

        $productIdAttribute = Mage::getStoreConfig ('mhub/product/id', $scopeId);

        $collection = Mage::getModel ('catalog/product')->getCollection ()
            ->addAttributeToFilter ('type_id', array ('in' => array(
                Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
            )))
            ->addAttributeToFilter ('entity_id', array ('in' => array_keys ($result)))
            ->AddAttributeToFilter ($productIdAttribute, array ('notnull' => true))
            ->addAttributeToSelect (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MANUFACTURER)
        ;

        if (!$collection->count ()) return false;

        $post = array (
            'itens' => array (),
            'cep' => $postCode
        );

        foreach ($collection as $product)
        {
            $post ['itens'][] = array (
                'id'         => $product->getData ($productIdAttribute),
                'quantidade' => $result [$product->getId ()]
            );
        }

        $id = md5 (json_encode ($post));

        $cache = Mage::getStoreConfig ('mhub/cart/cache_enabled', $scopeId);

        if ($cache)
        {
            $result = Mage::app ()->loadCache ($id);
            if (!empty ($result))
            {
                return unserialize ($result);
            }
        }

        $uniqueParam = $unique ? 'true' : 'false';

        $result = Mage::helper ('mhub')->api (self::CART_CALCULATE_METHOD . "?entregaUnica={$uniqueParam}", $post, null, $scopeId);

        if ($unique)
        {
            foreach ($result as $i => $values)
            {
                foreach ($values->itens as $j => $_item)
                {
                    $product = $collection->getItemByColumnValue ($productIdAttribute, $_item->id);

                    $result [$i]->itens [$j]->fornecedor = $product->getData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MANUFACTURER);
                }
            }
        }
        else
        {
            foreach ($result as $id => $_item)
            {
                $product = $collection->getItemByColumnValue ($productIdAttribute, $_item->id);

                $result [$id]->fornecedor = $product->getData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MANUFACTURER);
            }
        }

        if ($cache)
        {
            $lifetime = Mage::getStoreConfig ('mhub/cart/cache_lifetime', $scopeId);

            Mage::app ()->saveCache (serialize ($result), $id, array (self::COLLECTIONS_CACHE_TAG, $lifetime));
        }

        return $result;
    }

    public function getMarketplaceCollection ()
    {
        $collection = Mage::getModel ('core/config_data')->getCollection ()
            ->addFieldToFilter ('path', array('eq' => Epicom_MHub_Helper_Data::XML_PATH_MHUB_SETTINGS_ACTIVE))
            ->addValueFilter (1)
        ;

        $scopeIds = array ();

        foreach ($collection as $config)
        {
            $scopeIds [] = $config->getScopeId ();
        }

        $collection = Mage::getModel ('core/config_data')->getCollection ()
            ->addFieldToFilter ('scope_id', array ('in' => $scopeIds))
            ->addFieldToFilter ('path',  array ('eq' => Epicom_MHub_Helper_Data::XML_PATH_MHUB_SETTINGS_MODE))
            ->addFieldToFilter ('value', array ('eq' => Epicom_MHub_Helper_Data::API_MODE_MARKETPLACE))
        ;

        return $collection;
    }
}

