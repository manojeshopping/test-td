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
 * @package   Sphinx Search Ultimate
 * @version   2.3.2
 * @build     962
 * @copyright Copyright (C) 2014 Mirasvit (http://mirasvit.com/)
 */


/**
 * ÐÐ¾Ð´ÐµÐ»Ñ Ð´Ð»Ñ ÑÐ°Ð±Ð¾ÑÑ ÑÐ¾ ÑÑÐ¾Ð¿-ÑÐ»Ð¾Ð²Ð°Ð¼Ð¸
 *
 * @category Mirasvit
 * @package  Mirasvit_SearchSphinx
 */
class Mirasvit_SearchSphinx_Model_Stopword extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('searchsphinx/stopword');
    }

    /**
     * ÐÐ¼Ð¿Ð¾ÑÑ ÑÑÐ¾Ð¿-ÑÐ»Ð¾Ð²
     *
     * @param  string $filePath Ð¿Ð¾Ð»Ð½ÑÐ¹ Ð¿ÑÑÑ Ðº ÑÐ°Ð¹Ð»Ñ (csv)
     * @param  array  $stores
     *
     * @return integer ÐºÐ¾Ð»-Ð²Ð¾ Ð¸Ð¼Ð¿Ð¾ÑÑÐ¸ÑÐ¾Ð²Ð°Ð½ÑÑ ÑÑÐ¾Ð¿-ÑÐ»Ð¾Ð²
     */
    public function import($filePath, $stores)
    {
        if (!is_array($stores)) {
            $stores = array($stores);
        }

        $resource   = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $tableName  = Mage::getSingleton('core/resource')->getTableName('searchsphinx/stopword');

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        foreach ($stores as $store) {
            $rows   = array();
            $errors = array();

            foreach ($lines as $num => $value) {
                try {
                    $value = $this->prepareWord($value);
                } catch (Exception $e) {
                    $errors[$store.$num] = 'Warning on line #' . ($num + 1) . ': ' . $e->getMessage();
                    continue;
                }
                $rows[] = array(
                    'word' => $value,
                    'store' => $store,
                );

                if (count($rows) > 1000) {
                    $connection->insertArray($tableName, array('word', 'store'), $rows);
                    $rows = array();
                }
            }

            if (count($rows) > 0) {
                $connection->insertArray($tableName, array('word', 'store'), $rows);
            }
        }
        if (count($errors) > 0) {
            foreach ($errors as $e) {
                Mage::getSingleton('adminhtml/session')->addError($e);
            }
            Mage::app()->getResponse()->setRedirect('*/*/')->sendResponse();
        }

        return count($rows);
    }

    public function isStopWord($word, $store)
    {
        $uid = Mage::helper('mstcore/debug')->start();

        $collection = $this->getCollection()->addFieldToFilter('word', $word)
            ->addFieldToFilter('store', $store);

        $cnt = $collection->count();

        return $cnt;
    }

    /**
     * @param  string $word
     * @return string $word
     * @throws Mage_Core_Exception
     */
    public function prepareWord($word)
    {
        $word = trim(strtolower($word));
        if (count(explode(' ', $word)) > 1) {;
            Mage::throwException('Stopword "'.$word.'" can contain only one word.');
        }
        if ($word === '?') {
            Mage::throwException('Stopword contains an invalid character: "'.$word.'".');
        }

        return $word;
    }
}