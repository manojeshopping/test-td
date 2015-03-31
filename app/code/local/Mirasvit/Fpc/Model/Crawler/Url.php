<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Full Page Cache
 * @version   1.0.1
 * @build     268
 * @copyright Copyright (C) 2014 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_Fpc_Model_Crawler_Url extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('fpc/crawler_url');
    }

    public function saveUrl($line)
    {
        if (count($line) != 4) {
            return $this;
        }

        $url     = $line[2];
        $cacheId = preg_replace('/\s+/', ' ', trim($line[3]));

        $collection = $this->getCollection();
        $collection->getSelect()->where('url = ?', $url);
        $model = $collection->getFirstItem();

        try {
            if (trim($cacheId) != '') {
                $model->setCacheId($cacheId)
                    ->setUrl($url)
                    ->setRate(intval($model->getRate()) + 1)
                    ->save();
            } elseif ($model->getId()) {
                $model->setRate(intval($model->getRate()) + 1)
                    ->save();
            }
        } catch (Exception $e) {}

        return $this;
    }

    public function isCacheExist()
    {
        $cache = Mirasvit_Fpc_Model_Cache::getCacheInstance();

        if ($cache->load($this->getCacheId())) {
            return true;
        }

        return false;
    }

    public function clearCache()
    {
        $cache = Mirasvit_Fpc_Model_Cache::getCacheInstance();
        $cache->remove($this->getCacheId());

        return $this;
    }

    public function warmCache()
    {
        $url = $this->getUrl();
        $content = '';
        if (function_exists('curl_multi_init')) {
            $adapter    = new Varien_Http_Adapter_Curl();
            $options    = array(
                CURLOPT_USERAGENT => Mirasvit_Fpc_Model_Crawler_Crawl::USER_AGENT,
                CURLOPT_HEADER    => true
            );

            $content = $adapter->multiRequest(array($url), $options);
            $content = $content[0];
        } else {
            $content = implode(PHP_EOL, get_headers($url));
        }

        preg_match('/Fpc-Cache-Id: ('.Mirasvit_Fpc_Model_Processor::REQUEST_ID_PREFIX.'[a-z0-9]{32})/', $content, $matches);
        if (count($matches) == 2) {
            $cacheId = $matches[1];
            if ($this->getCacheId() != $cacheId) {
                $this->setCacheId($cacheId)
                    ->save();
            }
        } else {
            $this->delete();
        }

        return $this;
    }
}