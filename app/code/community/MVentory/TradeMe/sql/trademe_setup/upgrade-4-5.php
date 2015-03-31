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
  'tm_fixedend_limit' => array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'int',
    'label' => '$1 auctions limit',
    'frontend_class' => 'validate-digits',
    'required' => false,
    'user_defined' => true,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'is_configurable' => false
  )
);

$this->startSetup();

$this->addAttributes($attrs);

$this->endSetup();
