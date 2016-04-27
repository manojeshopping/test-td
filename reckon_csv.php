<?php
	require_once 'app/Mage.php';
	umask(0);
	Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
	
	$resource = Mage::getSingleton('core/resource');
	$readConnection = $resource->getConnection('core_read');
	$writeConnection = $resource->getConnection('core_write');

	// TRUNCATE initially
	$query = "TRUNCATE TABLE reckon"; 
	$writeConnection->query($query);
	echo "TRUNCATE finished<br>";
	flush();
	ob_flush();
	
	// first insert
	$file = 'var/import/reckon/data_TDL.csv';
	$csv = new Varien_File_Csv();
	$data = $csv->getData($file);
	for($i = 1; $i < count($data); $i++) {
		$sku = $data[$i][0];
		$price = $data[$i][1];
		$stock = $data[$i][2];
		$q_on_order = $data[$i][3];
		$q_on_sales_order = $data[$i][4];
		$inc_tax = $data[$i][5];
		if ($price == null) {
			$price = 0;
		}
		if ($stock == null) {
			$stock = 0;
		}
		if ($q_on_order == null) {
			$q_on_order = 0;
		}
		if ($q_on_sales_order == null) {
			$q_on_sales_order = 0;
		}
		
		$query = "insert into reckon (sku, price, stock, q_on_order, q_on_sales_order, inc_tax) values ('" . 
			$sku . "', " . 
			$price . ", " . 
			$stock . ", " . 
			$q_on_order . ", " . 
			$q_on_sales_order . ", " . 
			$inc_tax . ")";
		//echo $query . "<br>";
		echo ".";
		flush();
		ob_flush();
		$writeConnection->query($query);
	}
	
	echo "<br>insert price and stock finished<br>";
	flush();
	ob_flush();
	
	// then update ExpectedDate
	$file = 'var/import/reckon/ExpectedDate.csv';
	$csv = new Varien_File_Csv();
	$data = $csv->getData($file);
	for($i = 1; $i < count($data); $i++) {
		$oldDate = $data[$i][0];
		$pieces = explode("/", $oldDate);
		//$date = $pieces[2] . "-" . $pieces[1] . "-" . $pieces[0];
		$date = $pieces[2] . "-" . $pieces[0] . "-" . $pieces[1];
		$fullSku = $data[$i][2];
		
		$pieces = explode(":", $fullSku);
		$lastIndex = count($pieces) - 1;
		$sku = $pieces[$lastIndex];
		
		$query = "update reckon set arrive_date = '" . $date . "' where sku = '" . $sku . "'";
		echo $query . "<br>";
		echo ".";
		flush();
		ob_flush();
		$writeConnection->query($query);
	}
	
	echo "<br>update arrive date finished<br>";
	flush();
	ob_flush();
	
	// then update us price
	$file = 'var/import/reckon/usprice.csv';
	$csv = new Varien_File_Csv();
	$data = $csv->getData($file);
	for($i = 1; $i < count($data); $i++) {
		$sku = $data[$i][0];
		$usPrice = $data[$i][1];
		if ($usPrice == null) {
				$usPrice = 0;
		}
		
		$query = "update reckon set us_price = '" . $usPrice . "' where sku = '" . $sku . "'";
		//echo $query . "<br>";
		echo ".";
		flush();
		ob_flush();
		$writeConnection->query($query);
	}
	
	echo "<br>update us price finished<br>";
	flush();
	ob_flush();
	
	// then update q0 q1 q2 q3 q4
	for ($index = 0; $index < 5; $index++) {
		$file = 'var/import/reckon/Q' . $index . '.csv';
		$csv = new Varien_File_Csv();
		$data = $csv->getData($file);
		for($i = 1; $i < count($data); $i++) {
			$sku = $data[$i][0];
			$qty = $data[$i][1];
			if ($qty == null) {
				$qty = 0;
			}
			
			$query = "update reckon set q" . $index . " = '" . $qty . "' where sku = '" . $sku . "'";
			//echo $query . "<br>";
			echo ".";
			flush();
			ob_flush();
			$writeConnection->query($query);
		}
		
		echo "<br>update q" . $index . " finished<br>";
		flush();
		ob_flush();
	}
?>