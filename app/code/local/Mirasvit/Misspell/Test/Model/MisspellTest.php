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


class Mirasvit_Misspell_Test_Model_MisspellTest extends EcomDev_PHPUnit_Test_Case
{
    protected $_model;

    protected function setUp()
    {
        $this->_model = Mage::getModel('misspell/misspell');
        Mage::getSingleton('misspell/indexer')->reindexAll();
    }

    /**
     * @test
     * @cover getSuggest
     * 
     * @loadFixture  products
     * @dataProvider getSuggestProvider
     * 
     * @doNotIndex catalog_product_price
     */
    public function getSuggestTest($correct, $fail)
    {
        $result = $this->_model->getSuggest($fail);
        $this->assertEquals($correct, $result);
    }

    public function getSuggestProvider()
    {
        return array(
            // Ð¾Ð´Ð½Ð° Ð¾ÑÐ¸Ð±ÐºÐ°
            array('canon', 'canin'),
            array('canon', 'canun'),
            array('samsung', 'simsung'),
            array('samsung', 'samsing'),
            array('diamond', 'diemond'),

            // Ð´Ð²Ðµ Ð¾ÑÐ¸Ð±ÐºÐ¸
            array('canon', 'cinun'),
            array('samsung', 'simuung'),
            array('samsung', 'simuung'),

            // Ð¿ÑÐ¾Ð¿ÑÑÐº Ð±ÑÐºÐ²Ñ
            array('canon', 'caon'),
            array('samsung', 'samung'),
            array('diamond', 'diamod'),

            // Ð»Ð¸ÑÐ½ÑÑ Ð±ÑÐºÐ²Ð°
            array('canon', 'cannon'),
            array('samsung', 'samsiung'),
            array('diamond', 'diammond'),

            // Ð¿ÐµÑÐµÑÑÐ°Ð½Ð¾Ð²ÐºÐ°
            array('canon', 'caonn'),
            array('samsung', 'samsugn'),
            array('diamond', 'diamnod'),

            // ÑÐ»Ð¸ÑÐ½Ð¾Ðµ Ð½Ð°Ð¿Ð¸ÑÐ°Ð½Ð¸Ðµ
            array('samsung phone', 'samsungphone'),
            array('htc diamond touch', 'htc diamondtouch'),
            array('htc diamond phone', 'htc diamond phone'),

            // ÑÐµÐ³Ð¸ÑÑÑ
            array('Samsung Phone SMG-GLX-6798', 'SamsungPhone SMG-GLX-6798'),

            // Ð½ÐµÑ ÑÐ¾Ð¾ÑÐ²ÐµÑÑÐ²Ð¸Ñ
            array('SMG-GLX-6798', 'SMG-GLX-6798'),
            array('', 'apple'),
            array('', 'nikon'),
        );
    }
}