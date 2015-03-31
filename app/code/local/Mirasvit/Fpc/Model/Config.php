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


class Mirasvit_Fpc_Model_Config extends Varien_Simplexml_Config
{
    protected $_containers = null;

    public function __construct($data = null)
    {
        parent::__construct($data);

        $cacheConfig  = Mage::getConfig()->loadModulesConfiguration('cache.xml');
        $customConfig = Mage::getConfig()->loadModulesConfiguration('custom.xml');
        $cacheConfig->extend($customConfig);

        $this->setXml($cacheConfig->getNode());

        return $this;
    }

    public function getLifetime()
    {
        $lifetime = intval(Mage::getStoreConfig('fpc/general/lifetime'));
        if (!$lifetime) {
            $lifetime = 3600;
        }
        return $lifetime;
    }

    public static function isDebug()
    {
        $options = Mage::app()->getConfig()->getNode('global/cache')->asCanonicalArray();
        if (isset($options['debug']) && $options['debug'] == 1) {
            if (isset($options['ip'])
                && $options['ip'] != '*'
                && !in_array($_SERVER['REMOTE_ADDR'], explode(',', $options['ip']))) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function getMaxCacheSize()
    {
        $size = intval(Mage::getStoreConfig('fpc/general/max_cache_size'));
        if (!$size) {
            $size = 2048;
        }

        $size *= 1024 * 1024;

        return $size;
    }

    public function getMaxCacheNumber()
    {
        $number = intval(Mage::getStoreConfig('fpc/general/max_cache_number'));
        if (!$number) {
            $number = 100000;
        }

        return $number;
    }

    public function getCacheEnabled($storeId = null)
    {
        return Mage::getStoreConfig('fpc/general/enabled', $storeId);
    }

    public function getCrawlerEnabled($storeId = null)
    {
        return Mage::getStoreConfig('fpc/crawler/enabled', $storeId);
    }

    public function getCrawlerMaxThreads($storeId = null)
    {
        $threads = Mage::getStoreConfig('fpc/crawler/max_threads', $storeId);

        if (!$threads) {
            $threads = 1;
        }

        return $threads;
    }

    public function getCrawlerThreadDelay($storeId = null)
    {
        $delay = Mage::getStoreConfig('fpc/crawler/thread_delay', $storeId);

        if (!$delay) {
            $delay = 0;
        }

        return $delay * 1000;
    }

    public function getCrawlerMaxUrlsPerRun($storeId = null)
    {
        $urls = Mage::getStoreConfig('fpc/crawler/max_urls_per_run', $storeId);

        if (!$urls) {
            $urls = 1000000;
        }

        return $urls;
    }

    public function getCrawlerSchedule($storeId = null)
    {
        return Mage::getStoreConfig('fpc/crawler/schedule', $storeId);
    }

    public function getStatus()
    {
        return Mage::getStoreConfig('fpc/crawler/status');
    }

    public function getMaxDepth()
    {
        return Mage::getStoreConfig('fpc/cache_rules/max_depth');
    }

    public function getAllowedPages()
    {
        $result = array();
        $key    = 'fpc/cache_rules/allowed_pages';

        if ($values = Mage::getStoreConfig($key)) {
            $values = unserialize($values);
            foreach ($values as $value) {
                if (count($value) == 1) {
                    $result[] = array_pop($value);
                } else {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }

    public function getCacheableActions()
    {
        $result = array();
        $key    = 'fpc/cache_rules/cacheable_actions';

        if ($values = Mage::getStoreConfig($key)) {
            $values = unserialize($values);
            foreach ($values as $value) {
                if (count($value) == 1) {
                    $result[] = array_pop($value);
                } else {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }

    public function getIgnoredPages()
    {
        $result = array();
        $key    = 'fpc/cache_rules/ignored_pages';

        if ($values = Mage::getStoreConfig($key)) {
            $values = unserialize($values);
            foreach ($values as $value) {
                if (count($value) == 1) {
                    $result[] = array_pop($value);
                } else {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }

    public function getUserAgentSegmentation()
    {
        $result = array();

        $key = 'fpc/cache_rules/user_agent_segmentation';

        if ($values = Mage::getStoreConfig($key)) {
            $values = unserialize($values);
            foreach ($values as $value) {
                if (count($value) == 1) {
                    $result = array_pop($value);
                } else {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    public function getContainers()
    {
        if ($this->_containers === null) {
            $this->_containers = array();
            foreach ($this->getNode('containers')->children() as $container) {
                $this->_containers[(string)$container->block] = array(
                    'container'      => (string) $container->container,
                    'block'          => (string) $container->block,
                    'cache_lifetime' => (int) $container->cache_lifetime,
                    'name'           => (string) $container->name,
                    'depends'        => (string) $container->depends,
                    'in_app'         => empty($container->in_app)?intval($container->in_app)+1:intval($container->in_app),
                );
            }
        }

        return $this->_containers;
    }

    public function isDebugHintsEnabled($storeId = null)
    {
        if (!self::isDebugAllowed()) {
            return false;
        }

        return Mage::getStoreConfig('fpc/debug/hints', $storeId);
    }

    public function isDebugInfoEnabled($storeId = null)
    {
        if (!self::isDebugAllowed()) {
            return false;
        }

        return Mage::getStoreConfig('fpc/debug/info', $storeId);
    }

    public function isDebugLogEnabled($storeId = null)
    {
        if (!self::isDebugAllowed()) {
            return false;
        }

        return Mage::getStoreConfig('fpc/debug/log', $storeId);
    }

    public function isDebugAllowed($storeId = null)
    {
        $ips = Mage::getStoreConfig('fpc/debug/allowed_ip', $storeId);
        if ($ips == '') {
            return true;
        }
        $ips = explode(',', $ips);

        return in_array($_SERVER['REMOTE_ADDR'], $ips);
    }
}
