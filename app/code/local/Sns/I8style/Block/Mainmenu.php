<?php
class Sns_I8style_Block_Mainmenu extends Mage_Catalog_Block_Navigation {
    protected $_groupitemwidth = 3;

    protected function _construct()
    {
        parent::_construct();
    }
    protected function _getMoMenuHtml($category, $level = 0, $isLast = false, $isFirst = false) {
        if (!$category->getIsActive()) {
            return '';
        }
        $html = array();
		
        // get all children
        if (Mage::helper('catalog/category_flat')->isEnabled()) {
            $children = (array)$category->getChildrenNodes();
            $childrenCount = count($children);
        } else {
            $children = $category->getChildren();
            $childrenCount = $children->count();
        }
        $hasChildren = ($children && $childrenCount);

        // select active children
        $activeChildren = array();
        foreach ($children as $child) {
            if ($child->getIsActive()) {
                $activeChildren[] = $child;
            }
        }
        $activeChildrenCount = count($activeChildren);
        $hasActiveChildren = ($activeChildrenCount > 0);
        $catdetail = Mage::getModel('catalog/category')->load($category->getId());
        
        // render children
        $li = '';
        $j = 0;
        foreach ($activeChildren as $child) {
            $li .= $this->_getMoMenuHtml(
                $child,
                ($level + 1),
                ($j == $activeChildrenCount - 1),
                ($j == 0)
            );
            $j++;
        }

        // prepare list item html classes
        $itemposition = $this->_getItemPosition($level);
        
        $linkClass = '';
        $classes = array();
        $classes[] = 'level' . $level;

        $classes[] = 'nav-' . $itemposition;
        if ($this->isCategoryActive($category)) {
            $classes[] = 'active';
			$classes[] = 'open';
            $linkClass = ' active';
        }
        
		if($level==0 || $level==1){
			// $classes[] = 'open';
		}
		if($level==1){
			$ulClass = 'dropdown-menu1';
		}else{
			$ulClass = 'dropdown-menu';
		}
	  
        $linkClass .= ' menu-title-lv'.$level;

		if ($isFirst) $classes[] = 'first';
        if ($isLast) $classes[] = 'last';
        if ($hasActiveChildren) $classes[] = 'parent';
		if(!empty($li) && $hasActiveChildren && $level>1){
			$classes[] = 'dropdown-submenu';
		}
		elseif(!empty($li) && $hasActiveChildren && $level!=1 && $level<0){
			$classes[] = 'dropdown';
		}
		if(!empty($li) && $hasActiveChildren && $level!=1){
			$arrowIcon = '<span class="caret"></span>';
		}
		if($hasActiveChildren){
			$linkClass .= ' dropdown-toggle';
			$dataMoToggle = 'data-toggle="dropdown"';
		}
		
        $liclass = implode(' ', $classes);
		
		$html[] = '<li class="'.$liclass.'">';
        $html[] = '<a href="'.$this->getCategoryUrl($category).'" class="'.$linkClass.' dropdown-toggle" '.$dataMoToggle.'>';
        $html[] = '<span>' . $this->escapeHtml($category->getName()) . '</span>';
		$html[] = $arrowIcon;
        $html[] = '</a>';
        
        if (!empty($li) && $hasActiveChildren) {
            $html[] = '<ul class="level' . $level . ' '.$ulClass.'">';
            $html[] = $li;
            $html[] = '</ul>';
        }

        $html[] = '</li>';

        $html = implode("\n", $html);
        return $html;

    }
    protected function _getMenuItemHtml($category, $level = 0, $isLast = false, $isFirst = false) {
        if (!$category->getIsActive()) {
            return '';
        }
        $html = array();
		
        // get all children
        if (Mage::helper('catalog/category_flat')->isEnabled()) {
            $children = (array)$category->getChildrenNodes();
            $childrenCount = count($children);
        } else {
            $children = $category->getChildren();
            $childrenCount = $children->count();
        }
        $hasChildren = ($children && $childrenCount);

        // select active children
        $activeChildren = array();
        foreach ($children as $child) {
            if ($child->getIsActive()) {
                $activeChildren[] = $child;
            }
        }
        $activeChildrenCount = count($activeChildren);
        $hasActiveChildren = ($activeChildrenCount > 0);
        $catdetail = Mage::getModel('catalog/category')->load($category->getId());
        
        // render children
        $li = '';
        $j = 0;
        foreach ($activeChildren as $child) {
            $li .= $this->_getMenuItemHtml(
                $child,
                ($level + 1),
                ($j == $activeChildrenCount - 1),
                ($j == 0)
            );
            $j++;
        }
		
        $showsubmenu = false;
        if(!empty($li) && $hasActiveChildren) $showsubmenu = true;
        
        $menutypes = $catdetail->getData('i8style_menutype');
        $rightblock = $this->_getCatBlock($catdetail, 'i8style_block_r');
        $topblock = $this->_getCatBlock($catdetail, 'i8style_block_t');
        $bottomblock = $this->_getCatBlock($catdetail, 'i8style_block_b');
        $hidelink = $this->_getCatBlock($catdetail, 'i8style_menulink');
        
        $showblock = false;
        if(($rightblock || $topblock || $bottomblock) && $menutypes != 0) $showblock = true;

        // prepare list item html classes
        $itemposition = $this->_getItemPosition($level);
        
        $classes = array();
        $classes[] = 'level' . $level;

        $classes[] = 'nav-' . $itemposition;
        if ($this->isCategoryActive($category)) {
            $classes[] = 'active';
		}
        $linkClass = '';
        
        $linkClass .= ' menu-title-lv'.$level;
        
		// $linkClass .= ' dropdown-toggle';
        if($level==0){
        	if($this->_isgroup) {
        		$classes[] = 'group-item';
        	} else {
        		$classes[] = 'no-group';
        	}
        	if($menutypes == 0){
	            $classes[] = 'drop-submenu';
	            $showblock = false;
	        }
        	if($menutypes == 1){
	            $classes[] = 'drop-blocks';
	            $showsubmenu = false;
	        }
        	if($menutypes == 2){
	            $classes[] = 'drop-submenu-blocks';
	        }
			if (!empty($li) && $hasActiveChildren && $showsubmenu == true && $showblock){
				$classes[] = 'dropdown yamm-fw';
			}elseif(!empty($li) && $hasActiveChildren && $showsubmenu == true && !$showblock){
				$classes[] = 'dropdown';
			}
			
        }
		if ($isFirst) $classes[] = 'first';
        if ($isLast) $classes[] = 'last';
        if ($hasActiveChildren) $classes[] = 'parent';
		
		
        if ($level == 0) {
        	$submenuwidth = $catdetail->getData('i8style_subcat_w');
			
        	if(!$rightblock) {
        		$submenuwidth = 12;
        	}
            if($submenuwidth == 12 || $showsubmenu == false){
            	$rightwidth = 12;
            } else {
            	$rightwidth = 12 - $submenuwidth;
            }
        }
		
        if($level==1 && $this->_isgroup){
            $classes[] = 'group-block col-sm-'.$this->_groupitemwidth;
			$classes[] = 'dept';
        }
        
        $liclass = implode(' ', $classes);
		
		if($level == 0){
			if (!empty($li) && $hasActiveChildren && $showsubmenu == true){
				$below_arrow = '<span class="caret"></span>';
				$data_toggle_elm = 'data-toggle="dropdown"';
			}
			else{
				$below_arrow = '';
				$data_toggle_elm = '';
			}
		
			if($category->getId()==13){
				// $caret_down = '<i class="fa fa-caret-down menu-caret"></i>';
				$products_count = Mage::getModel('catalog/category')->load($category->getId())->getProductCount();
				$notification = '<div class="notification">'.$products_count.'</div>';
			}
			else{
				// $caret_down = ''; 
				$notification = '';
			}
			if($hidelink) {
				$url = 'javascript:void(0)';
			} else {
				$url = $this->getCategoryUrl($category);
			}
			
			$html[] = '<li class="'.$liclass.'">';
	        $html[] = '<a href="'.$url.'" class="'.$linkClass.' dropdown-toggle" '.$data_toggle_elm.'>';
	        $html[] = '<span>' . $this->escapeHtml($category->getName()) . '</span>';
			$html[] = $below_arrow;
	        $html[] = '</a>';
			$html[] = $notification;
  		} elseif($level == 1) {
			$html[] = '<li class="'.$liclass.' dropdown1">';
	        $html[] = '<a href="'.$this->getCategoryUrl($category).'" class="'.$linkClass.' dropdown-toggle" tabindex="0">';
	        $html[] = '<span>' . $this->escapeHtml($category->getName()) . '</span>';
	        $html[] = '</a>';
		} else {
			if (!empty($li) && $hasActiveChildren && $showsubmenu == true){
				// $submenuClass = 'dropdown-submenu1';
				// $dataToggleClass = '';
				// $dataToggle = 'data-toggle="dropdown"';
				$gourl= $this->getCategoryUrl($category);
				// $area_expanded = 'aria-expanded="false"';
			}
			else{
				$gourl = $this->getCategoryUrl($category);
			}
			$html[] = '<li class="'.$liclass.'">';
	        $html[] = '<a href="'.$gourl.'" class="'.$linkClass.'">';
	        $html[] = '<span>' . $this->escapeHtml($category->getName()) . '</span>';
	        $html[] = '</a>';
		}

		if($level == 0 && $showblock) {
			$html[] = '<ul class="dropdown-menu fullwidth">';
			$html[] = '<div class="row">';
			
			// top block
			if($topblock){
				$html[] = '<div class="col-sm-12 hidden-xs hidden-sm">';
				$html[] = '<div class="wrap_topblock">';
				$html[] = $topblock;
				$html[] = '</div>';
				$html[] = '</div>';
			}
		}
        if (!empty($li) && $hasActiveChildren && $showsubmenu == true) {
            if($level == 0){
            	if($this->_isgroup) {
            		if($showblock) { $html[] = '<div class="wrap_group  col-sm-'.$submenuwidth.'">';
					$dropdownmenuClass = '';}
            		elseif(!$showblock){ $html[] = '';
					$dropdownmenuClass = ' dropdown-menu'; }
					else { $html[] = '<div class="wrap_group fullwidth">';
            		$dropdownmenuClass = ''; }
					
	                $html[] = '<ul class="row level' . $level . $dropdownmenuClass.'">';
	                $html[] = $li;
	                $html[] = '</ul>';
	                if($showblock) $html[] = '</div>';
					elseif(!$showblock) $html[] = '';
					else $html[] = '</div>';
            	} else {
            		if($showblock) { $html[] = '<div class="wrap_submenu col-sm-'.$submenuwidth.'">';
					$dropdownmenuClass = '';
					}
            		elseif(!$showblock){ 
						$html[] = '';
						$dropdownmenuClass = ' dropdown-menu';
					}
					else{ 
					$html[] = '<div class="wrap_submenu">';
					$dropdownmenuClass = '';
            		}
	                $html[] = '<ul class="level' . $level . $dropdownmenuClass.'">';
	                $html[] = $li;
	                $html[] = '</ul>';
	                if($showblock) $html[] = '</div>';
					elseif(!$showblock) $html[] = '';
					else $html[] = '</div>';
            	}
            }elseif($level == 1){
				$html[] = '<ul class="level' . $level . ' mega-list dropdown-menu1">';
                $html[] = $li;
                $html[] = '</ul>';
			} else {
            	// $html[] = '<div class="wrap_submenu">';
                $html[] = '<ul class="hide level' . $level . ' dropdown-menu">';
                $html[] = $li;
                $html[] = '</ul>';
               // $html[] = '</div>';
            }
        }
		if($level == 0 && $showblock) {

			// right block
			if($rightblock){
				$html[] = '<div class="col-sm-'.$rightwidth.'">';
				$html[] = '<div class="wrap_rightblock">';
				$html[] = $rightblock;
				$html[] = '</div>';
				$html[] = '</div>';
			}

			// bottom block
			if($bottomblock){
				$html[] = '<div class="col-sm-12">';
				$html[] = '<div class="wrap_bottomblock">';
				$html[] = $bottomblock;
				$html[] = '</div>';
				$html[] = '</div>';
			}
			
			$html[] = '</div>'; // end row
			$html[] = '</ul>';
		}
		

        $html[] = '</li>';

		if($level==0 && $category->getId()==14){
			$_applianceCategory = Mage::getModel('catalog/category')->load(5);
			$_applianceCategoryUrl = $_applianceCategory->getUrl();
			
			$_furnitureCategory = Mage::getModel('catalog/category')->load(6);
			$_furnitureCategoryUrl = $_furnitureCategory->getUrl();
			
			//MVENTORY: define default value for the variable
			$activeApplianceClass = '';
			if ($this->isCategoryActive($_applianceCategory)) {
				$activeApplianceClass = 'active';
			}

			//MVENTORY: define default value for the variable
			$activeFurnitureClass = '';
			if ($this->isCategoryActive($_furnitureCategory)) {
				$activeFurnitureClass = 'active';
			}
			
			$html[] = '<li class="level0 nav-2 no-group drop-submenu parent '.$activeApplianceClass.'">';
	        $html[] = '<a href="'.$_applianceCategoryUrl.'" class="'.$linkClass.'">';
	        $html[] = '<span>' . $this->escapeHtml('APPLIANCES') . '</span>';
	        $html[] = '</a>';
			$html[] = '</li>';
			
			$html[] = '<li class="level0 nav-2 no-group drop-submenu parent '.$activeFurnitureClass.'">';
	        $html[] = '<a href="'.$_furnitureCategoryUrl.'" class="'.$linkClass.'">';
	        $html[] = '<span>' . $this->escapeHtml('FURNITURE') . '</span>';
	        $html[] = '</a>';
			$html[] = '</li>';
		}
		
        $html = implode("\n", $html);
        return $html;

    }

    public function getMenuHtml($momenu = false, $level = 0) {
    	
        $this->_isMomenu = $momenu;
        $activeCategories = array();
        foreach ($this->getStoreCategories() as $child) {
            if ($child->getIsActive()) {
                $activeCategories[] = $child;
            }
        }
        $activeCategoriesCount = count($activeCategories);
        $hasActiveCategoriesCount = ($activeCategoriesCount > 0);

        if (!$hasActiveCategoriesCount) {
            return '';
        }

        $html = '';
        $j = 0;
        
        
        $customitems	=	Mage::helper('i8style/data')->getField('menu_customItems');
		$array_customitems = unserialize($customitems);

		$collect_customitems = array();
		foreach($array_customitems as $key=>$customitem){
			$customitem['link'] = Mage::helper('cms')->getBlockTemplateProcessor()->filter($customitem['link']);
			$collect_customitems[] = $customitem;
		}
		if($this->_isMomenu) $html .= $this->_getCustomItems(true, $collect_customitems, 'first');
		else $html .= $this->_getCustomItems(false, $collect_customitems, 'first');

        foreach ($activeCategories as $category) {
            if($this->_isMomenu){
                $html .= $this->_getMoMenuHtml(
                    $category,
                    $level,
                    ($j == $activeCategoriesCount - 1),
                    ($j == 0)
                );
            } else {
		        $catdetail = Mage::getModel('catalog/category')->load($category->getId());
		        if($catdetail->getData('i8style_groupsubcat')) {
		        	$this->_isgroup = true;
		        	$this->_groupitemwidth = $catdetail->getData('i8style_subcat_colw');
		        } else {
		        	$this->_isgroup = false;
		        }
                $html .= $this->_getMenuItemHtml(
                    $category,
                    $level,
                    ($j == $activeCategoriesCount - 1),
                    ($j == 0)
                );
                $html .= $this->_getCustomItems(false, $collect_customitems, $category->getId());
            }
            $j++;
        }
		if($this->_isMomenu) $html .= $this->_getCustomItems(true, $collect_customitems, 'last');
		else $html .= $this->_getCustomItems(false, $collect_customitems, 'last');
        
        return $html;
    } 
    protected function _getCustomItems($momenu = false, $items, $position){
    	$curUrl = Mage::helper('core/url')->getCurrentUrl();
    	$html = '';
		foreach($items as $menuitem){
			$liClass = 'level0 custom-item';
			$lickClass = 'menu-title-lv0';
			if($menuitem['status'] && $momenu == false) $liClass .= "drop-staticblock";
			if(strtolower($curUrl) == strtolower($menuitem['link'])) {
				$liClass .= ' active';
				$lickClass .= ' active';
			}
			$drophtml = '';
			if($menuitem['status'] && $momenu == false){
				$drophtml .= '<ul class="dropdown-menu fullwidth">';
				$drophtml .= $this->getLayout()->createBlock('cms/block')->setBlockId($menuitem['block_id'])->toHtml();
				$drophtml .= '</ul>';
			}
			if($menuitem['position'] == $position){
				$html .= '<li class="'.$liClass.'">';
				$html .= 	'<a class="'.$lickClass.'" target="'.$menuitem['target'].'" href="'.$menuitem['link'].'">';
				$html .= 		'<span>'.$menuitem['title'].'</span>';
				$html .= 	'</a>';
				$html .= 	$drophtml;
				$html .= '</li>';
			}
		}
		return $html;
    }
    protected function _getCatBlock($category, $block){
        if (!$this->_tplProcessor){
            $this->_tplProcessor = Mage::helper('cms')->getBlockTemplateProcessor();
        }
        return $this->_tplProcessor->filter( trim($category->getData($block)) );
    }
	protected function _toHtml(){
		if (!$this->getTemplate()){
			$this->setTemplate('sns/blocks/mainmenu.phtml');
		}
		return parent::_toHtml();
	}
}