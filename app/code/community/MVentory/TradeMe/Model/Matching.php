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

/**
 * Model for category matching rules
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Matching
  extends Mage_Core_Model_Abstract
  implements IteratorAggregate {

  const DEFAULT_RULE_ID = 'trademe_default_rule';
  const CATEGORY_ATTR_ID = -1;

  /**
   * Stores map of category ID and its parent IDs
   *
   * @see MVentory_TradeMe_Model_Matching::_getCategoryToParentsMap()
   * @var array
   */
  protected $_categoriesMap = null;

  /**
   * Initialize resource mode
   *
   */
  protected function _construct () {
    $this->_init('trademe/matching');
  }

  public function loadBySetId ($setId, $cleanRules = true) {
    $this
      ->setData('attribute_set_id', $setId)
      ->load($setId, 'attribute_set_id');

    return $cleanRules ? $this->_clean() : $this;
  }

  public function getIterator () {
    return new ArrayIterator($this->getData('rules'));
  }

  public function append ($rule) {
    $all = $this->getData('rules');

    if ($rule['id'] != self::DEFAULT_RULE_ID
        && isset($all[self::DEFAULT_RULE_ID])) {
      $defaultRule = array_pop($all);

      $all[$rule['id']] = $rule;
      $all[self::DEFAULT_RULE_ID] = $defaultRule;
    } else
      $all[$rule['id']] = $rule;

    return $this->setData('rules', $all);
  }

  public function remove ($ruleId) {
    $all = $this->getData('rules');

    unset($all[$ruleId]);

    return $this->setData('rules', $all);
  }

  public function reorder ($ids) {
    $_all = $this->getData('rules');

    $all = array();

    foreach ($ids as $id)
      $all[$id] = $_all[$id];

    if (isset($_all[self::DEFAULT_RULE_ID]))
      $all[self::DEFAULT_RULE_ID] = $_all[self::DEFAULT_RULE_ID];

    return $this->setData('rules', $all);
  }

  public function matchCategory ($product) {
    if (($setId = $product->getAttributeSetId()) === false)
      return false;

    $this->loadBySetId($setId);

    if (!$this->getId())
      return false;

    $_attributes = array(
      self::CATEGORY_ATTR_ID => 'category_ids'
    );

    foreach ($product->getAttributes() as $code => $attribute)
      $_attributes[$attribute->getId()] = $code;

    unset($attribute, $code);

    $categoryId = null;

    $rules = $this->getData('rules');

    foreach ($rules as $rule) {
      foreach ($rule['attrs'] as $attribute) {
        if (!isset($_attributes[$attribute['id']]))
          continue;

        $code = $_attributes[$attribute['id']];

        $productValue = $this->_getValue($product, $code);
        $ruleValue = (array) $attribute['value'];

        if (!count(array_intersect($productValue, $ruleValue)))
          continue 2;
      }

      if (isset($rule['category'])) {
        $categoryId = (int) $rule['category'];

        break;
      }
    }

    if ($categoryId == -1)
      return array(
        'id' => $categoryId,
        'category' => 'Don\'t list on TradeMe'
      );

    if ($categoryId == null && isset($rules[self::DEFAULT_RULE_ID]))
      $categoryId = (int) $rules[self::DEFAULT_RULE_ID]['category'];

    if (!$categoryId)
      return false;

    $api = new MVentory_TradeMe_Model_Api();
    $categories = $api->getCategories();

    if (!isset($categories[$categoryId]))
      return false;

    return array(
      'id' => $categoryId,
      'category' => implode(' / ', $categories[$categoryId]['name'])
    );
  }

  protected function _clean () {
    $attrs = array(
      self::CATEGORY_ATTR_ID => array_keys($this->_getCategoryToParentsMap())
    );

    $_attrs = Mage::getResourceModel('catalog/product_attribute_collection')
               ->setAttributeSetFilter($this->getData('attribute_set_id'));

    foreach ($_attrs as $attr) {
      if (!$attr->getIsUserDefined())
        continue;

      $type = $attr->getFrontendInput();

      if (!($type == 'select' || $type == 'multiselect'))
        continue;

      $allOptions = $attr
                      ->getSource()
                      ->getAllOptions();

      $optionIds = array();

      foreach ($allOptions as $options)
        if ($options['value'])
          $optionIds[] = $options['value'];

      $attrs[$attr->getId()] = $optionIds;
    }

    unset($_attrs);

    $rules = $this->getData('rules');

    $isChanged = false;

    foreach ($rules as $ruleId => &$rule) {
      if ($ruleId == self::DEFAULT_RULE_ID)
        continue;

      foreach ($rule['attrs'] as $n => $attr) {
        $id = $attr['id'];

        //Keep attribute in the rule if it exists in the system, has values
        //and containes one value at least which exists in the attribute
        $keepAttr = isset($attrs[$id])
                    && count($attrs[$id])
                    && count(array_intersect($attr['value'], $attrs[$id]));

        if (!$keepAttr) {
          unset($rule['attrs'][$n]);

          $isChanged = true;
        }
      }

      if (!count($rule['attrs'])) {
        unset($rules[$ruleId]);

        $isChanged = true;
      }
    }

    if ($isChanged)
      $this
        ->setData('rules', $rules)
        ->save();

    return $this;
  }

  /**
   * Get value of attribute by code from supplied product.
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param string $code
   *   Attribute code
   *
   * @return array
   *   Attribute value in the product
   */
  protected function _getValue ($product, $code) {
    if ($code == 'category_ids')
      return ($ids = $product->getCategoryIds())
               ? $this->_expandCategories($ids)
               : array();

    return is_string($value = $product->getData($code))
             ? explode(',', $value)
             : (array) $value;
  }

  /**
   * Expand list of categories IDs with parent categories IDs
   *
   * @param array $categories
   *   List of categories IDs
   *
   * @return array
   *   Expanded list of categories IDs
   */
  protected function _expandCategories ($categories) {
    $map = $this->_getCategoryToParentsMap();

    if (!$map)
      return array();

    //Double array_flip() is used to remove repeating category IDs
    //It's faster than array_unique()
    return array_flip(array_flip(call_user_func_array(
      'array_merge',
      array_intersect_key($map, array_flip($categories))
    )));
  }

  /**
   * Return map of category ID to its parent IDs
   *
   * @return array
   *   Map of category ID to its parent IDs
   */
  protected function _getCategoryToParentsMap () {
    if ($this->_categoriesMap !== null)
      return $this->_categoriesMap;

    $tree = Mage::getResourceSingleton('catalog/category_tree')
      ->load()
      ->getNodeById(Mage_Catalog_Model_Category::TREE_ROOT_ID);

    $this->_categoriesMap = array();

    if (!$tree->hasChildren())
      return $this->_categoriesMap;

    foreach ($tree->getChildren() as $child)
      $this->_addCategoryToMap($child, $this->_categoriesMap);

    return $this->_categoriesMap;
  }

  /**
   * Add category ID and its parent IDs to the map. Processes child categories
   * recursively
   *
   * @param Varien_Data_Tree_Node $category
   *   Category node from the category tree
   *
   * @param array $map
   *   Map of category ID to its parent IDs
   *
   * @param array $parents
   *   List of category's parents IDs
   */
  protected function _addCategoryToMap ($category, &$map, $parents = array()) {
    $id = $category->getId();

    /**
     * Add category ID to its list of parent IDs, thus we don't need to add
     * category ID when we expand list of product categories
     * in _expandCategories() method
     *
     * @see MVentory_TradeMe_Model_Matching::_expandCategories()
     */
    $parents[] = $id;

    $map[$id] = $parents;

    if ($category->hasChildren())
      foreach ($category->getChildren() as $child)
        $this->_addCategoryToMap($child, $map, $parents);
  }
}
