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
  protected
  $config,
  $modules,
  $projectModules;
  
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
    $config = sfFactoryConfigHandler::getConfiguration(ProjectConfiguration::getActive()->getConfigPaths('config/factories.yml'));
    
    $options = $config['module_manager']['param'];
    $managerClass = $config['module_manager']['class'];
    
    $this->parse($configFiles);
    
    $this->validate();
    
    $this->processHierarchy();
    
    $this->sortModuleTypes();
    
    $data = array();

    $data[] = sprintf('$options = %s;', var_export($options, true));

    $data[] = sprintf('$manager = new %s($options);', $managerClass);

    $data[] = sprintf('$modules = array(); $projectModules = array(); $modelModules = array();');

    $data[] = sprintf('$types = array();');

    foreach($this->config as $typeName => $typeConfig)
    {
      $data[] = sprintf('$types[\'%s\'] = new %s;', $typeName, $options['type_class']);

      $data[] = sprintf('$typeSpaces = array();');

      foreach($typeConfig as $spaceName => $modulesConfig)
      {
        $data[] = sprintf('$typeSpaces[\'%s\'] = new %s;', $spaceName, $options['space_class']);

        $data[] = sprintf('$spaceModules = array();');

        foreach($modulesConfig as $moduleKey => $moduleConfig)
        {
          $moduleClass = $options[$moduleConfig['is_project'] ? 'module_node_class' : 'module_base_class'];

          if ($moduleConfig['is_project'])
          {
            $moduleReceivers = sprintf('$modules[\'%s\'] = $projectModules[\'%s\'] = $spaceModules[\'%s\']', $moduleKey, $moduleKey, $moduleKey);
          }
          else
          {
            $moduleReceivers = sprintf('$modules[\'%s\'] = $spaceModules[\'%s\']', $moduleKey, $moduleKey);
          }
          
          $data[] = sprintf('%s = new %s(\'%s\', $typeSpaces[\'%s\'], %s);', $moduleReceivers, $moduleClass, $moduleKey, $spaceName, $this->getExportedModuleOptions($moduleKey, $moduleConfig));
        
          if ($moduleConfig['model'])
          {
            $data[] = sprintf('$modelModules[\'%s\'] = \'%s\';', $moduleConfig['model'], $moduleKey);
          }
        }

        $data[] = sprintf('$typeSpaces[\'%s\']->initialize(\'%s\', $types[\'%s\'], $spaceModules);', $spaceName, $spaceName, $typeName);
        
        $data[] = 'unset($spaceModules);';
      }

      $data[] = sprintf('$types[\'%s\']->initialize(\'%s\', $typeSpaces);', $typeName, $typeName);
      
      $data[] = 'unset($typeSpaces);';
    }

    $data[] = sprintf('$manager->load($types, $modules, $projectModules, $modelModules);');
    
    $data[] = 'unset($types, $modules, $projectModules, $modelModules);';

    $data[] = 'return $manager;';
    
    unset($this->config, $this->modules, $this->projectModules);

    return sprintf("<?php\n".
                 "// auto-generated by dmModuleManagerConfigHandler\n".
                 "// date: %s\n%s", date('Y/m/d H:i:s'), implode("\n", $data)
    );
  }
  
  protected function sortModuleTypes()
  {
    // We generally want writer modules first
    if($projectModules = dmArray::get($this->config, 'Project'))
    {
      unset($this->config['Project']);
      
      $this->config = array_merge(array('Project' => $projectModules), $this->config);
    }
  }
  
  protected function validate()
  {
    if (!isset($this->modules['main']))
    {
      $this->throwException('No main module');
    }
    
    if (!isset($this->config['Project']))
    {
      $this->throwException('No Project module type');
    }
    
    foreach($this->modules as $key => $module)
    {
      if ($key != dmString::modulize($key))
      {
        $this->throwModulizeException($key);
      }

      foreach(dmArray::get($module, 'actions', array()) as $actionKey => $action)
      {
        if (is_numeric($actionKey))
        {
          $actionKey = $action;
        }
        
        if ($actionKey != dmString::modulize($actionKey))
        {
          $this->throwModulizeException($actionKey);
        }
      }
      
      if (!$module['model'])
      {
//        if (dmArray::get($module, 'has_page'))
//        {
//          $this->throwException('module %s has a page, but no model', $key);
//        }
//        if (dmArray::get($module, 'parent_key'))
//        {
//          $this->throwException('module %s has a parent, but no model', $key);
//        }
      }
      else
      {
//        if(!Doctrine_Core::isValidModelClass($module['model']))
//        {
//          $this->throwException('module %s has a model that do not exist : %s', $key, $module['model']);
//        }
        if($parentKey = dmArray::get($module, 'parent_key'))
        {
          if ($parentKey == $key)
          {
            $this->throwException('module %s is it\'s own parent...');
          }
          if (!isset($this->modules[$parentKey]))
          {
            $this->throwException('module %s has a parent that do not exist : %s', $key, $parentKey);
          }
        }
      }
    }
    
    $moduleKeys = array();
    foreach($this->config as $typeName => $typeConfig)
    {
      foreach($typeConfig as $spaceName => $modulesConfig)
      {
        foreach($modulesConfig as $moduleKey => $moduleConfig)
        {
          if (in_array($moduleKey, $moduleKeys))
          {
            $this->throwException('The module '.$moduleKey.' is declared twice');
          }
          else
          {
            $moduleKeys[] = $moduleKey;
          }
        }
      }
    }
  }
  
  protected function throwException($message)
  {
    $params = func_get_args();
    
    if (count($params) > 1)
    {
      ob_start();
      call_user_func_array('printf', $params);
      $message = ob_get_clean();
    }
    
    $fullMessage = 'Error in config/dm/modules.yml : '.$message;
    
    throw new sfConfigurationException($fullMessage);
  }
  
  protected function throwModulizeException($string)
  {
    return $this->throwException(sprintf('The word "%s" must follow the symfony module convention : "%s"',
      $string, dmString::modulize($string)
    ));
  }

  protected function getExportedModuleOptions($key, $options)
  {
    if ($options['is_project'] && !empty($options['actions']))
    {
      //export actions properly
      
      $actionsConfig = $options['actions'];
      
      $options['actions'] = '__DM_MODULE_ACTIONS_PLACEHOLDER__';
      
      $exported = var_export($options, true);
      
      $actions = 'array(';

      foreach($actionsConfig as $actionKey => $actionConfig)
      {
        if (is_integer($actionKey))
        {
          $actionKey = $actionConfig;
          $actionConfig = array();
        }
        
        if (empty($actionConfig['name']))
        {
          $actionConfig['name'] = dmString::humanize($actionKey);
        }
    
        if (empty($actionConfig['type']))
        {
          if (strncmp($actionKey, 'list', 4) === 0)
          {
            $actionConfig['type'] = 'list';
          }
          elseif (strncmp($actionKey, 'show', 4) === 0)
          {
            $actionConfig['type'] = 'show';
          }
          elseif (strncmp($actionKey, 'form', 4) === 0)
          {
            $actionConfig['type'] = 'form';
          }
          else
          {
            $actionConfig['type'] = 'simple';
          }
        }
        
        $actions .= sprintf('\'%s\' => new dmAction(\'%s\', %s), ', $actionKey, $actionKey, var_export($actionConfig, true));
      }

      $actions .= ')';
      
      $exported = str_replace('\'__DM_MODULE_ACTIONS_PLACEHOLDER__\'', $actions, $exported);
    }
    else
    {
      $exported = var_export($options, true);
    }
    
    return $exported;
  }
  
  protected function getModuleChildrenKeys($key)
  {
    $children = array();
    
    foreach($this->projectModules as $moduleConfig)
    {
      if ($moduleConfig['parent'] === $this->key)
      {
        $children[$otherModule->getKey()] = $otherModule;
      }
    }
  }

  protected function parse($configFiles)
  {
    // parse the yaml
    $config = self::getConfiguration($configFiles);
    
    $this->config = array();
    $this->modules = array();
    $this->projectModules = array();
    
    foreach($config as $typeName => $typeConfig)
    {
      $this->config[$typeName] = array();
      $isInProject = $typeName === 'Project';
      
      foreach($typeConfig as $spaceName => $spaceConfig)
      {
        $this->config[$typeName][$spaceName] = array();
        
        foreach((array) $spaceConfig as $moduleKey => $moduleConfig)
        {
          $moduleKey = dmString::modulize($moduleKey);
          
          if (isset($this->modules[$moduleKey]))
          {
            continue;
          }
    
          $moduleConfig = $this->fixModuleConfig($moduleKey, $moduleConfig, $isInProject);
          
          $this->modules[$moduleKey] = $moduleConfig;
          
          if ($moduleConfig['is_project'])
          {
            $this->projectModules[$moduleKey] = $moduleConfig;
          }
          
          $this->config[$typeName][$spaceName][$moduleKey] = $moduleConfig;
        }
      }
    }
  }
  
  protected function fixModuleConfig($moduleKey, $moduleConfig, $isInProject)
  {
    /*
     * Extract plural from name
     * name | plural
     */
    if (!empty($moduleConfig['name']))
    {
      if (strpos($moduleConfig['name'], '|'))
      {
        list($moduleConfig['name'], $moduleConfig['plural']) = explode('|', $moduleConfig['name']);
      }
    }
    else
    {
      $moduleConfig['name'] = dmString::humanize($moduleKey);
    }
    
    if (empty($moduleConfig['model']))
    {
      $candidateModel = dmString::camelize($moduleKey);
      
      $model = class_exists('Base'.$candidateModel, true) ?
      Doctrine_Core::isValidModelClass($candidateModel) ? $candidateModel : false
      : false;
    }
    else
    {
      $model = $moduleConfig['model'];
    }
    
    $moduleOptions = array(
      'name' =>       (string) trim($moduleConfig['name']),
      'plural' =>     (string) trim(empty($moduleConfig['plural']) ? ($model ? dmString::pluralize($moduleConfig['name']) : $moduleConfig['name']) : $moduleConfig['plural']),
      'model' =>      $model,
      'credentials' => isset($moduleConfig['credentials']) ? trim($moduleConfig['credentials']) : null,
      'underscore'  => (string) dmString::underscore($moduleKey),
      'is_project'  => (boolean) dmArray::get($moduleConfig, 'project', $isInProject),
      'has_admin'   => (boolean) dmArray::get($moduleConfig, 'admin', $model || !$isInProject),
      'actions'     => dmArray::get($moduleConfig, 'actions', array())
    );
    
    if ($moduleOptions['is_project'])
    {
      $moduleOptions = array_merge($moduleOptions, array(
        'parent_key' => dmArray::get($moduleConfig, 'parent') ? dmString::modulize(trim(dmArray::get($moduleConfig, 'parent'))) : null,
        'has_page'   => (boolean) dmArray::get($moduleConfig, 'page', false)
      ));
    }
    
    // fix non array action filters
    foreach($moduleOptions['actions'] as $actionKey => $actionConfig)
    {
      if(is_array($actionConfig) && array_key_exists('filters', $actionConfig) && !is_array($actionConfig['filters']))
      {
        $moduleOptions['actions'][$actionKey]['filters'] = array($actionConfig['filters']);
      }
    }
    
    return $moduleOptions;
  }
  
  protected function processHierarchy()
  {
    foreach($this->config as $typeName => $typeConfig)
    {
      foreach($typeConfig as $spaceName => $spaceConfig)
      {
        foreach($spaceConfig as $moduleKey => $moduleConfig)
        {
          if (!$moduleConfig['is_project'])
          {
            continue;
          }
          
          $moduleConfig['children_keys'] = $this->getChildrenKeys($moduleKey);
          
          $moduleConfig['path_keys'] = $this->getPathKeys($moduleKey);
          
          $this->config[$typeName][$spaceName][$moduleKey] = $moduleConfig;
        }
      }
    }
  }
  
  protected function getChildrenKeys($moduleKey)
  {
    $childrenKeys = array();
    
    foreach($this->projectModules as $otherModuleKey => $otherModuleConfig)
    {
      if ($otherModuleConfig['parent_key'] === $moduleKey)
      {
        $childrenKeys[] = $otherModuleKey;
      }
    }
    
    return $childrenKeys;
  }
  
  protected function getPathKeys($moduleKey)
  {
    $pathKeys = array();

    $ancestorModuleKey = $moduleKey;
    while($ancestorModuleKey = $this->projectModules[$ancestorModuleKey]['parent_key'])
    {
      $pathKeys[] = $ancestorModuleKey;
    }

    return array_reverse($pathKeys);
  }
  
  /**
   * @see sfConfigHandler
   */
  static public function getConfiguration(array $configFiles)
  {
    return self::parseYamls($configFiles);
  }
}