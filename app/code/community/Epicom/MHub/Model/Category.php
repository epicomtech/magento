<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Category extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
       $this->_init('mhub/category');
    }
}

