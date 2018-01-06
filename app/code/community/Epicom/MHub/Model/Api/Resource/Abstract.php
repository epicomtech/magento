<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

/**
 * Api resource abstract
 */
abstract class Epicom_MHub_Model_Api_Resource_Abstract extends Mage_Api_Model_Resource_Abstract
{
    protected function _log ($model, $message, $fault = null)
    {
        Mage::app ()->getResponse ()->setHttpResponseCode (400);

        $model->setStatus (Epicom_MHub_Helper_Data::STATUS_ERROR)
            ->setMessage ($message)
            ->save ()
        ;

        if ($fault) $this->_fault ($fault);
    }
}

