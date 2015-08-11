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
 * Controller for exporting options of TradeMe accounts
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_OptionsController
  extends Mage_Adminhtml_Controller_Action
{
  /**
   * Temporarily allow access for all users
   */
  protected function _isAllowed() {
    return true;
  }

  protected function _construct() {
    $this->setUsedModuleName('MVentory_TradeMe');
  }

  /**
   * Export options in csv format
   */
  public function exportAction () {
    $website = Mage::app()
                 ->getWebsite($this->getRequest()->getParam('website'));

    $content = $this
                 ->getLayout()
                 ->createBlock('trademe/options')
                 ->setWebsite($website)
                 ->getCsvFile();

    $this->_prepareDownloadResponse(
      'trademe-options-' . $website->getCode() . '.csv',
      $content
    );
  }
}
