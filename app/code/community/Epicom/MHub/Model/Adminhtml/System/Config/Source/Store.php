<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Adminhtml_System_Config_Source_Store
    extends Mage_Adminhtml_Model_System_Config_Source_Store
{
    public function toOptionArray()
    {
        // if (!$this->_options)
        {
            $this->_options = Mage::getResourceModel ('core/store_collection')
                ->setLoadDefault (true)
                ->load ()
                ->toOptionArray ()
            ;
        }

        return $this->_options;
    }
}

