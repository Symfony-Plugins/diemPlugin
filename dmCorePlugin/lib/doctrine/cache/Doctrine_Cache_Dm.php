<?php
/**
 * File Cache Driver
 */
class Doctrine_Cache_Dm extends Doctrine_Cache_Driver
{
	protected
	$cache;

  public function getCache()
  {
  	if (is_null($this->cache))
  	{
      $this->cache = dmCacheManager::getCache('dm/doctrine');
  	}

  	return $this->cache;
  }

  /**
   * Test if a cache is available for the given id and (if yes) return it (false else).
   *
   * @param string $id cache id
   * @param boolean $testCacheValidity  if set to false, the cache validity won't be tested
   * @return mixed The stored variable on success. FALSE on failure.
   */
  public function fetch($id, $testCacheValidity = true)
  {
    if ($results = $this->getCache()->_get($id))
    {
    	return $results;
    }
    else
    {
      return false;
    }
  }

  /**
   * Test if a cache is available or not (for the given id)
   *
   * @param string $id cache id
   * @return mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
   */
  public function contains($id)
  {
    return $this->getCache()->has($id);
  }

  /**
   * Save some string datas into a cache record
   *
   * Note : $data is always saved as a string
   *
   * @param string $data    data to cache
   * @param string $id    cache id
   * @param int $lifeTime   if != false, set a specific lifetime for this cache record (null => infinite lifeTime)
   * @return boolean true if no problem
   */
  public function save($id, $data, $lifeTime = false)
  {
    return $this->getCache()->_set($id, $data, $lifeTime);
  }

  /**
   * Remove a cache record
   *
   * @param string $id cache id
   * @return boolean true if no problem
   */
  public function delete($id)
  {
    return $this->getCache()->remove($id);
  }
}