<?php

class TradeDepot_ProductFeed_Helper_Data extends Mage_Core_Helper_Abstract
{

  const FEEDS_FOLDER = 'feeds';
  const FEED_PRICEME = 'http://www.priceme.co.nz/';
  const FEED_PRICESPY = 'http://pricespy.co.nz/';

  /**
   * Returns product data for PriceMe
   *
   * @param Mage_Catalog_Product $product
   * @return array
   */
  public function getProductData($product, $feedFor){
    switch ($feedFor) {
      case self::FEED_PRICEME:
        return $this->getProductDataPriceMe($product);
        break;
      case self::FEED_PRICESPY:
        return $this->getProductDataPriceSpy($product);
        break;
      default:
        return array();
        break;
    }
  }

  /**
   * Returns product data for PriceMe
   *
   * @param Mage_Catalog_Product $product
   * @return array
   */
  public function getProductDataPriceMe($product){
    $data = array();
    $data['MPN'] = '';
    $data['UPC'] = '';
    $data['UIC'] = $this->getProductSkuPriceMe($product);
    $data['Name'] = $this->getProductName($product);
    $data['Price'] = $this->getProductFinalPrice($product);
    $data['ProductURL'] = $this->getProductUrl($product);
    $data['Category'] = $this->getCategoriesPriceMe($product);
    $data['Manufacturer'] = '';
    $data['StockStatus'] = $this->getStockStatusPriceMe($product);
    $data['Condition'] = 'New';
    $data['Image'] = $this->getProductImageUrl($product);
    $data['Description'] = $this->getProductShortDescription($product);
    $data['LongDescription'] = $this->getProductDescription($product);
    $data['PaymentType'] = '';
    $data['PromotionalMessage'] = '';
    $data['Shipping'] = '';
    $data['Currency'] = 'NZD';
    $data['Availability'] = $this->getFormatedStockStatus($product);
    $data['ProductType'] = '';
    return $data;
  }

  /**
   * Returns product data for PriceSpy
   *
   * @param Mage_Catalog_Product $product
   * @return array
   */
  public function getProductDataPriceSpy($product){
    $data = array();
    $data['Product-name'] = $this->getProductName($product);
    $data['Your-item-number'] = $this->getProductSkuPriceMe($product);
    $data['Category'] = $this->getCategoriesPriceSpy($product);
    $data['price-including-gst'] = $this->getProductFinalPrice($product);
    $data['Product-URL'] = $this->getProductUrl($product);
    $data['manufacturer'] = '';
    $data['manufacturer-SKU'] = '';
    $data['shipping'] = '';
    $data['image-URL'] = $this->getProductImageUrl($product);
    $data['stock status'] = $this->getFormatedStockStatus($product);
    return $data;
  }

  /**
   * Returns feed delimiter
   *
   * @param string $feedFor
   * @return string
   */
  public function getDelimiter($feedFor, $delimiter){
    if($delimiter == 1) return ';';
    if($delimiter == 2) return ',';
    if($delimiter == 3) return "\t";
    switch ($feedFor) {
      case self::FEED_PRICEME:
        return ';';
      case self::FEED_PRICESPY:
        return ';';
      default:
        return ',';
    }
  }

  /**
   * Returns CSV Header
   *
   * @param string $feedFor
   * @return array
   */
  public function getCsvHeader($feedFor){
    switch ($feedFor) {
      case self::FEED_PRICEME:
        return array('MPN', 'UPC', 'UPC', 'Product Name',
          'Product Price', 'Product URL', 'Category', 'Manufacturer',
          'Stock Status', 'Condition', 'Image URL', 'Product Short Description',
          'Product Long Description', 'Payment Type', 'Promotional Message',
          'Shipping Cost', 'Currency','Availability', 'Product Type');
      case self::FEED_PRICESPY:
        return array('Product-name','Your-item-number','category',
          'price-including-gst','Product-URL','manufacturer','manufacturer-SKU',
          'shipping;image-URL','stock status');
      default:
        return '';
    }
  }

  /**
   * Returns product description
   *
   * @param Mage_Catalog_Product $product
   * @return string
   */
  public function getProductDescription($product){
    return $this->utf8_for_xml($product->getDescription());
  }

  /**
   * Returns product short description
   *
   * @param Mage_Catalog_Product $product
   * @return string
   */
  public function getProductShortDescription($product){
    return $this->utf8_for_xml($product->getShortDescription());
  }

  function utf8_for_xml($string)
  {
    return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
  }

  /**
   * Returns product SKU for PriceMe UIC
   *
   * @param Mage_Catalog_Product $product
   * @return string
   */
  public function getProductSkuPriceMe($product){
    return $product->getSku();
  }

  /**
   * Returns product name
   *
   * @param Mage_Catalog_Product $product
   * @return string
   */
  public function getProductName($product){
    return $product->getName();
  }

  /**
   * Returns product price
   *
   * @param Mage_Catalog_Product $product
   * @return string
   */
  public function getProductFinalPrice($product){
    return round($product->getFinalPrice(),2);
  }

  /**
   * Returns product url
   *
   * @param Mage_Catalog_Product $product
   * @return string
   */
  public function getProductUrl($product){
    return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)
      .$product->getUrlPath();
  }

  /**
   * Returns stock status PriceMe
   *
   * @param Mage_Catalog_Product $product
   * @return string
   */
  public function getStockStatusPriceMe($product){
    return ($product->getStockItem()->getIsInStock())?
            'Y':'N';
  }

  /**
   * Returns formatted stock status PriceSpy
   *
   * @param Mage_Catalog_Model_Product $stock
   * @return int|string stock qty or no
   */
  public function getFormatedStockStatus($product){
    return ($product->getStockItem()->getIsInStock())?
            (int)$product->getQty():'no';
  }

  /**
   * Returns file absolute path
   *
   * @param string $fileName
   * @return string
   */
  public function getFileName($fileName){
    $basePath = Mage::getBaseDir().DS.self::FEEDS_FOLDER;
    if (!file_exists ($basePath)){
      mkdir($basePath);
    }
    return $basePath.DS.$fileName;
  }

  /**
   * Returns file url path
   *
   * @param string $fileName
   * @param string $format
   * @return string
   */
  public function getFileUrl($fileName, $format){
    return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)
      .self::FEEDS_FOLDER.DS.
      $fileName.'.'.self::getFileExtension($format);
  }

  /**
   * Returns file extension url path
   *
   * @param string $format
   * @return string
   */
  protected static function getFileExtension($format){
    switch ($format) {
      case 'XML':
        return 'xml';
        break;
      case 'CSV':
        return 'csv';
        break;
      default:
        return 'txt';
        break;
    }
  }

  /**
   * Returns string of categories in PriceMe format >
   *
   * @param Mage_Catalog_Product $product
   * @return string
   */
  public function getCategoriesPriceMe($product){
    $cache = Mage::app()->getCache();
    $categories = '';
    $catIds = $product->getCategoryIds();

    $catCollection = Mage::getResourceModel('catalog/category_collection')
           ->addAttributeToSelect('*')
           ->addAttributeToFilter('entity_id', $catIds)
           ->addIsActiveFilter();
    $i = 0;
    foreach ($catCollection as $category) {
      if ($i > 0){
        $categories .= ' > ';
      }
      $categories .= $category->getName();
      $i++;
    }
    unset($catCollection);
    return $categories;
  }

  /**
   * Returns string of categories in PriceSpy format >
   *
   * @param Mage_Catalog_Product $product
   * @return string
   */
  public function getCategoriesPriceSpy($product){
    $catIds = $product->getCategoryIds();
    $childCategory = end($catIds);
    $category = Mage::getModel('catalog/category')
      ->load($childCategory);
    return $category->getName();
  }

  /**
   * Returns product image url
   *
   * @param Mage_Catalog_Product $product
   * @return string
   */
  public function getProductImageUrl($product){
    if ($product->getImage() == 'no_selection')
      return '';
    $image = Mage::getModel('catalog/product_media_config')
      ->getMediaUrl( $product->getImage() );
    return $image;
  }
}
