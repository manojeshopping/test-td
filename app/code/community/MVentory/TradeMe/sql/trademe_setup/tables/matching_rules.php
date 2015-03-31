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
  'attribute_set_id',
  Varien_Db_Ddl_Table::TYPE_SMALLINT,
  null,
  array(
    'unsigned' => true,
    'nullable' => false,
    'default' => '0'
  ),
  'Attribute Set ID'
);

$table->addColumn(
  'rules',
  Varien_Db_Ddl_Table::TYPE_TEXT,
  null,
  array(
    'nullable' => true,
  ),
  'Matching rule data in JSON format'
);

$table->addIndex(
  $this->getIdxName($tableName, array('attribute_set_id')),
  array('attribute_set_id')
);

$table->addForeignKey(
  $this->getFkName(
    $tableName,
    'attribute_set_id',
    'eav/attribute_set',
    'attribute_set_id'
  ),
  'attribute_set_id',
  $this->getTable('eav/attribute_set'),
  'attribute_set_id',
  Varien_Db_Ddl_Table::ACTION_CASCADE,
  Varien_Db_Ddl_Table::ACTION_CASCADE
);

$table->setComment('TradeMe matching rules');

$connection->createTable($table);
