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

class Magentoguys_Checkoutc_Model_Observer
{
    public function saveQuoteBefore($evt){
        $quote = $evt->getQuote();
        $post = Mage::app()->getFrontController()->getRequest()->getPost();
		if(isset($post['billing']['use_for_shipping'])){
            $var = $post['billing']['use_for_shipping'];
            $quote->setPickup($var);
        }
    }
    public function saveQuoteAfter($evt){
        $quote = $evt->getQuote();
		if($quote->getPickup()){
            $var = $quote->getPickup();
			if(!empty($var)){
                $model = Mage::getModel('checkoutc/quote');
				// $model->deteleByQuote($quote->getId(),'pickup');
                $model->setQuoteId($quote->getId());
                $model->setKey('pickup');
                $model->setValue($var);
				$model->save();
			}
        }
    }
    public function loadQuoteAfter($evt){
        $quote = $evt->getQuote();
		$model = Mage::getModel('checkoutc/quote');
        $data = $model->getByQuote($quote->getId())->getData();
		foreach($data as $key => $value){
            $quote->setData($key,$value);
        }
	}
	
	public function saveOrderAfter($evt){
        $order = $evt->getOrder();
	    $quote = $evt->getQuote();
		if($quote->getValue()){
            $var = $quote->getValue();
			if(!empty($var)){
                $model = Mage::getModel('checkoutc/order');
               // $model->deleteByOrder($order->getId(),'pickup');
                $model->setOrderId($order->getId());
                $model->setKey('pickup');
                $model->setValue($var);
                $order->setPickup($var);
                $model->save();
            }
        }
    }
    public function loadOrderAfter($evt){
        $order = $evt->getOrder();
        $model = Mage::getModel('checkoutc/order');
        $data = $model->getByOrder($order->getId())->getData();
		foreach($data as $key => $value){
            $order->setData($key,$value);
        }
	}
}
