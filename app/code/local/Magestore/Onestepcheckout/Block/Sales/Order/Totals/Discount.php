<?php 
class Magestore_Onestepcheckout_Block_Sales_Order_Totals_Discount extends Mage_Sales_Block_Order_Totals
{
    public function initTotals()
    {
		// var_dump($this->discountAmount());
		if($this->discountAmount() != 0){
			$total = new Varien_Object();
			$total->setCode('onestepcheckoutdiscount');
			$total->setValue(-$this->discountAmount());
			$total->setBaseValue(-$this->baseDiscountAmout());
			$total->setLabel('Discount(Admin)');
			$parent = $this->getParentBlock();
			$parent->addTotal($total,'subtotal');
		}
	}
	
	public function discountAmount() {
		$order = $this->getParentBlock()->getOrder();
		// $order->setData('onestepcheckout_discount_amount',123)->save();
		// var_dump($order->getData());
		$discountAmount = $order->getOnestepcheckoutDiscountAmount();
		return $discountAmount;
	}
	
	public function baseDiscountAmout(){
		$order = $this->getParentBlock()->getOrder();
		$discountAmount = $order->getOnestepcheckoutDiscountAmount();
		$orderCurrencyCode = $order->getOrderCurrency()->getCurrencyCode();
		$baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
		if ($baseCurrencyCode != $currentCurrencyCode) {
			$allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
			$rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrencyCode, array_values($allowedCurrencies));
			return $discountAmount/$rates[$orderCurrencyCode];
		}else{
			return $discountAmount;
		}
	}
}