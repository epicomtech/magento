<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Mysql4_Nf extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct ()
    {
        $this->_init ('mhub/nf', 'entity_id');
    }
}

