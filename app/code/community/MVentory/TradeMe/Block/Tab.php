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
 * @copyright Copyright (c) 2014 mVentory Ltd. (http://mventory.com)
 * @license Commercial
 */

/**
 * TradeMe tab
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Block_Tab
  extends Mage_Adminhtml_Block_Widget
  implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
  const URL = 'http://www.trademe.co.nz';

  private $_helper = null;

  protected $_website = null;
  protected $_store = null;

  private $_preselectedCategories = null;

  //TradeMe options from the session
  private $_session = null;

  private $_auction;

  private $_accounts = null;
  private $_accountId = null;

  protected $_currency = null;
  private $_hasSpecialPrice;
  private $_productPrice;

  public function __construct() {
    parent::__construct();

    $trademe = Mage::helper('trademe/product');

    $product = $this->getProduct();
    $productId = $product->getId();

    $this->_auctions = $this->_loadAuctions($productId);

    $this->_helper = Mage::helper('mventory/product');
    $this->_website = $this->_helper->getWebsite($product);
    $this->_store = $this->_website->getDefaultStore();

    $this->_currency = $this->_store->getBaseCurrency();

    /**
     * @var MVentory_TradeMe_Helper_Auction::getPrice()
     *
     * @todo This should be replace with call to the function above
     *   after it will be updated to calculate "partial" auction price
     */
    $this->_productPrice = $trademe->getPrice($product, $this->_website);

    $this->_hasSpecialPrice = Mage::helper('trademe/product')->hasSpecialPrice(
      $product,
      $this->_store
    );

    /**
     * @var MVentory_TradeMe_Helper_Auction::getPrice()
     *
     * @todo This should be replace with call to the function above after
     *   it will be updated to calculate "partial" auction price
     */
    if ($this->_currency->getCode() != MVentory_TradeMe_Model_Config::CURRENCY)
      $this->_productPrice = $trademe->currencyConvert(
        $this->_productPrice,
        $this->_currency,
        MVentory_TradeMe_Model_Config::CURRENCY,
        $this->_store
      );

    //Get TradeMe parameters from the session
    $session = Mage::getSingleton('adminhtml/session');

    $this->_session = $session->getData('trademe_data');
    $session->unsetData('trademe_data');

    $this->_accounts = $trademe->prepareAccounts(
      $trademe->getAccounts($this->_website),
      $product,
      $this->_website->getDefaultStore()
    );

    //Select current TradeMe acccount
    switch (true) {
      case isset($this->_session['account_id']):
        $this->_accountId = $this->_session['account_id'];
        break;

      case count($this->_auctions):
        $this->_accountId = $this
          ->_auctions
          ->getLastItem()['account_id'];

        break;

      case count($this->_accounts):
        reset($this->_accounts);
        $this->_accountId = key($this->_accounts);
        break;

      default:
        $this->_accountId = false;
    }

    if (count($this->_accounts)) {
        foreach ($this->_accounts as $id => $data)
          unset($this->_accounts[$id]['free_shipping_cost']);

      $this->_calculateShippingRates();
      $this->_calculateFees();
    }
  }

  /**
   * Return Tab label
   *
   * @return string
   */
  public function getTabLabel() {
    return $this->__('TradeMe');
  }

  /**
   * Return Tab title
   *
   * @return string
   */
  public function getTabTitle() {
    return $this->__('TradeMe');
  }

  /**
   * Can show tab in tabs
   *
   * @return boolean
   */
  public function canShowTab() {
    return true;
  }

  /**
   * Tab is hidden
   *
   * @return boolean
   */
  public function isHidden() {
    return false;
  }

  public function getProduct () {
    return Mage::registry('current_product');
  }

  /**
   * Return price or special price for current product
   *
   * @return float
   */
  public function getProductPrice () {
    return $this->_productPrice;
  }

  public function getCategories () {
    $api = new MVentory_TradeMe_Model_Api();
    return $api->getCategories();
  }

  public function getPreselectedCategories () {
    if ($this->_preselectedCategories)
      return $this->_preselectedCategories;

    $this->_preselectedCategories = array();

    $matchResult = Mage::getModel('trademe/matching')
      ->matchCategory($this->getProduct());

    if (isset($matchResult['id']) && $matchResult['id'] > 0) {
      $this->_preselectedCategories[] = $matchResult['id'];
      $this->setCategory($this->_preselectedCategories[0]);
    }

    if (isset($this->_session['category'])
        && ($id = $this->_session['category'])
        && !in_array($id, $this->_preselectedCategories)) {

      $categories = $this->getCategories();

      if (isset($categories[$id])) {
        $this->_preselectedCategories[] = $id;
        $this->setCategory($id);
      }
    }

    return $this->_preselectedCategories;
  }

  public function getColsNumber () {
    if (!$categories = $this->getCategories())
      return 0;

    $cols = 0;

    foreach ($categories as $category)
      if (count($category['name']) > $cols)
        $cols = count($category['name']);

    return $cols;
  }

  public function getUrlTemplates () {
    $productId = $this
                   ->getProduct()
                   ->getId();

    $submit = $this->getUrl('adminhtml/trademe_listing/submit/',
                            array('id' => $productId));

    $categories = $this->getUrl('adminhtml/trademe_categories/',
                                array('product_id' => $productId));

    $updates = [];

    foreach ($this->_auctions as $auction)
      if ($auction->isFullPrice())
        $updates[$auction['listing_id']] = $this->getUrl(
          'adminhtml/trademe_listing/update',
          [
            'id' => $auction['listing_id'],
            'product_id' => $productId
          ]
        );

    return Zend_Json::encode(compact('submit', 'categories','updates'));
  }

  public function getSubmitButton () {
    $enabled = count($this->getPreselectedCategories()) > 0;

    $label = $this->__('Submit');
    $class = $enabled ? '' : 'disabled';

    return $this->getButtonHtml($label, null, $class, 'trademe-submit');
  }

  public function getCategoriesButton () {
    $label = $this->__('Show all categories');

    return $this->getButtonHtml($label, null, '', 'trademe-categories-show');
  }

  public function getPreparedAttributes ($categoryId) {
    $attributes = Mage::helper('trademe')->getAttributes($categoryId);

    if (!$attributes)
      return;

    $product = $this->getProduct();

    $existing = array();
    $missing = array();
    $optional = array();

    foreach ($attributes as $attribute) {
      if (!(isset($attribute['IsRequiredForSell'])
            && $attribute['IsRequiredForSell'])) {
        $optional[] = $attribute;

        continue;
      }

      $name = strtolower($attribute['Name']);

      if ($product->hasData($name) || $product->hasData($name . '_'))
        $existing[] = $attribute;
      else
        $missing[] = $attribute;
    }

    return compact('existing', 'missing', 'optional');
  }

  public function getAllowBuyNow () {
    $attr = 'tm_allow_buy_now';
    $field = 'allow_buy_now';

    return (int) $this->_getAttributeValue($attr, $field);
  }

  public function getAddFees () {
    return (int) $this->_getAttributeValue('tm_add_fees', 'add_fees');
  }

  public function getRelist () {
    return (int) $this->_getAttributeValue('tm_relist', 'relist');
  }

  public function getAvoidWithdrawal () {
    $attr = 'tm_avoid_withdrawal';
    $field = 'avoid_withdrawal';

    return (int) $this->_getAttributeValue($attr, $field);
  }

  public function getShippingType () {
    $attr = 'tm_shipping_type';
    $field = 'shipping_type';

    return (int) $this->_getAttributeValue($attr, $field);
  }

  public function getPickup () {
    $attr = 'tm_pickup';
    $field = 'pickup';

    return (int) $this->_getAttributeValue($attr, $field);
  }

  public function getAccounts () {
    $_accounts = array();

    foreach ($this->_accounts as $id => $account)
      $_accounts[] = array(
        'value' => $id,
        'label' => $account['name'],
        'selected' => $this->_accountId === $id
      );

    return $_accounts;
  }

  protected function _getAttributeValue ($code, $field = null) {
    $sources = array();

    if ($field)
      $sources[$field] = $this->_session;

    $sources[$code] = $this->getProduct()->getData();

    foreach ($sources as $key => $source)
      if (isset($source[$key])) {
        $value = $source[$key];

        if ($value === null)
          return -1;

        return $value;
      }

    return -1;
  }

  public function getShippingRate () {
    if (!isset($this->_accounts[$this->_accountId]))
      return null;

    $account = $this->_accounts[$this->_accountId];

    //$shippingType = $this->getShippingType();

    //if ($shippingType == -1 || $shippingType == null)
    //  $shippingType = $account['shipping_type'];

    //if ($shippingType == MVentory_TradeMe_Model_Config::SHIPPING_FREE)
    //  return isset($account['free_shipping_cost'])
    //           ? $account['free_shipping_cost']
    //             : null;

    return isset($account['shipping_rate']) ? $account['shipping_rate'] : null;
  }

  public function getFees () {
    if (!isset($this->_accounts[$this->_accountId]))
      return 0;

    $account = $this->_accounts[$this->_accountId];

    $addFees = $this->getAddFees();

    if ($addFees == -1)
      $addFees = $account['add_fees'];

    if ($addFees == MVentory_TradeMe_Model_Config::FEES_NO)
      return 0;

    if ($addFees == MVentory_TradeMe_Model_Config::FEES_SPECIAL
        && !$this->_hasSpecialPrice)
      return 0;

    //$shippingType = $this->getShippingType();

    //if ($shippingType == -1 || $shippingType == null)
    //  $shippingType = $account['shipping_type'];

    //return $shippingType == MVentory_TradeMe_Model_Config::SHIPPING_FREE
    //         ? $account['free_shipping_fees']
    //           : $account['fees'];

    return $account['fees'];
  }

  public function prepareDataForJs () {
    $product = $this->getProduct();

    $data = array(
      'product' => array(
        'price' => $this->_productPrice,
        'has_special_price' => $this->_hasSpecialPrice
      ),
      'accounts' => $this->_accounts
    );

    foreach ($product->getData() as $key => $value)
      if (strpos($key, 'tm_') === 0)
        $data['product'][substr($key, 3)] = $value;

    foreach ($data['accounts'] as &$account)
      unset($account['key'], $account['secret'], $account['access_token']);

    return $data;
  }

  public function getAttributeOptions ($code) {
    $product = $this->getProduct();

    $attributes = $product->getAttributes();

    if (!isset($attributes[$code]))
      return array();

    $attribute = $attributes[$code];

    return $attribute
             ->getSource()
             ->getAllOptions();
  }

  public function getAttributeLabel ($code) {
    $product = $this->getProduct();

    $attributes = $product->getAttributes();

    if (!isset($attributes[$code]))
      return;

    $attribute = $attributes[$code];

    return $this->__($attributes[$code]->getFrontendLabel());
  }

  protected function _loadAuctions ($productId) {
    return Mage::getResourceModel('trademe/auction_collection')
      ->addFieldToFilter('product_id', $productId);
  }

  /**
   * Return prepared list of all active auctions for the current product
   *
   * @return array
   *   Prepared list of active auctions
   */
  protected function _getAuctions () {
    if (!count($this->_auctions))
      return [];

    $auctions = [];
    $helper = Mage::helper('core');

    $types = [
      MVentory_TradeMe_Model_Config::AUCTION_NORMAL
        => $this->_helper->__('Full-price auction'),
      MVentory_TradeMe_Model_Config::AUCTION_FIXED_END_DATE
        => $this->_helper->__('$1 reserve auction')
    ];

    foreach ($this->_auctions as $auction) {
      $auctionId = $auction['account_id'];
      $listingId = $auction['listing_id'];

      $p = [
        'id' => $listingId,
        'product_id' => $this->getProduct()->getId()
      ];

      $data = [
        'account' => isset($this->_accounts[$auctionId])
          ? $this->_accounts[$auctionId]['name']
          : '',
        'type' => $types[(int) $auction['type']],
        'listing_id' => $listingId,
        'listing_url' => $auction->getUrl($this->_website),
        'listed_at' => $helper->formatDate(
          $auction['listed_at'],
          Mage_Core_Model_Locale::FORMAT_TYPE_SHORT,
          true
        ),
        'remove_url' => $this->getUrl('adminhtml/trademe_listing/remove/', $p)
      ];

      if ($auction->isFullPrice()) {
        $data['status_url'] = $this->getUrl(
          'adminhtml/trademe_listing/check/',
          $p
        );

        $data['update_url'] = true;
      }

      $auctions[] = $data;
    }

    return $auctions;
  }

  protected function _calculateShippingRates () {
    $helper = Mage::helper('trademe');

    $product = $this->getProduct();

    foreach ($this->_accounts as &$account) {
      $account['shipping_rate'] = (float) $helper->getShippingRate(
        $product,
        $account['name'],
        $this->_website
      );

      $code = $this->_currency;

      /**
       * @todo Replace with MVentory_TradeMe_Helper_Data::applyTmCurrency()
       *   helper method after it'll have been implemented.
       *
       * @see MVentory_TradeMe_Helper_Data::currencyConvert()
       *   See description for the method to find more info
       */
      if ($code->getCode() != MVentory_TradeMe_Model_Config::CURRENCY)
        $account['shipping_rate'] = $helper->currencyConvert(
          $account['shipping_rate'],
          $this->_currency,
          MVentory_TradeMe_Model_Config::CURRENCY,
          $this->_store
        );
    }
  }

  protected function _calculateFees () {
    $helper = Mage::helper('trademe');

    foreach ($this->_accounts as &$account) {
      $shippingRate = isset($account['shipping_rate'])
                        ? $account['shipping_rate']
                          : 0;

      //$freeShippingCost = isset($account['free_shipping_cost'])
      //                      ? $account['free_shipping_cost']
      //                        : 0;

      $account['fees']
        = $helper->calculateFees($this->_productPrice + $shippingRate);

      //$account['free_shipping_fees']
      //  = $helper->calculateFees($this->_productPrice + $freeShippingCost);
    }
  }
}
