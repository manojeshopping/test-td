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
 * TradeMe categories table block
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Block_Categories extends Mage_Core_Block_Template
{

  const TYPE_CHECKBOX = 'checkbox';
  const TYPE_RADIO = 'radio';

  const URL = 'http://www.trademe.co.nz';

  private $_categories = null;

  /**
   * Return list of categories
   *
   * @return array
   */
  public function getCategories () {
    if ($this->_categories === null) {
      $api = new MVentory_TradeMe_Model_Api();
      $this->_categories = $api->getCategories();
    }

    return $this->_categories;
  }

  /**
   * Calculate required number of columns in table
   *
   * @return int
   */
  public function getColsNumber () {
    $categories = $this->getCategories();

    $cols = 0;

    foreach ($categories as $category)
      if (count($category['name']) > $cols)
        $cols = count($category['name']);

    return $cols;
  }

  /**
   * Set type of category selector: single or multiple
   *
   * @param string $type Type of selector
   * @return MVentory_TradeMe_Block_Categories
   */
  public function setInputType ($type) {
    if (!($type == self::TYPE_CHECKBOX || $type == self::TYPE_RADIO))
      $type = self::TYPE_CHECKBOX;

    $this->setData('input_type', $type);

    return $this;
  }
}
