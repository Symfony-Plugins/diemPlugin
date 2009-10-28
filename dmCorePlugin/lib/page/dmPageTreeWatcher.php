<?php

class dmPageTreeWatcher extends dmConfigurable
{
  protected
  $dispatcher,
  $serviceContainer,
  $options,
  $modifiedTables;

  public function __construct(sfEventDispatcher $dispatcher, dmBaseServiceContainer $serviceContainer, array $options = array())
  {
    $this->dispatcher = $dispatcher;
    $this->serviceContainer = $serviceContainer;
    
    $this->initialize($options);
  }
  
  public function getDefaultOptions()
  {
    return array(
      'use_thread' => 'auto'
    );
  }

  public function initialize(array $options = array())
  {
    $this->configure($options);
    
    $this->reset();
  }
  
  public function reset()
  {
    $this->modifiedTables = array();
  }
  
  public function connect()
  {
    $this->dispatcher->connect('dm.controller.redirect', array($this, 'listenToControllerRedirectionEvent'));
    
    $this->dispatcher->connect('dm.record.modification', array($this, 'listenToRecordModificationEvent'));
  }
  
  public function listenToRecordModificationEvent(sfEvent $event)
  {
    $record = $event->getSubject();
    
    if ($record instanceof DmAutoSeo)
    {
      $table = $record->getTargetDmModule()->getTable();
    }
    else
    {
      $table = $record->getTable();
    }
    
    if ($table instanceof dmDoctrineTable && !isset($this->modifiedTables[$table->getComponentName()]) && $table->interactsWithPageTree())
    {
      $this->addModifiedTable($table);
    }
    
//    if($record->isFieldModified('is_active'))
//    {
//      $isActive = $record->get('is_active');
//      
//      if ($record instanceof DmPage && ($pageRecord = $record->getRecord()) && $pageRecord->getTable()->hasField('is_active') && $isActive != $pageRecord->get('is_active'))
//      {
//        $pageRecord->set('is_active', $record->get('is_active'));
//        $pageRecord->save();
//      }
//      elseif($record->getDmModule()->hasPage() && ($page = $record->getDmPage()) && $isActive != $page->get('is_active'))
//      {
//        $page->set('is_active', $record->get('is_active'));
//        $page->save();
//      }
//    }
  }
  
  public function addModifiedTable(dmDoctrineTable $table)
  {
    $model = $table->getComponentName();
    
    if (!isset($this->modifiedTables[$model]))
    {
      $this->modifiedTables[$model] = $table;
    }
  }

  public function listenToControllerRedirectionEvent(sfEvent $event)
  {
    $this->update();
  }

  public function update()
  {
    $modifiedModules = $this->getModifiedModules();
    
    if(!empty($modifiedModules))
    {
      try
      {
        $this->synchronizePages($modifiedModules);
      
        $this->synchronizeSeo($modifiedModules);
      }
      catch(Exception $e)
      {
        $this->serviceContainer->get('user')->logError('Something went wrong when updating project');
        
        if (sfConfig::get('sf_debug'))
        {
          throw $e;
        }
      }
    }

    $this->initialize();
  }

  public function getModifiedModules()
  {
    $modifiedModules = array();
    foreach($this->modifiedTables as $table)
    {
      /*
       * If table belongs to a project module,
       * it may interact with tree
       */
      if ($module = $table->getDmModule())
      {
        if ($module->interactsWithPageTree())
        {
          $modifiedModules[] = $module->getKey();
        }
      }
      /*
       * If table owns project tables,
       * it may interact with tree
       */
      else
      {
        $moduleManager = $this->serviceContainer->getService('module_manager');
        
        foreach($table->getRelationHolder()->getLocals() as $localRelation)
        {
          if ($localModule = $moduleManager->getModuleByModel($localRelation->getClass()))
          {
            if ($localModule->interactsWithPageTree())
            {
              $modifiedModules[] = $localModule->getKey();
            }
          }
        }
      }
    }

    return $modifiedModules;
  }
  
  protected function useThread()
  {
    if ('auto' == $this->getOption('use_thread'))
    {
      $useThread = false;
      
      $apacheMemoryLimit = dmString::convertBytes(ini_get('memory_limit'));
      if($apacheMemoryLimit < 128 * 1024 * 1024)
      {
        $filesystem = $this->serviceContainer->getService('filesystem');
        
        if ($filesystem->exec('php -r "die(ini_get(\'memory_limit\'));"'))
        {
          $cliMemoryLimit = dmString::convertBytes($filesystem->getLastExec('output'));
          
          $useThread = ($cliMemoryLimit >= $apacheMemoryLimit);
        }
      }
      
      $this->setOption('use_thread', $useThread);
    }
    
    return $this->getOption('use_thread');
  }
  
  public function synchronizePages(array $modules = array())
  {
    if ($this->useThread())
    {
      $threadLauncher = $this->serviceContainer->getService('thread_launcher');
    
      $pageSynchronizerSuccess = $threadLauncher->execute('dmPageSynchronizerThread', array(
        'class'   => $this->serviceContainer->getParameter('page_synchronizer.class'),
        'modules' => $modules
      ));
    }
    else
    {
      $this->serviceContainer->getService('page_synchronizer')->execute($modules);
    }
  }
  
  public function synchronizeSeo(array $modules = array())
  {
    if ($this->useThread())
    {
      $threadLauncher = $this->serviceContainer->getService('thread_launcher');
      
      $seoSynchronizerSuccess = $threadLauncher->execute('dmSeoSynchronizerThread', array(
        'class'   => $this->serviceContainer->getParameter('seo_synchronizer.class'),
        'markdown_class' => $this->serviceContainer->getParameter('markdown.class'),
        'culture' => $this->serviceContainer->getParameter('user.culture'),
        'modules' => $modules
      ));
    }
    else
    {
      $this->serviceContainer->getService('seo_synchronizer')->execute($modules);
    }
  }
}