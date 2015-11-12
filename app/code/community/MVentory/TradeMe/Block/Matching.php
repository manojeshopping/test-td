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
 * Block for list of category matching rules
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Block_Matching
  extends Mage_Adminhtml_Block_Template {

  protected $_attrs = array();

  protected $_categories = null;
  protected $_usedCategories = array();

  protected function _construct () {
    $attrs = Mage::getResourceModel('catalog/product_attribute_collection')
               ->setAttributeSetFilter($this->_getSetId());

    foreach ($attrs as $attr) {
      if (!$attr->getIsUserDefined())
        continue;

      $type = $attr->getFrontendInput();

      if (!($type == 'select' || $type == 'multiselect'))
        continue;

      $id = $attr->getId();

      $this->_attrs[$id] = array(
        'label' => $attr->getFrontendLabel(),
        'used' => false,
        'used_values' => array()
      );

      $labels[] = $attr->getFrontendLabel();
    }

    unset($attrs);

    $keys = array_keys($this->_attrs);

    array_multisort($labels, SORT_ASC, SORT_STRING, $keys, $this->_attrs);

    $this->_attrs = array_combine($keys, $this->_attrs);

    unset($keys, $labels);

    $options = Mage::getResourceModel('eav/entity_attribute_option_collection')
                 ->setAttributeFilter(array('in' => array_keys($this->_attrs)))
                 ->setStoreFilter();

    foreach ($options as $option)
      $this->_attrs[$option->getAttributeId()]['values'][] = array(
        'id' => $option->getId(),
        'label' => $option->getValue()
      );

    $this->_attrs = array_replace(
      array(
        '-2' => array(
          'label' => $this->__('Select an attribute...'),
          'used' => false,
          'used_values' => array()
        ),
        '-1' => array(
          'label' => $this->__('Categories'),
          'used' => false,
          'used_values' => array(),
          'values' => $this->_getCategoriesForSelect()
        )
      ),
      $this->_attrs
    );

    $api = new MVentory_TradeMe_Model_Api();
    $this->_categories = $api->getCategories();
  }

  /**
   * Prepare layout
   *
   * @return MVentory_TradeMe_Block_Catalog_Product_Attribute_Set_Matching
   */
  protected function _prepareLayout () {
    parent::_prepareLayout();

    $data = array(
      'id' => 'trademe-rule-reset',
      'label' => Mage::helper('trademe')->__('Reset rule')
    );

    $button = $this
                ->getLayout()
                ->createBlock('adminhtml/widget_button')
                ->setData($data);

    $this->setChild('button_rule_reset', $button);

    $data = array(
      'id' => 'trademe-rule-save',
      'class' => 'disabled',
      'label' => Mage::helper('trademe')->__('Save rule')
    );

    $button = $this
                ->getLayout()
                ->createBlock('adminhtml/widget_button')
                ->setData($data);

    $this->setChild('button_rule_save', $button);

    $data = array(
      'id' => 'trademe-rule-categories',
      'label' => Mage::helper('trademe')->__('Select category')
    );

    $button = $this
                ->getLayout()
                ->createBlock('adminhtml/widget_button')
                ->setData($data);

    $this->setChild('button_rule_categories', $button);

    $data = array(
      'id' => 'trademe-rule-ignore',
      'label' => Mage::helper('trademe')->__('Don\'t list on TradeMe')
    );

    $button = $this
      ->getLayout()
      ->createBlock('adminhtml/widget_button')
      ->setData($data);

    $this->setChild('button_rule_ignore', $button);
  }

  protected function _getAttributesJson () {
    return Mage::helper('core')->jsonEncode($this->_attrs);
  }

  protected function _getRules () {
    return Mage::getModel('trademe/matching')->loadBySetId($this->_getSetId());
  }

  protected function _getAddRuleButtonHtml () {
    return $this->getChildHtml('add_rule_button');
  }

  protected function _getUrlsJson () {
    $params = array(
      'type' => MVentory_TradeMe_Block_Categories::TYPE_RADIO
    );

    $categories = $this->getUrl('adminhtml/trademe_categories', $params);

    $params = array(
      'set_id' => $this->_getSetId(),
      'ajax' => true
    );

    $addrule = $this->getUrl('adminhtml/trademe_matching/append/', $params);
    $remove = $this->getUrl('adminhtml/trademe_matching/remove/', $params);
    $reorder = $this->getUrl('adminhtml/trademe_matching/reorder/', $params);

    return Mage::helper('core')->jsonEncode(compact('categories',
                                                    'addrule',
                                                    'remove',
                                                    'reorder'));
  }

  protected function _getUsedCategories () {
    return Mage::helper('core')
      ->jsonEncode(array_unique($this->_usedCategories, SORT_NUMERIC));
  }

  /**
   * Retrieve current attribute set
   *
   * @return Mage_Eav_Model_Entity_Attribute_Set
   */
  protected function _getAttributeSet () {
    return Mage::registry('current_attribute_set');
  }

  /**
   * Retrieve current attribute set ID
   *
   * @return int
   */
  protected function _getSetId () {
    return $this->_getAttributeSet()->getId();
  }

  protected function _prepareRule ($data) {
    $id = $data['id'];
    $default = ($id == MVentory_TradeMe_Model_Matching::DEFAULT_RULE_ID);

    $category = $data['category'];

    $hasCategory = isset($this->_categories[$category]) || $category == -1;

    switch (true) {
      case ($category == -1):
        $category = $this->__('Don\'t list on TradeMe');

        break;

      case $hasCategory:
        $this->_usedCategories[] = (int) $category;
        $category = implode(' - ', $this->_categories[$category]['name']);

        break;

      default:
        $category = $this->__('Category doesn\'t exist anymore');
    }

    $attrs = array();

    foreach ($data['attrs'] as $attr) {
      $_attr = &$this->_attrs[$attr['id']];

      $_attr['used'] = true;

      if (is_array($attr['value'])) {
        $values = array();

        foreach ($attr['value'] as $valueId)
          foreach ($_attr['values'] as $option)
            if ($valueId == $option['id']) {
              $values[] = isset($option['full_label'])
                            ? $option['full_label']
                            : $option['label'];

              $_attr['used_values'][$valueId] = true;

              break;
            }

        $attrs[$_attr['label']] = implode(', ', $values);

        continue;
      }

      $attrs[$_attr['label']] = isset($_attr['values'][$attr['value']])
                                  ? $_attr['values'][$attr['value']]
                                    : '';
    }

    return array(
      'id' => $id,
      'default' => $default,
      'category' => $category,
      'has_category' => $hasCategory,
      'attrs' => $attrs
    );
  }

  /**
   * Prepare categories data to use in dropdown field
   *
   * @return array
   *   Prepared categories data
   */
  protected function _getCategoriesForSelect () {
    $categories = Mage::getResourceModel('catalog/category_collection')
      ->addNameToResult();

    $tree = Mage::getResourceSingleton('catalog/category_tree')
      ->load()
      ->addCollectionData($categories)
      ->getNodeById(Mage_Catalog_Model_Category::TREE_ROOT_ID);

    $categories = array();

    foreach ($tree->getChildren() as $child)
       $this->_getNode($child, $categories);

    return $categories;
  }

  /**
   * Adds prepared category data to the supplied list of categories data.
   * Processes sub-categories recursively.
   *
   * @param Varien_Data_Tree_Node $node
   *   Category node from the categories tree
   *
   * @param array $categories
   *   Prepared list of categories data
   *
   * @param array $fullLabel
   *   List of full category names, e.g Default / Category / Sub-category
   */
  protected function _getNode ($node, &$categories, $fullLabel = array()) {
    $label = $this->htmlEscape($node->getName());

    $fullLabel[] = $label;

    $indent = str_repeat(
      '&nbsp;&nbsp;&nbsp;&nbsp;',
      ($level = $node->getLevel()) ? $level - 1 : 0
    );

    $item['id']  = $node->getId();
    $item['label'] = $indent . $label;
    $item['full_label'] = implode('&nbsp;/&nbsp;', $fullLabel);

    $categories[] = $item;

    if ($node->hasChildren())
      foreach ($node->getChildren() as $child)
        $this->_getNode($child, $categories, $fullLabel);
  }
}
