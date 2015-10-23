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
 * TradeMe сategories controller
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_CategoriesController
  extends Mage_Adminhtml_Controller_Action
{

  protected function _construct() {
    $this->setUsedModuleName('MVentory_TradeMe');
  }

  /**
   * Return table with TradeMe categories
   *
   * @return null
   */
  public function indexAction () {
    $request = $this->getRequest();

    if (!is_array($ids = $request->getParam('selected_categories')))
      $ids = array();

    if (!$type = $request->getParam('type'))
      $type = MVentory_TradeMe_Block_Categories::TYPE_CHECKBOX;

    $body = $this
      ->getLayout()
      ->createBlock('trademe/categories')
      ->setTemplate('trademe/categories.phtml')
      //Set selected categories
      ->setSelectedCategories($ids)
      ->setInputType($type)
      ->toHtml();

    $this
      ->getResponse()
      ->setBody($body);
  }

  /**
   * Temporarily allow access for all users
   */
  protected function _isAllowed() {
    return true;
  }
}
