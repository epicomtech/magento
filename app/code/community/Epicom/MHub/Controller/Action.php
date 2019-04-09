<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Controller_Action extends Mage_Core_Controller_Front_Action
{
    public function _construct ()
    {
        if (Mage::getStoreConfigFlag ('mhub/settings/auth_enabled'))
        {
            if (!isset ($_SERVER ['PHP_AUTH_USER']) || !isset ($_SERVER ['PHP_AUTH_PW']))
            {
                header ('WWW-Authenticate: Basic realm="Authentication Required"');
                header ('HTTP/1.0 401 Unauthorized');

                die (__('Unauthorized'));
            }

            $authUser = $_SERVER ['PHP_AUTH_USER'];
            $authPass = $_SERVER ['PHP_AUTH_PW'];

            $username = Mage::getStoreConfig ('mhub/settings/auth_username');
            $password = Mage::getStoreConfig ('mhub/settings/auth_password');

            if (strcmp ($authUser, $username) || strcmp ($authPass, $password))
            {
                header ('HTTP/1.0 403 Forbidden');

                die (__('Forbidden'));
            }
        }

        // Mage::app ()->setCurrentStore (Mage_Core_Model_App::ADMIN_STORE_ID);
    }
}

