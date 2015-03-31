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
 * TradeMe product API
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Product_Api extends MVentory_API_Model_Product_Api
{
  public function submit ($productId, $data) {
    $product = $this->_getProduct($productId, null, 'id');

    if (is_null($product->getId()))
      $this->_fault('product_not_exists');

    $match = Mage::getModel('trademe/matching')->matchCategory($product);

    if (!(isset($match['id']) && $match['id'] > 0))
      $this->_fault('unable_to_match_category');

    $connector = new MVentory_TradeMe_Model_Api();

    $result = $connector->send(
      $product,
      $match['id'],
      $data['account_id']
    );

    if (is_int($result))
      Mage::getModel('trademe/auction')
        ->setData(array(
            'product_id' => $product->getId(),
            'listing_id' => $result,
            'account_id' => $data['account_id']
          ))
        ->save();

    $_result = $this->fullInfo($productId, 'id');

    if (!is_int($result))
      $_result['tm_error'] = $result;

    return $_result;
  }
}