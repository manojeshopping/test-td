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
 * TradeMe settings block
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Block_Settings extends Mage_Adminhtml_Block_Abstract
{

  /**
   * Render block HTML
   *
   * @return string
   */
  protected function _toHtml () {
    $trademe = array(
      'url' => array(
        'authenticate' => $this->_getUrl('trademe/account/authenticate'),
        'canremove' => $this->_getUrl('trademe/account/canremove'),
        'remove' => $this->_getUrl('trademe/account/remove')
      )
    );

    return Mage::helper('core/js')->getScript(
      'var trademe = ' . Mage::helper('core')->jsonEncode($trademe)
    );
  }

  protected function _getUrl ($route) {
    return $this->getUrl(
      $route,
      array('website' => $this->getRequest()->getParam('website', ''))
    );
  }
}
