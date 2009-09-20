<?php

/*
 * Provides shortcuts to Symfony & Diem static methods
 */
class dm
{
  
  protected static
  $startTime,
  $version,
  $dir;

  public static function register($dir)
  {
    if (null !== self::$dir)
    {
      throw new Exception('Diem has already been registered');
    }
    
    self::resetStartTime();

    self::$dir = $dir;

    require_once(self::$dir.'/dmCorePlugin/lib/config/dmProjectConfiguration.php');
  }
  
  public static function resetStartTime()
  {
    self::$startTime = microtime(true);
  }
  
  public static function getDir()
  {
    return self::$dir;
  }
  
  public static function getStartTime()
  {
    return self::$startTime;
  }
  
  public static function lightAction()
  {
    switch(isset($_REQUEST['action']) ? $_REQUEST['action'] : null)
    {
      case 'markdown':
        require_once(implode(DIRECTORY_SEPARATOR, array(dm::getDir(), 'dmCorePlugin', 'lib', 'markdown', 'dmMarkdown.php')));
        print isset($_REQUEST['text']) ? dmMarkdown::toHtml($_REQUEST['text']) : '';
        break;
      default:
        header('HTTP/1.0 404 Page Not Found');
    }
  }
  
  public static function checkServer()
  {
    $configuration = ProjectConfiguration::getApplicationConfiguration('admin', 'test', true);
    dmContext::createInstance($configuration);
    
    $serverCheck = new dmServerCheck;
    
    print $serverCheck->render();
    
    exit;
  }
  
  /*
   * Symfony common objects accessors
   */

  public static function getRouting()
  {
    return dmContext::getInstance()->getRouting();
  }

  /*
   * @return dmWebRequest
   */
  public static function getRequest()
  {
    return dmContext::getInstance()->getRequest();
  }

  public static function getResponse()
  {
    return dmContext::getInstance()->getResponse();
  }

  public static function getController()
  {
    return dmContext::getInstance()->getController();
  }

  public static function getEventDispatcher()
  {
    return dmContext::hasInstance()
    ? dmContext::getInstance()->getEventDispatcher()
    : ProjectConfiguration::getActive()->getEventDispatcher();
  }

  public static function getUser()
  {
    return dmContext::getInstance()->getUser();
  }

  public static function getI18n()
  {
    return dmContext::getInstance()->getI18n();
  }
  
  public static function loadHelpers($helpers)
  {
    return dmContext::getInstance()->getConfiguration()->loadHelpers($helpers);
  }

  /*
   * Diem common features shortcuts
   */

  public static function version()
  {
    return sfConfig::get('dm_version');
  }

  /*
   * Gadgets
   */

  /*
   * Diem code size
   * returns array(files, lines, characters)
   */
  public static function getDiemSize()
  {
    $timer = dmDebug::timerOrNull('dm::getDiemSize()');

    $pluginsDir = sfConfig::get('sf_plugins_dir').'/';

    $files = sfFinder::type('file')
      ->prune('om')
      ->prune('map')
      ->prune('base')
      ->prune('vendor')
      ->name('*\.php', '*\.css', '*\.js', '*\.yml')
      ->in($pluginsDir.'dmCorePlugin', $pluginsDir.'dmAdminPlugin', $pluginsDir.'dmFrontPlugin');

    foreach($files as $key => $file)
    {
      if(strpos($file, '/web/lib/'))
      {
        unset($files[$key]);
      }
    }

    $lines = 0;
    $characters = 0;

    foreach($files as $file)
    {
      $content = file($file);
      $lines += count($content);
      $characters += strlen(implode(' ', $content));
    }

    $response = array(
      'nb_files' => count($files),
      'lines' => $lines,
      'characters' => $characters
    );

    $timer && $timer->addTime();

    return $response;
  }
}