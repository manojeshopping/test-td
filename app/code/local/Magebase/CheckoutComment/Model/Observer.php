<?php
/**
 * Magebase
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @category    Magebase
 * @package     Magebase_CheckoutComment
 * @copyright   Copyright (c) 2011 Magebase. (http://magebase.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Magebase_CheckoutComment_Model_Observer
{
    public function saveComment($observer) {
        
        $enable_comments = Mage::helper('checkoutcomment')->getConfig('enable_comment');
		
        if ($enable_comments)	{

            $orderComment = $observer->getEvent()->getRequest()->getPost('myorder_customercomment');

            $orderComment = trim($orderComment);

			if ($orderComment != "") {
				Mage::getModel('checkout/session')->setMyCustomerOrderComment($orderComment);
                // prepend a heading
                // $orderComment = Mage::helper('checkoutcomment')->__("Customer Order Comment:\n").$orderComment;
                
               // $observer->getEvent()->getQuote()->getShippingAddress()->setMyorderCustomercomment($orderComment)->save();
            }
        }

    }
	
	
	public function saveCustomerOrderComment($observer) {
		
		$customerOrderComment = Mage::getModel('checkout/session')->getMyCustomerOrderComment();
		
		$order = $observer->getOrder();
		
		$SaleaOrderAdd=Mage::getModel('sales/order')->load($order->getId());       
        $SaleaOrderAdd->setData('myorder_customercomment',$customerOrderComment);
        $SaleaOrderAdd->getResource()->saveAttribute($SaleaOrderAdd, 'myorder_customercomment');
		
		return $SaleaOrderAdd;
		
	}
	
	
}