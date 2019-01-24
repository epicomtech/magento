<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Exception extends Mage_Core_Exception
{
    public function __construct ($message, $code)
    {
        parent::__construct ($message, $code);

        $emailRecipient = Mage::getStoreConfig ('mhub/error/email_recipient');

        if (!empty ($emailRecipient))
        {
            $emailIdentity = Mage::getStoreConfig ('mhub/error/email_identity');

            $emailTemplate = Mage::getModel ('core/email_template')
                ->loadDefault (Mage::getStoreConfig ('mhub/error/email_template'), 'en_US')
                ->setSenderName (Mage::getStoreConfig ("trans_email/ident_{$emailIdentity}/name"))
                ->setSenderEmail (Mage::getStoreConfig ("trans_email/ident_{$emailIdentity}/email"))
            ;

            $emailTemplate->send ($emailRecipient, null, array (
                'website_url'   => sprintf ("http://%s%s", $_SERVER ['HTTP_HOST'], $_SERVER ['REQUEST_URI']),
                'error_message' => $message,
            ));
        }

        $saveError = Mage::getStoreConfigFlag ('mhub/error/save_error');

        if ($saveError)
        {
            Mage::getModel ('mhub/error')
                ->setUrl (sprintf ("http://%s%s", $_SERVER ['HTTP_HOST'], $_SERVER ['REQUEST_URI']))
                ->setMessage ($message)
                ->setCode ($code)
                ->setCreatedAt (date ('c'))
                ->save ()
            ;
        }
    }
}

