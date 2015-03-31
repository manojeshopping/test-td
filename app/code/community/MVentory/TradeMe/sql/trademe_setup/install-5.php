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
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

$attrs = array(
  'tm_relist' => array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'int',
    'input' => 'select',
    'label' => 'Allow to list',
    'source' => 'trademe/attribute_source_relist',
    'required' => false,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'is_configurable' => false
  ),

  'tm_avoid_withdrawal' => array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'int',
    'input' => 'select',
    'label' => 'Avoid withdrawal',
    'source' => 'trademe/attribute_source_boolean',
    'required' => false,
    'default' => -1,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'is_configurable' => false
  ),

  'tm_shipping_type' => array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'int',
    'input' => 'select',
    'label' => 'Use free shipping',
    'source' => 'trademe/attribute_source_freeshipping',
    'required' => false,
    'default' => -1,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'visible' => true,
    'is_configurable' => false
  ),

  'tm_allow_buy_now' => array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'int',
    'input' => 'select',
    'label' => 'Allow Buy Now',
    'source' => 'trademe/attribute_source_boolean',
    'required' => false,
    'default' => -1,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'visible' => true,
    'is_configurable' => false
  ),

  'tm_add_fees' => array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'int',
    'input' => 'select',
    'label' => 'Add Fees',
    'source' => 'trademe/attribute_source_addfees',
    'required' => false,
    'default' => -1,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'visible' => true,
    'is_configurable' => false
  ),

  'tm_pickup' => array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'int',
    'input' => 'select',
    'label' => 'Pickup',
    'source' => 'trademe/attribute_source_pickup',
    'required' => false,
    'default' => -1,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'visible' => true,
    'is_configurable' => false
  ),

  'tm_condition' => array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'int',
    'input' => 'select',
    'label' => 'Condition',
    'source' => 'eav/entity_attribute_source_table',
    'required' => false,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'filterable' => true,
    'visible_on_front' => true,
    'is_html_allowed_on_front' => true,

    'option' => array('values' => array('New', 'Near New', 'Used'))
  ),

  'tm_fixedend_limit' => array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'int',
    'label' => '$1 auctions limit',
    'frontend_class' => 'validate-digits',
    'required' => false,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'is_configurable' => false
  )
);

$this->startSetup();

$this->addAttributes($attrs);

$this->createTable('matching_rules');
$this->createTable('auction');

$this->endSetup();
