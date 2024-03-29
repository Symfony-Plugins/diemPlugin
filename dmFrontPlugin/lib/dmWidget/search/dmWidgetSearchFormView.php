<?php

class dmWidgetSearchFormView extends dmWidgetPluginView
{
  protected
  $isIndexable = false;

  protected function filterViewVars(array $vars = array())
  {
    $vars = parent::filterViewVars($vars);
    
    $vars['form'] = new mySearchForm();
    
    if ($searchRequestParameter = $this->getService('request')->getParameter($vars['form']->getName()))
    {
      $vars['form']->setDefault('query', dmArray::get($searchRequestParameter, 'query'));
    }
    
    return $vars;
  }

}