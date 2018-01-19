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

        $productSkuAttribute = Mage::getStoreConfig ('mhub/product/sku');

        $mageProduct = Mage::getModel ('catalog/product')->loadByAttribute (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productId);
        if (!$mageProduct || !$mageProduct->getId())
        {
            $mageProduct = Mage::getModel ('catalog/product')->loadByAttribute ($productSkuAttribute, $productSku);
            if (!$mageProduct || !$mageProduct->getId())
            {
                $productNotExists = true;
            }
        }

        if ($productNotExists)
        {
            $mageProduct = Mage::getModel ('catalog/product');
        }

        $mageProduct->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productId);
        $mageProduct->setData ($productSkuAttribute, $productSku);

        /**
         * Parse
         */
        switch ($type)
        {
            case Epicom_MHub_Helper_Data::API_PRODUCT_DISASSOCIATED_SKU:
            {
                break;
            }
            case Epicom_MHub_Helper_Data::API_PRODUCT_ASSOCIATED_SKU:
            {
                if (!$productNotExists)
                {
                    return Mage::app ()->getResponse ()->setBody ('Product Already Exists'); // $this->_fault ('product_already_exists');
                }

                // default
                $mageProduct->setTypeId (Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
                $mageProduct->setTaxClassId (0); // none
                $mageProduct->setVisibility (Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
                $mageProduct->setWeight (999999);
                $mageProduct->setPrice (999999);
                $mageProduct->setWebsiteIds (array (1)); // Default

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

                $mageProduct->setAttributeSetId ($categoryAttributeSetId ? $categoryAttributeSetId : $defaultAttributeSetId);
            }
            case Epicom_MHub_Helper_Data::API_PRODUCT_UPDATED_SKU:
            {
                // child
                $mageProduct->setName ($productsSkusResult->nome);
                $mageProduct->setUrl ($productsSkusResult->nome);
                $mageProduct->setStatus ($productsSkusResult->ativo
                    ? Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                    : Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                );

                $productWeight = intval ($productsSkusResult->dimensoes->peso);
                $mageProduct->setWeight ($productWeight > 0 ? $productWeight : 999999);

                // custom
                $productCodeAttribute         = Mage::getStoreConfig ('mhub/product/code');
                $productEanAttribute          = Mage::getStoreConfig ('mhub/product/ean');
                $productUrlAttribute          = Mage::getStoreConfig ('mhub/product/url');
                $productOutOfLineAttribute    = Mage::getStoreConfig ('mhub/product/out_of_line');
                $productShortNameAttribute    = Mage::getStoreConfig ('mhub/product/short_name');
                $productHeightAttribute       = Mage::getStoreConfig ('mhub/product/height');
                $productWidthAttribute        = Mage::getStoreConfig ('mhub/product/width');
                $productLengthAttribute       = Mage::getStoreConfig ('mhub/product/length');

                $mageProduct->setData ($productCodeAttribute, $productsSkusResult->codigo);
                $mageProduct->setData ($productEanAttribute, $productsSkusResult->ean);
                $mageProduct->setData ($productUrlAttribute, $productsSkusResult->url);
                $mageProduct->setData ($productOutOfLineAttribute, $productsSkusResult->foraDeLinha);
                $mageProduct->setData ($productShortNameAttribute, $productsSkusResult->nomeReduzido);
                $mageProduct->setData ($productHeightAttribute, $productsSkusResult->dimensoes->altura);
                $mageProduct->setData ($productWidthAttribute, $productsSkusResult->dimensoes->largura);
                $mageProduct->setData ($productLengthAttribute, $productsSkusResult->dimensoes->comprimento);

                // parent
                $mageProduct->setDescription ($productsInfoResult->descricao);
                $mageProduct->setMetaKeyword ($productsInfoResult->palavrasChave);

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

                    $mageProduct->setData ($productBrandAttribute, $productBrandAttributeOptionId);
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

                    $mageProduct->setData ($productManufacturerAttribute, $productManufacturerOptionId);
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
                $mageProduct->setData ($productSummaryAttribute, $attributesResult);

                $mageProduct->save ();

                // images
                $baseImageSet = false;

                foreach ($productsSkusResult->imagens as $id => $image)
                {
                    $client = new Zend_Http_Client ();
                    $client->setUri ($image->zoom);

                    $response = $client->request ('GET');

                    $imageContent = $response->getRawBody ();
                    if (!empty ($imageContent))
                    {
                        $_image ['file'] = array ('content' => base64_encode ($imageContent), 'mime' => 'image/jpeg');
                        $_image ['types'] = !$id ? $_image ['types'] = array ('image', 'small_image', 'thumbnail') : array ();
                        $_image ['exclude'] = 0;

                        try
                        {
                            Mage::getModel ('catalog/product_attribute_media_api')->create ($mageProduct->getId (), $_image);
                        }
                        catch (Exception $e)
                        {
                            // nothing
                        }
                    }
                }

                $productNotExists = false;
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

