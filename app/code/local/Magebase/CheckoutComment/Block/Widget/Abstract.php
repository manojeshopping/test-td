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

/**
 * Checkout comment widget modelled on other Magento built in widgets like dob.
 */
class Magebase_CheckoutComment_Block_Widget_Abstract extends Mage_Core_Block_Template
{
    public function getConfig($key)
    {
        return $this->helper('checkoutcomment')->getConfig($key);
    }

    public function getFieldIdFormat()
    {
        if (!$this->hasData('field_id_format')) {
            $this->setData('field_id_format', '%s');
        }
        return $this->getData('field_id_format');
    }

    public function getFieldNameFormat()
    {
        if (!$this->hasData('field_name_format')) {
            $this->setData('field_name_format', '%s');
        }
        return $this->getData('field_name_format');
    }

    public function getFieldId($field)
    {
        return sprintf($this->getFieldIdFormat(), $field);
    }

    public function getFieldName($field)
    {
        return sprintf($this->getFieldNameFormat(), $field);
    }
}
