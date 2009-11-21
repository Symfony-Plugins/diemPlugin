<?php

class dmFrontLinkTagAction extends dmFrontLinkTag
{
  protected
  $action;
  
  protected function initialize(array $options = array())
  {
    parent::initialize($options);
    
    $this->action = $this->resource->getSubject();
  }

  protected function getBaseHref()
  {
    return self::$context->getController()->genUrl($this->action);
  }

}