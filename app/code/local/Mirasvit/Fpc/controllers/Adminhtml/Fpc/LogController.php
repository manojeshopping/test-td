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


class Mirasvit_Fpc_Adminhtml_Fpc_LogController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Temporarily allow access for all users
     */
    protected function _isAllowed() {
        return true;
    }

    public function updateAction()
    {
        $log = Mage::getSingleton('fpc/log');
        $log->importFileLog();
        $log->getResource()->aggregate();

        $this->_redirectReferer();
    }
}

