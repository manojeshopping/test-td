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
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

$helper = Mage::helper('trademe');

foreach (Mage::getResourceModel('trademe/auction_collection') as $auction) {
  $auction['distinction_hash'] = $helper->getHash([
    'title' => Mage::getModel('catalog/product')
      ->load($auction['product_id'])
      ->getName()
  ]);

  $auction->save();
}
