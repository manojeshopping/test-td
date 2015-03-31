<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a Commercial Software License.
 * No sharing - This file cannot be shared, published or
 * distributed outside of the licensed organisation.
 * No Derivatives - You can make changes to this file for your own use,
 * but you cannot share or redistribute the changes.
 * This Copyright Notice must be retained in its entirety.
 * The full text of the license was supplied to your organisation as
 * part of the licensing agreement with mVentory.
 *
 * @package MVentory/TradeMe
 * @copyright Copyright (c) 2014 mVentory Ltd. (http://mventory.com)
 * @license Commercial
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

/**
 * Image helper
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Helper_Image extends MVentory_TradeMe_Helper_Data
{
  /**
   * Return full path to prepared image for uploading to TradeMe
   * It tries to download image by the URL used on the frontend
   * if product's image doesn't exist on server (e.g. images are served from
   * someCDN service)
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param array $size
   *   Array which contains 'width' and 'height' keys
   *
   * @param Mage_Core_Model_Store $store
   *   Store object
   *
   * @return string
   *   Full path to prepared image or nothing if product doesn't have image
   *
   * @throws Exception
   *   If prepared image doesn't exist
   */
  public function getImage ($product, $size, $store) {
    $image = $product->getImage();

    if (!$image && $image == 'no_selection')
      return;

    if ($store->getId != Mage::app()->getStore()->getId())
      $env = $this->changeStore($store);

    $imageModel = Mage::getModel('catalog/product_image')
      ->setDestinationSubdir('image')
      ->setKeepFrame(false)
      ->setConstrainOnly(true)
      ->setWidth($size['width'])
      ->setHeight($size['height']);

    //Don't call this method in chain because some exts can override it
    //but don't return correct object
    $imageModel->setBaseFile($image);

    //Check if resized image exists before resizing it
    $newImage = ($newImage = $imageModel->getNewFile())
                ? $newImage
                : $this->_buildFileName($image, $imageModel, $store);

    if (file_exists($newImage)) {
      if (isset($env))
        $this->changeStore($env);

      return $newImage;
    }

    $newImage = $imageModel
      ->resize()
      ->saveFile()
      ->getNewFile();

    if ($newImage && file_exists($newImage)) {
      if (isset($env))
        $this->changeStore($env);

      return $newImage;
    }

    $newImage = $this->_download(
      $imageModel->getUrl(),
      $this->_buildFileName($image, $imageModel, $store)
    );

    if (isset($env))
      $this->changeStore($env);

    if (!file_exists($newImage))
      throw new Exception('Prepared image doesn\'t exist');

    return $newImage;
  }

  /**
   * Dowload image by supplied URL and store it under specifed name
   *
   * @param string $url
   *   URL to image
   *
   * @param string $file
   *   Path to use for saving of downloaded image
   *
   * @return string|null
   *   Full path to the downloaded image or nothing
   */
  protected function _download ($url, $file) {
    $client = new Varien_Http_Client($url);

    $response = $client->request();

    if (!$response->isSuccessful())
      return;

    if (!$imgData = $response->getBody())
      return;

    if (!file_exists($path = dirname($file)))
      mkdir($path, 0777, true);

    return (file_put_contents($file, $imgData) == false) ? null : $file;
  }

  /**
   * Build full path for prepared image in same way as Magento does for
   * product images
   *
   * @param string $file
   *   Name of image file with dispretion path
   *
   * @param Mage_Catalog_Model_Product_Image $imageModel
   *   Initialised Mage_Catalog_Model_Product_Image object
   *
   * @param Mage_Core_Model_Store $store
   *   Store object
   *
   * @return string
   *   Full path for prepared product image
   */
  protected function _buildFileName ($file, $imageModel, $store) {
    //Most important params
    $path = array(
      Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath(),
      'cache',
      $store->getId(),
      $imageModel->getDestinationSubdir()
    );

    $width = $imageModel->getWidth();
    $height = $imageModel->getHeight();

    if ($width || $height)
      $path[] = $width . 'x' . $height;

    //Miscellaneous params as a hash

    //NOTE: we can't access protected fields of Mage_Catalog_Model_Product_Image
    //object and it doesn't provide getter for all following fields, so we
    //use hardcoded values. Some of them default, some of them are set in
    //getImage() method

    //Use default value of _keepAspectRatio field
    $_keepAspectRatio = true;

    //Use value set in the getImage() method for _keepFrame field
    $_keepFrame = false;

    //Use default value of _keepTransparency field
    $_keepTransparency = true;

    //Use value set in the getImage() method for _constrainOnly field
    $_constrainOnly = true;

    //Use default value of _backgroundColor field
    $_backgroundColor = array(255, 255, 255);

    //Use default value of _angle field
    $_angle = null;

    $params = array(
      ($_keepAspectRatio ? '' : 'non') . 'proportional',
      ($_keepFrame ? '' : 'no') . 'frame',
      ($_keepTransparency ? '' : 'no') . 'transparency',
      ($_constrainOnly ? 'do' : 'not') . 'constrainonly',
      $this->_rgbToString($_backgroundColor),
      'angle' . $_angle,
      'quality' . $imageModel->getQuality()
    );

    //If has watermark add watermark params to hash
    if ($imageModel->getWatermarkFile()) {
      $params[] = $imageModel->getWatermarkFile();
      $params[] = $imageModel->getWatermarkImageOpacity();
      $params[] = $imageModel->getWatermarkPosition();
      $params[] = $imageModel->getWatermarkWidth();
      $params[] = $imageModel->getWatermarkHeigth();
    }

    $path[] = md5(implode('_', $params));

    //The $file contains heading slash
    return implode('/', $path) . $file;
  }

  /**
   * Convert array of 3 items (decimal r, g, b) to string of their hex values
   * Copied from Mage_Catalog_Model_Product_Image class
   *
   * @see Mage_Catalog_Model_Product_Image::_rgbToString()
   *
   * @param array $rgbArray Array of RGB values
   * @return string
   */
  protected function _rgbToString ($rgbArray) {
    $rgb = '';

    foreach ($rgbArray as $value)
      $rgb .= ($value === null) ? 'null' : sprintf('%02s', dechex($value));

    return $rgb;
  }
}