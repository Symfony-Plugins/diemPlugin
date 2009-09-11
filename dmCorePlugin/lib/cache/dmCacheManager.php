<?php

class dmCacheManager
{
	protected
	  $dispatcher,
	  $caches;

	public function __construct(sfEventDispatcher $dispatcher, array $options = array())
	{
	  $this->dispatcher = $dispatcher;
	  
	  $this->initialize($options);
	}
	  
	public function initialize(array $options = array())
	{
	  $this->metaCacheClass = dmArray::get($options, 'meta_cache_class', 'dmMetaCache');
		$this->reset();
	}

	public function getCache($cacheName)
	{
		$cacheName = dmString::modulize($cacheName);

		if (!isset($this->caches[$cacheName]))
		{
			$this->caches[$cacheName] = new $this->metaCacheClass(array(
			  'prefix' => $cacheName
			));
		}

		return $this->caches[$cacheName];
	}

	/*
	 * remove all cache instances created
	 * does NOT clear caches content
	 */
	public function reset()
	{
    $this->caches = array();
	}

  public function clearAll()
  {
    $success = true;

    // Always clear file cache
    ob_start();
    self::clearFile();
    $success = !ob_get_clean();
    
    if (dmAPCCache::isEnabled())
    {
      $success &= self::clearApc();
    }

    return $success;
  }

  protected function clearApc()
  {
    apc_clear_cache('opcode');
    return apc_clear_cache('user');
  }

  protected function clearFile()
  {
    sfToolkit::clearDirectory(sfConfig::get("sf_cache_dir"));
  }


}