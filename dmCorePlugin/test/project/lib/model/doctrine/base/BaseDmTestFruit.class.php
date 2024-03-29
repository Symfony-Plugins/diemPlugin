<?php

/**
 * BaseDmTestFruit
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property string $title
 * 
 * @method string      getTitle() Returns the current record's "title" value
 * @method DmTestFruit setTitle() Sets the current record's "title" value
 * 
 * @package    retest
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7021 2010-01-12 20:39:49Z lsmith $
 */
abstract class BaseDmTestFruit extends myDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('dm_test_fruit');
        $this->hasColumn('title', 'string', 255, array(
             'type' => 'string',
             'length' => '255',
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $dmtaggable0 = new Doctrine_Template_DmTaggable();
        $this->actAs($dmtaggable0);
    }
}