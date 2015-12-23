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

$this->startSetup();

$this
  ->getConnection()
  ->addColumn(
      $this->getTable('trademe/auction'),
      'distinction_hash',
      [
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => '32',
        'nullable' => false,
        'comment' => 'Distinction hash',
        'after' => 'type'
      ]
    );

$this->endSetup();
