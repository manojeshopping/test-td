<?php 

class Medma_Attrib_Model_Attribute_Backend_Provider extends Mage_Eav_Model_Entity_Attribute_Backend_Abstract{
     
	 /**
     * Before Attribute Save Process
     *
     * @param Varien_Object $object
     * @return Mage_Catalog_Model_Category_Attribute_Backend_Sortby
     */
    public function beforeSave($object) {
	
		$attributeCode = $this->getAttribute()->getName();
		if ($attributeCode == 'related_categories') {
            $data = $object->getData($attributeCode);
            if (!is_array($data)) {
                $data = array();
            }
			$object->setData($attributeCode, join(',', $data));
        }
        if (is_null($object->getData($attributeCode))) {
			$object->setData($attributeCode, false);
        }
        return $this;
    }

    public function afterLoad($object) {
		$attributeCode = $this->getAttribute()->getName();
		if ($attributeCode == 'related_categories') {
            $data = $object->getData($attributeCode);
            if ($data) {
                $object->setData($attributeCode, explode(',', $data));
            }
        }
        return $this;
    }
} 