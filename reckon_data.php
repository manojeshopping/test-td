<?php
	require_once 'app/Mage.php';
	umask(0);
	Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

	$reckonPriceId =  Mage::getModel('catalog/product')->getResource()->getAttribute("reckon_price")->getId();
	$reckonStockId =  Mage::getModel('catalog/product')->getResource()->getAttribute("reckon_stock")->getId();
	
	$resource = Mage::getSingleton('core/resource');
	$readConnection = $resource->getConnection('core_read');
	$writeConnection = $resource->getConnection('core_write');
	$query = 'SELECT * FROM reckon';
	$results = $readConnection->fetchAll($query);
	
	echo "Total: " . count($results) . "<br>";
	$i = 0;
	foreach ($results as $result) {
		//echo $result["sku"] . "<br>";
		$id = Mage::getModel('catalog/product')->getResource()->getIdBySku($result["sku"]);
		if ($id) {
			$rp = round($result["price"] * 1.15, 2);	
			
			$sql = "update catalog_product_entity_decimal set value=" . $rp . " where entity_id = " . $id . " and attribute_id = " . $reckonPriceId;
			$writeConnection->query($sql);
			//echo $sql . "<br>";
			
			$sql = "update catalog_product_entity_varchar set value=" . $result["stock"] . " where entity_id = " . $id . " and attribute_id = " . $reckonStockId;
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