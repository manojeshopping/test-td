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
 * TradeMe API
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Api {

  const CACHE_CATEGORIES = 'TRADEME_CATEGORIES';
  const CACHE_CATEGORY_ATTRS = 'TRADEME_CATEGORY_ATTRS';

  const PAYNOW_ERR_MSG = <<<'EOT'
pay now cannot be used with a buy now or reserve price over
EOT;

  const __E_NO_ACCOUNT = <<<'EOT'
Account data is not loaded
EOT;
  const __E_NO_TOKEN = <<<'EOT'
OAuth access token is not available
EOT;
  const __E_TOKEN_INVALID = <<<'EOT'
Unserializing of OAuth access token failed
EOT;
  const __E_ACCOUNT_SHIPPING = <<<'EOT'
Account doesn't contain settings for product's shipping type
EOT;
  const __E_RESPONSE_STATUS = <<<'EOT'
Status of response is %d, expected is %d
EOT;
  const __E_RESPONSE_EMPTY = <<<'EOT'
Response is empty
EOT;
  const __E_RESPONSE_DECODING = <<<'EOT'
Decoding of response failed
EOT;
  const __E_RESPONSE_INCOMPLETE = <<<'EOT'
Response is missing %s field
EOT;
  const __E_IMAGE_FAILED = <<<'EOT'
Uploading of image failed (status: %d)
EOT;
  const __E_AUCTION_DESC = <<<'EOT'
Length of the description exceeded the limit of %d characters
EOT;
  const __E_STORE_PAYMENT = <<<'EOT'
TradeMe Payment methods are not selected
EOT;

  //List of TradeMe categories to ignore. Categories are selected by its number
  private $_ignoreCategories = array(
    //'0001-' => true, //Trade Me Motors
    '0350-' => true, //Trade Me Property
    '5000-' => true, //Trade Me Jobs
    '9374-' => true, //Travel, events & activities
    '9334-' => true, //Services
  );

  private $_imageSize = array('width' => 670, 'height' => 502);

  private $_helper = null;

  private $_config = null;
  private $_host = 'trademe';
  private $_categories = array();

  private $_website = null;

  private $_accountId = null;
  private $_accountData = null;

  private $_attrTypes = array(
    0 => 'None',
    1 => 'Boolean',
    2 => 'Integer',
    3 => 'Decimal',
    4 => 'String',
    5 => 'DateTime',
    6 => 'StayPeriod'
  );

  private $_pickupValues = array(
    1 => 'Allow',
    2 => 'Demand',
    3 => 'Forbid'
  );

  private $_durations = array(
    2 => 'Two',
    3 => 'Three',
    4 => 'Four',
    5 => 'Five',
    6 => 'Six',
    7 => 'Seven'
  );

  private $_paymentMethods = array(
    MVentory_TradeMe_Model_Config::PAYMENT_BANK => 'BankDeposit',
    MVentory_TradeMe_Model_Config::PAYMENT_CC => 'CreditCard',
    MVentory_TradeMe_Model_Config::PAYMENT_CASH => 'Cash',
    MVentory_TradeMe_Model_Config::PAYMENT_SAFE => 'SafeTrader'
  );

  private $_htmlConvert = array(
    //Remove all CR and LF symbols
    '#\R+#s' => '',

    //Replace single tab symbol, &nbsp;, </td> and whitespace symbols with space
    //symbol
    '#\t|&nbsp;|</td>|\h{2,}#is' => ' ',

    //Replace <li> tag with '* ' string
    '#<li[^<>]*>#i' => '* ',

    //Replace all whitespans other than single space with single space
    //Either one [\t\r\n\f\v] and zero or more ws,
    //or two or more consecutive-any-whitespace.
    '#(?>[^\S ]\s*|\s{2,})#ix' => ' ',

    //Replace empty tags with newline symbol
    '#<([a-z1-9]+)[^<>]*>\s*</\g{1}>#is' => "\r\n",

    //Replace opening and ending <div>, <p>, <hx> tags with empty line
    '#</?(div|p|h[1-6])[^<>]*>#i' => "\r\n\r\n",

    //Replace <br>, <br />, </br>, </li>, </tr> tags with newline symbol
    '#</?br[^<>]*>|</li>|</tr>#i' => "\r\n",

    //Remove remained tags
    '#<[^<>]+>#s' => '',

    //Replace multiple empty lines with single line
    '#\R{2,}#s' => "\r\n\r\n",

    //Remove any horizontal space adjacent to newline symbols
    '#\h*(\R+)\h*#' => '$1',

    //Remove all CR and LF symbols from start and end
    '#|^\R+|\R+$#s' => '',
  );

  public function __construct () {
    $this->_helper = Mage::helper('mventory/product');
  }

  private function _getConfig ($path) {
    return $this
      ->_helper
      ->getConfig($path, $this->_getWebsite());
  }

  private function getConfig () {
    if ($this->_config)
      return $this->_config;

    $host = $this->_getConfig(MVentory_TradeMe_Model_Config::SANDBOX)
              ? 'tmsandbox'
                : 'trademe';

    $this->_config = array(
      'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER,
      'version' => '1.0',
      'signatureMethod' => 'HMAC-SHA1',
      'siteUrl' => 'https://secure.' . $host . '.co.nz/Oauth/',
      'requestTokenUrl'
        => 'https://secure.' . $host . '.co.nz/Oauth/RequestToken',
      'userAuthorisationUrl'
        => 'https://secure.' . $host . '.co.nz/Oauth/Authorize',
      'accessTokenUrl'
        => 'https://secure.' . $host . '.co.nz/Oauth/AccessToken',
      'consumerKey' => $this->_accountData['key'],
      'consumerSecret' => $this->_accountData['secret']
    );

    $request = Mage::app()->getRequest();

    //Check if code was invoked during HTTP request.
    //Don't set incorrect value when code is invoked by cron
    if ($request->getHttpHost())
      $this->_config['callbackUrl'] = Mage::helper('core/url')->getCurrentUrl();

    $this->_host = $host;

    return $this->_config;
  }

  private function getWebsiteId ($product) {
    $this->_website = $this
      ->_helper
      ->getWebsite($product);
  }

  public function setWebsiteId ($websiteId) {
    $this->_website = Mage::app()->getWebsite($websiteId);

    return $this;
  }

  /**
   * Getter for _website field
   *
   * @return Mage_Core_Model_Website
   *   Website model
   *
   * @throws LogicException
   *   If _website field is empty
   */
  protected function _getWebsite () {
    if (!$this->_website)
      throw new LogicException('_website field must be set');

    return $this->_website;
  }

  public function setAccountId ($data) {
    if (is_array($data))
      $this->_accountId = isset($data['account_id'])
                            ? $data['account_id']
                              : null;
    else
      $this->_accountId = $data;

    $accounts = Mage::helper('trademe')->getAccounts($this->_getWebsite());

    if ($this->_accountId)
      $this->_accountData = $accounts[$this->_accountId];
    else {
      $this->_accountId = key($accounts);
      $this->_accountData = current($accounts);
    }

    return $this;
  }

  public function auth () {
    if (!$this->_accountData)
      throw new MVentory_TradeMe_AccountException(null, self::__E_NO_ACCOUNT);

    $account = $this->_accountData;

    if (!(isset($account['access_token'])
          && $params = $account['access_token']))
      throw new MVentory_TradeMe_AccountException($account, self::__E_NO_TOKEN);

    if (($params = unserialize($params)) === null)
      throw new MVentory_TradeMe_AccountException(
        $account,
        self::__E_TOKEN_INVALID
      );

    $token = new Zend_Oauth_Token_Access();
    return $token->setParams($params);
  }

  public function send ($product, $categoryId, $data, $overwrite = array()) {
    MVentory_TradeMe_Model_Log::debug();

    $helper = Mage::helper('trademe/auction');

    $this->getWebsiteId($product);
    $this->setAccountId($data);

    $store = $this
      ->_getWebsite()
      ->getDefaultStore();

    $account = $helper->prepareAccount($this->_accountData, $product, $store);

    if (!$account)
      throw new MVentory_TradeMe_AccountException(
        $account,
        self::__E_ACCOUNT_SHIPPING
      );

    if (!$isUpdateOptions = is_array($data))
      $_data = $helper->getFields($product, $account);
    else {
      $_data = $data;

      foreach ($_data as $key => $value)
        if ($value == -1 && isset($account[$key]))
          $_data[$key] = $account[$key];
    }

    MVentory_TradeMe_Model_Log::debug(
      array('final TradeMe options' => $_data)
    );

    $return = 'Error';

    $accessToken = $this->auth();

    if (!$categoryId)
      throw new MVentory_TradeMe_ApiException(
        'Product doesn\'t have matched TradeMe category'
      );

    $shippingType = MVentory_TradeMe_Model_Config::SHIPPING_UNDECIDED;

    Mage::unregister('product');
    Mage::register('product', $product);

    $descriptionTmpl = $account['footer'];

    $description = '';

    if ($descriptionTmpl)
      $description = $this->processDescription(
        $descriptionTmpl,
        $product->getData()
      );

    if (strlen($description)
          > MVentory_TradeMe_Model_Config::DESCRIPTION_MAX_LENGTH)
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_AUCTION_DESC,
        MVentory_TradeMe_Model_Config::DESCRIPTION_MAX_LENGTH
      ));

    //Convert all HTML entities to chars first (to allow entities which
    //are not exists in XML) and then convert special chars to entities
    //to not break XML
    //Set encoding parameter to support PHP < 5.4
    $description = htmlspecialchars(
      html_entity_decode($description, ENT_COMPAT, 'UTF-8')
    );

    $image = Mage::helper('trademe/image')->getImage(
      $product,
      $this->_imageSize,
      $store
    );

    $photoId = $this->uploadImage($image);

    $client = $accessToken->getHttpClient($this->getConfig());
    $client->setUri('https://api.' . $this->_host . '.co.nz/v1/Selling.xml');
    $client->setMethod(Zend_Http_Client::POST);

    $title = $this->_getTitle($product, $store, $overwrite);

    if (strlen($title) > MVentory_TradeMe_Model_Config::TITLE_MAX_LENGTH)
      $title = htmlspecialchars(substr(
        $title,
        0,
        MVentory_TradeMe_Model_Config::TITLE_MAX_LENGTH - 1
      ))
      . '&#8230;';
    else
      $title = htmlspecialchars($title);

    $price = $this->_getPrice(
      $product,
      $account,
      $_data,
      $overwrite,
      $store->getBaseCurrency()
    );

    $buyNow = '';

    if ($this->_getAllowBuyNow($_data, $overwrite))
      $buyNow = '<BuyNowPrice>' . $price . '</BuyNowPrice>';

    $duration = $this->_durations[$this->_getDuration($account, $overwrite)];

    $shippingTypes
      = Mage::getModel('trademe/attribute_source_freeshipping')->toArray();

    $shippingType = $shippingTypes[$shippingType];

    unset($shippingTypes);

    $pickup = $this->_getPickup($_data, $account);
    $pickup = $this->_pickupValues[$pickup];

    $isBrandNew = (int) $this->_getIsBrandNew($product);
    $paymentMethods = $this->_getPaymentMethods($store);

    $tries = 1;

    do {
      $xml = '<ListingRequest xmlns="http://api.trademe.co.nz/v1">
<Category>' . $categoryId . '</Category>
<Title>' . $title . '</Title>
<Description><Paragraph>' . $description . '</Paragraph></Description>
<StartPrice>' . $price . '</StartPrice>
<ReservePrice>' . $price . '</ReservePrice>'
. $buyNow .
'<Duration>' . $duration . '</Duration>
<Pickup>' . $pickup . '</Pickup>
<IsBrandNew>' . $isBrandNew . '</IsBrandNew>
<SendPaymentInstructions>true</SendPaymentInstructions>';

      if (isset($account['category_image']) && $account['category_image'])
        $xml .= '<HasGallery>true</HasGallery>';

      $xml .= '<PhotoIds><PhotoId>' . $photoId . '</PhotoId></PhotoIds>';

      if ($photoId) {
        $xml .= '<PhotoIds><PhotoId>' . $photoId . '</PhotoId></PhotoIds>';
      }

      $xml .= '<ShippingOptions>';

      if (isset($account['shipping_options']) && $account['shipping_options'])
        foreach ($account['shipping_options'] as $shippingOption)
          $xml .= '<ShippingOption><Type>Custom</Type><Price>'
                  . $shippingOption['price']
                  . '</Price><Method>'
                  . $shippingOption['method']
                  . '</Method></ShippingOption>';
      else
        $xml .= '<ShippingOption><Type>'
                . $shippingType
                . '</Type></ShippingOption>';

      $xml .= '</ShippingOptions>';

      $xml .= '<PaymentMethods><PaymentMethod>'
              . implode(
                  '</PaymentMethod><PaymentMethod>',
                  array_intersect_key(
                    $this->_paymentMethods,
                    array_flip($paymentMethods)
                  )
                )
              . '</PaymentMethod></PaymentMethods>';

      /**
       * @todo Temporarily disabled. Matching code is buggy in some corner cases
       * and should be fixed and refactored.
       */
      //$attributes = $this->getCategoryAttrs($categoryId);
      $attributes = false;

      if ($attributes) {
        $attributes = $helper->fillAttributes(
          $product,
          $attributes,
          $helper->getMappingStore()
        );

        if ($attributes['error']) {
          if (isset($attributes['required']))
            return 'Product has empty "' . $attributes['required']
                   . '" attribute';

          if (isset($attributes['no_match']))
            return 'Error in matching "' . $attributes['no_match']
                   . '" attribute: incorrect value in "fake" store';
        }

        if ($attributes = $attributes['attributes']) {
          $xml .= '<Attributes>';

          foreach ($attributes as $attributeName => $attributeValue) {
            $xml .= '<Attribute>';
            $xml .= '<Name>' . htmlspecialchars($attributeName) . '</Name>';
            $xml .= '<Value>' . htmlspecialchars($attributeValue) . '</Value>';
            $xml .= '</Attribute>';
          }

          $xml .= '</Attributes>';
        }
      }

      $xml .=  '<SKU>' . htmlspecialchars($product->getSku()) . '</SKU>';
      $xml .= '</ListingRequest>';

      $client->setRawData($xml, 'application/xml');
      $response = $client->request();

      if (($status = $response->getStatus()) != 200)
        throw new MVentory_TradeMe_ApiException(sprintf(
          self::__E_RESPONSE_STATUS,
          $status,
          200
        ));

      $body = $response->getBody();

      if ($body === '')
        throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_EMPTY);

      $xml = simplexml_load_string($body);

      if ($xml === false)
        throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_DECODING);

      if (strtolower(trim((string) $xml->Success)) != 'true') {
        $errors = (string) $xml->Description;

        foreach ($this->_parseErrors($errors) as $error) {
          $hasPayNowError = false !== strrpos(
            strtolower($error),
            self::PAYNOW_ERR_MSG,
            -strlen($error)
          );

          if (!$hasPayNowError)
            continue;

          $paymentMethods = array_diff(
            $paymentMethods,
            array(MVentory_TradeMe_Model_Config::PAYMENT_CC)
          );

          MVentory_TradeMe_Model_Log::debug(array(
            'price' => $price,
            'removed payment method' => 'Credit card'
          ));

          if (!$paymentMethods)
            throw new MVentory_TradeMe_ApiException(self::__E_STORE_PAYMENT);

          $tries++;

          //We found pay now error, so we don't need to check for others
          continue 2;
        }

        throw new MVentory_TradeMe_ApiException($errors);
      }

      if (!($xml->ListingId && ($listingId = (int) $xml->ListingId)))
        throw new MVentory_TradeMe_ApiException(sprintf(
          self::__E_RESPONSE_INCOMPLETE,
          'ListingId'
        ));

      if ($isUpdateOptions)
        $helper->setFields($product, $data);
    } while (--$tries);

    return $listingId;
  }

  /**
   * NOTE: requires $this->_website to be set
   *
   * @param MVentory_TradeMe_Model_Auction $auction Auction
   * @return bool
   */
  public function remove ($auction) {
    MVentory_TradeMe_Model_Log::debug();

    $this->setAccountId($auction['account_id']);
    $listingId = $auction['listing_id'];

    $accessToken = $this->auth();

    $client = $accessToken->getHttpClient($this->getConfig());
    $client->setUri('https://api.' . $this->_host . '.co.nz/v1/Selling/Withdraw.xml');
    $client->setMethod(Zend_Http_Client::POST);

    $xml = '<WithdrawRequest xmlns="http://api.trademe.co.nz/v1">
<ListingId>' . $listingId . '</ListingId>
<Type>ListingWasNotSold</Type>
<Reason>Withdraw</Reason>
</WithdrawRequest>';

    $client->setRawData($xml, 'application/xml');
    $response = $client->request();

    if (($status = $response->getStatus()) != 200)
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_STATUS,
        $status,
        200
      ));

    $body = $response->getBody();

    if ($body === '')
      throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_EMPTY);

    $xml = simplexml_load_string($body);

    if ($xml === false)
      throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_DECODING);

    MVentory_TradeMe_Model_Log::debug(array('response' => $xml));

    if ((string) $xml->Success != 'true')
      throw new MVentory_TradeMe_ApiException((string) $xml->Description);

    return true;
  }

  /**
   * NOTE: requires $this->_website to be set
   *
   * @param MVentory_TradeMe_Model_Auction $auction Auction
   * @return null|int Status of auction
   */
  public function check ($auction) {
    MVentory_TradeMe_Model_Log::debug();

    $this->setAccountId($auction['account_id']);
    $listingId = $auction['listing_id'];

    $item = $this->_loadListingDetailsAuth($listingId);

    //Check if item on sold
    if ($item['AsAt'] < $item['EndDate'])
      return 3;

    //Check if item was sold
    if ($item['BidCount'] > 0)
      return 2;

    //Item wasn't sold or was withdrawn
    return 1;
  }

  public function update ($product,
                          $auction,
                          $parameters = null,
                          $_formData = null)
  {
    MVentory_TradeMe_Model_Log::debug();

    $helper = Mage::helper('trademe/auction');

    $this->getWebsiteId($product);

    if ($_formData && isset($_formData['account_id']))
      unset($_formData['account_id']);

    $this->setAccountId($auction['account_id']);

    $store = $this
      ->_getWebsite()
      ->getDefaultStore();

    $account = $helper->prepareAccount($this->_accountData, $product, $store);

    if (!$account)
      throw new MVentory_TradeMe_AccountException(
        $account,
        self::__E_ACCOUNT_SHIPPING
      );

    $listingId = $auction['listing_id'];

    $accessToken = $this->auth();

    $client = $accessToken->getHttpClient($this->getConfig());

    $client->setUri('https://api.' . $this->_host . '.co.nz/v1/Selling/Edit.json');
    $client->setMethod(Zend_Http_Client::POST);

    $item = $this->_loadListingDetailsAuth($listingId);
    $item = $this->_listingDetailsToEditingRequest($item);

    $formData = $_formData;

    if ($formData)
      foreach ($formData as $key => $value)
        if ($value == -1 && isset($account[$key]))
          $formData[$key] = $account[$key];

    $shippingType = MVentory_TradeMe_Model_Config::SHIPPING_UNDECIDED;

    if (!isset($parameters['Category']) && isset($formData['category'])
        && $formData['category'])
      $parameters['Category'] = $formData['category'];

    if (!isset($parameters['Title'])) {
      $title = $helper->getTitle($product, $store);

      if (strlen($title) > MVentory_TradeMe_Model_Config::TITLE_MAX_LENGTH)
        //!!!TODO: use hellip instead 3 dots, see send() method
        $title = substr(
          $title,
          0,
          MVentory_TradeMe_Model_Config::TITLE_MAX_LENGTH - 3
        ) . '...';

      $parameters['Title'] = $title;
    }

    if (!isset($parameters['ShippingOptions']))
      if (isset($account['shipping_options']) && $account['shipping_options'])
        foreach ($account['shipping_options'] as $shippingOption)
          $parameters['ShippingOptions'][] = array(
            'Type' => MVentory_TradeMe_Model_Config::SHIPPING_CUSTOM,
            'Price' => $shippingOption['price'],
            'Method' => $shippingOption['method'],
          );
      else
        $parameters['ShippingOptions'][]['Type'] = $shippingType;

    //set price
    if (!isset($parameters['StartPrice']))
      $parameters['StartPrice'] = $this->_getPrice(
        $product,
        $account,
        $formData,
        array(),
        $store->getBaseCurrency()
      );

    if(!isset($parameters['ReservePrice']))
      $parameters['ReservePrice'] = $parameters['StartPrice'];
    if(!isset($parameters['BuyNowPrice']) && ((isset($formData['allow_buy_now'])
       && $formData['allow_buy_now'])) || isset($item['BuyNowPrice']))
      $parameters['BuyNowPrice'] = $parameters['StartPrice'];

    //set description
    if(!isset($parameters['Description'])) {
      $descriptionTmpl = $account['footer'];

      $description = '';

      if ($descriptionTmpl) {
        //Set current product in Magento registry, it's required by the block
        //which shows product's attributes
        Mage::register('product', $product, true);

        $_data = $product->getData();

        //if ($productShippingType == 'tab_ShipFree'
        //    || ($productShippingType == 'tab_ShipParcel'
        //        && $shippingType == MVentory_TradeMe_Model_Config::SHIPPING_FREE
        //        && isset($account['free_shipping_cost'])
        //        && $account['free_shipping_cost'] > 0))
        //  $_data['free_shipping_text'] = isset($account['free_shipping_text'])
        //                                   ? $account['free_shipping_text']
        //                                     : '';

        $description = $this->processDescription($descriptionTmpl, $_data);

        unset($_data);

        //Convert all HTML entities to chars first (to allow entities which
        //are not exists in XML) and then convert special chars to entities
        //to not break XML
        //Set encoding parameter to support PHP < 5.4
        $description = html_entity_decode($description, ENT_COMPAT, 'UTF-8');
      }
      $parameters['Description'] = array($description);
    }
    else {
      $parameters['Description'] = array($parameters['Description']);
    }

    //set Duration
    $item['Duration'] = $helper->getDuration($account);

    //Set pickup option
    if (!isset($parameters['Pickup']) && isset($formData['pickup']))
      $parameters['Pickup'] = $this->_getPickup($formData, $account);

    $item['PaymentMethods'] = $this->_getPaymentMethods($store);

    if (!isset($parameters['SKU']))
      $parameters['SKU'] = htmlspecialchars($product->getSku());

    $item = array_merge($item,$parameters);
    $client->setRawData(Zend_Json::encode($item), 'application/json');

    $response = $client->request();

    if (($status = $response->getStatus()) != 200)
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_STATUS,
        $status,
        200
      ));

    $body = $response->getBody();

    if ($body === '')
      throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_EMPTY);

    $body = json_decode($body, true);

    if ($body === null)
      throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_DECODING);

    if (!(isset($body['Success']) && $body['Success']))
      throw new MVentory_TradeMe_ApiException($body['Description']);

    if (!(isset($body['ListingId']) && $body['ListingId']))
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_INCOMPLETE,
        'ListingId'
      ));

    if ($_formData) {
      $helper->setFields($product, $_formData);

      $product->save();
    }

    return (int) $body['ListingId'];
  }

  public function massCheck ($auctions) {
    $accessToken = $this->auth();
    $client = $accessToken->getHttpClient($this->getConfig());
    $client->setUri(
      'https://api.' . $this->_host
        . '.co.nz/v1/MyTradeMe/SellingItems/All.json'
    );
    $client->setMethod(Zend_Http_Client::GET);

    //Request more rows than number of auctions to be sure that all listings
    //from account will be included in a response
    $client->setParameterGet('rows', count($auctions) * 10);

    $response = $client->request();

    if (($status = $response->getStatus()) != 200)
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_STATUS,
        $status,
        200
      ));

    $body = $response->getBody();

    if ($body === '')
      throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_EMPTY);

    $items = json_decode($body, true);

    if ($items === null)
      throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_DECODING);

    if (!isset($items['List']))
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_INCOMPLETE,
        'List'
      ));

    if (!isset($items['TotalCount']))
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_INCOMPLETE,
        'TotalCount'
      ));

    foreach ($auctions as $auction)
      foreach ($items['List'] as $item)
        if (isset($item['ListingId'])
            && $item['ListingId'] == $auction['listing_id'])
          $auction['is_selling'] = true;

    return $items['TotalCount'];
  }

  public function uploadImage ($image) {
    MVentory_TradeMe_Model_Log::debug();

    $accessToken = $this->auth();
    $client = $accessToken->getHttpClient($this->getConfig());

    $url = 'https://api.' . $this->_host . '.co.nz/v1/Photos.json';

    $info = pathinfo($image);

    $data = array(
      'PhotoData' => base64_encode(file_get_contents($image)),
      'FileName' => $info['filename'],
      'FileType' => $info['extension'],
    );

    $client->setUri($url);
    $client->setMethod(Zend_Http_Client::POST);
    $client->setRawData(Zend_Json::encode($data), 'application/json');

    $response = $client->request();

    if (($status = $response->getStatus()) != 200)
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_STATUS,
        $status,
        200
      ));

    $body = $response->getBody();

    if ($body === '')
      throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_EMPTY);

    MVentory_TradeMe_Model_Log::debug(array('response' => $body));

    $result = json_decode($body, true);

    if ($result === null)
      throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_DECODING);

    if (!(isset($result['Status']) && $result['Status'] == 1))
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_IMAGE_FAILED,
        $result['Status']
      ));

    if (!isset($result['PhotoId']))
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_INCOMPLETE,
        'PhotoId'
      ));

    return $result['PhotoId'];
  }

  private function processDescription ($template, $data) {
    $search = array();
    $replace = array();

    $search[] = '{{url}}';
    $replace[] = rtrim($this->_getConfig('web/unsecure/base_url'), '/')
                 . '/'
                 . Mage::getModel('core/url')
                     ->setRouteParams(array('sku' => $data['sku']))
                     ->getRoutePath();

    $shortDescription = isset($data['short_description'])
                          ? $this->_removeHtml(trim($data['short_description']))
                            : '';

    $search[] = '{{sd}}';
    $replace[] = strlen($shortDescription) > 5 ? $shortDescription : '';

    $fullDescription = isset($data['description'])
                         ? $this->_removeHtml(trim($data['description']))
                           : '';

    $search[] = '{{fd}}';
    $replace[] = strlen($fullDescription) > 5 ? $fullDescription : '';

    $search[] = '{{fs}}';
    $replace[] = isset($data['free_shipping_text'])
                   ? trim($data['free_shipping_text'])
                     : '';

    $_attrs = Mage::app()
      ->getLayout()
      ->createBlock('trademe/attributes')
      ->getAdditionalData(array('product_barcode_', 'mv_created_date'));

    $attrs = '';

    foreach ($_attrs as $_attr)
      if ($value = $this->_removeHtml(trim($_attr['value'])))
        $attrs .= $_attr['label'] . ': ' . $value . "\r\n";

    $search[] = '{{attrs}}';
    $replace[] = rtrim($attrs);

    /**
     * @todo temp. solution to calculate how much of data we can insert into
     *   the template. Calculated number contains length of all tags,
     *   it decreases free space for data
     */
    $limit = MVentory_TradeMe_Model_Config::DESCRIPTION_MAX_LENGTH
             - strlen($template);

    $description = str_replace(
      $search,
      $limit ? $this->_truncateTagsValues($replace, $limit) : '',
      $template
    );

    do {
      $before = strlen($description);

      $description = str_replace("\r\n\r\n\r\n", "\r\n\r\n", $description);

      $after = strlen($description);
    } while ($before != $after);

    return trim($description);
  }

  protected function _truncateTagsValues ($values, $limit) {
    $lastId = count($values) - 1;

    foreach ($values as $i => &$value) {
      $limit -= strlen($value);

      if ($limit > 0)
        continue;

      if ($limit == 0 && $i == $lastId)
        return $values;

      $value = substr($value, 0, $limit - 3) . '&hellip;';

      return array_slice($values, 0, $i + 1);
    }

    return $values;
  }

  public function _removeHtml ($text) {
    return preg_match('#</?[^<>]+>#', $text)
             ? preg_replace(
                 array_keys($this->_htmlConvert),
                 array_values($this->_htmlConvert),
                 $text
               )
               : $text;
  }

  public function _loadCategories () {
    $options = array(
      CURLOPT_URL => 'http://api.trademe.co.nz/v1/Categories.json',
      CURLOPT_RETURNTRANSFER => true,
    );

    $curl = curl_init();

    if (!curl_setopt_array($curl, $options))
      return null;

    $output = curl_exec($curl);

    curl_close($curl);

    return $output;
  }

  public function _loadCategoryAttrs ($categoryId) {
    $options = array(
      CURLOPT_URL => 'http://api.trademe.co.nz/v1/Categories/'
                     . $categoryId
                     . '/Attributes.json',
      CURLOPT_RETURNTRANSFER => true,
    );

    $curl = curl_init();

    if (!curl_setopt_array($curl, $options))
      return null;

    $output = curl_exec($curl);

    curl_close($curl);

    return $output;
  }

  public function _loadListingDetails ($listingId) {
    $options = array(
      CURLOPT_URL => 'http://api.'
                     . $this->_host
                     . '.co.nz/v1/Listings/'
                     . $listingId
                     . '.json',
      CURLOPT_RETURNTRANSFER => true,
    );

    $curl = curl_init();

    if (!curl_setopt_array($curl, $options))
      return null;

    $output = curl_exec($curl);

    curl_close($curl);

    return $output;
  }

  public function _loadListingDetailsAuth ($listingId) {
    $accessToken = $this->auth();
    $client = $accessToken->getHttpClient($this->getConfig());

    $url = 'https://api.'
           . $this->_host
           . '.co.nz/v1/Listings/'
           . $listingId
           . '.json';

    $client->setUri($url);
    $client->setMethod(Zend_Http_Client::GET);

    $response = $client->request();

    if (($status = $response->getStatus()) != 200)
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_STATUS,
        $status,
        200
      ));

    $body = $response->getBody();

    if ($body === '')
      throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_EMPTY);

    $body = json_decode($body, true);

    if ($body === null)
      throw new MVentory_TradeMe_ApiException(self::__E_RESPONSE_DECODING);

    if (isset($body['ErrorDescription']))
      throw new MVentory_TradeMe_ApiException($body['ErrorDescription']);

    return $this->_prepareListingDetails($body);
  }

  public function _parseCategories (&$list, $categories, $names = array()) {
    foreach ($categories as $category) {
      if (isset($this->_ignoreCategories[$category['Number']]))
        continue;

      $_names = array_merge($names, array($category['Name']));

      if (isset($category['Subcategories']))
        $this->_parseCategories($list, $category['Subcategories'], $_names);
      else {
        $id = explode('-', $category['Number']);
        $id = (int) $id[count($id) - 2];

        $list[$id] = array(
          'name' => $_names,
          'path' => $category['Path']
        );
      }
    }
  }

  public function _prepareListingDetails ($details) {
    if (!(isset($details['EndDate']) && $details['EndDate']))
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_INCOMPLETE,
        'EndDate'
      ));

    if (!(isset($details['AsAt']) && $details['AsAt']))
      throw new MVentory_TradeMe_ApiException(sprintf(
        self::__E_RESPONSE_INCOMPLETE,
        'AsAt'
      ));

    $details['EndDate'] = $this->_prepareTimestamp($details['EndDate']);
    $details['AsAt'] = $this->_prepareTimestamp($details['AsAt']);

    if (!isset($details['BidCount']))
      $details['BidCount'] = 0;

    return $details;
  }

  public function _listingDetailsToEditingRequest ($details) {

    //Prepare attached photos for editing request
    if (isset($details['Photos']) && $photos = $details['Photos']) {
      foreach ($photos as $photo)
        $details['PhotoIds'][] = $photo['Key'];

      unset($details['Photos']);
    }

    //Rename AllowsPickups option to Pickup
    if (isset($details['AllowsPickups'])) {
      $details['Pickup'] = $details['AllowsPickups'];

      unset($details['AllowsPickups']);
    }

    return $details;
  }

  private function _prepareTimestamp ($data) {
    return substr($data, 6, -2) / 1000;
  }

  protected function _getPickup ($data, $account) {
    if (isset($data['pickup'])) {
      $pickup = (int) $data['pickup'];

      if ($pickup == MVentory_TradeMe_Model_Config::PICKUP_ALLOW
          || $pickup == MVentory_TradeMe_Model_Config::PICKUP_DEMAND
          || $pickup == MVentory_TradeMe_Model_Config::PICKUP_FORBID)
        return $pickup;
    }

    return isset($account['allow_pickup']) && $account['allow_pickup']
             ? MVentory_TradeMe_Model_Config::PICKUP_ALLOW
               : MVentory_TradeMe_Model_Config::PICKUP_FORBID;

    if (!isset($account['allow_pickup']))
      return MVentory_TradeMe_Model_Config::PICKUP_FORBID;
  }

  protected function _getIsBrandNew ($product) {
    return ($value = $product->getData('tm_condition')) === null
           || in_array(
                $value,
                explode(
                  ',',
                  $this->_getConfig(MVentory_TradeMe_Model_Config::LIST_AS_NEW)
                )
              );
  }

  /**
   * Check if fees can be applied to the product
   *
   * @param Mage_Catalog_Model_Product $product
   * @param array $data Account data
   * @return bool
   */
  protected function _getAddFees ($product, $data) {
    if (!isset($data['add_fees']))
      return false;

    if ($data['add_fees'] == MVentory_TradeMe_Model_Config::FEES_ALWAYS)
      return true;

    if ($data['add_fees'] == MVentory_TradeMe_Model_Config::FEES_SPECIAL)
      return $product->getFinalPrice() < $product->getPrice();

    return false;
  }

  /**
   * Calculate final price for auction
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param array $account
   *   Current account
   *
   * @param array $data
   *   Additional data
   *
   * @param array $overwrite
   *   Overwrite values
   *
   * @param Mage_Directory_Model_Currency $currency
   *   Base currency of current store
   *
   * @return float
   *   Final price for TradeMe auction
   */
  protected function _getPrice ($product,
                                $account,
                                $data,
                                $overwrite,
                                $currency) {

    if (isset($overwrite['price']))
      return $overwrite['price'];

    $website = $this->_getWebsite();
    $helper = Mage::helper('trademe');

    $price = $helper->getProductPrice($product, $website);

    $price += $helper->getShippingRate(
      $product,
      $account['name'],
      $website
    );

    if ($currency->getCode != MVentory_TradeMe_Model_Config::CURRENCY)
      $price = $helper->currencyConvert(
        $price,
        $currency,
        MVentory_TradeMe_Model_Config::CURRENCY,
        $website->getDefaultStore()
      );

    return $this->_getAddFees($product, $data)
               ? $helper->addFees($price)
                 : $price;
  }

  /**
   * Check if Allow but now is allowed
   *
   * @param $data Data
   * @param array $overwrite Overwrite values
   * @return bool
   */
  protected function _getAllowBuyNow ($data, $overwrite) {
    return isset($overwrite['allow_buy_now'])
             ? $overwrite['allow_buy_now']
             : (isset($data['allow_buy_now']) && $data['allow_buy_now']);
  }

  /**
   * Get auction duration
   *
   * @param array $account Current account
   * @param array $overwrite Overwrite values
   * @return int Duration
   */
  protected function _getDuration ($account, $overwrite) {
    return isset($overwrite['duration'])
             ? $overwrite['duration']
             : Mage::helper('trademe')->getDuration($account);
  }

  /**
   * Return allowed TradeMe payment methods
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   List of IDs of allowed payment methods
   */
  protected function _getPaymentMethods ($store) {
    return explode(
      ',',
      $store->getConfig(MVentory_TradeMe_Model_Config::_PAYMENT_METHODS)
    );
  }

  /**
   * Get auction title
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @param array $overwrite
   *   Values to overwrite
   *
   * @return string
   *   Auction title
   */
  protected function _getTitle ($product, $store, $overwrite) {
    return isset($overwrite['title'])
             ? $overwrite['title']
             : Mage::helper('trademe/auction')->getTitle ($product, $store);
  }

  public function getCategories () {
    $app = Mage::app();

    if ($list = $app->loadCache(self::CACHE_CATEGORIES))
      return unserialize($list);

    $json = $this->_loadCategories();

    if (!$json)
      return null;

    $categories = json_decode($json, true);

    unset($json);

    $list = array();

    $this->_parseCategories($list, $categories['Subcategories']);

    unset($categories);

    if ($app->useCache(MVentory_TradeMe_Model_Config::CACHE_TYPE))
      $app->saveCache(
        serialize($list),
        self::CACHE_CATEGORIES,
        array(MVentory_TradeMe_Model_Config::CACHE_TAG)
      );

    return $list;
  }

  public function getCategoryAttrs ($categoryId) {
    if (!$categoryId)
      return null;

    $app = Mage::app();

    if ($attrs = $app->loadCache(self::CACHE_CATEGORY_ATTRS . $categoryId))
      return unserialize($attrs);

    $json = $this->_loadCategoryAttrs($categoryId);

    if (!$json)
      return null;

    $attrs = json_decode($json, true);

    if ($app->useCache(MVentory_TradeMe_Model_Config::CACHE_TYPE))
      $app->saveCache(
        serialize($attrs),
        self::CACHE_CATEGORY_ATTRS . $categoryId,
        array(MVentory_TradeMe_Model_Config::CACHE_TAG)
      );

    return $attrs;
  }

  public function getAttrTypeName ($id) {
    if (isset($this->_attrTypes[$id]))
      return $this->_attrTypes[$id];

    return 'Unknown';
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
}
