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
 */

/**
 * TradeMe config model
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Config
{
  const SANDBOX = 'trademe/settings/sandbox';
  const CRON_INTERVAL = 'trademe/settings/cron';
  const MAPPING_STORE = 'trademe/settings/mapping_store';
  const ENABLE_LISTING = 'trademe/settings/enable_listing';
  const LIST_AS_NEW = 'trademe/settings/list_as_new';
  const _NAME_VARIANTS_ATTR = 'trademe/settings/name_variants_attr';
  const _SHIPPING_ATTR = 'trademe/settings/shipping_attr';
  const _PAYMENT_METHODS = 'trademe/settings/payment_methods';
  const _STOCK_STATUS = 'trademe/settings/stock_status';

  const _WATERMARK_IMG = 'trademe/watermark/image';
  const _WATERMARK_SIZE = 'trademe/watermark/size';
  const _WATERMARK_OPC = 'trademe/watermark/opacity';
  const _WATERMARK_POS = 'trademe/watermark/position';

  const _1AUC_ENDTIME = 'trademe/one_dollar/end_time';
  const _1AUC_ENDDAYS = 'trademe/one_dollar/end_days';
  const _1AUC_DURATION = 'trademe/one_dollar/duration';
  const _1AUC_FULL_PRICE = 'trademe/one_dollar/list_full_price';
  const _1AUC_LIMIT = 'trademe/one_dollar/limit';

  const TITLE_MAX_LENGTH = 50;
  const DESCRIPTION_MAX_LENGTH = 2048;
  const PAYNOW_PRICE_LIMIT = 3000;

  //TradeMe shipping types
  //const SHIPPING_UNKNOWN = 0;
  //const SHIPPING_NONE = 0;
  const SHIPPING_UNDECIDED = 1;
  //const SHIPPING_PICKUP = 2;
  const SHIPPING_FREE = 3;
  const SHIPPING_CUSTOM = 4;

  //Pickup options
  //const PICKUP_NONE = 0; //None
  const PICKUP_ALLOW = 1;  //Buyer can pickup
  const PICKUP_DEMAND = 2; //Buyer must pickup
  const PICKUP_FORBID = 3; //No pickups

  //Add fees (TradeMe commission) options
  const FEES_NO = 0;      //No commission
  const FEES_ALWAYS = 1;  //Always add commission
  const FEES_SPECIAL = 2; //Only if the product has a current special price

  //TradeMe payment methods
  const PAYMENT_BANK = 1;
  const PAYMENT_CC = 2;
  const PAYMENT_CASH = 4;
  const PAYMENT_SAFE = 8;

  //Allow to list options
  const LIST_NO = 0;
  const LIST_YES = 1;
  const LIST_FIXEDEND = 2;

  const CACHE_TYPE = 'trademe';
  const CACHE_TAG = 'TRADEME';

  //Types of auctions
  //!!!TODO: move to MVentory_TradeMe_Model_Auction class
  const AUCTION_NORMAL = 0;
  const AUCTION_FIXED_END_DATE = 1;

  //Values for List full price setting
  const AUCTION_NORMAL_ALWAYS = 0;
  const AUCTION_NORMAL_STOCK = 1;
  const AUCTION_NORMAL_NEVER = 2;

  //Start and end hours for normal auctions
  const AUC_TIME_START = 7;
  const AUC_TIME_END = 23;

  //Values of Minimum stock level setting
  const STOCK_IN = 0;
  const STOCK_NOT_MANAGED = 1;
  const STOCK_NO = 2;

  //TradeMe currency
  const CURRENCY = 'NZD';

  //TradeMe time zone
  const TIMEZONE = 'Pacific/Auckland';
}
