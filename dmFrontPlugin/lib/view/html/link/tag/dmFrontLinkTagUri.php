<?php

class dmFrontLinkTagUri extends dmFrontLinkTag
{
  protected
  $uri;

  protected function initialize()
  {
    parent::initialize();
    
    $this->uri = $this->resource->getSubject();
  }

  protected function getBaseHref()
  {
    if (strncmp($this->uri, '#', 1) === 0)
    {
      return $this->uri;
    }
    
    return self::$context->get('controller')->genUrl($this->uri);
  }

}