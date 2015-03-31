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
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sales
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Sales observer
 *
 * @category   Mage
 * @package    Mage_Sales
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Magentoguys_Wishlistreminder_Model_Observer
{
    /**
     * Catalog Product After Save (change status process)
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Sales_Model_Observer
     */
    // public function sendWishlistReminderEmail(Varien_Event_Observer $observer)
    // {
		// $model = Mage::getModel('wishlist/wishlist');
		// $collection = $model->getCollection();
		// $wishlistData = $collection->getData();
		// foreach($wishlistData as $data){
			// $customerId = $data['customer_id'];
			// $wishlistId = $data['wishlist_id'];
			
			// $wishlist = Mage::getModel('wishlist/wishlist')->load($wishlistId);
			
		    // $customer = Mage::getModel('customer/customer')->load($customerId);
			// $customerEmail = $customer->getEmail();
			
			// $translate = Mage::getSingleton('core/translate');
			// $translate->setTranslateInline(false);
            // try {
				 // $wishlistBlock = Mage::app()->getLayout()->createBlock('wishlist/share_email_items')->toHtml();	
				 
				 // $emailModel = Mage::getModel('core/email_template');
				 // $emailModel->sendTransactional(
                    // Mage::getStoreConfig('wishlist/email/email_template'),
                    // Mage::getStoreConfig('wishlist/email/email_identity'),
                    // $customerEmail,
                    // null,
                    // array(
                        // 'customer'       => $customer,
                        // 'salable'        => $wishlist->isSalable() ? 'yes' : '',
                        // 'items'          => $wishlistBlock,
                        // 'addAllLink'     => Mage::getUrl('*/shared/allcart', array('code' => $sharingCode)),
                        // 'viewOnSiteLink' => Mage::getUrl('*/shared/index', array('code' => $sharingCode)),
                        // 'message'        => $message
                    // )
                // );
				// $translate->setTranslateInline(true);
			// }
			// catch (Exception $e) {
				// $translate->setTranslateInline(true);

				// Mage::getSingleton('wishlist/session')->addError($e->getMessage());
				// Mage::getSingleton('wishlist/session')->setSharingForm($this->getRequest()->getPost());
				// $this->_redirect('*/*/share');
			// }
			 
		// }
		// return $this;
    // }
    
}
