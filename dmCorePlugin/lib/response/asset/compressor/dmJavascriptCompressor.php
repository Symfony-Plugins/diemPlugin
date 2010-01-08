<?php

class dmJavascriptCompressor extends dmAssetCompressor
{

  public function getDefaultOptions()
  {
    return array_merge(parent::getDefaultOptions(), array(
      'black_list'          => array()
    ));
  }

  public function connect()
  {
    $this->dispatcher->connect('dm.layout.filter_javascripts', array($this, 'listenToFilterAssetsEvent'));
  }
  
  protected function getType()
  {
    return 'js';
  }
  
  protected function processAssetContent($content, $path)
  {
    if ($this->isMinifiable($path))
    {
      $content = dmJsMinifier::transform($content);
    }
    
    return $content.';';
  }

  protected function isMinifiable($path)
  {
    return $this->getOption('minify') && $this->dispatcher->filter(
      new sfEvent($this, 'dm.javascript_compressor.minifiable', array('path' => $path)),
      !in_array(basename($path), $this->getOption('black_list')) && !strpos($path, '.min.') && !strpos($path, '.pack.')
    )->getReturnValue();
  }

  protected function isCachable($path, array $options = array())
  {
    return $this->options['minify'] && $this->dispatcher->filter(
      new sfEvent($this, 'dm.javascript_compressor.cachable', array('path' => $path, 'options' => $options)),
      !($this->options['protect_user_assets'] && strncmp($path, '/dm', 3) !== 0)
    )->getReturnValue();
  }
}