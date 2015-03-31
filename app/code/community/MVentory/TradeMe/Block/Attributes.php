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
 * @package MVentory/API
 * @copyright Copyright (c) 2014 mVentory Ltd. (http://mventory.com)
 * @license Commercial
 */

/**
 * Product attributes block
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Block_Attributes
 extends Mage_Catalog_Block_Product_View_Attributes {

  /**
   * Prepares data for list of attributes and values for auction description
   *
   * @param array $excludeAttr
   *   Optional array of attribute codes to exclude them from additional
   *   data array
   *
   * @return array
   *   Prepared list of attributes and values which is used in the template
   */
  public function getAdditionalData (array $exclude = array()) {
    $data = array();

    $product = $this->getProduct();
    $attributes = $product->getAttributes();

    foreach ($attributes as $attribute) {
      $code = $attribute->getAttributeCode();

      if (!$attribute->getIsVisibleOnFront() || in_array($code, $exclude))
        continue;

      $values = $product->getData($code);

      if ($values === null || $values === '' || strpos($values, '~') === 0)
        continue;

      $input = $attribute->getFrontendInput();
      $isSelect = $input == 'select' || $input == 'multiselect';

      if ($isSelect) {
        $values = explode(',', $values);

        $source = $attribute->getSource();

        foreach ($values as $value)
          $_values[$value] = $source->getOptionText($value);

        $values = $_values;

        unset($_values);
      } else
        $values = (array) $attribute
                            ->getFrontend()
                            ->getValue($product);

      foreach ($values as $i => $value) {
        $_value = strtolower(str_replace(' ', '', $value));

        if (!$_value || $_value == 'n/a' || $_value == 'n\a' || $_value == '~')
          unset($values[$i]);
      }

      if (!count($values))
        continue;

      if ($input == 'price') {
        foreach ($values as $i => &$value)
          $value = Mage::app()->getStore()->convertPrice($value, true);

        unset($value);
      }

      $data[$code] = array(
        'label' => trim($attribute->getStoreLabel()),
        'value' => implode(', ', $values),
        'code'  => $code
      );
    }

    //Round value of weight attribute or unset if it's 0
    if (isset($data['weight'])) {
      if ($data['weight']['value'] == 0)
        unset($data['weight']);
      else if (is_numeric($data['weight']['value']))
        $data['weight']['value'] = round($data['weight']['value'], 2);
    }

    return $data;
  }
}
