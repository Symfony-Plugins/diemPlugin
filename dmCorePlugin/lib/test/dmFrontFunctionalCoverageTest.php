<?php

class dmFrontFunctionalCoverageTest extends dmCoreFunctionalCoverageTest
{
  
  protected function configure()
  {
    parent::configure();
    
    if (empty($this->options['app']))
    {
      $this->options['app'] = 'front';
    }
  }
  
  protected function execute()
  {
    foreach($this->getPages() as $page)
    {
      if ($page->isModuleAction('main', 'error404'))
      {
        $expectedStatusCode = 404;
      }
      elseif($page->isModuleAction('main', 'login'))
      {
        $expectedStatusCode = 401;
      }
      else
      {
        $expectedStatusCode = 200;
      }
      
      $this->testUrl('/'.$page->slug, $expectedStatusCode);
    }
  }
  
  protected function getPages()
  {
    return dmDb::query('DmPage p')
    ->withI18n()
    ->fetchRecords();
  }
}