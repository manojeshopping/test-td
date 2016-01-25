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
 * Buyer helper
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Helper_Buyer extends MVentory_TradeMe_Helper_Data
{
  /**
   * Data used to create dummy address when TradeMe doesn't supply it or
   * supplied data misses some required in Magento fields
   *
   * @var array
   */
  protected $_defaultAddressData = [
    'firstname' => 'First name not specified',
    'lastname' => 'Last name not specified',
    'street' => ['Shipping address not specified', ''],
    'suburb' => '',
    'city' => 'City not specified',
    'postcode' => '0000',
    'country' => 'New Zealand',
    'telephone' => 'Telephone not specified'
  ];

  /**
   * Get buyer using supplied data
   *
   * @param array $data
   *   Sale data from TradeMe listing details
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   Buyer model and address model
   */
  public function get ($data, $store) {
    $onlyDefaultCustomer = Mage::getStoreConfigFlag(
      MVentory_TradeMe_Model_Config::_ORDER_CUSTOMER_DEF,
      $store
    );

    if ($onlyDefaultCustomer)
      return $this->_default($store);

    $customer = $this->_load($data, $store);
    if ($customer)
      return $customer;

    $canCreateCustomer = Mage::getStoreConfigFlag(
      MVentory_TradeMe_Model_Config::_ORDER_CUSTOMER_NEW,
      $store
    );

    if ($canCreateCustomer && ($customer = $this->_create($data, $store)))
      return $customer;

    return $this->_default($store);
  }

  /**
   * Try to load buyer by email
   *
   * @param array $data
   *   Sale data from TradeMe listing details
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   Buyer model and address model
   */
  protected function _load ($data, $store) {
    if (!$data['buyer'])
      return;

    $buyer = Mage::getModel('customer/customer')
      ->setStore($store)
      ->loadByEmail($data['buyer']['email']);

    try {
      $address = $this->_createAddress($this->_prepareAddressData(
        $data['shipping_address'],
        $data['buyer']
      ));
    }
    catch (Mage_Core_Exception $e) {
      return Mage::logException($e);
    }

    return $buyer->getId() ? [$buyer, $address] : null;
  }

  /**
   * Create new buyer
   *
   * @param array $data
   *   Sale data from TradeMe listing details
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   Buyer model and address model
   */
  protected function _create ($data, $store) {
    if (!$data['buyer'])
      return;

    //Prepare data
    $buyer = $data['buyer'];
    $addressData = $this->_prepareAddressData(
      $data['shipping_address'],
      $buyer
    );

    //Create buyer model

    $buyer = Mage::getModel('customer/customer')
      ->setStore($store)
      ->setFirstname($addressData['firstname'])
      ->setLastname($addressData['lastname'])
      ->setEmail($buyer['email']);

    try {
      $buyer->save();
    }
    catch (Mage_Core_Exception $e) {
      return Mage::logException($e);
    }

    if (!$buyer->getId())
      return;

    try {
      $address = $this->_createAddress($addressData)
        ->setCustomer($buyer)
        ->setIsDefaultBilling(true)
        ->setIsDefaultShipping(true)
        ->save();
    }
    catch (Mage_Core_Exception $e) {
      Mage::logException($e);

      //Remove newly created buyer model because we can't get address for it
      //thus it can't be used to create orders
      $buyer->delete();

      return;
    }

    return [$buyer, $address];
  }

  /**
   * Load default buyer
   *
   * @param int $defaultBuyerId
   *   ID of default buyer model
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   Buyer model and address model
   */
  protected function _default ($store) {
    $id = $store->getConfig(MVentory_TradeMe_Model_Config::_ORDER_CUSTOMER_ID);
    if (!$id)
      return;

    $defaultBuyer = Mage::getModel('customer/customer')
      ->setStore($store)
      ->load($id);

    return $defaultBuyer->getId()
             ? [$defaultBuyer, $defaultBuyer->getDefaultBillingAddress()]
             : null;
  }

  /**
   * Prepare address data to create address model
   *
   * @see MVentory_TradeMe_Helper_Buyer::$_defaultAddressData
   *   See for dummy data used for missing address fields
   *
   * @param array $data
   *   Address data from TradeMe listing details
   *
   * @return array
   *   Prepare address data
   */
  protected function _prepareAddressData ($data, $buyer) {
    foreach (['firstname', 'lastname'] as $key)
      if (!(isset($data[$key]) && $data[$key]))
        $data[$key] = $buyer['nickname'];

    return array_merge($this->_defaultAddressData, $data);
  }

  /**
   * Create address model using supplied data
   *
   * @param array $data
   *   Address data
   *
   * @return Mage_Customer_Model_Address
   *   Address model
   *
   * @throws Mage_Core_Exception
   *   If supplied address data is not valid
   */
  protected function _createAddress ($data) {
    //Get country ID by country name
    $countryId = Mage::getModel('directory/country')
      ->loadByCode(
          Zend_Locale_Data_Translation::$regionTranslation[$data['country']]
        )
      ->getId();

    //Create address
    $address = Mage::getModel('customer/address')
      ->setFirstname($data['firstname'])
      ->setLastname($data['lastname'])
      ->setStreet($data['street'])
      ->setCity($data['city'])
      ->setCountryId($countryId)
      ->setPostcode($data['postcode'])
      ->setTelephone($data['telephone']);

    if (is_array($valid = $address->validate()))
      throw new Mage_Core_Exception(implode("\n", $valid));

    return $address;
  }
}
