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
 * @copyright Copyright (c) 2014-2015 mVentory Ltd. (http://mventory.com)
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

    $settings = null;

    if ($store->getId != Mage::app()->getStore()->getId())
      $env = $this->changeStore($store);

    $watermarkImg = $this->_getWatermarkImg($store);

    $imageModel = Mage::getModel('catalog/product_image')
      ->setDestinationSubdir('image')
      ->setKeepFrame((bool) $watermarkImg)
      ->setConstrainOnly(true)
      ->setWidth($size['width'])
      ->setHeight($size['height']);

    if ($watermarkImg)
      $imageModel
        ->setWatermarkFile($watermarkImg)
        ->setWatermarkSize($this->_getWatermarkSize($store))
        ->setWatermarkImageOpacity($this->_getWatermarkOpacity($store))
        ->setWatermarkPosition($this->_getWatermarkPos($store));

    //Don't call this method in chain because some exts can override it
    //but don't return correct object
    $imageModel->setBaseFile($image);

    //Check if resized image exists before resizing it
    $newImage = ($newImage = $imageModel->getNewFile())
                ? $newImage
                : $this->_buildFileName(
                    $image,
                    $settings = $this->_extractSettings($imageModel),
                    $store
                  );

    if (file_exists($newImage)) {
      if (isset($env))
        $this->changeStore($env);

      return $newImage;
    }

    $imageModel->resize();

    if ($watermarkImg)
      $imageModel->setWatermark($watermarkImg);

    $newImage = $imageModel
      ->saveFile()
      ->getNewFile();

    if ($newImage && file_exists($newImage)) {
      if (isset($env))
        $this->changeStore($env);

      return $newImage;
    }

    $newImage = $this->_download(
      $imageModel->getUrl(),
      $this->_buildFileName(
        $image,
        $settings ?: $settings = $this->_extractSettings($imageModel),
        $store
      )
    );

    if ($watermarkImg && file_exists($newImage))
      $this->_addWatermark($newImage, $settings);

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
   * Add watermark specified in settings to passed image file. Overwrites
   * original file.
   *
   * @param string $file
   *   Path to original image file
   *
   * @param array $settings
   *   Settings from product's image model
   *
   * @return MVentory_TradeMe_Helper_Image
   *   Instance of this class
   */
  protected function _addWatermark ($file, $settings) {
    $image = new Varien_Image($file);

    $image->keepAspectRatio($settings['keep_aspect_ratio']);
    $image->keepFrame($settings['keep_frame']);
    $image->keepTransparency($settings['keep_transparency']);
    $image->constrainOnly($settings['constrain_only']);
    $image->backgroundColor($settings['background_color']);
    $image->quality($settings['quality']);

    $image
      ->setWatermarkPosition($settings['watermark_position'])
      ->setWatermarkImageOpacity($settings['watermark_opacity'])
      ->setWatermarkWidth($settings['watermark_width'])
      ->setWatermarkHeigth($settings['watermark_height']);

    $image->watermark($settings['watermark_filepath']);
    $image->save($file);

    return $this;
  }

  /**
   * Extract settings from supplied product's image model
   *
   * @param Mage_Catalog_Model_Product_Image $model
   *   Product's image model
   *
   * @return array
   *   Extracted settings
   */
  protected function _extractSettings ($model) {
    $ref = new ReflectionObject($model);

    $prop = function ($name) use ($ref, $model) {
      $p = $ref->getProperty($name);
      $p->setAccessible(true);

      return $p->getValue($model);
    };

    $call = function ($name) use ($ref, $model) {
      $m = $ref->getMethod($name);
      $m->setAccessible(true);

      return $m->invokeArgs($model, array());
    };

    return array(
      'destination_subdir' => $model->getDestinationSubdir(),

      'width' => $model->getWidth(),
      'height' => $model->getHeight(),

      'keep_aspect_ratio' => $prop('_keepAspectRatio'),
      'keep_frame' => $prop('_keepFrame'),
      'keep_transparency' => $prop('_keepTransparency'),
      'constrain_only' => $prop('_constrainOnly'),
      'background_color' => $prop('_backgroundColor'),
      'angel' => $prop('_angle'),
      'quality' => $model->getQuality(),

      'watermark_file' => $model->getWatermarkFile(),
      'watermark_filepath' => $call('_getWatermarkFilePath'),
      'watermark_opacity' => $model->getWatermarkImageOpacity(),
      'watermark_position' => $model->getWatermarkPosition(),
      'watermark_width' => $model->getWatermarkWidth(),
      'watermark_height' => $model->getWatermarkHeigth()
    );
  }

  /**
   * Build full path for prepared image in same way as Magento does for
   * product images
   *
   * @param string $file
   *   Name of image file with dispretion path
   *
   * @param array $settings
   *   Image settings from product's image model
   *
   * @param Mage_Core_Model_Store $store
   *   Store object
   *
   * @return string
   *   Full path for prepared product image
   */
  protected function _buildFileName ($file, $settings, $store) {
    //Most important params
    $path = array(
      Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath(),
      'cache',
      $store->getId(),
      $settings['destination_subdir']
    );

    $width = $settings['width'];
    $height = $settings['height'];

    if ($width || $height)
      $path[] = $width . 'x' . $height;

    //Miscellaneous params as a hash
    $params = array(
      ($settings['keep_aspect_ratio'] ? '' : 'non') . 'proportional',
      ($settings['keep_frame'] ? '' : 'no') . 'frame',
      ($settings['keep_transparency'] ? '' : 'no') . 'transparency',
      ($settings['constrain_only'] ? 'do' : 'not') . 'constrainonly',
      $this->_rgbToString($settings['background_color']),
      'angle' . $settings['angel'],
      'quality' . $settings['quality']
    );

    //If has watermark add watermark params to hash
    if ($settings['watermark_file']) {
      $params[] = $settings['watermark_file'];
      $params[] = $settings['watermark_opacity'];
      $params[] = $settings['watermark_position'];
      $params[] = $settings['watermark_width'];
      $params[] = $settings['watermark_height'];
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

  /**
   * Parse size from string (e.g 300x200)
   *
   * @param str $string
   *   String with size in WxH format
   *
   * @return array|bool
   *   Parsed size or false if it's not correct
   */
  protected function _parseSize ($str) {
    $size = explode('x', strtolower($str));

    if (sizeof($size) == 2)
      return array(
        'width' => ($size[0] > 0) ? $size[0] : null,
        'height' => ($size[1] > 0) ? $size[1] : null,

        //Magento has misprint in height word in watermark functions
        'heigth' => ($size[1] > 0) ? $size[1] : null
      );

    return false;
  }

  /**
   * Return filename of watermark image from store's config
   *
   * @param Mage_Core_Model_Store $store
   *   Store object
   *
   * @return string
   *   Filename of watermark image
   */
  protected function _getWatermarkImg ($store) {
    return $store->getConfig(MVentory_TradeMe_Model_Config::_WATERMARK_IMG);
  }

  /**
   * Return size of watermark image from store's config
   *
   * @param Mage_Core_Model_Store $store
   *   Store object
   *
   * @return string
   *   Size of watermark image
   */
  protected function _getWatermarkSize ($store) {
    return $this->_parseSize(
      $store->getConfig(MVentory_TradeMe_Model_Config::_WATERMARK_SIZE)
    );
  }

  /**
   * Return opacity of watermark image from store's config
   *
   * @param Mage_Core_Model_Store $store
   *   Store object
   *
   * @return string
   *   Opacity of watermark image
   */
  protected function _getWatermarkOpacity ($store) {
    return $store->getConfig(MVentory_TradeMe_Model_Config::_WATERMARK_OPC);
  }

  /**
   * Return position of watermark image from store's config
   *
   * @param Mage_Core_Model_Store $store
   *   Store object
   *
   * @return string
   *   Position of watermark image
   */
  protected function _getWatermarkPos ($store) {
    return $store->getConfig(MVentory_TradeMe_Model_Config::_WATERMARK_POS);
  }
}