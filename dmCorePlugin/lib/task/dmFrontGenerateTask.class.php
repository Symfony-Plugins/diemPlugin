<?php

class dmFrontGenerateTask extends dmContextTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'front'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('clear', null, sfCommandOption::PARAMETER_NONE, 'Recreate all module'),
      new sfCommandOption('only', null, sfCommandOption::PARAMETER_OPTIONAL, 'Just for this module', false),
    ));

    $this->namespace = 'dmFront';
    $this->name = 'generate';
    $this->briefDescription = 'Generates front modules';

    $this->detailedDescription = <<<EOF
Will create non-existing front modules
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $this->log('Generate front for modules');
    
    sfConfig::set('sf_debug', true);
    
    foreach($this->get('module_manager')->getProjectModules() as $moduleKey => $module)
    {
      $this->log(sprintf("Generate front for module %s", $moduleKey));

      $actionGenerator = new dmFrontActionGenerator($module, $this->dispatcher, $this->get('filesystem'));
      $actionGenerator->setFormatter($this->formatter);
      
      if (!$actionGenerator->execute())
      {
        $this->logBlock('Can NOT create actions for module '.$module, 'ERROR');
      }

      $componentGenerator = new dmFrontComponentGenerator($module, $this->dispatcher, $this->get('filesystem'));
      $componentGenerator->setFormatter($this->formatter);
      
      if (!$componentGenerator->execute())
      {
        $this->logBlock('Can NOT create components for module '.$module, 'ERROR');
      }

      $actionTemplateGenerator = new dmFrontActionTemplateGenerator($module, $this->dispatcher, $this->get('filesystem'));
      $actionTemplateGenerator->setFormatter($this->formatter);
      
      if (!$actionTemplateGenerator->execute())
      {
        $this->logBlock('Can NOT create action templates for module '.$module, 'ERROR');
      }
    }
    
    $this->generateLayoutTemplates();
  }
  
  protected function generateLayoutTemplates()
  {
    $this->logSection('diem', 'generate layout templates');
    $filesystem = $this->get('filesystem');
    
    foreach(dmDb::query('DmLayout l')->fetchRecords() as $layout)
    {
      $template = $layout->get('template');
      $templateFile = dmProject::rootify('apps/front/modules/dmFront/templates/'.$template.'Success.php');
      
      if(!file_exists($templateFile))
      {
        if ($filesystem->mkdir(dirname($templateFile)))
        {
          $filesystem->copy(
            dmOs::join(sfConfig::get('dm_front_dir'), 'modules/dmFront/templates/pageSuccess.php'),
            $templateFile
          );
        }
        else
        {
          $this->logBlock('Can NOT create layout template '.$template, 'ERROR');
        }
      }
    }
  }

}