<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Pgrid
 */
class Amasty_Pgrid_Block_Adminhtml_Catalog_Product_Grid_Renderer_Reckon extends Amasty_Pgrid_Block_Adminhtml_Catalog_Product_Grid_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$value =  $row->getData($this->getColumn()->getIndex());
		if ($this->getColumn()->getIndex() == "reckon_price_equal") {
			if ($value) {
				$value = "Yes";
			} else {
				$value = "No";
			}
		}
		if ($this->getColumn()->getIndex() == "reckon_price") {
			$value = Mage::helper('core')->currency($value, true, false);
		}
        return '<div style="color:#45A1E4;text-align:right;width:100%;">'.$value.'</div>';
    }
}