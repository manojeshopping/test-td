<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Label
 */
class Amasty_Label_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_labels = null;
    protected $_sizes  = array();

    public function getLabels($product, $mode = 'category', $useJs = false)
    {
        $html = '';

        $applied = false;
        foreach ($this->_getCollection() as $label) {
            if ($label->getIsSingle() && $applied) {
                continue;
            }
            $label->init($product, $mode);
            if ($label->isApplicable()) {
                $applied = true;
                $html .= $this->_generateHtml($label, $useJs);
            } elseif ($label->getUseForParent() && ($product->isConfigurable() || $product->isGrouped())) {
                $usedProds = $this->getUsedProducts($product);
                foreach ($usedProds as $child) {
                    $label->init($child, $mode, $product);
                    if ($label->isApplicable()) {
                        $applied = true;
                        $html .= $this->_generateHtml($label, $useJs);
                    }
                }
            }
        }

        return $html;
    }

    protected function _getCollection()
    {
        if (is_null($this->_labels)) {
            $id            = Mage::app()->getStore()->getId();
            $this->_labels = Mage::getModel('amlabel/label')->getCollection()
                                 ->addFieldToFilter('stores', array('like' => "%,$id,%"))
                                 ->setOrder('pos', 'asc')
                                 ->load();
        }

        return $this->_labels;
    }

    protected function _generateHtml($label, $useJs = false)
    {
        $html = '';
        $imgUrl = $label->getImageUrl();

        if (empty($this->_sizes[$imgUrl])) {
            $this->_sizes[$imgUrl] = $label->getImageInfo();
        }

        $tableClass = $label->getCssClass();

        $tableStyle = '';
        $tableStyle .= 'height:' . $this->_sizes[$imgUrl]['h'] . 'px; ';
        $tableStyle .= 'width:' . $this->_sizes[$imgUrl]['w'] . 'px; ';

        $customStyle = $label->getStyle();
        if ($customStyle) {
            $tableStyle .= $customStyle;
        } else { //adjust image position for middle cases
            $tableStyle .= $this->_getPositionAdjustment($tableClass, $this->_sizes[$imgUrl]);
        }

        if ($label->getMode() == 'cat') {
            $textStyle = $label->getCatTextStyle();
            $imgWidth  = $label->getCatImageWidth();
            $imgHeight = $label->getCatImageHeight();
        } else {
            $textStyle = $label->getProdTextStyle();
            $imgWidth  = $label->getProdImageWidth();
            $imgHeight = $label->getProdImageHeight();
        }

        if ($textStyle) {
            $textStyle = 'style="' . $textStyle . '"';
        }

        // need both w and h for correct script logic and enabled admin setting
        if ($useJs && !($imgWidth && $imgHeight)) {
            Mage::log('UseJS enabled, but W&H do not filled.', null, 'Amasty_Label.log', true);
            return '';
        } else if ($useJs && ($imgWidth && $imgHeight)) {
            $textBlockStyle = 'style="width:' . $imgWidth . '%;height:' . $imgHeight . '%; background: url(' . $imgUrl . ') no-repeat 0 0; ' . $customStyle . '"';
            $html  = '<div class="amlabel-table2 top-left" ' . $label->getJs() . ' >';
            $html .= '  <div class="amlabel-txt2 ' . $tableClass . '" ' . $textBlockStyle . ' ><div class="amlabel-txt" ' . $textStyle . '>' . $label->getText() . '</div></div>';
            $html .= '</div>';
        } else {
            $html = '<table ' . $label->getJs() . ' class="amlabel-table ' . $tableClass . '" style ="' . $tableStyle . '">';
            $html .= '<tr>';
            $html .= '<td style="background: url(' . $imgUrl . ') no-repeat 0 0">';
            $html .= '<span class="amlabel-txt" ' . $textStyle . '>' . $label->getText() . '</span>';
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</table>';
        }

        return $html;
    }

    protected function _getPositionAdjustment($tableClass, $sizes)
    {
        $style = '';

        if ('top-center' == $tableClass) {
            $style .= 'margin-left:' . (-$sizes['w'] / 2) . 'px;';
        } elseif (false !== strpos($tableClass, 'center')) {
            $style .= 'margin-right:' . (-$sizes['w'] / 2) . 'px;';
        }
        if (false !== strpos($tableClass, 'middle')) {
            $style .= 'margin-top:' . (-$sizes['h'] / 2) . 'px;';
        }

        return $style;
    }

    public function getUsedProducts($product)
    {
        if ($product->isConfigurable()) {
            return $product->getTypeInstance(true)->getUsedProducts(null, $product);
        } else { // product is grouped
            return $product->getTypeInstance(true)->getAssociatedProducts($product);
        }
    }
}