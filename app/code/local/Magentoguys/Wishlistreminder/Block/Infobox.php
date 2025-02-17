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
 * @package     Mage_Wishlist
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Wishlist block customer items
 *
 * @category   Mage
 * @package    Mage_Wishlist
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Magentoguys_Wishlistreminder_Block_Infobox extends Mage_Wishlist_Block_Abstract
{
	protected $_customerId;
    /**
     * Initialize template
     *
     */
	
		
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('wishlistreminder/email/infobox.phtml');
    }
	
	// public function setCustomerId($customerId){
		
		// // if(is_null($this->_customerId)){
			// $this->_customerId = $customerId;
		// // }
	// }
	
	public function getCustomerId(){
	
		return Mage::helper('wishlistreminder')->getCustomerId();
	}

	
	public function getWishlistItems(){
		$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($this->getCustomerId(), true);
		$wishListItemCollection = $wishlist->getItemCollection();
		return $wishListItemCollection;
	}
	
	public function getProductUrl($product, $additional = array())
    {
        $additional['_store_to_url'] = true;
        return parent::getProductUrl($product, $additional);
    }
	
	public function getAddToCartUrl($product, $additional = array())
    {
        $additional['nocookie'] = 1;
        $additional['_store_to_url'] = true;
        return parent::getAddToCartUrl($product, $additional);
    }
	
	public function hasDescription($item)
    {
        $hasDescription = parent::hasDescription($item);
        if ($hasDescription) {
            return ($item->getDescription() !== Mage::helper('wishlist')->defaultCommentString());
        }
        return $hasDescription;
    }

}
