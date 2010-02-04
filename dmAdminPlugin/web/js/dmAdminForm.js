(function($)
{

  $.widget('ui.dmAdminForm', {
  
    _init: function()
    {
      this.$ = $("#dm_admin_content");
      
      this.focusFirstInput();
      this.markdown();
      this.checkBoxList();
      this.droppableInput();
      this.hotKeys();
    },

    droppableInput: function()
    {
      $('input.dm_link_droppable, .dm_link_droppable input', this.element).dmDroppableInput();
    },
    
    focusFirstInput: function()
    {
      if ($firstInput = $('div.sf_admin_form_row_inner input:first', this.$)) 
      {
        $firstInput.focus();
      }
    },
    
    hotKeys: function()
    {
      if ($save = $('li.sf_admin_action_save:first input', this.$).orNot()) 
      {
        var self = this;

        setTimeout(function()
        {
          self.$.bindKey('Ctrl+s', function()
          {
            $save.trigger('click');
            return false;
          });
        }, 1000);
      }
    },
    
    markdown: function()
    {
      var form = this;
      
      $('textarea.dm_markdown', form.element).each(function()
      {
        var $editor = $(this);
        var $preview = $('#dm_markdown_preview_'+$editor.metadata().code);
        var value = $editor.val();
				
				$editor.dmMarkdown();

        var $container = $editor.closest('div.markItUpContainer');

        $editor.bind('scroll', function()
        {
          if($editor.scrollTop() == 0)
          {
            $preview.scrollTop(0);
          }
          else if($editor.scrollTop()+$editor.height() == $editor[0].scrollHeight)
          {
            $preview.scrollTop($preview[0].scrollHeight - $preview.height());
          }
        });

        $container.find('div.markItUpHeader ul').append(
          $('<li class="markitup_full_screen"><a title="Full Screen">Full Screen</a></li>')
          .click(function() {
            $container.toggleClass('dm_markdown_full_screen');

            if($container.hasClass('dm_markdown_full_screen'))
            {
              $editor
              .data('old_height', $editor.height())
              .height($(window).height()-90)
              .parent().height($(window).height()-84);

              $preview.height($container.innerHeight() - 20);
              
              window.scrollTo(0, Math.round($container.offset().top) - 40);
            }
            else
            {
              $editor
              .height($editor.data('old_height'))
              .parent().height($editor.data('old_height')+6);

              $preview.height($container.innerHeight() - 20);
            }
          })
        );
				
        setInterval(function()
        {
          if ($editor.val() != value) 
          {
            value = $editor.val();
            $.ajax({
              type: "POST",
              mode: "abort",
              url: $.dm.ctrl.getHref('+/dmCore/markdown')+"?dm_nolog=1",
              data: {
                text: value
              },
              success: function(html)
              {
                $preview.html(html);
              }
            });
          }
        }, 500);


        $preview.height($container.innerHeight() - 13);

        $editor.resizable({
          alsoResize: $preview,
          handles: 's'
        }).width($container.width()-6);
      });
    },
    
    checkBoxList: function()
    {
      var $list = $('ul.checkbox_list', this.element);
      
      $('> li > label, > li > input', $list).click(function(e)
      {
        e.stopPropagation();
      });
      
      $('> li', $list).click(function()
      {
        var $input = $('> input', $(this));
        $input.attr('checked', !$input.attr('checked')).trigger('change');
      });
      
      $('> li > input', $list).change(function()
      {
        $(this).parent()[($(this).attr('checked') ? 'add' : 'remove') + 'Class']('active');
        return true;
      }).trigger('change');
      
      $('div.control span.select_all, div.control span.unselect_all', $list.parent().parent()).each(function()
      {
        $(this).click(function()
        {
          $(this).closest('div.sf_admin_form_row_inner').find('input:checkbox').attr('checked', $(this).hasClass('select_all')).trigger('change');
        });
      });
    }
    
  });
  
})(jQuery);