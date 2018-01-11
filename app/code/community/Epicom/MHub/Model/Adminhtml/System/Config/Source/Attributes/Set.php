<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Adminhtml_System_Config_Source_Attributes_Set
    extends Epicom_MHub_Model_Adminhtml_System_Config_Source_Attributes_Product
{
    public function toOptionArray ()
    {
        $attributeSetCollection = $this->getAttributeSetCollection ();

        foreach ($attributeSetCollection as $attributeset)
        {
            $result [] = array ('value' => $attributeset->getAttributeSetId (),
                                'label' => $attributeset->getAttributeSetName ());
        }

        return $result;
    }

    public function toArray ()
    {
        $result = array ();

        foreach ($this->toOptionArray () as $option)
        {
            $result [$option ['value']] = $option ['label'];
        }

        return $result;
    }
}

