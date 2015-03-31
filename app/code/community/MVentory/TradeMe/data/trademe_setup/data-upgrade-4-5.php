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
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

$conn = $this->getConnection();
$res = Mage::getSingleton('core/resource');

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

  foreach ($shippingTypes as $id => $settings) {
    $settings['shipping_type'] = $id;
    $settings['weight'] = '';

    $_shippingTypes[] = $settings;
  }

  $value = serialize($_shippingTypes);
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
