<?php

class BasedmWidgetActions extends dmFrontBaseActions
{

  /*
   * Quickly render a widget from Ajax
   * £link('+/dmWidget/render')->param('widget_id', $widgetId)->param('page_id', $pageId)
   */
  public function executeRender(dmWebRequest $request)
  {
    $this->forward404Unless(
      $widget = dmDb::table('DmWidget')->find($request->getParameter('widget_id')),
      'No widget found'
    );

    return $this->renderText(
      $this->getService('page_helper')->renderWidgetInner($widget->toArrayWithMappedValue())
    );
  }

  public function executeCopy(dmWebRequest $request)
  {
    $this->forward404Unless($widget = dmDb::table('DmWidget')->find($request->getParameter('id')));

    $this->getService('front_clipboard')->setWidget($widget);
  }

  public function executeEdit(dmWebRequest $request)
  {
    $this->forward404Unless($widget = dmDb::table('DmWidget')->find($request->getParameter('widget_id')));
  
    try
    {
      $widgetType = $this->getService('widget_type_manager')->getWidgetType($widget);
      $formClass = $widgetType->getFormClass();
      $form = new $formClass($widget);
    }
    catch(Exception $e)
    {
      return $this->renderError();
    }
    
    if ($request->isMethod('post') && $form->bindAndValid($request))
    {
      $form->updateWidget();

      if ($request->hasParameter('and_save'))
      {
        $widget->save();
        return $this->renderText('saved');
      }

      $widgetArray = $widget->toArrayWithMappedValue();

      $widgetRenderer = $this->getServiceContainer()
      ->setParameter('widget_renderer.widget', $widgetArray)
      ->getService('widget_renderer');

      return $this->renderAsync(array(
        'html'  => $this->renderEdit(new $formClass($widget), $widgetType).dmString::ENCODING_SEPARATOR.$this->getService('page_helper')->renderWidget($widgetArray),
        'js'    => $widgetRenderer->getJavascripts(),
        'css'   => $widgetRenderer->getStylesheets()
      ));
    }
    
    return $this->renderAsync(array(
      'html'  => $this->renderEdit($form, $widgetType),
      'js'    => $form->getJavascripts(),
      'css'   => $form->getStylesheets()
    ), true);
  }
  
  protected function renderError()
  {
    return $this->renderJson(array(
      'type' => 'error',
      'html' => sprintf('<p class="s16 s16_error">%s</p><div class="clearfix mt30"><a class="dm cancel close_dialog button mr10">%s</a><a class="dm delete button red" title="%s">%s</a></div>',
      $this->getService('i18n')->__('The widget can not be rendered because its type does not exist anymore.'),
      $this->getService('i18n')->__('Cancel'),
      $this->getService('i18n')->__('Delete this widget'),
      $this->getService('i18n')->__('Delete')
    )));
  }

  protected function renderEdit(dmWidgetBaseForm $form, dmWidgetType $widgetType)
  {
    $helper = $this->getHelper();

    $devActions= '';
    if ($this->getUser()->can('code_editor') && $form instanceof dmWidgetProjectForm)
    {
      if ($this->getUser()->can('code_editor_view'))
      {
        $templateDir = dmOs::join(sfConfig::get('sf_app_module_dir'), $form->getDmModule()->getKey(), 'templates', '_'.$form->getDmAction()->getKey().'.php');
        if (file_exists($templateDir))
        {
          $devActions .= '<a href="#'.dmProject::unRootify($templateDir).'" class="code_editor s16 s16_code_editor block">'.$this->getService('i18n')->__('Edit template code').'</a>';
        }
      }
      
      if ($this->getUser()->can('code_editor_controller'))
      {
        $componentDir = dmOs::join(sfConfig::get('sf_app_module_dir'), $form->getDmModule()->getKey(), 'actions/components.class.php');
        if (file_exists($componentDir))
        {
          $devActions .= '<a href="#'.dmProject::unRootify($componentDir).'" class="code_editor s16 s16_code_editor block">'.$this->getService('i18n')->__('Edit component code').'</a>';
        }
      }
    }

    if ($devActions)
    {
      $devActions = '<div class="code_editor_links">'.$devActions.'</div>';
    }

    $copyActions = '';
    if (false && $this->getUser()->can('widget_add'))
    {
      $copyActions = $helper->£('div.dm_cut_copy_actions',
        $helper->£link('+/dmWidget/cut')
        ->param('id', $form->getDmWidget()->get('id'))
        ->text($this->getService('i18n')->__('Cut'))
        ->set('.s16.s16_cut.dm_widget_cut').
        $helper->£link('+/dmWidget/copy')
        ->param('id', $form->getDmWidget()->get('id'))
        ->text($this->getService('i18n')->__('Copy'))
        ->set('.s16.s16_copy.dm_widget_copy')
      );
    }
    
    return $helper->£('div.dm.dm_widget_edit.'.dmString::underscore($widgetType->getFullKey()).'_form',
    array('json' => array('form_class' => $widgetType->getFullKey().'Form', 'form_name' => $form->getName())),
    $form->render('.dm_form.list.little').$devActions.$copyActions
    );
  }

  public function executeGetFull(sfWebRequest $request)
  {
    $this->forwardSecureUnless(
      $this->getUser()->can('widget_edit')
    );

    $this->forward404Unless(
      $widget = dmDb::table('DmWidget')->find($request->getParameter('widget_id'))
    );

    $widgetArray = $widget->toArrayWithMappedValue();

    $widgetRenderer = $this->getServiceContainer()
    ->setParameter('widget_renderer.widget', $widgetArray)
    ->getService('widget_renderer');

    return $this->renderAsync(array(
      'html'  => $this->getService('page_helper')->renderWidget($widgetArray),
      'css'   => $widgetRenderer->getStylesheets(),
      'js'    => $widgetRenderer->getJavascripts()
    ), true);
  }

  public function executeDelete(sfWebRequest $request)
  {
    $this->forwardSecureUnless(
      $this->getUser()->can('widget_delete')
    );

    $this->forward404Unless(
      $this->widget = dmDb::table('DmWidget')->find($request->getParameter('widget_id'))
    );

    $this->widget->delete();

    return $this->renderText('ok');
  }

  public function executeSort(sfWebRequest $request)
  {
    $this->forward404Unless(
      $zone = dmDb::table('DmZone')->find($request->getParameter('dm_zone')),
      'Can not find zone'
    );

    $this->forward404Unless(
      $widgetList = $request->getParameter('dm_widget'),
      'Missing widget list'
    );

    $this->sortWidgets($widgetList);

    return $this->renderText('ok');
  }

  public function executeAdd(sfWebRequest $request)
  {
    $this->forward404Unless(
      $toZone = dmDb::table('DmZone')->find($request->getParameter('to_dm_zone')),
      'Can not find to zone'
    );

    $this->forward404Unless(
      $widgetModule = $request->getParameter('mod'),
      'Can not find widget module'
    );

    $this->forward404Unless(
      $widgetAction = $request->getParameter('act'),
      'Can not find widget action'
    );

    $widgetType = $this->getService('widget_type_manager')->getWidgetType($widgetModule, $widgetAction);

    $formClass = $widgetType->getFormClass();
    $form = new $formClass($widgetType->getNewWidget());
    
    $form->removeCsrfProtection();

    $widget = dmDb::create('DmWidget', array(
      'module' => $widgetModule,
      'action' => $widgetAction,
      'dm_zone_id' => $toZone->id,
      'values' => $form->getDefaults()
    ))->saveGet();

    return $this->renderText($this->getService('page_helper')->renderWidget($widget->toArrayWithMappedValue(), true));
  }

  public function executeMove(sfWebRequest $request)
  {
    $this->forward404Unless(
      $widget = dmDb::table('DmWidget')->find($request->getParameter('moved_dm_widget')),
      'Can not find widget'
    );

    $this->forward404Unless(
      $toZone = dmDb::table('DmZone')->find($request->getParameter('to_dm_zone')),
      'Can not find to zone'
    );

    $this->forward404Unless(
      $widgetList = $request->getParameter('dm_widget'),
      'Missing widget list'
    );

    $widget->set('dm_zone_id', $toZone->id)->save();

    $this->sortWidgets($widgetList);

    return $this->renderText('ok');
  }

  protected function sortWidgets(array $widgetList)
  {
    $widgets = array();

    foreach($widgetList as $position => $widgetId)
    {
      $widgets[$widgetId] = $position+1;
    }

    try
    {
      dmDb::table('DmWidget')->doSort($widgets);
    }
    catch(Exception $e)
    {
      if ($this->getUser()->can('system'))
      {
        throw $e;
      }

      $this->getUser()->logError($this->context->getI18()->__('A problem occured when sorting the items'));
    }
  }
}