<?php

class dmRequestLog extends dmFileLog
{
  protected function getDefaultOptions()
  {
    return array_merge(parent::getDefaultOptions(), array(
      'name'                => 'Requests',
      'file'                => 'data/dm/log/request.log',
      'entry_service_name'  => 'request_log_entry'
    ));
  }
  
  public function connect()
  {
    $this->serviceContainer->getService('dispatcher')->connect('dm.context.end', array($this, 'listenToContextEndEvent'));
  }
  
  public function listenToContextEndEvent(sfEvent $event)
  {
    if (!$event->getSubject()->getRequest()->getParameter('dm_nolog'))
    {
      $this->log(array(
        'context' => $event->getSubject(),
        'server'  => $_SERVER
      ));
    }
  }
}