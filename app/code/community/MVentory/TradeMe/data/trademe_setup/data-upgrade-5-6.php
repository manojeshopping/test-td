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
  'tm_relist',
  'tm_avoid_withdrawal',
  'tm_shipping_type',
  'tm_allow_buy_now',
  'tm_add_fees',
  'tm_pickup',
  'tm_condition',
  'tm_fixedend_limit'
);

$entityTypeId = $this->getEntityTypeId(Mage_Catalog_Model_Product::ENTITY);
$setIds = $this->getAllAttributeSetIds($entityTypeId);

$this->startSetup();

foreach ($setIds as $setId) {
  $this->addAttributeGroup($entityTypeId, $setId, 'TM');
  $groupId = $this->getAttributeGroupId($entityTypeId, $setId, 'TM');

  foreach ($attrs as $attr)
    $this->addAttributeToGroup($entityTypeId, $setId, $groupId, $attr);
}

$this->updateAttributes(array(
  'tm_condition' => array('user_defined' => false),
  'tm_fixedend_limit' => array('user_defined' => false)
));

$this->endSetup();
