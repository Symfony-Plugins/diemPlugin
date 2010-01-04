<?php

class dmFrontDoctrinePager extends dmDoctrinePager
{
  protected
  $navigationConfiguration = array(
    'top' => true,
    'bottom' => true
  ),
  $navigationCache = array(),
  $context;

  public function configureNavigation(array $navigationConfiguration)
  {
    $this->navigationConfiguration = $navigationConfiguration;
  }

  public function setContext(dmContext $context)
  {
    $this->context = $context;
  }
  
  public function renderNavigationTop($options = array())
  {
    if ($this->navigationConfiguration['top'])
    {
      return $this->renderNavigation($options);
    }
  }
  
  public function renderNavigationBottom($options = array())
  {
    if ($this->navigationConfiguration['bottom'])
    {
      return $this->renderNavigation($options);
    }
  }

  protected function getNavigationDefaults()
  {
    return array(
      'separator'       => null,
      'class'           => null,
      'currentClass'    => 'current',
      'first'           => "&lt;&lt;",
      'prev'            => "&lt;",
      'next'            => "&gt;",
      'last'            => "&gt;&gt;",
      'nbLinks'         => 9,
      'uri'             => $this->context->getPage()
      ? $this->context->getHelper()->£link($this->context->getPage())->getAbsoluteHref()
      : $this->context->getRequest()->getUri()
    );
  }

  public function renderNavigation($options = array())
  {
    if (!$this->haveToPaginate())
    {
      return '';
    }

    $options = dmString::toArray($options);

    $hash = md5(var_export($options, true));

    if (isset($this->navigationCache[$hash]))
    {
      return $this->navigationCache[$hash];
    }

    $options = array_merge($this->getNavigationDefaults(), $options);

    $options['uri'] = preg_replace("|/page/([0-9]+)|", "?page=$1", $options['uri']);

    $helper = $this->context->getHelper();

    $html = $helper->£o('div.pager'.(!empty($options['class']) ? '.'.implode('.', $options['class']) : ''));

    $html .= $helper->£o('ul.clearfix');

    // First and previous page
    if ($this->getPage() != 1)
    {
      if($options['first'])
      {
        $html .= $helper->£("li.page.first", $helper->£link($options['uri'])->param('page', $this->getFirstPage())->text($options['first']));
      }

      if($options['prev'])
      {
        $html .= $helper->£("li.page.prev", $helper->£link($options['uri'])->param('page', $this->getPreviousPage())->text($options['prev']));
      }
    }

    // Pages one by one
    $links = array();
    foreach ($this->getLinks($options['nbLinks']) as $page)
    {
      // current page
      if($page == $this->getPage())
      {
        $links[] = $helper->£("li.page.".$options['currentClass'], $helper->£('span.link', $page));
      }
      else
      {
        $links[] = $helper->£("li.page", $helper->£link($options['uri'])->param('page', $page)->text($page));
      }
    }

    $html .= join($options['separator'] ? '<li>'.$separateur.'</li>' : '', $links);

    // Next and last page
    if ($this->getPage() != $this->getCurrentMaxLink())
    {
      if($options['next'])
      {
        $html .= $helper->£("li.page.next", $helper->£link($options['uri'])->param('page', $this->getNextPage())->text($options['next']));
      }
      if($options['last'])
      {
        $html .= $helper->£("li.page.last", $helper->£link($options['uri'])->param('page', $this->getLastPage())->text($options['last']));
      }
    }

    $html .= '</ul></div>';

    $html = preg_replace("|\?page=([0-9]+)|", "/page/$1", $html);

    $this->navigationCache[$hash] = $html;

    return $html;
  }

}