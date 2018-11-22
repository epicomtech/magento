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

    private function readMHubProductsCollection ()
    {
        $limit = intval (Mage::getStoreConfig ('mhub/queue/product'));

        $collection = Mage::getModel ('mhub/product')->getCollection ()
            ->addFieldToFilter ('method', array ('in' => array (
                Epicom_MHub_Helper_Data::API_PRODUCT_ASSOCIATED_SKU,
                Epicom_MHub_Helper_Data::API_PRODUCT_DISASSOCIATED_SKU,
                Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_SKU
            )))
        ;
        $select = $collection->getSelect ();
        $select->where ('synced_at < updated_at OR synced_at IS NULL')
            ->where (sprintf ("operation = '%s' AND status != '%s'",
                Epicom_MHub_Helper_Data::OPERATION_IN, Epicom_MHub_Helper_Data::STATUS_OKAY
            ))
            ->group ('external_sku')
            ->group ('method')
            ->order ('updated_at DESC')
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
                if (!strcmp ($product->getMethod (), Epicom_MHub_Helper_Data::API_PRODUCT_DISASSOCIATED_SKU))
                {
                    $this->disableMHubProduct ($product, $e->getCode ());
                }

                $this->logMHubProduct ($product, $e->getMessage ());

                self::logException ($e);
            }

            if (!empty ($result)) $this->cleanupMHubProduct ($product, $result);
        }

        return true;
    }

    private function updateMHubProduct (Epicom_MHub_Model_Product $product)
    {
        $productId  = $product->getExternalId ();
        $productSku = $product->getExternalSku ();

        /**
         * Product Info
         */
        $helper = Mage::Helper ('mhub');

        $productsInfoMethod = str_replace ('{productId}', $productId, self::PRODUCTS_INFO_METHOD);
        $productsInfoResult = $helper->api ($productsInfoMethod);

        if (empty ($productsInfoResult))
        {
            throw new Exception (Mage::helper ('mhub')->__('Empty Product Info!'));
        }

        /**
         * SKU Info
         */
        $productsSkusMethod = str_replace (array ('{productId}', '{productSku}'), array ($productId, $productSku), self::PRODUCTS_SKUS_METHOD);
        $productsSkusResult = $helper->api ($productsSkusMethod);

        if (empty ($productsSkusResult))
        {
            throw new Exception (Mage::helper ('mhub')->__('Empty SKU Info!'));
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

                $collection = Mage::getModel('catalog/category')->getCollection ()
                    ->addAttributeToSelect (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SET, array ('notnull' => true))
                    ->addAttributeToSelect (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_ISACTIVE, array ('eq' => '1'))
                    ->addAttributeToFilter ('entity_id', array ('eq' => $categoryId))
                ;

                $mageCategory = $collection->getFirstItem ();

                $categoryAttributeSetId = $mageCategory->getData (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SET);
                $defaultAttributeSetId  = Mage::getStoreConfig ('mhub/attributes_set/product');
                $productAttributeSetId  = $categoryAttributeSetId ? $categoryAttributeSetId : $defaultAttributeSetId;
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
                $mageProduct->setStatus ($productsSkusResult->ativo
                    ? Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                    : Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                );

                $mageProduct->setShortDescription ($productsSkusResult->nome);

                $productWeight = intval ($productsSkusResult->dimensoes->peso);
                $mageProduct->setWeight ($productWeight > 0 ? $productWeight : 999999);

                // parent
                $mageProduct->setDescription ($productsInfoResult->descricao);

                // custom
                $productCodeAttribute         = Mage::getStoreConfig ('mhub/product/code');
                $productModelAttribute        = Mage::getStoreConfig ('mhub/product/model');
                $productEanAttribute          = Mage::getStoreConfig ('mhub/product/ean');
                $productUrlAttribute          = Mage::getStoreConfig ('mhub/product/url');
                // $productOutOfLineAttribute    = Mage::getStoreConfig ('mhub/product/out_of_line');
                $productOfferTitleAttribute    = Mage::getStoreConfig ('mhub/product/offer_title');
                $productHeightAttribute       = Mage::getStoreConfig ('mhub/product/height');
                $productWidthAttribute        = Mage::getStoreConfig ('mhub/product/width');
                $productLengthAttribute       = Mage::getStoreConfig ('mhub/product/length');

                $mageProduct->setData ($productCodeAttribute, $productsSkusResult->codigo);
                $mageProduct->setData ($productModelAttribute, $productsSkusResult->modelo);
                $mageProduct->setData ($productEanAttribute, $productsSkusResult->ean);
                $mageProduct->setData ($productUrlAttribute, $productsSkusResult->url);
                // $mageProduct->setData ($productOutOfLineAttribute, $productsSkusResult->foraDeLinha);
                $mageProduct->setData ($productOfferTitleAttribute, $productsSkusResult->nomeReduzido);
                $mageProduct->setData ($productHeightAttribute, $productHeight = $productsSkusResult->dimensoes->altura);
                $mageProduct->setData ($productWidthAttribute,  $productWidth  = $productsSkusResult->dimensoes->largura);
                $mageProduct->setData ($productLengthAttribute, $productLength = $productsSkusResult->dimensoes->comprimento);

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
                                throw new Exception (Mage::helper ('mhub')->__('Custom attribute not found: %s value: %s', $attribute->nome, $attribute->valor));
                            }

                            $productAttributeOptionId = $this->getConfig ()->addAttributeOptionValue ($productAttribute->getId (), array(
                                'order' => '0',
                                'label' => array (
                                    array ('store_code' => 'admin', 'value' => $productAttributeValue)
                                ),
                            ));

                            $mageProduct->setData ($productAttribute->getAttributeCode (), $productAttributeOptionId);
                        }
                    }
                }

                $mageProduct->save ();

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
                    $parentProduct->setData ($productCodeAttribute, $productsInfoResult->codigo);
                    $parentProduct->setData ($productOfferTitleAttribute, $productsSkusResult->nomeReduzido);

                    if ($productWeight > 0 && $productWeight < $parentProduct->getWeight ())
                    {
                        $parentProduct->setWeight ($productWeight);
                    }
/*
                    $parentProduct->setWeight ($productWeight > 0 ? $productWeight : 999999);
*/
                    $parentProduct->setData ($productHeightAttribute, $productHeight);
                    $parentProduct->setData ($productWidthAttribute,  $productWidth);
                    $parentProduct->setData ($productLengthAttribute, $productLength);

                    // brand
                    $productBrandValue = $productsInfoResult->marca;
                    if (!empty ($productBrandValue))
                    {
                        $productBrandAttribute   = Mage::getStoreConfig ('mhub/product/brand');
                        $productBrandAttributeId = $this->getConfig ()->getAttributeId ($productBrandAttribute);

                        if (!$productBrandAttribute || !$productBrandAttributeId)
                        {
                            throw new Exception (Mage::helper ('mhub')->__('Brand attribute not found: %s value: %s', $productBrandAttribute, $productBrandValue));
                        }

                        $productBrandAttributeOptionId = $this->getConfig ()->addAttributeOptionValue ($productBrandAttributeId, array(
                            'order' => '0',
                            'label' => array (
                                array ('store_code' => 'admin', 'value' => $productBrandValue)
                            ),
                        ));

                        $parentProduct->setData ($productBrandAttribute, $productBrandAttributeOptionId);
                    }

                    // manufacturer
                    $productManufacturerValue = $productsInfoResult->codigoFornecedor;
                    if (!empty ($productManufacturerValue))
                    {
                        /*
                        $productManufacturerAttribute   = Mage::getStoreConfig ('mhub/product/manufacturer');
                        $productManufacturerAttributeId = $this->getConfig ()->getAttributeId ($productManufacturerAttribute);

                        if (!$productManufacturerAttribute || !$productManufacturerAttributeId)
                        {
                            throw new Exception (Mage::helper ('mhub')->__('Manufacturer attribute not found: %s value: %s', $productManufacturerAttribute, $productManufacturerValue));
                        }

                        $productManufacturerOptionId    = $this->getConfig ()->addAttributeOptionValue ($productManufacturerAttributeId, array(
                            'order' => '0',
                            'label' => array (
                                array ('store_code' => 'admin', 'value' => $productManufacturerValue)
                            ),
                        ));

                        $parentProduct->setData ($productManufacturerAttribute, $productManufacturerOptionId);
                        */
                        $parentProduct->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_MANUFACTURER, $productManufacturerValue);
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

                    $productSummaryAttribute      = Mage::getStoreConfig ('mhub/product/summary');
                    $parentProduct->setData ($productSummaryAttribute, $attributesResult);

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
                    $write->delete ($table, "product_id = {$parentProduct->getId ()}"); // remove previous super_links

                    $parentProduct->setCanSaveCustomOptions (true);
                    $parentProduct->setCanSaveConfigurableAttributes (true);

                    $productAttributeSets = null;

                    $configurableAttributeSets = explode (',', Mage::getStoreConfig ('mhub/attributes_set/product_associations'));
                    foreach ($configurableAttributeSets as $value)
                    {
                        list ($attributeSetId, $attributeId) = explode (':', $value);

                        $attribute = Mage::getModel ('eav/entity_attribute')->load ($attributeId);

                        $productAttributeSets [] = array ('attribute_id' => $attributeId, 'attribute_code' => $attribute->getAttributeCode (), 'attribute_set_id' => $attributeSetId);
                    }

                    $configurableAttributesData = null;

                    foreach ($productAttributeSets as $value)
                    {
                        if ($parentProduct->getAttributeSetId () == $value ['attribute_set_id'])
                        {
                            $configurableAttributesData [] = array ('attribute_id' => $value ['attribute_id'], 'attribute_code' => $value ['attribute_code']);
                        }
                    }

                    $parentProduct->setConfigurableAttributesData ($configurableAttributesData);

                    /*
                     * Simple products
                     */
                    $configurableProductsData = null;

                    $collection = Mage::getModel ('mhub/product_association')->getCollection ()
                        ->addFieldToFilter ('parent_sku', array ('eq' => $productId))
                    ;

                    foreach ($collection as $item)
                    {
                        $simpleProduct = Mage::getModel ('catalog/product')->loadByAttribute (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $item->getSku ());
                        if ($simpleProduct && intval ($simpleProduct->getId ()) > 0)
                        {
                            foreach ($productAttributeSets as $value)
                            {
                                if ($simpleProduct->getAttributeSetId () == $value ['attribute_set_id'])
                                {
                                    $configurableProductsData [$simpleProduct->getId ()][] = array ('attribute_id' => $value ['attribute_id']);
                                }
                            }

                            // lowest price
                            if ($simpleProduct->getPrice () < $parentProduct->getPrice ())
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
                    $parentProduct = Mage::getModel ('catalog/product')->loadByAttribute (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productId);
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
                    throw new Exception (Mage::helper ('mhub')->__('Empty SKU Availability!'));
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
                $setIsInStock = $productsAvailabilityResult->disponivel;
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
                    if ($mageProduct->getPrice () < $parentProduct->getPrice ())
                    {
                        $parentProduct->setPrice ($mageProduct->getPrice ())
                            ->setSpecialPrice ($mageProduct->getSpecialPrice ())
                            ->save ()
                        ;
                    }
                }
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
            throw new Exception (Mage::helper ('mhub')->__('Invalid Product Tracking!'));
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
    }

    private function disableMHubProduct (Epicom_MHub_Model_Product $product, $code = null)
    {
        if (!empty ($product->getExternalSku ()) && $code == 404)
        {
            $mageProduct = Mage::getModel ('catalog/product')->loadByAttribute (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $product->getExternalSku ());
            if ($mageProduct && intval ($mageProduct->getId ()) > 0)
            {
                $mageProduct->setStatus (Mage_Catalog_Model_Product_Status::STATUS_DISABLED)->save ();
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
                    return true;
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
        $length = $collection->count ();
        if (!$length) return false;

        $this->updateProducts ($collection);

        return true;
    }
}

