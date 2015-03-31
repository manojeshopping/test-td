<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License BY-NC-ND.
 * By Attribution (BY) - You can share this file unchanged, including
 * this copyright statement.
 * Non-Commercial (NC) - You can use this file for non-commercial activities.
 * A commercial license can be purchased separately from mventory.com.
 * No Derivatives (ND) - You can make changes to this file for your own use,
 * but you cannot share or redistribute the changes.  
 *
 * See the full license at http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * @package MVentory/API
 * @copyright Copyright (c) 2014 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Categories block
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Block_Matching_Categories
  extends Mage_Adminhtml_Block_Catalog_Category_Abstract {

  /**
   * Get JSON of a tree node or an associative array
   *
   * @param Varien_Data_Tree_Node|array $node
   * @param int $level
   * @return string
   */
  protected function _getNode ($node) {
    $item['text'] = $this->htmlEscape($node->getName());
    $item['id']  = $node->getId();

    if ($node->hasChildren()) {
      $item['children'] = array();

      foreach ($node->getChildren() as $child)
        $item['children'][] = $this->_getNode($child);
    }

    return $item;
  }

  public function getTreeJson () {
    $root = $this->_getNode($this->getRoot());
    $root = isset($root['children']) ? $root['children'] : array();

    return Mage::helper('core')->jsonEncode($root);
  }
}
