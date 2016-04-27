<?php
	require_once 'app/Mage.php';
	umask(0);
	Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

	$main_positionId =  Mage::getModel('catalog/product')->getResource()->getAttribute("main_position")->getId();
	
	$resource = Mage::getSingleton('core/resource');
	$readConnection = $resource->getConnection('core_read');
	$writeConnection = $resource->getConnection('core_write');
	$query = 'SELECT * FROM main_position';
	$results = $readConnection->fetchAll($query);
	
	echo "Total: " . count($results) . "<br>";
	$i = 0;
	foreach ($results as $result) {
		//echo $result["sku"] . "<br>";
		$id = Mage::getModel('catalog/product')->getResource()->getIdBySku($result["sku"]);
		if ($id) {
			$sql = "update catalog_product_entity_varchar set value=" . $result["position"] . " where entity_id = " . $id . " and attribute_id = " . $main_positionId;
			$writeConnection->query($sql);
			//echo $sql . "<br>";
		}
		
		$i++;
		if ($i % 100 == 0) echo "<br>";
		echo $i;
		flush();
		ob_flush();
	}	
	echo "<br>All done!";	
?>