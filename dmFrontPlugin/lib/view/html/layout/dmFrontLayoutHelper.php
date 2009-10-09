<?php

class dmFrontLayoutHelper extends dmCoreLayoutHelper
{
  protected
    $page;

  public function connect()
  {
    $this->dispatcher->connect('dm.context.change_page', array($this, 'listenToChangePageEvent'));
  }
  
  /**
   * Listens to the user.change_culture event.
   *
   * @param sfEvent An sfEvent instance
   */
  public function listenToChangePageEvent(sfEvent $event)
  {
    $this->setPage($event['page']);
  }
  
  public function setPage(DmPage $page)
  {
    $this->page = $page;
  }
  
  public function renderBrowserStylesheets()
  {
    $html = '';

    // search in theme_dir/css/browser/ieX.css
    foreach(array(6, 7, 8) as $ieVersion)
    {
      if (file_exists($this->theme->getFullPath('css/browser/msie'.$ieVersion.'.css')))
      {
        $html .= "\n".sprintf('<!--[if IE %d]><link href="%s" rel="stylesheet" type="text/css" /><![endif]-->',
          $ieVersion,
          $this->theme->getWebPath('css/browser/msie'.$ieVersion.'.css')
        );
      }
    }

    return $html;
  }


  public function renderIeHtml5Fix()
  {
    return '<!--[if IE]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->';
  }
  
  public function renderBodyTag()
  {
    $bodyClass = dmArray::toHtmlCssClasses(array(
      $this->page->get('module').'_'.$this->page->get('action'),
      $this->page->getPageView()->get('Layout')->get('css_class')
    ));
    
    return '<body class="'.$bodyClass.'">';
  }

  protected function getMetas()
  {
    $metas = array(
      'description'  => $this->page->get('description'),
      'language'     => $this->user->getCulture(),
      'generator'    => 'Diem '.dm::version()
    );
    
    if (sfConfig::get('dm_seo_use_keywords') && $keywords = $this->page->get('keywords'))
    {
      $metas['keywords'] = $keywords;
    }
    
    if (!dmConfig::get('site_indexable') || !$this->page->get('is_indexable'))
    {
      $metas['robots'] = 'noindex, nofollow';
    }
    
    if (dmConfig::get('gwt_key') && $this->page->getNode()->isRoot())
    {
      $metas['verify-v1'] = dmConfig::get('gwt_key');
    }
    
    return $metas;
  }
  
  public function renderMetas()
  {
    $metaHtml = "\n".'<title>'.$this->page->get('title').'</title>';
    
    foreach($this->getMetas() as $key => $value)
    {
      $metaHtml .= "\n".'<meta name="'.$key.'" content="'.$value.'" />';
    }

    return $metaHtml;
  }
  
  
  public function renderEditBars()
  {
    if (!$this->user->can('admin'))
    {
      return '';
    }
    
    $cacheKey = sfConfig::get('sf_cache') ? $this->user->getCredentialsHash() : null;
    
    $html = '';
    
    if (sfConfig::get('dm_pageBar_enabled', true) && $this->user->can('page_bar_front'))
    {
      $html .= $this->helper->renderPartial('dmInterface', 'pageBar', array('cacheKey' => $cacheKey));
    }
    
    if (sfConfig::get('dm_mediaBar_enabled', true) && $this->user->can('media_bar_front'))
    {
      $html .= $this->helper->renderPartial('dmInterface', 'mediaBar', array('cacheKey' => $cacheKey));
    }
    
    if ($this->user->can('tool_bar_front'))
    {
      $html .= $this->helper->renderComponent('dmInterface', 'toolBar', array('cacheKey' => $cacheKey));
    }
    
    return $html;
  }

  public function getJavascriptConfig()
  {
    return array_merge(parent::getJavascriptConfig(), array(
      'page_id' => $this->page->get('id')
    ));
  }
  
  public function renderGoogleAnalytics()
  {
    if (dmConfig::get('ga_key') && !$this->user->can('admin') && !dmOs::isLocalhost())
    {
      return str_replace("\n", ' ', '<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("'.dmConfig::get('ga_key').'");
pageTracker._trackPageview();
} catch(err) {}</script>');
    }
    
    return '';
  }
}