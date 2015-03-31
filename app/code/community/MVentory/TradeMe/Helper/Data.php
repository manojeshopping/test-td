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
 * Image helper
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Helper_Data extends Mage_Core_Helper_Abstract
{
  const COUNTRY_CODE = 'NZ';

  const LISTING_DURATION_MAX = 7;
  const LISTING_DURATION_MIN = 2;

  //TradeMe fees description
  //Available fields:
  // * from - Min product price for the fee (value is included in comparision)
  // * to - Max product price for the fee (value is included in comparision)
  // * rate - Percents
  // * fixed - Fixed part of fee
  // * min - Min fee value
  // * max - Max fee value
  private $_fees = array(
    //Up to $200 : 7.9% of sale price (50c minimum)
    array(
      'from' => 0,
      'to' => 200,
      'rate' => 0.079,
      'fixed' => 0,
      'min' => 0.5
    ),

    //$200 - $1500 : $15.80 + 4.9% of sale price over $200
    array(
      'from' => 200,
      'to' => 1500,
      'rate' => 0.049,
      'fixed' => 15.8,
    ),

    //Over $1500 : $79.50 + 1.9% of sale price over $1500 (max fee = $149)
    array(
      'from' => 1500,
      'rate' => 0.019,
      'fixed' => 79.5,
      'max' => 149
    ),
  );

  protected $_fields = array(
    'shipping_type' => 'tm_shipping_type',
    'allow_buy_now' => 'tm_allow_buy_now',
    'add_fees' => 'tm_add_fees',
    'avoid_withdrawal' => 'tm_avoid_withdrawal',
  );

  protected $_fieldsWithoutDefaults = array(
    'relist' => 'tm_relist',
    'pickup' => 'tm_pickup'
  );

  public function getAttributes ($categoryId) {
    $model = new MVentory_TradeMe_Model_Api();

    $attrs = $model->getCategoryAttrs($categoryId);

    if (!(is_array($attrs) && count($attrs)))
      return null;

    foreach ($attrs as &$attr)
      $attr['Type'] = $model->getAttrTypeName($attr['Type']);

    return $attrs;
  }

  /**
   * Return product's final price.
   *
   * Set data for calculation of catalog price rule as required in
   * @see Mage_CatalogRule_Model_Observer::processAdminFinalPrice()
   *
   * @param Mage_Catalog_Model_Product $product
   * @param Mage_Core_Model_Website $website Product's website
   * @return float
   */
  public function getProductPrice ($product, $website) {
    Mage::register(
      'rule_data',
      new Varien_Object(array(
        'website_id' => $website->getId(),
        'customer_group_id' => Mage_Customer_Model_Group::NOT_LOGGED_IN_ID
      )),
      true
    );

    $price = $product->getFinalPrice();

    Mage::unregister('rule_data');

    return $price;
  }

  /**
   * Calculate TradeMe fees for the product
   *
   * @param float $price
   * @return float Calculated fees
   */
  public function calculateFees ($price) {

    //What we need to calculate here is the price to be used
    //for TradeMe listing. The value of that price after subtracting TradeMe
    //fees must be equal to the sell price from magento
    //(which is the price passed as an argument to this function)

    //This should never happen
    if ($price < 0)
      return 0;

    //First we need to figure out in which range the final TradeMe listing price
    //is going to be
    foreach ($this->_fees as $_fee) {

      //If a range doesn't have the upper bound then we are in the last range
      //so no need to check anything and we are just gonna use that range
      if (isset($_fee['to'])) {

        //Max fee value we can add to the price not to exceed the from..to range
        $maxFee = $_fee['to'] - $price;

        //If cannot add any fees then we are in the wrong range
        if ($maxFee < 0)
          continue;

        //Fee for the maximum price that still fits in the current range
        $feeForTheMaxPrice = $_fee['fixed']
                             + (($_fee['to']) - $_fee['from'])
                             * $_fee['rate'];

        //If the maximum fees we can apply not to exceed the from..to range
        //are still not enough then we are in the wrong range
        if ($maxFee < $feeForTheMaxPrice)
          continue;
      }

      //Calculate the fee for the range selected
      $fee = ($_fee['fixed'] + ($price - $_fee['from']) * $_fee['rate'])
             / (1 - $_fee['rate']);

      //Take into account the min and max values. */
      if (isset($_fee['min']) && $fee < $_fee['min'])
        $fee = $_fee['min'];

      if (isset($_fee['max']) && $fee > $_fee['max'])
        $fee = $_fee['max'];

      return $fee;
    }

    //This should never happen
    return 0;
  }

  /**
   * Add TradeMe fees to the product's price and format it
   *
   * @param float $price
   * @return float Product's price with calculated fees
   */
  public function addFees ($price) {
    return round($price + $this->calculateFees($price), 2);
  }

  /**
   * Calculates shippign rate for product by region ID.
   * Uses 'tablerate' carrier (Table rates shipping method)
   *
   * @param Mage_Catalog_Model_Product $product
   * @param string $regionName
   * @param int|string|Mage_Core_Model_Website $website Website, its ID or code
   * @return float Calculated shipping rate
   */
  public function getShippingRate ($product, $regionName, $website) {
    $destRegion = Mage::getModel('directory/region')
      ->loadByName($regionName, self::COUNTRY_CODE);

    if (!$destRegionId = $destRegion->getId())
      return;

    $store = $website->getDefaultStore();

    $path = Mage_Shipping_Model_Shipping::XML_PATH_STORE_REGION_ID;

    //Ignore when destionation region similar to origin one
    if (($origRegionId = Mage::getStoreConfig($path, $store)) == $destRegionId)
      return;

    $request = Mage::getModel('shipping/rate_request')
      ->setCountryId(self::COUNTRY_CODE)
      ->setRegionId($origRegionId)
      ->setDestCountryId(self::COUNTRY_CODE)
      ->setDestRegionId($destRegionId)
      ->setStoreId($store->getId())
      ->setStore($store)
      ->setWebsiteId($website->getId())
      ->setBaseCurrency($website->getBaseCurrency())
      ->setPackageCurrency($store->getCurrentCurrency())
      ->setAllItems(array($product))

      //We're using only volume carrier
      ->setLimitCarrier('volumerate')

      //Show that we have origin country and region in the request
      ->setOrig(true);

    $result = Mage::getModel('shipping/shipping')
      ->collectRates($request)
      ->getResult();

    if (!$result)
      return;

    //Expect only one rate
    if (!$rate = $result->getRateById(0))
      return;

    return $rate->getPrice();
  }

  /**
   * Retrieve array of accounts for specified website. Returns accounts from
   * default scope when parameter is omitted
   *
   * @param mixin $websiteId
   * @return array List of accounts
   */
  public function getAccounts ($website, $withRandom = true) {
    $website = Mage::app()->getWebsite($website);

    $configData = Mage::getModel('adminhtml/config_data')
      ->setWebsite($website->getCode())
      ->setSection('trademe');

    $configData->load();

    $groups = $configData->getConfigDataValue('trademe')->asArray();

    $accounts = array();

    if ($withRandom)
      $accounts[null] = array('name' => $this->__('Random'));

    foreach ($groups as $id => $fields)
      if (strpos($id, 'account_', 0) === 0) {
        if (isset($fields['shipping_types']))
          $fields['shipping_types']
            = (($types = unserialize($fields['shipping_types'])) === false)
                 ? array()
                   : $types;

        $accounts[$id] = $fields;
      }

    return $accounts;
  }

  /**
   * Prepare accounts for the supplied product.
   * Leave TradeMe options for product's shipping type only
   *
   * @see MVentory_TradeMe_Helper_Data::prepareAccount()
   *
   * @param array $accounts
   *   TradeMe accounts data
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param Mage_Core_Model_Store $store
   *   Store model or null for current store
   *
   * @return array
   *   Prepared accounts data only for the product's shipping type
   */
  public function prepareAccounts ($accounts, $product, $store = null) {
    foreach ($accounts as $id => $account) {
      if (!isset($account['shipping_types']))
        continue;

      $account = $this->prepareAccount($account, $product, $store);

      if (!$account) {
        unset($accounts[$id]);
        continue;
      }

      $accounts[$id] = $account;
    }

    return $accounts;
  }

  /**
   * Prepare account for the supplied product.
   * Leave TradeMe options for product's shipping type and weight only
   *
   * @param array $account
   *   TradeMe account data
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param Mage_Core_Model_Store $store
   *   Store model or null for current store
   *
   * @return array|boolean
   *   Prepared account data only for the product's shipping type and weight
   *   or false if account doesn't match product's parameters
   */
  public function prepareAccount ($account, $product, $store = null) {
    $type = $this->getShippingType($product, true, $store);
    $weight = $product->getWeight();

    foreach ($account['shipping_types'] as $settings) {
      $_type = $settings['shipping_type'];
      $_weight = $settings['weight'];

      $cond = ($_type == '*' || $_type == $type)
              && ($_weight === '' || (float) $weight <= (float) $_weight);

      if ($cond) {
        unset($account['shipping_types']);

        return $account + $settings;
      }
    }

    return false;
  }

  /**
   * Remove account
   *
   * @param string $id Account ID
   * @param Mage_Core_Model_Website $website Website
   * @return bool
   */
  public function removeAccount ($id, $website) {
    $accounts = $this->getAccounts($website, false);

    if (!isset($accounts[$id]))
      return;

    $config = Mage::getConfig();

    foreach (array_keys($accounts[$id]) as $setting)
      $config->deleteConfig(
        'trademe/' . $id . '/' . $setting,
        'websites',
        $website->getId()
      );

    $config->reinit();

    Mage::app()->reinitStores();

    return true;
  }

  /**
   * Extracts data for TradeMe options from product or from optional
   * account data if the product doesn't have attribute values
   *
   * @param Mage_Catalog_Model_Product|array $product Product's data
   * @param array $account TradeMe account data
   *
   * @return array TradeMe options
   */
  public function getFields ($product, $account = null) {
    if ($product instanceof Mage_Catalog_Model_Product)
      $product = $product->getData();

    return $this->_getFields(
      $product,
      $account,
      $this->_fields,
      $this->_fieldsWithoutDefaults
    );
  }

  /**
   * Extract data from form data or from optional account data
   * if the form data doesn't have field values
   *
   * @param array $formData Form data
   * @param array $account Auction account data
   * @return array List of fields and their values
   */
  public function getFormFields ($formData, $account = null) {
    return $this->_getFields(
      $formData,
      $account,
      $this->_fields,
      $this->_fieldsWithoutDefaults + array('account_id' => 'tm_account_id')
    );
  }

  /**
   * Return values for all fields from input values (or from default values),
   * for all fields without default values returns only from input values
   *
   * @param array $in Input values
   * @param array $def Default values
   * @param array $flds List of fields
   * @param array $_flds List of fields without default values
   * @return array List of fields with values
   */
  protected function _getFields ($in, $def, $flds, $_flds) {
    $out = array();

    //Process fields
    foreach ($flds as $name => $code) {
      $val = isset($in[$code]) ? $in[$code] : null;

      if (!($def && ($val == '-1' || $val === null)))
        $out[$name] = $val;
      else
        $out[$name] = isset($def[$name]) ? $def[$name] : null;
    }

    //Process fields without default values
    foreach ($_flds as $name => $code)
      $out[$name] = isset($in[$code]) ? $in[$code] : null;

    return $out;
  }

  /**
   * Sets TradeMe options in product
   *
   * @param Mage_Catalog_Model_Product|array $product Product
   * @param array $fields Trademe options data
   *
   * @return MVentory_TradeMe_Helper_Data
   */
  public function setFields ($product, $fields) {
    $_fields = $this->_fields + $this->_fieldsWithoutDefaults;

    foreach ($_fields as $name => $code)
      if (isset($fields[$name]))
        $product->setData($code, $fields[$name]);

    return $this;
  }

  public function fillAttributes ($product, $attrs, $store) {
    $storeId = $store->getId();

    foreach ($attrs as $attr)
      $_attrs[strtolower($attr['Name'])] = $attr;

    unset($attrs);

    foreach ($product->getAttributes() as $code => $pAttr) {
      $input = $pAttr->getFrontendInput();

      if (!($input == 'select' || $input == 'multiselect'))
        continue;

      $frontend = $pAttr->getFrontend();

      $defaultValue = $frontend->getValue($product);
      $attributeStoreId = $pAttr->getStoreId();

      $pAttr->setStoreId($storeId);
      $value = $frontend->getValue($product);
      $pAttr->setStoreId($attributeStoreId);

      if ($defaultValue == $value)
        continue;

      $value = trim($value);

      if (!$value)
        continue;

      $parts = explode(':', $value, 2);

      if (!(count($parts) == 2 && $parts[0]))
        return array(
          'error' => true,
          'no_match' => $code
        );

      $name = strtolower(rtrim($parts[0]));
      $value = ltrim($parts[1]);

      if (!isset($_attrs[$name]))
        continue;

      $attr = $_attrs[$name];

      $value = trim($value);

      if (!$value && $attr['IsRequiredForSell'])
        return array(
          'error' => true,
          'required' => $attr['DisplayName']
        );

      $result[$attr['Name']] = $value;
    }

    return array(
      'error' => false,
      'attributes' => isset($result) ? $result : null
    );
  }

  public function getMappingStore () {
    return Mage::app()->getStore(
      (int) Mage::helper('mventory')->getConfig(
        MVentory_TradeMe_Model_Config::MAPPING_STORE
      )
    );
  }

  /**
   * Returns duration of TradeMe listing limited by MIN and MAX values
   * By default returns MAX duration value
   *
   * @param array $data Account data
   * @return int duration
   */
  public function getDuration ($data) {
    if (!(isset($data['duration'])
          && $duration = (int) $data['duration']))
      return self::LISTING_DURATION_MAX;

    if ($duration < self::LISTING_DURATION_MIN)
      return self::LISTING_DURATION_MIN;

    if ($duration > self::LISTING_DURATION_MAX)
      return self::LISTING_DURATION_MAX;

    return $duration;
  }

  /**
   * Return minimal price level for TradeMe listing
   *
   * @param array $data
   *   TradeMe account data per shipping
   *
   * @return float
   *   Minimal price level from the supplied account data
   *   or zero if it doesn't exists in the data
   */
  public function getMinimalPrice ($data) {
    return isset($data['minimal_price']) ? (float) $data['minimal_price'] : 0;
  }

  /**
   * Return buyer (pre-configured customer for TradeMe sells) from supplied
   * TradeMe account data and store
   *
   * @param array $account
   *   TradeMe account data
   *
   * @param [type] $store
   *   Store model to use for loading customer model
   *
   * @return Mage_Customer_Model_Customer|null
   *   Customer model or null if it can't be retrieved from the supplied account
   *   data or model doesn't exist
   */
  public function getBuyer ($data, $store) {
    if (!(isset($data['buyer']) && ($buyer = (int) $data['buyer'])))
      return;

    $buyer = Mage::getModel('customer/customer')
      ->setStore($store)
      ->load($buyer);

    return $buyer->getId() ? $buyer : null;
  }

  /**
   * Upload TradeMe optons file and import data from it
   *
   * @param Varien_Object $object
   * @throws Mage_Core_Exception
   */
  public function importOptions ($data) {
    $scopeId = $data->getScopeId();
    $website = Mage::app()->getWebsite($scopeId);

    $shippingTypes =$this->getShippingTypes($website->getDefaultStore());

    if (!$shippingTypes)
      Mage::throwException($this->__('There\'re no available shipping types'));

    $accounts = $this->getAccounts($website, false);

    if (!$accounts)
      Mage::throwException($this->__('There\'re no accounts in this website'));

    foreach ($accounts as $id => $account)
      $accountMap[strtolower($account['name'])] = $id;

    /**
     * Map of shipping type labels to shipping type IDs
     * There's 2 special values:
     *   - * - means any shipping type in product
     *   - <empty> - means no shipping type is set in product
     *
     * @var array
     */
    $shippingTypeMap = array(
      '*' => '*',
      '' => ''
    );

    foreach ($shippingTypes as $id => $label)
      $shippingTypeMap[strtolower(trim($label))] = $id;

    $groupId = $data->getGroupId();
    $field = $data->getField();

    if (empty($_FILES['groups']
                     ['tmp_name']
                     [$groupId]
                     ['fields']
                     [$field]
                     ['value']))
      return;

    $file = $_FILES['groups']['tmp_name'][$groupId]['fields'][$field]['value'];

    $info = pathinfo($file);

    $io = new Varien_Io_File();

    $io->open(array('path' => $info['dirname']));
    $io->streamOpen($info['basename'], 'r');

    //Check and skip headers
    $headers = $io->streamReadCsv();

    if ($headers === false || count($headers) < 14) {
      $io->streamClose();

      Mage::throwException(
        $this->__('Invalid TradeMe options file format')
      );
    }

    $rowNumber = 1;
    $data = array();

    $params = array(
      'account' => $accountMap,
      'type' => $shippingTypeMap,
      'hash' => array(),
      'errors' => array()
    );

    try {
      while (false !== ($line = $io->streamReadCsv())) {
        $rowNumber ++;

        if (empty($line))
          continue;

        $row = $this->_getImportRow($line, $rowNumber, $params);

        if ($row !== false)
          $data[] = $row;
      }

      $io->streamClose();

      if ($data) {
        $data = $this->_restructureImportedOptions($data);
        $this->_saveImportedOptions($data, $website);

        $missingSettings = $this->_checkMissingSettings($data, $shippingTypes);
      } else
        $params['errors'][] = $this->__('No options in the file');

    } catch (Mage_Core_Exception $e) {
      $io->streamClose();

      Mage::throwException($e->getMessage());
    } catch (Exception $e) {
      $io->streamClose();

      Mage::logException($e);

      $params['errors'][] = $this->__(
        'An error occurred while processing file. See logs for more info'
      );
    }

    if ($params['errors']) {
      $newLine = '<br />&nbsp;&nbsp;&nbsp;&nbsp;*&nbsp;';
      $isSingle = count($params['errors']) == 1;

      $error = $this->__($isSingle ? 'Error' : 'Errors') . ':';
      $error .= $isSingle
                  ? ' ' . $params['errors'][0]
                    : $newLine . implode($newLine, $params['errors']);

      Mage::throwException(
        $this->__('File has not been imported') . '<br />' . $error
      );
    }

    if ($missingSettings) {
      foreach ($missingSettings as $accountId => $shippingTypes) {
        $name = $accounts[$accountId]['name'];

        foreach ($shippingTypes as $type)
          $msgs[] = $this->__(
            'Shipping type %s not configured for account %s.',
            $type,
            $name
          );
      }

      if (isset($msgs))
        Mage::getSingleton('adminhtml/session')->addWarning(
          implode('<br />', $msgs)
        );
    }
  }

  /**
   * Validate row for import and return options array or false
   *
   * @param array $row
   * @param int $rowNumber
   *
   * @return array|false
   */
  protected function _getImportRow ($row, $rowNumber = 0, &$params) {

    //Validate row
    if (count($row) < 14) {
      $msg = 'Invalid TradeMe options format in row %s';
      $params['errors'][] = $this->__($msg, $rowNumber);

      return false;
    }

    //Strip whitespace from the beginning and end of each column
    foreach ($row as $k => $v)
      $row[$k] = trim($v);

    $account = strtolower($row[0]);

    if (!isset($params['account'][$account])) {
      $msg = 'Invalid account ("%s") in row %s.';
      $params['errors'][] = $this->__($msg, $row[0], $rowNumber);

      return false;
    }

    $account = $params['account'][$account];

    $shippingType = strtolower($row[1]);

    if (!isset($params['type'][$shippingType])) {
      $msg = 'Invalid shipping type ("%s") in row %s.';
      $params['errors'][] = $this->__($msg, $row[1], $rowNumber);

      return false;
    }

    $shippingType = $params['type'][$shippingType];

    //Validate maximum weight
    $weight = $this->_parseWeight($row[2]);

    if ($weight === false) {
      $msg = 'Invalid Maximum weight value ("%s") in row %s.';
      $params['errors'][] = $this->__($msg, $row[2], $rowNumber);

      return false;
    }

    //Validate minimal price
    $minimalPrice = $this->_parsePrice($row[3]);

    if ($minimalPrice === false) {
      $msg = 'Invalid Minimal price value ("%s") in row %s.';
      $params['errors'][] = $this->__($msg, $row[3], $rowNumber);

      return false;
    }

    $freeShippingCost = $this->_parseDecimalValue($row[4]);

    if ($freeShippingCost === false) {
      $msg = 'Invalid Free shipping cost value ("%s") in row %s.';
      $params['errors'][] = $this->__($msg, $row[4], $rowNumber);

      return false;
    }

    $addFees = $this->_parseAddfeesValue($row[7]);

    if ($addFees === false) {
      $msg = 'Invalid Add fees value ("%s") in row %s.';
      $params['errors'][] = $this->__($msg, $row[7], $rowNumber);

      return false;
    }

    //Validate listing duration value
    $listingDuaration = (int) $row[11];

    //!!!TODO: use getDuration() method
    if (!$listingDuaration || $listingDuaration > self::LISTING_DURATION_MAX)
      $listingDuaration = self::LISTING_DURATION_MAX;
    else if ($listingDuaration < self::LISTING_DURATION_MIN)
      $listingDuaration = self::LISTING_DURATION_MIN;

    //Protect from duplicate
    $hash = sprintf('%s-%s-%s', $account, $shippingType, $weight);

    if (isset($params['hash'][$hash])) {
      $msg = 'Duplicate row %s.';

      $params['errors'][] = $this->__($msg, $rowNumber);

      return false;
    }

    $params['hash'][$hash] = true;

    return array(
      'account' => $account,
      'shipping_type' => $shippingType,
      'weight' => $weight,
      'minimal_price' => $minimalPrice,
      'free_shipping_cost' => $freeShippingCost,
      'allow_buy_now' => (bool) $row[5],
      'avoid_withdrawal' => (bool) $row[6],
      'add_fees' => $addFees,
      'allow_pickup' => (bool) $row[8],
      'category_image' => (bool) $row[9],
      'buyer' => (int) $row[10],
      'duration' => $listingDuaration,
      'shipping_options' => $this->_parseShippingOptionsValue($row[12]),
      'footer' => $row[13]
    );
  }

  /**
   * Parse and validate positive decimal value
   * Return false if value is not decimal or is not positive
   *
   * @param string $value
   * @return bool|float
   */
  protected function _parseDecimalValue ($value) {
    if (!is_numeric($value))
      return false;

    $value = (float) sprintf('%.2F', $value);

    if ($value < 0.0000)
      return false;

    return $value;
  }

  /**
   * Parse weight option from TradeMe config file
   *
   * @param string $value
   *   Raw value of the weight option
   *
   * @return string|float|false
   *   Returns false value if passed raw values is not numeric string,
   *   otherwise return float number or empty string for empty weight option
   */
  protected function _parseWeight ($value) {
    if ($value === '')
      return $value;

    return is_numeric($value) ? (float) $value : false;
  }

  /**
   * Parse price option from TradeMe config file
   *
   * @param string $value
   *   Raw value of the price option
   *
   * @return string|float|false
   *   Returns false value if passed raw values is not numeric string
   *   or value is negative, otherwise return float number or empty string
   *   for empty price option
   */
  protected function _parsePrice ($value) {
    if ($value === '')
      return $value;

    if (!is_numeric($value))
      return false;

    return (($value = (float) sprintf('%.2F', $value)) >= 0) ? $value : false;
  }

  protected function  _parseAddfeesValue ($value) {
    if (!$value = trim($value))
      return MVentory_TradeMe_Model_Config::FEES_NO;

    if (strlen($value) == 1) {
      $value = (int) $value;

      $values = Mage::getModel('trademe/attribute_source_addfees')
        ->toOptionArray();

      return isset($values[$value])
               ? $value
                 : MVentory_TradeMe_Model_Config::FEES_NO;
    }

    $value = strtolower($value);

    if (stripos($value, 'always') !== false)
      return MVentory_TradeMe_Model_Config::FEES_ALWAYS;

    if (stripos($value, 'special') !== false)
      return MVentory_TradeMe_Model_Config::FEES_SPECIAL;

    return MVentory_TradeMe_Model_Config::FEES_NO;
  }

  /**
   * Parse string with shipping options in following format
   *
   *   <price>,<method>\r\n
   *   ...
   *   <price>,<method>
   *
   * to a list with shipping options in following format
   *
   *   array(
   *     array(
   *       'price' => 12.5,
   *       'method' => 'Name of shipping method'
   *     ),
   *     ...
   *   )
   *
   * @param string $value Shipping options
   * @return array
   */
  protected function _parseShippingOptionsValue ($value) {
    $options = array();

    if (!$value = trim($value))
      return $options;

    foreach (explode("\n", str_replace("\r\n", "\n", $value)) as $opt)
      if (count($opt = explode(',', trim($opt, " ,\t\n\r\0\x0B"), 2)) == 2)
        $options[] = array(
          'price' => (float) rtrim($opt[0]),
          'method' => ltrim($opt[1])
        );

    return $options;
  }

  /**
   * Save parsed options in Magento config
   *
   * @param string $value
   * @return bool|float
   */
  protected function _saveImportedOptions ($accounts, $website) {
    $websiteId = $website->getId();
    $config = Mage::getConfig();

    foreach ($accounts as $id => $data)
      $config->saveConfig(
        'trademe/' . $id . '/shipping_types',
        serialize($data),
        'websites',
        $websiteId
      );

    $config->reinit();

    Mage::app()->reinitStores();
  }

  protected function _restructureImportedOptions ($data) {
    $accounts = array();

    foreach ($data as $options) {
      $accountId = $options['account'];
      unset($options['account']);

      $accounts[$accountId][] = $options;
    }

    return $accounts;
  }

  protected function _checkMissingSettings ($accounts, $shippingTypes) {
    $result = array();

    foreach ($accounts as $id => $data) {
      foreach ($data as $settings) {
        if ($settings['shipping_type'] == '*')
          continue 2;

        if ($settings['shipping_type'] == '')
          continue;

        $types[$settings['shipping_type']] = true;
      }

      if (isset($types) && ($missing = array_diff_key($shippingTypes, $types)))
        $result[$id] = $missing;
    }

    return $result;
  }

  public function isSandboxMode ($websiteId) {
    $path = MVentory_TradeMe_Model_Config::SANDBOX;

    return Mage::helper('mventory')->getConfig($path, $websiteId) == true;
  }

  /**
   * Change store and environment if store passed or restored original
   * environment if environment object is passed
   *
   * @param Mage_Core_Model_Store|Varien_Object $storeOrEnv
   *   Store or original environment
   *
   * @return Varien_Object|null
   *   Original environment or nothing
   */
  public function changeStore ($storeOrEnv) {
    $emu = Mage::getModel('core/app_emulation');

    if ($storeOrEnv instanceof Mage_Core_Model_Store)
      return $emu->startEnvironmentEmulation($storeOrEnv->getId());

    $emu->stopEnvironmentEmulation($storeOrEnv);
  }

  /**
   * Return code of shipping type attribute for the supplied store
   *
   * @param Mage_Core_Model_Store $store
   *   Store model or null for current store
   * @return string
   *   Code of shipping type attribute that is set in settings
   */
  public function getShippingAttr ($store = null) {
    if (!$store instanceof Mage_Core_Model_Store)
      $store = Mage::app()->getStore($store);

    return strtolower(trim(
      $store->getConfig(MVentory_TradeMe_Model_Config::_SHIPPING_ATTR)
    ));
  }

  /**
   * Return values of shipping type attribute for the supplied store
   *
   * @param Mage_Core_Model_Store $store
   *   Store model or null for current store
   *
   * @return array
   *   List of shipping types or empty array if shipping type attribute can't
   *   be loaded
   */
  public function getShippingTypes ($store = null) {
    if (!$store instanceof Mage_Core_Model_Store)
      $store = Mage::app()->getStore($store);

    $code = $this->getShippingAttr($store);

    if (!$code)
      return array();

    $attribute = Mage::getResourceModel('catalog/eav_attribute')->loadByCode(
      Mage_Catalog_Model_Product::ENTITY,
      $code
    );

    if (!$attribute->getId())
      return array();

    $options = $attribute
      ->setStoreId($store->getId())
      ->getSource()
      ->getAllOptions(false);

    foreach ($options as $option)
      $types[$option['value']] = $option['label'];

    return $types;
  }

  /**
   * Return value of shipping type attribute from the supplied product
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param boolean $rawValue
   *   Return raw value if false otherwise return option label
   *
   * @param Mage_Core_Model_Store $store
   *   Store model or null for current store
   *
   * @return int|string|null
   *   Raw value of the attribute or option label
   */
  public function getShippingType ($product, $rawValue = false, $store = null) {
    if (!$store instanceof Mage_Core_Model_Store)
      $store = Mage::app()->getStore($store);

    if (!$code = $this->getShippingAttr($store))
      return null;

    if ($rawValue)
      return $product->getData($code);

    $attributes = $product->getAttributes();

    return isset($attributes[$code])
             ? $attributes[$code]
                 ->setStoreId($store->getId())
                 ->getFrontend()
                 ->getValue($product)
             : null;
  }

  /**
   * Convert supplied price from one currency to another
   *
   * @param float $amount
   *   Price to convert
   *
   * @param string|null|Mage_Directory_Model_Currency $from
   *   Currency to convert from. Store's default currency is used if null
   *   is specified
   *
   * @param string|null|Mage_Directory_Model_Currency $to
   *   Currency to convert to. Store's default currency is used if null
   *   is specified
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return float
   *   Converted price or returns the supplied price if some currency doesn't
   *   exist or can't convert it (e.g no currency rate)
   */
  public function currencyConvert ($amount, $from, $to, $store) {
    if ($from === null)
      $from = $store->getBaseCurrency();
    else if (is_string($from)) {
      $from = Mage::getModel('directory/currency')->load($from);

      if (!$from->getId())
        return $amount;
    }

    if ($to === null)
      $to = $store->getBaseCurrency();
    else if (is_string($to)) {
      $to = Mage::getModel('directory/currency')->load($to);

      if (!$to->getId())
        return $amount;
    }

    try {
      return $from->convert($amount, $to);
    } catch (Exception $e) {
      return $amount;
    }
  }
}
