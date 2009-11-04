<?php

class dmLoremizeTask extends dmContextTask
{

  /**
   * @see sfTask
   */
  protected function configure()
  {
    parent::configure();

    $this->addOptions(array(
      new sfCommandOption('module', null, sfCommandOption::PARAMETER_REQUIRED, 'The module name'),
      new sfCommandOption('nb', null, sfCommandOption::PARAMETER_OPTIONAL, 'nb records to create', 20),
    ));

    $this->namespace = 'dm';
    $this->name = 'loremize';
    $this->briefDescription = 'Create random records for a model';

    $this->detailedDescription = <<<EOF
Create random records for a model
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $this->log('dmLoremize::execute');
    
    $this->withDatabase();

    if ($moduleName = $options['module'])
    {
      $loremizer = new dmModuleLoremizer($this->dispatcher);
      $loremizer->loremize($this->get('module_manager')->getModule($moduleName), $options['nb']);
    }
    else
    {
      $loremizer = new dmDatabaseLoremizer($this->dispatcher);
      $loremizer->loremize($options['nb']);
    }
    
    $this->logSection('Loremize', 'Database successfully loremized');
  }

}