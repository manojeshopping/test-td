<?php
require_once(Mage::getModuleDir('controllers','Mage_Checkout').DS.'CartController.php');
class Magentoguys_Checkoutc_CartController extends Mage_Checkout_CartController
{
    /**
     * Initialize shipping information
     */
    public function estimatePostAction()
    {
        $country    = (string) $this->getRequest()->getParam('country_id');
        $postcode   = (string) $this->getRequest()->getParam('estimate_postcode');
        $city       = (string) $this->getRequest()->getParam('estimate_city');
        $regionId   = (string) $this->getRequest()->getParam('region_id');
        $region     = (string) $this->getRequest()->getParam('region');
		
        $this->_getQuote()->getShippingAddress()
            ->setCountryId($country)
            ->setCity($city)
            ->setPostcode($postcode)
            ->setRegionId($regionId)
            ->setRegion($region)
			->setCollectShippingRates(true);
        $this->_getQuote()->save();
		$this->_getQuote()->getShippingAddress()->setShippingMethod('amtable_amtable1')->save();
		
		$this->_getQuote()->collectTotals()->save();
		$response = array();
		
        $response['shipping']=$this->eastmatesajax();
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
		// $this->_goBack();
    }
	
	protected function eastmatesajax()
    {

        $layout=$this->getLayout();
        $layout->getMessagesBlock()->setMessages(Mage::getSingleton('checkout/session')->getMessages(true),Mage::getSingleton('catalog/session')->getMessages(true)); 
        $block = $this->getLayout()->createBlock('checkout/cart_totals')->setTemplate('checkout/cart/totals.phtml');
        return $block->toHtml();
    }

    
}
