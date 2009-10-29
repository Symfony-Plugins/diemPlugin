<?php

class dmInstallerProjectConfiguration extends dmProjectConfiguration
{

  public function setup()
  {
  	parent::setup();

    $this->setWebDirName(##DIEM_WEB_DIR_NAME##);
  }
  
  public static function activate($rootDir = null, sfEventDispatcher $dispatcher = null)
  {
    return self::$active = new self($rootDir, $dispatcher);
  }
  
}