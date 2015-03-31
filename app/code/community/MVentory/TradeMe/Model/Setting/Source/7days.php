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
 * Source model for duration field
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Setting_Source_7days
{

  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray () {
    $helper = Mage::helper('trademe');

    return array(
      //Temporarily disabled because TradeMe doesn't support such predefined
      //duration
      //array('label' => $helper->__('1 day'), 'value' => 1),

      array('label' => $helper->__('2 days'), 'value' => 2),
      array('label' => $helper->__('3 days'), 'value' => 3),
      array('label' => $helper->__('4 days'), 'value' => 4),
      array('label' => $helper->__('5 days'), 'value' => 5),
      array('label' => $helper->__('6 days'), 'value' => 6),
      array('label' => $helper->__('7 days'), 'value' => 7)
    );
  }
}

?>
