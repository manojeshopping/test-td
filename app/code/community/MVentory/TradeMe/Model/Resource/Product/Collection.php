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
 */

/**
 * Product collection model
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

class MVentory_TradeMe_Model_Resource_Product_Collection
  extends Mage_Catalog_Model_Resource_Product_Collection
{
  /**
   * Retrive all SKUs for collection
   *
   * @param int|null $limit
   *   The number of rows to return
   *
   * @param int|null $offset
   *   Start returning after this many rows
   *
   * @return array
   *   List of all SKUs for collection. Key and value pair is value of SKU
   */
  public function getAllSkus ($limit = null, $offset = null) {
    $select = $this
      ->_getClearSelect()
      ->columns(array('e.sku', 'e.sku'))
      ->limit($limit, $offset)
      ->resetJoinLeft();

    return $this
      ->getConnection()
      ->fetchPairs($select, $this->_bindParams);
  }
}
