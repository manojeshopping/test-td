// JavaScript Document

jQuery(document).ready(function($){
	/* Begin: Show o hide cpanel */
	$('#sns_config_btn').click(function(){
		if(!$(this).hasClass('open')){
			$('#sns_cpanel').animate({
				'left':'-311px'
			}, 200, function(){
				$('#sns_cpanel').show().animate({
					'left':'0px'
				});
			});
			$(this).addClass('open');
			return;
		}else{
			$('#sns_cpanel').animate({
				'left':'0px'
			}, 200, function(){
				$('#sns_cpanel').show().animate({
					'left':'-311px'
				});
			});
			$(this).removeClass('open');
			return;
		}
	});
	/* End: Show o hide cpanel */
});
var TMPL_NAME = 'sns_i8style';

function cPReset(){
	var elems = document.getElementById('sns_cpanel').getElementsByTagName ('*');
	for (i=0;i<elems.length;i++) {
		var el = elems[i]; 
	    if (el.name && (match=el.name.match(/^sns_cfg_(.*)$/))) {
	    	var name = match[1];
	    	createCookie (TMPL_NAME+'_' + name, '', -1);
	    }
	}
	window.location.reload(true);
}

function cPApply () {
	var elems = document.getElementById('sns_cpanel').getElementsByTagName ('*');
	var usersetting = {};
	for (i=0;i<elems.length;i++) {
		var el = elems[i];
	    if (el.name && (match=el.name.match(/^sns_cfg_(.*)$/))) {
	        var name = match[1];
	        var value = '';
	        if (el.tagName.toLowerCase() == 'input' && (el.type.toLowerCase()=='radio' || el.type.toLowerCase()=='checkbox')) {
	        	if (el.checked) value = el.value;
	        } else {
	        	value = el.value;
	        }
			if(trim(value)){
				if (usersetting[name]) {
					if (value) usersetting[name] = value + ',' + usersetting[name];
				} else {
					usersetting[name] = value;
				}
			}
	    }
	}
	for (var k in usersetting) {
		name = TMPL_NAME + '_' + k; //alert(name);
		value = usersetting[k];
		createCookie(name, value, 0);
	}
	window.location.reload(true);
}

jQuery.cookie = function(name, value, options) {
	if (typeof value != 'undefined') { // name and value given, set cookie
		options = options || {};
		if (value === null) {
			value = '';
			options.expires = -1;
		}
		var expires = '';
		if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
			var date;
			if (typeof options.expires == 'number') {
				date = new Date();
				date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
			} else {
				date = options.expires;
			}
			expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
		}
		// CAUTION: Needed to parenthesize options.path and options.domain
		// in the following expressions, otherwise they evaluate to undefined
		// in the packed version for some reason...
		var path = options.path ? '; path=' + (options.path) : '';
		var domain = options.domain ? '; domain=' + (options.domain) : '';
		var secure = options.secure ? '; secure' : '';
		document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
	} else { // only name given, get cookie
		var cookieValue = null;
		if (document.cookie && document.cookie != '') {
			var cookies = document.cookie.split(';');
			for (var i = 0; i < cookies.length; i++) {
				var cookie = jQuery.trim(cookies[i]);
				// Does this cookie string begin with the name we want?
				if (cookie.substring(0, name.length + 1) == (name + '=')) {
					cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
					break;
				}
			}
		}
		return cookieValue;

	}
};

function trim(str, chars) {
	chars = chars || "\\s";
	str =   str.replace(new RegExp("^[" + chars + "]+", "g"), "");
	str =  str.replace(new RegExp("^[" + chars + "]+", "g"), "");
	return str;
}

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}
function getCookie(c_name, defaultvalue){	//alert(document.cookie);
	var i,x,y,arrcookies=document.cookie.split(";");
	for (i=0;i<arrcookies.length;i++){
	  x=arrcookies[i].substr(0,arrcookies[i].indexOf("="));
	  y=arrcookies[i].substr(arrcookies[i].indexOf("=")+1);
	  x=x.replace(/^\s+|\s+$/g,"");
	  if (x==c_name){
		  return unescape(y);
	  }
	}
	return defaultvalue;
}

toggleMenu = function (el, over) {
}
String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); };