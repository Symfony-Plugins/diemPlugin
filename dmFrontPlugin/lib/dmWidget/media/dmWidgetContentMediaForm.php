<?php

class dmWidgetContentMediaForm extends dmWidgetPluginForm
{

	protected static $methods = array(
	  'fit' => 'Fit',
	  'center' => 'Center',
	  'scale' => 'Scale',
    'inflate' => 'Inflate'
	);

	protected static
	$dmMediaFolder;

	public function configure()
	{
    if($this->getValueOrDefault('mediaId'))
    {
      $media = dmDb::table('DmMedia')->find($this->getDefault('mediaId'));
    }
    else
    {
    	$media = null;
    }

    $this->widgetSchema['mediaName'] = new sfWidgetFormInputText(array(), array(
      'read-only' => true,
      'class' => 'dm_media_receiver'
    ));
    $this->validatorSchema['mediaName'] = new sfValidatorPass();

    $this->widgetSchema['mediaId'] = new sfWidgetFormInputHidden(array());
    $this->validatorSchema['mediaId'] = new sfValidatorInteger(array(
      'required' => false
    ));

    $this->widgetSchema['file'] = new sfWidgetFormDmInputFile();
    $this->validatorSchema['file'] = new sfValidatorFile(array(
      'required' => false
    ));


    $this->widgetSchema['legend'] = new sfWidgetFormInputText();
    $this->validatorSchema['legend'] = new sfValidatorString(array(
      'required' => false
    ));

    $this->widgetSchema['width'] = new sfWidgetFormInputText(array(), array('size' => 5));
    $this->validatorSchema['width'] = new dmValidatorCssSize(array(
      'required' => false
    ));

    $this->widgetSchema['height'] = new sfWidgetFormInputText(array(), array('size' => 5));
    $this->validatorSchema['height'] = new dmValidatorCssSize(array(
      'required' => false
    ));

    $methods = dm::getI18n()->translateArray(self::$methods);
    $this->widgetSchema['method'] = new sfWidgetFormSelect(array(
      'choices' => $methods
    ));
    $this->validatorSchema['method'] = new sfValidatorChoice(array(
      'choices' => array_keys($methods),
      'required' => false
    ));
    if (!$this->getDefault('method'))
    {
      $this->setDefault('method', sfConfig::get('dm_image_resize', 'center'));
    }

    $this->widgetSchema['background'] = new sfWidgetFormInputText(array(), array('size' =>7));
    $this->validatorSchema['background'] = new dmValidatorCssColor(array(
      'required' => false
    ));

    $this->widgetSchema->setLabel('mediaName', 'Use media');
    $this->widgetSchema->setLabel('file', 'Or upload a file');

    if ($media)
    {
	    $this->setDefault('mediaName', $media->getRelPath());
//	    $this->setDefault('legend', $media->getLegend());
    }
    else
    {
    	$this->setDefault('mediaName', dm::getI18n()->__('Drag & Drop a media here'));
    }

    $this->validatorSchema->setPostValidator(
      new sfValidatorCallback(array('callback' => array($this, 'checkMediaSource')))
    );

    parent::configure();
	}

	public function checkMediaSource($validator, $values)
	{
    if (!$values['mediaId'] && !$values['file'])
    {
      throw new sfValidatorError($validator, 'You must use a media or upload a file');
    }

    return $values;
	}


  protected function renderContent($attributes)
  {
    return dmContext::getInstance()->getHelper()->renderPartial('dmWidget', 'forms/dmWidgetContentMedia', array(
      'form' => $this,
      'hasMedia' => (boolean) $this->getValueOrDefault('mediaId')
    ));
  }

  public function getWidgetValues()
  {
  	$values = $this->getValues();

    if ($file = $values['file'])
    {
    	$folder = dmDb::table('DmMediaFolder')->findOneByRelPathOrCreate('widget');

    	$media = dmDb::table('DmMedia')->findOneByFileAndDmMediaFolderId(array(
    	  dmOs::sanitizeFileName($file->getOriginalName()),
    	  $folder->id
    	));

    	if (!$media)
    	{
    		$media = dmDb::create('DmMedia', array(
    		  'dm_media_folder_id' => $folder->id
    		))
    		->create($file)
        ->saveGet();
    	}

      $values['mediaId'] = $media->getId();
    }

//  	if (!empty($values['legend']))
//  	{
//      if ($media = dmDb::table('DmMedia')->find($values['mediaId']))
//      {
//        $media->setLegend($values['legend'])->save();
//      }
//  	}

      if($media = dmDb::table('DmMedia')->find($values['mediaId']))
      {
      	if ($media->isImage())
      	{
      		$widgetWidth = dm::getRequest()->getParameter('dm_widget_width');
          if (empty($values['width']))
          {
          	if ($widgetWidth)
          	{
              $values['width'] = $widgetWidth;
              $values['height'] = (int) ($media->height * ($widgetWidth / $media->width));
          	}
          	else
          	{
              $values['width'] = $media->width;
          	}
          }
          elseif (empty($values['height']))
          {
            $values['height'] = (int) ($media->height * ($widgetWidth / $media->width));
          }
      	}
      }

    unset($values['mediaName']);
    unset($values['file']);

    $values['background'] = trim($values['background']);
    
    if (empty($values['method']))
    {
      $values['method'] = sfConfig::get('dm_image_resize', 'center');
    }

    return $values;
  }
}