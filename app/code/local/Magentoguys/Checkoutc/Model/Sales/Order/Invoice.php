<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Enter description here ...
 *
 * @method Mage_Sales_Model_Resource_Order_Invoice _getResource()
 * @method Mage_Sales_Model_Resource_Order_Invoice getResource()
 * @method int getStoreId()
 * @method Mage_Sales_Model_Order_Invoice setStoreId(int $value)
 * @method float getBaseGrandTotal()
 * @method Mage_Sales_Model_Order_Invoice setBaseGrandTotal(float $value)
 * @method float getShippingTaxAmount()
 * @method Mage_Sales_Model_Order_Invoice setShippingTaxAmount(float $value)
 * @method float getTaxAmount()
 * @method Mage_Sales_Model_Order_Invoice setTaxAmount(float $value)
 * @method float getBaseTaxAmount()
 * @method Mage_Sales_Model_Order_Invoice setBaseTaxAmount(float $value)
 * @method float getStoreToOrderRate()
 * @method Mage_Sales_Model_Order_Invoice setStoreToOrderRate(float $value)
 * @method float getBaseShippingTaxAmount()
 * @method Mage_Sales_Model_Order_Invoice setBaseShippingTaxAmount(float $value)
 * @method float getBaseDiscountAmount()
 * @method Mage_Sales_Model_Order_Invoice setBaseDiscountAmount(float $value)
 * @method float getBaseToOrderRate()
 * @method Mage_Sales_Model_Order_Invoice setBaseToOrderRate(float $value)
 * @method float getGrandTotal()
 * @method Mage_Sales_Model_Order_Invoice setGrandTotal(float $value)
 * @method float getShippingAmount()
 * @method Mage_Sales_Model_Order_Invoice setShippingAmount(float $value)
 * @method float getSubtotalInclTax()
 * @method Mage_Sales_Model_Order_Invoice setSubtotalInclTax(float $value)
 * @method float getBaseSubtotalInclTax()
 * @method Mage_Sales_Model_Order_Invoice setBaseSubtotalInclTax(float $value)
 * @method float getStoreToBaseRate()
 * @method Mage_Sales_Model_Order_Invoice setStoreToBaseRate(float $value)
 * @method float getBaseShippingAmount()
 * @method Mage_Sales_Model_Order_Invoice setBaseShippingAmount(float $value)
 * @method float getTotalQty()
 * @method Mage_Sales_Model_Order_Invoice setTotalQty(float $value)
 * @method float getBaseToGlobalRate()
 * @method Mage_Sales_Model_Order_Invoice setBaseToGlobalRate(float $value)
 * @method float getSubtotal()
 * @method Mage_Sales_Model_Order_Invoice setSubtotal(float $value)
 * @method float getBaseSubtotal()
 * @method Mage_Sales_Model_Order_Invoice setBaseSubtotal(float $value)
 * @method float getDiscountAmount()
 * @method Mage_Sales_Model_Order_Invoice setDiscountAmount(float $value)
 * @method int getBillingAddressId()
 * @method Mage_Sales_Model_Order_Invoice setBillingAddressId(int $value)
 * @method int getIsUsedForRefund()
 * @method Mage_Sales_Model_Order_Invoice setIsUsedForRefund(int $value)
 * @method int getOrderId()
 * @method Mage_Sales_Model_Order_Invoice setOrderId(int $value)
 * @method int getEmailSent()
 * @method Mage_Sales_Model_Order_Invoice setEmailSent(int $value)
 * @method int getCanVoidFlag()
 * @method Mage_Sales_Model_Order_Invoice setCanVoidFlag(int $value)
 * @method int getState()
 * @method Mage_Sales_Model_Order_Invoice setState(int $value)
 * @method int getShippingAddressId()
 * @method Mage_Sales_Model_Order_Invoice setShippingAddressId(int $value)
 * @method string getCybersourceToken()
 * @method Mage_Sales_Model_Order_Invoice setCybersourceToken(string $value)
 * @method string getStoreCurrencyCode()
 * @method Mage_Sales_Model_Order_Invoice setStoreCurrencyCode(string $value)
 * @method string getTransactionId()
 * @method Mage_Sales_Model_Order_Invoice setTransactionId(string $value)
 * @method string getOrderCurrencyCode()
 * @method Mage_Sales_Model_Order_Invoice setOrderCurrencyCode(string $value)
 * @method string getBaseCurrencyCode()
 * @method Mage_Sales_Model_Order_Invoice setBaseCurrencyCode(string $value)
 * @method string getGlobalCurrencyCode()
 * @method Mage_Sales_Model_Order_Invoice setGlobalCurrencyCode(string $value)
 * @method string getIncrementId()
 * @method Mage_Sales_Model_Order_Invoice setIncrementId(string $value)
 * @method string getCreatedAt()
 * @method Mage_Sales_Model_Order_Invoice setCreatedAt(string $value)
 * @method string getUpdatedAt()
 * @method Mage_Sales_Model_Order_Invoice setUpdatedAt(string $value)
 * @method float getHiddenTaxAmount()
 * @method Mage_Sales_Model_Order_Invoice setHiddenTaxAmount(float $value)
 * @method float getBaseHiddenTaxAmount()
 * @method Mage_Sales_Model_Order_Invoice setBaseHiddenTaxAmount(float $value)
 * @method float getShippingHiddenTaxAmount()
 * @method Mage_Sales_Model_Order_Invoice setShippingHiddenTaxAmount(float $value)
 * @method float getBaseShippingHiddenTaxAmount()
 * @method Mage_Sales_Model_Order_Invoice setBaseShippingHiddenTaxAmount(float $value)
 * @method float getShippingInclTax()
 * @method Mage_Sales_Model_Order_Invoice setShippingInclTax(float $value)
 * @method float getBaseShippingInclTax()
 * @method Mage_Sales_Model_Order_Invoice setBaseShippingInclTax(float $value)
 *
 * @category    Mage
 * @package     Mage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Magentoguys_Checkoutc_Model_Sales_Order_Invoice extends Mage_Sales_Model_Order_Invoice
{
   
    /**
     * Send email with invoice data
     *
     * @param boolean $notifyCustomer
     * @param string $comment
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function sendEmail($notifyCustomer = true, $comment = '')
    {
        $order = $this->getOrder();
        $storeId = $order->getStore()->getId();
		
		$orderId = $order->getOrderId();
		$deliveryOptions = Mage::getModel('checkoutc/order')->getByOrder($orderId);
		$deliverySelected = $deliveryOptions->getValue();
		
		if (!Mage::helper('sales')->canSendNewInvoiceEmail($storeId)) {
            return $this;
        }
        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);
        // Check if at least one recepient is found
        if (!$notifyCustomer && !$copyTo) {
            return $this;
        }

        // Start store emulation process
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $paymentBlock = Mage::helper('payment')->getInfoBlock($order->getPayment())
                ->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (Exception $exception) {
            // Stop store emulation process
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            throw $exception;
        }

        // Stop store emulation process
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        // Retrieve corresponding email template id and customer name
        if ($order->getCustomerIsGuest() && $deliverySelected!=2) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
            $customerName = $order->getBillingAddress()->getName();
        }elseif(!$order->getCustomerIsGuest() && $deliverySelected!=2) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
            $customerName = $order->getCustomerName();
        }elseif($order->getCustomerIsGuest() && $deliverySelected==2){
			$templateId = 21;
			$customerName = $order->getBillingAddress()->getName();
		}elseif(!$order->getCustomerIsGuest() && $deliverySelected==2){
			$templateId = 20;
			$customerName = $order->getCustomerName();
		}

        $mailer = Mage::getModel('core/email_template_mailer');
        if ($notifyCustomer) {
            $emailInfo = Mage::getModel('core/email_info');
            $emailInfo->addTo($order->getCustomerEmail(), $customerName);
            if ($copyTo && $copyMethod == 'bcc') {
                // Add bcc to customer email
                foreach ($copyTo as $email) {
                    $emailInfo->addBcc($email);
                }
            }
            $mailer->addEmailInfo($emailInfo);
        }

        // Email copies are sent as separated emails if their copy method is 'copy' or a customer should not be notified
        if ($copyTo && ($copyMethod == 'copy' || !$notifyCustomer)) {
            foreach ($copyTo as $email) {
                $emailInfo = Mage::getModel('core/email_info');
                $emailInfo->addTo($email);
                $mailer->addEmailInfo($emailInfo);
            }
        }

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
                'order'        => $order,
                'invoice'      => $this,
                'comment'      => $comment,
                'billing'      => $order->getBillingAddress(),
                'payment_html' => $paymentBlockHtml
            )
        );
        $mailer->send();
        $this->setEmailSent(true);
        $this->_getResource()->saveAttribute($this, 'email_sent');

        return $this;
    }

}
