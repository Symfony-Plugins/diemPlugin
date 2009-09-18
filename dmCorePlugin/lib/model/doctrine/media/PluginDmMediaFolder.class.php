<?php

/**
 * PluginDmMediaFolder
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5845 2009-06-09 07:36:57Z jwage $
 */
abstract class PluginDmMediaFolder extends BaseDmMediaFolder
{

  /*
   * Getter methods
   */
  
  public function getName()
  {
    $relPath = $this->get('rel_path');
    
    if(strpos($relPath, '/'))
    {
      $name = basename($relPath);
    }
    elseif($relPath)
    {
      $name = $relPath;
    }
    else
    {
      $name = 'root';
    }
    
    return $name; 
  }

  public function getFullPath()
  {
    return dmOs::join(sfConfig::get('sf_upload_dir'), $this->get('rel_path'));
  }

  public function getNbElements()
  {
    if($this->hasCache('nbElements'))
    {
      return $this->getCache('nbElements');
    }

    $nbMedias = dmDb::query('DmMedia m')
    ->where('m.dm_media_folder_id = ?', $this->get('id'))
    ->count();

    return $this->setCache('nbElements', $nbMedias + $this->getNode()->getNumberDescendants());
  }

  public function getSubFoldersByName()
  {
    $foldersName = array();

    if ($children = $this->getNode()->getChildren())
    {
      foreach ($children as $folder)
      {
        $foldersName[$folder->get('name')] = $folder;
      }
    }

    return $foldersName;
  }

  public function getDmMediasByFileName()
  {
    $filesName = array();
    foreach ($this->getMedias() as $file)
    {
      $filesName[$file->get('file')] = $file;
    }

    return $filesName;
  }

  public function getMedias()
  {
    if ($this->hasCache('medias'))
    {
      return $this->getCache('medias');
    }

    $timer = dmDebug::timer('Folder::getMedias');

    $medias = $this->_get('Medias');

    foreach($medias as $media)
    {
      $media->set('Folder', $this, false);
    }

    $timer->addTime();

    return $this->setCache('medias', $medias);
  }

  /*
   * Check methods
   */

  /**
   * Folder physically exists
   *
   * @return bool
   */
  public function dirExists()
  {
    return is_dir($this->getFullPath());
  }

  public function isWritable()
  {
    return is_writable($this->getFullPath());
  }

  /**
   * Checks if a name already exists in the list of subfolders to a folder
   *
   * @param string $name A folder name
   * @return bool
   */
  public function hasSubFolder($name)
  {
    return dmDb::query('DmMediaFolder f')
    ->where('f.name = ? AND f.lft > ? AND f.rgt < ?', array($name, $this->get('lft'), $this->get('rgt')))
    ->exists();
  }

  public function hasFile($name)
  {
    return dmDb::query('DmMedia m')
    ->where('m.dm_media_folder_id = ? AND m.file = ?', array($this->get('id'), $name))
    ->exists();
  }

  /*
   * Shortcut to ->getNode()->isRoot()
   */
  public function isRoot()
  {
    return $this->getNode()->isRoot();
  }

  /*
   * Setter methods
   */

  public function setRelPath($v)
  {
    return $this->_set('rel_path', trim($v, '/'));
  }

  /*
   * Manipulation methods
   */

  /**
   * Physically creates folder
   *
   * @return bool succes
   */
  public function create()
  {
    return dmContext::getInstance()->getFilesystem()->mkdir($this->getFullPath());
  }

  /**
   * Change folder name
   *
   * @param string $name
   */
  public function rename($name)
  {
    if($this->getNode()->getParent()->hasSubFolder($name))
    {
      throw new dmException('The parent folder already contains a folder named "%name%". The folder has not been renamed.', array('%name%' => $name));
    }
    else if ($name !== $this->name)
    {
      if(dmMediaTools::sanitizeDirName($name) != $name)
      {
        throw new dmException('The target folder name "%name%" contains incorrect characters. The folder has not be renamed.', array('%name%' => $name));
      }
      $oldName = $this->name;

      $fs = dmContext::getInstance()->getFilesystem();

      if(!$fs->exec(sprintf('cd %s && mv %s %s',
        dmOs::join($this->getNode()->geParent()->getFullPath()), $oldName, $name
      )))
      {
        throw new dmException($fs->getLastExec());
      }

      $this->relPath = preg_replace('|^(.*)('.preg_quote($oldName, '|').')$|', '$1'.$name, $this->relPath);

      $this->clearCache()->save();

      $this->getNode()->getDescendants()->save();
    }
    // else: nothing to do
  }

  public function sync()
  {
    $timer = dmDebug::timerOrNull('DmMediaFolder::sync');

    /*
     * Clear php filesystem cache
     * This will avoid some problems
     */
    clearstatcache();

    $this->refresh(true);

    $files = sfFinder::type('file')->maxdepth(0)->ignore_version_control()->in($this->getFullPath());
    $medias = $this->getDmMediasByFileName();

    foreach($files as $file)
    {
      /*
       * Sanitize files name ( move files )
       */
      if (basename($file) != dmOs::sanitizeFileName(basename($file)))
      {
        $renamed_file = dmOs::join(dirname($file), dmOs::sanitizeFileName(basename($file)));
        while(file_exists($renamed_file))
        {
          $renamed_file = dmOs::randomizeFileName($renamed_file);
        }
        rename($file, $renamed_file);
        $file = $renamed_file;
      }

      if (!array_key_exists(basename($file), $medias))
      {
        try
        {
          // File exists, asset does not exist: create asset
          dmDb::create('DmMedia', array(
            'dm_media_folder_id' => $this->get('id'),
            'file' => basename($file)
          ))->save();
        }
        catch(Exception $e)
        {
          dmDebug::kill($this, $medias, $file);
        }
      }
      else
      {
        // File exists, asset exists: do nothing
        unset($medias[basename($file)]);
      }
    }

    foreach ($medias as $name => $media)
    {
      // File does not exist, asset exists: delete asset
      $media->delete();
    }

    $dirs = sfFinder::type('dir')->maxdepth(0)->discard(".*")->ignore_version_control()->in($this->getFullPath());
    $folders = $this->getSubfoldersByName();

    foreach($dirs as $dir)
    {
      $dirName = basename($dir);
      /*
       * Sanitize folders name ( move folders )
       */
      if ($dirName != dmOs::sanitizeDirName($dirName))
      {
        $renamedDir = dmOs::join(dirname($dir), dmOs::sanitizeDirName($dirName));
        while(dir_exists($renamedDir))
        {
          $renamedDir = dmOs::randomizeDirName($renamedDir);
        }
        rename($dir, $renamedDir);
        $dir = $renamedDir;
        $dirName = basename($dir);
      }

      /*
       * Exists in fs, not in db
       */
      if (!array_key_exists($dirName, $folders))
      {
        $subfolderRelPath = trim(dmOs::join($this->get('rel_path'), $dirName), '/');

        if ($folder = $this->getTable()->findOneByRelPath($subfolderRelPath))
        {
          // folder exists in db but is not linked to its parent !
          $folder->getNode()->moveAsLastChildOf($this);
        }
        else
        {
          // dir exists in filesystem, not in database: create folder in database
          $folder = dmDb::create('DmMediaFolder', array(
            'rel_path' => $subfolderRelPath
          ));

          $folder->getNode()->insertAsLastChildOf($this);
        }
      }
      else
      {
        // dir exists in filesystem and database: do nothing
//        dmDebug::show('ok : '.$dirName, $dir, is_dir($dir), array_keys($folders));
        $folder = $folders[$dirName];
        unset($folders[$dirName]);
      }

      $folder->sync();
    }

    /*
     * Not unsetted folders
     * don't exist in fs
     */
    foreach ($folders as $folder)
    {
      $folder->getNode()->delete();
    }
    
    $this->refresh();
    $this->refreshRelated('Medias');

    $timer && $timer->addTime();
  }

  /*
   * Same as getNode()->getParent()->id
   * but will not hydrate full parent
   */
  public function getNodeParentId()
  {
    if ($this->getNode()->isRoot())
    {
      return null;
    }

    return $this->getTable()->createQuery('f')
    ->select('f.id as id')
    ->where('f.lft < ? AND f.rgt > ?', array($this->get('lft'), $this->get('rgt')))
    ->orderBy('f.rgt asc')
    ->limit(1)
    ->fetchValue();
  }

  /*
   * Common methods
   */

  public function __toString()
  {
    return $this->get('rel_path').' ('.$this->get('id').')';
  }


  /*
   * Override methods
   */

  public function save(Doctrine_Connection $conn = null)
  {
//    if(!$this->isFieldModified('rel_path'))
//    {
//      if($parent = $this->getNode()->getParent())
//      {
//        $this->setRelPath($parent->getRelPath().'/'.$this->getName());
//      }
//      else
//      {
//        $this->setRelPath('');
//      }
//    }

    // physical existence
    if (!$this->dirExists() && !$this->getNode()->isRoot())
    {
      if (!$this->create())
      {
        throw new dmException(sprintf('Impossible to create folder "%s"', $this->getFullPath()));
      }
    }

    return parent::save($conn);
  }

  public function delete(Doctrine_Connection $conn = null)
  {
    // Remove dir itself
    if(!$this->getNode()->isRoot() && $this->dirExists())
    {
      dmContext::getInstance()->getFilesystem()->deleteDir($this->fullPath);
    }

    return parent::delete($conn);
  }

}