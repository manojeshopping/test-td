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
 * Source model for payment methods setting
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Setting_Source_Paymentmethods
{

  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray () {
    $helper = Mage::helper('trademe');

    return array(
      array(
        'value' => MVentory_TradeMe_Model_Config::PAYMENT_BANK,
        'label' => $helper->__('NZ bank deposit')
      ),
      array(
        'value' => MVentory_TradeMe_Model_Config::PAYMENT_CC,
        'label' => $helper->__('Credit card')
      ),
      array(
        'value' => MVentory_TradeMe_Model_Config::PAYMENT_CASH,
        'label' => $helper->__('Cash')
      ),
      array(
        'value' => MVentory_TradeMe_Model_Config::PAYMENT_SAFE,
        'label' => $helper->__('Safe Trader')
      ),
    );
  }
}

?>
