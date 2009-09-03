<?php

class dmWidgetContentTitleView extends dmWidgetPluginView
{

	public function configure()
	{
    parent::configure();

    $this->addRequiredVar(array('text', 'tag'));
	}
	
	public function getViewVars(array $vars = array())
	{
		$vars = parent::getViewVars($vars);
		
		$vars['text'] = nl2br($vars['text']);
		
		return $vars;
	}

	protected function doRender(array $vars)
	{
	  return sprintf('<%s>%s</%s>', $vars['tag'], $vars['text'], $vars['tag']);
	}
	
	public function toIndexableString(array $vars)
	{
		return $vars['text'];
	}
}