<?php

class Magestore_Onestepcheckout_Helper_Message extends Mage_GiftMessage_Helper_Message {

    const CHECKOUT_PAGE_STYLE = 'onestepcheckout/general/page_style';
    const CHECKOUT_PAGE_LAYOUT = 'onestepcheckout/general/page_layout';
        
    public function getInline($type, Varien_Object $entity, $dontDisplayContainer = false) {
        if (in_array($type, array('onepage_checkout', 'multishipping_adress'))) {
            if (!$this->isMessagesAvailable('items', $entity)) {
                return '';
            }
        } elseif (!$this->isMessagesAvailable($type, $entity)) {
            return '';
        }

        $style = Mage::getStoreConfig(self::CHECKOUT_PAGE_STYLE);
        $layout = Mage::getStoreConfig(self::CHECKOUT_PAGE_LAYOUT);
        $folder = '';

        if ($style == 'flat' && $layout == '30columns')
            $folder = 'flatnew';
        else
            $folder = $style;

        return Mage::getSingleton('core/layout')->createBlock('giftmessage/message_inline')
                        ->setId('giftmessage_form_' . $this->_nextId++)
                        ->setDontDisplayContainer($dontDisplayContainer)
                        ->setEntity($entity)
                        ->setType($type)
                        ->setTemplate('onestepcheckout/'.$folder.'/giftmessage/inline.phtml')
                        ->toHtml();
    }

}
