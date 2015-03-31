<?php
/**
* @copyright Amasty.
*/
$this->startSetup();

$this->run("
 ALTER TABLE `{$this->getTable('amshiprules/rule')}` ADD `handling` double NOT NULL AFTER `rate_max`;  
"); 

$this->endSetup();