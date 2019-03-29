<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Product_Input extends Epicom_MHub_Model_Cron_Abstract
{
    const PRODUCTS_INFO_METHOD         = 'produtos/{productId}';
    const PRODUCTS_SKUS_METHOD         = 'produtos/{productId}/skus/{productSku}';
    const PRODUCTS_AVAILABILITY_METHOD = 'produtos/{productId}/skus/{productSku}/disponibilidade';

    const PRODUCTS_TRACKING_METHOD     = 'ofertas';

    const DEFAULT_QUEUE_LIMIT = 60;

    protected $_methods = array(
        Epicom_MHub_Helper_Data::API_PRODUCT_ASSOCIATED_SKU,
        Epicom_MHub_Helper_Data::API_PRODUCT_DISASSOCIATED_SKU,
        Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_SKU,
    );

    protected $_configurableAttributeSets = null;

    protected $_defaultAttributeSetId = null;

    protected $_productWeightMode = null;

    protected $_productCodeAttribute        = null;
    protected $_productModelAttribute       = null;
    protected $_productEanAttribute         = null;
    protected $_productUrlAttribute         = null;
    protected $_productOutOfLineAttribute   = null;
    protected $_productOfferTitleAttribute  = null;
    protected $_productHeightAttribute      = null;
    protected $_productWidthAttribute       = null;
    protected $_productLengthAttribute      = null;

    protected $_productSummaryAttribute     = null;

    protected $_productBrandAttribute       = null;
    protected $_productBrandAttributeId     = null;

    protected $_productManufacturerAttribute   = null;
    protected $_productManufacturerAttributeId = null;

    public function _construct ()
    {
        $this->_configurableAttributeSets = explode (',', Mage::getStoreConfig ('mhub/attributes_set/product_associations'));

        $this->_defaultAttributeSetId = Mage::getStoreConfig ('mhub/attributes_set/product');

        $this->_productWeightMode = Mage::getStoreConfig ('mhub/product/weight_mode');

        $this->_productCodeAttribute       = Mage::getStoreConfig ('mhub/product/code');
        $this->_productModelAttribute      = Mage::getStoreConfig ('mhub/product/model');
        $this->_productEanAttribute        = Mage::getStoreConfig ('mhub/product/ean');
        $this->_productUrlAttribute        = Mage::getStoreConfig ('mhub/product/url');
        $this->_productOutOfLineAttribute  = Mage::getStoreConfig ('mhub/product/out_of_line');
        $this->_productOfferTitleAttribute = Mage::getStoreConfig ('mhub/product/offer_title');
        $this->_productHeightAttribute     = Mage::getStoreConfig ('mhub/product/height');
        $this->_productWidthAttribute      = Mage::getStoreConfig ('mhub/product/width');
        $this->_productLengthAttribute     = Mage::getStoreConfig ('mhub/product/length');

        $this->_productSummaryAttribute    = Mage::getStoreConfig ('mhub/product/summary');

        $this->_productBrandAttribute      = Mage::getStoreConfig ('mhub/product/brand');
        $this->_productManufacturerAttribute = Mage::getStoreConfig ('mhub/product/manufacturer');

        $this->_productBrandAttributeId    = $this->getConfig ()->getAttributeId ($this->_productBrandAttribute);
        $this->_productManufacturerAttributeId = $this->getConfig ()->getAttributeId ($this->_productManufacturerAttribute);
    }

    private function readMHubProductsCollection ()
    {
        $limit = intval (Mage::getStoreConfig ('mhub/queue/product'));

        $collection = Mage::getModel ('mhub/product')->getCollection ()
            ->addFieldToFilter ('method', array ('in' => $this->_methods))
        ;
        $select = $collection->getSelect ();
        $select->where ('synced_at < updated_at OR synced_at IS NULL')
            ->where (sprintf ("operation = '%s' AND status != '%s'",
                Epicom_MHub_Helper_Data::OPERATION_IN, Epicom_MHub_Helper_Data::STATUS_OKAY
            ))
            ->group ('external_sku')
            ->group ('method')
            ->order (sprintf ("FIELD(method,%s)", implode (',', array_map (
                function ($n) { return "'{$n}'"; }, $this->_methods
            ))))
            ->order ('updated_at DESC')
            ->order ('status DESC')
            ->limit ($limit ? $limit : self::DEFAULT_QUEUE_LIMIT)
        ;

        return $collection;
    }

    private function updateProducts ($collection)
    {
        foreach ($collection as $product)
        {
            $result = null;

            try
            {
                $result = $this->updateMHubProduct ($product);
            }
            catch (Exception $e)
            {
                /*
                if (!strcmp ($product->getMethod (), Epicom_MHub_Helper_Data::API_PRODUCT_DISASSOCIATED_SKU))
                {
                    $this->disableMHubProduct ($product, $e->getCode ());
                }
                */
                if ($e->getCode () == 404)
                {
                    $result = $this->disableMHubProduct ($product, $e->getCode ());
                }

                $this->logMHubProduct ($product, $e->getMessage ());

                self::logException ($e);
            }

            if (!empty ($result)) $this->cleanupMHubProduct ($product, $result);
        }

        return true;
    }

    protected function updateMHubProduct (Epicom_MHub_Model_Product $product)
    {
        $productId  = $product->getExternalId ();
        $productSku = $product->getExternalSku ();

        $collection = Mage::getModel ('mhub/product')->getCollection ()
            ->addFieldToFilter ('external_id',  array ('eq' => $productId))
            ->addFieldToFilter ('external_sku', array ('eq' => $productSku))
            ->addFieldToFilter ('method',       array ('in' => array(
                Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_PRICE,
                Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_STOCK,
                Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_AVAILABILITY,
            )))
            ->addFieldToFilter ('operation', array ('eq' => Epicom_MHub_Helper_Data::OPERATION_IN))
            ->addFieldToFilter ('status',    array ('neq' => Epicom_MHub_Helper_Data::STATUS_OKAY))
        ;

        if ($collection->getSize () > 0)
        {
            return false; // availability_first
        }

        /**
         * Product Info
         */
        $helper = Mage::Helper ('mhub');

        $productsInfoMethod = str_replace ('{productId}', $productId, self::PRODUCTS_INFO_METHOD);
        $productsInfoResult = $helper->api ($productsInfoMethod);

        if (empty ($productsInfoResult))
        {
            throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Empty Product Info! ID %s', $productId), 9999);
        }

        /**
         * SKU Info
         */
        $productsSkusMethod = str_replace (array ('{productId}', '{productSku}'), array ($productId, $productSku), self::PRODUCTS_SKUS_METHOD);
        $productsSkusResult = $helper->api ($productsSkusMethod);

        if (empty ($productsSkusResult))
        {
            throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Empty SKU Info! SKU %s', $productSku), 9999);
        }

        /**
         * Load
         */
        $productNotExists = false;

        $mageProduct = Mage::getModel ('catalog/product')->loadByAttribute (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productSku);
        if (!$mageProduct || !$mageProduct->getId ())
        {
            $productNotExists = true;
        }

        if ($productNotExists)
        {
            $mageProduct = Mage::getModel ('catalog/product');
        }

        $mageProduct->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productSku);

        /**
         * Forced Mode?
         */
        if ($productNotExists && in_array ($product->getMethod (), array (
            Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_PRICE,
            Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_STOCK,
            Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_AVAILABILITY
        )))
        {
            $product->setMethod (Epicom_MHub_Helper_Data::API_PRODUCT_ASSOCIATED_SKU);
        }

        /**
         * Parse
         */
        switch ($product->getMethod ())
        {
            case Epicom_MHub_Helper_Data::API_PRODUCT_DISASSOCIATED_SKU:
            case Epicom_MHub_Helper_Data::API_PRODUCT_ASSOCIATED_SKU:
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_SKU:
            {
                // attributeset by category id
                $categoryId = $productsSkusResult->codigoCategoria;

                $collection = Mage::getModel('catalog/category')->getCollection ();

                $collection->getSelect ()->reset (Zend_Db_Select::COLUMNS)
                    ->columns (array ('entity_id'))
                ;

                $collection
                    ->addAttributeToSelect (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SET, array ('notnull' => true))
                    ->addAttributeToSelect (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_ISACTIVE, array ('eq' => '1'))
                    ->addAttributeToFilter ('entity_id', array ('eq' => $categoryId))
                ;

                $mageCategory = $collection->getFirstItem ();

                $categoryAttributeSetId = $mageCategory->getData (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SET);
                $productAttributeSetId  = $categoryAttributeSetId ? $categoryAttributeSetId : $this->_defaultAttributeSetId;
                /*
                $productHasVariations = (is_array ($productsInfoResult->grupos) && count ($productsInfoResult->grupos) > 0
                    && is_array ($productsInfoResult->grupos [0]->atributos) && count ($productsInfoResult->grupos [0]->atributos) > 0
                ) != false;
                */

                $productHasVariations = $this->_productHasVariations ($productsSkusResult);

                /**
                 * SKU
                 */
                if ($productNotExists)
                {
                    // default
                    $mageProduct->setTaxClassId (0); // none
                    $mageProduct->setWeight (999999);
                    $mageProduct->setPrice (999999);
                    $mageProduct->setWebsiteIds (array (1)); // Default
                    $mageProduct->setCategoryIds (array ($mageCategory->getId ()));
                }

                $mageProduct->setTypeId (Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
                $mageProduct->setVisibility ($productHasVariations
                    ? Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
                    : Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH
                );

                $mageProduct->setAttributeSetId ($productAttributeSetId);

                $mageProduct->setSku ($productSku);

                // sku
                $mageProduct->setName ($productsSkusResult->nome);
                $mageProduct->setUrl ($productsSkusResult->nome);
                $mageProduct->setStatus ($productsSkusResult->disponivel
                    ? Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                    : Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                );

                $mageProduct->setShortDescription ($productsSkusResult->nome);

                // weight
                $productWeight = intval ($productsSkusResult->dimensoes->peso);

                if (!strcmp ($this->_productWeightMode, Epicom_MHub_Helper_Data::PRODUCT_WEIGHT_KILO) && $productWeight > 0)
                {
                    $productWeight = floatval ($productWeight / 1000);
                }

                $mageProduct->setWeight ($productWeight > 0 ? $productWeight : 999999);

                // parent
                $mageProduct->setDescription ($productsInfoResult->descricao);

                // custom
                $mageProduct->setData ($this->_productCodeAttribute,       $productsSkusResult->codigo);
                $mageProduct->setData ($this->_productModelAttribute,      $productsSkusResult->modelo);
                $mageProduct->setData ($this->_productEanAttribute,        $productsSkusResult->ean);
                $mageProduct->setData ($this->_productUrlAttribute,        $productsSkusResult->url);
                $mageProduct->setData ($this->_productOutOfLineAttribute,  $productsSkusResult->foraDeLinha);
                $mageProduct->setData ($this->_productOfferTitleAttribute, $productsSkusResult->nomeReduzido);
                $mageProduct->setData ($this->_productHeightAttribute,     $productHeight = $productsSkusResult->dimensoes->altura);
                $mageProduct->setData ($this->_productWidthAttribute,      $productWidth  = $productsSkusResult->dimensoes->largura);
                $mageProduct->setData ($this->_productLengthAttribute,     $productLength = $productsSkusResult->dimensoes->comprimento);

                // brand
                $productBrandValue = $productsInfoResult->marca;
                if (!empty ($productBrandValue))
                {
                    if (!$this->_productBrandAttribute || !$this->_productBrandAttributeId)
                    {
                        throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Brand attribute not found: %s value: %s SKU: %s', $this->_productBrandAttribute, $productBrandValue, $productSku), 9999);
                    }

                    $productBrandAttributeOptionId = $this->getConfig ()->addAttributeOptionValue ($this->_productBrandAttributeId, array(
                        'order' => '0',
                        'label' => array (
                            array ('store_code' => 'admin', 'value' => $productBrandValue)
                        ),
                    ));

                    $mageProduct->setData ($this->_productBrandAttribute, $productBrandAttributeOptionId);
                }

                // manufacturer
                $productManufacturerValue = $productsInfoResult->codigoFornecedor;
                if (!empty ($productManufacturerValue))
                {
                    $mageProduct->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MANUFACTURER, $productManufacturerValue);

                    if (!$this->_productManufacturerAttribute || !$this->_productManufacturerAttributeId)
                    {
                        throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Manufacturer attribute not found: %s value: %s SKU: %s', $this->_productManufacturerAttribute, $productManufacturerValue, $productSku), 9999);
                    }

                    $mhubProductManufacturer = Mage::getModel ('mhub/provider')->load ($productManufacturerValue, 'code');

                    if ($mhubProductManufacturer && $mhubProductManufacturer->getId ())
                    {
                        $productManufacturerValue = $mhubProductManufacturer->getName ();

                        if ($mhubProductManufacturer->getIsService ())
                        {
                            $mageProduct->setTypeId (Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL);
                        }
                    }

                    $productManufacturerOptionId    = $this->getConfig ()->addAttributeOptionValue ($this->_productManufacturerAttributeId, array(
                        'order' => '0',
                        'label' => array (
                            array ('store_code' => 'admin', 'value' => $productManufacturerValue)
                        ),
                    ));

                    $mageProduct->setData ($this->_productManufacturerAttribute, $productManufacturerOptionId);
                }

                // groups
                if ($productHasVariations)
                {
                    foreach ($productsSkusResult->grupos as $id => $group)
                    {
                        foreach ($group->atributos as $attribute)
                        {
                            $productAttribute      = $this->getConfig ()->getAttribute ($attribute->nome, 'frontend_label');
                            $productAttributeValue = $attribute->valor;

                            if (!$productAttribute || !$productAttribute->getId ())
                            {
                                throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Custom attribute not found: %s value: %s SKU: %s', $attribute->nome, $attribute->valor, $productSku), 9999);
                            }
                            /*
                            $productAttributeOptionId = $this->getConfig ()->addAttributeOptionValue ($productAttribute->getId (), array(
                                'order' => '0',
                                'label' => array (
                                    array ('store_code' => 'admin', 'value' => $productAttributeValue)
                                ),
                            ));

                            $mageProduct->setData ($productAttribute->getAttributeCode (), $productAttributeOptionId);
                            */
                            $mageProduct->setData ($attribute->codigoAtributoCategoria, $attribute->codigoValorAtributoCategoria);
                        }
                    }
                }

                $mageProduct->save ();
/*
                // stock
                $stockItem = Mage::getModel ('cataloginventory/stock_item')
                    ->assignProduct ($mageProduct)
                    ->setProduct ($mageProduct)
                    ->setStockId (Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID)
                    ->setUseConfigManageStock (true)
                    ->setManageStock (true)
                    ->setIsInStock (false)
                    ->setStockStatusChangedAuto (true)
                    ->setQty (0)
                    ->save ()
                ;
*/
                $productIds = array ($mageProduct->getId ());

                $productNotExists = false;

                /**
                 * Load Parent
                 */
                $parentNotExists = false;

                if ($productHasVariations)
                {
                    $parentProduct = Mage::getModel ('catalog/product')->loadByAttribute (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productId);
                    if (!$parentProduct || !$parentProduct->getId ())
                    {
                        $parentNotExists = true;
                    }

                    if ($parentNotExists)
                    {
                        $parentProduct = Mage::getModel ('catalog/product');
                    }

                    $parentProduct->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productId);

                    if ($parentNotExists)
                    {
                        // default
                        $parentProduct->setTaxClassId (0); // none
                        $parentProduct->setVisibility (Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
                        $parentProduct->setWeight (999999);
                        $parentProduct->setPrice (999999);
                        $parentProduct->setWebsiteIds (array (1)); // Default
                        $parentProduct->setStatus (Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                        $parentProduct->setCategoryIds (array ($mageCategory->getId ()));
                    }

                    $parentProduct->setTypeId (Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);

                    $parentProduct->setAttributeSetId ($productAttributeSetId);

                    $parentProduct->setSku ($productId);

                    // product
                    $parentProduct->setName ($productsInfoResult->nome);
                    $parentProduct->setUrl ($productsInfoResult->nome);
                    $parentProduct->setShortDescription ($productsInfoResult->nome);

                    // parent
                    $parentProduct->setDescription ($productsInfoResult->descricao);
                    $parentProduct->setMetaKeyword ($productsInfoResult->palavrasChave);

                    // custom
                    $parentProduct->setData ($this->_productCodeAttribute,       $productsInfoResult->codigo);
                    $parentProduct->setData ($this->_productOfferTitleAttribute, $productsSkusResult->nomeReduzido);

                    if ($productWeight > 0 && $productWeight < $parentProduct->getWeight ())
                    {
                        $parentProduct->setWeight ($productWeight);
                    }

                    $parentProduct->setWeight ($productWeight > 0 ? $productWeight : 999999);

                    $parentProduct->setData ($this->_productHeightAttribute, $productHeight);
                    $parentProduct->setData ($this->_productWidthAttribute,  $productWidth);
                    $parentProduct->setData ($this->_productLengthAttribute, $productLength);

                    // brand
                    $productBrandValue = $productsInfoResult->marca;
                    if (!empty ($productBrandValue))
                    {
                        if (!$this->_productBrandAttribute || !$this->_productBrandAttributeId)
                        {
                            throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Brand attribute not found: %s value: %s SKU: %s', $this->_productBrandAttribute, $productBrandValue, $productSku), 9999);
                        }

                        $productBrandAttributeOptionId = $this->getConfig ()->addAttributeOptionValue ($this->_productBrandAttributeId, array(
                            'order' => '0',
                            'label' => array (
                                array ('store_code' => 'admin', 'value' => $productBrandValue)
                            ),
                        ));

                        $parentProduct->setData ($this->_productBrandAttribute, $productBrandAttributeOptionId);
                    }

                    // manufacturer
                    $productManufacturerValue = $productsInfoResult->codigoFornecedor;
                    if (!empty ($productManufacturerValue))
                    {
                        $parentProduct->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MANUFACTURER, $productManufacturerValue);

                        if (!$this->_productManufacturerAttribute || !$this->_productManufacturerAttributeId)
                        {
                            throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Manufacturer attribute not found: %s value: %s SKU: %s', $this->_productManufacturerAttribute, $productManufacturerValue, $productSku), 9999);
                        }

                        $mhubProductManufacturer = Mage::getModel ('mhub/provider')->load ($productManufacturerValue, 'code');

                        if ($mhubProductManufacturer && $mhubProductManufacturer->getId ())
                        {
                            $productManufacturerValue = $mhubProductManufacturer->getName ();
                        }

                        $productManufacturerOptionId    = $this->getConfig ()->addAttributeOptionValue ($this->_productManufacturerAttributeId, array(
                            'order' => '0',
                            'label' => array (
                                array ('store_code' => 'admin', 'value' => $productManufacturerValue)
                            ),
                        ));

                        $parentProduct->setData ($this->_productManufacturerAttribute, $productManufacturerOptionId);
                    }

                    // attributes
                    $attributesResult = null;

                    foreach ($productsInfoResult->grupos as $group)
                    {
                        $attributesResult .= $group->nome . str_repeat (PHP_EOL, 2);

                        foreach ($group->atributos as $attribute)
                        {
                            $attributesResult .= $attribute->nome . ' ' . $attribute->valor . PHP_EOL;
                        }
                    }

                    $parentProduct->setData ($this->_productSummaryAttribute, $attributesResult);

                    $parentProduct->save ();

                    // stock
                    $stockItem = Mage::getModel ('cataloginventory/stock_item')
                        ->assignProduct ($parentProduct)
                        ->setProduct ($parentProduct)
                        ->setStockId (Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID)
                        ->setUseConfigManageStock (true)
                        ->setManageStock (true)
                        ->setIsInStock (true)
                        ->setStockStatusChangedAuto (true)
                        ->setQty (0)
                        ->save ()
                    ;

                    // product association
                    $assocItem = Mage::getModel ('mhub/product_association')->load ($productSku, 'sku');
                    if (empty ($assocItem) || !$assocItem->getId ())
                    {
                        $assocItem = Mage::getModel ('mhub/product_association');
                        $assocItem->setSku ($productSku);
                    }

                    $assocItem->setParentSku ($productId)
                        ->setIsModified (1)
                        ->save ()
                    ;

                    /*
                     * Configurable attributes
                     */
                    $resource = Mage::getSingleton ('core/resource');
                    $write = $resource->getConnection ('core_write');

                    $table = $resource->getTableName ('catalog_product_super_attribute');
                    $write->delete ($table, "product_id = {$parentProduct->getId ()}"); // remove previous super_attributes

                    $table = $resource->getTableName ('catalog_product_super_link');
                    $write->delete ($table, "parent_id =  {$parentProduct->getId ()}"); // remove previous super_links

                    $table = $resource->getTableName ('catalog_product_relation');
                    $write->delete ($table, "parent_id = {$parentProduct->getId ()}"); // remove previous relations

                    $parentProduct->setCanSaveCustomOptions (true);
                    $parentProduct->setCanSaveConfigurableAttributes (true);

                    $parentProduct = Mage::getModel ('catalog/product')->load ($parentProduct->getId ()); // reload

                    $productAttributeSets = null;

                    $configurableAttributeIds = array ();

                    foreach ($this->_configurableAttributeSets as $value)
                    {
                        list ($attributeSetId, $attributeId) = explode (':', $value);

                        $configurableAttributeIds [$attributeId] = $attributeSetId;
                    }

                    $collection = Mage::getModel ('eav/entity_attribute')->getCollection ()
                        ->addFieldToFilter ('attribute_id', array ('in' => array_keys ($configurableAttributeIds)))
                    ;

                    $collection->getSelect ()->reset (Zend_Db_Select::COLUMNS)
                        ->columns (array ('attribute_id', 'attribute_code', 'frontend_label'))
                    ;

                    foreach ($collection as $attribute)
                    {
                        $attributeId = $attribute->getId ();

                        $attributeSetId = $configurableAttributeIds [$attributeId];
                        /*
                        $attribute = Mage::getModel ('eav/entity_attribute')->load ($attributeId);
                        */
                        $productAttributeSets [] = array (
                            'attribute_id'     => $attributeId,
                            'attribute_code'   => $attribute->getAttributeCode (),
                            'frontend_label'   => $attribute->getFrontendLabel (),
                            'attribute_set_id' => $attributeSetId
                        );
                    }

                    // $configurableAttributesIds  = null;
                    $configurableAttributesData = null;

                    foreach ($productAttributeSets as $value)
                    {
                        if (!$this->_productHasAttribute ($productsSkusResult, $value ['frontend_label']))
                        {
                            continue;
                        }

                        if ($parentProduct->getAttributeSetId () == $value ['attribute_set_id'])
                        {
                            $configurableAttributesData [] = array ('attribute_id' => $value ['attribute_id'], 'attribute_code' => $value ['attribute_code']);

                            // $configurableAttributesIds [] = $value ['attribute_id'];
                        }
                    }

                    // $parentProduct->getTypeInstance ()->setUsedProductAttributeIds ($configurableAttributesIds);

                    $parentProduct->setConfigurableAttributesData ($configurableAttributesData);

                    /*
                     * Simple products
                     */
                    $configurableProductsData = null;

                    $collection = Mage::getModel ('mhub/product_association')->getCollection ()
                        ->addFieldToFilter ('parent_sku', array ('eq' => $productId))
                    ;

                    $collection->getSelect ()->reset (Zend_Db_Select::COLUMNS)
                        ->columns (array ('id' => 'entity_id', 'name' => 'sku'))
                    ;

                    $simpleSKUs = array_values ($collection->toOptionHash ());

                    $collection = Mage::getModel ('catalog/product')->getCollection ()
                        ->addAttributeToFilter (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, array ('in' => $simpleSKUs))
                        ->addAttributeToSelect ('price')
                        ->addAttributeToSelect ('special_price')
                    ;

                    foreach ($collection as $simpleProduct)
                    {
                        /*
                        $simpleProduct = Mage::getModel ('catalog/product')->loadByAttribute (
                            Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $item->getSku (),
                            array ('price', 'special_price')
                        );
                        */
                        if ($simpleProduct && intval ($simpleProduct->getId ()) > 0)
                        {
                            foreach ($productAttributeSets as $value)
                            {
                                if (!$this->_productHasAttribute ($productsSkusResult, $value ['frontend_label']))
                                {
                                    continue;
                                }

                                if ($simpleProduct->getAttributeSetId () == $value ['attribute_set_id'])
                                {
                                    $configurableProductsData [$simpleProduct->getId ()][] = array ('attribute_id' => $value ['attribute_id']);
                                }
                            }

                            // lowest price
                            if ($simpleProduct->getPrice () < $parentProduct->getPrice () /* && $simpleProduct->isSalable () */)
                            {
                                $parentProduct->setPrice ($simpleProduct->getPrice ())
                                    ->setSpecialPrice ($simpleProduct->getSpecialPrice ())
                                ;
                            }
                        }
                    }

                    $parentProduct->setConfigurableProductsData ($configurableProductsData);

                    $parentProduct->save ();

                    $productIds [] = $parentProduct->getId ();

                    $parentNotExists = true;
                }
                else
                {
                    $parentProduct = Mage::getModel ('catalog/product')->loadByAttribute (
                        Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productId, null
                    );

                    if ($parentProduct && intval ($parentProduct->getId ()) > 0)
                    {
                        $parentProduct->delete ();
                    }
                }

                /**
                 * Images
                 */
                $mediaApi = Mage::getModel ('catalog/product_attribute_media_api');

                foreach ($productIds as $id)
                {
                    foreach ($mediaApi->items ($id) as $item)
                    {
                        $mediaApi->remove ($id, $item ['file']);
                    }
                }

                foreach ($productsSkusResult->imagens as $id => $image)
                {
                    $uri = null;

                    foreach (array ($image->zoom, $image->maior, $image->menor) as $_uri)
                    {
                        if (!empty ($_uri))
                        {
                            $uri = $_uri;

                            break;
                        }
                    }

                    if (empty ($uri)) continue;

                    $client = new Zend_Http_Client ();
                    $client->setUri ($uri);

                    $response = $client->request ('GET');

                    $imageContent = $response->getRawBody ();
                    if (!empty ($imageContent))
                    {
                        $_image ['file'] = array ('content' => base64_encode ($imageContent), 'mime' => 'image/jpeg');
                        $_image ['types'] = !$id ? $_image ['types'] = array ('image', 'small_image', 'thumbnail') : array ();
                        $_image ['exclude'] = 0;

                        try
                        {
                            foreach ($productIds as $_id)
                            {
                                $mediaApi->create ($_id, $_image);
                            }
                        }
                        catch (Exception $e)
                        {
                            // nothing
                        }
                    }
                }

                break;
            }
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_PRICE:
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_STOCK:
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_AVAILABILITY:
            {
                if ($productNotExists)
                {
                    $this->_fault ('product_not_exists');
                }

                $productsAvailabilityMethod = str_replace (array ('{productId}', '{productSku}'), array ($productId, $productSku), self::PRODUCTS_AVAILABILITY_METHOD);
                $productsAvailabilityResult = $helper->api ($productsAvailabilityMethod);

                if (empty ($productsAvailabilityResult))
                {
                    throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Empty SKU Availability! SKU %s', $productSku), 9999);
                }

                // price
                $productPriceFrom = $productsAvailabilityResult->precoDe;
                $productPriceTo = $productsAvailabilityResult->preco;

                if (!empty ($productPriceFrom))
                {
                    $mageProduct->setPrice ($productPriceFrom);
                    $mageProduct->setSpecialPrice ($productPriceTo);
                }
                else
                {
                    $mageProduct->setPrice ($productPriceTo ? $productPriceTo : 999999);
                    $mageProduct->setSpecialPrice (null);
                }

                $mageProduct->save ();

                // stock
                $setIsInStock = true; // $productsAvailabilityResult->disponivel;
                $setQty = $productsAvailabilityResult->estoque;

                $stockItem = Mage::getModel ('cataloginventory/stock_item')
                    ->assignProduct ($mageProduct)
                    ->setProduct ($mageProduct)
                    ->setStockId (Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID)
                    ->setUseConfigManageStock (true)
                    ->setManageStock (true)
                    ->setIsInStock ($setIsInStock)
                    ->setStockStatusChangedAuto (true)
                    ->setQty ($setQty)
                    ->save ()
                ;

                // parent
                $parentProduct = Mage::getModel ('catalog/product')->loadByAttribute (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productId);
                if ($parentProduct && intval ($parentProduct->getId ()) > 0)
                {
                    if ($mageProduct->getPrice () < $parentProduct->getPrice () /* && $mageProduct->isSalable () */)
                    {
                        $parentProduct->setPrice ($mageProduct->getPrice ())
                            ->setSpecialPrice ($mageProduct->getSpecialPrice ())
                            ->save ()
                        ;
                    }
                }

                break;
            }
        }

        /**
         * Webhook
         */
        $post = array(
            'skuId'      => $productSku,
            'codigo'     => $productsSkusResult->codigo,
            'url'        => $productsSkusResult->url,
            'status'     => 30, /* Ativa */
            'erro'       => null,
            'pendencias' => null
        );

        try
        {
            $helper->api (self::PRODUCTS_TRACKING_METHOD, $post, 'PUT');
        }
        catch (Exception $e)
        {
            throw Mage::exception ('Epicom_MHub', Mage::helper ('mhub')->__('Invalid SKU Tracking! SKU %s', $productSku), 9999);
        }

        return $mageProduct->getId ();
    }

    private function cleanupMHubProduct (Epicom_MHub_Model_Product $product, $mageProductId)
    {
/*
        $product->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ()
        ;
*/
        $resource = Mage::getSingleton ('core/resource');
        $write    = $resource->getConnection ('core_write');
        $table    = $resource->getTableName ('mhub/product');

        $write->query (sprintf ("UPDATE %s SET synced_at = '%s', status = '%s', message = NULL, product_id = '%s' WHERE external_sku = '%s' AND method = '%s'",
            $table, date ('c'), Epicom_MHub_Helper_Data::STATUS_OKAY, $mageProductId, $product->getExternalSku (), $product->getMethod ()
        ));

        $write->query (sprintf ("DELETE FROM %s WHERE entity_id <> %s AND external_sku = '%s' AND method = '%s'",
            $table, $product->getId (), $product->getExternalSku (), $product->getMethod ()
        ));

        return true;
    }

    private function logMHubProduct (Epicom_MHub_Model_Product $product, $message = null)
    {
/*
        $product->setStatus (Epicom_MHub_Helper_Data::STATUS_ERROR)->setMessage ($message)->save ();
*/
        $resource = Mage::getSingleton ('core/resource');
        $write    = $resource->getConnection ('core_write');
        $table    = $resource->getTableName ('mhub/product');

        $write->query (sprintf ("UPDATE %s SET status = '%s', message = '%s' WHERE external_sku = '%s' AND method = '%s'",
            $table, Epicom_MHub_Helper_Data::STATUS_ERROR, $message, $product->getExternalSku (), $product->getMethod ()
        ));

        $write->query (sprintf ("DELETE FROM %s WHERE entity_id <> %s AND external_sku = '%s' AND method = '%s'",
            $table, $product->getId (), $product->getExternalSku (), $product->getMethod ()
        ));
    }

    private function disableMHubProduct (Epicom_MHub_Model_Product $product, $code = null)
    {
        if (!empty ($product->getExternalSku ()) && $code == 404)
        {
            $mageProduct = Mage::getModel ('catalog/product')->loadByAttribute (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $product->getExternalSku ());
            if ($mageProduct && intval ($mageProduct->getId ()) > 0)
            {
                $mageProduct->setStatus (Mage_Catalog_Model_Product_Status::STATUS_DISABLED)->save ();

                return $mageProduct->getId ();
            }
        }
    }

    private function _productHasAttribute ($productsSkusResult, $frontendLabel)
    {
        if (is_array ($productsSkusResult->grupos) && count ($productsSkusResult->grupos) > 0)
        {
            foreach ($productsSkusResult->grupos as $group)
            {
                if (is_array ($group->atributos) && count ($group->atributos) > 0)
                {
                    foreach ($group->atributos as $attribute)
                    {
                        if (!strcmp (strtolower ($attribute->nome), strtolower ($frontendLabel)))
                        {
                            return true;
                        }
                    }
                }
            }
        }
    }

    private function _productHasVariations ($productsSkusResult)
    {
        if (is_array ($productsSkusResult->grupos) && count ($productsSkusResult->grupos) > 0)
        {
            foreach ($productsSkusResult->grupos as $group)
            {
                if (!strcmp ($group->nome, Epicom_MHub_Helper_Data::PRODUCT_FIXED_GROUP_NAME))
                {
                    if (is_array ($group->atributos) && count ($group->atributos) > 0)
                    {
                        return true;
                    }
                }
            }
        }
    }

    protected function getConfig ()
    {
        return Mage::getModel ('mhub/config');
    }

    public function run ()
    {
        if (!$this->getStoreConfig ('active') || !$this->getHelper ()->isMarketplace ())
        {
            return false;
        }
/*
        $result = $this->readMHubProductsMagento ();
        if (!$result) return false;
*/
        $collection = $this->readMHubProductsCollection ();
        if (!$collection->getSize ()) return false;

        $this->updateProducts ($collection);

        return true;
    }
}

