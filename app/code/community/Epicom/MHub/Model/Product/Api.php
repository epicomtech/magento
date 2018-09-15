<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Product_Api extends Mage_Api_Model_Resource_Abstract
{
    const PRODUCTS_INFO_METHOD         = 'produtos/{productId}';
    const PRODUCTS_SKUS_METHOD         = 'produtos/{productId}/skus/{productSku}';
    const PRODUCTS_AVAILABILITY_METHOD = 'produtos/{productId}/skus/{productSku}/disponibilidade';

    const PRODUCTS_TRACKING_METHOD     = 'ofertas';

    public function manage ($type, $send_date, $parameters)
    {
        if (empty ($type) || empty ($send_date) || empty ($parameters))
        {
            $this->_fault ('invalid_request_param');
        }

        /**
         * Transaction
         */
        $productId  = strval ($parameters ['idProduto']);
        $productSku = strval ($parameters ['idSku']);

        if (empty ($productId) || empty ($productSku))
        {
            $this->_fault ('invalid_request_param');
        }

        $product = Mage::getModel ('mhub/product')
            ->setOperation (Epicom_MHub_Helper_Data::OPERATION_IN)
            ->setMethod ($type)
            ->setSendDate ($send_date)
            ->setParameters (json_encode ($parameters))
            ->setExternalId ($productId)
            ->setExternalSku ($productSku)
            ->setUpdatedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ()
        ;

        /**
         * Product Info
         */
        $helper = Mage::Helper ('mhub');

        $productsInfoMethod = str_replace ('{productId}', $productId, self::PRODUCTS_INFO_METHOD);
        $productsInfoResult = $helper->api ($productsInfoMethod);

        /**
         * SKU Info
         */
        $productsSkusMethod = str_replace (array ('{productId}', '{productSku}'), array ($productId, $productSku), self::PRODUCTS_SKUS_METHOD);
        $productsSkusResult = $helper->api ($productsSkusMethod);

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
        switch ($type)
        {
            case Epicom_MHub_Helper_Data::API_PRODUCT_DISASSOCIATED_SKU:
            case Epicom_MHub_Helper_Data::API_PRODUCT_ASSOCIATED_SKU:
            {
                return true; // cron will process this
            }
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_SKU:
            {
                return true; // cron will process this

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

                $productHasVariations = is_array ($productsSkusResult->grupos) && count ($productsSkusResult->grupos) > 0;

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
                }

                $mageProduct->setTypeId (Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
                $mageProduct->setVisibility ($productHasVariations
                    ? Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
                    : Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH
                );

                $mageProduct->setAttributeSetId ($productAttributeSetId);

                $mageProduct->setCategoryIds (array ($mageCategory->getId ()));

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
                    }

                    $parentProduct->setTypeId (Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);

                    $parentProduct->setAttributeSetId ($productAttributeSetId);

                    $parentProduct->setCategoryIds (array ($mageCategory->getId ()));

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

                    $parentProduct->setWeight ($productWeight > 0 ? $productWeight : 999999);

                    $parentProduct->setData ($productHeightAttribute, $productHeight);
                    $parentProduct->setData ($productWidthAttribute,  $productWidth);
                    $parentProduct->setData ($productLengthAttribute, $productLength);

                    // brand
                    $productBrandValue = $productsInfoResult->marca;
                    if (!empty ($productBrandValue))
                    {
                        $productBrandAttribute   = Mage::getStoreConfig ('mhub/product/brand');
                        $productBrandAttributeId = $this->getConfig ()->getAttributeId ($productBrandAttribute);

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
                        $productManufacturerAttribute   = Mage::getStoreConfig ('mhub/product/manufacturer');
                        $productManufacturerAttributeId = $this->getConfig ()->getAttributeId ($productManufacturerAttribute);

                        $productManufacturerOptionId    = $this->getConfig ()->addAttributeOptionValue ($productManufacturerAttributeId, array(
                            'order' => '0',
                            'label' => array (
                                array ('store_code' => 'admin', 'value' => $productManufacturerValue)
                            ),
                        ));

                        $parentProduct->setData ($productManufacturerAttribute, $productManufacturerOptionId);
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
                    $assocItem = Mage::getModel ('mhub/product_association')->load ($productsSkusResult->codigo, 'sku');
                    if (empty ($assocItem) || !$assocItem->getId ())
                    {
                        $assocItem = Mage::getModel ('mhub/product_association');
                        $assocItem->setSku ($productsSkusResult->codigo);
                    }

                    $assocItem->setParentSku ($productsInfoResult->codigo)
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
                        ->addFieldToFilter ('parent_sku', array ('eq' => $productsInfoResult->codigo))
                    ;

                    foreach ($collection as $item)
                    {
                        $simpleProduct = Mage::getModel ('catalog/product')->loadByAttribute ('sku', $item->getSku ());
                        if ($simpleProduct && intval ($simpleProduct->getId ()) > 0)
                        {
                            foreach ($productAttributeSets as $value)
                            {
                                if ($simpleProduct->getAttributeSetId () == $value ['attribute_set_id'])
                                {
                                    $configurableProductsData [$simpleProduct->getId ()][] = array ('attribute_id' => $value ['attribute_id']);
                                }
                            }
                        }
                    }

                    $parentProduct->setConfigurableProductsData ($configurableProductsData);

                    $parentProduct->save ();

                    $productIds [] = $parentProduct->getId ();

                    $parentNotExists = true;
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
                    $parentProduct->setPrice ($mageProduct->getPrice ())
                        ->setSpecialPrice ($mageProduct->getSpecialPrice ())
                        ->save ();
                    ;
                }
            }
        }

        // transaction
        $product->setProductId ($mageProduct->getId ())
            ->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ();

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

        $helper->api (self::PRODUCTS_TRACKING_METHOD, $post, 'PUT');

        return true;
    }

    protected function getConfig ()
    {
        return Mage::getModel ('mhub/config');
    }
}

