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
 * ÐÐ¾Ð´ÐµÐ»Ñ Ð´Ð»Ñ ÑÐ°Ð±Ð¾ÑÑ Ñ ÑÐ¸Ð½Ð¾Ð½Ð¸Ð¼Ð°Ð¼Ð¸
 *
 * @category Mirasvit
 * @package  Mirasvit_SearchSphinx
 */
class Mirasvit_SearchSphinx_Model_Synonym extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('searchsphinx/synonym');
    }

    /**
     * ÐÐ¼Ð¿Ð¾ÑÑ ÑÐ¸Ð½Ð¾Ð½Ð¸Ð¼Ð¾Ð²
     *
     * @param  string $filePath Ð¿Ð¾Ð»Ð½ÑÐ¹ Ð¿ÑÑÑ Ðº ÑÐ°Ð¹Ð»Ñ (csv)
     * @param  array  $stores
     *
     * @return integer ÐºÐ¾Ð»-Ð²Ð¾ Ð¸Ð¼Ð¿Ð¾ÑÑÐ¸ÑÐ¾Ð²Ð°Ð½ÑÑ ÑÐ¸Ð½Ð¾Ð½Ð¸Ð¼Ð¾Ð²
     */
    public function import($filePath, $stores)
    {
        if (!is_array($stores)) {
            $stores = array($stores);
        }

        $resource   = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $tableName  = Mage::getSingleton('core/resource')->getTableName('searchsphinx/synonym');

        $content = file_get_contents($filePath);
        $lines   = explode("\n", $content);

        foreach ($stores as $store) {
            $rows    = array();
            $errors  = array();

            foreach ($lines as $num => $value) {
                $value    = strtolower($value);
                $value    = explode(',', $value);
                $synonyms = array();

                foreach ($value as $ind => $val) {
                    try {
                        $val = $this->prepareWord($val);
                    } catch (Exception $e) {
                        $errors[$store.$num.$ind] = 'Warning on line #' . ($num + 1) . ': ' . $e->getMessage();
                        continue;
                    }
                    if (count(Mage::helper('core/string')->splitWords($val, true)) == 1 && $val) {
                        $synonyms[$val] = $val;
                    }
                }

                if (count($synonyms) > 1) {
                    $synonyms = implode(',', $synonyms);
                    $rows[] = array(
                        'synonyms' => $synonyms,
                        'store' => $store,
                    );
                }

                if (count($rows) > 1000) {
                    $connection->insertArray($tableName, array('synonyms', 'store'), $rows);
                    $rows = array();
                }
            }

            if (count($rows) > 0) {
                $connection->insertArray($tableName, array('synonyms', 'store'), $rows);
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

    public function getSynonymsByWord($arWord, $storeId)
    {
        $result = array();

        if (!is_array($arWord)) {
            $arWord = array($arWord);
        }

        $collection = $this->getCollection();

        foreach ($arWord as $word) {
            $collection->getSelect()->orWhere(new Zend_Db_Expr("FIND_IN_SET('".addslashes($word)."', synonyms)"));
        }

        foreach ($collection as $synonym) {
            $synonyms = explode(',', $synonym->getSynonyms());

            foreach ($arWord as $word) {
                if (in_array($word, $synonyms)) {
                    foreach ($synonyms as $synonym) {
                        $result[$word][$synonym] = $synonym;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param $word
     * @return string $word
     * @throws Mage_Core_Exception
     */
    public function prepareWord($word)
    {
        $word = trim($word);
        if (strlen($word) <= 1) {
            Mage::throwException(sprintf(__('The lenght of synonym "%s" must be greater than 1'), $word));
        }

        if (count(explode(' ', $word)) != 1) {
            Mage::throwException(sprintf(__('Synonym "%s" can contain only one word'), $word));
        }

        return $word;
    }
}