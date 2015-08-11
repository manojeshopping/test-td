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
 * Controller for category mapping rules
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_MatchingController
  extends Mage_Adminhtml_Controller_Action {

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
   * Append rule action
   */
  public function appendAction () {
    $request = $this->getRequest();

    $setId =  $request->getParam('set_id');

    $rule = $request->getParam('rule');

    if (!$rule)
      return 0;

    $rule = Mage::helper('core')->jsonDecode($rule);

    Mage::getModel('trademe/matching')
      ->loadBySetId($setId, false)
      ->append($rule)
      ->save();

    echo 1;
  }

  /**
   * Append rule action
   */
  public function removeAction () {
    $request = $this->getRequest();

    $setId = $request->getParam('set_id');
    $ruleId = $request->getParam('rule_id');

    if (!($setId && $ruleId))
      return 0;

    Mage::getModel('trademe/matching')
      ->loadBySetId($setId, false)
      ->remove($ruleId)
      ->save();

    echo 1;
  }

  /**
   * Reorder rules action
   */
  public function reorderAction () {
    $request = $this->getRequest();

    $setId = $request->getParam('set_id');
    $ids = $request->getParam('ids');

    if (!($setId && $ids))
      return 0;

    Mage::getModel('trademe/matching')
      ->loadBySetId($setId, false)
      ->reorder($ids)
      ->save();

    echo 1;
  }
}
