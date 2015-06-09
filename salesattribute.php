<?php
require_once 'app/Mage.php';
umask(0);
Mage::app('default');
// Add the code you want to execute here:

$c = array (
'entity_type_id'  => 5,         // 11 is the id of the entity model 
'attribute_code'  => 'myorder_customercomment',
'backend_type'    => 'text',     // MySQL-DataType
'frontend_input'  => 'textarea', // Type of the HTML-Form-Field
'is_global'       => '1',
'is_visible'      => '1',
'is_required'     => '0',
'is_user_defined' => '0',
'frontend_label'  => 'Customer Comment',
);
$attribute = new Mage_Eav_Model_Entity_Attribute();
$attribute->loadByCode($c['entity_type_id'], $c['attribute_code'])
->setStoreId(0)
->addData($c);
$attribute->save();
?><?php
require_once 'app/Mage.php';
umask(0);
Mage::app('default');
// Add the code you want to execute here:

$c = array (
'entity_type_id'  => 5,         // 11 is the id of the entity model 
'attribute_code'  => 'myorder_customercomment',
'backend_type'    => 'text',     // MySQL-DataType
'frontend_input'  => 'textarea', // Type of the HTML-Form-Field
'is_global'       => '1',
'is_visible'      => '1',
'is_required'     => '0',
'is_user_defined' => '0',
'frontend_label'  => 'Customer Comment',
);
$attribute = new Mage_Eav_Model_Entity_Attribute();
$attribute->loadByCode($c['entity_type_id'], $c['attribute_code'])
->setStoreId(0)
->addData($c);
$attribute->save();
?>