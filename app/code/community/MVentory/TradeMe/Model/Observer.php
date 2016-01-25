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
 * Event handlers
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 * @author Andrew Gilman <andrew@mventory.com>
 */
class MVentory_TradeMe_Model_Observer {

  const TAG_FREE_SLOTS = 'tag_trademe_free_slots';
  const TAG_EMAILS = 'tag_trademe_emails';

  const __NO_SHIPPING_S = <<<'EOT'
Warning: mVentory Trade Me account %s does not have a valid shipping configuration. Please import a configuration CSV file.
EOT;
  const __NO_SHIPPING_P = <<<'EOT'
Warning: mVentory Trade Me accounts %s do not have a valid shipping configuration. Please import a configuration CSV file.
EOT;
  const __NO_ATTR = <<<'EOT'
Attribute %s for name variants doesn't exist. Create it and try again or leave blank.
EOT;

  public function sortChildren ($observer) {
    $content = Mage::app()
      ->getFrontController()
      ->getAction()
      ->getLayout()
      ->getBlock('content');

    $matching = $content->getChild('trademe.matching');

    $content
      ->unsetChild('trademe.matching')
      ->append($matching);
  }

  public function addAccountsToConfig ($observer) {
    if (Mage::app()->getRequest()->getParam('section') != 'trademe')
      return;

    $settings = $observer
                  ->getConfig()
                  ->getNode('sections')
                  ->trademe
                  ->groups
                  ->settings;

    $template = $settings
                  ->account_template
                  ->asArray();

    if (!$accounts = Mage::registry('trademe_config_accounts')) {
      $groups = Mage::getSingleton('adminhtml/config_data')
                  ->getConfigDataValue('trademe');

      $accounts = array();

      if ($groups) foreach ($groups->children() as $id => $account)
        if (strpos($id, 'account_', 0) === 0)
          $accounts[$id] = (string) $account->name;

      unset($id);
      unset($account);

      $accounts['account_' . str_replace('.', '_', microtime(true))]
        = '+ Add account';
    }

    $noAccounts = count($accounts) == 1;

    $position = 0;

    foreach ($accounts as $id => $account) {
      $group = $settings
                 ->fields
                 ->addChild($id);

      $group->addAttribute('type', 'group');

      if (isset($template['@']))
        foreach ($template['@'] as $key => $value)
          $group->addAttribute($key, $value);

      $group->addChild('frontend_model', 'trademe/account');
      $group->addChild('label', $account);
      $group->addChild('show_in_default', 0);
      $group->addChild('show_in_website', 1);
      $group->addChild('show_in_store', 0);
      $group->addChild('expanded', (int) $noAccounts);
      $group->addChild('sort_order', $position++);

      if (isset($template['comment']))
        $group->addChild('comment', $template['comment']);

      $fields = $group->addChild('fields');

      foreach ($template as $name => $field) {
        if (!is_array($field) || $name == '@')
          continue;

        $node = $fields->addChild($name);

        if (isset($field['@'])) {
          foreach ($field['@'] as $key => $value)
            $node->addAttribute($key, $value);

          unset($field['@']);

          unset($key);
          unset($value);
        }

        foreach ($field as $key => $value)
          $node->addChild($key, $value);

        unset($key);
        unset($value);
      }
    }
  }

  public function restoreNewAccountInConfig ($observer) {
    $configData = $observer->getObject();

    if ($configData->getSection() != 'trademe')
      return;

    $groups = $configData->getGroups();

    $accounts = array();

    foreach ($groups as $id => $group)
      if (strpos($id, 'account_', 0) === 0)
        if ($group['fields']['name']['value']
            && $group['fields']['key']['value']
            && $group['fields']['secret']['value'])
          $accounts[$id] = $group['fields']['name']['value'];
        else
          unset($groups[$id]);

    $configData->setGroups($groups);

    Mage::register('trademe_config_accounts', $accounts);
  }

  /**
   * Main cron method
   *
   * @param Mage_Cron_Model_Schedule $job Cron job
   * @return null
   */
  public function sync ($job) {
    $helper = Mage::helper('trademe/auction');
    $productHelper = Mage::helper('mventory/product');

    //Get website and its default store from current cron job
    list($website, $store) = $this->_getWebsiteAndStore($job);

    //Load TradeMe accounts which are used in specified website
    //Unset Random pseudo-account
    $accounts = $helper->getAccounts($website);
    unset($accounts[null]);

    //Cache loaded objects and data for re-using
    $this->_helper = $helper;
    $this->_productHelper = $productHelper;
    $this->_tmProductHelper = Mage::helper('trademe/product');
    $this->_website = $website;
    $this->_store = $store;
    $this->_accounts = $accounts;

    $this->_isMultAuctionsEnabled = $helper->isMultipleAuctionsEnabled(
      $store
    );

    $this->_isStockManagedInStore = (int) $store->getConfig(
      Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK
    );

    $this->_isDollarAuctionExclusive = $helper->isDollarAuctionExclusive(
      $store
    );

    //Sync current auctions and list new
    $this->_automatedWithdrawal();
    $this->_syncAllAuctions();
    $this->_listNormalAuctions();
    $this->_listFixedEndAuctions($job);
  }

  /**
   * Sync current auctions
   *
   * @return null
   */
  protected function _syncAllAuctions () {
    $canCreateOrder = Mage::getStoreConfigFlag(
      MVentory_TradeMe_Model_Config::_ORDER_ALLOW,
      $this->_store
    );

    foreach ($this->_accounts as $accountId => &$accountData) {
      $auctions = Mage::getResourceModel('trademe/auction_collection')
        ->addFieldToFilter('account_id', $accountId);

      try {
        $connector = new MVentory_TradeMe_Model_Api();

        $connector
          ->setWebsiteId($this->_website)
          ->setAccountId($accountId);

        $accountData['listings'] = $connector->massCheck($auctions);
      }
      catch (Exception $e) {
        //Unset account so it won't be used for listing auctions
        unset($this->_accounts[$accountId]);

        Mage::logException($e);

        MVentory_TradeMe_Model_Log::debug(array(
          'account' => $accountData['name'],
          'error on mass checking of auctions' => $e->getMessage(),
          'error' => true
        ));

        continue;
      }

      MVentory_TradeMe_Model_Log::debug(array(
        'account' => $accountData['name'],
        'number of active auctions' => $accountData['listings']
      ));

      foreach ($auctions as $auction) try {
        if ($auction['is_selling']) {
          MVentory_TradeMe_Model_Log::debug(array(
            'auction' => $auction,
            'status' => 'active'
          ));

          //We don't include $1 auctions in the total quota
          //for the number of listings.
          if ($auction['type']
                == MVentory_TradeMe_Model_Config::AUCTION_FIXED_END_DATE)
            --$accountData['listings'];

          continue;
        }

        try {
          $listingDetails = $connector->getListingDetails($auction);
        }
        catch (Exception $e) {
          Mage::logException($e);

          MVentory_TradeMe_Model_Log::debug(array(
            'auction' => $auction,
            'error on getting status' => $e->getMessage(),
            'error' => true
          ));

          continue;
        }

        $result = $this->_helper->getSaleStatus($listingDetails);

        MVentory_TradeMe_Model_Log::debug(array(
          'auction' => $auction,
          'status' => $result
        ));

        //Auction is on sell
        if ($result == 3) {

          //Increase number of active auctions to include auctions
          //which weren't counted by massCheck() method for some reason
          if ($auction['type'] == MVentory_TradeMe_Model_Config::AUCTION_NORMAL)
            ++$accountData['listings'];

          continue;
        }

        //Auction was sold
        if ($result == 2) {
          $product = Mage::getModel('catalog/product')->load(
            $auction['product_id']
          );

          $perShipping = $this
            ->_helper
            ->prepareAccount($accountData, $product, $this->_store);

          if (!$perShipping) {
            MVentory_TradeMe_Model_Log::debug(
              'Error: shipping type '
              . $this->_helper->getShippingType($product, false, $this->_store)
              . ' doesn\'t exists in ' . $accountData['name']
              . ' account. Product SKU: ' . $product->getSku()
            );

            continue;
          }

          //Get sale data from listing details.
          //If sale data is missing then probably the listing is not yet in
          //"closed" state.
          //Try to create order next time
          $saleData = $connector->getSaleDataFromListing($listingDetails);
          if (!$saleData) {
            MVentory_TradeMe_Model_Log::debug(array(
              'auction' => $auction,
              'warning' => 'Sale data is not presented in the listing details'
            ));

            continue;
          }

          if ($canCreateOrder)
            $this->_createOrder(
              $product,
              $perShipping,
              $connector->getSaleDataFromListing($listingDetails)
            );
        }

        $auction->delete();
      } catch (Exception $e) {
        Mage::unregister('mventory_website');

        Mage::logException($e);
      }

      if ($accountData['listings'] < 0) {
        MVentory_TradeMe_Model_Log::debug(array(
          'final number of auctions' => $accountData['listings'],
          'expected number is greater or equal' => 0
        ));

        $accountData['listings'] = 0;
      }
    }
  }

  /**
   * Submit new full-price auctions
   *
   * Maximum number of full-price auctions is limited by product's QTY
   * and by number of name variants. $1 auctions are limited only by
   * available product's QTY
   *
   * 1. Multiple full-price auctions are disabled, $1 auctions are exclusive
   * -----------------------------------------------------------------------
   *
   *    Only one full-price auction or 1$ auctions is allowed per product.
   *
   *    Product pool should contain:
   *
   *      * Not listed products (only full-price type)
   *
   *    Steps:
   *
   *      1. Limit pool by products with full-price auction
   *      2. Filter out products for already listed full-price auctions
   *         from the product pool
   *
   * 2. Multiple full-price auctions are enabled, $1 auctions are exclusive
   * ----------------------------------------------------------------------
   *
   *    Multiple full-price auctions or $1 auctions is allowed per product.
   *
   *    Product pool should contain:
   *
   *      * Not listed products (only full-price type)
   *      * Listed products for full-price auctions with sufficient QTY
   *
   *    Steps:
   *
   *      1. Check if stock of products for full-price auctions is sufficient
   *         to list new auctions.
   *      2. Limit pool by products with full-price auction
   *      3. Filter out products from step 1. with insufficient stock.
   *
   * 3. Multiple full-price auctions are disabled, $1 auctions aren't exclusive
   * --------------------------------------------------------------------------
   *
   *    Only one full-price auction and 1$ auctions are allowed per product.
   *
   *    Product pool should contain:
   *
   *      * Not listed products (any auction type)
   *      * Listed products for $1 auctions with sufficient qty
   *
   *    Steps:
   *
   *      1. Check if stock of products for $1 auctions is sufficient to list
   *         new auctions.
   *      2. Filter out products for already listed full-price auctions
   *         and products from step 1. with insufficient stock from
   *         the product pool
   *
   * 4. Multiple full-price auctions are enabled, $1 auctions aren't exclusive
   * -------------------------------------------------------------------------
   *
   *    Multiple full-price auctions and $1 auctions are allowed per product.
   *
   *    Product pool should contain:
   *
   *      * Not listed products (any auction type)
   *      * Listed products (any auction type) with sufficient QTY
   *
   *   Steps:
   *
   *     1. Check if stock of products for listed auctions is sufficient
   *        to list new auctions.
   *     2. Filter out products from step 1. with insufficient stock.
   *
   * @return null
   */
  protected function _listNormalAuctions () {
    if (!$this->_helper->isInAllowedHours())
      return;

    $cronInterval = (int) $this
      ->_productHelper
      ->getConfig(
          MVentory_TradeMe_Model_Config::CRON_INTERVAL,
          $this->_website
        );

    if ($cronInterval < 1)
      return;

    $interval = MVentory_TradeMe_Model_Config::AUC_TIME_END
                  - MVentory_TradeMe_Model_Config::AUC_TIME_START;

    //Calculate number of runnings of the sync script during 1 day
    $runsNumber = $interval * 60 / $cronInterval - 1;

    if ($runsNumber < 1)
      return;

    //Assign to local variable to preserve original data because the array
    //can be modified
    $accounts = $this->_accounts;

    foreach ($accounts as $accountId => $accountData) {

      //Remember IDs of all existing accounts for further using
      $allAccountsIDs[$accountId] = true;

      MVentory_TradeMe_Model_Log::debug(array(
        'account' => $accountData['name'],
        'max number of listings' => $accountData['max_listings'],
        'number of listings' => $accountData['listings']
      ));

      if (($accountData['max_listings'] - $accountData['listings']) < 1)
        unset($accounts[$accountId]);
    }

    if (!count($accounts))
      return;

    unset($accountId, $accountData);

    $aucResource = Mage::getResourceModel('trademe/auction');

    //Load distinction hashes to use them later and collect IDs of products
    //with insufficient stock for listing new products to filter them out
    //from the product poll
    if ($this->_isMultAuctionsEnabled || !$this->_isDollarAuctionExclusive) {

      //Additional full-price auctions can be listed for products with existing
      //$1 auctions, so need to check stock only for these products
      if (!($this->_isMultAuctionsEnabled || $this->_isDollarAuctionExclusive))
        $mode = MVentory_TradeMe_Model_Config::AUCTION_FIXED_END_DATE;

      //Additional full-price auctions can be listed for products with existing
      //full-price auctions (as name variants), so need to check stock
      //only for these products
      elseif ($this->_isMultAuctionsEnabled && $this->_isDollarAuctionExclusive)
        $mode = MVentory_TradeMe_Model_Config::AUCTION_NORMAL;

      //Additional full-price auctions can be listed for products with existing
      //auctions of any type, so no need to filter hashes by the auction type
      else
        $mode = null;

      $hashes = $aucResource->getDistinctionHashes($mode);

      foreach ($hashes as $productId => $auctions) {
        $qty = $this->_getProductQty($productId);

        if (!$qty || ($qty !== true && $qty <= $auctions['count']))
          $filterIds[] = $productId;
      }
    }

    $listNormAuc = (int) $this
      ->_store
      ->getConfig(MVentory_TradeMe_Model_Config::_1AUC_FULL_PRICE);

    $products = Mage::getModel('catalog/product')
      ->getCollection()
      ->addAttributeToFilter('type_id', 'simple')

      //Load all allowed to list products (incl. for $1 dollar auctions)
      //or only products with full-price auction if $1 auctions are exclusive
      ->addAttributeToFilter(
          'tm_relist',
          $this->_isDollarAuctionExclusive
            ? MVentory_TradeMe_Model_Config::LIST_YES
            : ['gt' => MVentory_TradeMe_Model_Config::LIST_NO]
        )
      ->addAttributeToFilter('image', array('nin' => array('no_selection', '')))
      ->addAttributeToFilter(
          'status',
          Mage_Catalog_Model_Product_Status::STATUS_ENABLED
        )
      ->addStoreFilter($this->_store);

    $this->_tmProductHelper->addStockStatusFilter(
      $products,
      $this->_store
    );

    //Filter out products with insufficient stock for submitting new auctions
    if (isset($filterIds)) {
      $products->addIdFilter($filterIds, true);
      unset($filterIds);
    }

    //Filter out products with existing full-price auctions because multiple
    //full-price auctions are disabled
    if (!$this->_isMultAuctionsEnabled)
      $aucResource->filterNormalAuctions($products);

    $ids = $products->getAllIds();
    MVentory_TradeMe_Model_Log::debug(array('pool size' => count($ids)));

    if (!$ids)
      return;

    unset($productIds, $products);

    //Calculate avaiable slots for current run of the sync script
    foreach ($accounts as $accountId => &$accountData) {
      $cacheId = implode(
        '_',
        array(
          'trademe_sync',
          $this->_website->getCode(),
          $accountId,
        )
      );

      try {
        $syncData = unserialize(Mage::app()->loadCache($cacheId));
      } catch (Exception $e) {
        $syncData = null;
      }

      if (!is_array($syncData))
        $syncData = array(
          'free_slots' => 1,
          'duration' => MVentory_TradeMe_Helper_Data::LISTING_DURATION_MAX
        );

      $freeSlots = $accountData['max_listings']
                   / ($runsNumber * $syncData['duration'])
                   + $syncData['free_slots'];

      $_freeSlots = (int) floor($freeSlots);
      MVentory_TradeMe_Model_Log::debug(array(
        'account' => $accountData['name'],
        'free slots' => $_freeSlots
      ));

      $syncData['free_slots'] = $freeSlots - $_freeSlots;

      if ($_freeSlots < 1) {
        Mage::app()->saveCache(
          serialize($syncData),
          $cacheId,
          array(self::TAG_FREE_SLOTS),
          null
        );

        unset($accounts[$accountId]);

        continue;
      }

      $accountData['free_slots'] = $_freeSlots;

      $accountData['cache_id'] = $cacheId;
      $accountData['sync_data'] = $syncData;
    }

    if (!count($accounts))
      return;

    unset($accountId, $accountData, $syncData);

    shuffle($ids);

    foreach ($ids as $id) {
      $product = Mage::getModel('catalog/product')->load($id);

      if (!$id = $product->getId())
        continue;

      $matchResult = Mage::getModel('trademe/matching')
        ->matchCategory($product);

      MVentory_TradeMe_Model_Log::debug(array(
        'product' => $product,
        'matching result' => $matchResult
      ));

      if (!(isset($matchResult['id']) && $matchResult['id'] > 0))
        continue;

      //??? Do we need it?
      //$accountIds = $accountId
      //                ? (array) $accountId
      //                  : array_keys($accounts);

      $accountIds = array_keys($accounts);

      shuffle($accountIds);

      foreach ($accountIds as $accountId) {
        $accountData = $accounts[$accountId];
        $perShipping = $this
          ->_helper
          ->prepareAccount($accountData, $product, $this->_store);

        MVentory_TradeMe_Model_Log::debug(array(
          'product' => $product,
          'account' => $accountData['name'],
          'account data per shipping' => $perShipping
        ));

        if (!$perShipping)
          continue;

        $this->_debugData = [
          'product' => $product,
          'name variants enabled' => $this->_isMultAuctionsEnabled,
          '$1 auctions exclusive' => $this->_isDollarAuctionExclusive
        ];

        if (isset($hashes[$id])) {
          $this->_debugData['stock'] = $this->_getProductQty($id);
          $this->_debugData['number of auctions'] = $hashes[$id]['count'];
        }

        $auctionTitle = $this->_getAuctionTitle(
          $product,
          isset($hashes[$id]) ? $hashes[$id] : null
        );

        $this->_debugData['title'] = $auctionTitle;

        MVentory_TradeMe_Model_Log::debug($this->_debugData);

        //Go to next product if auction title can't be obtained or we have
        //used all name variants
        if (!$auctionTitle)
          continue 2;

        try {
          $api = new MVentory_TradeMe_Model_Api();
          $listingId = $api->send(
            $product,
            $matchResult['id'],
            $accountId,
            array('title' => $auctionTitle)
          );
        }
        catch (Exception $e) {
          $errors = $this->_parseErrors($e->getMessage());

          Mage::logException($e);
          MVentory_TradeMe_Model_Log::debug(array(
            'product' => $product,
            'submit result' => $errors,
            'error' => true
          ));

          foreach ($errors as $error)
            if ($error == 'Insufficient balance') {
              $this->_negativeBalanceError($accountData['name']);

              if (count($accounts) == 1)
                return;

              unset($accounts[$accountId]);

              break;
            }

          //Try next account
          continue;
        }

        MVentory_TradeMe_Model_Log::debug(array(
          'product' => $product,
          'submit result' => $listingId,
          'error' => false
        ));

        Mage::getModel('trademe/auction')
          ->setData([
              'product_id' => $product->getId(),
              'listing_id' => $listingId,
              'account_id' => $accountId,
              'distinction_hash' => $this
                ->_helper
                ->getHash(['title' => $auctionTitle])
            ])
          ->save();

        if (--$accounts[$accountId]['free_slots'] <= 0) {
          $accountData['sync_data']['duration'] = $this
            ->_helper
            ->getDuration($perShipping);

          Mage::app()->saveCache(
            serialize($accountData['sync_data']),
            $accountData['cache_id'],
            array(self::TAG_FREE_SLOTS),
            null
          );

          if (count($accounts) == 1)
            return;

          unset($accounts[$accountId]);
        }

        //We have succesfully listed product go to the next one
        break;
      }
    }
  }

  /**
   * Submit only $1 auctions
   * @param  Mage_Cron_Model_Schedule $job Cron job
   * @return null
   */
  public function _listFixedEndAuctions ($job) {
    //Get website and its default store from current cron job
    list($website, $store) = $this->_getWebsiteAndStore($job);

    $helper = Mage::helper('trademe/auction');
    $productHelper = Mage::helper('mventory/product');

    if (!$helper->isInAllowedPeriod($store))
      return;

    //Load TradeMe accounts which are used in the specified website
    $accounts = $helper->getAccounts($website);

    //Unset Random pseudo-account
    unset($accounts[null]);

    if (!count($accounts))
      return;

    $filterIds = $helper->getProductsListedToday(
      $store,
      MVentory_TradeMe_Model_Config::AUCTION_FIXED_END_DATE
    );

    $auctions = Mage::getResourceSingleton('trademe/auction')
      ->getNumberPerProduct($filterIds);

    foreach ($auctions as $id => $auctionsNumber) {
      $stock = Mage::getModel('cataloginventory/stock_item')
        ->loadByProduct($id);

      if (!$stock->getId())
        continue;

      $manageStock = $stock->getUseConfigManageStock()
                       ? $this->_isStockManagedInStore
                       : (int) $stock['manage_stock'];

      if ($manageStock && ($stock->getQty() - $auctionsNumber < 1))
        $filterIds[] = $id;
    }

    unset($auctions);

    $products = Mage::getModel('catalog/product')
      ->getCollection()
      ->addAttributeToFilter('type_id', 'simple')

      //Allow only product enabled for $1 auctions
      ->addAttributeToFilter(
          'tm_relist',
          MVentory_TradeMe_Model_Config::LIST_FIXEDEND
        )
      ->addAttributeToFilter('image', array('nin' => array('no_selection', '')))
      ->addAttributeToFilter(
          'status',
          Mage_Catalog_Model_Product_Status::STATUS_ENABLED
        )
      ->addStoreFilter($store);

    if ($filterIds)
      $products->addIdFilter($filterIds, true);

    $this->_tmProductHelper->addStockStatusFilter(
      $products,
      $this->_store
    );

    $ids = $products->getAllIds();
    MVentory_TradeMe_Model_Log::debug(array('pool' => count($ids)));

    if (!$ids)
      return;

    unset($products, $filterIds);

    $auctions = Mage::getResourceSingleton('trademe/auction')
      ->getNumberPerProduct(
          null,
          MVentory_TradeMe_Model_Config::AUCTION_FIXED_END_DATE
        );

    shuffle($ids);

    foreach ($ids as $id) {
      $product = Mage::getModel('catalog/product')->load($id);

      if (!$productId = $product->getId())
        continue;

      //Check if current number of product's $1 auctions is smaller than allowed
      //maximum number in the product
      $allowed = $this->_isAllowedNumberOfAuctions(
        isset($auctions[$productId]) ? (int) $auctions[$productId] : 0,
        $product->getData('tm_fixedend_limit')
      );

      MVentory_TradeMe_Model_Log::debug(array(
        'product' => $product,
        'number of auctions' => isset($auctions[$productId])
                                  ? (int) $auctions[$productId]
                                  : 0,
        'product limit' => $product->getData('tm_fixedend_limit'),
        'allowed' => $allowed
      ));

      if (!$allowed)
        continue;

      $matchResult = Mage::getModel('trademe/matching')
        ->matchCategory($product);

      MVentory_TradeMe_Model_Log::debug(array(
        'product' => $product,
        'matching result' => $matchResult
      ));

      if (!(isset($matchResult['id']) && $matchResult['id'] > 0))
        continue;

      $accountIds = array_keys($accounts);

      shuffle($accountIds);

      $shippingType = $this
        ->_helper
        ->getShippingType($product, true, $this->_store);

      foreach ($accountIds as $accountId) {
        $accountData = $accounts[$accountId];
        $perShipping = $this
          ->_helper
          ->prepareAccount($accountData, $product, $this->_store);

        MVentory_TradeMe_Model_Log::debug(array(
          'product' => $product,
          'account' => $accountData['name'],
          'account data per shipping' => $perShipping
        ));

        if (!$perShipping)
          continue;

        try {
          $api = new MVentory_TradeMe_Model_Api();
          $listingId = $api->send(
            $product,
            $matchResult['id'],
            $accountId,
            array(
              'price' => 1,
              'allow_buy_now' => false,
              'duration' => (int) $store->getConfig(
                MVentory_TradeMe_Model_Config::_1AUC_DURATION
              )
            )
          );
        }
        catch (Exception $e) {
          $errors = $this->_parseErrors($e->getMessage());

          Mage::logException($e);
          MVentory_TradeMe_Model_Log::debug(array(
            'product' => $product,
            'submit result' => $errors,
            'error' => true
          ));

          foreach ($errors as $error)
            if ($error == 'Insufficient balance') {
              $this->_negativeBalanceError($accountData['name']);

              if (count($accounts) == 1)
                return;

              unset($accounts[$accountId]);

              break;
            }

          //Try next account
          continue;
        }

        MVentory_TradeMe_Model_Log::debug(array(
          'product' => $product,
          'submit result' => $listingId,
          'error' => false
        ));

        Mage::getModel('trademe/auction')
          ->setData(array(
              'product_id' => $product->getId(),
              'type' => MVentory_TradeMe_Model_Config::AUCTION_FIXED_END_DATE,
              'listing_id' => $listingId,
              'account_id' => $accountId,
              'distinction_hash' => $this
                ->_helper
                ->getHash(['title' => $product->getName()])
            ))
          ->save();

        break;
      }
    }
  }

  public function removeListing ($observer) {
    if (Mage::registry('trademe_disable_withdrawal'))
      return;

    $order = $observer
               ->getEvent()
               ->getOrder();

    $items = $order->getAllItems();

    $productHelper = Mage::helper('mventory/product');

    $productsToUnlink = [];

    foreach ($items as $item) {
      $stockItem = Mage::getModel('cataloginventory/stock_item')
        ->loadByProduct($item->getProductId());

      if (!($stockItem->getManageStock() && $stockItem->getQty() == 0))
        continue;

      $product = Mage::getModel('catalog/product')->load($item->getProductId());
      if ($product->getId())
        $productsToUnlink[] = $product;
    }

    if (!$productsToUnlink)
      return;

    $errors = $this->_removeAuctions($productsToUnlink);

    if ($errors) foreach ($errors as $error) {
      //Send email with error message to website's general contact address

      $productUrl = $productHelper->getUrl($error['product']);
      $listingId = $error['auction']->getUrl($website);

      $subject = 'TradeMe: error on removing listing';
      $message = 'Error on increasing price or withdrawing listing ('
                 . $listingId
                 . ') linked to product ('
                 . $productUrl
                 . ')'
                 . ' Error: ' . $error['exception']->getMessage();

      $productHelper->sendEmail($subject, $message);
    }
  }

  protected function _removeAuctions ($products) {
    $helper = Mage::helper('trademe');
    $productHelper = Mage::helper('mventory/product');
    $api = new MVentory_TradeMe_Model_Api();
    $errorsData = [];

    foreach ($products as $product) {
      $auctions = Mage::getResourceModel('trademe/auction_collection')
        ->addFieldToFilter('product_id', $product->getId());

      if (!count($auctions))
        continue;

      $attrs = $product->getAttributes();
      $website = $productHelper->getWebsite($product);
      $accounts = $helper->prepareAccounts(
        $helper->getAccounts($website),
        $product
      );

      foreach ($auctions as $auction) {
        $accountId = $auction['account_id'];
        $account = $accountId && isset($accounts[$accountId])
          ? $accounts[$accountId]
          : null;

        $fields = $helper->getFields($product, $account);

        //Avoid withdrawal by default
        $avoidWithdrawal = true;

        if (isset($attrs['tm_avoid_withdrawal'])) {
          $attr = $attrs['tm_avoid_withdrawal'];

          if ($attr->getDefaultValue() != $fields['avoid_withdrawal']) {
            $options = $attr
              ->getSource()
              ->toOptionArray();

            if (isset($options[$fields['avoid_withdrawal']]))
              $avoidWithdrawal = (bool) $fields['avoid_withdrawal'];
          }
        }

        try {
          if ($avoidWithdrawal) {
            $price = $product->getPrice() * 5;

            if ($fields['add_fees'])
              $price = $helper->addFees($price);

            $api->update($product, $auction, ['StartPrice' => $price]);

          }
          else
            $api
              ->setWebsiteId($website)
              ->remove($auction);

          $auction->delete();
        }
        catch (Exception $e) {
          Mage::logException($e);

          $error = $e->getMessage();

          $errorsData[] = [
            'exception' => $e,
            'product' => $products,
            'auction' => $auction
          ];
        }
      }
    }

    return $errorsData;
  }

  public function addTradeMeData ($observer) {
    $website = $observer->getWebsite();
    $product = $observer
      ->getProduct()
      ->getData();

    $helper = Mage::helper('mventory/product');
    $trademe = Mage::helper('trademe');

    $accounts = $trademe->getAccounts($website);
    $auction = Mage::getModel('trademe/auction')->loadByProduct(
      $product['product_id']
    );

    $id = $auction['account_id'];
    $account = $id && isset($accounts[$id]) ? $accounts[$id] : null;

    $data = $trademe->getFields($product, $account);

    $data['account'] = $id;
    $data['listing'] = $auction['listing_id'];

    if ($data['listing']) {
      $data['listing_url']
        = 'http://www.'
          . ($trademe->isSandboxMode($website) ? 'tmsandbox' : 'trademe')
          . '.co.nz/Browse/Listing.aspx?id='
          . $data['listing'];
    }

    $data['shipping_types']
      = Mage::getModel('trademe/attribute_source_freeshipping')
          ->getAllOptions();

    foreach ($accounts as $id => $account)
      $data['accounts'][$id] = $account['name'];

    $matchResult = Mage::getModel('trademe/matching')->matchCategory(
      Mage::getModel('catalog/product')->load($product['product_id'])
    );

    if ($matchResult)
      $data['matched_category'] = $matchResult;

    $shippingAttr = $trademe->getShippingAttr($website->getDefaultStore());

    //Add shipping rate if product's shipping type is 'tab_ShipTransport'
    if (isset($product[$shippingAttr]) && $account) {
      $do = false;

      //!!!TODO: this needs to be optimised and cached
      $attrs = Mage::getModel('mventory/product_attribute_api')
        ->fullInfoList($product['set']);

      //Iterate over all attributes...
      foreach ($attrs as $attribute)
        //... to find attribute with shipping type info, then...
        if ($attribute['attribute_code'] == $shippingAttr)
          //... iterate over all its options...
          foreach ($attribute['options'] as $option)
            //... to find option with same value as in product and with
            //label equals 'tab_ShipTransport'
            if ($option['value'] == $product[$shippingAttr]
                && $do = ($option['label'] == 'tab_ShipTransport'))
              break 2;

      if ($do) {
        $rate = Mage::helper('trademe')->getShippingRate(
          $observer->getProduct(),
          $account['name'],
          $website
        );

        if ($rate !== null)
          $data['shipping_rate'] = $rate;
      }
    }

    $product['auction']['trademe'] = $data;

    //!!!TODO: remove after the app upgrade. This is for compatibility with
    //old versions of the app
    $product['tm_options'] = $this->_getTmOptions($product);

    //!!!TODO: remove after the app upgrade. This is for compatibility with
    //old versions of the app
    if (isset($product['auction']['trademe']['shipping_rate']))
      $product['shipping_rate']
        = $product['auction']['trademe']['shipping_rate'];
    else
      $product['shipping_rate'] = null;

    $observer
      ->getProduct()
      ->setData($product);
  }

  public function setAllowListingFlag ($observer) {
    $product = $observer->getProduct();

    if ($product->getId())
      return;

    $helper = Mage::helper('mventory');

    $product->setData(
      'tm_relist',
      (bool) $helper->getConfig(
        MVentory_TradeMe_Model_Config::ENABLE_LISTING,
        $helper->getCurrentWebsite()
      )
    );
  }

  public function showNoticeOnSettingsSave ($observer) {
    if (!$website = $observer->getWebsite())
      return;

    $accounts = Mage::helper('trademe')->getAccounts($website);

    foreach ($accounts as $account)
      if (!(isset($account['shipping_types']) && $account['shipping_types']))
        $noOptions[] = $account['name'];

    if (!isset($noOptions))
      return;

    Mage::getSingleton('adminhtml/session')->addWarning(
      Mage::helper('trademe')->__(
        count($noOptions) > 1 ? self::__NO_SHIPPING_P : self::__NO_SHIPPING_S,
        implode(', ', $noOptions)
      )
    );
  }

  /**
   * Check if entered attribute code of attribute with name variants for
   * auctions is valid
   *
   * @param Varien_Event_Observer $observer Event observer
   * @return MVentory_TradeMe_Model_Observer
   */
  public function checkNameVariantsAttr ($observer) {
    $config = $observer['object'];

    if ($config['section'] != 'trademe')
      return $this;

    $path = explode('/', MVentory_TradeMe_Model_Config::_AUC_NAME_VAR_ATTR);
    $key = array('groups', $path[1], 'fields', $path[2], 'value');

    if (!$code = trim($config->getData(implode('/', $key))))
      return $this;

    $id = Mage::getResourceModel('eav/entity_attribute')->getIdByCode(
      Mage_Catalog_Model_Product::ENTITY,
      $code
    );

    if ($id)
      return $this;

    $groups = $config['groups'];
    $groups[$key[1]][$key[2]][$key[3]][$key[4]] = '';
    $config['groups'] = $groups;

    Mage::getSingleton('adminhtml/session')->addWarning(
      Mage::helper('trademe')->__(self::__NO_ATTR, $code)
    );

    return $this;
  }

  /**
   * Return cron job's website and website's default store
   *
   * @param Mage_Cron_Model_Schedule $job Cron job
   * @return array Array of Website and its default store
   */
  protected function _getWebsiteAndStore ($job) {
    $website = Mage::app()->getWebsite(
      (string) Mage::getConfig()
        ->getNode('default/crontab/jobs')
        ->{$job->getJobCode()}
        ->website
    );

    return array($website, $website->getDefaultStore());
  }

  /**
   * Check if current number auction for product is smaller than max number
   * per product. Checks for global max number if product doesn't have its own
   * value.
   *
   * @param int $number
   *   Current number of auctions
   *
   * @param string|null $max
   *   Maximum number of auctions per products. Null value or empty string value
   *   are treated as unlimited number
   *
   * @return boolean
   *   Result fo check
   */
  protected function _isAllowedNumberOfAuctions ($number, $max) {
    if ($max === null ||  ($max = trim($max)) === '')
      $max = $this
        ->_store
        ->getConfig(MVentory_TradeMe_Model_Config::_1AUC_LIMIT);

    if ($max === null ||  ($max = trim($max)) === '')
      return true;

    return (int) $number < (int) $max;
  }

  /**
   * Return title for auction.
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param array $hashesData
   *   Distinction hashes for the product
   *
   * @return string|null
   *   Returns auction title or null if it can't be obtained
   */
  protected function _getAuctionTitle ($product, $hashesData) {
    //We can simply choose one name variant from all if there're no hashes
    //for full-price auctions (it means the product is being listed for
    //the first time) or multiple full-price auctions are disabled

    $canUseNameVariants = $this->_isMultAuctionsEnabled
                          && $hashesData
                          && $hashesData['hashes'][0];

    if (!$canUseNameVariants)
      return $this->_helper->getTitle($product, $this->_store);

    //Get all available name variants for the product
    $names = Mage::helper('trademe/product')->getNameVariants(
      $product,
      $this->_store
    );

    $this->_debugData['number of available name variants'] = count($names);

    //We don't list the product if name variants can't be obtained or all name
    //variants have been already listed
    if (!$names || count($hashesData['hashes'][0]) >= count($names))
      return null;

    //Calculate distinction hashes for all available name variants
    $namesHashes = [];
    foreach ($names as $name) {
      $hash = $this->_helper->getHash(['title' => $name]);
      $namesHashes[$hash] = $name;
    }

    //Get not used name variants
    $notUsedNames = array_diff_key($namesHashes, $hashesData['hashes'][0]);

    //Pick and return one of not used name variants
    return $notUsedNames[array_rand($notUsedNames)];
  }

  /**
   * Return QTY for the specified product
   *
   * @param Mage_Catalog_Model_Product|int $product
   *   Product model or product ID
   *
   * @return int|bool|null
   *   Returns QTY number or null if stock item can't be loaded or true if
   *   stock is not managed
   */
  protected function _getProductQty ($product) {
    $productId = $product instanceof Mage_Catalog_Model_Product
      ? $product->getId()
      : $product;

    if (isset($this->_productQtys[$productId]))
      return $this->_productQtys[$productId];

    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct(
      $productId
    );

    if (!$stockItem->getId())
      return $this->_productQtys[$productId] = null;

    $isStockManage = $stockItem->getUseConfigManageStock()
      ? $this->_isStockManagedInStore
      : (bool) $stockItem['manage_stock'];

    return $this->_productQtys[$productId]
      = ($isStockManage ? $stockItem->getQty() : true);
  }

  /**
   * Prepare data and create order for the specified product and account
   * data
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param array $account
   *   Account data
   */
  protected function _createOrder ($product, $account, $saleData) {
    MVentory_TradeMe_Model_Log::debug();

    list($buyer, $address) = Mage::helper('trademe/buyer')->get(
      $saleData,
      $this->_store
    );

    MVentory_TradeMe_Model_Log::debug(array('buyer' => $buyer));

    if (!$buyer)
      return;

    //Set global flag to prevent removing product from TradeMe during
    //order creating. No need to remove it because it was bought
    //on TradeMe. The flag is used in removeListing() method
    Mage::register('trademe_disable_withdrawal', true, true);

    //Set global flag to enable mVentory shipping method in MVentory API
    //extension
    Mage::register('mventory_allow_shipping', true, true);

    try {
      //Make order for the product
      $order = Mage::helper('trademe/order')->create(
        $product,

        //Final product price without taxes
        $this->_tmProductHelper->getPrice($product, $this->_website, false),

        1, //QTY
        $buyer,
        $address,
        $this->_store
      );

      MVentory_TradeMe_Model_Log::debug(array(
        'result' => $order->getIncrementId()
      ));
    } catch (Exception $e) {
      MVentory_TradeMe_Model_Log::debug(array(
        'error on order creation' => $e->getMessage()
      ));

      throw $e;
    }
  }

  /**
   * Report to store owner about insufficient balance in TradeMe account.
   * It sends email only 1 time per hour.
   *
   * @param string $accountName
   *   Name of account tor report about
   *
   * @return MVentory_TradeMe_Model_Observer
   *   Instance of this class
   */
  protected function _negativeBalanceError ($accountName) {
    $app = Mage::app();

    $cacheId = implode(
      '_',
      array(
        $this->_website->getCode(),
        $accountName,
        'negative_balance'
      )
    );

    //Don't send email if cache record hasn't expired
    if ($app->loadCache($cacheId))
      return $this;

    $this
      ->_productHelper
      ->sendEmailTmpl(
          'trademe_negative_balance',
          array('account' => $accountName),
          $this->_website
        );

    $app->saveCache(true, $cacheId, array(self::TAG_EMAILS), 3600);

    return $this;
  }

  /**
   * Parse and prepare errors from TradeMe API response
   *
   * @param string $errors
   *   Raw string of errors from TradeMe API response
   *
   * @return array
   *   Parse and prepare list of errors
   */
  protected function _parseErrors ($errors) {
    $errors = explode("\r\n", $errors);

    array_walk($errors, 'trim');

    return array_filter($errors);
  }

  /**
   * !!!TODO: remove after the app upgrade. This is for compatibility with
   * old versions of the app
   */
  private function _getTmOptions ($product) {
    $data = $product['auction']['trademe'];

    $data['account_id'] = $data['account'];
    $data['add_tm_fees'] = $data['add_fees'];
    $data['tm_listing_id'] = $data['listing'];
    $data['shipping_types_list'] = $data['shipping_types'];

    if (isset($data['accounts']))
      $data['tm_accounts'] = $data['accounts'];

    if (isset($data['matched_category']))
      $data['preselected_categories'][$data['matched_category']['id']]
        = $data['matched_category']['category'];

    unset(
      $data['account'],
      $data['listing'],
      $data['shipping_types'],
      $data['accounts'],
      $data['shipping_rate']
    );

    return $data;
  }


  /**
   * Cron function to withdraw active listing when related products
   * become out of stock
   *
   * TEMPORARY FIX ONLY. Required because we do not track alternative name auctions
   * The method works by pulling all auctions from TM and matching their SKUs to a list of
   * SKUs of all products enabled for TM and out_of_stock and withdrawing any matches.
   *
   */
  public function _automatedWithdrawal () {
    $helper = Mage::helper('trademe');
    $connector = new MVentory_TradeMe_Model_Api();
    $websites = Mage::app()->getWebsites();

    $withdrawnAuctions = array();

    foreach ($websites as $websiteId => $website) {
      $enabled = $website->getConfig(
        MVentory_TradeMe_Model_Config::_AUTO_WITHDRAW
      );

      if (!$enabled)
        continue;

      if (!$accounts = $helper->getAccounts($website, false))
        continue;

      if (!$skus = $this->_getSkus(array($websiteId)))
        continue;

      $websiteCode = $website->getCode();

      $connector->setWebsiteId($website);

      foreach ($accounts as $accountId => $account) {
        $connector->setAccountId($accountId);

        try {
          $auctions = $connector->getAllSellingAuctions();
        }
        catch (Exception $e) {
          Mage::logException($e);

          MVentory_TradeMe_Model_Log::debug(array(
            'website' => $websiteCode,
            'account' => $accountId,
            'error on getting selling auctions' => $e->getMessage(),
            'error' => true
          ));

          continue;
        }

        if (!$auctions)
          continue;

        foreach ($auctions as $auction) {
          if (!(isset($auction['SKU'])
            && ($sku = $auction['SKU'])
            && isset($skus[$sku])))
            continue;

          try {
            $result = $connector->remove(array(
              'account_id' => $accountId,
              'listing_id' => $auction['ListingId']
            ));
          }
          catch (Exception $e) {
            Mage::logException($e);
            $result = $e->getMessage();
          }

          if ($result === true)
            $withdrawnAuctions[] = $auction['ListingId'];

          MVentory_TradeMe_Model_Log::debug(array(
            'website' => $websiteCode,
            'account' => $accountId,
            'sku' => $sku,
            'auction' => $auction['ListingId'],
            'result' => $result,
            'error' => $result !== true
          ));
        }
      }
    }

    if ($withdrawnAuctions)
      $this->_unlinkAuctions($withdrawnAuctions);

  }

  /**
   * Get SKU fro all out of stock products which are allowed for TradeMe
   * and are assigned to the specified website ID
   *
   * @param array $website
   *   Website IDs to filter product
   *
   * @return array
   *   List of SKU of out of stock products
   */
  protected function _getSkus ($website) {
    return $collection = Mage::getResourceModel('trademe/product_collection')
      ->addAttributeToFilter('type_id', 'simple')

      //Load all allowed to list products (incl. for $1 dollar auctions)
      ->addAttributeToFilter('tm_relist', array('gt' => 0))
      ->addAttributeToFilter('image', array('nin' => array('no_selection', '')))
      ->addAttributeToFilter(
        'status',
        Mage_Catalog_Model_Product_Status::STATUS_ENABLED
      )
      ->addWebsiteFilter($website)
      ->joinField(
        'inventory_in_stock',
        'cataloginventory/stock_item',
        'is_in_stock',
        'product_id=entity_id',
        '{{table}}.is_in_stock = 0'
      )
      ->getAllSkus();
  }

  /**
   * Unlink auctions from related pruducts by supplied list of listing IDs
   *
   * @param array $auctions
   *   List of listing IDs to unlink
   *
   * @return MVentory_TradeMe_Model_Cron
   *   Instance of this class
   */
  protected function _unlinkAuctions ($auctions) {
    Mage::getResourceModel('trademe/auction_collection')
      ->addFieldToFilter('listing_id', array('in' => $auctions))
      ->delete();

    return $this;
  }
}
