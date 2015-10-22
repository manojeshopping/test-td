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

foreach ($data as $configId => &$value) {
  if (($shippingTypes = unserialize($value)) === false) {
    unset($data[$configId]);

    continue;
  }

  $_shippingTypes = [];

  foreach ($shippingTypes as $settings) {
    $shippingTypeId = $settings['shipping_type'];
    $weight = (int) $settings['weight'];

    unset(
      $settings['shipping_type'],
      $settings['weight'],
      $settings['minimal_price']
    );

    if ($weight > 0)
      $_shippingTypes[$shippingTypeId]['condition'] = 'weight';
    else
      $_shippingTypes[$shippingTypeId]['condition'] = '';

    $_shippingTypes[$shippingTypeId]['settings'][$weight] = $settings;
  }

  $value = $helper->jsonEncode($_shippingTypes);;
}

unset($value);

if ($data) {
  $configIdent = $conn->quoteIdentifier(array($table, 'config_id'));

  $this->startSetup();

  foreach ($data as $configId => $value)
    $conn->update(
      $table,
      array('value' => $value),
      $conn->prepareSqlCondition(
        $configIdent,
        $configId
      )
    );

  $this->endSetup();
}
