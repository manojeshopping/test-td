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
 * @copyright Copyright (c) 2016 mVentory Ltd. (http://mventory.com)
 * @license Commercial
 */

/**
 * Source model for promotions field
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Setting_Source_Promotions
{

  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray () {
    $helper = Mage::helper('trademe');

    return [
      [
        'label' => $helper->__('No promotion'),
        'value' => MVentory_TradeMe_Model_Config::PROMOTION_NO
      ],
      [
        'label' => $helper->__('Bold promotion'),
        'value' => MVentory_TradeMe_Model_Config::PROMOTION_BOLD
      ],
      [
        'label' => $helper->__('Feature promotion'),
        'value' => MVentory_TradeMe_Model_Config::PROMOTION_FEATURE
      ],
      [
        'label' => $helper->__('Gallery promotion'),
        'value' => MVentory_TradeMe_Model_Config::PROMOTION_GALLERY
      ],
      [
        'label' => $helper->__('"Highlight" promotion'),
        'value' => MVentory_TradeMe_Model_Config::PROMOTION_HIGHLIGHT
      ],
      [
        'label' => $helper->__('"Super feature" promotion'),
        'value' => MVentory_TradeMe_Model_Config::PROMOTION_SUPER
      ]
    ];
  }
}

?>
