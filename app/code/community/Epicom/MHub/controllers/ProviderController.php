<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2017 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_ProviderController extends Epicom_MHub_Controller_Action
{
    protected $_jsonData = array ();

    public function _construct ()
    {
        parent::_construct ();

        $rawData = $this->getRequest ()->getRawBody ();
        if (empty ($rawData)) die (__('Data not specified'));

        $this->_jsonData = json_decode ($rawData, true);

        $this->getResponse ()->setHeader ('Content-Type', 'application/json');
    }

    public function calcularCarrinhoAction ()
    {
        $result = Mage::getModel ('mhub/cart_api')->calculate (
            $this->_jsonData ['marketplace'],
            $this->_jsonData ['cep'],
            $this->_jsonData ['entregaUnica'],
            $this->_jsonData ['itens']
        );

        $this->getResponse ()->setBody ($result);
    }

    public function criadoAction ()
    {
        $result = Mage::getModel ('mhub/order_api')->create (
            $this->_jsonData ['marketplace'],
            $this->_jsonData ['codigoEpicom'],
            $this->_jsonData ['dataCriacao'],
            $this->_jsonData ['itens'],
            $this->_jsonData ['destinatario'],
            $this->_jsonData ['entrega']
        );

        $this->getResponse ()->setBody (json_encode ($result));
    }

    public function aprovadoAction ()
    {
        $result = Mage::getModel ('mhub/order_status_api')->approve (
            $this->_jsonData ['codigo'],
            $this->_jsonData ['codigoPedidoEpicom'],
            $this->_jsonData ['marketplace'],
            $this->_jsonData ['destinatario']
        );

        $this->getResponse ()->setBody (json_encode ($result));
    }

    public function canceladoAction ()
    {
        $result = Mage::getModel ('mhub/order_status_api')->cancel (
            $this->_jsonData ['codigo'],
            $this->_jsonData ['codigoPedidoEpicom'],
            $this->_jsonData ['marketplace'],
            $this->_jsonData ['destinatario']
        );

        $this->getResponse ()->setBody (json_encode ($result));
    }

    public function despachadoAction ()
    {
        $result = Mage::getModel ('mhub/order_status_api')->sent (
            $this->_jsonData ['codigo'],
            $this->_jsonData ['codigoPedidoEpicom'],
            $this->_jsonData ['rastreio']
        );

        $this->getResponse ()->setBody (json_encode ($result));
    }

    public function entregueAction ()
    {
        $result = Mage::getModel ('mhub/order_status_api')->delivered (
            $this->_jsonData ['codigo'],
            $this->_jsonData ['codigoPedidoEpicom']
        );

        $this->getResponse ()->setBody (json_encode ($result));
    }
}

