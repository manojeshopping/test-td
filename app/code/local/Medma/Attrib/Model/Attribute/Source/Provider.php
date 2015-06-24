<?php 

class Medma_Attrib_Model_Attribute_Source_Provider extends Mage_Eav_Model_Entity_Attribute_Source_Abstract{
    protected $_options = null;
    public function getAllOptions($withEmpty = false){
		
        if (is_null($this->_options)){
		
			$category = Mage::getModel('catalog/category');
			$catTree = $category->getTreeModel()->load();
			$catIds = $catTree->getCollection()->getAllIds();
			$this->_options = array();
			if ($catIds){
				foreach ($catIds as $id){
					$cat = Mage::getModel('catalog/category');
					$cat->load($id);
					if($cat->getIsActive() && $cat->getId()!=2 && $cat->getId()!=1){
						$this->_options[] = array('label'=> $cat->getName(), 'value'=>$cat->getId()); 
					}
				}
			}
		}
        $options = $this->_options;
        if ($withEmpty) {
            array_unshift($options, array('value'=>'', 'label'=>''));
        }
        return $options;
    }
	
} 