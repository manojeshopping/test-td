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
 * Config form fieldset renderer
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Block_Setting_Fieldset
  extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

  /**
   * Return value of field that is set in the related XML node
   *
   * @param string $key Name of field
   * @return string|null
   */
  public function getXmlData ($key) {
    $data = $this
      ->getElement()
      ->getOriginalData();

    return isset($data[$key]) ? $data[$key] : null;
  }

  /**
   * Return footer html for fieldset
   * Add extra tooltip comments to elements
   *
   * @param Varien_Data_Form_Element_Abstract $element
   * @return string
   */
  protected function _getFooterHtml ($element) {
    $html = '</tbody></table>';

    if ($footer = $this->getXmlData('footer'))
      $html .= '<div class="fieldset-footer">' . $footer . '</div>';

    return $html
           . '</fieldset>'
           . $this->_getExtraJs($element, false)
           . '</div>'
           . ($element->getIsNested() ? '</td></tr>' : '');
  }
}

?>