<?php

/**
 * PluginDmPage
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5845 2009-06-09 07:36:57Z jwage $
 */
abstract class PluginDmPage extends BaseDmPage
{
  protected
  $nameBackup;

  protected static
  $autoSeoFields = array('slug', 'name', 'title', 'h1', 'description', 'keywords');

  /*
   * An automatic page represents an myDoctrineRecord object ( article, product... )
   * It will be created, updated and deleted according to its object
   * Automatic pages with the same module will share the same DmPageView & DmAutoSeo
   */
  public function getIsAutomatic()
  {
    if ($this->hasCache('is_automatic'))
    {
      return $this->getCache('is_automatic');
    }

    return $this->setCache('is_automatic', $this->get('action') === 'show');
  }

  public function hasRecords()
  {
    return $this->get('module') === 'list';
  }

  public function hasRecord()
  {
    return 0 != $this->get('record_id');
  }

  public function getRecord()
  {
    if ($this->hasCache('record'))
    {
      return $this->getCache('record');
    }
    
    if (($module = $this->getDmModule()) && ($table = $module->getTable()))
    {
      return $this->setCache('record', $table->find($this->get('record_id')));
    }
    
    return $this->setCache('record', false);
  }

  public function setRecord(myDoctrineRecord $record)
  {
    if ($record->getDmModule()->getKey() != $this->get('module'))
    {
      throw new dmException('Assigning record with wrong module');
    }

    return $this->setCache('record', $record);
  }

  public function getDmModule()
  {
    if($this->hasCache('dm_module'))
    {
      return $this->getCache('dm_module');
    }
    
    if ($serviceContainer = self::$serviceContainer)
    {
      $moduleManager = self::$serviceContainer->getService('module_manager');
    }
    elseif(!$moduleManager = self::$moduleManager)
    {
      throw new dmException('DmPage has no reference to moduleManager');
    }

    return $this->setCache('dm_module', $moduleManager->getModuleOrNull($this->get('module')));
  }

  public function getPageView()
  {
    if($this->hasCache('page_view'))
    {
      return $this->getCache('page_view');
    }

    $pageView = dmDb::query('DmPageView p, p.Layout l')
    ->where('p.module = ? AND p.action = ?', array($this->get('module'), $this->get('action')))
    ->fetchOne();
    
//    $pageView = dmDb::query('DmPageView p')
//    ->where('p.module = ? AND p.action = ?', array($this->module, $this->action))
//    ->fetchRecord();

//    $pageView = dmDb::query('DmPageView p, p.Layout l, p.Area pa, pa.Zones paz, paz.Widgets paw, l.Areas las, las.Zones lasz, lasz.Widgets lasw')
//    ->where('p.module = ? AND p.action = ?', array($this->module, $this->action))
//    ->orderBy('paz.position asc, paw.position asc, lasz.position asc, lasw.position asc')
//    ->fetchRecords();

    if(!$pageView)
    {
      $pageView = dmDb::table('DmPageView')->createFromModuleAndAction($this->get('module'), $this->get('action'));
    }

    return $this->setCache('page_view', $pageView);
  }

  public function setPageView(DmPageView $pageView, $check = true)
  {
    if ($check)
    {
      if ($pageView->get('module') != $this->get('module'))
      {
        throw new dmException('Assigning page view with wrong module');
      }
      if ($pageView->get('action') != $this->get('action'))
      {
        throw new dmException('Assigning page view with wrong action');
      }
    }

    return $this->setCache('page_view', $pageView);
  }

  public function getModuleAction()
  {
    return $this->get('module').'.'.$this->get('action');
  }

  public function isModuleAction($module, $action)
  {
    return $this->module == $module && $this->action == $action;
  }

  /*
   * Same as getNode()->getParent()->id
   * but will not hydrate full parent
   */
  public function getNodeParentId()
  {
    if (!$this->get('lft'))
    {
      return null;
    }

    $stmt = Doctrine_Manager::connection()->prepare('SELECT p.id
FROM dm_page p
WHERE p.lft < ? AND p.rgt > ?
ORDER BY p.rgt ASC
LIMIT 1')->getStatement();

    $stmt->execute(array($this->get('lft'), $this->get('rgt')));
    
    $result = $stmt->fetch(PDO::FETCH_NUM);
    
    return $result[0];
  }

  public function save(Doctrine_Connection $conn = null)
  {
    if ($this->isModified())
    {
      if (!$this->isNew() && ($this->isFieldModified('module') || $this->isFieldModified('module')))
      {
        if ($pageView = dmDb::table('DmPageView')->findOneByModuleAndAction($this->get('module'), $this->get('action')))
        {
          $this->setPageView($pageView);
        }
        else
        {
          $this->getPageView()->fromArray(array(
            'module' => $this->get('module'),
            'action' => $this->get('action')
          ));
        }
      }
      
      $this->getPageView();

      if ($this->getIsAutomatic() && !($this->getRecord() instanceof dmDoctrineRecord))
      {
        throw new dmException(sprintf(
          '%s automatic page can not be saved because it has no object for record_id = %s',
          $this, $this->record_id
        ));
      }
    }

    return parent::save($conn);
  }

  public function preDelete($event)
  {
    parent::preDelete($event);
    
    $this->nameBackup = $this->get('name');
  }
  
  public function getNameBackup()
  {
    return $this->nameBackup;
  }

  public function __toString()
  {
    return $this->nameBackup ? $this->nameBackup : sprintf('#%d %s.%s',
      $this->get('id'),
      $this->get('module'),
      $this->get('action')
    );
  }

  /*
   * SEO methods
   */

  public function getDmAutoSeo()
  {
    if ($this->hasCache('auto_seo'))
    {
      return $this->getCache('auto_seo');
    }

    if (!$autoSeo = dmDb::table('DmAutoSeo')->findOneByModuleAndAction($this->get('module'), $this->get('action')))
    {
      $autoSeo = dmDb::table('DmAutoSeo')->createFromModuleAndAction($this->get('module'), $this->get('action'))->saveGet();
    }

    return $this->setCache('auto_seo', $autoSeo);
  }

  public function getMyAutoSeoFields()
  {
    $fields = array();
    
    foreach(self::getAutoSeoFields() as $field)
    {
      if ($this->isSeoAuto($field))
      {
        $fields[] = $field;
      }
    }

    return $fields;
  }

  public static function getAutoSeoFields()
  {
    return self::$autoSeoFields;
  }

  /*
   * @return boolean true if the field must be setted automatically
   */
  public function isSeoAuto($seoField)
  {
    return strpos($this->get('auto_mod'), $seoField{0}) !== false;
  }
  
  /*
   * Update auto_mod field according to modified fields
   * when fieds are updated manualy
   * if description has been changed,
   * the letter 'd' will be removed from auto_mod
   * but if new description is empty,
   * the letter 'd' will be added to auto_mod
   */
  public function updateAutoModFromModified()
  {
    if (!$this->getIsAutomatic())
    {
      return;
    }
    
    $modifiedFields = $this->get('Translation')->get(self::getDefaultCulture())->getModified();
    
    foreach(self::getAutoSeoFields() as $seoField)
    {
      if(isset($modifiedFields[$seoField]))
      {
        if (empty($modifiedFields[$seoField]) && !$this->isSeoAuto($seoField))
        {
          $this->set('auto_mod', $this->get('auto_mod').$seoField{0});
        }
        if (!empty($modifiedFields[$seoField]) && $this->isSeoAuto($seoField))
        {
          $this->set('auto_mod', str_replace($seoField{0}, '', $this->get('auto_mod')));
        }
      }
    }
    
    return $this;
  }
  
  /*
   * Called when a manual page has been created by an administrator.
   * Overload this method to add default content to the page
   * @return null
   */
  public function initializeManualPage()
  {
  }
  
  /*
   * Get html produced by widgets in this page
   * usefull for search engine indexation
   */
  public function getIndexableContent()
  {
    $command = sprintf('dmFront:page-indexable-content %d %s', $this->get('id'), self::getDefaultCulture());
    
    $filesystem = self::$serviceContainer->getService('filesystem');
    
    $filesystem->sf($command);
    
    return $filesystem->getLastExec('output');
  }
}