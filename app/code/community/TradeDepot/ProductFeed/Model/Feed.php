<?php

class TradeDepot_ProductFeed_Model_Feed
{
  public static function removeFile ($file) {
    if (file_exists($file))
      unlink($file);
  }

  public static function getRootCategoryId ($storeId) {
    return Mage::app()->getStore($storeId)->getRootCategoryId();
  }

  public static function getProductCollection($rootCategoryId, $storeId){
    ini_set('memory_limit','512M');
    $category = Mage::getModel('catalog/category')->load($rootCategoryId);

    if ($category->hasChildren()) {
      $categoryLimit = explode(',',$category->getAllChildren());
    } else {
      $categoryLimit = $category->getId();
    }

    $productCollection = Mage::getResourceModel('catalog/product_collection')
      ->setStoreId($storeId)
      ->addAttributeToSelect('*')
      ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
      ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
      ->addFinalPrice();

    $productCollection->joinTable('cataloginventory_stock_item','product_id=entity_id',
      array('qty'=>'qty', 'is_in_stock'=>'is_in_stock'), null, 'inner');

    $productCollection->joinTable('catalog_category_product','product_id=entity_id',
      array('category_id'=>'category_id'), null, 'left')
    ->addAttributeToFilter('category_id', array('in' => $categoryLimit));
    /* Issue 2224 - Filter Products Managed only*/
    $productCollection->joinField('manages_stock','cataloginventory/stock_item','use_config_manage_stock',
      'product_id=entity_id','{{table}}.use_config_manage_stock=1 or {{table}}.manage_stock=1');
    $productCollection->addAttributeToFilter('manages_stock', true);
    /* Issue 2224 - Filter Products Managed only*/
    $productCollection->getSelect()->group('catalog_category_product.product_id')->distinct(true);

    return $productCollection;
  }

  public static function generate ($data) {
    $helper = Mage::helper('productfeed');

    $file = $helper->getFileName(
      $data['filename'] . '.' . strtolower($data['feed_format'])
      );

    self::removeFile($file);

    $rootCategoryId = self::getRootCategoryId($data['store']);

    $productCollection = self::getProductCollection(
      $rootCategoryId,
      $data['store']
    );

    if($data['feed_format']=='XML'){
      $xmlWriter = new XMLWriter();
      $xmlWriter->openMemory();
      $xmlWriter->setIndent(true);
      $xmlWriter->startDocument('1.0', 'ISO-8859-2');
      $xmlWriter->startElement('Feed');
      $counter = 0;

      foreach ($productCollection as $product) {
        $xmlWriter->startElement('Product');
        $productData = $helper->getProductData($product, $data['feedfor']);

        foreach ($productData as $key => $value) {
          $key = str_replace(array(' ', '-', '_'), '', $key);
          $xmlWriter->startElement($key);
          $xmlWriter->writeCData($value);
          $xmlWriter->endElement();
        }

        $xmlWriter->endElement(); // End Product Tag
        $counter++;
        unset($productData);
        // Flush XML in memory to file every 100 iterations
        if (0 == $counter%100 ) {
          file_put_contents($file, $xmlWriter->flush(true), FILE_APPEND);
        }
      }

      $xmlWriter->endElement(); // End Feed Tag
      file_put_contents($file, $xmlWriter->flush(true), FILE_APPEND);
    }
    else{
      $delimiter = $helper->getDelimiter($data['feedfor'], null);
      $header = $helper->getCSVHeader($data['feedfor']);
      $fp = fopen($file, "w+");
      fputcsv($fp, $header, $delimiter);

      foreach ($productCollection as $product) {
        $productData = $helper->getProductData($product, $data['feedfor']);
        fputcsv($fp, $productData, $delimiter);
      }

      fclose($fp);
    }
  }
}
