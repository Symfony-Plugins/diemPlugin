<?php

class dmSeoSynchronizer
{
  protected static
  $truncateCache;
  
  protected
  $moduleManager,
  $culture,
  $nodeParentIdStmt;
  
  public function __construct(dmModuleManager $moduleManager)
  {
    $this->moduleManager  = $moduleManager;
  }
  
  public function setCulture($culture)
  {
    $this->culture = $culture;
  }
  
  public function execute(array $onlyModules = array(), $culture)
  {
    $this->setCulture($culture);
    
    $recordDefaultCulture = myDoctrineRecord::getDefaultCulture();
    myDoctrineRecord::setDefaultCulture($this->culture);
    
    if(empty($onlyModules))
    {
      $onlyModules = $this->moduleManager->getProjectModules();
    }
    elseif(is_string(dmArray::first($onlyModules)))
    {
      $onlyModules = $this->moduleManager->keysToModules($onlyModules);
    }
    
    $onlyModules = dmModuleManager::removeModulesChildren($onlyModules);
    
    foreach($onlyModules as $module)
    {
      $this->updateRecursive($module);
    }
    
    myDoctrineRecord::setDefaultCulture($recordDefaultCulture);
  }

  public function updateRecursive($module)
  {
    if (!$module->hasPage())
    {
      foreach($module->getChildren() as $child)
      {
        $this->updateRecursive($child);
      }
      
      return;
    }

    /*
     * get autoSeo patterns
     */
    $autoSeoRecord = dmDb::query('DmAutoSeo a')
    ->withI18n($this->culture, null, 'a')
    ->where('a.module = ?', $module->getKey())
    ->andWhere('a.action = ?', 'show')
    ->fetchRecord();
    
    if(!$autoSeoRecord)
    {
      $autoSeoRecord = dmDb::table('DmAutoSeo')
      ->createFromModuleAndAction($module, 'show', $this->culture)
      ->saveGet();
    }
    
    $patterns = array();
    foreach(DmPage::getAutoSeoFields() as $patternField)
    {
      $patterns[$patternField] = $autoSeoRecord->get($patternField);
    }
    
    if (isset($patterns['keywords']) && !sfConfig::get('dm_seo_use_keywords'))
    {
      unset($patterns['keywords']);
    }

    /*
     * get pages
     */
    $pdoPages = dmDb::pdo('
    SELECT p.id, p.lft, p.rgt, p.record_id, t.auto_mod, t.slug, t.name, t.title, t.h1, t.description, t.keywords, t.id as exist
    FROM dm_page p LEFT JOIN dm_page_translation t ON (t.id = p.id AND t.lang = ?)
    WHERE p.module = ? AND p.action = ?', array($this->culture, $module->getKey(), 'show')
    )->fetchAll(PDO::FETCH_ASSOC);

    $pages = array();
    foreach($pdoPages as $p)
    {
      $pages[$p['id']] = $p;
    }
    unset($pdoPages);
    
    /*
     * get records
     */
    $records = $module->getTable()->createQuery('r INDEXBY r.id')
    ->withI18n($this->culture, $module->getModel(), 'r')
    ->fetchRecords();
    
    /*
     * get parent slugs
     * if slug pattern starts with a /
     * we don't use parent slug to build  the page slug
     */
    if ($patterns['slug']{0} === '/')
    {
      $parentSlugs = array();
    }
    else
    {
      $parentSlugs = $this->getParentSlugs($module);
    }

    $modifiedPages = array();
    foreach($pages as $page)
    {
      $record = $records[$page['record_id']];
      $parentId = $this->getNodeParentId($page);
      $parentSlug = isset($parentSlugs[$parentId]) ? $parentSlugs[$parentId] : '';

      $modifiedFields = $this->updatePage($page, $module, $record, $patterns, $parentSlug);
      
      if (!empty($modifiedFields))
      {
        $modifiedPages[$page['id']] = $modifiedFields;
      }
    }
    
    $records->free(true);

    /*
     * Save modifications
     */
    if(!empty($modifiedPages))
    {
      /*
       * Fix bug when no DmPage instance have been loaded yet
       * ( this can happen when synchronization is run in a thread )
       * DmPageTranslation class does not exist
       */
      if (!class_exists('DmPageTranslation'))
      {
        new DmPage;
      }
      
      $conn = Doctrine_Manager::getInstance()->getCurrentConnection();
      try
      {
        $conn->beginTransaction();

        foreach($modifiedPages as $id => $modifiedFields)
        {
          if (!$pages[$id]['exist'])
          {
            $modifiedFields['id'] = $id;
            $modifiedFields['lang'] = $this->culture;
            $translation = new DmPageTranslation;
            $translation->fromArray($modifiedFields);
            $conn->unitOfWork->processSingleInsert($translation);
          }
          else
          {
            myDoctrineQuery::create($conn)->update('DmPageTranslation')
            ->set($modifiedFields)
            ->where('id = ?', $id)
            ->andWhere('lang = ?', $this->culture)
            ->execute();
          }
        }

        $conn->commit();
      }
      catch(Doctrine_Exception $e)
      {
        $conn->rollback();
        throw $e;
      }
    
    }
    
    unset($pages);

    foreach($module->getChildren() as $child)
    {
      $this->updateRecursive($child);
    }
  }

  public function updatePage(array $page, dmProjectModule $module, dmDoctrineRecord $record, $patterns, $parentSlug)
  {
    $pageAutoMod = dmArray::get($page, 'auto_mod', 'snthdk');

    foreach($patterns as $field => $pattern)
    {
      if (strpos($pageAutoMod, $field{0}) === false)
      {
        unset($patterns[$field]);
      }
    }

    /*
     * Calculate replacements
     */
    $replacements = $this->getReplacementsForPatterns($module, $patterns, $record);

    /*
     * Assign replacements to patterns
     */
    $values = $this->compilePatterns($patterns, $replacements, $record, $parentSlug);

    /*
     * Compare obtained seo values with page values
     */
    $modifiedFields = array();
    foreach($values as $field => $value)
    {
      if ($value != $page[$field])
      {
        $modifiedFields[$field] = $value;
      }
    }

    return $modifiedFields;
  }

  public function validatePattern(dmProjectModule $module, $field, $pattern, dmDoctrineRecord $record = null)
  {
    $record = null === $record ? $module->getTable()->findOne() : $record;

    try
    {
      $this->getReplacementsForPatterns($module, array($pattern), $record);
    }
    catch(Exception $e)
    {
      return false;
    }
    
    return true;
  }
  
  public function getReplacementsForPatterns(dmProjectModule $module, $patterns, dmDoctrineRecord $record)
  {
    preg_match_all('/%([\w\d\.-]+)%/i', implode('', $patterns), $results);
    $placeholders = array_unique($results[1]);
    
    $moduleKey = $module->getKey();
    $replacements = array();
    
    foreach ($placeholders as $placeholder)
    {
      if ('culture' === $placeholder)
      {
        $replacements[$this->wrap($placeholder)] = $this->culture;
        continue;
      }
      /*
       * Extract model and field from 'model.field' or 'model'
       */
      if (strpos($placeholder, '.'))
      {
        list($usedModuleKey, $field) = explode('.', $placeholder);
      }
      else
      {
        $usedModuleKey = $placeholder;
        $field = '__toString';
      }

      $usedModuleKey = dmString::modulize($usedModuleKey);
      $usedRecord = null;
      /*
       * Retrieve used record
       */
      if ($usedModuleKey == $moduleKey)
      {
        $usedRecord = $record;
      }
      elseif($module->hasAncestor($usedModuleKey))
      {
        $usedRecord = $record->getAncestorRecord($usedModuleKey);
      }
      else
      {
        $usedRecord = $record->getRelatedRecord($this->moduleManager->getModule($usedModuleKey)->getModel());
      }

      if ($usedRecord instanceof dmDoctrineRecord)
      {
        /*
         * get record value for field
         */
        if ($field == '__toString')
        {
          $usedValue = $usedRecord->__toString();
          $processMarkdown = true;
        }
        else
        {
          $usedValue = $usedRecord->get($field);
          
          $processMarkdown = $usedRecord->getTable()->hasColumn($field) && $usedRecord->getTable()->isMarkdownColumn($field);
        }
        
        unset($usedRecord);
      }
      else
      {
        $usedValue = $moduleKey.'-'.$usedModuleKey.' not found';
        $processMarkdown = false;
      }
      
      $usedValue = trim($usedValue);
      
      if($processMarkdown)
      {
        $usedValue = dmMarkdown::brutalToText($usedValue);
      }

      $replacements[$this->wrap($placeholder)] = $usedValue;
    }
    
    return $replacements;
  }
  
  public function compilePatterns(array $patterns, array $replacements, dmDoctrineRecord $record, $parentSlug)
  {
    $values = array();
    
    foreach($patterns as $field => $pattern)
    {
      if ($field === 'slug')
      {
        $slugReplacements = array();
        foreach($replacements as $key => $replacement)
        {
          $slugReplacements[$key] = dmString::slugify($replacement);
        }
        
        $value = strtr($pattern, $slugReplacements);
        
        // add parent slug
        if ($pattern{0} != '/')
        {
          $value = $parentSlug.'/'.strtr($pattern, $slugReplacements);
        }
        
        $value = trim(preg_replace('|(/{2,})|', '/', $value), '/');
      }
      elseif($field === 'title')
      {
        $value = ucfirst(strtr($pattern, $replacements));
      }
      else
      {
        $value = strtr($pattern, $replacements);
      }

      $values[$field] = self::truncateValueForField(trim($value), $field);
    }
    
    return $values;
  }
  
  public function wrap($property)
  {
    return '%'.$property.'%';
  }

  protected function getParentSlugs($module)
  {
    if($module->hasListPage())
    {
      $parentPageModuleKey = $module->getKey();
      $parentPageActionKey = 'list';
    }
    elseif ($parentModule = $module->getNearestAncestorWithPage())
    {
      $parentPageModuleKey = $parentModule->getKey();
      $parentPageActionKey = 'show';
    }
    else
    {
      throw new dmException(sprintf(
        'can not identify parent module for %s module', $module
      ));
    }

    $parentSlugResults = dmDb::pdo('SELECT t.id, t.slug
    FROM dm_page p, dm_page_translation t
    WHERE p.module = ? AND p.action = ? AND p.id = t.id AND t.lang = ?',
    array($parentPageModuleKey, $parentPageActionKey, $this->culture))
    ->fetchAll(PDO::FETCH_NUM);
    
    $parentSlugs = array();
    foreach($parentSlugResults as $psr)
    {
      $parentSlugs[$psr[0]] = $psr[1];
    }
    unset($parentSlugsResult);

    return $parentSlugs;
  }
  
  protected function getNodeParentId(array $pageData)
  {
    if (null === $this->nodeParentIdStmt)
    {
      $this->nodeParentIdStmt = Doctrine_Manager::getInstance()->getCurrentConnection()->prepare('SELECT p.id
FROM dm_page p
WHERE p.lft < ? AND p.rgt > ?
ORDER BY p.rgt ASC
LIMIT 1')->getStatement();
    }

    $this->nodeParentIdStmt->execute(array($pageData['lft'], $pageData['rgt']));
    
    $result = $this->nodeParentIdStmt->fetch(PDO::FETCH_NUM);
    
    return $result[0];
  }

  /*
   * Static methods
   */

  public static function truncateValueForField($value, $field)
  {
    return function_exists('mb_substr')
    ? mb_substr($value, 0, self::getFieldMaxLength($field))
    : substr($value, 0, self::getFieldMaxLength($field));
  }

  public static function getFieldMaxLength($field)
  {
    if (null === self::$truncateCache)
    {
      $truncateConfig = sfConfig::get('dm_seo_truncate');
      self::$truncateCache = array();
      foreach(DmPage::getAutoSeoFields() as $seoField)
      {
        self::$truncateCache[$seoField] = dmArray::get($truncateConfig, $seoField, 255);
      }
    }

    return self::$truncateCache[$field];
  }
}