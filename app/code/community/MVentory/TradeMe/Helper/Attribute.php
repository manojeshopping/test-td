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
 * TradeMe attribute helper
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Helper_Attribute extends MVentory_TradeMe_Helper_Data
{
  /**
   * List of allowed magento attributes
   * @var array
   */
  protected $_allowedTypes = [
    'text' => true,
    'textarea' => true,
    'select' => true,
    'multiselect' => true
  ];

  /**
   * Return list of TM attributes with their values from the supplied product.
   * Format of return data:
   *
   *   [
   *     //Shows if error happened during preparing list of aTM attributes with
   *     //values.
   *     'error' => false,
   *
   *     //Name of required TM attribute which value doesn't exists
   *     //in the supplied product.
   *     //OPTIONAL field, it's set when error is true
   *     'required' => 'CPUSpeed',
   *
   *     //Pairs of TM atribute name and its value from the supplied product
   *     'attributes' => [
   *       'Memory' => '2',
   *
   *       ...
   *     ]
   *   ]
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param array $attrs
   *   List of TM attributes for product's TM category
   *
   * @param Mage_Core_Model_Store $store
   *   Store for TM attributes matching
   *
   * @return array
   *   List of TM attributes with their values or error
   */
  public function fillAttributes ($product, $attrs, $store) {
    $storeId = $store->getId();

    //Prepare list of TM attributes and list of required attributes.
    //Use attribute name in lower case as array's key for faster
    //and case-independent search
    list($names, $required) = $this->_prepareAttrs($attrs);

    $collected = [];

    //Iterater over all product's attributes to collect mapped TradeMe
    //attributes
    foreach ($product->getAttributes() as $pAttr) {

      /**
       * Check if magento attribute has allowed type
       *
       * @see MVentory_TradeMe_Helper_Attribute::_allowedTypes
       *   For list if allowed attribute types
       */
      $input = $pAttr->getFrontendInput();
      if (!isset($this->_allowedTypes[$input]))
        continue;

      $collected = array_merge(
        $collected,
        $this->_getTmAttrData($product, $pAttr, $storeId)
      );
    }

    //Get lists of mapped and missed attributes
    $mapped = array_intersect_key($collected, $names);
    $missed = array_diff_key($required, $mapped);

    if ($missed)
      return array(
        'error' => true,
        'required' => implode(', ', $missed)
      );

    return array(
      'error' => false,

      //Restore lowercased names of the mapped attributes to their originals
      'attributes' => $this->_restoreNames($mapped, $names)
    );
  }

  /**
   * Prepare list of names of TradeMe attributes and list of required attributes
   *
   * @param array $attrs
   *   TradeMe attributes
   *
   * @return array
   *   List of names of TradeMe attributes and list of required attributes
   */
  protected function _prepareAttrs ($attrs) {
    $names = $required = [];

    foreach ($attrs as $attr) {
      $name = $this->_prepareName($attr['Name']);

      $names[$name] = $attr['Name'];

      if (isset($attr['IsRequiredForSell']) && $attr['IsRequiredForSell'])
        $required[$name] = $attr['Name'];
    }

    return [$names, $required];
  }

  /**
   * Return name-value pairs of TM attributes for supplied product and attribute
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param Mage_Catalog_Model_Resource_Eav_Attribute $attr
   *   Attribute model
   *
   * @param int $storeId
   *   Store ID for TM attributes matching
   *
   * @return array
   *   List of name-value pairs of TM attributes
   */
  protected function _getTmAttrData ($product, $attr, $storeId) {
    //Try to get TM attributes' name and value from Magento's attribute label
    //used in store for TM attributes mapping.
    if ($data = $this->_getDataFromLabel($product, $attr, $storeId))
      return $data;

    //Otherwise check if Magento attribute is dropbdown or multiselect
    //attribute
    $input = $attr->getFrontendInput();

    if (!($input == 'select' || $input == 'multiselect'))
      return [];

    //and return TM attributes' name and value from Magento attribute's option
    //label
    return $this->_getDataFromValue($product, $attr, $storeId);
  }

  /**
   * Return name-value pairs of TM attributes from supplied Magento's attribute
   * label used in store for TM attributes mapping
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param Mage_Catalog_Model_Resource_Eav_Attribute $attr
   *   Attribute model
   *
   * @param int $storeId
   *   Store ID for TM attributes matching
   *
   * @return array
   *   List of name-value pairs of TM attributes
   */
  protected function _getDataFromLabel ($product, $attr, $storeId) {

    //Get attribute labels for all stores
    $labels = $attr->getStoreLabels();
    if (!isset($labels[$storeId]))
      return [];

    //Ignore empty label
    $names = trim($labels[$storeId]);
    if (!$names)
      return [];

    //Split attribute label by comma to get list of all TM attributes names
    $names = explode(',', trim($labels[$storeId]));
    if (!$names)
      return [];

    //Get value of Magento attribute in supplied product.
    //Return empty string for attributes without values in the products because
    //Magento will return 'No' for such attributes
    $value = $product[$attr->getAttributeCode()]
               ? $attr->getFrontend()->getValue($product)
               : '';

    //Ignore TM attributes with empty value. TradeMe will use default one
    if ($value === '')
      return [];

    $data = [];

    //Go through list of TM atrributes names and assign Magento attribute value
    //to every TM attribute
    array_walk(
      $names,
      function ($name) use (&$data, $value) {

        //Ignore empty names
        $name = $this->_prepareName($name);
        if (!$name)
          return;

        $data[$name] = $value;
      }
    );

    return $data;
  }

  /**
   * Return name-value pairs of TM attributes from supplied Magento's attribute
   * option label used in store for TM attributes mapping
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param Mage_Catalog_Model_Resource_Eav_Attribute $attr
   *   Attribute model
   *
   * @param int $storeId
   *   Store ID for TM attributes matching
   *
   * @return array
   *   List of name-value pairs of TM attributes
   */
  protected function _getDataFromValue ($product, $attr, $storeId) {
    $frontend = $attr->getFrontend();

    //Remember value of the supplied attribute in current store
    $defaultValue = $frontend->getValue($product);
    //Remeber current store ID to restore it later
    $_storeId = $attr->getStoreId();

    //Change attribute's store ID to store used for TM attributes matching
    //and get value for the attribute, restore original store ID
    $attr->setStoreId($storeId);
    $value = $frontend->getValue($product);
    $attr->setStoreId($_storeId);

    //Similar values mean we probably don't have data for TM attributes mathing,
    //return nothing
    if ($defaultValue == $value)
      return [];

    //Ignore empty value
    $value = trim($value);
    if (!$value)
      return [];

    //Split name-value pairs by comma. Comma is used to specify multiple TradeMe
    //attributes for one to many mapping
    $pairs = explode(',', $value);
    if (!$pairs)
      return [];

    $data = [];

    //Go through list of TM atrributes's name-value pairs separated by column
    //and convert them to the list of name-value pair
    array_walk(
      $pairs,
      function ($pair) use (&$data) {

        //Ignore empty pairs
        $pair = trim($pair);
        if (!$pair)
          return;

        //Extract TM attribute name and value from Magento's attribute option
        //label. We use column as seaparator for them
        $parts = explode(':', $pair, 2);

        //Ignore pairs which don't follow format or have no TM attribute
        //name before the separator
        if (!(count($parts) == 2 && $parts[0]))
          return;

        //Ignore empty names
        $name = $this->_prepareName($parts[0]);
        if (!$name)
          return;

        //Ignore TM attributes with empty value. TradeMe will use default one
        $value = ltrim($parts[1]);
        if ($value === '')
          return;

        $data[$name] = $value;
      }
    );

    return $data;
  }

  /**
   * Prepare name of TradeMe attribute for case-insensitive checks
   *
   * @param string $name
   *   Name of TradeMe attribute
   *
   * @return string
   *   Prepared name of TradeMe attribute
   */
  protected function _prepareName ($name) {
    return strtolower(trim($name));
  }

  /**
   * Restore lowercased names of the mapped TM attributes to their originals
   *
   * @param array $mapped
   *   List of mapped TM attributes
   *
   * @param array $names
   *   List of mapping between lowercased names and original names
   *
   * @return array
   *   List of mapped TM attributes with original names
   */
  protected function _restoreNames ($mapped, $names) {
    $_mapped = [];

    foreach ($mapped as $name => $value)
      $_mapped[$names[$name]] = $value;

    return $_mapped;
  }
};