<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Table
 */ 
class Amasty_Table_Block_Adminhtml_Rate_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'amtable';
        $this->_controller = 'adminhtml_rate';
        
        $this->_removeButton('back'); 
        $this->_removeButton('reset'); 
        $this->_removeButton('delete'); 
    }

    public function getHeaderText()
    {
        return Mage::helper('amtable')->__('Rate Configuration');
    }
}