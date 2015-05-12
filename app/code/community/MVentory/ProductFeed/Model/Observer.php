<?php

class MVentory_ProductFeed_Model_Observer
{
  public function generateFeeds ($observer) {
    $categories = 166;
    $attributeSets = array(9, 19, 20, 21, 22, 23, 24);

    $feed = new MVentory_ProductFeed_Model_Feed();

    $feed->generate(array(
      'feedfor' => MVentory_ProductFeed_Helper_Data::FEED_PRICEME,
      'filename' => 'priceme_feed',
      'feed_format' => 'XML',
      'store' => 1,
      'categories' => $categories,
      'attribute_sets' => $attributeSets
    ));

    $feed->generate(array(
      'feedfor' => MVentory_ProductFeed_Helper_Data::FEED_PRICESPY,
      'filename' => 'pricespy_feed',
      'feed_format' => 'TXT',
      'store' => 1,
      'categories' => $categories,
      'attribute_sets' => $attributeSets
    ));
  }
}