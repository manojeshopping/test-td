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
 * Grid of the TradeMe options for CSV generation
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Block_Options
  extends Mage_Adminhtml_Block_Widget_Grid {

  const TYPE_TEXT = 0;
  const TYPE_INT = 1;
  const TYPE_BOOL = 2;
  const TYPE_PRICE = 3;
  const TYPE_FLOAT = 4;

  protected $_helper = null;
  protected $_options = null;

  //Cache some data
  private $_addfeesValues = null;

  /**
   * Define grid properties
   *
   * @return void
   */
  public function __construct () {
    parent::__construct();

    $this->setId('optionsGrid');

    $this->_exportPageSize = 10000;

    $this->_helper = Mage::helper('trademe');

    $this->_options = array(
      'account_name' => array(
        'label' => 'Account',
        'type' => self::TYPE_TEXT
      ),
      'shipping_type' => array(
        'label' => 'Shipping method',
        'type' => self::TYPE_TEXT
      ),
      'weight' => array(
        'label' => 'Maximum weight',
        'type' => self::TYPE_FLOAT,
        'empty' => true
      ),
      'price' => array(
        'label' => 'Maximum price',
        'type' => self::TYPE_PRICE,
        'empty' => true
      ),
      'free_shipping_cost' => array(
        'label' => 'Free shipping cost',
        'type' => self::TYPE_PRICE
      ),
      'allow_buy_now' => array(
        'label' => 'Allow Buy Now',
        'type' => self::TYPE_BOOL
      ),
      'avoid_withdrawal' => array(
        'label' => 'Avoid withdrawal',
        'type' => self::TYPE_BOOL
      ),
      'add_fees' => array(
        'label' => 'Add fees',
        'type' => self::TYPE_TEXT,
        'prepare' => '_prepareAddfeesValue'
      ),
      'allow_pickup' => array(
        'label' => 'Allow pickup',
        'type' => self::TYPE_BOOL
      ),
      'category_image' => array(
        'label' => 'Add category image',
        'type' => self::TYPE_BOOL
      ),
      'duration' => array(
        'label' => 'Listing duration',
        'type' => self::TYPE_INT
      ),
      'shipping_options' => array(
        'label' => 'Shipping options',
        'type' => self::TYPE_TEXT,
        'prepare' => '_exportShippingOptions'
      ),
      'footer' => array(
        'label' => 'Footer description',
        'type' => self::TYPE_TEXT
      )
    );
  }

  /**
   * Prepare options collection
   *
   * @return MVentory_TradeMe_Block_Options
   */
  protected function _prepareCollection () {
    $helper = Mage::helper('trademe/settings');
    $collection = new Varien_Data_Collection();

    $accounts = $helper->getAccounts($this->getWebsite());

    $_shippingTypes = $this->_getShippingTypes();

    if (count($accounts) && count($_shippingTypes)) {

      //Get array of all available conditions and convert it to key-based array
      //with empty values for further use
      $availConds = $helper->getAvailConds();
      $availConds = array_combine(
        $availConds,
        array_fill(0, count($availConds), '')
      );

      foreach ($accounts as $account) {
        $hasShippingTypes = isset($account['shipping_types'])
                            && count($account['shipping_types']);

        $shippingTypes = $hasShippingTypes
                           ? $account['shipping_types']
                             : $this->_fillShippingTypes($_shippingTypes);

        foreach ($shippingTypes as $shippingTypeId => $shippingType) {
          if (!isset($_shippingTypes[$shippingTypeId]))
            continue;

          foreach ($shippingType['settings'] as $condValue => $settings) {
            $row = array(
              'account_name' => $account['name'],
              'shipping_type' => $_shippingTypes[$shippingTypeId]
            );

            //Merging of following arrays sets empty value for all conditions
            //except condition used for current shipping type if the condition
            //exists
            $row += array_merge(
              //All conditions names with empty values
              $availConds,

              //Condition name and its value used for current shipping type
              //Empty array if there's no condition
              array_intersect_key(
                [$shippingType['condition'] => $condValue],
                $availConds
              )
            );

            $row += $settings;

            foreach ($row as $optionId => $optionValue) {
              if (!isset($this->_options[$optionId]))
                continue;

              $option = $this->_options[$optionId];

              if (isset($option['empty']) && $optionValue === '')
                continue;

              if (isset($option['prepare']))
                $optionValue = call_user_func(
                  array($this, $option['prepare']),
                  $optionValue
                );

              switch ($option['type']) {
                case self::TYPE_INT:
                case self::TYPE_BOOL:
                  $optionValue = (int) $optionValue;
                  break;
                case self::TYPE_FLOAT:
                  $optionValue = (float) $optionValue;
                  break;
                case self::TYPE_PRICE:
                  $optionValue = number_format((float) $optionValue, 2);
              }

              $row[$optionId] = $optionValue;
            }

            $collection->addItem(new Varien_Object($row));
          }
        }
      }
    }

    $this->setCollection($collection);

    return parent::_prepareCollection();
  }

  /**
   * Return array with empty options for all shipping types
   *
   * @param array $shippingTypes
   * @return array
   */
  protected function _fillShippingTypes ($shippingTypes) {
    $emptyOptions = array_fill_keys(array_keys($this->_options), '');

    unset($emptyOptions['account_name']);

    $_shippingTypes = array();

    foreach ($shippingTypes as $value => $label)
      $_shippingTypes[$value] = [
        'condition' => null,
        'settings' => [$emptyOptions]
      ];

    return $_shippingTypes;
  }

  /**
   * Return all shipping options
   *
   * @return array
   */
  protected function _getShippingTypes () {
    $types = $this
      ->_helper
      ->getShippingTypes($this->getWebsite()->getDefaultStore());

    //There's 2 special values:
    //  - * - means any shipping type in product
    //  - <empty> - means no shipping type is set in product
    $types['*'] = '*';
    $types[''] = '';

    return $types;
  }

  /**
   * Return all values for Add fees option
   *
   * @return array
   */
  protected function _getAddfeesValues () {
    if ($this->_addfeesValues !== null)
      return $this->_addfeesValues;

    $this->_addfeesValues = Mage::getModel('trademe/attribute_source_addfees')
      ->toArray();

    return $this->_addfeesValues;
  }

  /**
   * Prepare table columns
   *
   * @return MVentory_TradeMe_Block_Options
   */
  protected function _prepareColumns () {
    foreach ($this->_options as $id => $option)
      $this->addColumn($id, array(
        'header' => $this->_helper->__($option['label']),
        'index' => $id
      ));

    return parent::_prepareColumns();
  }

  /**
   * Convert shipping options to string.
   *
   * Returns empty string if shipping options is SHIPPING_UNDECIDED
   * Or following string if shipping options is custom:
   *
   *   <price>,<method>\r\n
   *   ...
   *   <price>,<method>
   *
   * @param int|array $options
   *   Shipping options
   *
   * @return string
   *   Shipping options converted to string
   */
  protected function _exportShippingOptions ($options) {
    $labels = Mage::getModel('trademe/attribute_source_freeshipping')
      ->toArray();

    //In TM config file we use empty string for Undecided option
    $labels[MVentory_TradeMe_Model_Config::SHIPPING_UNDECIDED] = '';

    switch (gettype($options)) {
      case 'array':
        $_options = '';

        foreach ($options as $option)
          $_options .= "\r\n"
                       . $option['price']
                       . ','
                       . (
                          isset($option['description'])
                            ? $option['description']
                            : $option['method']
                         );

        $_options = substr($_options, 2);

        return $_options
          ?: $labels[MVentory_TradeMe_Model_Config::SHIPPING_UNDECIDED];

      case 'integer':
        return isset($labels[$options])
          ? $labels[$options]
          : $labels[MVentory_TradeMe_Model_Config::SHIPPING_UNDECIDED];
    }

    return $labels[MVentory_TradeMe_Model_Config::SHIPPING_UNDECIDED];
  }

  /**
   * Prepare value of Add fees option for export
   *
   * @param $value int Value to prepare
   * @return string
   */
  protected function _prepareAddfeesValue ($value) {
    $options = $this->_getAddfeesValues();

    return isset($options[$value])
             ? $options[$value]
               : $options[MVentory_TradeMe_Model_Config::FEES_NO];
  }
}
