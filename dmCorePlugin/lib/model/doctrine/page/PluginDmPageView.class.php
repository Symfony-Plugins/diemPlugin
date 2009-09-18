<?php

/**
 * PluginDmPageView
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5845 2009-06-09 07:36:57Z jwage $
 */
abstract class PluginDmPageView extends BaseDmPageView
{

  public function save(Doctrine_Connection $conn = null)
  {
    $return = parent::save($conn);

    if ($this->Area->isNew())
    {
      $this->Area->fromArray(array(
        'type' => 'content'
      ))->save();
    }
  }

}