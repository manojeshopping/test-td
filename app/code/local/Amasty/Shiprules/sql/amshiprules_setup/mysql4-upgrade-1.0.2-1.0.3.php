<?php
/**
* @copyright Amasty.
*/
$this->startSetup();

$this->run("
 ALTER TABLE `{$this->getTable('amshiprules/rule')}` ADD `coupon` varchar(255) AFTER `methods`;  
"); 

$this->endSetup();