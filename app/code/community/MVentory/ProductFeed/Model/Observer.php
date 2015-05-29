<?php

class MVentory_ProductFeed_Model_Observer
{
  public function generateFeeds ($observer) {
    $feed = new MVentory_ProductFeed_Model_Feed();

    $feed->generate(array(
      'feedfor' => MVentory_ProductFeed_Helper_Data::FEED_PRICEME,
      'filename' => 'priceme_feed',
      'feed_format' => 'XML',
      'store' => 1
    ));

    $feed->generate(array(
      'feedfor' => MVentory_ProductFeed_Helper_Data::FEED_PRICESPY,
      'filename' => 'pricespy_feed',
      'feed_format' => 'TXT',
      'store' => 1
    ));
  }
}