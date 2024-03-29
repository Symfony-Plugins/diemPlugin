<?php

/**
 * PluginDmLayout
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5845 2009-06-09 07:36:57Z jwage $
 */
abstract class PluginDmLayout extends BaseDmLayout
{
  protected static
  $areaTypes = array('top', 'bottom', 'left', 'right');

  /**
   * How many pages use this layout?
   */
  public function getNbPages()
  {
    $nb = 0;
    
    foreach($this->PageViews as $pageView)
    {
      $nb += dmDb::query('DmPage p')
      ->where('p.module = ?', $pageView->module)
      ->andWhere('p.action = ?', $pageView->action)
      ->count();
    }

    return $nb;
  }

  public function duplicate()
  {
    $newLayout = $this->getTable()->create(array(
      'css_class' => $this->cssClass,
      'name' => $this->name
    ));
    
    do
    {
      $newLayout->set('name', $newLayout->get('name').' copy');
    }
    while($this->getTable()->createQuery('l')->where('l.name = ?', $newLayout->get('name'))->exists());
    
    foreach($this->get('Areas') as $area)
    {
      $newArea = $area->copy(false);
      
      foreach($area->get('Zones') as $zone)
      {
        $newZone = $zone->copy(false);
        
        foreach($zone->get('Widgets') as $widget)
        {
          $widget->get('Translation');
          $newZone->Widgets[] = $widget->copy(true);
        }
        
        $newArea->Zones[] = $newZone;
      }
      
      $newLayout->Areas[] = $newArea;
    }
    
    return $newLayout;
  }
  
  public static function getAreaTypes()
  {
    return self::$areaTypes;
  }

  public function getArea($type)
  {
    if (!in_array($type, self::getAreaTypes()))
    {
      throw new dmException(sprintf('%s is not a valid area type. These are : %s', $type, implode(', ', self::getAreaTypes())));
    }

    foreach($this->get('Areas') as $area)
    {
      if($area->get('type') == $type)
      {
        return $area;
      }
    }

    return null;
  }

  public function save(Doctrine_Connection $conn = null)
  {
    parent::save($conn);

    $this->checkMissingAreas();
  }
  
  protected function checkMissingAreas()
  {
    foreach(self::getAreaTypes() as $type)
    {
      if (!$this->getArea($type))
      {
        $this->get('Areas')->add(dmDb::create('DmArea', array(
          'dm_layout_id' => $this->get('id'),
          'type' => $type
        ))->saveGet());
      }
    }
  }

}