<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron_Abstract
{
    protected $_debug = false;

    public function __construct ()
    {
        $this->_debug = $this->getStoreConfig ('debug');

        Mage::app ()->getTranslator ()->init (Mage_Core_Model_App_Area::AREA_ADMINHTML, true);

        if (!$this->isCli ()) $this->message ('<pre>');

        $this->message ('+ Begin : ' . strftime ('%c'));

        static::_construct ();
    }

    public function _construct () {}

    public function __destruct ()
    {
        $this->message ('= End : ' . strftime ('%c'));

        if (!$this->isCli ()) $this->message ('</pre>');
    }

    protected function getConfig ()
    {
        return Mage::getModel ('mhub/config');
    }

    protected function getHelper ()
    {
        return Mage::helper ('mhub');
    }

    protected function getRegionName ($regionId, $countryId = 'BR')
    {
        $collection = Mage::getModel ('directory/region')->getResourceCollection ()->addCountryFilter ($countryId);
        $collection->getSelect ()->where ("main_table.region_id = '{$regionId}'");

        $result = $collection->getFirstItem ()->getCode ();

        return $result;
    }

    protected function getStoreConfig ($key, $storeId = null)
    {
        return $this->getHelper ()->getStoreConfig ($key, $storeId);
    }

    protected function getCoreResource ()
    {
        return Mage::getSingleton ('core/resource');
    }

    protected function getReadConnection ()
    {
        return $this->getCoreResource ()->getConnection ('core_read');
    }

    protected function getWriteConnection ()
    {
        return $this->getCoreResource ()->getConnection ('core_write');
    }

    protected function isCli ()
    {
        if (!strcmp (php_sapi_name (), 'cli') && empty ($_SERVER ['REMOTE_ADDR']))
        {
            return true;
        }
    }

    protected function logException (Exception $e)
    {
        Mage::log ("\n" . $e->__toString (), Zend_Log::ERR, Epicom_MHub_Helper_Data::LOG, $this->_debug);
    }

    protected function message ($text)
    {
        Mage::log ($text, null, Epicom_MHub_Helper_Data::LOG, $this->_debug);
    }

    protected function _fault ($code, $message = null)
    {
        throw new Exception ($message, 6666);
    }
}

