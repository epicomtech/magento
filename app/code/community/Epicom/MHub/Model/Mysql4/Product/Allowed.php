<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Mysql4_Product_Allowed extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct ()
    {
        $this->_init ('mhub/product_allowed', 'entity_id');
    }
}

