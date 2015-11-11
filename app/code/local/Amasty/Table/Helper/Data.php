<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Table
 */ 
class Amasty_Table_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getAllGroups()
    {
        $customerGroups = Mage::getResourceModel('customer/group_collection')
            ->load()->toOptionArray();

        $found = false;
        foreach ($customerGroups as $group) {
            if ($group['value']==0) {
                $found = true;
            }
        }
        if (!$found) {
            array_unshift($customerGroups, array('value'=>0, 'label'=>Mage::helper('salesrule')->__('NOT LOGGED IN')));
        } 
        
        return $customerGroups;
    }
    
    public function getStatuses()
    {
        return array(
            '0' => $this->__('Inactive'),
            '1' => $this->__('Active'),
        );       
    }
      
    public function getStates()
    {
        $hash = array();
        $hashCountry = $this->getCountries();
        
        $collection = Mage::getResourceModel('directory/region_collection')->getData();

        foreach ($collection as $state){
            $hash[$state['region_id']] = $hashCountry[$state['country_id']] ."/".$state['default_name'];
        }
        asort($hash);
        $hashAll['0'] = 'All';
        $hash = $hashAll + $hash;        
        return $hash;    
    }
        
    public function getCountries()
    {
        $hash = array();
        $countries = Mage::getModel('directory/country')->getCollection()->toOptionArray();

        foreach ($countries as $country){
            if($country['value']){
                $hash[$country['value']] = $country['label'];                
            }
        }
        asort($hash);
        $hashAll['0'] = 'All';
        $hash = $hashAll + $hash; 
        return $hash;    
    } 
   
    public function getTypes()
    {
        $hash = array();
        $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'am_shipping_type');
        if ($attribute->usesSource()) {
            $options = $attribute->getSource()->getAllOptions(false);
        }
        foreach ($options as $option){
            $hash[$option['value']] = $option['label'];    
        }
        asort($hash);
        $hashAll['0'] = 'All';
        $hash = $hashAll + $hash; 
        return $hash;
    }

    /**
     * @param $zip
     * @return array('area', 'district')
     */
    public function getDataFromZip($zip)
    {
        $dataZip = array('area' => '', 'district' => '');

        if (!empty($zip)) {
            $zipSpell = str_split($zip);
            foreach ($zipSpell as $element) {
                if ($element === ' ') {
                    break;
                }
                if (is_numeric($element)) {
                    $dataZip['district'] = $dataZip['district'] . $element;
                } elseif (empty($dataZip['district'])) {
                    $dataZip['area'] = $dataZip['area'] . $element;
                }
            }
        }

        return $dataZip;
    }

    public function _isRateResultRewritten()
    {
        $isRewritten = false;

        $modelName = Mage::getConfig()->getModelClassName('shipping/rate_result');
        if ($modelName == 'Amasty_Table_Model_Shipping_Rate_Result') {
            $isRewritten = true;
        }

        return $isRewritten;
    }
}
