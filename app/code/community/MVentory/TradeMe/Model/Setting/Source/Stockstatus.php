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
 * Source model for stock status setting
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Setting_Source_Stockstatus
{

  /**
   * Options getter
   *
   * @return array
   *   List of options
   */
  public function toOptionArray () {
    $helper = Mage::helper('trademe');

    return array(
      array(
        'label' => $helper->__('Managed and in stock'),
        'value' => MVentory_TradeMe_Model_Config::STOCK_IN
      ),
      array(
        'label' => $helper->__('Stock not managed'),
        'value' => MVentory_TradeMe_Model_Config::STOCK_NOT_MANAGED
      ),
      array(
        'label' => $helper->__('Both of the above'),
        'value' => MVentory_TradeMe_Model_Config::STOCK_BOTH
      )
    );
  }

  /**
   * Return options as value-label array
   *
   * @return array
   *   Value-label list of options
   */
  public function toArray () {
    $helper = Mage::helper('trademe');

    return array(
      MVentory_TradeMe_Model_Config::STOCK_IN
        => $helper->__('Managed and in stock'),
      MVentory_TradeMe_Model_Config::STOCK_NOT_MANAGED
        => $helper->__('Stock not managed'),
      MVentory_TradeMe_Model_Config::STOCK_BOTH
        => $helper->__('Both of the above'),
      MVentory_TradeMe_Model_Config::STOCK_NO
        => $helper->__('Out of stock')
    );
  }
}

?>
