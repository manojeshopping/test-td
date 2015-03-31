<?php

class Magentoguys_Checkoutc_Block_Onepage_Billing extends Mage_Checkout_Block_Onepage_Billing
{
    
    public function isUseBillingAddressForShipping()
    {
		if(Mage::registry('selectedDelivery')=='pickup'){
			return 2;
		}
        else{
		
			if(($this->getQuote()->getIsVirtual()) 
					|| !$this->getQuote()->getShippingAddress()->getSameAsBilling()) 
			{
				return 0;
			}
		
			return 1;
		}	
    }

}
