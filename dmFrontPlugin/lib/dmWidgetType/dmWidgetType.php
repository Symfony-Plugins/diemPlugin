<?php

class dmWidgetType
{

  protected
    $module,
    $action,
    $params;

  public function __construct($module, $action, $config = array())
  {
    $this->module  = $module;
    $this->action  = $action;

    $this->params = $config;
  }

  public function getModule()
  {
    return $this->module;
  }

  public function getAction()
  {
    return $this->action;
  }

  public function getFullKey()
  {
    return $this->getParam('full_key');
  }
  
  public function isCachable()
  {
    return (bool) $this->getParam('cache');
  }
  
  public function isStatic()
  {
    return 'static' === $this->getParam('cache');
  }

  public function getNewWidget()
  {
    return dmDb::create('DmWidget', array(
      'module' => $this->getModule(),
      'action' => $this->getAction()
    ));
  }

  public function useComponent()
  {
    return $this->getParam('use_component');
  }

  public function getName()
  {
    return $this->getParam('name');
  }
  
  public function getPublicName()
  {
    return dmString::humanize($this->getName());
  }

  public function getFormClass()
  {
    return $this->getParam('form_class');
  }

  public function getViewClass()
  {
    return $this->getParam('view_class');
  }

  public function getUnderscore()
  {
    return dmString::underscore($this->getModule());
  }

  public function getParam($key)
  {
    return isset($this->params[$key]) ? $this->params[$key] : null;
  }

  public function setParam($key, $value)
  {
    return $this->params[$key] = $value;
  }

  public function __toString()
  {
    return $this->getKey();
  }

}