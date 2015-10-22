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
  const __E_NO_PREPARED_IMGS = <<<'EOT'
Prepared images don't exist
EOT;

  /**
   * Return full path to prepared main image for uploading to TradeMe
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

    $images = $this->_get([$image], $size, $store);

    if (isset($env))
      $this->changeStore($env);

    if (!($images && isset($images[$image]) && $images[$image]))
      throw new MVentory_TradeMe_Exception(self::__E_NO_PREPARED_IMGS);

    return $images[$image];
  }

  /**
   * Return full path to prepared images for uploading to TradeMe
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
   * @return array
   *   List of full paths for prepared images
   *
   * @throws Exception
   *   If prepared image doesn't exist
   */
  public function getAllImages ($product, $size, $store) {
    if ($store->getId != Mage::app()->getStore()->getId())
      $env = $this->changeStore($store);

    $images = $product
      ->getMediaGalleryImages()
      ->getColumnValues('file');

    if (count($images) > MVentory_TradeMe_Model_Config::AUCTION_MAX_IMGS)
      $images = array_slice(
        $images,
        0,
        MVentory_TradeMe_Model_Config::AUCTION_MAX_IMGS
      );

    $images = $this->_get($images, $size, $store);

    if (isset($env))
      $this->changeStore($env);

    //Check if no images were prepared
    if (!array_filter($images))
      throw new MVentory_TradeMe_Exception(self::__E_NO_PREPARED_IMGS);

    //Move product's main image to the first position in the array if product
    //has main image and this image is prepared
    $cond = ($image = $product->getImage())
            && $image != 'no_selection'
            && isset($images[$image])
            && ($preparedImage = $images[$image]);

    if ($cond) {
      unset($images[$image]);
      $images = array_merge([$image => $preparedImage], $images);
    }

    return $images;
  }

  /**
   * Return full path to prepared images for uploading to TradeMe
   * It tries to download image by the URL used on the frontend
   * if product's image doesn't exist on server (e.g. images are served from
   * some CDN service)
   *
   * @param array
   *   List of product images
   *
   * @param array $size
   *   Array which contains 'width' and 'height' keys
   *
   * @param Mage_Core_Model_Store $store
   *   Store object
   *
   * @return array
   *   List of full paths to prepared images
   *
   * @throws Exception
   *   If prepared image doesn't exist
   */
  public function _get ($images, $size, $store) {
    if (!$images)
      return [];

    $padding = $this->_getPadding($store);

    $watermarkImg = $this->_getWatermarkImg($store);
    $watermarkSize = $this->_getWatermarkSize($store);
    $watermarkOpacity = $this->_getWatermarkOpacity($store);
    $watermarkPos = $this->_getWatermarkPos($store);

    //Re-use supplied array to store prepared images.
    //But use supplied image names as keys, values will be filled with paths
    //for corresponding prepared images
    $images = array_flip($images);

    foreach ($images as $image => &$newImage) try {
      //Array values are not empty after array_flip function, so set them
      //to null
      $newImage = null;

      $settings = null;

      $imageModel = Mage::getModel('catalog/product_image')
        ->setDestinationSubdir('image')
        ->setKeepFrame((bool) $watermarkImg)
        ->setConstrainOnly(true)
        ->setWidth($size['width'])
        ->setHeight($size['height']);

      if ($watermarkImg)
        $imageModel
          ->setWatermarkFile($watermarkImg)
          ->setWatermarkSize($watermarkSize)
          ->setWatermarkImageOpacity($watermarkOpacity)
          ->setWatermarkPosition($watermarkPos);

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

      if (file_exists($newImage))
        continue;

      //Check if image processor is instance or child of Varien_Image because
      //padding code depends on its internal things. It can be redeclared in
      //some Magento extensions to implement different logic.
      if (($scaleSize = $this->_getScaleSize($padding, $size))
          && ($imgProcessor = $imageModel->getImageProcessor())
          && ($imgProcessor instanceof Varien_Image)) {

        $imgProcessor->resize($scaleSize['width'], $scaleSize['height']);
        $this->_pad($imgProcessor, $size);

        unset($imgProcessor);
      }
      else
        $imageModel->resize();

      if ($watermarkImg)
        $imageModel->setWatermark($watermarkImg);

      $newImage = $imageModel
        ->saveFile()
        ->getNewFile();

      if ($newImage && file_exists($newImage))
        continue;

      $newImage = $this->_download(
        $imageModel->getUrl(),
        $this->_buildFileName(
          $image,
          $settings ?: $settings = $this->_extractSettings($imageModel),
          $store
        )
      );

      if (file_exists($newImage)) {
        $varienImage = null;

        if ($scaleSize) {
          $varienImage = $this->_getVarienImage($newImage, $settings);

          $varienImage->resize($scaleSize['width'], $scaleSize['height']);
          $this->_pad($varienImage, $size);
        }

        if ($watermarkImg)
          $this->_addWatermark(
            $varienImage = $varienImage
                           ?: $this->_getVarienImage($newImage, $settings),
            $settings
          );

        if ($varienImage)
          $varienImage->save($newImage);
      }

      if (file_exists($newImage))
        continue;

      //Failed to prepare image, set it to null in the array of prepared images
      $newImage = null;
    }
    catch (Exception $e) {
      Mage::logException($e);

      //Failed to prepare image, set it to null in the array of prepared images
      $newImage = null;
    }

    return $images;
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
   * Get image handler for passed image file
   *
   * @param string $file
   *   Path to image file
   *
   * @param array $settings
   *   Settings from product's image model
   *
   * @return Varien_Image
   *   Image handler for the passed image file
   */
  protected function _getVarienImage ($file, $settings) {
    $image = new Varien_Image($file);

    $image->keepAspectRatio($settings['keep_aspect_ratio']);
    $image->keepFrame($settings['keep_frame']);
    $image->keepTransparency($settings['keep_transparency']);
    $image->constrainOnly($settings['constrain_only']);
    $image->backgroundColor($settings['background_color']);
    $image->quality($settings['quality']);

    return $image;
  }

  /**
   * Add watermark specified in settings to passed image handler
   *
   * @param Varien_Image $image
   *   Image handler
   *
   * @param array $settings
   *   Settings from product's image model
   *
   * @return MVentory_TradeMe_Helper_Image
   *   Instance of this class
   */
  protected function _addWatermark ($image, $settings) {
    $image
      ->setWatermarkPosition($settings['watermark_position'])
      ->setWatermarkImageOpacity($settings['watermark_opacity'])
      ->setWatermarkWidth($settings['watermark_width'])
      ->setWatermarkHeigth($settings['watermark_height'])
      ->watermark($settings['watermark_filepath']);

    return $this;
  }

  /**
   * Add padding around supplied image to make final image size as specified
   * in $size parameter
   *
   * @param Varien_Image $image
   *   Image handler
   *
   * @param array $size
   *   Final image size (array must contain 'width' and 'height' keys)
   *
   * @return MVentory_TradeMe_Helper_Image
   *   Instance of this class
   *
   * @throws Exception
   *   If padding of image failed
   */
  protected function _pad ($image, $size) {
    $adapter = $this->_getImageAdapter($image);

    $width = $size['width'];
    $height = $size['height'];

    $ref = new ReflectionObject($adapter);

    $prop = function ($name, $value = null) use ($ref, $adapter) {
      $p = $ref->getProperty($name);
      $p->setAccessible(true);

      return $value ? $p->setValue($adapter, $value) : $p->getValue($adapter);
    };

    $call = function () use ($ref, $adapter) {
      $args = func_num_args() == 1 && is_array(func_get_arg(0))
                ? func_get_arg(0)
                : func_get_args();

      $m = $ref->getMethod(array_shift($args));
      $m->setAccessible(true);

      return $m->invokeArgs($adapter, $args);
    };

    $handler = imagecreatetruecolor($width, $height);
    $call(array('_fillBackgroundColor', &$handler));

    $oHandler = $prop('_imageHandler');
    $oWidth = $prop('_imageSrcWidth');
    $oHeight = $prop('_imageSrcHeight');

    $result = imagecopy(
      $handler,
      $oHandler,
      ($width - $oWidth) / 2,
      ($height - $oHeight) / 2,
      0,
      0,
      $oWidth,
      $oHeight
    );

    if (!$result) {
      imagedestroy($handler);

      throw new Exception('Padding of image failed');
    }

    $prop('_imageHandler', $handler);
    $call('refreshImageDimensions');

    imagedestroy($oHandler);

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

  protected function _getImageAdapter ($image) {
    $ref = new ReflectionObject($image);

    $method = $ref->getMethod('_getAdapter');
    $method->setAccessible(true);

    return $method->invoke($image);
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
   * Calculate size for scaling
   *
   * @param int $padding
   *   Padding value
   *
   * @param array $size
   *   Final size
   *
   * @return array
   *   Size for scaling
   */
  protected function _getScaleSize ($padding, $size) {
    return $padding
             ? array(
                 'width' => $size['width'] - 2 * $padding,
                 'height' => $size['height'] - 2 * $padding
               )
             : array();
  }

  /**
   * Return padding value from store's config
   *
   * @param Mage_Core_Model_Store $store
   *   Store object
   *
   * @return string
   *   Padding
   */
  protected function _getPadding ($store) {
    return (int) $store->getConfig(MVentory_TradeMe_Model_Config::_IMG_PADDING);
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