<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Fee extends Varien_Object
{
    const FEE_AMOUNT = 0;

    public static function getFee ()
    {
        return self::FEE_AMOUNT;
    }

    public static function canApply ($address)
    {
        return true;
    }

    public static function getBalance ()
    {
        $rawData = Mage::app ()->getRequest ()->getRawBody ();

        $jsonData = json_decode ($rawData, true);

        return floatval ($jsonData ['valorJuros']);
    }
}

