<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Label
 */
$this->startSetup();

$this->run("
    ALTER TABLE `{$this->getTable('amlabel/label')}` CHANGE `include_sku` `include_sku` TEXT;
");

$this->endSetup();