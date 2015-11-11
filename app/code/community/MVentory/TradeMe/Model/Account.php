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
 */

/**
 * TradeMe account model
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Model_Account
{
  protected $_accountId = null;
  protected $_website = null;

  protected $_configuration = null;

  function __construct ($accountId, $website) {
    $this->_accountId = $accountId;
    $this->_website = $website;

    $helper = Mage::helper('trademe');

    $siteUrl = 'https://secure.'
               . ($helper->isSandboxMode($website) ? 'tmsandbox' : 'trademe')
               . '.co.nz/Oauth/';

    $accounts = $helper->getAccounts($website);
    $account = $accounts[$accountId];

    $route = 'adminhtml/trademe_account/authorise';
    $params = array(
      'account_id' => $accountId,
      'website' => $website);

    $this->_configuration = array(
      'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER,
      'version' => '1.0',
      'signatureMethod' => 'HMAC-SHA1',
      'siteUrl' => $siteUrl,
      'requestTokenUrl' => $siteUrl . 'RequestToken',
      'authorizeUrl' => $siteUrl . 'Authorize',
      'accessTokenUrl' => $siteUrl . 'AccessToken',
      'consumerKey' => $account['key'],
      'consumerSecret' => $account['secret'],
      'callbackUrl' => Mage::helper('adminhtml')->getUrl($route, $params)
    );
  }

  public function authenticate () {
    $consumer = new Zend_Oauth_Consumer($this->_configuration);

    $requestToken = $consumer->getRequestToken();

    if (!$requestToken->isValid())
      return null;

    Mage::getSingleton('core/session')
      ->setTradeMeRequestToken(serialize($requestToken));

    $params = array('scope' => 'MyTradeMeRead,MyTradeMeWrite');

    return $consumer->getRedirectUrl($params);
  }

  public function authorise ($data) {
    $session = Mage::getSingleton('core/session');

    $requestToken = $session->getTradeMeRequestToken();

    if (!$requestToken)
      return;

    $consumer = new Zend_Oauth_Consumer($this->_configuration);

    $token = $consumer->getAccessToken($data, unserialize($requestToken));

    $data = array(
      Zend_Oauth_Token::TOKEN_PARAM_KEY => $token->getToken(),
      Zend_Oauth_Token::TOKEN_SECRET_PARAM_KEY => $token->getTokenSecret()
    );

    $path = 'trademe/' . $this->_accountId . '/access_token';
    $websiteId = Mage::app()
                   ->getWebsite($this->_website)
                   ->getId();

    Mage::getConfig()
      ->saveConfig($path, serialize($data), 'websites', $websiteId)
      ->reinit();

    Mage::app()->reinitStores();

    $session->setTradeMeRequestToken(null);

    return true;
  }
}
