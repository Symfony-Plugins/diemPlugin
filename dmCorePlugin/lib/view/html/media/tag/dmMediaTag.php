<?php

abstract class dmMediaTag extends dmHtmlTag
{
  protected
  $resource,
  $context;

  public function __construct(dmMediaResource $resource, dmContext $context, array $options = array())
  {
    $this->resource         = $resource;
    $this->context          = $context;
    
    $this->initialize($options);
  }
  
  public function width($v)
  {
    return $this->set('width', max(0, (int)$v));
  }

  public function height($v)
  {
    return $this->set('height', max(0, (int)$v));
  }

  public function size($width, $height = null)
  {
    if (is_array($width))
    {
      list($width, $height) = $width;
    }

    return $this->width($width)->height($height);
  }

  public function getSrc($throwException = true)
  {
    if ($throwException)
    {
      return dmArray::get($this->prepareAttributesForHtml($this->options), 'src');
    }
    else
    {
      try
      {
        return dmArray::get($this->prepareAttributesForHtml($this->options), 'src');
      }
      catch(Exception $e)
      {
        return false;
      }
    }
  }

  protected function prepareAttributesForHtml(array $attributes)
  {
    $attributes = parent::prepareAttributesForHtml($attributes);

    $attributes['src'] = $this->resource->getWebPath();

    return $attributes;
  }

  protected function hasSize()
  {
    return !(empty($this->options['width']) && empty($this->options['height']));
  }

  public function quality($val)
  {
    // override me
  }
}