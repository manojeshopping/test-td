<?php

class Magestore_Onestepcheckout_Model_Giftwrap extends Mage_Core_Model_Abstract
{

    public function toOptionArray()
    {
        return array(
            0    => Mage::helper('onestepcheckout')->__('Per Order'),
            1    => Mage::helper('onestepcheckout')->__('Per Item')
        );
    }
	
/* 	public function paypal_prepare_line_items($observer)
	{
		$paypalCart = $observer->getEvent()->getPaypalCart();
		if ($paypalCart){
			$salesEntity = $paypalCart->getSalesEntity();
			if(Mage::getModel('checkout/session')->getData('onestepcheckout_giftwrap_amount') > 0){
				$paypalCart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_SUBTOTAL,abs((float)Mage::getModel('core/session')->getData('onestepcheckout_giftwrap_amount')),Mage::helper('onestepcheckout')->__('Giftwrap'));				
			}
		}

	} */
	
	//QuyenNH 
    public function paypal_prepare_line_items($observer) {

        if (!Mage::helper('onestepcheckout')->enabledOnestepcheckout()) {
            return;
        }

        if (version_compare(Mage::getVersion(), '1.4.2', '>=')) {
            $paypalCart = $observer->getEvent()->getPaypalCart();
            if ($paypalCart) {
                $salesEntity = $paypalCart->getSalesEntity();
                $giftwrapAmount = 0;
                if ($salesEntity->getOnestepcheckoutGiftwrapAmount()) {
                    $giftwrapAmount = $salesEntity->getOnestepcheckoutGiftwrapAmount();
                } else {
                    $giftwrapAmount = Mage::getModel('checkout/session')->getData('onestepcheckout_giftwrap_amount');
                }
                if ($giftwrapAmount) {
                    $paypalCart->addItem(Mage::helper('onestepcheckout')->__('Giftwrap'), 1, abs((float) $giftwrapAmount));
                }
            }
        } else {
            $salesEntity = $observer->getSalesEntity();
            $additional = $observer->getAdditional();
            if ($salesEntity && $additional) {
                $giftwrapAmount = 0;
                if ($salesEntity->getOnestepcheckoutGiftwrapAmount()) {
                    $giftwrapAmount = $salesEntity->getOnestepcheckoutGiftwrapAmount();
                } else {
                    $giftwrapAmount = Mage::getModel('checkout/session')->getData('onestepcheckout_giftwrap_amount');
                }
                if ($giftwrapAmount) {
                    $items = $additional->getItems();
                    $items[] = new Varien_Object(array(
                        'name' => Mage::helper('onestepcheckout')->__('Giftwrap'),
                        'qty' => 1,
                        'amount' => (abs((float) $giftwrapAmount)),
                    ));
                    $additional->setItems($items);
                }              
            }
        }
    }
}