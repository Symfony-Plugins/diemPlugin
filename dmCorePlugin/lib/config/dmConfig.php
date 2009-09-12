<?php

/**
 * dmConfig stores all configuration information for a diem application in database.
 */
class dmConfig
{
  protected static
  $culture,
  $config,
  $loaded = false;

  /**
   * Retrieves a config parameter.
   *
   * @param string $name    A config parameter name
   * @param mixed  $default A default config parameter value
   *
   * @return mixed A config parameter value, if the config parameter exists, otherwise null
   */
  public static function get($name)
  {
    if (!self::has($name))
    {
      throw new dmException(sprintf('There is no setting called "%s". Available settings are : %s', $name, implode(', ', array_keys(self::$config))));
    }
    return self::$config[$name];
  }

  /**
   * Indicates whether or not a config parameter exists.
   *
   * @param string $name A config parameter name
   *
   * @return bool true, if the config parameter exists, otherwise false
   */
  public static function has($name)
  {
    if(!self::$loaded)
    {
      self::load();
    }
    return array_key_exists($name, self::$config);
  }

  /**
   * Sets a config parameter.
   *
   * If a config parameter with the name already exists the value will be overridden.
   *
   * @param string $name  A config parameter name
   * @param mixed  $value A config parameter value
   */
  public static function set($name, $value)
  {
    if (!self::has($name))
    {
      throw new dmException(sprintf('There is no setting called "%s". Available settings are : %s', $name, implode(', ', array_keys(self::$config))));
    }
    
    /*
     * Convert booleans to 0, 1 not to fail doctrine validation
     */
    if (is_bool($value))
    {
      $value = (string) (int) $value;
    }

    $setting = dmDb::query('DmSetting s')->where('s.name = ?', $name)->withI18n(self::$culture)->fetchOne();

    $setting->value = $value;

    $setting->save();

    return self::$config[$name] = $value;
  }

  /**
   * Retrieves all configuration parameters.
   *
   * @return array An associative array of configuration parameters.
   */
  public static function getAll()
  {
    if(!self::$loaded)
    {
      self::load();
    }

    return self::$config;
  }

  public static function initialize(sfEventDispatcher $dispatcher)
  {
    $dispatcher->connect('user.change_culture', array('myConfig', 'listenToChangeCultureEvent'));
  }

  /**
   * Listens to the user.change_culture event.
   *
   * @param sfEvent An sfEvent instance
   */
  public static function listenToChangeCultureEvent(sfEvent $event)
  {
    if (self::$culture != $event['culture'])
    {
      self::$culture = $event['culture'];
      self::load();
    }
  }

  public static function load($useCache = true)
  {
    if (!self::$culture)
    {
      if (class_exists('sfContext', false) && sfContext::hasInstance() && $user = sfContext::getInstance()->getUser())
      {
        self::$culture = $user->getCulture();
      }
      else
      {
        self::$culture = sfConfig::get('sf_default_culture');
      }
    }

    if(self::$culture == sfConfig::get('sf_default_culture'))
    {
      $stmt = Doctrine_Manager::connection()->prepare('SELECT s.name, t.value
FROM dm_setting s
LEFT JOIN dm_setting_translation t ON t.id=s.id AND t.lang = ?');

      $stmt->execute(array(self::$culture));
    }
    else
    {
      $stmt = Doctrine_Manager::connection()->prepare('SELECT s.name, t.value
FROM dm_setting s
LEFT JOIN dm_setting_translation t ON t.id=s.id AND t.lang IN (?, ?)');

      $stmt->execute(array(self::$culture, sfConfig::get('sf_default_culture')));
    }

    $results = $stmt->fetchAll(Doctrine::FETCH_NUM);

    self::$config = array();
    foreach($results as $result)
    {
      self::$config[$result[0]] = $result[1];
    }
    unset($results);

    self::$loaded = true;
  }

  public static function getCulture()
  {
    return self::$culture;
  }

  public static function isCli()
  {
    return !isset($_SERVER['HTTP_HOST']);
  }

}