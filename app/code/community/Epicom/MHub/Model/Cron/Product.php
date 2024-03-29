<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Product extends Epicom_MHub_Model_Cron_Abstract
{
    const PRODUCTS_POST_METHOD       = 'produtos';
    const PRODUCTS_PATCH_METHOD      = 'produtos/{productId}';
    const PRODUCTS_SKUS_POST_METHOD  = 'produtos/{productCode}/skus';
    const PRODUCTS_SKUS_PATCH_METHOD = 'produtos/{productCode}/skus/{productSku}';

    const PRODUCTS_SKUS_AVAILABLE_POST_METHOD  = 'produtos/{productCode}/skus/{productSku}/disponibilidades';
    const PRODUCTS_SKUS_AVAILABLE_PATCH_METHOD = 'produtos/{productCode}/skus/{productSku}/disponibilidades/{marketplaceCode}';

    const DEFAULT_QUEUE_LIMIT = 60;

    protected $_codeAttribute      = null;
    protected $_modelAttribute     = null;
    protected $_eanAttribute       = null;
    protected $_descriptionAttribute = null;
    protected $_offerTitleAttribute = null;
    protected $_heightAttribute    = null;
    protected $_widthAttribute     = null;
    protected $_lengthAttribute    = null;
    protected $_priceAttribute     = null;
    protected $_specialAttribute   = null;

    protected $_hasProductAllowed = false;

    public function _construct ()
    {
        parent::_construct ();

        $this->_idAttribute        = Mage::getStoreConfig ('mhub/product/id');
        $this->_codeAttribute      = Mage::getStoreConfig ('mhub/product/code');
        $this->_modelAttribute     = Mage::getStoreConfig ('mhub/product/model');
        $this->_eanAttribute       = Mage::getStoreConfig ('mhub/product/ean');
        $this->_descriptionAttribute = Mage::getStoreConfig ('mhub/product/description');
        $this->_offerTitleAttribute = Mage::getStoreConfig ('mhub/product/offer_title');
        $this->_heightAttribute    = Mage::getStoreConfig ('mhub/product/height');
        $this->_widthAttribute     = Mage::getStoreConfig ('mhub/product/width');
        $this->_lengthAttribute    = Mage::getStoreConfig ('mhub/product/length');
        $this->_priceAttribute     = Mage::getStoreConfig ('mhub/product/price');
        $this->_specialAttribute    = Mage::getStoreConfig ('mhub/product/special_price');

        $collection = Mage::getModel ('mhub/product_allowed')->getCollection ();
        $collection->getSelect ()->limit (1);

        $this->_hasProductAllowed = $collection->getSize ();
    }

    private function readMHubProductsMagento ()
    {
        // categories
        $collection = Mage::getModel ('catalog/category')->getCollection ()
            // ->addAttributeToFilter (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SET,          array ('notnull' => true))
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_ISACTIVE,     array ('eq' => true))
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SENDPRODUCTS, array ('eq' => true))
        ;

        $collection->getSelect ()->reset (Zend_Db_Select::COLUMNS)->columns ('entity_id');

        $mageCategoryIds = array_keys ($collection->exportToArray (array ('entity_id')));

        foreach ($mageCategoryIds as $_id)
        {
            // products
            $collection = Mage::getModel ('catalog/product')->getCollection ()
                ->joinField ('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'inner')
                ->addAttributeToFilter ('category_id', array ('in' => $_id))
                ->addAttributeToFilter ('type_id', array ('in' => array (Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)))
                ->addAttributeToSelect ($this->_idAttribute)
                ->addAttributeToSelect ($this->_codeAttribute)
            ;

            $productOperation = Epicom_MHub_Helper_Data::OPERATION_OUT;

            $select = $collection->getSelect ()
                ->joinLeft(
                    array ('mhub' => Epicom_MHub_Helper_Data::PRODUCT_TABLE),
                    "e.entity_id = mhub.product_id AND mhub.operation = '{$productOperation}'",
                    array('mhub_updated_at' => 'mhub.updated_at', 'mhub_synced_at' => 'mhub.synced_at')
                )->where ('e.updated_at > mhub.synced_at OR mhub.synced_at IS NULL')
            ;

            // orphans
            $select->joinLeft (
                array ('relation' => 'catalog_product_super_link'),
                'e.entity_id = relation.product_id',
                array ('parent_id')
            )->where (sprintf ("(type_id = '%s' AND parent_id IS NULL) || (type_id = '%s')",
                Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
            ));

            foreach ($collection as $product)
            {
                $productId  = $product->getId();

                $mhubProductCollection = Mage::getModel ('mhub/product')->getCollection ()
                    ->addFieldToFilter ('product_id', $productId)
                    ->addFieldToFilter ('operation',  $productOperation)
                ;

                $mhubProduct = $mhubProductCollection->getFirstItem ();

                $productSku  = $product->getSku();
                $productCode = $product->getData ($this->_codeAttribute);
                $productCode = preg_replace ('/[^A-Za-z0-9_\-\.]+/', "", $productCode ? $productCode : $productSku);

                $mhubProduct->setProductId ($productId)
                    ->setExternalCode ($productCode)
                    ->setExternalSku ($product->getData ($this->_idAttribute))
                    ->setOperation ($productOperation)
                    ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
                    ->setUpdatedAt (date ('c'))
                    ->save ();
            }
        }

        return true;
    }

    private function readMHubProductsCollection ()
    {
        $limit = intval (Mage::getStoreConfig ('mhub/queue/product'));

        $collection = Mage::getModel ('mhub/product')->getCollection ();

        $collection->getSelect ()
               ->where ('synced_at < updated_at OR synced_at IS NULL')
               ->where (sprintf ("operation = '%s'", Epicom_MHub_Helper_Data::OPERATION_OUT))
               ->where ('external_code IS NOT NULL')
               ->group ('product_id')
               ->order ('updated_at DESC')
               ->order ('status DESC')
               ->limit ($limit ? $limit : self::DEFAULT_QUEUE_LIMIT)
        ;

        return $collection;
    }

    private function updateProducts ($collection)
    {
        $brandAttribute = Mage::getStoreConfig ('mhub/product/brand');

        foreach ($collection as $product)
        {
            $result = null;

            try
            {
                $result = $this->updateMHubProduct ($product, $brandAttribute);
            }
            catch (Exception $e)
            {
                $this->logMHubProduct ($product, $e->getMessage ());

                self::logException ($e);
            }

            if (!empty ($result)) $this->cleanupMHubProduct ($product, $result);
        }

        return true;
    }

    private function updateMHubProduct (Epicom_MHub_Model_Product $product, $brandAttribute = null)
    {
        if ($this->_hasProductAllowed)
        {
            $allowedCollection = Mage::getModel ('mhub/product_allowed')->getCollection ()
                ->addFieldToFilter ('code', $product->getExternalCode ())
            ;

            $allowedCollection->getSelect ()->limit (1);

            if (!$allowedCollection->getSize ()) return false;
        }

        $productId = $product->getProductId ();

        $mageProduct = Mage::getModel ('catalog/product')->load ($productId);

        if (!$mageProduct || !$mageProduct->getId ())
        {
            return false;
        }

        $collection = Mage::getModel ('catalog/category')->getCollection ()
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_ISACTIVE,     array ('eq' => true))
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SENDPRODUCTS, array ('eq' => true))
            ->addIdFilter ($mageProduct->getCategoryIds ())
        ;

        $mageCategoryId = $collection->count () > 0 ? $collection->getFirstItem ()->getId () : null;

        $post = array(
            'codigo'          => $product->getExternalCode (),
            'nome'            => $mageProduct->getName (),
            'nomeReduzido'    => substr ($mageProduct->getData ($this->_offerTitleAttribute), 0, 60), // $mageProduct->getShortDescription (),
            'descricao'       => strval ($mageProduct->getData ($this->_descriptionAttribute)), // $mageProduct->getDescription (),
            'codigoCategoria' => $mageCategoryId,
            'codigoMarca'     => $mageProduct->getData ($brandAttribute),
            'palavrasChave'   => $mageProduct->getMetaKeyword (),
            'grupos'          => array ()
        );

        /**
         * Groups
         */
        $mhubAttributeGroups = array ();

        $collection = Mage::getModel ('mhub/attributegroup')->getCollection ()
            ->addFieldToFilter ('attribute_set_id', array ('eq' => $mageProduct->getAttributeSetId ()))
        ;

        if ($collection->count () > 0)
        {
            foreach ($collection as $item)
            {
                $mhubAttributeGroups [$item->getGroupName ()] = explode (',', $item->getAttributeCodes ());
            }
        }

        if (count ($mhubAttributeGroups) > 0)
        {
            foreach ($mhubAttributeGroups as $id => $group)
            {
                $result = array ('nome' => $id);

                foreach ($group as $code)
                {
                    $value = strval ($mageProduct->getResource ()->getAttribute ($code)->getFrontend ()->getOption ($mageProduct->getData ($code)));

                    if (!empty ($value))
                    {
                        $attribute = Mage::getModel ('catalog/entity_attribute')->loadByCode (Mage_Catalog_Model_Product::ENTITY, $code);

                        $code = $attribute->getFrontendLabel ();

                        $result ['atributos'][] = array ('nome' => $code, 'valor' => $value);
                    }
                }

                $post ['grupos'][] = $result;
            }
        }

        /**
         * Product
         */
        try
        {
            $this->getHelper ()->api (self::PRODUCTS_POST_METHOD, $post);
        }
        catch (Exception $e)
        {
            if ($e->getCode () == 409 /* Resource Exists */)
            {
                $productsPatchMethod = str_replace ('{productId}', $product->getExternalCode (), self::PRODUCTS_PATCH_METHOD);

                $this->getHelper ()->api ($productsPatchMethod, $post, 'PATCH');
            }
            else
            {
                throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
            }
        }

        /**
         * External ID
         */
        $productsInfoMethod = str_replace ('{productId}', $product->getExternalCode (), self::PRODUCTS_PATCH_METHOD);

        $productsInfoResult = $this->getHelper ()->api ($productsInfoMethod);

        /*
        $mageProduct->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productsInfoResult->id)->save ();
        */

        /**
         * Children
         */
        $childrenIds = array ($productId);

        $childrenVariations = null;

        if (!strcmp ($mageProduct->getTypeId (), Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE))
        {
            $result = Mage::getModel ('catalog/product_type_configurable')->getChildrenIds ($productId);

            if (!empty ($result [0]) && count ($childrenIds [0]) > 0) $childrenIds = $result [0];

            $childrenVariations = $mageProduct->getTypeInstance ()->getConfigurableAttributesAsArray ($mageProduct);
        }

        /**
         * SKUs
         */
        $collection = Mage::getModel ('catalog/product')->getCollection ()
            ->addIdFilter ($childrenIds)
            ->addAttributeToSelect (array ('name', 'short_description', 'weight', 'price', 'special_price'))
            ->addAttributeToSelect (array (
                $this->_codeAttribute,
                $this->_modelAttribute,
                $this->_eanAttribute,
                $this->_descriptionAttribute,
                $this->_offerTitleAttribute,
                $this->_heightAttribute,
                $this->_widthAttribute,
                $this->_lengthAttribute,
                $this->_priceAttribute,
                $this->_specialAttribute
            ))
            ->joinTable (array ('stock' => 'cataloginventory/stock_item'),
                'product_id = entity_id', array ('qty', 'is_in_stock'), null, 'left')
        ;

        if (is_array ($childrenVariations) && count ($childrenVariations) > 0)
        {
            foreach ($childrenVariations as $variation)
            {
                $code = $variation ['attribute_code'];

                $collection->addAttributeToSelect ($code);
            }
        }

        if (count ($mhubAttributeGroups) > 0)
        {
            foreach ($mhubAttributeGroups as $id => $group)
            {
                foreach ($group as $code)
                {
                    $collection->addAttributeToSelect ($code);
                }
            }
        }

        $parentProduct = Mage::getModel ('catalog/product')->load ($productId); // images

        $parentHeight = $parentProduct->getData ($this->_heightAttribute);
        $parentWidth  = $parentProduct->getData ($this->_widthAttribute);
        $parentLength = $parentProduct->getData ($this->_lengthAttribute);

        foreach ($collection as $mageProduct)
        {
            $productSku  = $mageProduct->getSku ();
            $productCode = $mageProduct->getData ($this->_codeAttribute);
            $productCode = preg_replace ('/[^A-Za-z0-9_\-\.]+/', "", $productCode ? $productCode : $productSku);

            if ($this->_hasProductAllowed)
            {
                $allowedCollection = Mage::getModel ('mhub/product_allowed')->getCollection ()
                    ->addFieldToFilter ('code', $product->getExternalCode ())
                    // ->addFieldToFilter ('sku',  $productCode)
                ;

                $allowedCollection->getSelect ()->limit (1);

                if (!$allowedCollection->getSize ()) continue;
            }

            $productQty = intval ($mageProduct->getQty ());

            $productWeight = floatval ($mageProduct->getWeight ());

            $productWeightMode = Mage::getStoreConfig ('mhub/product/weight_mode');

            if (!strcmp ($productWeightMode, Epicom_MHub_Helper_Data::PRODUCT_WEIGHT_KILO) && $productWeight > 0)
            {
                $productWeight = intval ($productWeight * 1000);
            }

            $productHeight = $mageProduct->getData ($this->_heightAttribute);
            $productWidth  = $mageProduct->getData ($this->_widthAttribute);
            $productLength = $mageProduct->getData ($this->_lengthAttribute);

            $post = array(
                'nome'            => $mageProduct->getName (),
                'nomeReduzido'    => substr ($mageProduct->getData ($this->_offerTitleAttribute), 0, 60), // $mageProduct->getShortDescription (),
                'url'             => $mageProduct->getUrlModel ()->getUrl ($mageProduct),
                'codigo'          => $productCode,
                'modelo'          => $mageProduct->getData ($this->_modelAttribute),
                'ean'             => $mageProduct->getData ($this->_eanAttribute),
                'foraDeLinha'     => false,
                'estoque'         => $productQty > 0 ? $productQty : 0,
                'dimensoes' => array(
                    /*
                    'altura'      => $mageProduct->getData ($this->_heightAttribute),
                    'largura'     => $mageProduct->getData ($this->_widthAttribute),
                    'comprimento' => $mageProduct->getData ($this->_lengthAttribute),
                    */
                    'altura'      => $productHeight ? $productHeight : $parentHeight,
                    'largura'     => $productWidth ? $productWidth : $parentWidth,
                    'comprimento' => $productLength ? $productLength : $parentLength,
                    'peso'        => $productWeight, // intval ($mageProduct->getWeight ())
                ),
                'imagens' => array (),
                'grupos'  => array (),
                'variacoes' => array (),
            );

            if (count ($mhubAttributeGroups) > 0)
            {
                foreach ($mhubAttributeGroups as $id => $group)
                {
                    $result = array ('nome' => $id);

                    foreach ($group as $code)
                    {
                        $value = strval ($mageProduct->getResource ()->getAttribute ($code)->getFrontend ()->getOption ($mageProduct->getData ($code)));

                        if (!empty ($value))
                        {
                            $attribute = Mage::getModel ('catalog/entity_attribute')->loadByCode (Mage_Catalog_Model_Product::ENTITY, $code);

                            $code = $attribute->getFrontendLabel ();

                            $result ['atributos'][] = array ('nome' => $code, 'valor' => $value);
                        }
                    }

                    $post ['grupos'][] = $result;
                }
            }

            if (is_array ($childrenVariations) && count ($childrenVariations) > 0)
            {
                foreach ($childrenVariations as $variation)
                {
                    $code = $variation ['attribute_code'];
                    $name = $variation ['frontend_label'];

                    $value = strval ($mageProduct->getResource ()->getAttribute ($code)->getFrontend ()->getOption ($mageProduct->getData ($code)));

                    if (!empty ($value))
                    {
                        $post ['variacoes'][] = array ('nome' => $name, 'valor' => $value);
                    }
                }
            }

            $attribute = $mageProduct->getResource ()->getAttribute ('media_gallery');
            $attribute->getBackend ()->afterLoad ($mageProduct);

            foreach ($mageProduct->getMediaGalleryImages () as $_image)
            {
                $post ['imagens'][] = array(
                    'zoom'  => (string) $_image->getUrl (), // Mage::helper ('catalog/image')->init ($mageProduct, 'image',       $_image->getFile ()),
                    'maior' => (string) $_image->getUrl (), // Mage::helper ('catalog/image')->init ($mageProduct, 'small_image', $_image->getFile ()),
                    'menor' => (string) $_image->getUrl (), // Mage::helper ('catalog/image')->init ($mageProduct, 'thumbnail',   $_image->getFile ()),
                    'order' => $_image->getPosition (),
                );
            }

            if (count ($post ['imagens']) == 0)
            {
                foreach ($parentProduct->getMediaGalleryImages () as $_image)
                {
                    $post ['imagens'][] = array(
                        'zoom'  => (string) $_image->getUrl (), // Mage::helper ('catalog/image')->init ($parentProduct, 'image',       $_image->getFile ()),
                        'maior' => (string) $_image->getUrl (), // Mage::helper ('catalog/image')->init ($parentProduct, 'small_image', $_image->getFile ()),
                        'menor' => (string) $_image->getUrl (), // Mage::helper ('catalog/image')->init ($parentProduct, 'thumbnail',   $_image->getFile ()),
                        'order' => $_image->getPosition (),
                    );
                }
            }

            try
            {
                $productsSkusPostMethod = str_replace ('{productCode}', $product->getExternalCode (), self::PRODUCTS_SKUS_POST_METHOD);

                $this->getHelper ()->api ($productsSkusPostMethod, $post);
            }
            catch (Exception $e)
            {
                if ($e->getCode () == 409 /* Resource Exists */)
                {
                    $productsSkusPatchMethod = str_replace (array ('{productCode}', '{productSku}'), array ($product->getExternalCode (), $productCode), self::PRODUCTS_SKUS_PATCH_METHOD);

                    $this->getHelper ()->api ($productsSkusPatchMethod, $post, 'PATCH');
                }
                else
                {
                    throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
                }
            }

            /**
             * External ID
             */
            $productsSkusMethod = str_replace (array ('{productCode}', '{productSku}'), array ($product->getExternalCode (), $productCode), self::PRODUCTS_SKUS_PATCH_METHOD);

            $productsSkusResult = $this->getHelper ()->api ($productsSkusMethod);

            /*
            $mageProduct->setData (Epicom_MHub_Helper_Data::PRODUCT_ATTRIBUTE_ID, $productsSkusResult->id)->save ();
            */

            /**
             * Availability
             */
            $priceFrom = $mageProduct->getData ($this->_specialAttribute) ? $mageProduct->getData ($this->_priceAttribute) : null;
            $priceTo   = $mageProduct->getData ($this->_specialAttribute) ? $mageProduct->getData ($this->_specialAttribute) : $mageProduct->getData ($this->_priceAttribute);

            $marketplaceProduct = new Varien_Object ();
            $marketplaceProduct->addData (array(
                'value'            => $mageProduct->getData ($this->_priceAttribute),
                'special_price'    => $mageProduct->getData ($this->_specialAttribute),
                'is_active'        => $mageProduct->getStatus (),
                'marketplace_code' => null,
            ));

            $marketplaceItems = array ($marketplaceProduct);

            $marketplaceCollection = Mage::getModel ('mhub/product_attribute_marketplace_price')->getCollection ()
                ->addFieldToFilter ('main_table.entity_id', array ('eq' => $mageProduct->getId ()))
            ;

            $marketplaceCollection->getSelect ()
                ->join(
                    array ('marketplace' => Epicom_MHub_Helper_Data::MARKETPLACE_TABLE),
                    'main_table.marketplace_id = marketplace.external_id',
                    array ('code')
                )
            ;

            foreach ($marketplaceCollection as $marketplace)
            {
                $marketplaceItems [] = $marketplace;
            }

            foreach ($marketplaceItems as $marketplace)
            {

            $priceFrom = $marketplace->getSpecialPrice () ? $marketplace->getValue () : null;
            $priceTo   = $marketplace->getSpecialPrice () ? $marketplace->getSpecialPrice () : $marketplace->getValue ();

            $post = array(
                'nome'       => $mageProduct->getName (),
                /*
                'disponivel' => boolval ($mageProduct->getIsInStock ()),
                */
                'disponivel' => $marketplace->getIsActive () == Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
                'precoDe'    => $priceFrom,
                'preco'      => $priceTo,
                'codigoMarketplace' => $marketplace->getCode (),
            );

            try
            {
                $productsSkusAvailablePostMethod = str_replace (array ('{productCode}', '{productSku}'), array ($product->getExternalCode (), $productCode), self::PRODUCTS_SKUS_AVAILABLE_POST_METHOD);

                $this->getHelper ()->api ($productsSkusAvailablePostMethod, $post);
            }
            catch (Exception $e)
            {
                if ($e->getCode () == 409 /* Resource Exists */)
                {
                    $productsSkusAvailablePatchMethod = str_replace (
                        array ('{productCode}', '{productSku}', '{marketplaceCode}'),
                        array ($product->getExternalCode (), $productCode, $marketplaceCode),
                        self::PRODUCTS_SKUS_AVAILABLE_PATCH_METHOD
                    );

                    $this->getHelper ()->api ($productsSkusAvailablePatchMethod, $post, 'PATCH');
                }
                else
                {
                    throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
                }
            }

            } // marketplaceItems
        }

        return $productsInfoResult->id;
    }

    private function cleanupMHubProduct (Epicom_MHub_Model_Product $product, $externalSku)
    {
        if ($externalSku !== null && $externalSku !== true)
        {
            $product->setExternalSku ($externalSku);
        }

        $product->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ();

        return true;
    }

    private function logMHubProduct (Epicom_MHub_Model_Product $product, $message = null)
    {
        $product->setStatus (Epicom_MHub_Helper_Data::STATUS_ERROR)->setMessage ($message)->save ();
    }

    public function run ()
    {
        if (!$this->getStoreConfig ('active') || $this->getHelper ()->isMarketplace ())
        {
            return false;
        }

        $result = $this->readMHubProductsMagento ();

        if (!$result) return false;

        $collection = $this->readMHubProductsCollection ();

        if (!$collection->getSize ()) return false;

        $this->updateProducts ($collection);

        return true;
    }
}

