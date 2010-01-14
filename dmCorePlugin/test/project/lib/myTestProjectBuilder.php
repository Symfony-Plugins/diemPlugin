<?php

class myTestProjectBuilder
{
  protected
  $context;

  public function __construct(dmContext $context)
  {
    $this->context = $context;
  }

  public function execute()
  {
    $this->clearWidgets();

    $this->loremize();

    $this->addRecords();

    $this->context->get('page_tree_watcher')->synchronizePages();
    $this->context->get('page_tree_watcher')->synchronizeSeo();

    dmDb::table('DmPage')->clear();

    $this->changeHomeLayout();
    
    $this->addLoginForm();

    $this->addBreadCrumb();

    $this->addNavigation();

    $this->addH1();
  }

  protected function addRecords()
  {
    dmDb::table('DmTestDomain')->create(array(
      'title' => 'Domain 1',
      'Categs' => array(
        array(
          'title' => 'Categ 1',
          'Posts' => array(
            array(
              'title' => 'Post 1',
              'user_id' => dmDb::table('DmUser')->findOne()->id,
              'date' => '2010-01-12',
              'url' => 'http://diem-project.org',
              'body' => 'Post 1 body',
              'excerpt' => 'Post 1 excerpt',
              'Tags' => array(
                array(
                  'name' => 'Tag 1',
                  'slug' => 'tag-1'
                )
              ),
              'Comments' => array(
                array(
                  'author' => 'Author 1',
                  'body' => 'Comment 1'
                )
              )
            )
          )
        )
      )
    ))->save();
  }

  protected function clearWidgets()
  {
    dmDb::query('DmWidget w')->delete()->execute();
  }

  protected function changeHomeLayout()
  {
    $globalLayout = dmDb::table('DmLayout')->findOneByName('Global');
    $globalLayout->cssClass = 'global_layout';
    $globalLayout->save();

    $root = dmDb::table('DmPage')->getTree()->fetchRoot();
    $root->PageView->Layout = $globalLayout;
    $root->PageView->save();
  }

  protected function addNavigation()
  {
    // domain left menu
    $this->createWidget(
      'dmTestDomain/list',
      array(
        'orderField'  => 'position',
        'orderType'   => 'asc',
        'maxPerPage'  => 0,
        'navTop'      => false,
        'navBottom'   => false
      ),
      dmDb::table('DmLayout')->findOneByName('Global')->getArea('left')->Zones[0]
    )->save();

    // domains link
    $this->createWidget(
      'dmWidgetContent/link',
      array('href' => 'page:'.dmDb::table('DmPage')->findOneByModuleAndAction('dmTestDomain', 'list')->id),
      dmDb::table('DmLayout')->findOneByName('Global')->getArea('left')->Zones[0]
    )->save();

    // tags link
    $this->createWidget(
      'dmWidgetContent/link',
      array('href' => 'page:'.dmDb::table('DmPage')->findOneByModuleAndAction('dmTestTag', 'list')->id),
      dmDb::table('DmLayout')->findOneByName('Global')->getArea('left')->Zones[0]
    )->save();

    // domains list
    $this->createWidget(
      'dmTestDomain/list',
      array(
        'orderField'  => 'position',
        'orderType'   => 'asc',
        'maxPerPage'  => 0,
        'navTop'      => true,
        'navBottom'   => true
      ),
      dmDb::table('DmPage')->findOneByModuleAndAction('dmTestDomain', 'list')->PageView->Area->Zones[0]
    )->save();

    $this->createWidget(
      'dmWidgetContent/title',
      array('text' => 'Domains', 'tag' => 'h1'),
      dmDb::table('DmPage')->findOneByModuleAndAction('dmTestDomain', 'list')->PageView->Area->Zones[0]
    )->save();

    $this->context->setPage($page = dmDb::table('DmPage')->findOneByModuleAndAction('dmTestDomain', 'show'));

    // categ list
    $this->createWidget(
      'dmTestCateg/listByDomain',
      array(
        'orderField'  => 'position',
        'orderType'   => 'asc',
        'maxPerPage'  => 0,
        'navTop'      => true,
        'navBottom'   => true
      ),
      $page->PageView->Area->Zones[0]
    )->save();
    
    // domain show
    $this->createWidget(
      'dmTestDomain/show',
      array(),
      $page->PageView->Area->Zones[0]
    )->save();

    $this->context->setPage($page = dmDb::table('DmPage')->findOneByModuleAndAction('dmTestCateg', 'show'));

    // post list
    $this->createWidget(
      'dmTestPost/listByCateg',
      array(
        'orderField'  => 'position',
        'orderType'   => 'asc',
        'maxPerPage'  => 0,
        'navTop'      => true,
        'navBottom'   => true
      ),
      $page->PageView->Area->Zones[0]
    )->save();
    
    // categ show
    $this->createWidget(
      'dmTestCateg/show',
      array(),
      $page->PageView->Area->Zones[0]
    )->save();

    $this->context->setPage($page = dmDb::table('DmPage')->findOneByModuleAndAction('dmTestPost', 'show'));

    // comment list
    $this->createWidget(
      'dmTestComment/listByPost',
      array(
        'orderField'  => 'created_at',
        'orderType'   => 'asc',
        'maxPerPage'  => 0,
        'navTop'      => true,
        'navBottom'   => true
      ),
      $page->PageView->Area->Zones[0]
    )->save();

    // comment form
    $this->createWidget(
      'dmTestComment/form',
      array(),
      $page->PageView->Area->Zones[0]
    )->save();

    // tag list
    $this->createWidget(
      'dmTestTag/listByPost',
      array(
        'orderField'  => 'created_at',
        'orderType'   => 'asc',
        'maxPerPage'  => 0,
        'navTop'      => true,
        'navBottom'   => true
      ),
      $page->PageView->Area->Zones[0]
    )->save();

    // post show
    $this->createWidget(
      'dmTestPost/show',
      array(),
      $page->PageView->Area->Zones[0]
    )->save();

    // tag list
    $this->createWidget(
      'dmTestTag/list',
      array(
        'orderField'  => 'created_at',
        'orderType'   => 'asc',
        'maxPerPage'  => 0,
        'navTop'      => true,
        'navBottom'   => true
      ),
      dmDb::table('DmPage')->findOneByModuleAndAction('dmTestTag', 'list')->PageView->Area->Zones[0]
    )->save();

    $this->context->setPage($page = dmDb::table('DmPage')->findOneByModuleAndAction('dmTestTag', 'show'));

    // post list
    $this->createWidget(
      'dmTestPost/listByTag',
      array(
        'orderField'  => 'position',
        'orderType'   => 'asc',
        'maxPerPage'  => 0,
        'navTop'      => true,
        'navBottom'   => true
      ),
      $page->PageView->Area->Zones[0]
    )->save();

    // tag show
    $this->createWidget(
      'dmTestTag/show',
      array(),
      $page->PageView->Area->Zones[0]
    )->save();
  }

  protected function loremize()
  {
    foreach($this->context->getModuleManager()->getModulesWithModel() as $module)
    {
      if('DmUser' == $module->getModel()) continue;
      
      $module->getTable()->createQuery()->delete()->execute();
    }
    
    $this->context->get('project_loremizer')->execute(5);

    foreach(array('DmTestDomain' => 9, 'DmTestCateg' => 9, 'DmTestPost' => 19, 'DmTestTag' => 39, 'DmTestComment' => 39) as $model => $nb)
    {
      $this->context->get('table_loremizer')->execute(dmDb::table($model), $nb);

      if(dmDb::table($model)->hasField('is_active'))
      {
        foreach(dmDb::table($model)->createQuery('r')->limit(ceil($nb/2))->fetchRecords() as $record)
        {
          if(!$record->isActive)
          {
            $record->isActive = true;
            $record->save();
          }
        }
      }
    }
  }

  protected function addBreadCrumb()
  {
    $this->createWidget(
      'dmWidgetNavigation/breadCrumb',
      array('includeCurrent' => true),
      dmDb::table('DmPage')->findOneByModuleAndAction('main', 'login')->PageView->Layout->getArea('top')->Zones[0]
    )->save();
  }

  protected function addH1()
  {
    $this->createWidget(
      'dmWidgetContent/link',
      array('href' => 'page:1'),
      dmDb::table('DmPage')->findOneByModuleAndAction('main', 'login')->PageView->Layout->getArea('top')->Zones[0]
    )->save();

    $this->createWidget(
      'dmWidgetContent/title',
      array('text' => 'Home H1', 'tag' => 'h1'),
      dmDb::table('DmPage')->findOneByModuleAndAction('main', 'root')->PageView->Area->Zones[0]
    )->save();
  }

  protected function addLoginForm()
  {
    $this->createWidget(
      'main/loginForm',
      array(),
      dmDb::table('DmPage')->findOneByModuleAndAction('main', 'login')->PageView->Area->Zones[0]
    )->save();
  }

  protected function createWidget($moduleAction, array $data, DmZone $zone)
  {
    list($module, $action) = explode('/', $moduleAction);
    
    $widgetType = $this->context->get('widget_type_manager')->getWidgetType($module, $action);

    $formClass = $widgetType->getOption('form_class');
    $form = new $formClass(dmDb::create('DmWidget', array(
      'module' => $module,
      'action' => $action,
      'value'  => '[]',
      'dm_zone_id' => $zone->id
    )));
    $form->removeCsrfProtection();

    $form->bind(array_merge($form->getDefaults(), $data), array());

    if(!$form->isValid())
    {
      throw $form->getErrorSchema();
    }

    return $form->updateWidget();
  }

}