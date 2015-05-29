<?php

/**
 * Resource setup model
 *
 * @package MVentory/ProductFeed
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_ProductFeed_Model_Resource_Setup
  extends Mage_Catalog_Model_Resource_Setup
{
  /**
   * Add attributes
   *
   * @param array $attrs
   *   List of attributes codes and parameters
   *
   * @return MVentory_ProductFeed_Model_Resource_Setup
   *   Instance of this class
   */
  public function addAttributes ($attrs) {
    $entityTypeId = $this->getEntityTypeId('catalog_product');
    $setId = $this->getDefaultAttributeSetId($entityTypeId);
    $groupId = $this->getAttributeGroupId($entityTypeId, $setId, 'Default');

    foreach ($attrs as $name => $attr)
      $this
        ->addAttribute($entityTypeId, $name, $attr)
        ->addAttributeToGroup($entityTypeId, $setId, $groupId, $name);

    return $this;
  }

  /**
   * Remove attributes and their data if requested
   *
   * @param array $attrs
   *   List of attributes IDs or codes
   *
   * @param bool $removeData
   *   Remove attribute data
   *
   * @return MVentory_ProductFeed_Model_Resource_Setup
   *   Instance of this class
   */
  public function removeAttributes ($attrs, $removeData = false) {
    $entityTypeId = $this->getEntityTypeId('catalog_product');

    foreach ($attrs as $attr) {
      if ($removeData) {
        $_attr = Mage::getModel('eav/entity_attribute')->loadByCode(
          $entityTypeId,
          $attr
        );

        $this->deleteTableRow(
          $_attr->getBackendTable(),
          'attribute_id',
          $_attr->getAttributeId()
        );
      }

      $this->removeAttribute($entityTypeId, $attr);
    }

    return $this;
  }
}
