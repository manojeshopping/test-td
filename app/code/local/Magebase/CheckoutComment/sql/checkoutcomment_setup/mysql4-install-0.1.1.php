<?php

$installer = $this;
$installer->startSetup();
$attribute  = array(
'type'          => 'varchar',
'backend_type'  => 'varchar',
'frontend_input' => 'varchar',
'is_user_defined' => true,
'label'         => 'Customer Comment',
'visible'       => true,
'required'      => false,
'user_defined'  => false,
'searchable'    => false,
'filterable'    => false,
'comparable'    => false,
'default'       => ''
);
$installer->addAttribute("order", "myorder_customercomment", $attribute);
$installer->addAttribute("quote", "myorder_customercomment", $attribute);
$installer->endSetup();
