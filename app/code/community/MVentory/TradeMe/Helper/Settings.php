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
 * Settings import/export helper
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

class MVentory_TradeMe_Helper_Settings extends MVentory_TradeMe_Helper_Data
{
  const NUM_OF_COLUMNS = 14;

  /**
   * Column numbers
   */
  const COL_ACCOUNT = 0;
  const COL_SHIPPING_TYPE = 1;
  const COL_WEIGHT = 2;
  const COL_PRICE = 3;
  const COL_FREE_SHIPPING_COST = 4;
  const COL_ALLOW_BUY_NOW = 5;
  const COL_AVOID_WITHDRAWAL = 6;
  const COL_ADD_FEES = 7;
  const COL_ALLOW_PICKUP = 8;
  const COL_CATEGORY_IMAGE = 9;
  const COL_BUYER = 10;
  const COL_DURATION = 11;
  const COL_SHIPPING_OPTIONS = 12;
  const COL_FOOTER = 13;

  const __E_COND_EMPTY_ROWS = <<<'EOT'
There's more than one row with empty weight and price conditions. Shipping type should contain only one default row
EOT;
  const __E_COND_BOTH_IN_ROW = <<<'EOT'
One of rows contains both weight and price conditions. Only one condition is allowed at a time
EOT;
  const __E_COND_MULTIPLE = <<<'EOT'
Rows contain both weight and price conditions. Only one condition is allowed per shipping type
EOT;

  protected $_conditions = ['weight', 'price'];

  public function getAvailConds () {
    return $this->_conditions;
  }

  /**
   * Upload TradeMe optons file and import data from it
   *
   * @param Varien_Object $object
   * @throws Mage_Core_Exception
   */
  public function import ($data) {
    $scopeId = $data->getScopeId();
    $website = Mage::app()->getWebsite($scopeId);

    $shippingTypes = $this->getShippingTypes($website->getDefaultStore());

    if (!$shippingTypes)
      Mage::throwException($this->__('There\'re no available shipping types'));

    $accounts = $this->getAccounts($website);

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

    if ($headers === false || count($headers) < self::NUM_OF_COLUMNS) {
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
        $data = $this->_restructureRows($data);
        $data = $this->_prepareConditions($data, $this->_conditions);

        foreach ($data as $accountId => $_shippingTypes) {
          if (!isset($_shippingTypes['errors']))
            continue;

          foreach ($_shippingTypes['errors'] as $error) {
            $params['errors'][] = sprintf(
              '%s (account "%s", shipping type "%s")',
              $error['message'],
              $accounts[$accountId]['name'],
              isset($shippingTypes[$error['shipping_type_id']])
                ? $shippingTypes[$error['shipping_type_id']]
                : $error['shipping_type_id']
            );
          }
        }

        if (!$params['errors'])
          $this->_saveImportedOptions($data, $website);

        $missingShippingTypes = $this->_getMissingShippingTypes(
          $data,
          $shippingTypes
        );
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

    if ($missingShippingTypes) {
      foreach ($missingShippingTypes as $accountId => $shippingTypes) {
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
    if (count($row) < self::NUM_OF_COLUMNS) {
      $msg = 'Invalid TradeMe options format in row %s';
      $params['errors'][] = $this->__($msg, $rowNumber);

      return false;
    }

    //Strip whitespace from the beginning and end of each column
    foreach ($row as $k => $v)
      $row[$k] = trim($v);

    $account = strtolower($row[self::COL_ACCOUNT]);

    if (!isset($params['account'][$account])) {
      $msg = 'Invalid account ("%s") in row %s.';
      $params['errors'][] = $this->__(
        $msg,
        $row[self::COL_ACCOUNT],
        $rowNumber
      );

      return false;
    }

    $account = $params['account'][$account];

    $shippingType = strtolower($row[self::COL_SHIPPING_TYPE]);

    if (!isset($params['type'][$shippingType])) {
      $msg = 'Invalid shipping type ("%s") in row %s.';
      $params['errors'][] = $this->__(
        $msg,
        $row[self::COL_SHIPPING_TYPE],
        $rowNumber
      );

      return false;
    }

    $shippingType = $params['type'][$shippingType];

    //Validate maximum weight
    $weight = $this->_parseWeight($row[self::COL_WEIGHT]);

    if ($weight === false) {
      $msg = 'Invalid Maximum weight value ("%s") in row %s.';
      $params['errors'][] = $this->__($msg, $row[self::COL_WEIGHT], $rowNumber);

      return false;
    }

    //Validate maximum price
    $price = $this->_parsePrice($row[self::COL_PRICE]);

    if ($price === false) {
      $msg = 'Invalid Minimal price value ("%s") in row %s.';
      $params['errors'][] = $this->__($msg,$row[self::COL_PRICE], $rowNumber);

      return false;
    }

    $freeShippingCost = $this->_parseDecimalValue(
      $row[self::COL_FREE_SHIPPING_COST]
    );

    if ($freeShippingCost === false) {
      $msg = 'Invalid Free shipping cost value ("%s") in row %s.';
      $params['errors'][] = $this->__(
        $msg,
        $row[self::COL_FREE_SHIPPING_COST],
        $rowNumber
      );

      return false;
    }

    $addFees = $this->_parseAddfeesValue($row[self::COL_ADD_FEES]);

    if ($addFees === false) {
      $msg = 'Invalid Add fees value ("%s") in row %s.';
      $params['errors'][] = $this->__(
        $msg,
        $row[self::COL_ADD_FEES],
        $rowNumber
      );

      return false;
    }

    //Validate listing duration value
    $listingDuaration = (int) $row[self::COL_DURATION];

    //!!!TODO: use getDuration() method
    if (!$listingDuaration || $listingDuaration > self::LISTING_DURATION_MAX)
      $listingDuaration = self::LISTING_DURATION_MAX;
    else if ($listingDuaration < self::LISTING_DURATION_MIN)
      $listingDuaration = self::LISTING_DURATION_MIN;

    //Protect from duplicate
    $hash = sprintf('%s-%s-%s-%s', $account, $shippingType, $weight, $price);

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
      'price' => $price,
      'free_shipping_cost' => $freeShippingCost,
      'allow_buy_now' => (bool) $row[self::COL_ALLOW_BUY_NOW],
      'avoid_withdrawal' => (bool) $row[self::COL_AVOID_WITHDRAWAL],
      'add_fees' => $addFees,
      'allow_pickup' => (bool) $row[self::COL_ALLOW_PICKUP],
      'category_image' => (bool) $row[self::COL_CATEGORY_IMAGE],
      'buyer' => (int) $row[self::COL_BUYER],
      'duration' => $listingDuaration,
      'shipping_options' => $this->_parseShippingOptionsValue(
        $row[self::COL_SHIPPING_OPTIONS]
      ),
      'footer' => $row[self::COL_FOOTER]
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
   * Parse string with shipping options.
   *
   * - SHIPPING_UNDECIDED is returned for the empty string
   * - SHIPPING_FREE is returned if the string is equal to Free word
   *   (case insensitive)
   *
   * If the string is in following format
   *
   *   <price>,<method>\r\n
   *   ...
   *   <price>,<method>
   *
   * then a list of shipping options in the following format is returned
   *
   *   [
   *     [
   *       'price' => 12.5,
   *       'description' => 'Desription of a shipping method'
   *     ],
   *
   *     ...
   *   ]
   *
   * @param string $value
   *   String with shipping options in of 3 formats described above
   *
   * @return int|array
   *   Result of parsing supplied string with shipping options as described
   *   above.
   */
  protected function _parseShippingOptionsValue ($value) {
    $value = trim($value);
    if (!$value)
      return MVentory_TradeMe_Model_Config::SHIPPING_UNDECIDED;

    if (strtolower($value) === 'free')
      return MVentory_TradeMe_Model_Config::SHIPPING_FREE;

    $options = [];

    foreach (explode("\n", str_replace("\r\n", "\n", $value)) as $opt)
      if (count($opt = explode(',', trim($opt, " ,\t\n\r\0\x0B"), 2)) == 2)
        $options[] = array(
          'price' => (float) rtrim($opt[0]),
          'description' => ltrim($opt[1])
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
        Mage::helper('core')->jsonEncode($data),
        'websites',
        $websiteId
      );

    $config->reinit();

    Mage::app()->reinitStores();
  }

  protected function _restructureRows ($rows) {
    $accounts = array();

    foreach ($rows as $row) {
      $accountId = $row['account'];
      $shippingType = $row['shipping_type'];

      unset($row['account'], $row['shipping_type']);

      $accounts[$accountId][$shippingType]['settings'][] = $row;
    }

    return $accounts;
  }

  /**
   * Prepare conditions for all shipping types in supplied accounts settings
   *
   * @param array $accounts
   *   Settings for accounts
   *
   * @param array $availConds
   *   List of available conditions
   *
   * @return array
   *   Settings for accounts with prepared conditions
   */
  protected function _prepareConditions ($accounts, $availConds) {
    foreach ($accounts as &$shippingTypes) {
      $errors = [];

      foreach ($shippingTypes as $shippingTypeId => &$shippingType) try {
        $condition = $this->_getCondition(
          $shippingType['settings'],
          $availConds
        );

        $shippingType = [
          'condition' => $condition['name'],
          'settings' => $condition['values']
        ];
      }
      catch (MVentory_TradeMe_Exception $e) {
        $errors[] = [
          'shipping_type_id' => $shippingTypeId,
          'message' => $e->getMessage()
        ];
      }

      if ($errors)
        $shippingTypes['errors'] = $errors;
    }

    return $accounts;
  }

  /**
   * Return condition for the supplied settings for shipping type
   *
   * @param array $settings
   *   Settings for shipping type
   *
   * @param array $availConds
   *   List of available conditions
   *
   * @return array
   *   Condition's name and values
   */
  protected function _getCondition ($settings, $availConds) {
    $cond = [];

    foreach ($settings as $setting) {
      $found = [];

      //Collect all available conditions and their values from the setting row
      foreach ($availConds as $availCond) {
        if ($setting[$availCond] !== '')
          $found[$availCond] = $setting[$availCond];

        unset($setting[$availCond]);
      }

      //Found no conditions in the setting row
      if (!$found) {

        //Raise error if setting row without conditions is not sole one
        if (count($settings) > 1)
          throw new MVentory_TradeMe_Exception($this->__(
            self::__E_COND_EMPTY_ROWS
          ));

        $cond['name'] = null;
        $cond['values'][] = $setting;

        return $cond;
      }

      //Found setting row with multiple conditions - raise error
      if (count($found) > 1)
        throw new MVentory_TradeMe_Exception($this->__(
          self::__E_COND_BOTH_IN_ROW
        ));

      //Found setting row with different condition from previous row - raise
      //error
      if (isset($cond['name']) && $cond['name'] != key($found))
        throw new MVentory_TradeMe_Exception($this->__(
          self::__E_COND_MULTIPLE
        ));

      $cond['name'] = key($found);
      $cond['values'][current($found)] = $setting;
    }

    return $cond;
  }

  protected function _getMissingShippingTypes ($accounts, $allShippingTypes) {
    $result = array();

    foreach ($accounts as $accountsId => $shippingTypes) {
      unset($shippingTypes[''], $shippingTypes['*']);

      if ($shippingTypes
          && ($missing = array_diff_key($allShippingTypes, $shippingTypes)))
        $result[$accountsId] = $missing;
    }

    return $result;
  }
}
