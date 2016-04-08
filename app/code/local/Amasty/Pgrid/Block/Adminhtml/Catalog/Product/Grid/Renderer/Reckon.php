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

		$attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $this->getColumn()->getIndex());
		$options = array();
		foreach ($attribute->getSource()->getAllOptions(true, true) as $option) {
			$id = $option['value'];
			if ($id == $value) {
				$value = $option['label'];
				break;
			}
		}
		
		if ($this->getColumn()->getIndex() == "reckon_price") {
			$value = Mage::helper('core')->currency($value, true, false);
		}
        return '<div style="color:#45A1E4;text-align:right;width:100%;">'.$value.'</div>';
    }
}