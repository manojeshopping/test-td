<?php

class Magestore_Onestepcheckout_Model_Source_Layout {

    public function toOptionArray() {
        $options = array();

        $options[] = array(
            'label' => Mage::helper('onestepcheckout')->__('2 Columns'),
            'value' => '20columns'
        );

        $options[] = array(
            'label' => Mage::helper('onestepcheckout')->__('3 Columns'),
            'value' => '25columns'
        );

        $options[] = array(
            'label' => Mage::helper('onestepcheckout')->__('3 Columns (Optimized)'),
            'value' => '30columns'
        );

        return $options;
    }

}
