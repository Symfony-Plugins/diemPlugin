<?php

/**
 * PluginDmMedia
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5845 2009-06-09 07:36:57Z jwage $
 */
abstract class PluginDmMedia extends BaseDmMedia
{
  protected
  $isRefreshed = false;

  public function getTimeHash()
  {
    return $this->checkFileExists() ? substr(md5(filemtime($this->getFullPath())), -5) : null;
  }

  /*
   * Store a copy of the file in backup folder
   */
  public function backup()
  {
    if(!$backupFolder = $this->getBackupFolder())
    {
      throw new dmException(sprintf('Can not create backup folder for %s', $this));
    }

    $this->copyTo(dmDb::create('DmMedia')->set('Folder', $backupFolder))->save();
  }

  public function getBackupFolder()
  {
    return dmDb::table('DmMediaFolder')->findOneByRelPathOrCreate(
      $this->get('Folder')->get('rel_path').'/backup'
    );
  }

  public function getForeigns()
  {
    if ($this->hasCache('foreigns'))
    {
      return $this->getCache('foreigns');
    }

    $foreigns = array();

    foreach($this->getTable()->getRelationHolder()->getForeigns() as $foreignRelation)
    {
      if ($foreign = $relation->fetchRelatedFor($this))
      {
        $foreigns[] = $foreign;
      }
    }

    return $this->setCache('foreign', $foreigns);
  }

  public function getNbForeigns()
  {
    return count($this->getNbForeigns());
  }

  public function getDimensions()
  {
    if (!$this->isImage() || !$this->checkFileExists())
    {
      return false;
    }

    if (!$dimensions = $this->_get('dimensions'))
    {
      $infos = getimagesize($this->getFullPath());
      $this->_set('dimensions', $dimensions = $infos[0]."x".$infos[1], false)->save();
    }

    return $dimensions;
  }

  public function getWidth()
  {
    if($this->hasCache('width'))
    {
      return $this->getCache('width');
    }
    
    return $this->setCache('width', ($dimensions = $this->get('dimensions')) ? substr($dimensions, 0, strpos($dimensions, 'x')) : null);
  }

  public function getHeight()
  {
    if($this->hasCache('height'))
    {
      return $this->getCache('height');
    }
    
    return $this->setCache('height', ($dimensions = $this->get('dimensions')) ? substr($dimensions, strpos($dimensions, 'x')+1) : null);
  }

  public function isWritable()
  {
    return is_writable($this->getFullPath());
  }

  public function checkFileExists($orDelete = false)
  {
    if (!$this->get('file'))
    {
      return false;
    }

    $exists = file_exists($this->getFullPath());

    if (false === $exists && $orDelete && $this->exists())
    {
      $this->delete();
    }

    return $exists;
  }

  public function __toString()
  {
    return $this->getRelPath();
  }

  public function getFullPath()
  {
    return dmOs::join(sfConfig::get('sf_upload_dir'), $this->getRelPath());
  }

  public function getRelPath()
  {
    if ($this->hasCache('rel_path'))
    {
      return $this->getCache('rel_path');
    }

    return $this->setCache('rel_path', trim($this->get('Folder')->get('rel_path').'/'.$this->get('file'), '/'));
  }

  public function getWebPath()
  {
    return sfConfig::get('sf_upload_dir_name').'/'.$this->getRelPath();
  }

  public function getFullWebPath()
  {
    return dm::getRequest()->getAbsoluteUrlRoot().'/'.$this->getWebPath();
  }

  public function isImage()
  {
    return strncmp($this->mime, 'image/', 6) === 0;
  }

  
  /*
   * @return dmImage
   */
  public function getImage()
  {
    if(!$this->isImage())
    {
      throw new dmException($this.' is not an image');
    }

    return new dmImage($this->getFullPath(), $this->get('mime'));
  }

  /**
   * Physically creates asset
   *
   * @param string $asset_path path to the asset original file
   * @param bool $move do move or just copy ?
   */
  public function create(sfValidatedFile $file)
  {
    $this->file = dmOs::sanitizeFileName($file->getOriginalName());

    $this->clearCache();

    $file->save($this->fullPath);

    $this->refreshFromFile();

    return $this;
  }

  /**
   * Physically replaces asset
   */
  public function replaceFile(sfValidatedFile $file)
  {
    $this->destroy();
    
    return $this->create($file);
  }

  /*
   * @return DmMedia the new media with $toMedia values
   */
  public function copyTo(DmMedia $toMedia)
  {
    $toMedia->set('file', $this->get('file'));

    if (!copy($this->getFullPath(), $toMedia->getFullPath()))
    {
      throw new dmException(sprintf(
        'Can not copy from %s to %s',
        $this->getFullPath(),
        $toMedia->getFullPath()
      ));
    }
    
    $toMedia->fromArray(array(
      'legend'      => $this->get('legend'),
      'author'      => $this->get('author'),
      'license'     => $this->get('license'),
      'mime'        => $this->get('mime'),
      'dimensions'  => $this->get('dimensions')
    ));
    
    return $toMedia;
  }


  public function refreshFromFile()
  {
    $this->fromArray(array(
      'size' => filesize($this->getFullPath()),
      'mime' => dmOs::getFileMime($this->getFullPath())
    ));
    /*
     * Important to set dimensions without reload data
     */
    $this->set('dimensions', null, false);
    $this->clearCache();

    return $this;
  }

  /**
   * Physically remove assets
   */
  protected function destroy()
  {
    if ($this->isImage())
    {
      $this->destroyThumbnails();
    }

    if ($this->checkFileExists())
    {
//      dmDebug::kill('unlink '.$this->fullPath, $this);
      self::$serviceContainer->getService('filesystem')->unlink($this->getFullPath());
    }

    return !$this->checkFileExists();
  }

  public function destroyThumbnails()
  {
    if (!$this->isImage())
    {
      return true;
    }
    
    $thumbs = sfFinder::type('file')
    ->name(dmOs::getFileWithoutExtension($this->get('file')).'*')
    ->maxdepth(0)
    ->in(dmOs::join($this->Folder->getFullPath(), '.thumbs'));

    return self::$serviceContainer->getService('filesystem')->unlink($thumbs);
  }


  public function save(Doctrine_Connection $conn = null)
  {
    if (!$this->file)
    {
      throw new dmException('Trying to save DmMedia with empty file field');
    }

    if (!$this->checkFileExists())
    {
      throw new dmException(sprintf('Trying to save DmMedia with no existing file : %s', $this->file));
    }

    /*
     * If this media is new, and shares its name with another media in the same folder,
     * this media is not saved and the other one is updated with this media's value
     */
    if($this->isNew())
    {
      if($sameMedia = $this->getTable()->findOneByFileAndDmMediaFolderId($this->file, $this->dm_media_folder_id))
      {
        return $sameMedia
        ->setLegend($this->getLegend())
        ->setAuthor($this->getAuthor())
        ->setLicense($this->getLicense())
        ->setMime($this->getMime())
        ->setSize($this->getSize())
        ->set('dimensions', $this->getDimensions(), false)
        ->save($conn);
      }
      else
      {
        $this->refreshFromFile();
      }
    }

    return parent::save($conn);
  }

  public function delete(Doctrine_Connection $conn = null)
  {
    if (!$this->destroy())
    {
      throw new dmException('Can not delete '.$this->getFullPath());
    }

    return parent::delete($conn);
  }

}