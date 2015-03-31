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


class Mirasvit_Fpc_Block_Adminhtml_Crawler_Url_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('grid');
        $this->setDefaultSort('rate');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('fpc/crawler_url')
            ->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('url_id', array(
            'header'    => Mage::helper('fpc')->__('ID'),
            'align'     => 'right',
            'width'     => '50px',
            'index'     => 'url_id',
            )
        );

        $this->addColumn('url', array(
            'header'   => Mage::helper('fpc')->__('URL'),
            'index'    => 'url',
            )
        );

        $this->addColumn('cache_id', array(
            'header'   => Mage::helper('fpc')->__('Cache Id'),
            'index'    => 'cache_id',
            )
        );

        $this->addColumn('rate', array(
            'header' => Mage::helper('fpc')->__('Rate (number of visits)'),
            'index'  => 'rate',
            'align'  => 'right',
            'width'  => '100px',
            'type'   => 'number',
            )
        );

        $this->addColumn('cache_status', array(
            'header'    => Mage::helper('fpc')->__('Cache Status'),
            'index'     => 'url',
            'renderer'  => 'Mirasvit_Fpc_Block_Adminhtml_Crawler_Url_Grid_Renderer_Cache',
            'filter'    => false,
            'sortable'  => false,
            )
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('url_id');
        $this->getMassactionBlock()->setFormFieldName('url_id');
        $this->getMassactionBlock()->addItem('delete', array(
            'label'    => Mage::helper('fpc')->__('Delete'),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('fpc')->__('Are you sure?')
        ));
        $this->getMassactionBlock()->addItem('warm', array(
            'label'    => Mage::helper('fpc')->__('Warm cache'),
            'url'      => $this->getUrl('*/*/massWarm'),
        ));
        $this->getMassactionBlock()->addItem('clear', array(
            'label'    => Mage::helper('fpc')->__('Clear cache'),
            'url'      => $this->getUrl('*/*/massClear')
        ));
        return $this;
    }

    protected function _urlFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        $value = base64_encode($value);
        $value = substr($value, 0, strlen($value) - 3);

        $this->getCollection()
            ->addFieldToFilter($column->getIndex(), array('like' => '%'.$value.'%'));


        return $this;
    }
}