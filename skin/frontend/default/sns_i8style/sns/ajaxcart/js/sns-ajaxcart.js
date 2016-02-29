var bwish = ".page-wrap .my-account";
var btnAddtocartClass = '.btn-cart';
var closeCFirm = 0;
var windown_compare = '';
var deletePCompare = 0;
var winFocus = 1;
var miniCartForm = Class.create();
var CANFLY = 0;

function reURLPoductsSpecial() {
    $$(btnAddtocartClass).each(function(el) {
        link = String(el.readAttribute('onclick'));
        if (link.search('checkout/cart/add') != -1 || link.search('options=cart') != -1) {} else {
            if (link.search("setLocation") != -1) {
                link = link.replace("')", "?options=cart')");
                el.writeAttribute('onclick', link);
            }
        }
    });
}

function aCConstruct() {
	/* reNewLinkLogin();
	reNewLinkRegister();
	reNewLinkForgotpassword(); */
    reURLPoductsSpecial();
    if (typeof productAddToCartForm != 'undefined') {
        productAddToCartForm.submit = function(args) {
            if (this.validator && this.validator.validate())
                callAjax($('product_addtocart_form').action, 'form');
            return false;
        };
    }
    reNewLinkDeleteInCart();
    if (ajax_for_compare) {
        reNewLinkCompare();
        reNewLinkRemoveCompare();
        reNewLinkClearCompare();
    }
    if (ajax_for_wishlist) {
		reNewLinkRemoveWishList();
		/* if (isLoggedIn == "0") reNewLinkWishList(); */
		if (isLoggedIn == "1") reNewLinkWishList();
        reNewLinkAddToCartInWishList();
		reNewLinkClearAllInWishList();
	}
    mycart_link = ($$('.top-link-cart') != '') ? '.top-link-cart' : '';
    cart_block = '.mini-cart, .block-cart';
    minicart_block = '.mini-cart';
    wishlist_block = ($$('.mini-wishlist') != '') ? '.mini-wishlist' : '.block-wishlist';
    compare_block = ($$('.mini-compare') != '') ? '.mini-compare' : '.block-compare';
}
window.popWin = function(url, newwin, para) {
    windown_compare = window.open(url, newwin, para);
    windown_compare.focus();
};
window.onunload = function() {
    if (windown_compare) {
        windown_compare.close();
    }
};
window.setLocation = function(args) {
    if (args.search('checkout/onepage') != -1 || args.search('catalog/category') != -1) {
        window.location = args;
        return;
    }
    if ((args.search('checkout/cart/add') != -1 || args.search('options=cart') != -1 || args.search('wishlist/index/cart') != -1 || args.search('wishlist/index/cart') != -1)) {
        callAjax(args, 'url');
    } else if (ajax_for_compare && args.search('catalog/product_compare/remove') != -1) {
        opener.deletePCompare = 1;
        if (winFocus) {
            opener.isCPPage = 1;
            opener.deletePCompare = 1;
            opener.focus();
            opener.callAjax(args, 'url');
            exit;
        } else
            callAjax(args, 'url');
    } else {
        window.location.href = args;
    }
};
window.setPLocation = function(args) {
    if ((args.search('checkout/cart/add') != -1 || args.search('options=cart') != -1)) {
        if (winFocus) {
            opener.isCPPage = 1;
            opener.focus();
            opener.callAjax(args, 'url');
        } else {
            callAjax(args, 'url');
        }
    } else if (ajax_for_wishlist && args.search('wishlist/index/add') != -1) {
        if (opener.isLoggedIn != '0') {
            if (winFocus) {
                opener.isCPPage = 1;
                opener.focus();
                opener.callAjax(args, 'url');
            } else {
                callAjax(args, 'url');
            }
        } else {
            opener.focus();
            opener.location.href = args;
        }
    } else {
        opener.focus();
        opener.location.href = args;
    }
};

function reCreateLink(link, msg, hasConfirm, skipAny) {
    var _links = document.links;
    for (var i = 0; i < _links.length; i++) {
        if (_links[i].href.search(link) != -1) {
            if (typeof skipAny != "undefined" && skipAny != "" && _links[i].href.search(skipAny) != -1) {
                continue;
            }
            _links[i].onclick = function(e) {
                e.preventDefault();
                if (typeof hasConfirm == "undefined") {
                    callAjax(this.href, 'url', link);
                } else {
                    if (confirm(msg)) {
                        closeCFirm = 1;
                        callAjax(this.href, 'url', link);
                    }
                }
            };
        }
    }
}

function reNewLinkDeleteInCart() {
    //reCreateLink('checkout/cart/delete', 'Are you sure you would like to remove this item from the shopping cart?', true, skiplink);
}

function reNewLinkRemoveCompare() {
    reCreateLink('catalog/product_compare/remove', 'Are you sure you would like to remove this item from the Comparison List?', true);
}

function reNewLinkClearCompare() {
    reCreateLink('catalog/product_compare/clear', 'Are you sure you would like to remove all products from your comparison?', true);
}

function reNewLinkCompare() {
    reCreateLink('catalog/product_compare/add');
}

function reNewLinkRemoveWishList() {
    reCreateLink('wishlist/index/remove', 'Are you sure you would like to remove this item from the your Wish List?', true);
}

function reNewLinkClearAllInWishList(){ 
	reCreateLink('ajaxcart/wishlist/clearall', 'Are you sure you would like to remove all item from the your Wish List?', true);
}

function reNewLinkWishList() {
    reCreateLink('wishlist/index/add');
}

/* function reNewLinkLogin() {
    reCreateLink('customer/account/login');
}

function reNewLinkRegister() {
    reCreateLink('customer/account/create');
}

function reNewLinkForgotpassword() {
    reCreateLink('customer/account/forgotpassword');
} */

function reNewLinkAddToCartInWishList() {
    reCreateLink('wishlist/index/cart');
}

function fixPosWrap() {
    var fixTop = (parseInt(window.innerHeight / 2) - parseInt($('sns_ajaxbox').getHeight() / 2)) + 'px';
    $('sns_ajaxinner').setStyle({
        top: fixTop
    });
}

function renderAjaxWrap() {
    str = '		<div id="sns_ajaxwrap" class="modal">';
    str = str + '		<div id="sns_ajaxinner" style="top:0px;" class="modal-dialog">';
    str = str + '			<div id="sns_ajaxbox" class="modal-content">';
    str = str + '					<div id="ajax_process" style="display:block;"></div>';
    str = str + '					<div id="ajax_content">';
    str = str + '<div class="modal-header"><button aria-label="Close" data-dismiss="modal" class="close" type="button" onclick="closeAll()"><span aria-hidden="true">&times;</span></button><h4 id="myModalLabel" class="modal-title uppercase">Login Form</h4></div><div id="confirmbox"></div>';
    str = str + '					</div>';
    str = str + '			</div>';
    str = str + '		</div><div id="ajax_overlay"></div>';
    str = str + '	</div>';
    str = str + '	';
    return str;
}

function closeAll() {
    $('confirmbox').innerHTML = '';
    $('sns_ajaxwrap').setStyle({
        display: 'none'
    });
    $('ajax_overlay').setStyle({
        display: 'none'
    });
}

function snsAjaxCart() {
    aCConstruct();
    $$('body')[0].insert({
        top: renderAjaxWrap()
    });
    setInterval("aCConstruct()", 1000);
}

function callAjax(url, action, link) {
    if (action == "url") {
        overlaywrap = (closeCFirm == 1) ? 'close' : 'show';
		if(isSecureUrl==1){
			url = url.replace("http://", "https://");
		}else if(isSecureUrl==0){
			url = url.replace("https://", "http://");
		}
        new Ajax.Request(url, {
            encoding: 'UTF-8',
            method: 'post',
            parameters: {
                isCOPage: isCOPage,
                isWLPage: isWLPage,
				isLoggedIn: isLoggedIn
            },
            onSuccess: function(result) {
                if (link == 'checkout/cart/delete') {
                    window.location.reload();
                } else {
                    setResult(result.responseText.evalJSON());
                }
            },
            onLoading: function() {
                $("ajax_content").setStyle({
                    display: "none"
                });
                $("ajax_process").innerHTML = loader;
                $("ajax_process").setStyle({
                    display: "block"
                });
                $("sns_ajaxwrap").setStyle({
                    display: "block"
                });
                $("ajax_overlay").setStyle({
                    display: "block"
                });
                fixPosWrap();
            },
            onFailure: function(msg) {
                Element.setInnerHTML(display, msg.responseText);
            },
            onComplete: function() {
                $("ajax_process").setStyle({
                    display: "none"
                });
                if (overlaywrap == 'close') $("ajax_overlay").setStyle({
                    display: "none"
                });
            }
        });
    } else {
        $('product_addtocart_form').request({
            encoding: 'UTF-8',
            method: 'post',
            parameters: {
                isCOPage: isCOPage
            },
            onLoading: function() {
                $("ajax_process").innerHTML = loader;
                $("ajax_process").setStyle({
                    display: "block"
                })
                $("ajax_content").setStyle({
                    display: "none"
                });
                $("sns_ajaxwrap").setStyle({
                    display: "block"
                });
                $("ajax_overlay").setStyle({
                    display: "block"
                });
                fixPosWrap();
            },
            onComplete: function(result) {
                $("ajax_process").setStyle({
                    display: "none"
                });
                setResult(result.responseText.evalJSON());
            }
        });
    }
}

function setResult(result) {
    if (result.addtype == '0') {
        if (!result.options) {
            if (isCOPage == '1') {
                setCartCO(result);
                $("sns_ajaxwrap").setStyle({
                    display: "none"
                });
                if (closeCFirm != 1)
                    getConfirm(result);
                closeCFirm = 0;
            } else {
                if (result.wishlist) {
                    setWlist(result);
                }
                setCartBlock(result);
				$("sns_ajaxwrap").setStyle({
                    display: "none"
                });
                if (closeCFirm != 1) {
                    getConfirm(result);
                    CANFLY = 1;
                }
                closeCFirm = 0;
            }
            setMiniCart(result);
            reNewLinkDeleteInCart();
            setCOLinks(result);
            setRelated(result);
            if (isLoggedIn == "1") {
                reNewLinkWishList();
            }
            reNewLinkCompare();
        } else {
            getPOptions(result);
            if (result.wishlist) {
                setWlist(result);
            }
			// $("sns_ajaxwrap").setStyle({
                // display: "none"
            // });
            CANFLY = 0;
        }
    } else if (result.addtype == '1') {
        if (isWLPage == '1') {
            setWlist(result);
			$("sns_ajaxwrap").setStyle({
                display: "none"
            });
            if (closeCFirm != 1)
                getConfirm(result);
            closeCFirm = 0;
        } else {
            setBlockWList(result);
			$("sns_ajaxwrap").setStyle({
                display: "none"
            });
            if (closeCFirm != 1)
                getConfirm(result);
            closeCFirm = 0;
        }
    } else if (result.addtype == '2') {
        setBlockCompare(result);
        if (isCPPage && deletePCompare) {
            windown_compare.location.reload();
        }
        if (closeCFirm != 1)
            getConfirm(result);
        deletePCompare = 0;
        closeCFirm = 0;
    
	} else if (result.addtype == '3') {
		if (closeCFirm != 1)
			getConfirm(result);
		closeCFirm = 0;	
		
    }
	else if (result.addtype == '4') {
		if (closeCFirm != 1)
			getConfirm(result);
		closeCFirm = 0;	
		
    }
	else if (result.addtype == '5') {
		if (closeCFirm != 1)
			getConfirm(result);
		closeCFirm = 0;	
		
    }
}

function setCartCO(result) {
    if (typeof($$(".col-main .cart")) != 'undefined') {
        $$(".col-main .cart").each(function(el) {
            el.innerHTML = result.co_sb;
        });
    }
}

function setCartBlock(result) {
    if (typeof($$(cart_block)) != 'undefined') {
        $$(cart_block).each(function(blockitem) {
            blockitem.replace(result.co_sb);
        });
        truncPOptions();
    }
}

function setMiniCart(result) {
    if (typeof($$(minicart_block)) != 'undefined') {
        $$(minicart_block).each(function(blockitem) {
            if (result.ajaxcart != "") {
                blockitem.replace(result.ajaxcart);
            }
        });
        truncPOptions();
    }
}

function setFlyToCart(result) {
    if (result.options == 0) {}
}

function setRelated(result) {}

function setBlockWList(result) {
    if (typeof($$(wishlist_block)) != 'undefined') {
        $$(wishlist_block).each(function(el) {
            el.replace(result.co_sb);
        })
    } else if (typeof($$(minicart_block)) != 'undefined') {
        $$(minicart_block).each(function(el) {
            el.insert({
                after: result.co_sb
            });
        });
    }
    reNewLinkAddToCartInWishList();
    reNewLinkRemoveWishList();
    setWLlinks(result);
}

function setCOLinks(result) {
    $$(mycart_link).each(function(el) {
        el.innerHTML = result.links;
    });
}

function setWlist(result) {
    var str = "";
    if (result.wishlist) {
        str = result.wishlist;
    } else {
        str = result.co_sb;
    }
    if (isWLPage == '0') {
        if (typeof($$(wishlist_block)) != 'undefined') {
            $$(wishlist_block).each(function(el) {
                el.replace(str);
            });
        } else if (typeof($$(minicart_block)) != 'undefined') {
            $$(minicart_block).each(function(el) {
                el.insert({
                    after: str
                });
            });
        }
        reNewLinkAddToCartInWishList();
    } else {
        $$(bwish).each(function(el) {
            el.innerHTML = "";
            el.innerHTML = str;
        });
    }
    reNewLinkRemoveWishList();
    setWLlinks(result);
}

function setWLlinks(result) {
    var strwishlink = "";
    if (result.wishlinks) {
        strwishlink = result.wishlinks;
    } else {
        strwishlink = result.links;
    }
    $$('ul.links li a').each(function(el) {
        if (el.href.search('/wishlist/') != -1) {
            el.innerHTML = strwishlink;
        }
    });
}

function setBlockCompare(result) {
    if (typeof($$(compare_block)[0]) != 'undefined') {
        $$(compare_block)[0].replace(result.co_sb);
        reNewLinkRemoveCompare();
        reNewLinkClearCompare();
    }
}

function getPOptions(result) {
    var scripts = result.co_sb.extractScripts();
    for (i = 0; i < scripts.length; i++) {
        if (typeof(scripts[i]) != 'undefined' && i < 2) {
            try {
                eval(scripts[i]);
            } catch (e) {
                console.debug(e);
            }
        } else {
            break;
        }
    }
	var confirmtitle = "Product Options";
    $("myModalLabel").innerHTML= confirmtitle;
	$("confirmbox").innerHTML = result.co_sb.stripScripts();
	$("ajax_overlay").onclick = function(e) {
        $("confirmbox").innerHTML = '';
        $("ajax_content").setStyle({
            display: "none"
        });
        $("sns_ajaxwrap").setStyle({
            display: "none"
        });
        $("ajax_overlay").setStyle({
            display: "none"
        });
    };
    $("ajax_process").setStyle({
        display: "none"
    });
    $("ajax_content").setStyle({
        display: "block"
    });
    $("sns_ajaxwrap").setStyle({
        display: "block"
    });
    fixPosWrap();
    for (i; i < scripts.length; i++) {
        if (typeof(scripts[i]) != 'undefined') {
            try {
                eval(scripts[i]);
            } catch (e) {
                console.debug(e);
            }
        }
    }
    productAddToCartForm = new VarienForm('product_addtocart_form');
    decorateGeneric($$('#product-options-wrapper dl'), ['last']);
    if (typeof productAddToCartForm != 'undefined') {
        productAddToCartForm.submit = function(url) {
            if (this.validator && this.validator.validate()) {
                url = $('product_addtocart_form').action;
                callAjax(url, 'form');
            }
            return false;
        }
    }
}

function truncPOptions() {
    $$('.truncated').each(function(element) {
        Event.observe(element, 'mouseover', function() {
            if (element.down('div.truncated_full_value')) {
                element.down('div.truncated_full_value').addClassName('show')
            }
        });
        Event.observe(element, 'mouseout', function() {
            if (element.down('div.truncated_full_value')) {
                element.down('div.truncated_full_value').removeClassName('show')
            }
        });
    });
}
Product.Downloadable = Class.create();
Product.Downloadable.prototype = {
    config: {},
    initialize: function(config) {
        this.config = config;
        this.reloadPrice();
    },
    reloadPrice: function() {
        var price = 0;
        config = this.config;
        $$('.product-downloadable-link').each(function(elm) {
            if (config[elm.value] && elm.checked) {
                price += parseFloat(config[elm.value]);
            }
        });
        try {
            var _displayZeroPrice = optionsPrice.displayZeroPrice;
            optionsPrice.displayZeroPrice = false;
            optionsPrice.changePrice('downloadable', price);
            optionsPrice.reload();
            optionsPrice.displayZeroPrice = _displayZeroPrice;
        } catch (e) {}
    }
};

function validateDownloadableCallback(elmId, result) {
    var container = $('downloadable-links-list');
    if (result == 'failed') {
        container.removeClassName('validation-passed');
        container.addClassName('validation-failed');
    } else {
        container.removeClassName('validation-failed');
        container.addClassName('validation-passed');
    }
}
Product.Options = Class.create();
Product.Options.prototype = {
    initialize: function(config) {
        this.config = config;
        this.reloadPrice();
    },
    reloadPrice: function() {
        price = new Number();
        config = this.config;
        skipIds = [];
        $$('.product-custom-option').each(function(element) {
            var optionId = 0;
            element.name.sub(/[0-9]+/, function(match) {
                optionId = match[0];
            });
            if (this.config[optionId]) {
                if (element.type == 'checkbox' || element.type == 'radio') {
                    if (element.checked) {
                        if (config[optionId][element.getValue()]) {
                            price += parseFloat(config[optionId][element.getValue()]);
                        }
                    }
                } else if (element.hasClassName('datetime-picker') && !skipIds.include(optionId)) {
                    dateSelected = true;
                    $$('.product-custom-option[id^="options_' + optionId + '"]').each(function(dt) {
                        if (dt.getValue() == '') {
                            dateSelected = false;
                        }
                    });
                    if (dateSelected) {
                        price += parseFloat(this.config[optionId]);
                        skipIds[optionId] = optionId;
                    }
                } else if (element.type == 'select-one' || element.type == 'select-multiple') {
                    if (element.options) {
                        $A(element.options).each(function(selectOption) {
                            if (selectOption.selected) {
                                if (this.config[optionId][selectOption.value]) {
                                    price += parseFloat(this.config[optionId][selectOption.value]);
                                }
                            }
                        });
                    }
                } else {
                    if (element.getValue().strip() != '') {
                        price += parseFloat(this.config[optionId]);
                    }
                }
            }
        });
        try {
            optionsPrice.changePrice('options', price);
            optionsPrice.reload();
        } catch (e) {}
    }
}

function validateOptionsCallback(elmId, result) {
    var container = $(elmId).up('ul.options-list');
    if (result == 'failed') {
        container.removeClassName('validation-passed');
        container.addClassName('validation-failed');
    } else {
        container.removeClassName('validation-failed');
        container.addClassName('validation-passed');
    }
}
miniCartForm.prototype = {
    initialize: function(form, addressUrl, saveUrl) {
        this.form = form;
        if ($(this.form)) {
            $(this.form).observe('submit', function(event) {
                this.callAction();
                Event.stop(event);
            }.bind(this));
        }
        this.addressUrl = addressUrl;
        this.saveUrl = saveUrl;
        this.onSave = this.newCart.bindAsEventListener(this);
        this.onComplete = this.closeAll.bindAsEventListener(this);
        this.onFailure = this.loadFailure.bindAsEventListener(this);
        this.onLoading = this.showLoading.bindAsEventListener(this);
        this.onChanged = false;
    },
    callAction: function() {
        var validator = new Validation(this.form);
        if (validator.validate()) {
            if (ajax_for_update) {
                var request = new Ajax.Request(this.saveUrl, {
                    method: 'post',
                    onComplete: this.onComplete,
                    onSuccess: this.onSave,
                    onLoading: this.onLoading,
                    parameters: Form.serialize(this.form) + '&isCOPage=' + isCOPage,
                });
            } else {
                $(this.form).submit();
            }
        }
    },
    newCart: function(result) {
        closeCFirm = 1;
        setResult(result.responseText.evalJSON());
		if(jQuery('#region_id').length != 0) {
			jQuery('#region_id').trigger("change");
		}
    },
    loadFailure: function() {},
    showLoading: function() {
        $("ajax_content").setStyle({
            display: "none"
        });
        $("ajax_process").innerHTML = loader;
        $("ajax_process").setStyle({
            display: "block"
        });
        $("sns_ajaxwrap").setStyle({
            display: "block"
        });
        $("ajax_overlay").setStyle({
            display: "block"
        });
        fixPosWrap();
    },
    closeAll: function() {
        $("ajax_process").setStyle({
            display: "none"
        });
        $("ajax_overlay").setStyle({
            display: "none"
        });
    }
};

function getCDown(numb) {
    if (numb != 1) {
        try {
            timenumb = numb - 1;
            $("cout_down").innerHTML = timenumb + "s";
            setTimeout("getCDown(timenumb)", 1000);
        } catch (e) {}
    } else {
        $("ajax_overlay").setStyle({
            display: "none"
        });
        $("sns_ajaxwrap").setStyle({
            display: "none"
        });
        $('ajax_content').setStyle({
            display: 'none'
        });
		if(jQuery('#region_id').length != 0) {
			jQuery('#region_id').trigger("change");
		}
        if (isCPPage) {
            isCPPage = 0;
            windown_compare.focus();
        }
        return;
    }
}
(function() {
    document.observe('dom:loaded', snsAjaxCart);
})();
jQuery(document).ready(function($) {
    var imgToFly = '';
    if (usingImgFly == 1) {
        function bindClickAddToCart() {
            $('.btn-cart').each(function() {
                $(this).bind('click', function() {
                    if (!$(this).parents('#confirmbox').length && !$(this).parents('.quickview-main').length) {
                        $('.img-to-fly').remove();
                        if ($('body.catalog-product-view .product-view').length) {
                            if ($(this).parents('.item').find('a.product-image, .img-main').find('img').length) {
                                imgP = $(this).parents('.item').find('a.product-image, .img-main').find('img');
                            } else {
                                imgP = $(this).parents('.product-essential').find('.product-image-zoom').find('img');
                            }
                        } else if($(this).parents('.item').find('a.product-image, .img-main').find('img').length) {
                            imgP = $(this).parents('.item').find('a.product-image, .img-main').find('img');
                        }else{
							imgP = $(this).parents('.custom-item').find('a.product-image, .img-main').find('img');
						}
                        imgToFly = imgP.clone();
                        if ($(this).attr('onclick').indexOf("?options=cart") == -1 && CANFLY == 1) {
                            flyNow();
                        }
                    }
                });
            });
        }

        function canfly() {
            if (CANFLY == 1 && imgToFly !== '') {
                flyNow();
            }
        }
        setInterval(function() {
            bindClickAddToCart()
        }, 1000);
        setInterval(function() {
            canfly()
        }, 1000);

        function flyNow() {
            imgToFly.offset({
                top: imgP.offset().top,
                left: imgP.offset().left
            }).addClass('img-to-fly').css({
                'opacity': '0.8',
                'position': 'absolute',
                'height': '180px',
                'width': '180px',
                'z-index': '1000'
            }).appendTo($('body')).animate({
                'top': $('.mini-cart').offset().top + 10,
                'left': $('.mini-cart').offset().left + 10,
                'width': 50,
                'height': 50
            }, 1000, 'easeInOutExpo');
            if (scrollToCart == 1) {
                $('body,html').animate({
                    scrollTop: $('.mini-cart').offset().top
                }, 1000, 'easeInOutExpo');
            }
            imgToFly.animate({
                'width': 0,
                'height': 0
            });
            imgToFly = '';
            CANFLY = 0;
        }
    }
});
