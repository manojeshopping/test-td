<?php
/*------------------------------------------------------------------------
 * Copyright (C) 2013 The SNS Theme. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: SNS Theme
 * Websites: http://www.snstheme.com
-------------------------------------------------------------------------*/
class SnsTheme {
	var $_params;
	var $template = '';
	function SnsTheme () {
		$this->_params = array();
		$this->template = Mage::getSingleton('core/design_package')->getTheme('frontend');
	}
	function getParam ($key, $default = '') { 
		return isset($this->_params[$key])?$this->_params[$key]:$default;
	}
	function setParam ($key, $value = '') {
		$this->_params[$key] = $value;
	}
	function isOP () {
		return isset($_SERVER['HTTP_USER_AGENT']) &&
			preg_match('/opera/i',$_SERVER['HTTP_USER_AGENT']);
	}
	function isChrome () {
		return isset($_SERVER['HTTP_USER_AGENT']) &&
			preg_match('/chrome/i',$_SERVER['HTTP_USER_AGENT']);
	}
	function isSafari () {
		return isset($_SERVER['HTTP_USER_AGENT']) &&
			preg_match('/safari/i',$_SERVER['HTTP_USER_AGENT']);
	}
	function isIE() {
		return isset($_SERVER['HTTP_USER_AGENT']) &&
			preg_match('/msie/i',$_SERVER['HTTP_USER_AGENT']);
	}
	function mobile_device_detect () {
		require_once ('sns-devicedetect.php');
		//bypass special browser:
		$special = array('jigs', 'w3c ', 'w3c-', 'w3c_');
		if (in_array(strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4)), $special)) return false;
		return mobile_device_detect('iphone','android','opera','blackberry','palm','windows');
	}
	
	function mobile_device_detect_layout () {
		$ui = $this->getParam('ui');
		return $ui=='desktop'?false:(($ui=='mobile' && !$this->mobile_device_detect())?'iphone':$this->mobile_device_detect());
	}
	
	function baseurl(){
		return $this->getBaseURL();
	}
	function getBaseURL() {
		static $_baseURL = '';
		if (!$_baseURL) {
			// Determine if the request was over SSL (HTTPS)
			if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) {
				$https = 's://';
			} else {
				$https = '://';
			}

			/*
			 * Since we are assigning the URI from the server variables, we first need
			 * to determine if we are running on apache or IIS.  If PHP_SELF and REQUEST_URI
			 * are present, we will assume we are running on apache.
			 */
			if (!empty ($_SERVER['PHP_SELF']) && !empty ($_SERVER['REQUEST_URI'])) {

				/*
				 * To build the entire URI we need to prepend the protocol, and the http host
				 * to the URI string.
				 */
				$theURI = 'http' . $https . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			/*
			 * Since we do not have REQUEST_URI to work with, we will assume we are
			 * running on IIS and will therefore need to work some magic with the SCRIPT_NAME and
			 * QUERY_STRING environment variables.
			 */
			}
			 else
			{
				// IIS uses the SCRIPT_NAME variable instead of a REQUEST_URI variable... thanks, MS
				$theURI = 'http' . $https . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

				// If the query string exists append it to the URI string
				if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
					$theURI .= '?' . $_SERVER['QUERY_STRING'];
				}
			}

			// Now we need to clean what we got since we can't trust the server var
			$theURI = urldecode($theURI);
			$theURI = str_replace('"', '&quot;',$theURI);
			$theURI = str_replace('<', '&lt;',$theURI);
			$theURI = str_replace('>', '&gt;',$theURI);
			$theURI = preg_replace('/eval\((.*)\)/', '', $theURI);
			$theURI = preg_replace('/[\\\"\\\'][\\s]*javascript:(.*)[\\\"\\\']/', '""', $theURI);	
			
			//Parse theURL
			$_parts = $this->_parseURL ($theURI);
			$_baseURL = '';
			$_baseURL .= (!empty($_parts['scheme']) ? $_parts['scheme'].'://' : '');
			$_baseURL .= (!empty($_parts['host']) ? $_parts['host'] : '');
			$_baseURL .= (!empty($_parts['port']) && $_parts['port']!=80 ? ':'.$_parts['port'] : '');

			if (strpos(php_sapi_name(), 'cgi') !== false && !empty($_SERVER['REQUEST_URI'])) {
				//Apache CGI
				$_path =  rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
			} else {
				//Others
				$_path =  rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
			}

			$_baseURL .= $_path;
		}
		return $_baseURL;
	}

	function _parseURL($uri)
	{
		$parts = array();
		if (version_compare( phpversion(), '4.4' ) < 0)
		{
			$regex = "<^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\\?([^#]*))?(#(.*))?>";
			$matches = array();
			preg_match($regex, $uri, $matches, PREG_OFFSET_CAPTURE);

			$authority = @$matches[4][0];
			if (strpos($authority, '@') !== false) {
				$authority = explode('@', $authority);
				@list($parts['user'], $parts['pass']) = explode(':', $authority[0]);
				$authority = $authority[1];
			}

			if (strpos($authority, ':') !== false) {
				$authority = explode(':', $authority);
				$parts['host'] = $authority[0];
				$parts['port'] = $authority[1];
			} else {
				$parts['host'] = $authority;
			}

			$parts['scheme'] = @$matches[2][0];
			$parts['path'] = @$matches[5][0];
			$parts['query'] = @$matches[7][0];
			$parts['fragment'] = @$matches[9][0];
		}
		else
		{
			$parts = @parse_url($uri);
		}
		return $parts;
	}
	
	function templateurl(){
		return Mage::getBaseDir('app')."/design/frontend/default/".$this->template;
	}

	function skinurl(){
		return Mage::getBaseUrl('skin')."frontend/default/".$this->template;
	}
	
	function isHomepage () {
		if (strpos(php_sapi_name(), 'cgi') !== false && !empty($_SERVER['REQUEST_URI'])) {
			//Apache CGI
			$_path =  rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		} else {
			//Others
			$_path =  rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
		}
		$uri = $_SERVER['REQUEST_URI']; //echo $_path.' ++++ '.$uri.'<br/>';
		if ($uri && $_path && strpos ($uri, $_path) === 0) {
			$uri = substr($uri, strlen ($_path));
		}
		$uri = strtolower($uri); //echo $uri.'<br/>';
		if(strpos($uri, 'index.php/?___store=')){
			$uri = substr($uri, 0, strpos($uri, '=')); //echo $uri; die();
		}
		if (in_array($uri, array('', '/', '/index.php','/index.php/', '/home', '/home/', '/default', '/default/', '/default/home', '/default/home/', '/index.php/home', '/index.php/home', '/index.php/?___store'))) return $uri;
		if (strpos($uri, '/home-')===0) return $uri;
		return FALSE;
	}
	function ieversion() {
		preg_match('/MSIE ([0-9]\.[0-9])/', $_SERVER['HTTP_USER_AGENT'], $reg);
		if(!isset($reg[1])) {
			return -1;
		} else {
			return floatval($reg[1]);
		}
	}
	function windowversion() { //echo $_SERVER['HTTP_USER_AGENT']; die();
		preg_match('/Windows NT ([0-9]\.[0-9])/', $_SERVER['HTTP_USER_AGENT'], $reg);
		if(!isset($reg[1])) {
			return -1;
		} else {
			return floatval($reg[1]);
		}
	}
}
?>


