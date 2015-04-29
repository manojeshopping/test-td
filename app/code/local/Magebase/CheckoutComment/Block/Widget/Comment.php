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
 * @copyright   Copyright (c) 2012 Magebase. (http://magebase.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Magebase_CheckoutComment_Block_Widget_Comment extends Magebase_CheckoutComment_Block_Widget_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('magebase/checkoutcomment/widget/comment.phtml');
    }

    public function isEnabled() {

        return $this->getConfig('enable_comment');
    }
}