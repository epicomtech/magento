<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Category extends Epicom_MHub_Model_Cron_Abstract
{
    const CATEGORIES_POST_METHOD       = 'categorias';
    const CATEGORIES_PATCH_METHOD      = 'categorias/{categoryId}';
    const CATEGORIES_ATTRIBUTES_METHOD = 'categorias/{categoryId}/atributos';

    const ATTRIBUTES_METHOD = 'atributos';

    private function readMHubCategoriesMagento ()
    {
        $collection = Mage::getModel ('catalog/category')->getCollection ()
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SET,      array ('notnull' => true))
            ->addAttributeToFilter (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_ISACTIVE, array ('eq' => true))
        ;

        $select = $collection->getSelect ()
            ->joinLeft(
                array ('mhub' => Epicom_MHub_Helper_Data::CATEGORY_TABLE),
                'e.entity_id = mhub.category_id',
                array('mhub_updated_at' => 'mhub.updated_at', 'mhub_synced_at' => 'mhub.synced_at')
            )->where ('e.updated_at > mhub.synced_at OR mhub.synced_at IS NULL')
        ;

        $categoryIds = array ();

        foreach ($collection as $category)
        {
            foreach ($category->getParentCategories () as $parent)
            {
                if (!in_array ($parent->getId (), $categoryIds))
                {
                    $categoryIds [] = $parent->getId ();
                }
            }

            if (!in_array ($category->getId (), $categoryIds))
            {
                $categoryIds [] = $category->getId ();
            }
        }

        $collection = Mage::getModel ('catalog/category')->getCollection ()
            ->addAttributeToSelect (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SET)
            ->addAttributeToSelect (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_ISACTIVE)
            ->addIdFilter ($categoryIds)
        ;

        foreach ($collection as $category)
        {
            $categoryId = $category->getId ();

            $categoryAttributeSetId = $category->getData (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_SET);
            $defaultAttributeSetId  = Mage::getStoreConfig ('mhub/attributes_set/product');

            $mhubCategory = Mage::getModel ('mhub/category')->load ($categoryId, 'category_id');
            $mhubCategory->setCategoryId ($categoryId)
                ->setAttributeSetId ($categoryAttributeSetId ? $categoryAttributeSetId : $defaultAttributeSetId)
                ->setAssociable (intval ($categoryAttributeSetId) > 0 ? true : false)
                ->setStatus (Epicom_MHub_Helper_Data::STATUS_PENDING)
                ->setUpdatedAt (date ('c'))
                ->save ();
        }

        return true;
    }

    private function readMHubCategoriesCollection ()
    {
        $collection = Mage::getModel ('mhub/category')->getCollection ();
        $select = $collection->getSelect ();
        $select->where ('synced_at < updated_at OR synced_at IS NULL')
               // ->group ('category_id')
               // ->order ('updated_at DESC')
        ;

        return $collection;
    }

    private function updateCategories ($collection)
    {
        foreach ($collection as $category)
        {
            $result = null;

            try
            {
                $result = $this->updateMHubCategory ($category);
            }
            catch (Exception $e)
            {
                $this->logMHubCategory ($category, $e->getMessage ());

                Mage::logException ($e);
            }

            if (!empty ($result)) $this->cleanupMHubCategory ($category);
        }

        return true;
    }

    private function updateMHubCategory (Epicom_MHub_Model_Category $category)
    {
        $categoryId = $category->getCategoryId ();

        $mageCategory = Mage::getModel ('catalog/category');
        $loaded = $mageCategory->load ($categoryId);
        if (!$loaded || !$loaded->getId ())
        {
            return false;
        }
        else
        {
            $mageCategory = $loaded;
        }

        $parentCategory = $mageCategory->getParentCategory ();

        $parentId = $parentCategory->getId () != Mage_Catalog_Model_Category::TREE_ROOT_ID ? $parentCategory->getId () : null;

        $post = array(
            'codigo'       => $mageCategory->getId (),
            'nome'         => $mageCategory->getName (),
            'categoriaPai' => $parentId,
            'associavel'   => $mageCategory->getChildrenCount () > 0 ? true : false,
            'ativo'        => $mageCategory->getData (Epicom_MHub_Helper_Data::CATEGORY_ATTRIBUTE_ISACTIVE) ? true : false,
        );

        try
        {
            $this->getHelper ()->api (self::CATEGORIES_POST_METHOD, $post);
        }
        catch (Exception $e)
        {
            if ($e->getCode () == 409 /* Resource Exists */)
            {
                $categoriesPatchMethod = str_replace ('{categoryId}', $mageCategory->getId (), self::CATEGORIES_PATCH_METHOD);

                $this->getHelper ()->api ($categoriesPatchMethod, $post, 'PATCH');
            }
            else
            {
                throw Mage::exception ('Epicom_MHub', $e->getMessage (), $e->getCode ());
            }
        }

        /**
         * Attributes
         */
        if (!$this->getHelper ()->isMarketplace ()) return true;

        $categoriesAttributesMethod = str_replace ('{categoryId}', $mageCategory->getId (), self::CATEGORIES_ATTRIBUTES_METHOD);

        $collection = Mage::getResourceModel ('eav/entity_attribute_collection');
        $collection->setAttributeSetFilter ($category->getAttributeSetId())
            ->getSelect()->reset(Zend_Db_Select::WHERE) // just join
        ;

        $entityTypeId = Mage::getModel('eav/entity')
            ->setType('catalog_product')
            ->getTypeId();

        $collection->setEntityTypeFilter($entityTypeId)
            ->setFrontendInputTypeFilter(array ('in' => array ('select', 'multiselect', 'text')))
        ;

        $select = $collection->getSelect()->group('main_table.attribute_id');

        $attributeIds = Mage::getStoreConfig ('mhub/attributes/product');
        $attributeIds = !empty ($attributeIds) ? $attributeIds : '0';
        $categoryUseAttributeSet = Mage::getStoreConfigFlag('mhub/category/use_attribute_set');
        if ($categoryUseAttributeSet)
        {
            $select->where(
                "main_table.attribute_id IN ({$attributeIds}) OR (entity_attribute.attribute_set_id = {$category->getAttributeSetId()} AND main_table.is_required = 1)"
            );
        }
        else
        {
            $select->where("main_table.attribute_id IN ({$attributeIds})");
        }

        if ($collection->count() > 0)
        {
            foreach ($collection as $attribute)
            {
                $options = array();

                if ($attribute->getSourceModel())
                {
                    $options = $attribute->getSource()->getAllOptions ();
                }
                else
                {
                    $options = Mage::getResourceModel('eav/entity_attribute_option_collection')
                        ->setAttributeFilter($attribute->getId())
                        ->setStoreFilter(0)
                        ->toOptionArray()
                    ;
                }

                $values = array ();

                foreach ($options as $_option)
                {
                    if (!empty ($_option ['value']))
                    {
                        $values [] = array(
                            'Codigo' => $_option ['value'],
                            'Nome'   => $_option ['label'],
                        );
                    }
                }

                $attributeCode          = $attribute->getAttributeCode ();
                $attributeFrontendLabel = $attribute->getFrontendLabel ();

                $post = array(
                    'Codigo'  => $attributeCode,
                    'Nome'    => $attributeFrontendLabel ? $attributeFrontendLabel : $attributeCode,
                    'Valores' => $values,
                    'atributoValorLivre' => !$attribute->getSourceModel () ? true : false
                );

                $this->getHelper ()->api (self::ATTRIBUTES_METHOD, $post, 'PUT');

                /**
                 * Associations
                 */
                $allowedValues = array ();
                if (is_array ($values))
                {
                    foreach ($values as $item)
                    {
                        $allowedValues [] = $item ['Codigo'];
                    }
                }

                $post = array(
                    'AllowVariations' => true,
                    'Codigo'          => $attribute->getAttributeCode(),
                    'Obrigatorio'     => true,
                    'CodigosValores'  => $allowedValues,
                );

                $this->getHelper ()->api ($categoriesAttributesMethod, $post, 'PUT');
            }
        }

        return true;
    }

    private function cleanupMHubCategory (Epicom_MHub_Model_Category $category)
    {
        $category->setSyncedAt (date ('c'))
            ->setStatus (Epicom_MHub_Helper_Data::STATUS_OKAY)
            ->setMessage (new Zend_Db_Expr ('NULL'))
            ->save ();

        return true;
    }

    private function logMHubCategory (Epicom_MHub_Model_Category $category, $message = null)
    {
        $category->setStatus (Epicom_MHub_Helper_Data::STATUS_ERROR)->setMessage ($message)->save ();
    }

    public function run ()
    {
        if (!$this->getStoreConfig ('active')) return false;

        $result = $this->readMHubCategoriesMagento ();
        if (!$result) return false;

        $collection = $this->readMHubCategoriesCollection ();
        $length = $collection->count ();
        if (!$length) return false;

        $this->updateCategories ($collection);

        return true;
    }
}

