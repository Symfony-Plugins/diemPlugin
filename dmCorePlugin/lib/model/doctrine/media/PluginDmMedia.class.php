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

	/*
	 * Returns last 4 numbers of filemtime
	 */
	public function getLittleMTime()
	{
		if ($this->hasCache('little_m_time'))
		{
			return $this->getCache('little_m_time');
		}

		return $this->setCache('little_m_time', $this->checkFileExists() ? substr(filemtime($this->getFullPath()), -4) : null);
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

		$backupMedia = dmDb::create('DmMedia')->setDmMediaFolder($backupFolder);

		$this->copyTo($backupMedia)->saveGet();
	}

	public function getBackupFolder()
	{
		return dmDb::table('DmMediaFolder')->findOneByRelPathOrCreate(
		dmOs::join($this->Folder->rel_path, 'backup')
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
		if ($dimensions = $this->getDimensions())
		{
			return substr($dimensions, 0, strpos($dimensions, 'x'));
		}
	}

	public function getHeight()
	{
		if ($dimensions = $this->getDimensions())
		{
			return substr($dimensions, strpos($dimensions, 'x')+1);
		}
	}

	public function isWritable()
	{
		return is_writable($this->getFullPath());
	}

	public function checkFileExists($orDelete = false)
	{
		if (!$this->file)
		{
			return false;
		}

		$exists = file_exists($this->fullPath);

		if (!$exists && $orDelete)
		{
			$this->delete();
		}

		return $exists;
	}

	public function __toString()
	{
		return $this->rel_path;
	}

	public function getFullPath()
	{
		return dmOs::join(sfConfig::get('sf_upload_dir'), $this->relPath);
	}

	public function getRelPath()
	{
		if ($this->hasCache('rel_path'))
		{
			return $this->getCache('rel_path');
		}

//		$fullPath = dmOs::join($this->Folder->fullPath, $this->file);
//
//    /*
//     * Let's check if file has changed
//     */
//    if (!file_exists($fullPath))
//    {
//      dmDebug::log(sprintf('Media sync error : media %s exists in db but not in fs', $fullPath));
//    }
//    elseif (strtotime($this->updated_at) < filemtime($fullPath))
//    {
//      /*
//       * File has been updated
//       * Let's update the record
//       */
//      $this->refreshFromFile()->save();
//
//      dmDebug::log(sprintf("%s refreshed %s %s : %s / %s",
//        $this, strtotime($this->updated_at), filemtime($fullPath), $media->size, $media->dimensions
//      ));
//    }

		return $this->setCache('rel_path', trim($this->Folder->relPath.'/'.$this->file, '/'));
	}

	public function getWebPath()
	{
		return sfConfig::get('sf_upload_dir_name').'/'.$this->relPath;
	}

	public function getFullWebPath()
	{
		return dm::getRequest()->getAbsoluteUrlRoot().'/'.$this->webPath;
	}

	public function isImage()
	{
		return strncmp($this->mime, 'image/', 6) === 0;
	}

	public function getImage()
	{
		if(!$this->isImage())
		{
			throw new dmException($this.' is not an image');
		}

		return new dmImage($this->fullPath, $this->mime);
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

    $this->file = dmOs::sanitizeFileName($file->getOriginalName());

    $file->save($this->fullPath);

		$this->refreshFromFile();

		return true;
	}

	/*
	 * @return DmMedia the new media with $toMedia values
	 */
	public function copyTo(DmMedia $toMedia)
	{
		$toMedia->file = $this->file;

		if (!@copy($this->getFullPath(), $toMedia->getFullPath()))
		{
			throw new dmException(sprintf(
        'Can not copy from %s to %s',
				$this->getFullPath(),
				$toMedia->getFullPath()
			));
		}

		return $toMedia
		->setLegend($this->getLegend())
		->setAuthor($this->getAuthor())
		->setCopyright($this->getCopyright())
		->setMime($this->getMime())
		->setSize($this->getSize())
		->setDimensions($this->getDimensions());
	}


	public function refreshFromFile()
	{
    $this->fromArray(array(
      'size' => filesize($this->fullPath),
      'mime' => dmOs::getFileMime($this->fullPath)
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
			dmFilesystem::get()->unlink($this->fullPath);
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
		// width x height - method _ quality _ littleMTime _ filename
		->name('/^[0-9]*x[0-9]*-[a-z]+_[0-9]*_[0-9]{4}_'.preg_quote($this->getFile(), '|').'$/')
		->maxdepth(0)
		->in(dmOs::join($this->Folder->getFullPath(), '.thumbs'));

		return dmFilesystem::get()->unlink($thumbs);
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