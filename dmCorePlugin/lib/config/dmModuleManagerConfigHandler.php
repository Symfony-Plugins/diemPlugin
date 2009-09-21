<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony
 * @subpackage config
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfRoutingConfigHandler.class.php 21923 2009-09-11 14:47:38Z fabien $
 */
class dmModuleManagerConfigHandler extends sfYamlConfigHandler
{
  /**
   * Executes this configuration handler.
   *
   * @param array $configFiles An array of absolute filesystem path to a configuration file
   *
   * @return string Data to be written to a cache file
   *
   * @throws sfConfigurationException If a requested configuration file does not exist or is not readable
   * @throws sfParseException         If a requested configuration file is improperly formatted
   */
  public function execute($configFiles)
  {
    $data = array();

    foreach ($this->parse($configFiles) as $name => $routeConfig)
    {
      $r = new ReflectionClass($routeConfig[0]);
      $route = $r->newInstanceArgs($routeConfig[1]);

      $routes = $route instanceof sfRouteCollection ? $route : array($name => $route);
      foreach (sfPatternRouting::flattenRoutes($routes) as $name => $route)
      {
        $route->setDefaultOptions($options);

        $data[] = sprintf('$map[\'%s\'] = new %s(%s, %s, %s, %s);', $name, get_class($route), var_export($route->getPattern(), true), var_export($route->getDefaults(), true), var_export($route->getRequirements(), true), var_export($route->getOptions(), true));
        $data[] = sprintf('$map[\'%s\']->setCompiledData(%s);', $name, var_export($route->getCompiledData(), true));
      }
    }
    return sprintf("<?php\n".
                   "// auto-generated by sfRoutingConfigHandler\n".
                   "// date: %s\n\$map = array();\n%s\nreturn \$map;\n", date('Y/m/d H:i:s'), implode("\n", $data)
    );
  }

  public function evaluate($configFiles)
  {
    $routeDefinitions = $this->parse($configFiles);

    $routes = array();
    foreach ($routeDefinitions as $name => $route)
    {
      $r = new ReflectionClass($route[0]);
      $routes[$name] = $r->newInstanceArgs($route[1]);
    }

    return $routes;
  }

  protected function parse($configFiles)
  {
    // parse the yaml
    $config = self::getConfiguration($configFiles);
    
    print_r($config);die;
//
//    // collect routes
//    $routes = array();
//    foreach ($config as $name => $params)
//    {
//      if (
//        (isset($params['type']) && 'collection' == $params['type'])
//        ||
//        (isset($params['class']) && false !== strpos($params['class'], 'Collection'))
//      )
//      {
//        $options = isset($params['options']) ? $params['options'] : array();
//        $options['name'] = $name;
//        $options['requirements'] = isset($params['requirements']) ? $params['requirements'] : array();
//
//        $routes[$name] = array(isset($params['class']) ? $params['class'] : 'sfRouteCollection', array($options));
//      }
//      else
//      {
//        $routes[$name] = array(isset($params['class']) ? $params['class'] : 'sfRoute', array(
//          $params['url'] ? $params['url'] : '/',
//          isset($params['params']) ? $params['params'] : (isset($params['param']) ? $params['param'] : array()),
//          isset($params['requirements']) ? $params['requirements'] : array(),
//          isset($params['options']) ? $params['options'] : array(),
//        ));
//      }
//    }

    return $routes;
  }

  /**
   * @see sfConfigHandler
   */
  static public function getConfiguration(array $configFiles)
  {
    return self::parseYamls($configFiles);
  }
}