<?php

class Sns_I8style_Block_Adminhtml_System_Config_Form_Field_Addpartner extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        if (!$this->getTemplate()) {
            $this->setTemplate('sns/i8style/system/config/form/field/array.phtml');
        }
        $this->addColumn('title', array(
            'label' => Mage::helper('adminhtml')->__('Title '),
            'style' => 'width:150px',
            'type' => 'input'
        ));
        $this->addColumn('link', array(
            'label' => Mage::helper('adminhtml')->__('Link'),
            'style' => 'width:150px',
            'type' => 'input',
        ));
        $this->addColumn('target', array(
            'label' => Mage::helper('adminhtml')->__('Target'),
            'style' => 'width:80px',
            'type' => 'select',
            'values' => array(
            				'_blank'	=> '_blank',
            				'_self'		=> '_self',
				            '_parent'	=> '_parent',
				            '_top'		=> '_top'
        				)
        ));
        $this->addColumn('image', array(
            'label' => Mage::helper('adminhtml')->__('Logo'),
            'type' => 'image',
        ));	
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Partner');
        parent::__construct();
    }
    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }
		if ($column['type'] == 'input') {
	        $html = '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
		            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
		            (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
		            (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';
		}
		if ($column['type'] == 'image') {
			$inputId = $columnName.'#{_id}';
			$url = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg_images/index', array(
                    'static_urls_allowed'   => 1,
                    'target_element_id'     => $inputId
                ));
            $html = '<div style="width: 185px; height: 1px;"></div>';
	        $html .= '<input data-url="'.$url.'" id="'.$inputId.'" type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
		            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
		            (isset($column['class']) ? $column['class'] : 'input-text') . '"'.' style="width: 130px;"/>';
			$html .= '<button onclick="popup_wysiwyg('.$inputId.')" class="scalable popup_wysiwyg" type="button" title="Click to browser media"><span><span><span>...</span></span></span></button>';
			$html .= '<button style="" onclick="on_clear_click('.$inputId.');" class="scalable " type="button" title="Click to clear value"><span><span><span>x</span></span></span></button>';
		}
		if ($column['type'] == 'select') {
			$html = '<select name="' . $inputName . '" ' .
		            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
		            (isset($column['class']) ? $column['class'] : 'input') . '"'.
		            (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '>';
			
			$options = $column['values'];
			foreach ($options as $key => $option) {
				$html .= '<option data-selected="#{' . $columnName . '}" value="'.$key.'">'.$option.'</option>';
			}
			$html .= '</select>';
		}
		return $html;
    }
    public function addColumn($name, $params) {
        $this->_columns[$name] = array(
            'label'     => empty($params['label']) ? 'Column' : $params['label'],
            'size'      => empty($params['size'])  ? false    : $params['size'],
            'style'     => empty($params['style'])  ? null    : $params['style'],
            'class'     => empty($params['class'])  ? null    : $params['class'],
            'type'    	=> empty($params['type'])  ? null    : $params['type'],
            'values'   	=> empty($params['values'])  ? null    : $params['values'],
            'renderer'  => false,
        );
        if ((!empty($params['renderer'])) && ($params['renderer'] instanceof Mage_Core_Block_Abstract)) {
            $this->_columns[$name]['renderer'] = $params['renderer'];
        }
    }
}