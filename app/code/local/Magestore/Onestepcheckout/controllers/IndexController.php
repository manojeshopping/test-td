<?php

class Magestore_Onestepcheckout_IndexController extends Mage_Core_Controller_Front_Action {

    public function testAction() {
        $values = Mage::helper('onestepcheckout')->getFieldEnables();
        $i = 0;
        $fields = array();

        Zend_Debug::dump(Mage::app()->getWebsite()->getData());
        Zend_Debug::dump(Mage::app()->getStore()->getData());
        foreach ($values as $value) {
            Zend_Debug::dump($value);
        }
        die('---');
    }

    public function indexAction() {
        if (!Mage::helper('magenotification')->checkLicenseKey('Onestepcheckout')) {
            Mage::getSingleton('core/config')->saveConfig('onestepcheckout/general/active', 0);
            return $this->_redirect('checkout/onepage');
        }

        if (!Mage::helper('onestepcheckout')->enabledOnestepcheckout()) {
            $this->_redirect('checkout/onepage');
            return;
        }
        $this->enableCustomerFields();
        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }

        if (!Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->getData('country_id')) {
            if (Mage::getStoreConfig('onestepcheckout/general/country_id')) {
                Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->setData('country_id', Mage::getStoreConfig('onestepcheckout/general/country_id'))->save();
            }
        }
        $checkNull = 1;
        $helper = Mage::helper('onestepcheckout');
        for ($i = 0; $i < 15; $i++) {
            if ($helper->getDefaultField($i)) {
                $checkNull = 0;
                break;
            }
        }
        if ($checkNull == 1) {
            $arrayDefaults = $helper->getDefaultPositionArray();
            foreach ($arrayDefaults as $number => $value) {
                $model = Mage::getModel('onestepcheckout/config');
                $model->setScope('default')
                        ->setScopeId(0)
                        ->setPath('onestepcheckout/field_position_management/row_' . $number)
                        ->setValue($value);
                $model->save();
            }
        }
		//        QuyenNH fix bug persistent cart
        if (Mage::helper('persistent/session')->isPersistent()) {
            Mage::helper('persistent/session')->getSession()->removePersistentCookie();
            /** @var $customerSession Mage_Customer_Model_Session */
            $customerSession = Mage::getSingleton('customer/session');
            if (!$customerSession->isLoggedIn()) {
                $customerSession->setCustomerId(null)
                        ->setCustomerGroupId(null);
            }

            Mage::getSingleton('persistent/observer')->setQuoteGuest();
        }
		//End QuyenNH fix bug persistent cart
//        $this->loadLayout();
        $update = $this->getLayout()->getUpdate();
        $update->addHandle('default');
        $this->addActionLayoutHandles();
        $update->addHandle($this->_getOnestepcheckoutHandle('onestepcheckout_index_index'));
        $this->loadLayoutUpdates();
        $this->generateLayoutXml();
        $this->generateLayoutBlocks();
        $this->_isLayoutLoaded = true;
        $this->_initLayoutMessages('checkout/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('One Step Checkout'));
        $this->renderLayout();
    }

    //check if email is registered
    private function _emailIsRegistered($email_address) {
        $model = Mage::getModel('customer/customer');
        $model->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email_address);
        if ($model->getId()) {
            return true;
        } else {
            return false;
        }
    }

    public function getOnepage() {
        return Mage::getSingleton('checkout/type_onepage');
    }

    public function getSession() {
        return Mage::getSingleton('checkout/session');
    }

    /*
     * check if an email is valid
     */

    public function is_valid_emailAction() {
        $validator = new Zend_Validate_EmailAddress();
        $email_address = $this->getRequest()->getPost('email_address');
        $message = 'Invalid';
        if ($email_address != '') {
            // Check if email is in valid format
            if (!$validator->isValid($email_address)) {
                $message = 'invalid';
            } else {
                //if email is valid, check if this email is registered
                if ($this->_emailIsRegistered($email_address)) {
                    $message = 'exists';
                } else {
                    $message = 'valid';
                }
            }
        }
        $result = array('message' => $message);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function show_loginAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function show_passwordAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    /*
     * send new password to customer 
     */

    public function retrievePasswordAction() {
        $email = $this->getRequest()->getPost('email', false);
        $result = array();
        if ($email) {
            $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email);
            if ($customer->getId()) {
                try {
                    $newPassword = $customer->generatePassword();
                    $customer->changePassword($newPassword, false);
                    $customer->sendPasswordReminderEmail();
                    $result = array('success' => true);
                } catch (Exception $e) {
                    $result = array('success' => false, 'error' => $e->getMessage());
                }
            } else {
                $result = array('success' => false, 'error' => 'This email address was not found in our records.');
            }
        }
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /*
     * show term and condition pop up
     */

    public function show_term_conditionAction() {
        $helper = Mage::helper('onestepcheckout');
        if ($helper->enableTermsAndConditions()) {
            $html = $helper->getTermsConditionsHtml();
            echo $html;
            echo '<p class="a-right"><a href="#" onclick="javascript:TINY.box.hide();return false;">Close</a></p>';
        }
    }

    /*
     * add coupon to the order
     * copy from CartController.php
     */

    public function add_couponAction() {
        $couponCode = (string) $this->getRequest()->getPost('coupon_code', '');

        $quote = $this->getOnepage()->getQuote();
        if ($this->getRequest()->getParam('remove') == '1') {
            $couponCode = '';
        }

        $oldCouponCode = $quote->getCouponCode();
        if (!strlen($couponCode) && !strlen($oldCouponCode)) {
            return;
        }
        try {
            $error = false;
            /* remove by Leo 10042015 */
            // $quote->getShippingAddress()->setCollectShippingRates(true);
            /* remove by Leo 10042015 */
            $quote->setCouponCode(strlen($couponCode) ? $couponCode : '')
                    ->collectTotals()
                    ->save();
            if ($couponCode) {
                if ($couponCode == $quote->getCouponCode()) {
                    $message = $this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode));
                } else {
                    $error = true;
                    $message = $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode));
                }
            } else {
                $message = $this->__('Coupon code was canceled.');
            }
        } catch (Mage_Core_Exception $e) {
            $error = true;
            $message = $e->getMessage();
        } catch (Exception $e) {
            $error = true;
            $message = $this->__('Cannot apply the coupon code.');
        }
        $result = array(
            'error' => $error,
            'message' => $message
        );
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /*
     * process login action in check out.
     */

    public function loginPostAction() {
        $email = $this->getRequest()->getPost('email', false);
        $password = $this->getRequest()->getPost('password', false);

        $error = '';
        if ($email && $password) {
            try {
                $this->_getCustomerSession()->login($email, $password);
            } catch (Exception $ex) {
                $error = $ex->getMessage();
            }
        }
        $result = array();
        $result['error'] = $error;
        if ($error == '')
            $result['success'] = true;
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /*
     * save billing & shipping address
     */
    /* Thinhnd */

    public function saveAddressOnestepcheckoutAction() {
        $billing_data = $this->getRequest()->getPost('billing', false);
        $shipping_data = $this->getRequest()->getPost('shipping', false);
        $shipping_method = $this->getRequest()->getPost('shipping_method', false);
        $billing_address_id = $this->getRequest()->getPost('billing_address_id', false);

        if(isset($billing_data['onestepcheckout_comment']))
            Mage::getModel('checkout/session')->setOSCCM($billing_data['onestepcheckout_comment']);
        
        //load default data for disabled fields
        if (Mage::helper('onestepcheckout')->isUseDefaultDataforDisabledFields()) {
            Mage::helper('onestepcheckout')->setDefaultDataforDisabledFields($billing_data);
            Mage::helper('onestepcheckout')->setDefaultDataforDisabledFields($shipping_data);
        }

        if (isset($billing_data['use_for_shipping']) && $billing_data['use_for_shipping'] == '1') {
            $shipping_address_data = $billing_data;
        } else {
            $shipping_address_data = $shipping_data;
        }
		
		/* customize for load country ma khong dien day du thong tin */
		$quote = $this->getOnepage()->getQuote();
        $shipping = $quote->getShippingAddress();
        $billing = $quote->getBillingAddress();
        
		$billingCountryId = "";
		$billingRegionId = "";
		$billingZipcode = "";
		$billingRegion = "";
		$billingCity = "";
		
		if(isset($shipping_address_data['country_id']))
			$billingCountryId = $shipping_address_data['country_id'];     
		if(isset($shipping_address_data['region_id']))
			$billingRegionId = $shipping_address_data['region_id'];     
		if(isset($shipping_address_data['postcode']))
			$billingZipcode = $shipping_address_data['postcode'];       
		if(isset($shipping_address_data['region']))
			$billingRegion = $shipping_address_data['region'];
		if(isset($shipping_address_data['city']))
			$billingCity = $shipping_address_data['city'];
			
        $this->getOnepage()->getQuote()->getShippingAddress()
                                       ->setCountryId($billingCountryId)
                                       ->setRegionId($billingRegionId)
                                       ->setPostcode($billingZipcode)
                                       ->setRegion($billingRegion)
                                       ->setCity($billingCity)
                                       ->setCollectShippingRates(true);
      
		/* end customize */
		
        $billing_street = trim(implode("\n", $billing_data['street']));
        $shipping_street = trim(implode("\n", $shipping_address_data['street']));

        if (isset($billing_data['email'])) {
            $billing_data['email'] = trim($billing_data['email']);
        }

        //2014.18.11 update VAT apply start
        if (isset($billing_data['taxvat'])) {
            $billing_data['vat_id'] = trim($billing_data['taxvat']);
            $shipping_data['vat_id'] = trim($billing_data['taxvat']);
        }
        //2014.18.11 update VAT apply end

		
        // Ignore disable fields validation
			$this->setIgnoreValidation();
		
        if (Mage::helper('onestepcheckout')->isShowShippingAddress()) {
            if (!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1') {
                $shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
                $this->getOnepage()->saveShipping($shipping_data, $shipping_address_id);
            }
        }
		
        $this->getOnepage()->saveBilling($billing_data, $billing_address_id);
		/* Start: Modified by Daniel - 06/04/2015 - Improve Ajax speed */
        if(!$billing_address_id || $billing_address_id == '' || $billing_address_id == null){
            if ($billing_data['country_id']) {
                Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->setData('country_id', $billing_data['country_id'])->save();
            }   	
        }
		/* End: Modified by Daniel - 06/04/2015 - Improve Ajax speed */

        if ($shipping_method && $shipping_method != '') {
            Mage::helper('onestepcheckout')->saveShippingMethod($shipping_method);
        }
        $this->_addOnestepcheckoutHandle(true);
        $result = $this->_getBlockResults(null, true);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /*
     * save shipping & payment method 
     */

    public function save_shippingAction() {
        $shipping_method = $this->getRequest()->getPost('shipping_method', '');
        $payment_method = $this->getRequest()->getPost('payment_method', false);
        $billing_data = $this->getRequest()->getPost('billing', false);
        if ($billing_data['country_id']) {
            Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->setData('country_id', $billing_data['country_id'])->save();
        }
        /* edit by Leo 02042015 */
        /* Mage::helper('onestepcheckout')->saveShippingMethod($shipping_method); */
        /* End of edit by Leo 02042015 */
        $this->getOnepage()->saveShippingMethod($shipping_method);
        try {
            $payment = $this->getRequest()->getPost('payment', array());
            $payment['method'] = $payment_method;
            /* Start: Modified by Daniel - 03/04/2015 - improve ajax speed */
            /* $this->getOnepage()->savePayment($payment); */
            /* End: Modified by Daniel - 03/04/2015 - improve ajax speed */
            Mage::helper('onestepcheckout')->savePaymentMethod($payment);
        } catch (Exception $e) {
            
        }
        $this->getOnepage()->getQuote()->collectTotals()->save();

        $this->_addOnestepcheckoutHandle(false);
        $result = $this->_getBlockResults();
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function saveOrderAction() {
        $post = $this->getRequest()->getPost();

        if (!$post)
            return;
        $error = false;
        $helper = Mage::helper('onestepcheckout');

        $billing_data = $this->getRequest()->getPost('billing', array());
        $shipping_data = $this->getRequest()->getPost('shipping', array());

        //2014.18.11 update VAT apply start
        if (isset($billing_data['taxvat'])) {
            $billing_data['vat_id'] = trim($billing_data['taxvat']);
            $shipping_data['vat_id'] = trim($billing_data['taxvat']);
        }
        //2014.18.11 update VAT apply end

        if (isset($billing_data['onestepcheckout_comment']))
            Mage::getModel('checkout/session')->setOSCCM($billing_data['onestepcheckout_comment']);

        //JSON reponse array for wirecard payment method
        $JSONresponse = array();

        //isAjax variable is the code name of payment method
        $isAjax = $this->getRequest()->getParam('isAjax');
        //set checkout method 
        $checkoutMethod = '';
        if (!$this->_isLoggedIn()) {
            $checkoutMethod = 'guest';
            if ($helper->enableRegistration() || !$helper->allowGuestCheckout()) {
                $is_create_account = $this->getRequest()->getPost('create_account_checkbox');
                $email_address = $billing_data['email'];
                if ($is_create_account || !$helper->allowGuestCheckout()) {
                    if ($this->_emailIsRegistered($email_address)) {
                        $error = true;
                        Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Email is already registered.'));
                        $this->_redirect('*/*/index');
                    } else {
                        if (!$billing_data['customer_password'] || $billing_data['customer_password'] == '') {
                            $error = true;
                        } else if (!$billing_data['confirm_password'] || $billing_data['confirm_password'] == '') {
                            $error = true;
                        } else if ($billing_data['confirm_password'] !== $billing_data['customer_password']) {
                            $error = true;
                        }
                        if ($error) {
                            Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Please correct your password.'));
                            if ($isAjax) {
                                $JSONresponse['url'] = Mage::getUrl('onestepcheckout/index/index');
                            } else {
                                $this->_redirect('*/*/index');
                            }
                        } else {
                            $checkoutMethod = 'register';
                        }
                    }
                }
            }
        }
        if ($checkoutMethod != '')
            $this->getOnepage()->saveCheckoutMethod($checkoutMethod);

        //to ignore validation for disabled fields
            $this->setIgnoreValidation();

        //resave billing address to make sure there is no error if customer change something in billing section before finishing order
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        /* Start: Modified by Daniel - Improve speed */
        $result = array();
        if (isset($customerAddressId)) {
            $result = $this->getOnepage()->saveBilling($billing_data, $customerAddressId);
        }
        /* End: Modified by Daniel - Improve speed */
        if (isset($result['error'])) {
            $error = true;
            if (is_array($result['message']) && isset($result['message'][0]))
                Mage::getSingleton('checkout/session')->addError($result['message'][0]);
            else
                Mage::getSingleton('checkout/session')->addError($result['message']);
            if ($isAjax) {
                $JSONresponse['url'] = Mage::getUrl('onestepcheckout/index/index');
            } else {
                $this->_redirect('*/*/index');
            }
        }

        //re-save shipping address
        $shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
        if ($helper->isShowShippingAddress()) {
            if (!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1') {
                /* Start: Modified by Daniel - Improve speed */
                $result = array();
                if (isset($shipping_address_id)) {
                    $result = $this->getOnepage()->saveShipping($shipping_data, $shipping_address_id);
                }
                /* End: Modified by Daniel - Improve speed */
                if (isset($result['error'])) {
                    $error = true;
                    if (is_array($result['message']) && isset($result['message'][0]))
                        Mage::getSingleton('checkout/session')->addError($result['message'][0]);
                    else
                        Mage::getSingleton('checkout/session')->addError($result['message']);
                    $this->_redirect('*/*/index');
                }
            }
            else {
                $result['allow_sections'] = array('shipping');
                $result['duplicateBillingInfo'] = 'true';
                // $result = $this->getOnepage()->saveShipping($billing_data, $shipping_address_id); 
            }
        }

        //re-save shipping method
        $shipping_method = $this->getRequest()->getPost('shipping_method', '');
        if (!$this->isVirtual()) {
            $result = $this->getOnepage()->saveShippingMethod($shipping_method);
            if (isset($result['error'])) {
                $error = true;
                if (is_array($result['message']) && isset($result['message'][0])) {
                    Mage::getSingleton('checkout/session')->addError($result['message'][0]);
                } else {
                    Mage::getSingleton('checkout/session')->addError($result['message']);
                }
                if ($isAjax) {
                    $JSONresponse['url'] = Mage::getUrl('onestepcheckout/index/index');
                } else {
                    $this->_redirect('*/*/index');
                }
            } else {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request' => $this->getRequest(), 'quote' => $this->getOnepage()->getQuote()));
            }
        }

        $paymentRedirect = false;
        //save payment method		
        try {
            $result = array();
            $payment = $this->getRequest()->getPost('payment', array());
            $result = $helper->savePaymentMethod($payment);
            if ($payment) {
                $this->getOnepage()->getQuote()->getPayment()->importData($payment);
            }
            $paymentRedirect = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }

        if (isset($result['error'])) {
            $error = true;
            Mage::getSingleton('checkout/session')->addError($result['error']);
            if ($isAjax) {
                $JSONresponse['url'] = Mage::getUrl('onestepcheckout/index/index');
            } else {
                $this->_redirect('*/*/index');
            }
        }

        if ($paymentRedirect && $paymentRedirect != '') {
            Header('Location: ' . $paymentRedirect);
            exit();
        }

        //only continue to process order if there is no error
        if (!$error) {
            //newsletter subscribe
            if ($helper->isShowNewsletter()) {
                $news_billing = $this->getRequest()->getPost('billing');
                // $is_subscriber = $this->getRequest()->getPost('newsletter_subscriber_checkbox', false);	
                $is_subscriber = null;
                if (isset($news_billing['newsletter_subscriber_checkbox']))
                    $is_subscriber = $news_billing['newsletter_subscriber_checkbox'];
                if ($is_subscriber) {
                    $subscribe_email = '';
                    //pull subscriber email from billing data
                    if (isset($billing_data['email']) && $billing_data['email'] != '') {
                        $subscribe_email = $billing_data['email'];
                    } else if ($this->_isLoggedIn()) {
                        $subscribe_email = Mage::helper('customer')->getCustomer()->getEmail();
                    }
                    //check if email is already subscribed
                    $subscriberModel = Mage::getModel('newsletter/subscriber')->loadByEmail($subscribe_email);
                    if ($subscriberModel->getId() === NULL) {
                        Mage::getModel('newsletter/subscriber')->subscribe($subscribe_email);
                    } else if ($subscriberModel->getData('subscriber_status') != 1) {
                        $subscriberModel->setData('subscriber_status', 1);
                        try {
                            $subscriberModel->save();
                        } catch (Exception $e) {
                            
                        }
                    }
                }
            }
            try {
                $result = $this->getOnepage()->saveOrder();
                $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
            } catch (Mage_Core_Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('checkout/session')->addError($e->getMessage());
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                $redirect = Mage::getUrl('onestepcheckout/index/index');
                if ($isAjax) {
                    $JSONresponse['url'] = $redirect;
                } else {
                    Header('Location: ' . $redirect);
                    exit();
                }
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('checkout/session')->addError($e->getMessage());
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                $redirect = Mage::getUrl('onestepcheckout/index/index');
                if ($isAjax) {
                    $JSONresponse['url'] = $redirect;
                } else {
                    Header('Location: ' . $redirect);
                    exit();
                }
            }

            $this->getOnepage()->getQuote()->save();
            Mage::dispatchEvent('controller_action_postdispatch_checkout_onepage_saveOrder', array('post' => $post, 'controller_action' => $this));

            if ($redirectUrl) {
                $redirect = $redirectUrl;
            } else {
                $redirect = Mage::getUrl('checkout/onepage/success');
            }

            if ($isAjax == 'wirecard') {
                $this->getResponse()->setBody(json_encode($JSONresponse));
            } elseif ($isAjax == 'tco') {
                //Nothing to do here
                //tco payment response the JSON code automatically
            } else {
                Header('Location: ' . $redirect);
                exit();
            }
        } else {
            $this->_redirect('*/*/index');
        }
    }

    public function saveOrderProAction() {
        $post = $this->getRequest()->getPost();
        $result = new stdClass();
        if (!$post)
            return;
        $error = false;
        $helper = Mage::helper('onestepcheckout');

        $billing_data = $this->getRequest()->getPost('billing', array());
        $shipping_data = $this->getRequest()->getPost('shipping', array());

        if (isset($billing_data['onestepcheckout_comment']))
            Mage::getModel('checkout/session')->setOSCCM($billing_data['onestepcheckout_comment']);

        //set checkout method 
        $checkoutMethod = '';
        if (!$this->_isLoggedIn()) {
            $checkoutMethod = 'guest';
            if ($helper->enableRegistration() || !$helper->allowGuestCheckout()) {
                $is_create_account = $this->getRequest()->getPost('create_account_checkbox');
                $email_address = $billing_data['email'];
                if ($is_create_account || !$helper->allowGuestCheckout()) {
                    if ($this->_emailIsRegistered($email_address)) {
                        $error = true;
                        Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Email is already registered.'));
                        $redirect = Mage::getUrl('onestepcheckout/index/index');
                        // Header('Location: ' . $redirect);
                        // exit();
                        $result->url = $redirect;
                        $this->getResponse()->setBody(json_encode($result));
                    } else {
                        if (!$billing_data['customer_password'] || $billing_data['customer_password'] == '') {
                            $error = true;
                        } else if (!$billing_data['confirm_password'] || $billing_data['confirm_password'] == '') {
                            $error = true;
                        } else if ($billing_data['confirm_password'] !== $billing_data['customer_password']) {
                            $error = true;
                        }
                        if ($error) {
                            Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Please correct your password.'));
                            $redirect = Mage::getUrl('onestepcheckout/index/index');
                            // Header('Location: ' . $redirect);
                            // exit();
                            $result->url = $redirect;
                            $this->getResponse()->setBody(json_encode($result));
                        } else {
                            $checkoutMethod = 'register';
                        }
                    }
                }
            }
        }
        if ($checkoutMethod != '')
            $this->getOnepage()->saveCheckoutMethod($checkoutMethod);

        //to ignore validation for disabled fields
            $this->setIgnoreValidation();
        //resave billing address to make sure there is no error if customer change something in billing section before finishing order
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        $result = $this->getOnepage()->saveBilling($billing_data, $customerAddressId);
        if (isset($result['error'])) {
            $error = true;
            if (is_array($result['message']) && isset($result['message'][0]))
                Mage::getSingleton('checkout/session')->addError($result['message'][0]);
            else
                Mage::getSingleton('checkout/session')->addError($result['message']);
            $redirect = Mage::getUrl('onestepcheckout/index/index');
            // Header('Location: ' . $redirect);
            // exit();
            $result->url = $redirect;
            $this->getResponse()->setBody(json_encode($result));
        }

        //re-save shipping address
        $shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
        if ($helper->isShowShippingAddress()) {
            if (!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1') {
                $result = $this->getOnepage()->saveShipping($shipping_data, $shipping_address_id);
                if (isset($result['error'])) {
                    $error = true;
                    if (is_array($result['message']) && isset($result['message'][0]))
                        Mage::getSingleton('checkout/session')->addError($result['message'][0]);
                    else
                        Mage::getSingleton('checkout/session')->addError($result['message']);
                    $redirect = Mage::getUrl('onestepcheckout/index/index');
                    // Header('Location: ' . $redirect);
                    // exit();
                    $result->url = $redirect;
                    $this->getResponse()->setBody(json_encode($result));
                }
            }
            else {
                $result['allow_sections'] = array('shipping');
                $result['duplicateBillingInfo'] = 'true';
                // $result = $this->getOnepage()->saveShipping($billing_data, $shipping_address_id); 
            }
        }

        //re-save shipping method
        $shipping_method = $this->getRequest()->getPost('shipping_method', '');
        if (!$this->isVirtual()) {
            $result = $this->getOnepage()->saveShippingMethod($shipping_method);
            if (isset($result['error'])) {
                $error = true;
                if (is_array($result['message']) && isset($result['message'][0])) {
                    Mage::getSingleton('checkout/session')->addError($result['message'][0]);
                } else {
                    Mage::getSingleton('checkout/session')->addError($result['message']);
                }
                $redirect = Mage::getUrl('onestepcheckout/index/index');
                // Header('Location: ' . $redirect);
                // exit();
                $result->url = $redirect;
                $this->getResponse()->setBody(json_encode($result));
            } else {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request' => $this->getRequest(), 'quote' => $this->getOnepage()->getQuote()));
            }
        }

        $paymentRedirect = false;
        //save payment method		
        try {
            $result = array();
            $payment_method = $this->getRequest()->getPost('payment', array());
            $payment['method'] = $payment_method;
            $result = $helper->savePaymentMethod($payment);
            if ($payment) {
                $this->getOnepage()->getQuote()->getPayment()->importData($payment);
            }
            $paymentRedirect = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
        } catch (Mage_Payment_Exception $e) {

            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {

            $result['error'] = $e->getMessage();
        } catch (Exception $e) {

            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }

        if (isset($result['error'])) {
            $error = true;
            Mage::getSingleton('checkout/session')->addError($result['error']);
            $redirect = Mage::getUrl('onestepcheckout/index/index');
            $result->url = $redirect;
            $this->getResponse()->setBody(json_encode($result));
        }

        if ($paymentRedirect && $paymentRedirect != '') {
            $result = new stdClass();
            $result->url = $paymentRedirect;
            $this->getResponse()->setBody(json_encode($result));
        } else {

            //only continue to process order if there is no error
            if (!$error) {
                //newsletter subscribe
                if ($helper->isShowNewsletter()) {
                    $news_billing = $this->getRequest()->getPost('billing');
                    $is_subscriber = null;
                    if (isset($news_billing['newsletter_subscriber_checkbox']))
                        $is_subscriber = $news_billing['newsletter_subscriber_checkbox'];
                    // var_dump($is_subscriber);die();
                    if ($is_subscriber) {
                        $subscribe_email = '';
                        //pull subscriber email from billing data
                        if (isset($billing_data['email']) && $billing_data['email'] != '') {
                            $subscribe_email = $billing_data['email'];
                        } else if ($this->_isLoggedIn()) {
                            $subscribe_email = Mage::helper('customer')->getCustomer()->getEmail();
                        }
                        //check if email is already subscribed
                        $subscriberModel = Mage::getModel('newsletter/subscriber')->loadByEmail($subscribe_email);
                        if ($subscriberModel->getId() === NULL) {
                            Mage::getModel('newsletter/subscriber')->subscribe($subscribe_email);
                        } else if ($subscriberModel->getData('subscriber_status') != 1) {
                            $subscriberModel->setData('subscriber_status', 1);
                            try {
                                $subscriberModel->save();
                            } catch (Exception $e) {
                                
                            }
                        }
                    }
                }

                try {
                    $result = $this->getOnepage()->saveOrder();
                    $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
                } catch (Mage_Core_Exception $e) {
                    Mage::logException($e);
                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
                    Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                    $redirect = Mage::getUrl('onestepcheckout/index/index');
                    // Header('Location: ' . $redirect);
                    // exit();
                    $result->url = $redirect;
                    $this->getResponse()->setBody(json_encode($result));
                } catch (Exception $e) {
                    Mage::logException($e);
                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
                    Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                    $redirect = Mage::getUrl('onestepcheckout/index/index');
                    // Header('Location: ' . $redirect);
                    // exit();
                    $result->url = $redirect;
                    $this->getResponse()->setBody(json_encode($result));
                }
                $this->getOnepage()->getQuote()->save();

                if ($payment['method'] == 'hosted_pro') {
                    $this->loadLayout('checkout_onepage_review');
                    $html = $this->getLayout()->getBlock('paypal.iframe')->toHtml();

                    $result->html = $html;
                    $result->url = 'null';

                    $this->getResponse()->setBody(json_encode($result));
                } else {
                    if ($redirectUrl) {
                        $redirect = $redirectUrl;
                    } else {
                        $redirect = Mage::getUrl('checkout/onepage/success');
                    }
                    $result->html = '';
                    $result->url = $redirect;

                    $this->getResponse()->setBody(json_encode($result));
                }
            } else {
                $result = new stdClass();
                $redirect = Mage::getUrl('onestepcheckout/index/index');
                // Header('Location: ' . $redirect);
                // exit();
                $result->url = $redirect;
                $this->getResponse()->setBody(json_encode($result));
            }
        }
    }

    protected function _getCustomerSession() {
        return Mage::getSingleton('customer/session');
    }

    /*
     * Reload shipping method html
     */

//    protected function _getShippingMethodsHtml() {
//        //$this->_cleanLayoutCache();
//        $layout = $this->getLayout();
//        $update = $layout->getUpdate();
//        $update->load('onestepcheckout_onestepcheckout_shippingmethod');
//        $layout->generateXml();
//        $layout->generateBlocks();
//        $output = $layout->getOutput();
//        return $output;
//    }

    /*
     * Reload payment method html
     */

//    public function _getPaymentMethodsHtml() {
//        //$this->_cleanLayoutCache();
//        $layout = $this->getLayout();
//        $update = $layout->getUpdate();
//        $update->load('onestepcheckout_onestepcheckout_paymentmethod');
//        $layout->generateXml();
//        $layout->generateBlocks();
//        $output = $layout->getOutput();
//        return $output;
//    }
//
//    public function _getReviewTotalHtml() {
//        //$this->_cleanLayoutCache();
//        $layout = $this->getLayout();
//        $update = $layout->getUpdate();
//        $update->load('onestepcheckout_onestepcheckout_review');
//        $layout->unsetBlock('shippingmethod');
//        $layout->generateXml();
//        $layout->generateBlocks();
//        $output = $layout->getOutput();
//        return $output;
//    }

    protected function _isLoggedIn() {
        return $this->_getCustomerSession()->isLoggedIn();
    }

    public function isVirtual() {
        return $this->getOnepage()->getQuote()->isVirtual();
    }

    /*
     * this function is to pass the validation 
     * Only available for Magento 1.4.x 
     */

    public function setIgnoreValidation() {
        $this->getOnepage()->getQuote()->getBillingAddress()->setShouldIgnoreValidation(true);
        $this->getOnepage()->getQuote()->getShippingAddress()->setShouldIgnoreValidation(true);
    }

    protected function _cleanLayoutCache() {
        Mage::app()->cleanCache(LAYOUT_GENERAL_CACHE_TAG);
    }

    public function enableCustomerFields() {
        $helper = Mage::helper('onestepcheckout');
        $fieldValue = $helper->getFieldValue();
        $prefix = 0;
        $suffix = 0;
        $middlename = 0;
        $birthday = 0;
        $gender = 0;
        $taxvat = 0;
        $fields = $helper->getFieldEnables();
        for ($i = 0; $i < 20; $i++) {
            if ($fields[$i]['value'] == 'prefix')
                $prefix = 1;
            if ($fields[$i]['value'] == 'suffix')
                $suffix = 1;
            if ($fields[$i]['value'] == 'middlename')
                $middlename = 1;
            if ($fields[$i]['value'] == 'birthday')
                $birthday = 1;
            if ($fields[$i]['value'] == 'gender')
                $gender = 1;
            if ($fields[$i]['value'] == 'taxvat')
                $taxvat = 1;
        }

        try {
            if ($prefix == 1) {
                if ($helper->getFieldRequire('prefix')) {
                    Mage::getConfig()->saveConfig('customer/address/prefix_show', 'req');
                    $this->updateAttribute('prefix', 'reg');
                } else {
                    Mage::getConfig()->saveConfig('customer/address/prefix_show', 'opt');
                    $this->updateAttribute('prefix', 'opt');
                }
            }
            if ($suffix == 1) {
                if ($helper->getFieldRequire('suffix')) {
                    Mage::getConfig()->saveConfig('customer/address/suffix_show', 'req');
                    $this->updateAttribute('suffix', 'req');
                } else {
                    Mage::getConfig()->saveConfig('customer/address/suffix_show', 'opt');
                    $this->updateAttribute('suffix', 'opt');
                }
            }
            if ($middlename == 1) {
                Mage::getConfig()->saveConfig('customer/address/middlename_show', '1');
                $this->updateAttribute('middlename', '1');
            }
            if ($birthday == 1) {
                if ($helper->getFieldRequire('birthday')) {
                    Mage::getConfig()->saveConfig('customer/address/dob_show', 'req');
                    $this->updateAttribute('dob', 'req');
                } else {
                    Mage::getConfig()->saveConfig('customer/address/dob_show', 'opt');
                    $this->updateAttribute('dob', 'opt');
                }
            }
            if ($gender == 1) {
                if ($helper->getFieldRequire('gender')) {
                    Mage::getConfig()->saveConfig('customer/address/gender_show', 'req');
                    $this->updateAttribute('gender', 'req');
                } else {
                    Mage::getConfig()->saveConfig('customer/address/gender_show', 'opt');
                    $this->updateAttribute('gender', 'opt');
                }
            }
            if ($taxvat == 1) {
                if ($helper->getFieldRequire('taxvat')) {
                    Mage::getConfig()->saveConfig('customer/address/taxvat_show', 'req');
                    $this->updateAttribute('taxvat', 'req');
                } else {
                    Mage::getConfig()->saveConfig('customer/address/taxvat_show', 'opt');
                    $this->updateAttribute('taxvat', 'opt');
                }
            }
        } catch (Exception $e) {
            
        }
    }

    public function updateAttribute($attribute, $option) {
        $attributeObject = Mage::getSingleton('eav/config')->getAttribute('customer', $attribute);
        $valueConfig = array(
            '' => array('is_required' => 0, 'is_visible' => 0),
            'opt' => array('is_required' => 0, 'is_visible' => 1),
            '1' => array('is_required' => 0, 'is_visible' => 1),
            'req' => array('is_required' => 1, 'is_visible' => 1),
        );
        $data = $valueConfig[$option];
        $attributeObject->setData('is_required', $data['is_required']);
        $attributeObject->setData('is_visible', $data['is_visible']);
        $attributeObject->save();
    }

    public function getreionidAction() {
        $data = $this->getRequest()->getPost();
        $resion = Mage::getModel('directory/region')->getCollection()
                ->addFieldToFilter('country_id', $data['country'])
                ->addFieldToFilter('code', $data['region_id'])
                ->getFirstItem();
        $result = array();
        $result['id'] = $resion->getId();

        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    protected function _getCart() {
        return Mage::getSingleton('checkout/cart');
    }

    /* Start: Modified by Daniel - 02042015 - reload data after modify items - decrease ajax request */

    public function deleteproductAction() {
        $session = $this->getSession();
        $id = (int) $this->getRequest()->getParam('id');
        $result = array();
        $result['success'] = false;
        if ($id) {
            try {
                $this->_getCart()->removeItem($id)
                        ->save();
                $result['success'] = true;
                if (!$this->_getCart()->getQuote()->getItemsCount()) {
                    $result['url'] = Mage::getUrl('checkout/cart', array('_secure' => true));
                }
            } catch (Exception $e) {
                $result['error'] = Mage::helper('onestepcheckout')->__('Cannot remove the item.');
                Mage::logException($e);
            }
        }
        if (isset($result['error']))
            $session->setData('error', $result['error']);
        if (isset($result['url']))
            $session->setData('url', $result['url']);
        if ($result['success'])
            $session->setData('success', true);
        $this->_addOnestepcheckoutHandle(true);
        $result = $this->_getBlockResults($result, true, false);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function minus_productAction() {
        $session = $this->getSession();
        $id = (int) $this->getRequest()->getParam('id');
        /* Start: Huy - fix bug not accept decimal number */
        $qty = (double) $this->getRequest()->getParam('qty');
        /* End: Huy - fix bug not accept decimal number */
        $result = array();
        $result['success'] = false;
        /* Start: added by Daniel - 31/03/2015 - qty increment */
        $citem = Mage::getModel('checkout/session')->getQuote()->getItemById($id);
        if (isset($citem)) {
            $productId = $citem->getProductId();
            $product = Mage::getModel('catalog/product')->load($productId);
            if (isset($product)) {
                $productData = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                if ($productData->getEnableQtyIncrements() == true) {
                    $qtyIncrements = $productData->getQtyIncrements();
                }
            }
        }
        if (isset($qtyIncrements))
            $cartData = array($id => array('qty' => $qty - $qtyIncrements));
        else
        /* End: added by Daniel - 31/03/2015 - qty increment */
            $cartData = array($id => array('qty' => $qty - 1));

        try {


            $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            foreach ($cartData as $index => $data) {
                if (isset($data['qty'])) {
                    $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                }
            }
            $cart = $this->_getCart();
            if (!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                $cart->getQuote()->setCustomerId(null);
            }

            $cartData = $cart->suggestItemsQty($cartData);
            $cart->updateItems($cartData)
                    ->save();

            $result['qty'] = $cartData[$id]['qty'];
            if (!$this->_getCart()->getQuote()->getItemsCount()) {
                $result['url'] = Mage::getUrl('checkout/cart', array('_secure' => true));
            }
            $result['success'] = true;
        } catch (Mage_Core_Exception $e) {
            $result['error'] = Mage::helper('core')->escapeHtml($e->getMessage());
        } catch (Exception $e) {
            $result['error'] = $this->__('Cannot update shopping cart.');
            Mage::logException($e);
        }

        if (isset($result['error']))
            $session->setData('error', $result['error']);
        if (isset($result['url']))
            $session->setData('url', $result['url']);
        if ($result['success'])
            $session->setData('success', true);

        $this->_addOnestepcheckoutHandle(true);
        $result = $this->_getBlockResults($result, true, false);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function add_productAction() {
        $session = $this->getSession();
        $id = (int) $this->getRequest()->getParam('id');
        /* Start: Huy - fix bug not accept decimal number */
        $qty = (double) $this->getRequest()->getParam('qty');
        /* End: : Huy - fix bug not accept decimal number */
        $result = array();
        /* Start: added by Daniel - 31/03/2015 - qty increment */
        $citem = Mage::getModel('checkout/session')->getQuote()->getItemById($id);
        if (isset($citem)) {
            $productId = $citem->getProductId();
            $product = Mage::getModel('catalog/product')->load($productId);
            if (isset($product)) {
                $productData = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                if ($productData->getEnableQtyIncrements() == true) {
                    $qtyIncrements = $productData->getQtyIncrements();
                }
            }
        }
        if (isset($qtyIncrements))
            $cartData = array($id => array('qty' => $qty + $qtyIncrements));
        else
        /* End: added by Daniel - 31/03/2015 - qty increment */
            $cartData = array($id => array('qty' => $qty + 1));

        $result['success'] = false;

        try {
            $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            foreach ($cartData as $index => $data) {
                if (isset($data['qty'])) {
                    $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                }
            }
            $cart = $this->_getCart();
            if (!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                $cart->getQuote()->setCustomerId(null);
            }

            $cartData = $cart->suggestItemsQty($cartData);
            $cart->updateItems($cartData)
                    ->save();
            $message = $cart->getQuote()->getMessages();
            if ($message) {
                $result['error'] = $message['qty']->getCode();
                $cartData = array($id => array('qty' => $qty));
                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)->save();
            }
            $result['qty'] = $cartData[$id]['qty'];
            $result['success'] = true;
        } catch (Mage_Core_Exception $e) {
            $result['error'] = Mage::helper('core')->escapeHtml($e->getMessage());
        } catch (Exception $e) {
            $result['error'] = $this->__('Cannot update shopping cart.');
            Mage::logException($e);
        }

        if (isset($result['error']))
            $session->setData('error', $result['error']);
        if ($result['success'])
            $session->setData('success', true);

        $this->_addOnestepcheckoutHandle(true);
        $result = $this->_getBlockResults($result, true, false);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /* End: Modified by Daniel - 02042015 - reload data after modify items - decrease ajax request */

    private function _getOnestepcheckoutHandle($action, $style = null, $layout = null) {
        if (!$style) {
            $storeId = Mage::app()->getStore()->getStoreId();
            $style = Mage::getStoreConfig('onestepcheckout/general/page_style', $storeId);
            $layout = Mage::getStoreConfig('onestepcheckout/general/page_layout', $storeId);
        }
        return $action . '_' . $style . '_' . $layout;
    }

    private function _addOnestepcheckoutHandle($hasShipping = false, $otherHandle = null) {
        $storeId = Mage::app()->getStore()->getStoreId();
        $style = Mage::getStoreConfig('onestepcheckout/general/page_style', $storeId);
        $layout = Mage::getStoreConfig('onestepcheckout/general/page_layout', $storeId);
        $update = $this->getLayout()->getUpdate();
        if ($hasShipping)
            $update->addHandle($this->_getOnestepcheckoutHandle('onestepcheckout_shipping_payment_review', $style, $layout));
        else
            $update->addHandle($this->_getOnestepcheckoutHandle('onestepcheckout_payment_review', $style, $layout));
        if ($otherHandle)
            foreach ($otherHandle as $handle) {
                $update->addHandle($this->_getOnestepcheckoutHandle($handle, $style, $layout));
            }
        $this->loadLayoutUpdates();
        $this->generateLayoutXml();
        $this->generateLayoutBlocks();
        $this->_isLayoutLoaded = true;
    }
    
    private function _getBlockResults($result = null, $isShipping = false, $updatePayment = true){
        if(!$result) 
			$result = array();
        $payment_method_html = $this->getLayout()->getBlock('onestepcheckout_payment_method')->toHtml();
        $review_total_html = $this->getLayout()->getBlock('review_info')->toHtml();
		if($updatePayment) {
			$result['payment_method'] = $payment_method_html;
		}
        $result['review'] = $review_total_html;
        if($isShipping){
            $shipping_method_html = $this->getLayout()->getBlock('onestepcheckout_shipping_method')->toHtml();
            $result['shipping_method'] = $shipping_method_html;
        }
        return $result;
    }
}
