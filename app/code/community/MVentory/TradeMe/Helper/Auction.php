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
 * @copyright Copyright (c) 2014-2015 mVentory Ltd. (http://mventory.com)
 * @license Commercial
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

/**
 * Auction helper
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Helper_Auction extends MVentory_TradeMe_Helper_Data
{
  public function isInAllowedPeriod ($store) {
    $tz = new DateTimeZone(MVentory_TradeMe_Model_Config::TIMEZONE);

    if (!$period = $this->_getEndTimePeriod($tz, $store))
      return false;

    list($start, $end) = $period;

    $now = new DateTime('now', $tz);

    return ($start <= $now)
           && ($now < $end)
           && $this->_isAuctionDay($now, $store);
  }

  /**
   * Check if current NZ time is in allowed hours for normal auctions
   *
   * @return boolean
   *   Result of check
   */
  public function isInAllowedHours () {
    $tz = new DateTimeZone(MVentory_TradeMe_Model_Config::TIMEZONE);

    $start = DateTime::createFromFormat(
      'H',
      MVentory_TradeMe_Model_Config::AUC_TIME_START,
      $tz
    );

    $end = DateTime::createFromFormat(
      'H',
      MVentory_TradeMe_Model_Config::AUC_TIME_END,
      $tz
    );

    $now = new DateTime('now', $tz);

    return $start <= $now && $now < $end;
  }

  public function getProductsListedToday ($store, $type) {
    $defTz = new DateTimeZone(date_default_timezone_get());
    $tz = new DateTimeZone(MVentory_TradeMe_Model_Config::TIMEZONE);

    return Mage::getResourceSingleton('trademe/auction')->getListedProducts(
      $type,
      DateTime::createFromFormat('H', '00', $tz)
        ->setTimezone($defTz)
        ->format(Varien_Date::DATETIME_PHP_FORMAT),
      DateTime::createFromFormat('H:i:s', '23:59:59', $tz)
        ->setTimezone($defTz)
        ->format(Varien_Date::DATETIME_PHP_FORMAT)
    );
  }

  /**
   * Return title of auction choosen randomly from the name variants or return
   * product's name if name variants are missing.
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return string
   *   Auction title
   */
  public function getTitle ($product, $store) {
    $titles = Mage::helper('trademe/product')->getNameVariants(
      $product,
      $store
    );

    return $titles ? $titles[array_rand($titles)] : $product->getName();
  }

  /**
   * Unlink all sandbox auctions for specified accounts
   *
   * @param array $accounts
   *   List of account which aucitons will be unlinked. If empty or omitted
   *   then unlink auctions for all accounts
   *
   * @return MVentory_TradeMe_Helper_Auction
   *   Instance of this class
   */
  public function unlinkAll ($accounts = null) {
    $allowed = array();

    foreach (Mage::app()->getWebsites(false, true) as $code => $website)
      if ($website->getConfig(MVentory_TradeMe_Model_Config::SANDBOX))
        $allowed += array_keys($this->getAccounts($website));

    if (!$allowed)
      return $this;

    $accounts = $accounts ? array_intersect($accounts, $allowed) : $allowed;

    Mage::getResourceModel('trademe/auction_collection')
      ->addFieldToFilter('account_id', array('in' => $accounts))
      ->walk('delete');

    return $this;
  }

  /**
   * Calculate final price for auction
   *
   * @todo Allow to calculate "partial" price, e.g. only product final price +
   *   TradeMe shipping rate or only product final price + currency conversion.
   *   It should be specified via optional parameter - array with parameters
   *
   *   Example:
   *     $price = $helper->getPrice(
   *       $product,
   *       $account,
   *       $store,
   *       [
   *         'product_price' => true,
   *         'shipping_rate' => true
   *       ]
   *     );
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param array $account
   *   Account data
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return float
   *   Final price for TradeMe auction
   */
  public function getPrice ($product, $account, $store) {
    $productHelper = Mage::helper('trademe/product');
    $website = $store->getWebsite();

    $price = $productHelper->getPrice($product, $website);
    $price += $this->getShippingRate($product, $account['name'], $website);

    $currency = $store->getBaseCurrency();

    /**
     * @todo Replace with MVentory_TradeMe_Helper_Data::applyTmCurrency()
     *   helper method after it'll have been implemented.
     *
     * @see MVentory_TradeMe_Helper_Data::currencyConvert()
     *   See description for the method to find more info
     */
    if ($currency->getCode != MVentory_TradeMe_Model_Config::CURRENCY)
      $price = $this->currencyConvert(
        $price,
        $currency,
        MVentory_TradeMe_Model_Config::CURRENCY,
        $store
      );

    $hasSpecialPrice = $productHelper->hasSpecialPrice($product, $store);

    return $this->_hasAddFees($account, $hasSpecialPrice)
             ? $this->addFees($price)
             : $price;
  }

  /**
   * Determine and return sale status of auction
   *
   * Statuses:
   *
   *   1 - Auction wasn't sold or was withdrawn
   *   2 - Auction was sold
   *   3 - Auction is active
   *
   * @param array $listingDetails
   *   Auction detais retrieved from TradeMe
   *
   * @return int
   *   Sale status of the auction
   */
  public function getSaleStatus ($listingDetails) {
    //Auction is active
    if ($listingDetails['AsAt'] < $listingDetails['EndDate'])
      return 3;

    //Auction was sold
    if ($listingDetails['BidCount'] > 0)
      return 2;

    //Auction wasn't sold or was withdrawn
    return 1;
  }

  /**
   * Chec if multiple full-price auctions are enabled
   *
   * @param Mage_Core_Model_Store $store
   *   Store model. Used to get value of Auction end time setting
   *
   * @return boolean
   *   True if multiple full-price auctions are enabled, otherwise false
   */
  public function isMultipleAuctionsEnabled ($store) {
    return (bool) $store->getConfig(
      MVentory_TradeMe_Model_Config::_AUC_MULT_PER_NAME
    );
  }

  /**
   * Check if $1 reserve auctions are exclusive, i.e. concurrent full-price
   * auctions are disabled
   *
   * @return boolean
   *   True if $1 reserve auctions are exclusive, otherwise false
   */
  public function isDollarAuctionExclusive () {
    $mode = (int) Mage::getStoreConfig(
      MVentory_TradeMe_Model_Config::_1AUC_FULL_PRICE
    );

    return $mode == MVentory_TradeMe_Model_Config::AUCTION_NORMAL_NEVER;
  }

  /**
   * Return enabled promotions
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   List of enabled promotions
   */
  public function getPromotions ($store) {
    $promotions = $store->getConfig(MVentory_TradeMe_Model_Config::_PROMOTIONS);
    if (!$promotions)
      return [];

    $promotions = explode(',', $promotions);
    if (!$promotions)
      return [];

    $promotions = array_flip($promotions);

    if (isset($promotions[MVentory_TradeMe_Model_Config::PROMOTION_NO]))
      return [];

    if (isset($promotions[MVentory_TradeMe_Model_Config::PROMOTION_SUPER]))
      return [MVentory_TradeMe_Model_Config::PROMOTION_SUPER => true];

    return $promotions;
  }

  /**
   * Return period of time when listing of $1 auction is allowed using value
   * of Auction end time setting for the supplied store (it adds +/-30 mins
   * to that value)
   *
   * @param DateTimeZone $tz
   *   Timezone
   *
   * @param Mage_Core_Model_Store $store
   *   Store model. Used to get value of Auction end time setting
   *
   * @return array|null
   *   Period of time when listing of $1 auction is allowed
   */
  protected function _getEndTimePeriod ($tz, $store) {
    $endTime = $store->getConfig(MVentory_TradeMe_Model_Config::_1AUC_ENDTIME);

    if (!$endTime)
      return;

    $endTime = DateTime::createFromFormat('H:i', $endTime, $tz);

    $int = new DateInterval('PT30M');
    $start = clone $endTime;

    return array($start->sub($int), $endTime->add($int));
  }

  /**
   * Check if current day of week (retrived from the supplied time value)
   * is in the list of allowed days for listing $1 auction
   *
   * @param DateTime $time
   *   Time value
   *
   * @param Mage_Core_Model_Store $store
   *   Store model. Used to get various store specific settings for $1 auction
   *
   * @return boolean
   *   Result fo check
   */
  protected function _isAuctionDay ($time, $store) {
    $endDays = $store->getConfig(MVentory_TradeMe_Model_Config::_1AUC_ENDDAYS);

    if ($endDays === '')
      return false;

    $duration = $store->getConfig(
      MVentory_TradeMe_Model_Config::_1AUC_DURATION
    );

    if ($duration === '')
      return false;

    $endDays = explode(',', $endDays);
    $duration = (int) $duration;

    $dow = (int) $time->format('w');

    foreach ($endDays as $endDay) {
      if (($day = $endDay - $duration) < 0)
        $day = 7 + $day;

      if ($dow == $day)
        return true;
    }

    return false;
  }

  /**
   * Check if TradeMe fees should be added
   *
   * @param array $auction
   *   Account data
   *
   * @param boolean $hasSpecialPrice
   *   Flag shows if product has active special price
   *
   * @return boolean
   *   Result of the check
   */
  protected function _hasAddFees ($auction, $hasSpecialPrice) {
    if (!isset($auction['add_fees']))
      return false;

    if ($auction['add_fees'] == MVentory_TradeMe_Model_Config::FEES_ALWAYS)
      return true;

    return $auction['add_fees'] == MVentory_TradeMe_Model_Config::FEES_SPECIAL
             ? $hasSpecialPrice
             : false;
  }
}
