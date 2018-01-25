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

    public function runOrder ()
    {
        Mage::getModel ('mhub/cron_order')->run ();
    }

    public function runOrderStatus ()
    {
        Mage::getModel ('mhub/cron_order_status')->run ();
    }

    public function runShipment ()
    {
        Mage::getModel ('mhub/cron_shipment')->run ();
    }

    public function runQueue ()
    {
        $this->runBrand ();

        $this->runCategory ();

        $this->runProduct ();

        $this->runOrder ();

        $this->runOrderStatus ();

        $this->runShipment ();
    }
}

