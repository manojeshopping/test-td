<?php
/**
 * @copyright Amasty 2012
 */

class Amasty_Table_Model_Carrier_Table extends Mage_Shipping_Model_Carrier_Abstract
{
    protected $_code = 'amtable';

    /**
     * Collect rates for this shipping method based on information in $request
     *
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) 
    {
        if (!$this->getConfigData('active')) {
            return false;
        }

        $result = Mage::getModel('shipping/rate_result');

        $collection = Mage::getResourceModel('amtable/method_collection')
            ->addFieldToFilter('is_active', 1)
            ->addStoreFilter($request->getStoreId())
            ->addCustomerGroupFilter($this->getCustomerGroupId($request))
            ->setOrder('pos'); 
                            
        $rates = Mage::getModel('amtable/rate')->findBy($request, $collection);    
        
        $countOfRates = 0; 
        foreach ($collection as $customMethod){
            
            // create new instance of method rate
            $method = Mage::getModel('shipping/rate_result_method');
    
            // record carrier information
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
    
            // record method information
            $method->setMethod($this->_code . $customMethod->getId());
            $method->setMethodTitle(Mage::helper('amtable')->__($customMethod->getName()));
    
            if (isset($rates[$customMethod->getId()]))
            {
					// zw
					$resource = Mage::getSingleton('core/resource');
					$readConnection = $resource->getConnection('core_read');

					$noQtyRates = array();
					$qtyRates = array();
					foreach ($request->getAllItems() as $item) {
						$productId = $item->getProduct()->getId();
						$productQty = $item->getQty();
						$productWeight = $item->getWeight();
						//Mage::log($productId . "|" . $productQty . "|" . $productWeight);
						
						$query = 'SELECT cost_base FROM ' . 'am_table_rate' . ' WHERE state = '
								 . $request->getDestRegionId() . ' and weight_from = ' . $productWeight . ' LIMIT 1';
						$cost_base = $readConnection->fetchOne($query);

						array_push($noQtyRates, $cost_base);
						array_push($qtyRates, $productQty * $cost_base);
					}
					//Mage::log($noQtyRates);
					//Mage::log($qtyRates);
					rsort($qtyRates);
					//Mage::log($qtyRates);
					//$discArray = array(1, 0.8, 0.6, 0.4, 0.4, 0.4, 0.4, 0.4, 0.4, 0.4, 0.4, 0.4, 0.4, 0.4, 0.4, 0.4);
					$discArrayString = Mage::getModel('cms/block')->setStoreId(Mage::app()->getStore()->getId())->load('freight_discount_array')->getContent();
					$discArray = explode(", ", $discArrayString);
					//Mage::log($discArray);
					$adjQtyRates = array();
					for ($i = 0; $i < count($qtyRates); $i++) {
						array_push($adjQtyRates, $qtyRates[$i] * $discArray[$i]);	
					}
					//Mage::log($adjQtyRates);
					//Mage::log("---------------------");
					// zw end
					
                    $method->setCost($rates[$customMethod->getId()]);
                    //$method->setPrice($rates[$customMethod->getId()]);
					$method->setPrice(array_sum($adjQtyRates)); 

                    // add this rate to the result
                    $result->append($method);
                    $countOfRates++;        
            }

        }
        
        if (($countOfRates == 0) && ($this->getConfigData('showmethod') == 1)){
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $result->append($error);
        }        
        
        return $result;
    } 


    public function getAllowedMethods()
    {
        $collection = Mage::getResourceModel('amtable/method_collection')
                ->addFieldToFilter('is_active', 1)
                ->setOrder('pos');
        $arr = array();
        foreach ($collection as $method){
            $methodCode = 'amtable'.$method->getMethodId();
            $arr[$methodCode] = $method->getName();    
        }  
                
        return $arr;
    }
    
    public function getCustomerGroupId($request)
    {
        $allItems = $request->getAllItems();
        if (!$allItems){
            return 0;
        }
        foreach ($allItems as $item)
        {
            return $item->getProduct()->getCustomerGroupId();             
        }

    }
}
