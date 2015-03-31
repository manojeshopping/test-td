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
 * Source model for Add fees field
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Attribute_Source_Addfees
  extends MVentory_TradeMe_Model_Attribute_Source_WithDefault
{
  /**
   * Generate array of options
   *
   * @return array
   */
  protected function _getOptions () {
    return array(
      MVentory_TradeMe_Model_Config::FEES_NO => 'No',
      MVentory_TradeMe_Model_Config::FEES_ALWAYS => 'Always',
      MVentory_TradeMe_Model_Config::FEES_SPECIAL => 'On special price'
    );
  }

  /**
   * Retrieve options for using in exporting of account settings
   * Labels are different from the original array of options
   *
   * @see MVentory_TradeMe_Block_Options::_getAddfeesValues()
   * @return array
   */
  public function toArray () {
    return array(
      MVentory_TradeMe_Model_Config::FEES_NO => '',
      MVentory_TradeMe_Model_Config::FEES_ALWAYS  => 'Always',
      MVentory_TradeMe_Model_Config::FEES_SPECIAL => 'Special'
    );
  }
}

?>
