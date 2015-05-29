<?php

/**
 * @package MVentory/ProductFeed
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

$attrs = array(
  'productfeed_include' => array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'int',
    'input' => 'select',
    'label' => 'Include in product feeds',
    'source' => 'eav/entity_attribute_source_boolean',
    'required' => false,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'is_configurable' => false
  )
);

$this->startSetup();

$this->addAttributes($attrs);

$this->endSetup();
