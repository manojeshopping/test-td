<?php
/**
 * @copyright   Copyright (c) 2009-2012 Amasty (http://www.amasty.com)
 */
class Amasty_Table_Model_Rate extends Mage_Core_Model_Abstract
{
    const MAX_LINE_LENGTH  = 50000;
    const COL_NUMS         = 19;
	const HIDDEN_COLUMNS    = 2;
    const BATCH_SIZE       = 50000;
    const COUNTRY          = 0;
    const STATE            = 1;
    const ZIP_FROM         = 3;
    const NUM_ZIP_FROM     = 18;
    const ZIP_TO           = 4;
    const NUM_ZIP_TO       = 19;
    const PRICE_TO         = 6;
    const WEIGHT_TO        = 8;
    const QTY_TO           = 10;
    const SHIPPING_TYPE    = 11;
	const ALGORITHM_SUM    = 0;
    const ALGORITHM_MAX    = 1;
    const ALGORITHM_MIN    = 2;

    public function _construct()
    {
        parent::_construct();
        $this->_init('amtable/rate');
    }

    public function findBy($request, $collection)
    {
        if (!$request->getAllItems()) {
            return array();
        }

        if($collection->getSize() == 0)
        {
            return array();
        }

        $methodIds = array();
        foreach ($collection as $method)
        {
            $methodIds[] = $method -> getMethodId();

        }
        // calculate price and weight
        $allowFreePromo = Mage::getStoreConfig('carriers/amtable/allow_promo');
        $ignoreVirtual   = Mage::getStoreConfig('carriers/amtable/ignore_virtual');

        $items = $request->getAllItems();
        $shippingTypes = array();
        $shippingTypes[] = 0;
        /*foreach($items as $item)
        {
            // if attribute isn't load to product
            $product = Mage::getModel('catalog/product')->load($item->getProduct()->getEntityId());
            if ($product->getAmShippingType()){
                $shippingTypes[] = $product->getAmShippingType();                
            } else {
               $shippingTypes[] = 0; 
            }
        }
        
        $shippingTypes = array_unique($shippingTypes);*/
        $allCosts = array();

        $allRates = $this->getResourceCollection();
        $allRates->addMethodFilters($methodIds);
        $ratesTypes = array();

        foreach ($allRates as $singleRate){
            $ratesTypes[$singleRate->getMethodId()][]= $singleRate->getShippingType();
        }

        $intersectTypes = array();

        foreach ($ratesTypes as $key => $value){
            $intersectTypes[$key] = array_intersect($shippingTypes,$value);
            arsort($intersectTypes[$key]);
            $methodIds = array($key);
            $allTotals =  $this->calculateAllTotals($request, $ignoreVirtual, $allowFreePromo,'0');

            foreach ($items as $item){
                $totals = $this->calculateTotals($request, $ignoreVirtual, $allowFreePromo,$item);

                if ($allTotals['qty'] > 0 && (!Mage::getStoreConfig('carriers/amtable/dont_split') || $allTotals['qty'] == $totals['qty'])) {


                    $allTotals['not_free_price'] -= $totals['not_free_price'];
                    $allTotals['not_free_weight'] -= $totals['not_free_weight'];
                    $allTotals['not_free_qty'] -= $totals['not_free_qty'];
                    $allTotals['qty'] -= $totals['qty'];

                    $allRates = $this->getResourceCollection();
                    $allRates->addAddressFilters($request);
                    $allRates->addTotalsFilters($totals,'0');
                    $allRates->addMethodFilters($methodIds);
                    foreach($this->calculateCosts($allRates, $totals, $request,$item) as $key => $cost){

                        if (!empty($allCosts[$key])){
                            $allCosts[$key] += $cost;
                        }  else {
                            $allCosts[$key] = $cost;
                        }
                    }
                }
            }


        }


        return $allCosts;
    }

    protected function calculateCosts($allRates, $totals, $request,$item)
    {
        $shippingFlatParams  =  array('country', 'state', 'city');
        $shippingRangeParams =  array('price', 'qty', 'weight');

        $minCounts = array();   // min empty values counts per method
        $results   = array();
        foreach ($allRates as $rate){

            $rate = $rate->getData();

            $emptyValuesCount = 0;

            if(empty($rate['shipping_type'])){
                $emptyValuesCount++;
            }

            foreach ($shippingFlatParams as $param){
                if (empty($rate[$param])){
                    $emptyValuesCount++;
                }
            }

            foreach ($shippingRangeParams as $param){
                if ((ceil($rate[$param . '_from'])== 0) && (ceil($rate[$param . '_to'])== 999999)) {
                    $emptyValuesCount++;
                }
            }

            if (empty($rate['zip_from']) && empty($rate['zip_to']) ){
                $emptyValuesCount++;
            }

            if (!$totals['not_free_price'] && !$totals['not_free_qty'] && !$totals['not_free_weight']){
                $cost = 0;
            }
            else {
                $cost =  $rate['cost_base'] +  $totals['not_free_price'] * $rate['cost_percent'] / 100 + $totals['not_free_qty'] * $rate['cost_product'] + $totals['not_free_weight'] * $rate['cost_weight'];
                $cost =  $cost * $totals['not_free_qty'];
            }

            $id   = $rate['method_id'];
            if ((empty($minCounts[$id]) && empty($results[$id])) || ($minCounts[$id] > $emptyValuesCount) || (($minCounts[$id] == $emptyValuesCount) && ($cost > $results[$id]))){
                $minCounts[$id] = $emptyValuesCount;
                $results[$id]   =  $cost;
            }

        }
        return $results;
    }

    protected function calculateTotals($request, $ignoreVirtual, $allowFreePromo, $item)
    {
        $totals = $this->initTotals();

        $newItems = array();

        //reload child items 

        $isCalculateLater = array();



        // if ($item->getParentItemId())
            // continue;

        if ($ignoreVirtual && $item->getProduct()->isVirtual())
            continue;



        if ($item->getHasChildren()) {
            $qty = 0;
            $notFreeQty =0;
            $price = 0;
            $weight = 0;
            foreach ($item->getChildren() as $child) {

                $qty        +=  $child->getQty();
                $notFreeQty += ($qty - $this->getFreeQty($child, $allowFreePromo));
                $price  += $child->getPrice() * $child->getQty();
                $weight += $child->getWeight() * $qty;
                $totals['tax_amount']       += $child->getBaseTaxAmount() + $child->getBaseHiddenTaxAmount();
                $totals['discount_amount']  += $child->getBaseDiscountAmount();
            }

            if ($item->getProductType() == 'bundle'){
                $qty        = $item->getQty();

                if ($item->getProduct()->getWeightType() == 1){
                    $weight  = $item->getWeight();
                }

                if ($item->getProduct()->getPriceType() == 1){
                    $price   = $item->getPrice();
                }

                if ($item->getProduct()->getSkuType() == 1){
                    $totals['tax_amount']       += $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount();
                    $totals['discount_amount']  += $item->getBaseDiscountAmount();
                }

                $notFreeQty = ($qty - $this->getFreeQty($item, $allowFreePromo));
                $totals['qty']              += $qty;
                $totals['not_free_qty']     += $notFreeQty;
                $totals['not_free_price'] += $price * $notFreeQty;
                $totals['not_free_weight'] += $weight * $notFreeQty;

            }

            if ($item->getProductType() == 'configurable'){
                $qty     = $item->getQty();
                $price   = $item->getPrice();
                $weight  = $item->getWeight();
                $notFreeQty = ($qty - $this->getFreeQty($item, $allowFreePromo));
                $totals['qty']              += $qty;
                $totals['not_free_qty']     += $notFreeQty;
                $totals['not_free_price'] += $price * $notFreeQty;
                $totals['not_free_weight'] += $weight * $notFreeQty;
                $totals['tax_amount']       += $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount();
                $totals['discount_amount']  += $item->getBaseDiscountAmount();
            }


        } else {
            $qty        = $item->getQty();
            $notFreeQty = ($qty - $this->getFreeQty($item, $allowFreePromo));
            $totals['not_free_price'] += $item->getBasePrice() * $notFreeQty;
            $totals['not_free_weight'] += $item->getWeight() * $notFreeQty;
            $totals['qty']              += $qty;
            $totals['not_free_qty']     += $notFreeQty;
            $totals['tax_amount']       += $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount();
            $totals['discount_amount']  += $item->getBaseDiscountAmount();
        }



        // fix magento bug
        if ($totals['qty'] != $totals['not_free_qty'])
            $request->setFreeShipping(false);

        $afterDiscount = Mage::getStoreConfig('carriers/amtable/after_discount');
        $includingTax =  Mage::getStoreConfig('carriers/amtable/including_tax');

        if ($afterDiscount)
            $totals['not_free_price'] -= $totals['discount_amount'];

        if($includingTax)
            $totals['not_free_price'] += $totals['tax_amount'];

        if ($totals['not_free_price'] < 0)
            $totals['not_free_price'] = 0;

        if ($request->getFreeShipping() && $allowFreePromo)
            $totals['not_free_price'] = $totals['not_free_weight'] = $totals['not_free_qty'] = 0;

        $totals['not_free_price'] = $totals['not_free_price'] / $totals['not_free_qty'];
        $totals['not_free_weight'] = $totals['not_free_weight'] / $totals['not_free_qty'];
        //$totals['not_free_qty'] = 1;

        return $totals;
    }

    protected function calculateAllTotals($request, $ignoreVirtual, $allowFreePromo,$shippingType)
    {
        $totals = $this->initTotals();

        $newItems = array();

        //reload child items

        $isCalculateLater = array();

        foreach ($request->getAllItems() as $item) {
            // if attribute isn't load to product
            $product = Mage::getModel('catalog/product')->load($item->getProduct()->getEntityId());
            if (($product->getAmShippingType() != $shippingType) && ($shippingType != 0))
                continue;

            if ($item->getParentItemId())
                continue;

            if ($ignoreVirtual && $item->getProduct()->isVirtual())
                continue;



            if ($item->getHasChildren()) {
                $qty = 0;
                $notFreeQty =0;
                $price = 0;
                $weight = 0;
                foreach ($item->getChildren() as $child) {

                    $qty        +=  $child->getQty();
                    $notFreeQty += ($qty - $this->getFreeQty($child, $allowFreePromo));
                    $price  += $child->getPrice() * $child->getQty();
                    $weight += $child->getWeight() * $qty;
                    $totals['tax_amount']       += $child->getBaseTaxAmount() + $child->getBaseHiddenTaxAmount();
                    $totals['discount_amount']  += $child->getBaseDiscountAmount();
                }

                if ($item->getProductType() == 'bundle'){
                    $qty        = $item->getQty();

                    if ($item->getProduct()->getWeightType() == 1){
                        $weight  = $item->getWeight();
                    }

                    if ($item->getProduct()->getPriceType() == 1){
                        $price   = $item->getPrice();
                    }

                    if ($item->getProduct()->getSkuType() == 1){
                        $totals['tax_amount']       += $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount();
                        $totals['discount_amount']  += $item->getBaseDiscountAmount();
                    }

                    $notFreeQty = ($qty - $this->getFreeQty($item, $allowFreePromo));
                    $totals['qty']              += $qty;
                    $totals['not_free_qty']     += $notFreeQty;
                    $totals['not_free_price'] += $price * $notFreeQty;
                    $totals['not_free_weight'] += $weight * $notFreeQty;

                }

                if ($item->getProductType() == 'configurable'){
                    $qty     = $item->getQty();
                    $price   = $item->getPrice();
                    $weight  = $item->getWeight();
                    $notFreeQty = ($qty - $this->getFreeQty($item, $allowFreePromo));
                    $totals['qty']              += $qty;
                    $totals['not_free_qty']     += $notFreeQty;
                    $totals['not_free_price'] += $price * $notFreeQty;
                    $totals['not_free_weight'] += $weight * $notFreeQty;
                    $totals['tax_amount']       += $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount();
                    $totals['discount_amount']  += $item->getBaseDiscountAmount();
                }


            } else {
                $qty        = $item->getQty();
                $notFreeQty = ($qty - $this->getFreeQty($item, $allowFreePromo));
                $totals['not_free_price'] += $item->getBasePrice() * $notFreeQty;
                $totals['not_free_weight'] += $item->getWeight() * $notFreeQty;
                $totals['qty']              += $qty;
                $totals['not_free_qty']     += $notFreeQty;
                $totals['tax_amount']       += $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount();
                $totals['discount_amount']  += $item->getBaseDiscountAmount();
            }


        }// foreach

        // fix magento bug
        if ($totals['qty'] != $totals['not_free_qty'])
            $request->setFreeShipping(false);

        $afterDiscount = Mage::getStoreConfig('carriers/amtable/after_discount');
        $includingTax =  Mage::getStoreConfig('carriers/amtable/including_tax');

        if ($afterDiscount)
            $totals['not_free_price'] -= $totals['discount_amount'];

        if($includingTax)
            $totals['not_free_price'] += $totals['tax_amount'];

        if ($totals['not_free_price'] < 0)
            $totals['not_free_price'] = 0;

        if ($request->getFreeShipping() && $allowFreePromo)
            $totals['not_free_price'] = $totals['not_free_weight'] = $totals['not_free_qty'] = 0;

        return $totals;
    }

    public function getFreeQty($item, $allowFreePromo)
    {
        $freeQty = 0;

        if ($item->getFreeShipping() && $allowFreePromo)
            $freeQty = ((is_numeric($item->getFreeShipping())) && ($item->getFreeShipping() <= $item->getQty())) ? $item->getFreeShipping() : $item->getQty();

        return $freeQty;
    }

    public function import($methodId, $fileName)
    {
        $err = array();

        $fp = fopen($fileName, 'r');
        if (!$fp){
            $err[] = Mage::helper('amtable')->__('Can not open file %s .', $fileName);
            return $err;
        }
        $methodId = intval($methodId);
        if (!$methodId){
            $err[] = Mage::helper('amtable')->__('Specify a valid method ID.');
            return $err;
        }

        $countryCodes = $this->getCountries();
        $stateCodes   = $this->getStates();
        $countryNames = $this->getCountriesName();
        $stateNames   = $this->getStatesName();
        $typeLabels   = Mage::helper('amtable')->getTypes();

        $data = array();
        $dataIndex = 0;

        $currLineNum  = 0;
        while (($line = fgetcsv($fp, self::MAX_LINE_LENGTH, ',', '"')) !== false) {
            $currLineNum++;

            if (count($line) == 1)
            {
                continue;
            }

            if (count($line) != self::COL_NUMS){
                $err[] = 'Line #' . $currLineNum . ': skipped, expected number of columns is ' . self::COL_NUMS;
                continue;
            }

            for ($i = 0; $i < self::COL_NUMS; $i++) {
                $line[$i] = str_replace(array("\r", "\n", "\t", "\\" ,'"', "'", "*"), '', $line[$i]);
            }

            $countries = array('');
            if ($line[0]){
                $countries = explode(',', $line[0]);
            } else {
                $line[0] = '0';
            }
            $states = array('');
            if ($line[1]){
                $states = explode(',', $line[1]);
            }

            $types = array('');
            if ($line[11]){
                $types = explode(',', $line[11]);
            }

            $zips = array('');
            if ($line[3]){
                $zips = explode(',', $line[3]);
            }

            if(!$line[6]) $line[6] =  999999;
            if(!$line[8]) $line[8] =  999999;
            if(!$line[10]) $line[10] =  999999;

            foreach ($types as $type){
                if ($type == 'All'){
                    $type = 0;
                }
                if ($type && empty($typeLabels[$type])) {
                    if (in_array($type, $typeLabels)){
                        $typeLabels[$type] = array_search($type, $typeLabels);
                    }  else {
                        $err[] = 'Line #' . $currLineNum . ': invalid type code ' . $type;
                        continue;
                    }

                }
                $line[11] = $type ? $typeLabels[$type] : '';
            }

            foreach ($countries as $country){
                if ($country == 'All'){
                    $country = 0;
                }

                if ($country && empty($countryCodes[$country])) {
                    if (in_array($country, $countryNames)){
                        $countryCodes[$country] = array_search($country, $countryNames);
                    }  else {
                        $err[] = 'Line #' . $currLineNum . ': invalid country code ' . $country;
                        continue;
                    }

                }
                $line[0] = $country ? $countryCodes[$country] : '';

                foreach ($states as $state){

                    if ($state == 'All'){
                        $state = '';
                    }

                    if ($state && empty($stateCodes[$state][$country])) {
                        if (in_array($state, $stateNames)){
                            $stateCodes[$state][$country] = array_search($state, $stateNames);
                        } else {
                            $err[] = 'Line #' . $currLineNum . ': invalid state code ' . $state;
                            continue;
                        }

                    }
                    $line[1] = $state ? $stateCodes[$state][$country] : '';

                    foreach ($zips as $zip){
                        $line[3] = $zip;


                        $data[$dataIndex] = $line;
                        $dataIndex++;

                        if ($dataIndex > 500){
                            $err2 = $this->getResource()->batchInsert($methodId, $data);
                            if ($err2){
                                $err[] = 'Line #' . $currLineNum . ': duplicated conditions before this line have been skipped';
                            }
                            $data = array();
                            $dataIndex = 0;
                        }
                    }
                }// states  
            }// countries 
        } // end while read  
        fclose($fp);

        if ($dataIndex){
            $err2 = $this->getResource()->batchInsert($methodId, $data);

            if ($err2){
                $err[] = 'Line #' . $currLineNum . ': duplicated conditions before this line have been skipped';
            }
        }

        return $err;
    }

    public function getCountries()
    {
        $hash = array();

        $collection = Mage::getResourceModel('directory/country_collection');
        foreach ($collection as $item){
            $hash[$item->getIso3Code()] = $item->getCountryId();
            $hash[$item->getIso2Code()] = $item->getCountryId();
        }

        return $hash;
    }

    public function getStates()
    {
        $hash = array();

        $collection = Mage::getResourceModel('directory/region_collection');
        foreach ($collection as $state){
            $hash[$state->getCode()][$state->getCountryId()] = $state->getRegionId();
        }

        return $hash;
    }
    public function getCountriesName()
    {
        $hash = array();
        $collection = Mage::getResourceModel('directory/country_collection');
        foreach ($collection as $item){
            $country_name=Mage::app()->getLocale()->getCountryTranslation($item->getIso2Code());
            $hash[$item->getCountryId()] = $country_name;

        }
        return $hash;
    }


    public function getStatesName()
    {
        $hash = array();

        $collection = Mage::getResourceModel('directory/region_collection');
        $countryHash = $this->getCountriesName();
        foreach ($collection as $state){
            $string = $countryHash[$state->getCountryId()].'/'.$state->getDefaultName();
            $hash[$state->getRegionId()] =  $string;
        }
        return $hash;
    }

    public function initTotals()
    {
        $totals = array(
            //  'price'              => 0,
            'not_free_price'     => 0,
            //  'weight'             => 0,
            'not_free_weight'    => 0,
            'qty'                => 0,
            'not_free_qty'       => 0,
            'tax_amount'         => 0,
            'discount_amount'    => 0,
        );
        return $totals;
    }

    public function deleteBy($methodId)
    {
        return $this->getResource()->deleteBy($methodId);
    }
}
