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

$conn = $this->getConnection();
$res = Mage::getSingleton('core/resource');
$helper = Mage::helper('core');

$table = $res->getTableName('core/config_data');

$select = $conn
  ->select()
  ->from($table, array('config_id', 'value'))
  ->where($conn->prepareSqlCondition(
      $conn->quoteIdentifier(array($table, 'path')),
      array('like' => 'trademe/account%/shipping_types')
    ));

$data = $conn->fetchPairs($select);

$customerId = null;

foreach ($data as $configId => $shippingTypes) {
  try {
    $shippingTypes = $helper->jsonDecode($shippingTypes);
    if ($shippingTypes === null)
      continue;
  }
  catch (Exception $e) {
    continue;
  }

  foreach ($shippingTypes as $shippingTypes)
    if (isset($shippingTypes['settings']))
      foreach ($shippingTypes['settings'] as $settings)
        if (isset($settings['buyer']) && ($customerId = $settings['buyer']))
          break 3;
}

if ($customerId) {
  $this->startSetup();

  $conn->insertOnDuplicate(
    $table,
    [
      'path' => MVentory_TradeMe_Model_Config::_ORDER_CUSTOMER_ID,
      'value' => $customerId
    ],
    ['value' => 'value']
  );

  $this->endSetup();
}
