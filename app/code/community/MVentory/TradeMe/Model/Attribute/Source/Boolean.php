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
 * Source model for Yes/No/Default field
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Attribute_Source_Boolean
  extends MVentory_TradeMe_Model_Attribute_Source_WithDefault
{
  /**
   * "Yes" value, same as in Mage_Eav_Model_Entity_Attribute_Source_Boolean
   * Redeclared to make the extensions compatible with Magento < 1.8
   *
   * @see Mage_Eav_Model_Entity_Attribute_Source_Boolean::VALUE_YES
   */
  const VALUE_YES = 1;

  /**
   * "No" value, same as in Mage_Eav_Model_Entity_Attribute_Source_Boolean
   * Redeclared to make the extensions compatible with Magento < 1.8
   *
   * @see Mage_Eav_Model_Entity_Attribute_Source_Boolean::VALUE_NO
   */
  const VALUE_NO = 0;

  /**
   * Generate array of options
   *
   * @return array
   */
  protected function _getOptions () {
    if (defined('Mage_Eav_Model_Entity_Attribute_Source_Boolean::VALUE_YES')) {
      $yes = Mage_Eav_Model_Entity_Attribute_Source_Boolean::VALUE_YES;
      $no = Mage_Eav_Model_Entity_Attribute_Source_Boolean::VALUE_NO;
    } else {
      $yes = self::VALUE_YES;
      $no = self::VALUE_NO;
    }

    return array($yes => 'Yes', $no => 'No');
  }
}
