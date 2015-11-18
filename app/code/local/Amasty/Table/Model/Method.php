<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Table
 */ 
class Amasty_Table_Model_Method extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('amtable/method');
    }
    
    public function massChangeStatus ($ids, $status) {
        foreach ($ids as $id) {
                $model = Mage::getModel('amtable/method')->load($id);
                $model->setIsActive($status);
                $model->save();
            }
        return $this;
    }

    public function addComment($html)
    {
        preg_match_all('@<label for="s_method_amtable_amtable(.+?)">.+?label>@si', $html, $matches);
        if (!empty($matches[0])) {
            $hashMethods = Mage::getModel('amtable/method')->getCollection()->toOptionHash();
            foreach ($matches[0] as $key => $value) {
                $methodId = $matches[1][$key];
                $to[] = $matches[0][$key] . '<div>' . $hashMethods[$methodId] . '</div>';
            }

            $newHtml = str_replace($matches[0], $to, $html);
            return $newHtml;
        }

        return $html;
    }
}