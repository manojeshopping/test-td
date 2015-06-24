<?php
$installer = $this;
$installer->startSetup();

$entityTypeId     = $installer->getEntityTypeId('catalog_category');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$installer->addAttribute('catalog_category', 'related_categories',  array(
    'type'     => 'varchar',
	'group'    => 'Related Categories',
    'label'    => 'Related Category',
    'input'    => 'multiselect',
	'global'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'source'            => 'attrib/attribute_source_provider',
	'backend'           => 'attrib/attribute_backend_provider',
    'visible'           => true,
    'required'          => false,
	'visible_on_front' => true,
    'user_defined'      => false,
    'default'           => 0
));

$installer->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'related_categories',
    '11'

//last Magento's attribute position in General tab is 10
);


$installer->endSetup();
?>
