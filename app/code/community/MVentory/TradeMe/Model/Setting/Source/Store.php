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
 * Source model for list of stores with empty option
 *
 * @package MVentory/Trademe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Setting_Source_Store
  extends Mage_Adminhtml_Model_System_Config_Source_Store
{
  public function toOptionArray () {
    if ($this->_options !== null)
      return $this->_options;

    parent::toOptionArray();

    if (!$this->_options)
      return $this->_options;

    array_unshift($this->_options, array('label' => '', 'value' => ''));

    return $this->_options;
  }
}
