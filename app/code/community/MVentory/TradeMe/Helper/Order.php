<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a Commercial Software License.
 * No sharing - This file cannot be shared, published or
 * distributed outside of the licensed organisation.
 * No Derivatives - You can make changes to this file for your own use,
 * but you cannot share or redistribute the changes.
 * This Copyright Notice must be retained in its entirety.
 * The full text of the license was supplied to your organisation as
 * part of the licensing agreement with mVentory.
 *
 * @package MVentory/TradeMe
 * @copyright Copyright (c) 2015 mVentory Ltd. (http://mventory.com)
 * @license Commercial
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

/**
 * Order helper
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Helper_Order extends MVentory_TradeMe_Helper_Data
{
  /**
   * Create order using supplied data
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param float $price
   *   Sell price
   *
   * @param int $qty
   *   Sell quantity
   *
   * @param Mage_Customer_Model_Customer $customer
   *   Customer model
   *
   * @param array $address
   *   Address data
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return Mage_Sales_Model_Order
   *   Model of newly created order
   */
  public function create ($product, $price, $qty, $customer, $address, $store) {
    $quote = $this->_createQuote($store);

    $this->_setCustomer($quote, $customer);
    $this->_setAddresses($quote, $address);
    $this->_setProduct($quote, $product, $price, $qty);
    $this->_setShippingMethod($quote);
    $this->_setPaymentMethod($quote, $price, $store);

    $this->_saveQuote($quote);

    $order = $this->_createOrder($quote);

    try {
      $shipment = $this->_createShipment($order);
      $invoice = $this->_createInvoice($order);

      $this->_completeOrder($order, $shipment, $invoice);
    } catch (Exception $e) {
      Mage::logException($e);
    }

    return $order;
  }

  /**
   * Create new shopping cart
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return Mage_Sales_Model_Quote
   *   Quote (shopping cart) model
   */
  protected function _createQuote ($store) {
    return Mage::getModel('sales/quote')
      ->setStore($store)
      ->setIsActive(false)
      ->setIsMultiShipping(false);
  }

  /**
   * Set customer for shopping cart
   *
   * @param Mage_Sales_Model_Quote $quote
   *   Quote model
   *
   * @param Mage_Customer_Model_Customer $customer
   *   Customer model
   */
  protected function _setCustomer ($quote, $customer) {
    $quote
      ->setCustomer($customer)
      ->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER)
      ->setPasswordHash($customer->encryptPassword($customer->getPassword()));
  }

  /**
   * Set address for shopping cart
   *
   * @param Mage_Sales_Model_Quote $quote
   *   Quote model
   *
   * @param array $address
   *   Address data
   */
  protected function _setAddresses ($quote, $address) {
    $quoteAddress = Mage::getModel('sales/quote_address')
      ->importCustomerAddress($address)
      ->implodeStreetAddress();

    if (($valid = $address->validate()) !== true)
      throw new Exception(implode(PHP_EOL, $valid));

    $billingAddress = clone $address;
    $billingAddress
      ->unsAddressId()
      ->unsAddressType();

    $shippingAddress = $quote->getShippingAddress();
    $shippingMethod = $shippingAddress->getShippingMethod();

    $shippingAddress
      ->addData($billingAddress->getData())
      ->setSameAsBilling(1)
      ->setShippingMethod($shippingMethod)
      ->setCollectShippingRates(true);

    $quote->setBillingAddress($quoteAddress);
  }

  /**
   * Add product to shopping cart
   *
   * @param Mage_Sales_Model_Quote $quote
   *   Quote model
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param float $price
   *   Sell price
   *
   * @param int $qty
   *   Sell quantity
   */
  protected function _setProduct ($quote, $product, $price, $qty) {
    $quote
      ->addProduct($product, new Varien_Object(['qty' => $qty]))
      ->setCustomPrice($price)
      ->setOriginalCustomPrice($price);;
  }

  /**
   * Set shipping method for shopping cart
   *
   * @param Mage_Sales_Model_Quote $quote
   *   Quote model
   */
  protected function _setShippingMethod ($quote) {
    $address = $quote->getShippingAddress();

    $rate = $address
      ->collectShippingRates()
      ->getShippingRateByCode('mventory_mventory');

    if (!$rate)
      throw new Exception('shipping_method_is_not_available');

    $address->setShippingMethod('mventory_mventory');
  }

  /**
   * Set payment method for shopping cart
   *
   * @see MVentory_API_Model_Cart_Payment_Api::setPaymentMethod
   *   That Magento method is used as base for the current method
   *
   * @param Mage_Sales_Model_Quote $quote
   *   Quote model
   *
   * @param float $price
   *   Sell price
   *
   * @param int $qty
   *   Sell quantity
   */
  protected function _setPaymentMethod ($quote, $price, $store) {
    $methodCode = ($price == 0) ? 'free' : 'mventory';

    $address = $quote->getShippingAddress();
    $address->setPaymentMethod($methodCode);
    $address->setCollectShippingRates(true);

    $total = $quote->getBaseSubtotal();
    $methods = Mage::helper('payment')->getStoreMethods($store, $quote);

    $baseCurrencyCode = $store->getBaseCurrencyCode();

    foreach ($methods as $method) {
      if ($method->getCode() != $methodCode)
        continue;

      if (!$this->_canUsePaymentMethod($method, $quote, $baseCurrencyCode))
        throw new Exception('method_not_allowed');

      break;
    }

    $payment = $quote->getPayment();
    $payment->importData(['method' => $methodCode, 0 => null]);
  }

  /**
   * Save shopping cart
   *
   * @param Mage_Sales_Model_Quote $quote
   *   Quote model
   */
  protected function _saveQuote ($quote) {
    $quote->save();
  }

  /**
   * Create order for specified shopping cart
   *
   * @param Mage_Sales_Model_Quote $quote
   *   Quote model
   *
   * @return Mage_Sales_Model_Order
   *   Order model
   */
  protected function _createOrder ($quote) {
    $customerResource = Mage::getModel('checkout/api_resource_customer');
    $customerResource->prepareCustomerForQuote($quote);

    $quote
      ->setTotalsCollectedFlag(false)
      ->collectTotals();

    $service = Mage::getModel('sales/service_quote', $quote);
    $service->submitAll();

    $order = $service->getOrder();

    if ($order) {
      Mage::dispatchEvent(
        'checkout_type_onepage_save_order_after',
        ['order' => $order, 'quote' => $quote]
      );

      try {
        $order->queueNewOrderEmail();
      } catch (Exception $e) {
        Mage::logException($e);
      }
    }

    Mage::dispatchEvent(
      'checkout_submit_all_after',
      array('order' => $order, 'quote' => $quote)
      );

    return $order;
  }

  /**
   * Add shipment to supplied order
   *
   * @param Mage_Sales_Model_Order order
   *   Order model
   *
   * @return Mage_Sales_Model_Order_Shipment
   *   Order shipment model
   */
  protected function _createShipment ($order) {
    return $order
      ->prepareShipment()
      ->register();
  }

  /**
   * Add invoice to supplied order
   *
   * @param Mage_Sales_Model_Order order
   *   Order model
   *
   * @return Mage_Sales_Model_Order_Invoice
   *   Order invoice model
   */
  protected function _createInvoice ($order) {
    return $order
      ->prepareInvoice()
      ->register();
  }

  /**
   * Complete order
   *
   * @param Mage_Sales_Model_Order order
   *   Order model
   *
   * @param Mage_Sales_Model_Order_Shipment $shipment
   *   Order shipment model
   *
   * @param Mage_Sales_Model_Order_Invoice $invoice
   *   Order invoice model
   */
  protected function _completeOrder ($order, $shipment, $invoice) {
    $transactionSave = Mage::getModel('core/resource_transaction')
      ->addObject($shipment)
      ->addObject($invoice)
      ->addObject($order)
      ->save();
  }

  /**
   * Check if supplied payment method is applicable for the quote
   *
   * @see Mage_Checkout_Model_Cart_Payment_Api::_canUsePaymentMethod()
   *   That Magento method is used as base for the current method
   *
   * @param Mage_Sales_Model_Quote $quote
   *   Quote model
   *
   * @param string $baseCurrencyCode
   *   Code of store's base currency
   *
   * @return boolean
   *   Result of the check
   */
  protected function _canUsePaymentMethod($method, $quote, $baseCurrencyCode)
  {
    if (!($method->isGateway() || $method->canUseInternal()))
      return false;

    if (!$method->canUseForCountry($quote->getBillingAddress()->getCountry()))
      return false;

    if (!$method->canUseForCurrency($baseCurrencyCode))
      return false;

    //Checking for min/max order total for assigned payment method
    $total = $quote->getBaseGrandTotal();
    $minTotal = $method->getConfigData('min_order_total');
    $maxTotal = $method->getConfigData('max_order_total');

    if ((!empty($minTotal) && ($total < $minTotal))
        || (!empty($maxTotal) && ($total > $maxTotal)))
      return false;

    return true;
  }
}
