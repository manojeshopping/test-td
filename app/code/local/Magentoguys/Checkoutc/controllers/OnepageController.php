<?php
require_once(Mage::getModuleDir('controllers','Mage_Checkout').DS.'OnepageController.php');
class Magentoguys_Checkoutc_OnepageController extends Mage_Checkout_OnepageController
{
   
   /**
     * Checkout page
     */
    public function indexAction()
    {
		$selectedDelivery = $this->getRequest()->getParam('delivery_selected');
		
		Mage::register('selectedDelivery',$selectedDelivery);
        if (!Mage::helper('checkout')->canOnepageCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                Mage::getStoreConfig('sales/minimum_order/error_message') :
                Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');

            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure' => true)));
        $this->getOnepage()->initCheckout();
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));
        $this->renderLayout();
    }

   
   public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
			$data = $this->getRequest()->getPost('billing', array());
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

			Mage::getSingleton('core/session')->setPickupenable($data['use_for_shipping']);
			
            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                if ($this->getOnepage()->getQuote()->isVirtual()) {
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                }
				elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 2) {
					$this->getOnepage()->getQuote()->getShippingAddress()
							->setCountryId('NZ')
							->setCity('')
							->setPostcode(1111)
							->setRegionId('')
							->setRegion('')
							->setCollectShippingRates(true);
					$this->getOnepage()->getQuote()->save();
					$this->getOnepage()->getQuote()->getShippingAddress()->setShippingMethod('amtable_amtable1')->save();
		
					$this->getOnepage()->getQuote()->collectTotals();
					$this->getOnepage()->getQuote()->collectTotals()->save();
					
					$totals = $this->getOnepage()->getQuote()->getTotals();
					$grandPickupTotal = $totals["subtotal"]->getValue();
					
					// $grandPickupTotal = $this->getOnepage()->getQuote()->getSubTotal();
					$baseGrandPickupTotal = Mage::app()->getStore()->getBaseCurrency()->format($grandPickupTotal, array(), true);
					
					$result['grand_pickup_total'] = '<strong>Total:'.$baseGrandPickupTotal.'</strong>';
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                }
				elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    $result['goto_section'] = 'shipping_method';
                    $result['update_section'] = array(
                        'name' => 'shipping-method',
                        'html' => $this->_getShippingMethodsHtml()
                    );

                    $result['allow_sections'] = array('shipping');
                    $result['duplicateBillingInfo'] = 'true';
                } else {
                    $result['goto_section'] = 'shipping';
                }
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }
	
	/**
     * Shipping method save action
     */
    public function saveShippingMethodAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping_method', '');
            $result = $this->getOnepage()->saveShippingMethod($data);
            // $result will contain error data if shipping method is empty
            if (!$result) {
                Mage::dispatchEvent(
                    'checkout_controller_onepage_save_shipping_method',
                     array(
                          'request' => $this->getRequest(),
                          'quote'   => $this->getOnepage()->getQuote()));
                $this->getOnepage()->getQuote()->collectTotals();
                $this->getOnepage()->getQuote()->collectTotals()->save();
				$grandTotal = $this->getOnepage()->getQuote()->getBaseGrandTotal();
				$baseGrandTotal = Mage::app()->getStore()->getBaseCurrency()->format($grandTotal, array(), true);
				$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
				
				$result['grand_total'] = '<strong>Total:'.$baseGrandTotal.'</strong>';
                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'html' => $this->_getPaymentMethodsHtml()
                );
            }
            
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

   
}