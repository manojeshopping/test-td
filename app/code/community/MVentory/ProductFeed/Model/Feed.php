<?php

class MVentory_ProductFeed_Model_Feed
{
  protected $_paramsDefaults = array(
    'categories' => null,
    'attribute_sets' => null
  );

  public function removeFile ($file) {
    if (file_exists($file))
      unlink($file);
  }

  public function getProductCollection ($params){
    ini_set('memory_limit','512M');

    $productCollection = Mage::getResourceModel('catalog/product_collection')
      ->setStore($params['store'])
      ->addAttributeToSelect('*')
      ->addAttributeToFilter('productfeed_include', 1)
      ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
      ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
      ->addStoreFilter($params['store'])
      ->addFinalPrice();

    $conds = array();

    if ($params['attribute_sets'])
      $conds[] = array(
        'attribute' => 'attribute_set_id',
        'in' => $params['attribute_sets']
      );

    if ($params['categories']) {
      $productCollection
        ->joinTable(
            'catalog_category_product',
            'product_id = entity_id',
            array('category_id' => 'category_id'),
            null,
            'left'
          )
        ->getSelect()
        ->group('catalog_category_product.product_id')
        ->distinct(true);

      $conds[] = array(
        'attribute' => 'category_id',
        'in' => $params['categories']
      );
    }

    if ($conds)
      $productCollection->addAttributeToFilter($conds);

    return $productCollection;
  }

  public function generate ($params) {
    try {
      $params = $this->_prepareParams($params);
    } catch (Exception $e) {
      return Mage::logException($e);
    }

    $helper = Mage::helper('productfeed');

    $file = $helper->getFileName(
      $params['filename'] . '.' . strtolower($params['feed_format'])
    );

    $this->removeFile($file);

    $productCollection = $this->getProductCollection($params);

    if($params['feed_format']=='XML'){
      $xmlWriter = new XMLWriter();
      $xmlWriter->openMemory();
      $xmlWriter->setIndent(true);
      $xmlWriter->startDocument('1.0', 'ISO-8859-2');
      $xmlWriter->startElement('Feed');
      $counter = 0;

      foreach ($productCollection as $product) {
        $xmlWriter->startElement('Product');
        $productData = $helper->getProductData($product, $params['feedfor']);

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
      $delimiter = $helper->getDelimiter($params['feedfor'], null);
      $header = $helper->getCSVHeader($params['feedfor']);
      $fp = fopen($file, "w+");
      fputcsv($fp, $header, $delimiter);

      foreach ($productCollection as $product) {
        $productData = $helper->getProductData($product, $params['feedfor']);
        fputcsv($fp, $productData, $delimiter);
      }

      fclose($fp);
    }
  }

  protected function _prepareParams ($params) {
    $params = array_merge($this->_paramsDefaults, $params);

    /**
     * @todo check if store is set. If not then return error for multistore
     *   setup or use default store for single store setup
     */
    $params['store'] = Mage::app()->getStore($params['store']);

    //Convert single category ID to array if it's not empty
    if ($params['categories'] && !is_array($params['categories']))
      $params['categories'] = (array) $params['categories'];

    //Convert single attribute set ID to array if it's not empty
    if ($params['attribute_sets'] && !is_array($params['attribute_sets']))
      $params['attribute_sets'] = (array) $params['attribute_sets'];

    return $params;
  }
}
