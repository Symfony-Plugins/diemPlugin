<?php

class BasedmFrontActions extends dmFrontBaseActions
{
  
  public function executePage(dmWebRequest $request)
  {
    $this->page = $this->getPageFromRequest($request);

    $this->secure();
     
    return $this->renderPage();
  }

  protected function getPageFromRequest(dmWebRequest $request)
  {
    if($request->hasParameter('dm_page'))
    {
      return $request->getParameter('dm_page');
    }

    $slug = $request->getParameter('slug');

    // find matching page_route for this slug
    $pageRoute = $this->getService('page_routing')->find($slug);

    if ($pageRoute)
    {
      $page = $pageRoute->getPage();

      // found a page on another culture
      if($pageRoute->getCulture() !== $this->getUser()->getCulture())
      {
        $this->getUser()->setCulture($pageRoute->getCulture());
      }
    }
    // the page does not exist
    else
    {
      // if page_not_found_handler suggest a redirection
      if ($redirectionUrl = $this->getService('page_not_found_handler')->getRedirection($slug))
      {
        return $this->redirect($redirectionUrl, 301);
      }

      // else use main.error404 page
      $page = dmDb::table('DmPage')->fetchError404();
    }

    return $page;
  }

  protected function secure()
  {
    $user = $this->getUser();
    
    if (
          // the site is not active and requires the site_view permission to be displayed
          (!dmConfig::get('site_active') && !$user->can('site_view'))
          // the page is not active and requires the site_view permission to be displayed
      ||  (!$this->page->get('is_active') && !$user->can('site_view'))
          // the page is secured and requires authentication to be displayed
      ||  ($this->page->get('is_secure') && !$user->isAuthenticated())
          // the page is secured and the user has not required credentials
      ||  ($this->page->get('is_secure') && $this->page->get('credentials') && !$user->can($this->page->get('credentials')))
    )
    {
      $this->getResponse()->setStatusCode($user->isAuthenticated() ? 403 : 401);
      
      // use main.signin page
      $this->page = dmDb::table('DmPage')->fetchSignin();
    }
  }
  
  public function executeError404(dmWebRequest $request)
  {
    $this->page = dmDb::table('DmPage')->fetchError404();
    
    return $this->renderPage();
  }
  
  protected function renderPage()
  {
    // share current page
    $this->context->setPage($this->page);
    
    if ($this->page->isModuleAction('main', 'error404'))
    {
      $this->response->setStatusCode(404); 
    }
    
    $template = $this->page->getPageView()->getLayout()->get('template');

    if (empty($template))
    {
      $template = 'page';
    }
    
    $this->setTemplate($template);
    
    $userLayout = sfConfig::get('sf_root_dir').'/apps/front/modules/dmFront/templates/layout';
    if (file_exists($userLayout.'.php'))
    {
      $this->setLayout($userLayout);
    }
    else
    {
      $this->setLayout(sfConfig::get('dm_front_dir').'/modules/dmFront/templates/layout');
    }
    
    $this->helper = $this->getService('page_helper');
    
    $this->isEditMode = $this->getUser()->getIsEditMode();
    
    $this->launchDirectActions();
    
    return sfView::SUCCESS;
  }
  
  public function executeToAdmin(dmWebRequest $request)
  {
    return $this->redirect($this->getHelper()->link('app:admin')->getHref());
  }

  /*
   * If an sfAction exists for the current page module.action,
   * it will be executed
   * If some sfActions exist for the current widgets module.action,
   * they will be executed to
   */
  protected function launchDirectActions()
  {
    $moduleManager = $this->context->getModuleManager();
    
    $moduleActions = array();

    // Add module action for page
    if($moduleManager->hasModule($this->page->get('module')))
    {
      $moduleActions[] = $this->page->getModuleAction().'Page';
    }
    
    // Find module/action for page widgets ( including layout )
    foreach($this->helper->getAreas() as $areaArray)
    {
      foreach($areaArray['Zones'] as $zoneArray)
      {
        foreach($zoneArray['Widgets'] as $widgetArray)
        {
          if($moduleManager->hasModule($widgetArray['module']))
          {
            $widgetModuleAction = $widgetArray['module'].'/'.$widgetArray['action'].'Widget';
            
            if(!in_array($widgetModuleAction, $moduleActions))
            {
              $moduleActions[] = $widgetModuleAction;
            }
          }
        }
      }
    }

    foreach($moduleActions as $moduleAction)
    {
      list($module, $action) = explode('/', $moduleAction);

      if ($this->getController()->actionExists($module, $action))
      {
        $actionToRun = 'execute'.ucfirst($action);
        
        try
        {
          $this->getController()->getAction($module, $action)->$actionToRun($this->getRequest());
        }
        catch(sfControllerException $e)
        {
          $this->getContext()->getLogger()->warning(sprintf('The %s/%s direct action does not exist', $module, $action));
        }
      }
    }
  }

  public function executeEditToggle(sfWebRequest $request)
  {
    $this->getUser()->setIsEditMode($request->getParameter('active'));
    
    return $this->renderText('ok');
  }
  
  public function executeShowToolBarToggle(sfWebRequest $request)
  {
    $this->getUser()->setShowToolBar($request->getParameter('active'));
    
    return $this->renderText('ok');
  }

  public function executeSelectTheme(sfWebRequest $request)
  {
    $this->forward404Unless(
      $theme = $this->getService('theme_manager')->getTheme($request->getParameter('theme')),
      sprintf('%s is not a valid theme.',
        $request->getParameter('theme')
      )
    );
    
    $this->getUser()->setTheme($theme);

    return $this->redirectBack();
  }
  
  public function executeSelectCulture(dmWebRequest $request)
  {
    $this->forward404Unless(
      $culture = $request->getParameter('culture'),
      'No culture specified'
    );

    $this->forward404Unless(
      $this->getI18n()->cultureExists($culture),
      sprintf('The %s culture does not exist', $culture)
    );

    $this->getUser()->setCulture($culture);
    
    if ($pageId = $request->getParameter('dm_cpi'))
    {
      $this->forward404Unless($page = dmDb::table('DmPage')->findOneByIdWithI18n($pageId));
      
      return $this->redirect($this->getHelper()->link($page)->getHref());
    }

    return $this->redirectBack();
  }
}