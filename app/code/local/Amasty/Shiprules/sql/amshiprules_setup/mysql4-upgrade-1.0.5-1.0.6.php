<?php
/**
* @copyright Amasty.
*/
$this->startSetup();

$this->run("
 ALTER TABLE `{$this->getTable('amshiprules/rule')}` ADD `ship_max` decimal(12,2) unsigned NOT NULL default '0' AFTER `rate_max`;  
 ALTER TABLE `{$this->getTable('amshiprules/rule')}` ADD `ship_min` decimal(12,2) unsigned NOT NULL default '0' AFTER `rate_min`;  
"); 
  
$this->endSetup();