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


class Mirasvit_SearchSphinx_Test_Model_Engine_Fulltext extends EcomDev_PHPUnit_Test_Case
{
    protected $engine, $index;

    protected function mockConfigMethod($methods)
    {
        $config = $this->getModelMock('searchsphinx/config', array_keys($methods));
        foreach ($methods as $method => $value) {
            $config->expects($this->any())
                ->method($method)
                ->will($this->returnValue($value));
        }
        $this->replaceByMock('singleton', 'searchsphinx/config', $config);
    }

    protected function setUp()
    {
        $this->engine = Mage::getModel('searchsphinx/engine_fulltext');
        $this->index = Mage::helper('searchindex/index')->getIndex('mage_catalog_product');
        $this->index->reindexAll();
    }

    /**
    * @test
    * @loadFixture products
    * @doNotIndex catalog_product_price
    */
    public function queryTest()
    {
        $this->mockConfigMethod(array('isAllowedWildcard' => true));

        $result = $this->engine->query('Book', 2, $this->index);
        $this->assertEquals(4, count($result));

        $result = $this->engine->query('Book2', 2, $this->index);
        $this->assertEquals(1, count($result));
    }


    /**
    * @test
    * @loadFixture products
    * @doNotIndex catalog_product_price
    */
    public function wildcardSearchTest()
    {
        /////////////////
        $this->mockConfigMethod(array('isAllowedWildcard' => true));

        $result = $this->engine->query('Book2', 2, $this->index);
        $this->assertEquals(1, count($result));

        $result = $this->engine->query('Boo', 2, $this->index);
        $this->assertEquals(4, count($result));

        /////////////////
        $this->mockConfigMethod(array('isAllowedWildcard' => false));
        $result = $this->engine->query('Book', 2, $this->index);
        $this->assertEquals(1, count($result));

        $this->mockConfigMethod(array('isAllowedWildcard' => false));
        $result = $this->engine->query('Book2', 2, $this->index);
        $this->assertEquals(1, count($result));

        $this->mockConfigMethod(array('isAllowedWildcard' => false));
        $result = $this->engine->query('Boo', 2, $this->index);
        $this->assertEquals(0, count($result));
    }

    /**
    * @test
    * @loadFixture products
    * @doNotIndex catalog_product_price
    */
    public function wildcardExceptionsSearchTest()
    {
        $this->mockConfigMethod(array('isAllowedWildcard' => true));
        $result = $this->engine->query('book', 2, $this->index);
        $this->assertEquals(4, count($result));

        $this->mockConfigMethod(array('isAllowedWildcard' => true, 'getWildcardExceptions' => array('book')));
        $result = $this->engine->query('Book', 2, $this->index);
        $this->assertEquals(1, count($result));
    }

    /**
    * @test
    * @loadFixture products_synonyms
    * @loadFixture synonyms
    * @doNotIndex catalog_product_price
    */
    public function synonymsTableTest()
    {
        /* Ð¼Ñ Ð¸ÑÐµÐ¼ word01, Ð¾Ð½Ð¾ Ð¸Ð¼ÐµÐµÑ ÑÐ¸Ð½Ð¾Ð½Ð¸Ð¼ word04, Ð¿Ð¾ÑÑÐ¾Ð¼Ñ Ð½Ð°ÑÐ¾Ð´Ð¸Ð¼ ÑÐ¾Ð²Ð°Ñ word01 Ð¸ word04 */
        $result = $this->engine->query('word01', 2, $this->index);
        $this->assertEquals(2, count($result));

        /* Ð¼Ñ Ð¸ÑÐµÐ¼ word04, Ð¾Ð½Ð¾ Ð¸Ð¼ÐµÐµÑ ÑÐ¸Ð½Ð¾Ð½Ð¸Ð¼ word01, Ð¿Ð¾ÑÑÐ¾Ð¼Ñ Ð½Ð°ÑÐ¾Ð´Ð¸Ð¼ ÑÐ¾Ð²Ð°Ñ word01 Ð¸ word04 */
        $result = $this->engine->query('word04', 2, $this->index);
        $this->assertEquals(2, count($result));
    }

}
