<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

class Epicom_MHub_Adminhtml_MarketplaceController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed ()
	{
	    return Mage::getSingleton ('admin/session')->isAllowed ('epicom/mhub/marketplace');
	}

	protected function _initAction ()
	{
		$this->loadLayout ()->_setActiveMenu ('epicom/mhub/marketplace')
            ->_addBreadcrumb (Mage::helper ('mhub')->__('Marketplaces Manager'), Mage::helper ('mhub')->__('Marketplaces Manager'))
        ;

		return $this;
	}

	public function indexAction ()
	{
	    $this->_title ($this->__('MHub'));
	    $this->_title ($this->__('Marketplaces Manager'));

		$this->_initAction ();

		$this->renderLayout ();
	}

	public function editAction ()
	{			    
	    $this->_title ($this->__('MHub'));
		$this->_title ($this->__('Marketplace'));
	    $this->_title ($this->__('Edit Marketplace'));

		$id = $this->getRequest()->getParam ('id');

		$model = Mage::getModel ('mhub/marketplace')->load ($id);

		if ($model->getId ())
        {
			Mage::register ('marketplace_data', $model);

			$this->loadLayout ();

			$this->_setActiveMenu ('epicom/mhub/marketplace');
			$this->_addBreadcrumb (Mage::helper('mhub')->__('Marketplace Manager'), Mage::helper ('mhub')->__('Marketplace Manager'));
			$this->_addBreadcrumb (Mage::helper('mhub')->__('Marketplace Description'), Mage::helper ('mhub')->__('Marketplace Description'));
			$this->getLayout ()->getBlock ('head')->setCanLoadExtJs (true);
			$this->_addContent ($this->getLayout ()->createBlock ('mhub/adminhtml_marketplace_edit'));
            $this->_addLeft ($this->getLayout()->createBlock ('mhub/adminhtml_marketplace_edit_tabs'));

			$this->renderLayout();
		} 
		else
        {
			Mage::getSingleton ('adminhtml/session')->addError (Mage::helper ('mhub')->__('Marketplace does not exist.'));

			$this->_redirect ('*/*/');
		}
	}

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName = 'marketplaces.csv';

        $content = $this->getLayout()
            ->createBlock('mhub/adminhtml_marketplace_grid')
            ->getCsvFile()
        ;

        $this->_prepareDownloadResponse ($fileName, $content);
    }

	public function massRemoveAction ()
	{
		try
        {
			$ids = $this->getRequest ()->getPost ('entity_ids', array ());

			foreach ($ids as $id)
            {
                $model = Mage::getModel('mhub/marketplace');

                $model->setId ($id)->delete ();
			}

			Mage::getSingleton ('adminhtml/session')->addSuccess (Mage::helper ('mhub')->__('Marketplace(s) was successfully removed'));
		}
		catch (Exception $e)
        {
			Mage::getSingleton ('adminhtml/session')->addError ($e->getMessage ());
		}

		$this->_redirect('*/*/');
	}
}

