(function($, document, undefined) {

  // Set Up Overlay Options
  var overlayOpts = {target: '.overlay'},
      overlayEffects = {};

  if (!$.browser.msie || parseInt($.browser.version, 10) >= 9) {
    overlayEffects = {
      expose: {
        color: '#777',
        loadSpeed: 100,
        opacity: 0.5,
        closeSpeed: 0
      },
      speed: 100
    };
  }
  $.extend(overlayOpts, overlayEffects);

  $(document).ready(function() {

    // Delete Action
    $('a.action-delete').click(function() {
      return confirm('Do you want to delete this page from Structure? It will be removed from navigation and its entry status set to "Closed."');
    });

    // Add action
    $('div.rightNav').find('a[title^="Add"]').addClass("action-add");

	if (structure_settings.show_picker == 'y') {
	    $('a.action-add')
	    .overlay(overlayOpts)
	    .bind('click', function(event) {
	      event.preventDefault();
	      var $thisAdd = $(this),
	          // as of jQuery 1.4.4, .data() method can read HTML5 data-* attributes
	          parentId = $thisAdd.data('parent_id') || $thisAdd.attr('data-parent_id') || '0';

	      $('#overlay_listing').find('li a').each(function(){
	        var $link = $(this),
	            href = $link.attr('href') + '&parent_id=' + parentId;

	        $link.attr('href', href);
	      });
	    });
	}
	
	// View Page Dynamic Link
	$('a.action-view').bind('click', function(event) {
		event.preventDefault();
		postURL = EE.BASE + "&C=addons_modules&M=show_module_cp&module=structure&method=ajax_link";
		eid = $(this).data('entry_id') || $(this).attr('data-entry_id');
		
		$.post(postURL, {entry_id: eid, XID: structure_settings.xid}, function(data) {
			window.location.href=data;
			return false;
		});
	});

    // Nested Structure drag 'n drop
    if ( typeof $.fn.nestedStructure !== 'undefined' ) {

      var sid = 'page-ui',
          postURL = EE.BASE + "&C=addons_modules&M=show_module_cp&module=structure&method=ajax_reorder&site_id=" + structure_settings.site_id,
          XID = structure_settings.xid,
          $pageUi = $('#' + sid),
          nestedOpts = {
            forcePlaceholderSize: true,
            handle: '.sort-handle',
            items: 'li',
            opacity: 1,
            placeholder: 'placeholder',
            tabSize: 10,
            tolerance: 'pointer',
            toleranceElement: '> div',
            // containment: '#mainWrapper',
            stop: function() {

              var reorder = $pageUi.nestedStructure('toNested', $pageUi);
              reorder.XID = XID;

              $.post(postURL, reorder);

            }
          };

      $pageUi.nestedStructure(nestedOpts);
    }

  });

})(jQuery, document);

;(function($){
	$.fn.blinds = function(opts){
		
		var opts = $.extend({}, $.fn.blinds.defaults, opts);
		
		return this.each(function(){
			var $e = $(this),
			collapsible = $e.find("li > ul").parent(); 
			
			//Maps currently collapsed li's with a class of 'closed'
			Structure.collapsed = $.map($('.closed'), function(e, i){
				$(e).attr("id");
			}); 

			$(collapsible).each(function(){
				$("<a/>", {
					"href": "#", 
					"class": "collapsible",
					"text": "-",
					"click": function(e){
						$("> ul", $(this).parent()).stop(true, true).slideToggle(function(e){
							
							var list_item = $(this).parent(),
							anchor = list_item.find("a.collapsible:first"),
							page_id = list_item.attr("id"),
							txt = (anchor.text() === "-") ? "+": "-";

							//Set the text (Could also be a class)
							anchor.text(txt);
							var arr_position = $.inArray(page_id, Structure.collapsed); 
							//Check if it's in 
							if (arr_position > -1) {
								Structure.collapsed.pop(arr_position);
							} else {
								Structure.collapsed.unshift(page_id); 
							}
							
							$.post(opts.url, {'collapsed[]' : Structure.collapsed, XID : structure_settings.xid}); 
						});
						
						e.preventDefault();
					}
				}).prependTo(this); 
			});
			
			$('ul.closed').parent().find(".collapsible").text("+");
		}); 
	};
	
	//Default options
	$.fn.blinds.defaults = {
		'url' : 'http://www.cnn.com'
	};
	

})(jQuery);

$(function(){
	// Needs live binding
	// Structure = {};
	// $("ul#page-ui").blinds({'url': EE.BASE + "&C=addons_modules&M=show_module_cp&module=structure&method=ajax_collapse&site_id=" + structure_settings.site_id,});
});

$(function(){
	$('.hidden').hide();
	$(".type-picker select").change(function() {
		if ($(this).val() == 'asset') {
			$(this).parents('td').find('.hidden').fadeIn('fast');
		}
		else
		{
			$(this).parents('td').find('.assets-split').fadeOut('fast');
		}
	});
});