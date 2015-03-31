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
   * Return title of auction choosen randomly from the set of name variants and
   * product's name
   *
   * @param Mage_Catalog_Model_Product $product Product
   * @param Mage_Core_Model_Store $store Store
   * @return string Auctin title
   */
  public function getTitle ($product, $store) {
    $title = $product->getName();

    $code = trim(
      $store->getConfig(MVentory_TradeMe_Model_Config::_NAME_VARIANTS_ATTR)
    );

    if (!$code)
      return $title;

    if (!$_titles = trim($product[strtolower($code)]))
      return $title;

    $_titles = explode("\n", str_replace("\r\n", "\n", $_titles));

    foreach ($_titles as $_title)
      if ($_title = trim($_title))
        $titles[] = $_title;

    if (!isset($titles))
      return $title;

    $titles[] = $title;

    return $titles[array_rand($titles)];
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
        $allowed += array_keys($this->getAccounts($website, false));

    if (!$allowed)
      return $this;

    $accounts = $accounts ? array_intersect($accounts, $allowed) : $allowed;

    Mage::getResourceModel('trademe/auction_collection')
      ->addFieldToFilter('account_id', array('in' => $accounts))
      ->walk('delete');

    return $this;
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
}
