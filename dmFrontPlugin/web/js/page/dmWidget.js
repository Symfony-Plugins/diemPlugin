(function($) {
  
$.widget('ui.dmWidget', {

  _init : function()
  {
    this.initialize();
    
    this.element.addClass('loaded');
  },
  
  openEditDialog: function()
  {
    var widget = this;
    
    var $dialog = $.dm.ctrl.ajaxDialog({
      url:          $.dm.ctrl.getHref('+/dmWidget/edit'),
      data:         { widget_id: widget.getId() },
      title:        $('a.dm_widget_edit', widget.element).attr('title'),
      width:        370,
      beforeclose:  function() {
        if (widget.deleted) return;
        $.ajax({
          url:      $.dm.ctrl.getHref('+/dmWidget/getInner'),
          data:     { widget_id: widget.getId() },
          success:  function(data) {
            var dataParts = data.split('\_\_DM\_SPLIT\_\_');
            widget.element.attr('class', 'dm_widget loaded '+ dataParts[1]);
            $('div.dm_widget_inner', widget.element).html(dataParts[0]);
          }
        });
      }
    }).bind('dmAjaxResponse', function() {
      $dialog.prepare();
			var $form = $('div.dm_widget_edit', $dialog);
			if ($form.length)
			{
				/*
				 * Apply generic front form abilities
				 */
				$form.dmFrontForm();
	      /*
	       * Apply specific widget form abilities
	       */
	      if ((formClass = $form.metadata().form_class) && $.isFunction($form[formClass]))
	      {
	        $form[formClass](widget);
	      }
	      $form.find('form').dmAjaxForm({
	        beforeSubmit: function(data) {
	          $dialog.block();
	          widget.element.block();
	        },
	        success:  function(data) {
	          if (data == 'ok') {
	            $dialog.dialog('close');
	            widget.element.unblock();
							return;
	          }
	          if (data.indexOf('\_\_DM\_SPLIT\_\_') != -1) {
	            dataParts = data.split('\_\_DM\_SPLIT\_\_');
	            widget.element.attr('class', 'dm_widget loaded '+ dataParts[2]);
	            $('div.dm_widget_inner', widget.element).html(dataParts[1]);
	            formHtml = dataParts[0];
	          }
	          else {
	            formHtml = data;
	          }
	          widget.element.unblock();
	          $dialog.html(formHtml).trigger('dmAjaxResponse');
	        }
	      });
			}
      $('a.delete', $dialog).click(function() {
        if (confirm($(this).attr('title')+" ?")) {
          $dialog.dialog('close');
          widget.delete();
        }
      });
    });
  },
  
  delete: function()
  {
    var widget = this;
    this.deleted = true;
    
    $.ajax({
      url:      $.dm.ctrl.getHref('+/dmWidget/delete'),
      data:     { widget_id: this.getId() }
    });
    
    this.element.slideUp(500, function() { widget.destroy(); widget.element.remove(); });
  },
  
  initialize: function()
  {
    var widget = this;
    
    this.id = this.element.attr('id').substring(10);
    
    $('a.dm_widget_edit', this.element).click(function() {
      if (widget.element.hasClass('dm_dragging')) {
        return false;
      }
      widget.openEditDialog();
    });
  },
  
  getId: function()
  {
    return this.id;
  }

});

$.extend($.ui.dmWidget, {
  getter: "getId openEditDialog"
});

})(jQuery);