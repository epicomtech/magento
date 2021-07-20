<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Product_Allowed extends Mage_Core_Model_Abstract
{
    protected function _construct ()
    {
       $this->_init ('mhub/product_allowed');
    }
}

