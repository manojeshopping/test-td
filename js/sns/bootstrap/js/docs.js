'use strict';
jQuery(function() {
  jQuery.ajax({
    url: 'https://api.github.com/repos/vsn4ik/bootstrap-submenu',
    success: function(data) {
      // XSS check
      if (typeof data.stargazers_count != 'number') {
        return;
      }

      var $group = jQuery('<div class="input-group"><span class="input-group-btn"></span></div>');

      $group.append('<span class="input-group-addon">' + data.stargazers_count + '&nbsp;<span class="octicon octicon-star"></span></span>');

      jQuery('#gh-view-link').wrap($group);
    }
  });

  jQuery('#scroll_top').click(function() {
    this.disabled = true;

    // 'html' for Mozilla Firefox, 'body' for other browsers
    jQuery('body, html').animate({
      scrollTop: 0
    }, 800, function() {
      this.disabled = false;
    }.bind(this));

    this.blur();
  });

  // Dropdown fix
  jQuery('.dropdown > a[tabindex]').keydown(function(event) {
    // 13: Return

    if (event.keyCode == 13) {
      jQuery(this).dropdown('toggle');
    }
  });

  // Предотвращаем закрытие при клике на неактивный элемент списка
  jQuery('.dropdown-menu > .disabled, .dropdown-header').on('click.bs.dropdown.data-api', function(event) {
    event.stopPropagation();
  });

  jQuery('.dropdown-submenu > a').submenupicker();
  
  jQuery('#myAffix').affix({
	  offset: {
		top: 0,
		// bottom: function () {
		  // return (this.bottom = jQuery('.footer').outerHeight(true))
		// }
	  }
  })
  
	jQuery('[data-toggle="offcanvas"]').click(function () {
		jQuery('.row-offcanvas').toggleClass('active')
	});
	

	jQuery('.attributes-toggle i').parent().click(function () {
		if(jQuery('.attributes-toggle i').hasClass('fa-chevron-right'))
		{
			jQuery('.attributes-toggle').html('<i class="fa fa-chevron-left"></i>');
			jQuery('.attributes-toggle').addClass('active'); 
		}
	else
		{      
			jQuery('.attributes-toggle').html('<i class="fa fa-chevron-right"></i>'); 
			jQuery('.attributes-toggle').removeClass('active'); 
		}
	});


  
});