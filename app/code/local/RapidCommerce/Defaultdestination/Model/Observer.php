<?php
class RapidCommerce_Defaultdestination_Model_Observer {
	// public function handleCollect($observer) {
		// if (!Mage::getStoreConfig('shipping/origin/applydefaultstoemptyquote'))
			// return $this;
			
		// $quote = $observer->getEvent()->getQuote();
		// $shippingAddress = $quote->getShippingAddress();
		// $billingAddress = $quote->getBillingAddress();
		// $saveQuote = false;
		// if (!$shippingAddress->getCountryId()) {
			// $country = Mage::getStoreConfig('shipping/origin/country_id');
			// $state = Mage::getStoreConfig('shipping/origin/region_id');
			// $postcode = Mage::getStoreConfig('shipping/origin/postcode');
			// $method = Mage::getStoreConfig('shipping/origin/shippingmethod');
			
			// $shippingAddress
				// ->setCountryId($country)
				// ->setRegionId($state)
				// ->setPostcode($postcode)
				// ->setShippingMethod($method)
				// ->setCollectShippingRates(true);
			// $shippingAddress->save();
			
			// $saveQuote = true;
		// }
		// if (Mage::getStoreConfig('shipping/origin/applydefaultstobillingaddress') && !$billingAddress->getCountryId()) {
			// $country = Mage::getStoreConfig('shipping/origin/country_id');
			// $state = Mage::getStoreConfig('shipping/origin/region_id');
			// $postcode = Mage::getStoreConfig('shipping/origin/postcode');
						
			// $billingAddress
				// ->setCountryId($country)
				// ->setRegionId($state)
				// ->setPostcode($postcode);
				
			// $saveQuote = true;
			
			// $quote->save();
		// }
		// if ($saveQuote)
			// $quote->save();
		// return $this;
	// }
	
	public function setShipping($evt){
		
         $controller = $evt->getControllerAction();
        // if(!Mage::getSingleton('checkout/type_onepage')->getQuote()->getShippingAddress()->getCountryId()  && Mage::getSingleton('checkout/type_onepage')->getQuote()->getItemsCount()){
            // $country_id = 'NZ';
            // $region_id = false;
            // $country = Mage::getModel('directory/country')->loadByCode($country_id);
            // $regions = $country->getRegions();
            // if(sizeof($regions) > 0){
                // $region = $regions->getFirstItem();
                // $region_id = $region->getId();
            // }
			// $customerSession=Mage::getSingleton("customer/session");
            // if($customerSession->isLoggedIn()){
                // $customerAddress=$customerSession->getCustomer()->getDefaultShippingAddress();
                // if($customerAddress->getId()){
                    // $customerCountry=$customerAddress->getCountryId();
                    // $region_id = $customerAddress->getRegionId();
                    // $region = $customerAddress->getRegion();
                    // $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
                    // $shipping = $quote->getShippingAddress();
                    // $shipping->setCountryId($customerCountry);
                    // if($region_id){
                        // $shipping->setRegionId($region_id);
                    // }
                    // if($region){
                        // $shipping->setRegion($region);
                    // }
					// $shipping->setShippingMethod('amtable_amtable1');
                    // $quote->save();
                    // $controller->getResponse()->setRedirect(Mage::getUrl('*/*/*',array('_current'=>true)));
                // }else{
                    // $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
                    // $shipping = $quote->getShippingAddress();
                    // $shipping->setCountryId($country_id);
                    // if($region_id){
                        // $shipping->setRegionId($region_id);
                    // }
					// $shipping->setShippingMethod('amtable_amtable1');
                    // $quote->save();
                    // $controller->getResponse()->setRedirect(Mage::getUrl('*/*/*',array('_current'=>true)));
                // }
            // }else{
                // $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
                // $shipping = $quote->getShippingAddress();
                // $shipping->setCountryId($country_id);
                // if($region_id){
                    // $shipping->setRegionId($region_id);
                // }
				// $shipping->setShippingMethod('amtable_amtable1');
                // $quote->save();
                // $controller->getResponse()->setRedirect(Mage::getUrl('*/*/*',array('_current'=>true)));
            // }
        // }
				
		 $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
         $shipping = $quote->getShippingAddress();
		 $shipping->setShippingMethod('amtable_amtable1')->save();
        // $shipping->setCountryId('NZ');
		// if($region_id){
			// $shipping->setRegionId(486);
			// $shipping->setShippingMethod($code);
		// }
		// $quote->save();
		// $controller->getResponse()->setRedirect(Mage::getUrl('*/*/*',array('_current'=>true)));
 
    
	}
}