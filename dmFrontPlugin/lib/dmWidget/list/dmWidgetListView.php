<?php

class dmWidgetListView extends dmWidgetProjectModelView
{

  public function configure()
  {
    parent::configure();

    $this->addRequiredVar(array('maxPerPage', 'orderField', 'orderType'));

    foreach($this->dmAction->getParam('filters', array()) as $filter)
    {
      if ($filterModule = $this->dmModule->getAncestor($filter))
      {
        if (!$this->allowFilterAutoRecordId($filterModule))
        {
          $this->addRequiredVar($filter);
        }
      }
    }
    
    $this->isIndexable = !$this->dmModule->hasPage();
  }

  /*
   * Will put filters in an array
   * @return array viewVars
   */
  protected function filterViewVars(array $vars = array())
  {
    $viewVars = parent::filterViewVars($vars);

    $filters = array();
    foreach($viewVars as $key => $val)
    {
      if (strncmp($key, 'filter', 6) === 0)
      {
        $filters[dmString::modulize(preg_replace('|^filter(.+)$|', '$1', $key))] = $val;
        unset($viewVars[$key]);
      }
    }

    $viewVars['filters'] = $filters;

    return $viewVars;
  }

  protected function allowFilterAutoRecordId(dmModule $filterModule)
  {
    return $this->context->getPage()->getDmModule()->knows($filterModule);
  }
}