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
 * Entity/Attribute/Model - attribute selection source abstract with default
 * value
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
abstract class MVentory_TradeMe_Model_Attribute_Source_WithDefault
  extends MVentory_TradeMe_Model_Attribute_Source_Abstract
{
  /**
   * Value of Default option
   * @var integer
   */
  protected $_defaultValue = -1;

  /**
   * Label of Default option
   * @var string
   */
  protected $_defaultLabel = 'Default';

  /**
   * Initiates options array for getAllOptions() method with Default options
   * on first position
   *
   * @return array
   */
  protected function _initAllOptionsArray () {
    return array(
      array(
        'value' => $this->_defaultValue,
        'label' => Mage::helper('trademe')->__($this->_defaultLabel)
      )
    );
  }
}
