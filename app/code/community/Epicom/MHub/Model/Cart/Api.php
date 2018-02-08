<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Model_Cart_Api extends Mage_Api_Model_Resource_Abstract
{
    protected $_shippingAmount = 0;

    public function calculate ($marketplace, $zip, $unique, $items)
    {
        if (empty ($marketplace) || empty ($zip) || !isset ($unique) || empty ($items))
        {
            $this->_fault ('invalid_request_param');
        }

        $productCodeAttribute = Mage::getStoreConfig ('mhub/product/code');

        $mageQuote = Mage::getSingleton ('checkout/session')->getQuote ();

        $shippingAddress = $mageQuote->getShippingAddress ()
            ->setCountryId ('BR')
            ->setPostcode ($zip)
            ->setCollectShippingRates (true)
        ;

        $products = array ('codes' => array (), 'qtys' => 0);

        $result = array(
            'valorFrete'            => 0,
            'valorTotaldosProdutos' => 0,
            'items'   => array ()
        );

        foreach ($items as $_item)
        {
            $productCode = strval ($_item ['codigo']);
            $productQty  = intval ($_item ['quantidade']);

            $mageProduct = Mage::getModel ('catalog/product')->loadByAttribute ($productCodeAttribute, $productCode);
            if (!$mageProduct || !$mageProduct->getId ())
            {
                $this->_fault ('product_not_exists');
            }

            $stockItem = Mage::getModel ('cataloginventory/stock_item')->assignProduct ($mageProduct);

            $mageQuote->addProduct ($mageProduct, $productQty);

            if (strtolower ($unique) == 'false' || $unique === false)
            {
                $this->_shippingAmount = 0;

                $result ['items'][] = array(
                    'codigo'     => array ($productCode),
                    'quantidade' => $productQty,
                    'fretes'     => $this->_getShippingRates ($mageQuote, true)
                );
            }

            $products ['codes'][] = $productCode;
            $products ['qtys']   += $productQty;
        }

        if (strtolower ($unique) == 'true' || $unique === true)
        {
            $result ['items'][] = array(
                'codigo'     => $products ['codes'],
                'quantidade' => $products ['qtys'],
                'fretes'     => $this->_getShippingRates ($mageQuote)
            );
        }

        $result ['valorFrete']            = $this->_shippingAmount;
        $result ['valorTotaldosProdutos'] = $mageQuote->getBaseSubtotal ();

        return json_encode ($result);
    }

    protected function _getShippingRates ($quote, $removeItems = false)
    {
        $quote->collectTotals ();

        $result = array ();

        foreach ($quote->getShippingAddress ()->getGroupedAllShippingRates () as $code => $rates)
        {
            if ($code == Epicom_Mhub_Model_Shipping_Carrier_Epicom::CODE) continue;

            foreach ($rates as $_rate)
            {
                if (!$_rate->getErrorMessage ())
                {
                    $this->_shippingAmount += $_rate->getPrice ();

                    $result [] = array(
                        'diasParaEntrega'    => Mage::getStoreConfig ("carriers/{$code}/delivery_time"),
                        'entrega'            => Mage::getStoreConfig ("carriers/{$code}/title"),
                        'tranportadora'      => $_rate->getCode (),
                        'valorTotalFrete'    => $_rate->getPrice (),
                        'valorTotalPedido'   => $quote->getBaseSubtotal (),
                        'valorTotal'         => $quote->getBaseSubtotal () + $_rate->getPrice (),
                        'valorTotalImpostos' => 0,
                    );
                }
            }
        }

        if ($removeItems) $quote->removeAllItems ();

        return $result;
    }
}

