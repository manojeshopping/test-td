<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Cms
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * CMS block model
 *
 * @method Mage_Cms_Model_Resource_Block _getResource()
 * @method Mage_Cms_Model_Resource_Block getResource()
 * @method string getTitle()
 * @method Mage_Cms_Model_Block setTitle(string $value)
 * @method string getIdentifier()
 * @method Mage_Cms_Model_Block setIdentifier(string $value)
 * @method string getContent()
 * @method Mage_Cms_Model_Block setContent(string $value)
 * @method string getCreationTime()
 * @method Mage_Cms_Model_Block setCreationTime(string $value)
 * @method string getUpdateTime()
 * @method Mage_Cms_Model_Block setUpdateTime(string $value)
 * @method int getIsActive()
 * @method Mage_Cms_Model_Block setIsActive(int $value)
 *
 * @category    Mage
 * @package     Mage_Cms
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Magentoguys_Dailybackup_Model_Observer extends Mage_Core_Model_Abstract
{
    
	public function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}
	
    public function backup()
	{

		//get all the older db backup files in an array
		$filelist = array();
		if ($handle = opendir(Mage::getBaseDir('var') . DS . "backup" . DS)) {
			while ($entry = readdir($handle)) {
				if ($this->endsWith($entry, "_db.gz")) {
					$filelist[] = $entry;
				}
			}
			closedir($handle);
		}

		try {
			//create the db backup
			$backupDb = Mage::getModel('backup/db');
			$backup   = Mage::getModel('backup/backup')
				->setTime(time())
				->setType('db')
				->setPath(Mage::getBaseDir("var") . DS . "backup");

			$backupDb->createBackup($backup);

			//delete all older db backup files we found
			foreach((array)$filelist as $fileToDelete) {
					unlink(Mage::getBaseDir("var") . DS . "backup" . DS . $fileToDelete);
			}

			return Mage::helper('core')->__('Backup successfully created');

		} catch (Exception  $e) {
			Mage::logException($e);
		}

		return $this;
	}
}
