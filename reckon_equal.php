<?php
	require_once 'app/Mage.php';
	umask(0);
	Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
	
	$resource = Mage::getSingleton('core/resource');
	$readConnection = $resource->getConnection('core_read');
	$writeConnection = $resource->getConnection('core_write');

	$reckonPriceEqualId =  Mage::getModel('catalog/product')->getResource()->getAttribute("reckon_price_equal")->getId();	
	$collection = Mage::getResourceModel('catalog/product_collection');	
			
	echo "Total: " . count($collection) . "<br>";
	$i = 0;
	foreach ($collection as $product) {
		$productId = $product->getId();
		$rp = Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, 'reckon_price', Mage_Core_Model_App::ADMIN_STORE_ID);
		$pp = Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, 'price', Mage_Core_Model_App::ADMIN_STORE_ID);

		$equal = 0;
		if ($rp == $pp) {
			$equal = 1;
		}
		$sql = "update catalog_product_entity_int set value=" . $equal . " where entity_id = " . $productId . " and attribute_id = " . $reckonPriceEqualId;
		$writeConnection->query($sql);
		//echo $sql . "<br>";
		
		
		$i++;
		if ($i % 100 == 0) echo "<br>";
		echo $i;
		flush();
		ob_flush();
	}
	echo "<br>All done!";	
	 
?>