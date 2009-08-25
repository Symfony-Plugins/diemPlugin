(function($)
{

  $.widget('ui.dmCoreTabForm', {
    _init: function()
    {
      this.launchTabs();
      this.markErrorTabs();
      this.openFirstErrorTab();
    },
    
    launchTabs: function()
    {
      this.element.tabs(self.options.tabs);
    },
    
    markErrorTabs: function()
    {
      var self = this;
      
      self.element.find('>ul.ui-tabs-nav a').each(function()
      {
        if (self.element.find('>div.ui-tabs-panel' + $(this).attr('href') + ' ul.error_list').length) 
        {
          $(this).parent().addClass('dm_error');
        }
      });
    },
    
    openFirstErrorTab: function()
    {
      if ($errorTabLink = this.element.find('>ul.ui-tabs-nav li.dm_error:first a').orNot()) 
      {
        this.element.tabs('select', $errorTabLink.attr('href'));
      }
    }
    
  });
  
  $.extend($.ui.dmCoreTabForm, {
    defaults: {
      tabs: {} // options for tabs
    }
  });
  
})(jQuery);
