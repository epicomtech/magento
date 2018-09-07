<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Adminhtml_System_Config_Source_Attributes_Product_Configurable
    extends Epicom_MHub_Model_Adminhtml_System_Config_Source_Attributes_Product
{
    public function toOptionArray ()
    {
        $attributeSetCollection = $this->getAttributeSetCollection ();
        $attributeCollection = $this->getAttributeCollection ();

        foreach ($attributeSetCollection as $attributeset)
        {
            foreach ($attributeCollection as $attribute)
            {
                if ($attribute->getData ('is_configurable') == '1'
                    && $attribute->getData ('is_global') == '1'
                    && $attribute->getData ('is_user_defined') == '1')
                {
                    $result [] = array ('value' => $attributeset->getAttributeSetId () . ':' . $attribute->getAttributeId (),
                                        'label' => $attributeset->getAttributeSetName () . ' - ' . $attribute->getAttributeCode ());
                }
            }
        }

        return $result;
    }
}

