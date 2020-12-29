<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2020 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Discount extends Varien_Object
{
    const DISCOUNT_AMOUNT = 0;

    public static function getDiscount ()
    {
        return self::DISCOUNT_AMOUNT;
    }

    public static function canApply ($address)
    {
        return true;
    }

    public static function getBalance ()
    {
        $rawData = Mage::app ()->getRequest ()->getRawBody ();

        $jsonData = json_decode ($rawData, true);

        return floatval ($jsonData ['valorDesconto']) * -1;
    }
}

