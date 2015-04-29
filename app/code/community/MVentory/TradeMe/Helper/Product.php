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
      $conds
    );

    return $this;
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

    if (isset($modes[MVentory_TradeMe_Model_Config::STOCK_IN]))
      $_modes[] = array('managed' => 1, 'stock' => 1);

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
   * @return string|null
   *   Prepared SQL conditions to filter product collection or null if there's
   *   no conditions
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

        if (isset($mode['stock']))
          $andCond[] = '{{table}}.is_in_stock = ' . $mode['stock'];

        $cond[] = implode(' AND ', $andCond);
      }
    }

    return isset($cond) ? '(' . implode(') OR (', $cond) . ')' : null;
  }

  /**
   * Return name variants for the supplied product
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

    if (!$_names = trim($product[strtolower($code)]))
      return array();

    $_names = explode("\n", str_replace("\r\n", "\n", $_names));

    $names = array();

    foreach ($_names as $_name)
      if ($_name = trim($_name))
        $names[] = $_name;

    return $names;
  }
}
