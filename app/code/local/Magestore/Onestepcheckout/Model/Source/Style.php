<?php

class Magestore_Onestepcheckout_Model_Source_Style {

    public function toOptionArray() {
        $options = array();

        $options[] = array(
            'label' => 'Flat',
            'value' => 'flat'
        );

        $options[] = array(
            'label' => 'Material',
            'value' => 'material'
        );

        return $options;
    }

}
