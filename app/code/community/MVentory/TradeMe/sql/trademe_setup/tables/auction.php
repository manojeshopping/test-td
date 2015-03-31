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

$connection = $this->getConnection();
$table = $connection->newTable($tableName);

$table->addColumn(
  'id',
  Varien_Db_Ddl_Table::TYPE_INTEGER,
  null,
  array(
    'identity' => true,
    'unsigned' => true,
    'nullable' => false,
    'primary' => true,
  ),
  'Primary key'
);

$table->addColumn(
  'product_id',
  Varien_Db_Ddl_Table::TYPE_INTEGER,
  null,
  array(
    'unsigned' => true,
    'nullable' => false,
  ),
  'Product ID'
);

$table->addColumn(
  'type',
  Varien_Db_Ddl_Table::TYPE_SMALLINT,
  null,
  array(
    'unsigned' => true,
    'nullable' => false,
    'default' => MVentory_TradeMe_Model_Config::AUCTION_NORMAL,
  ),
  'Auction type'
);

$table->addColumn(
  'listing_id',
  Varien_Db_Ddl_Table::TYPE_TEXT,
  30,
  array(
    'nullable' => false,
    'default' => MVentory_TradeMe_Model_Config::AUCTION_NORMAL,
  ),
  'Listing ID'
);

$table->addColumn(
  'account_id',
  Varien_Db_Ddl_Table::TYPE_TEXT,
  30,
  array(
    'nullable' => false
  ),
  'Account ID'
);

$table->addColumn(
  'listed_at',
  Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
  null,
  array(
    'nullable' => false
  ),
  'Time of listing'
);

$table->addForeignKey(
  $this->getFkName(
    $tableName,
    'product_id',
    'catalog/product',
    'entity_id'
  ),
  'product_id',
  $this->getTable('catalog/product'),
  'entity_id',
  Varien_Db_Ddl_Table::ACTION_CASCADE,
  Varien_Db_Ddl_Table::ACTION_CASCADE
);

$table->setComment('TradeMe auctions');

$connection->createTable($table);
