<?php
class Magentoguys_Detectwidth_IndexController extends Mage_Core_Controller_Front_Action
{
   
   /**
     * Checkout page
     */
    public function indexAction()
    {
		if(isset($_POST['screen_width'])){
			$screenWidth = $_POST['screen_width'];	
			Mage::getSingleton('core/session')->setScreenWidth($screenWidth);
		}
		
    }
   
}