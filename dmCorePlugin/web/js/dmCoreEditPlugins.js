(function($)
{
  $.dm.loadedJavascripts = new Array();
  $.dm.loadedStylesheets = new Array();

  $.fn.extend({
  
    maxLength: function(max)
    {
      var $elem = this.addClass('dm_max_length'), $helper = $('<span class="dm_max_length_helper">').insertAfter($elem);
      
      $helper.bind('change.maxLength', function()
      {
        var freeChars = max - $elem.val().length;
        $helper.text(freeChars)[freeChars > 0 ? 'removeClass' : 'addClass']('max_length_alert');
      }).trigger('change.maxLength');
      
      $elem.bind('keyup click', function()
      {
        $helper.trigger('change.maxLength');
      });
    },
    
    outClick: function(callback)
    {
      callback = callback || null;
      
      if (callback == 'remove') 
      {
        return $(document).unbind('mousedown.outClick');
      }
      var $elem = this;
      $(document).bind('mousedown.outClick', function(e)
      {
        var a = $elem.offset(), b = {
          top: a.top + $elem.outerHeight(),
          left: a.left + $elem.outerWidth()
        };
        if (e.pageY < a.top || e.pageY > b.top || e.pageX < a.left || e.pageX > b.left) 
        {
          $(document).unbind('mousedown.outClick');
          if ($.isFunction(callback)) 
          {
            callback.apply($elem);
          }
          else 
          {
            $elem.hide();
          }
        }
      });
    },
    
    dmAjaxForm: function(options)
    {
      return this.ajaxForm($.extend({
        data: $.dm.defaults.ajaxData
      }, options));
    },

    // Detects javascripts and stylesheet inclusions and append them to the document
    dmExtractEncodedAssets: function()
    {
      if($encodedAssetsDiv = this.find('div.dm_encoded_assets').orNot())
      {
        json = $encodedAssetsDiv.html();
        $encodedAssetsDiv.remove();

        // Try to use the native JSON parser first
        if ( window.JSON && window.JSON.parse )
        {
          data = window.JSON.parse( json );
        }
        else
        {
          data = (new Function("return " + json))();
        }

        for (i in data.css)
        {
          if (-1 == $.inArray(data.css[i], $.dm.loadedStylesheets))
          {
            $('head').append('<link rel="stylesheet" href="' + data.css[i] + '" />');
            $.dm.loadedStylesheets.push(data.css[i]);
          }
        }

        for (var i in data.js)
        {
          if (-1 == $.inArray(data.js[i], $.dm.loadedJavascripts))
          {
            ajaxDefaultData = $.ajaxSettings.data;
            $.ajaxSettings.data = null;
            
            $.ajax({
              url:      data.js[i],
              dataType: 'script',
              cache:    true,
              async:    false
            });

            $.ajaxSettings.data = ajaxDefaultData;

            $.dm.loadedJavascripts.push(data.js[i]);
          }
        }
      }

      return this;
    }
  });
  
  $.widget('ui.dmMenu', {

    options: {
      hoverClass: 'hover'
    },
  
    _init: function()
    {
      var menu = this;
      
      menu.$tabs = $('> ul > li', menu.element);
      
      $('> a', menu.$tabs).click(function()
      {
        if (!menu.lock) 
        {
          menu.open($(this).parent());
        }
      });
    },
    
    open: function($tab)
    {
      var menu = this;
      
      $tab.addClass(this.options.hoverClass).find('> ul').outClick(function()
      {
        menu.close();
        menu.lock = true;
        $(document).bind('mouseup', function()
        {
          setTimeout(function()
          {
            menu.lock = false;
          }, 50);
        });
      });
      
      menu.$tabs.not($tab).bind('mouseover', function()
      {
        menu.close().open($(this));
      });
    },
    
    close: function()
    {
      this.lock = false;
      this.$tabs.unbind('mouseover').filter('li.' + this.options.hoverClass).removeClass(this.options.hoverClass).outClick('remove');
      return this;
    }
    
  });
  
  $.ui.dmMenu.getter = 'close';
	
	/*
	 * Make ui dialogs fixed
	 */
	$(function() {
		if ($.ui.dialog) 
		{
			$.ui.dialog.prototype._position = function(pos)
			{
				var wnd = $(window), pTop = 0, pLeft = 0, minTop = pTop;
				
				if ($.inArray(pos, ['center', 'top', 'right', 'bottom', 'left']) >= 0) 
				{
					pos = [pos == 'right' || pos == 'left' ? pos : 'center', pos == 'top' || pos == 'bottom' ? pos : 'middle'];
				}
				
				if (pos.constructor != Array && pos.constructor != Object) 
				{
					pos = ['center', 'middle'];
				}
				
				if (pos[0].constructor == Number) 
				{
					pLeft += pos[0];
				}
				else 
				{
					pLeft += (wnd.width() - this.uiDialog.outerWidth()) / 2;
				}
				
				if (pos[1].constructor == Number) 
				{
					pTop += pos[1];
				}
				else 
				{
					var dialogHeight = 350;
					pTop += (wnd.height() - dialogHeight) / 2;
				}
				
				// prevent the dialog from being too high (make sure the titlebar is accessible)
				pTop = Math.max(pTop, minTop);
				this.uiDialog.css({
					position: 'fixed',
					top: pTop,
					left: pLeft
				});
			};
		}
  });

})(jQuery);