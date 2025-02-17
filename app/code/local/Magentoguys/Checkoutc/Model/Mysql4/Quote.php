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
 * @category    Mage
 * @package     Mage_Cms
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Magentoguys_Checkoutc_Model_Mysql4_Quote extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct()
    {   
        $this->_init('checkoutc/checkoutc_quote', 'id');
    }
	
	public function getByQuote(Magentoguys_Checkoutc_Model_Quote $custommoduleObj,$quoteId)
	{
		$select = $this->_getReadAdapter()->select()
		->from($this->getMainTable())

		//MVENTORY: remove $this->getMainTable part
		//->where($this->getMainTable .'quote_id'.'=?',$quoteId);
		->where('quote_id'.'=?',$quoteId);

		$customModuleId = $this->_getReadAdapter()->fetchOne($select);
		if($customModuleId)
			$this->load($custommoduleObj,$customModuleId);
		return $this;	
	
	}
	
	public function deteleByQuote(Magentoguys_Checkoutc_Model_Quote $custommoduleObj,$quoteId,$key)
	{
		$select = $this->_getReadAdapter()->select()
		->from($this->getMainTable())

		//MVENTORY: remove $this->getMainTable part
		//->where($this->getMainTable .'quote_id'.'=?',$quoteId);
		->where('quote_id'.'=?',$quoteId);
		
		$customModuleId = $this->_getReadAdapter()->fetchOne($select);
		
		if($customModuleId)
			$this->delete($custommoduleObj,$customModuleId);
		return $this;	
	
	}

}
