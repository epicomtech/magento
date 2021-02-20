<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cron
{
    public function runBrand ()
    {
        Mage::getModel ('mhub/cron_brand')->run ();
    }

    public function runCategory ()
    {
        Mage::getModel ('mhub/cron_category')->run ();
    }

    public function runProduct ()
    {
        Mage::getModel ('mhub/cron_product')->run ();
    }

    public function runProductInput ()
    {
        Mage::getModel ('mhub/cron_product_input')->run ();
    }

    public function runProductAvailability ()
    {
        Mage::getModel ('mhub/cron_product_availability')->run ();
    }

    public function runManufacturer ()
    {
        Mage::getModel ('mhub/cron_manufacturer')->run ();
    }

    public function runProvider ()
    {
        Mage::getModel ('mhub/cron_provider')->run ();
    }

    public function runOrder ()
    {
        Mage::getModel ('mhub/cron_order')->run ();
    }

    public function runOrderStatus ()
    {
        Mage::getModel ('mhub/cron_order_status')->run ();
    }

    public function runOrderNF ()
    {
        Mage::getModel ('mhub/cron_order_nf')->run ();
    }

    public function runOrderShipment ()
    {
        Mage::getModel ('mhub/cron_order_shipment')->run ();
    }

    public function runOrderConciliation ()
    {
        Mage::getModel ('mhub/cron_order_conciliation')->run ();
    }

    public function runOrderComplete ()
    {
        Mage::getModel ('mhub/cron_order_complete')->run ();
    }

    public function runShipment ()
    {
        Mage::getModel ('mhub/cron_shipment')->run ();
    }
}

