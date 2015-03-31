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
 */

/**
 * Logging utilities
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Log
{
  const LOG_FILE = 'trademe.log';

  protected static $_objFields = array(
    'auction' => array(
      'listing_id',
      'type',
      'product_id',
      'account_id',
      'listed_at'
    ),
    'product' => array(
      'id',
      'sku'
    )
  );

  public static function debug ($data = null) {
    $backtrace = debug_backtrace();

    $callee = $backtrace[1];

    //$name = (isset($callee['line']) ? '[' . $callee['line'] . '] ' : '')
    //        . $callee['class']
    //        . $callee['type']
    //        . $callee['function'];

    $msg = $callee['function'] . ': ';

    if ($data === null)
      $msg .= 'args: ' . self::_exportVar($callee['args']);
    else if (is_string($data))
      $msg .= $data;
    else
      $msg .= self::_exportArgs($data);

    Mage::log($msg, Zend_Log::DEBUG, self::LOG_FILE);
  }

  protected static function _exportArgs ($args) {
    foreach ($args as $k => &$v)
      $v = $k . ': ' . self::_exportVar($v);

    return implode(', ', $args);
  }

  protected static function _exportVar ($var) {
    switch (gettype($var)) {
      case 'boolean': return $var ? 'true' : 'false';
      case 'integer':
      case 'double': return (string) $var;
      case 'string': return self::_exportString($var);
      case 'array': return self::_exportArray($var);
      case 'object': return self::_exportObject($var);
      case 'resource': return 'resource';
      case 'NULL': return 'null';
    }

    return 'unknown type';
  }

  protected static function _exportString ($str) {
    return '\''
           . str_replace(array("\n", "\r"), array('\n', '\r'), $str)
           . '\'';
  }

  protected static function _exportArray ($arr) {
    foreach ($arr as $k => &$v)
      $v = self::_exportVar($k) . ' => ' . self::_exportVar($v);

    return '[' . implode(', ', $arr) . ']';
  }

  protected static function _exportObject ($obj) {
    switch (true) {
      case $obj instanceof Mage_Catalog_Model_Product:
        return 'Product '
               . self::_exportObjFields($obj, self::$_objFields['product']);
      case $obj instanceof MVentory_TradeMe_Model_Auction:
        return 'Auction '
               . self::_exportObjFields($obj, self::$_objFields['auction']);
      case $obj instanceof Varien_Object:
        return get_class($obj) . ' {ID: ' . $obj->getId() . '}';
    }

    return get_class($obj);
  }

  protected static function _exportObjFields ($obj, $fields) {
    foreach ($fields as $field)
      $data[] = $field . ': ' . self::_exportVar($obj->getData($field));

    return '{' . implode(', ', $data) . '}';
  }
}