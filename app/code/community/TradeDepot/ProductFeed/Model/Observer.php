<?php

class TradeDepot_ProductFeed_Model_Observer
{
  public function generateFeeds ($observer) {
    TradeDepot_ProductFeed_Model_Feed::generate(array(
      'feedfor' => TradeDepot_ProductFeed_Helper_Data::FEED_PRICEME,
      'filename' => 'priceme_feed',
      'feed_format' => 'XML',
      'store' => 1
    ));

    TradeDepot_ProductFeed_Model_Feed::generate(array(
      'feedfor' => TradeDepot_ProductFeed_Helper_Data::FEED_PRICESPY,
      'filename' => 'pricespy_feed',
      'feed_format' => 'TXT',
      'store' => 1
    ));
  }
}