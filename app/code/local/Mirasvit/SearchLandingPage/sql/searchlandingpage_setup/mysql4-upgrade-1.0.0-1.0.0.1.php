<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Sphinx Search Ultimate
 * @version   2.3.2
 * @build     1216
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */



$installer = $this;

$sql = "
CREATE TABLE IF NOT EXISTS `{$this->getTable('searchlandingpage/page_store')}` (
    `page_store_id` int(11) NOT NULL AUTO_INCREMENT,
    `page_id` INT(11) NOT NULL,
    `store_id` SMALLINT(5) unsigned NOT NULL,
    CONSTRAINT `slp_ps_page_id` FOREIGN KEY (`page_id`) REFERENCES `{$this->getTable('searchlandingpage/page')}` (`page_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `slp_ps_store_id` FOREIGN KEY (`store_id`) REFERENCES `{$this->getTable('core/store')}` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    KEY `k_slp_ps_store_id` (`store_id`),
    PRIMARY KEY (`page_store_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
";
$installer->run($sql);

$installer->endSetup();
