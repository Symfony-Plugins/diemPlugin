<?php

/**
 * PluginDmWidget
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5845 2009-06-09 07:36:57Z jwage $
 */
abstract class PluginDmWidget extends BaseDmWidget
{
  /**
   * Add the i18n or fallback i18n value to the widget array
   */
  public function toArrayWithMappedValue()
  {
    $array = $this->toArray(false);
    
    $array['value'] = $this->_getI18n('value');
    
    return $array;
  }
  
  public function getValues()
  {
    return json_decode($this->get('value'), true);
  }

  public function setValues($v)
  {
    $this->set('value', json_encode($v));
  }
  
  public function getModuleAction()
  {
    return $this->get('module').'/'.$this->get('action');
  }

  public function __toString()
  {
    return $this->getModuleAction();
  }
}