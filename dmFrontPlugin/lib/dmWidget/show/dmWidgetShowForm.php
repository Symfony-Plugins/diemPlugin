<?php

class dmWidgetShowForm extends dmWidgetProjectModelForm
{

  public function configure()
  {
    /*
     * Record id
     */
    $this->widgetSchema['recordId']     = new sfWidgetFormDoctrineChoice(array(
      'model'     => $this->dmModule->getModel(),
      'add_empty' => $this->allowAutoRecordId()
      ? sprintf('(%s) %s', dm::getI18n()->__('automatic'), $this->getAutoRecord()->__toString())
      : false
    ));
    $this->widgetSchema['recordId']->setLabel($this->getDmModule()->getName());

    $this->validatorSchema['recordId']  = new sfValidatorDoctrineChoice(array(
      'model'     => $this->dmModule->getModel(),
      'required'  => !$this->allowAutoRecordId()
    ));

    $this->setDefaults($this->getDefaultsFromLastUpdated());

    if (!$this->allowAutoRecordId() && !$this->getDefault('recordId'))
    {
      $this->setDefault('recordId', dmArray::first(array_keys($this->widgetSchema['recordId']->getChoices())));
    }
    
    parent::configure();
  }

  protected function allowAutoRecordId()
  {
    return $this->getPage() ? $this->getPage()->getDmModule()->knows($this->dmModule) : false;
  }

  protected function getAutoRecord()
  {
    return $this->getPage() ? $this->getPage()->getRecord()->getAncestorRecord($this->dmModule->getModel()) : false;
  }

  protected function getFirstDefaults()
  {
    return array_merge(parent::getFirstDefaults(), array(
    'recordId' => null
    ));
  }
}