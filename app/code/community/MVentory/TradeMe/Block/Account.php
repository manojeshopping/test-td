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
 * Fieldset for TradeMe account
 *
 * @package MVentory/TradeMe
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_TradeMe_Block_Account
  extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
  protected function _getHeaderCommentHtml ($element) {
    $comment = $element->getComment()
                 ? '<div class="comment">' . $element->getComment() . '</div>'
                   : '';

    $accountId = $element->getGroup()->getName();

    $authButton = array(
      'id' => 'trademe_button_auth_' . $accountId,
      'label' => $this->__('Authorise'),
      'onclick' => 'javascript:trademe_auth_account(\''
                   . $accountId
                   . '\'); return false;'
    );

    $removeButton = array(
      'id' => 'trademe_button_remove_' . $accountId,
      'label' => $this->__('Remove'),
      'onclick' => 'javascript:trademe_remove_account(\''
                   . $accountId
                   . '\'); return false;'
    );

    $buttonBlock = $this
      ->getLayout()
      ->createBlock('adminhtml/widget_button');

    return '<div style="float:right">'
           .  '<div class="form-buttons trademe-account-buttons">'
           .    $buttonBlock->setData($authButton)->toHtml()
           .    $buttonBlock->setData($removeButton)->toHtml()
           .  '</div>'
           .  '<div style="clear: both"></div>'
           . '</div>'
           . $comment;
  }

}
