<?php
/*
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2018 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Adminhtml_QuoteController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed ()
	{
	    return Mage::getSingleton ('admin/session')->isAllowed ('epicom/mhub/quote');
	}

	protected function _initAction ()
	{
		$this->loadLayout ()->_setActiveMenu ('epicom/mhub/quote')
            ->_addBreadcrumb (Mage::helper ('mhub')->__('Quotes Manager'), Mage::helper ('mhub')->__('Quotes Manager'))
        ;

		return $this;
	}

	public function indexAction ()
	{
	    $this->_title ($this->__('MHub'));
	    $this->_title ($this->__('Quotes Manager'));

		$this->_initAction ();

		$this->renderLayout ();
	}

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'quotes.csv';
        $content    = $this->getLayout()
            ->createBlock('mhub/adminhtml_quote_grid')
            ->getCsvFile()
        ;

        $this->_prepareDownloadResponse ($fileName, $content);
    }
}

