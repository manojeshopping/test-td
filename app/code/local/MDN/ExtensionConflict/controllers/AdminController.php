<?php


class MDN_ExtensionConflict_AdminController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Temporarily allow access for all users
	 */
	protected function _isAllowed() {
		return true;
	}

	/**
	 * display list
	 *
	 */
	public function listAction()
	{
		$this->loadLayout();
        $this->renderLayout();
	}
	
	/**
	 * Refresh list
	 *
	 */
	public function RefreshAction()
	{
		mage::helper('ExtensionConflict')->RefreshList();
		
		//redirect on list
	   	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('List refreshed'));
		$this->_redirect('adminhtml/Admin/List');
	}
	
	/**
	 * Save file
	 *
	 */
	public function UploadAction()
	{
		//save file
	    $uploader = new Varien_File_Uploader('config_file');
	    $uploader->setAllowedExtensions(array('xml'));    		
    	$path = Mage::app()->getConfig()->getTempVarDir().'/ExtensionConflict/VirtualNamespace/VirtualModule/etc/';
	    $uploader->save($path);
    	
		//refresh list
		mage::helper('ExtensionConflict')->RefreshList();
		
		//redirect
	   	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('File Uploaded and List refreshed'));
		$this->_redirect('adminhtml/Admin/List');
	}
	
	public function DeleteVirtualModuleAction()
	{
		//delete file
		$filePath = Mage::app()->getConfig()->getTempVarDir().'/ExtensionConflict/VirtualNamespace/VirtualModule/etc/config.xml';
		if (file_exists($filePath))
			unlink($filePath);	
		
		//refresh list
		mage::helper('ExtensionConflict')->RefreshList();
		
		//redirect
	   	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Virtual Module deleted and List refreshed'));
		$this->_redirect('adminhtml/Admin/List');
	}
	
	/**
	 * Display conflict fix solution
	 *
	 */
	public function DisplayFixAction()
	{
		$this->loadLayout();
        $this->renderLayout();		
	}
}
