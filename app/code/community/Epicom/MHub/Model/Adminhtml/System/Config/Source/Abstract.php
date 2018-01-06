<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

abstract class Epicom_MHub_Model_Adminhtml_System_Config_Source_Abstract
{
    protected $_entityType = '';

    public function getEntityTypeId ()
    {
        return Mage::helper ('mhub')->getEntityTypeId ($this->_entityType);
    }
}

