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
 * Product helper
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Helper_Product extends MVentory_TradeMe_Helper_Data
{
  /**
   * Regex to replace tag with atttribute code by its product's value
   *
   * Groups:
   *
   *   - pre, post: whitespaces around tag
   *   - tag: whole tag
   *   - before, after: any text around code group inside tag group
   *   - code: attribute code which is replaced by its value in a product
   *
   * Example:
   *
   * Beach shorts   { in {color} color}   and t-shirt
   *             \A/\_B_/\__C__/\__D__/\E/
   *                \________F________/
   *
   * - A: pre
   * - B: before
   * - C: code
   * - D: after
   * - E: post
   * - F: tag
   *
   * Notes:
   *
   *   - Whole tag is removed when code is empty
   *     Above example become "Beach shorts and t-shirt"
   *   - Any number of spaces around tag is replaced with single space
   *     if tag is removed.
   *
   * @see MVentory_TradeMe_Helper_Product::_processNames()
   */
  const _RE_TAGS = <<<'EOT'
/(?<pre>\s*)(?<tag>{(?<before>[^{}]*){(?<code>[^{}]*)}(?<after>[^{}]*)})(?<post>\s*)/
EOT;

  /**
   * Add filtering by selected stock statuses in specified store to product
   * collection
   *
   * @param Mage_Catalog_Model_Resource_Product_Collection $products
   *   Product collection model
   *
   * @param Mage_Core_Model_Store $store
   *   Current store model
   *
   * @return MVentory_TradeMe_Helper_Product
   *   Instance of this class
   */
  public function addStockStatusFilter ($products, $store) {
    if (!$modes = $this->_getStockModes($store))
      return $this;

    if (!$conds = $this->_convertModesToConds($modes, $store))
      return $this;

    $products->joinField(
      'inventory_manage_stock',
      'cataloginventory/stock_item',
      'manage_stock',
      'product_id=entity_id',
      $conds['status']
    );

    if (isset($conds['qty']))
      $products->joinField(
        'inventory_qty',
        'cataloginventory/stock_item',
        'qty',
        'product_id=entity_id',
        $conds['qty']
      );

    return $this;
  }

  /**
   * Update attribute values for product per store
   *
   * @param int $productId
   *   ID of product
   *
   * @param array $attrData
   *   Array of key-value pair of attribute's code and its value
   *
   * @param int|string|Mage_Core_Model_Store $store
   *   Website model or its ID or code
   */
  public function setAttributesValue ($productId, $attrData, $store = 0) {
    Mage::getResourceSingleton('catalog/product_action')->updateAttributes(
      array($productId),
      $attrData,
      Mage::app()
        ->getStore($store)
        ->getId()
    );

    return $this;
  }

  /**
   * Return final price.
   *
   * Set data for calculation of catalog price rule as required in
   * @see Mage_CatalogRule_Model_Observer::processAdminFinalPrice()
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param Mage_Core_Model_Website $website
   *   Product's website
   *
   * @param boolean $inclTax
   *   Add taxes to product's price
   *
   * @return float
   *   Final price
   */
  public function getPrice ($product, $website, $inclTax = true) {
    Mage::register(
      'rule_data',
      new Varien_Object(array(
        'website_id' => $website->getId(),
        'customer_group_id' => Mage_Customer_Model_Group::NOT_LOGGED_IN_ID
      )),
      true
    );

    $price = (float) $product->getFinalPrice();

    Mage::unregister('rule_data');

    if (!$inclTax)
      return $price;

    $store = $website->getDefaultStore();

    //Remember current price display type to restore it after calculating
    //taxes
    $priceDisplayType = $store->getConfig(
      Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_DISPLAY_TYPE
    );

    //Temporarily set current price display to include tax type to trigger
    //tax calculation even if price display setting is to to exclude taxes
    $store->setConfig(
      Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_DISPLAY_TYPE,
      Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX
    );

    $price = Mage::helper('tax')->getPrice(
      $product,
      $price,
      true,
      null,
      null,
      null,
      $store
    );

    //Restore original value of current price display type
    $store->setConfig(
      Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_DISPLAY_TYPE,
      $priceDisplayType
    );

    return $price;
  }

  /**
   * Parse Minimum stock level setting and return list if modes for stock
   * filtering
   *
   * @param Mage_Core_Model_Store $store
   *   Current store model
   *
   * @return array|null
   *   List of modes for stock filtering or null if not modes or all modes
   *   are selected
   */
  protected function _getStockModes ($store) {
    $modes = array_flip(explode(
      ',',
      $store->getConfig(MVentory_TradeMe_Model_Config::_STOCK_STATUS)
    ));

    if (!$modes)
      return;

    $modes = array_intersect_key(
      Mage::getSingleton('trademe/setting_source_stockstatus')->toArray(),
      $modes
    );

    if (!$modes || count($modes) == 3)
      return;

    $_modes = array();

    if (isset($modes[MVentory_TradeMe_Model_Config::STOCK_NOT_MANAGED]))
      $_modes[] = array('managed' => 0);

    if (isset($modes[MVentory_TradeMe_Model_Config::STOCK_IN])) {
      $_mode = array('managed' => 1, 'stock' => 1);

      $minQty = (int) $store->getConfig(
        MVentory_TradeMe_Model_Config::_MIN_ALLOWED_QTY
      );

      if ($minQty > 1)
        $_mode['min_qty'] = $minQty;

      $_modes[] = $_mode;
    }

    if (isset($modes[MVentory_TradeMe_Model_Config::STOCK_NO]))
      $_modes[] = array('managed' => 1, 'stock' => 0);

    if (isset($modes[MVentory_TradeMe_Model_Config::STOCK_BOTH])) {
      $_modes[] = array('managed' => 0);
      $_modes[] = array('managed' => 1, 'stock' => 1);
    }

    return $_modes;
  }

  /**
   * Convert list of modes for stock filtering into SQL conditions to apply
   * to product collection
   *
   * @param array $modes
   *   List of modes for stock filtering
   *
   * @param Mage_Core_Model_Store $store
   *   Current store model
   *
   * @return array
   *   List of prepared SQL conditions to filter product collection
   */
  protected function _convertModesToConds ($modes, $store) {
    $storeManaged = (int) $store->getConfig(
      Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK
    );

    foreach ($modes as $mode) {
      $andCond = array();

      $andCond[] = '{{table}}.use_config_manage_stock = 0';
      $andCond[] = '{{table}}.manage_stock = ' . $mode['managed'];

      if (isset($mode['stock']))
        $andCond[] = '{{table}}.is_in_stock = ' . $mode['stock'];

      $cond[] = implode(' AND ', $andCond);

      if ($mode['managed'] == $storeManaged) {
        $andCond = array();

        $andCond[] = '{{table}}.use_config_manage_stock = 1';

        if (isset($mode['stock'])) {
          $andCond[] = '{{table}}.is_in_stock = ' . $mode['stock'];

          if (isset($mode['min_qty']))
            $qtyCond = '{{table}}.qty >= ' . $mode['min_qty'];
        }

        $cond[] = implode(' AND ', $andCond);
      }
    }

    $conds = array(
      'status' => isset($cond) ? '(' . implode(') OR (', $cond) . ')' : null
    );

    if (isset($qtyCond))
      $conds['qty'] = $qtyCond;

    return $conds;
  }

  /**
   * Return name variants for the supplied product with processed
   * {{attribute_code}} tags
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   List of name variants
   */
  public function getNameVariants ($product, $store) {
    $code = trim(
      $store->getConfig(MVentory_TradeMe_Model_Config::_AUC_NAME_VAR_ATTR)
    );

    if (!$code)
      return array();

    $names = trim($product[strtolower($code)]);

    if (!$names)
      return array();

    return array_filter(array_map(
      'trim',
      $this->_processNames(
        explode("\n", str_replace("\r\n", "\n", $names)),
        $product
      )
    ));
  }

  /**
   * Replace {{attribute_code}} tags in the supplied list of product's
   * alternative names with corresponding value from the specified product
   *
   * @see MVentory_TradeMe_Helper_Product::_RE_TAGS
   *   See description of regex
   *
   * @param array $names
   *   List of product's alternative names
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @return array
   *   List of processed alternative names
   */
  protected function _processNames ($names, $product) {
    $attrs = $product->getAttributes();

    return preg_replace_callback(
      self::_RE_TAGS,
      function ($matches) use ($product, $attrs) {
        $code = trim($matches['code']);

        //We check raw value in the condition because product can contain
        //null/empty string as value and dropdown attribute's frontend
        //returns "No" in this case
        $cond = $code
                && $product->getData($code)
                && isset($attrs[$code])
                && ($attr = $attrs[$code]);

        $value = $cond
                   ? trim($attr->getFrontend()->getValue($product))
                   : false;

        if ($value)
          return $matches['pre']
                 . $matches['before']
                 . $value
                 . $matches['after']
                 . $matches['post'];

        return ($matches['pre'] . $matches['post']) ? ' ' : '';
      },
      $names
    );
  }

  /**
   * Check if supplied product has active special price
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return boolean
   *   Result of the check
   */
  public function hasSpecialPrice ($product, $store) {
    $specialPrice = $product->getSpecialPrice();

    return $specialPrice !== null
           && $specialPrice !== false
           && Mage::app()
                ->getLocale()
                ->isStoreDateInInterval(
                    $store,
                    $product->getSpecialFromDate(),
                    $product->getSpecialToDate()
              );
  }
}
