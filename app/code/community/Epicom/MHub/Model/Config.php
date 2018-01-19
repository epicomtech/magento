<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Config
{
    const CART_CALCULATE_METHOD = 'calculocarrinho';

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

    public function getAttributeId ($attributeCode, $entityType = 'catalog_product')
    {
        $model = Mage::getModel ('eav/entity_attribute')
            ->setEntityTypeId ($entityType)
        ;

        $model->load ($attributeCode, 'attribute_code');

        return $model->getId ();
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
            ->where ("eao.attribute_id = {$attributeId} AND eaov.store_id = {$storeId} AND BINARY eaov.value = '{$tValue}'");

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

    public function getShippingPrices ($postCode, $unique = false)
    {
        $productSkuAttribute = Mage::getStoreConfig ('mhub/product/sku');

        $result = array ();

        $items  = Mage::getSingleton ('checkout/session')->getQuote ()->getAllItems ();
        foreach ($items as $_item)
        {
            $result [$_item->getProductId ()] = $_item->getQty ();
        }

        if (empty ($result)) return false;

        $collection = Mage::getModel ('catalog/product')->getCollection ()
            ->addAttributeToFilter ('entity_id', array ('in' => array_keys ($result)))
            ->AddAttributeToSelect ($productSkuAttribute, array ('gt' => 0))
        ;

        if (!$collection->count ()) return false;

        $post = array (
            'itens' => array (),
            'cep' => $postCode
        );

        foreach ($collection as $product)
        {
            $post ['itens'][] = array (
                'id'         => $product->getData ($productSkuAttribute),
                'quantidade' => $result [$product->getId ()]
            );
        }

        $cartCalculateMethod = self::CART_CALCULATE_METHOD . ($unique ? '?entregaUnica=true' : null);

        $result = Mage::helper ('mhub')->api ($cartCalculateMethod, $post);

        return $result;
    }
}

