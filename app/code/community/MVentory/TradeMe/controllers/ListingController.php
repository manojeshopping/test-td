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
 * TradeMe listing controller
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_ListingController
  extends Mage_Adminhtml_Controller_Action
{
  /**
   * Temporarily allow access for all users
   */
  protected function _isAllowed() {
    return true;
  }

  public function submitAction () {
    $helper = Mage::helper('mventory/product');
    $request = $this->getRequest();

    $params = $request->getParams();

    $productId = isset($params['id']) ? $params['id'] : null;

    $data = isset($params['product']) && is_array($params['product'])
              ? Mage::helper('trademe')->getFormFields($params['product'])
                : array();

    $data['category'] = isset($params['trademe_category'])
                          ? $params['trademe_category']
                            : null;

    $session = Mage::getSingleton('adminhtml/session');

    //Store TradeMe data in session to use it on TradeMe tab
    //when submit fails
    $session->setData('trademe_data', $data);

    if (!(isset($data['account_id']) && $data['account_id'])) {
      $session->addError($helper->__('Please, select account'));

      $this->_redirect('adminhtml/catalog_product/edit/id/' . $productId);

      return;
    }

    $product = Mage::getModel('catalog/product')->load($productId);

    if (!$product->getId()) {
      $session->addError($helper->__('Can\'t load product'));

      $this->_redirect('adminhtml/catalog_product/edit/id/' . $productId);

      return;
    }

    $auction = Mage::getModel('trademe/auction')->loadByProduct($product);

    $stock = Mage::getModel('cataloginventory/stock_item')
               ->loadByProduct($product);

    if ($stock->getManageStock() && $stock->getQty() == 0
        && !$auction['listing_id']) {
      $session->addError($helper->__('Item is not available in inventory'));

      $this->_redirect('adminhtml/catalog_product/edit/id/' . $productId);

      return;
    }

    try {
      $connector = new MVentory_TradeMe_Model_Api();
      $listingId = $connector->send($product, $data['category'], $data);

      $auction
        ->setData(array(
          'product_id' => $product->getId(),
          'listing_id' => $listingId,
          'account_id' => $data['account_id']
        ))
        ->save();
    }
    catch (Exception $e) {
      Mage::logException($e);

      foreach ($this->_parseErrors($e->getMessage()) as $error)
        $session->addError($helper->__($error));

      return $this->_redirect(
        'adminhtml/catalog_product/edit/id/' . $productId
      );
    }

    $path = MVentory_TradeMe_Model_Config::SANDBOX;
    $website = $helper->getWebsite($product);

    $host = $helper->getConfig($path, $website) ? 'tmsandbox' : 'trademe';
    $url = 'http://www.'
           . $host
           . '.co.nz/Browse/Listing.aspx?id='
           . $listingId;

    $link = '<a href="' . $url . '">' . $url . '</a>';

    $session->addSuccess($helper->__('Listing URL') . ': ' . $link);

    //Remove TradeMe data from the session on successful submit
    $session->unsetData('trademe_data');

    $this->_redirect('adminhtml/catalog_product/edit/id/' . $productId);
  }

  public function removeAction () {
    $params = $this
      ->getRequest()
      ->getParams();

    $auction = null;
    $helper = Mage::helper('trademe');

    if (isset($params['id']) && $params['id']) {
      $auction = Mage::getModel('trademe/auction')->load(
        $params['id'],
        'listing_id'
      );

      if (!$auction->getId())
        $auction = null;
    }

    if (!$auction && isset($params['product_id']) && $params['product_id']) {
      $auction = Mage::getModel('trademe/auction')->loadByProduct(
        $params['product_id']
      );

      if (!$auction->getId())
        $auction = null;
    }

    if (!$auction) {
      Mage::getSingleton('adminhtml/session')
        ->addError($helper->__('Can\'t load auction'));

      return $this->_redirect(
        'adminhtml/catalog_product/edit/id/' . $params['product_id']
      );
    }

    $session = Mage::getSingleton('adminhtml/session');

    try {
      $auction
        ->withdraw()
        ->delete();

      $session->addSuccess($helper->__('Removed from: %s', $auction->getUrl()));
    } catch (Exception $e) {
      Mage::logException($e);
      $session->addError($helper->__($e->getMessage()));
    }

    return $this->_redirect(
      'adminhtml/catalog_product/edit/id/' . $params['product_id']
    );
  }

  public function checkAction () {
    $id = $this->_request->getParam('id');
    $product = Mage::getModel('catalog/product')->load($id);

    $helper = Mage::helper('mventory/product');

    if ($product->getId()) {
      $auction = Mage::getModel('trademe/auction')->loadByProduct($product);

      if (!$auction->getId()) {
        Mage::getSingleton('adminhtml/session')
          ->addError($helper->__('Can\'t load auction'));

        $this->_redirect('adminhtml/catalog_product/edit/id/' . $id);

        return;
      }

      $website = $helper->getWebsite($product);

      try {
        $connector = new MVentory_TradeMe_Model_Api();
        $result = $connector
          ->setWebsiteId($website)
          ->check($auction);
      }
      catch (Exception $e) {
        Mage::logException($e);
        Mage::getSingleton('adminhtml/session')->addError(
          $helper->__($e->getMessage())
        );

        return $this->_redirect('adminhtml/catalog_product/edit/id/' . $id);
      }

      $path = MVentory_TradeMe_Model_Config::SANDBOX;

      $host = $helper->getConfig($path, $website) ? 'tmsandbox' : 'trademe';
      $url = 'http://www.'
             . $host
             . '.co.nz/Browse/Listing.aspx?id='
             . $auction['listing_id'];

      $link = '<a href="' . $url . '">' . $url . '</a>';

      switch ($result) {
        case 1:
          Mage::getSingleton('adminhtml/session')
            ->addSuccess($helper->__('Wasn\'t sold') . ': ' . $link);

          $auction->delete();

          break;
        case 2:
          Mage::getSingleton('adminhtml/session')
            ->addSuccess($helper->__('Successfully sold ') . ': ' . $link);

          $stock = Mage::getModel('cataloginventory/stock_item')
                     ->loadByProduct($product);

          if ($stock->getManageStock() && $stock->getQty()) {
            $stockData = $stock->getData();
            $stockData['qty'] -= 1;
            $product
              ->setStockData($stockData)
              ->save();
          }

          $auction->delete();

          break;
        case 3:
          Mage::getSingleton('adminhtml/session')
            ->addSuccess($helper->__('Listing is active ') . ': ' . $link);

          break;
        default:
          throw new LogicException('Unexpected result value');
      }
    } else
      Mage::getSingleton('adminhtml/session')->addError($helper->__('Error'));

    $this->_redirect('adminhtml/catalog_product/edit/id/' . $id);
  }

  public function updateAction () {
    $request = $this->getRequest();
    $helper = Mage::helper('mventory/product');

    $params = $request->getParams();

    if (!isset($params['id'])) {
      Mage::getSingleton('adminhtml/session')
        ->addError($helper->__('No product ID parameter'));

      $this->_redirect('adminhtml/catalog_product/index');

      return;
    }

    $data = isset($params['product']) && is_array($params['product'])
              ? Mage::helper('trademe')->getFormFields($params['product'])
                : array();

    $data['category'] = isset($params['trademe_category'])
                          ? $params['trademe_category']
                            : null;

    $product = Mage::getModel('catalog/product')->load($params['id']);

    if (!$product->getId()) {
      Mage::getSingleton('adminhtml/session')
        ->addError($helper->__('Can\'t load product'));

      $this->_redirect('adminhtml/catalog_product/edit/id/' . $params['id']);

      return;
    }

    $auction = Mage::getModel('trademe/auction')->loadByProduct($product);

    if (!$auction->getId()) {
      Mage::getSingleton('adminhtml/session')
        ->addError($helper->__('Can\'t load auction'));

      $this->_redirect('adminhtml/catalog_product/edit/id/' . $params['id']);

      return;
    }

    try {
      $api = new MVentory_TradeMe_Model_Api();
      $result = $api->update($product, $auction, null, $data);

      Mage::getSingleton('adminhtml/session')->addSuccess(
        $helper->__('Listing has been updated')
      );
    }
    catch (Exception $e) {
      Mage::logException($e);
      Mage::getSingleton('adminhtml/session')->addError(
        $helper->__($e->getMessage())
      );
    }

    $this->_redirect('adminhtml/catalog_product/edit/id/' . $params['id']);
  }

  /**
   * Unlink all sandbox auctions for specified accounts
   *
   * @return MVentory_TradeMe_ListingController
   *   Instance of this class
   */
  public function unlinkAction () {
    $helper = Mage::helper('trademe/auction');
    $session = Mage::getSingleton('adminhtml/session');

    $request = $this->getRequest();
    $params = $request->getParams();

    if (!($lastUrl = $session->getData('last_url', true)))
      $lastUrl = $this->getUrl('adminhtml');

    if (!isset($params['accounts'])) {
      $session->addError($helper->__('No accounts parameter'));
      return $this->_redirectUrl($lastUrl);
    }

    $accounts = $params['accounts'];
    $accounts = ($accounts && $accounts != 'all')
                  ? explode(',', $accounts)
                  : array();

    foreach ($accounts as &$account)
      $account = lowercase(trim($account));

    try {
      $helper->unlinkAll($accounts);

      $session->addSuccess($helper->__(
        'All sandbox auctions were successfully unlinked'
      ));
    } catch (Exception $e) {
      $session->addError($helper->__(
        'Error happened while unlinking all sandbox auctions: %s',
        $e->getMessage()
      ));
    }

    return $this->_redirectUrl($lastUrl);
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
