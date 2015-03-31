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

$this->startSetup();

$this->createTable('auction');

$products = Mage::getModel('catalog/product')
  ->getCollection()
  ->addAttributeToSelect('tm_current_account_id')
  ->addAttributeToFilter('type_id', 'simple')
  ->addAttributeToFilter('tm_current_listing_id', array('neq' => ''));

foreach ($products as $product)
  Mage::getModel('trademe/auction')
    ->setData(array(
        'product_id' => $product->getId(),
        'listing_id' => $product['tm_current_listing_id'],
        'account_id' => $product['tm_current_account_id']
      ))
    ->save();

$this->removeAttributes(
  array(
    'tm_listing_id',
    'tm_current_listing_id',
    'tm_account_id',
    'tm_current_account_id'
  ),
  true
);

$this->updateAttributes(
  array('tm_relist' => array('source' => 'trademe/attribute_source_relist'))
);


$this->endSetup();
