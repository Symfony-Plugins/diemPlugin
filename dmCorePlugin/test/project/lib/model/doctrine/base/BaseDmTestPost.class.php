<?php

/**
 * BaseDmTestPost
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $categ_id
 * @property integer $user_id
 * @property string $title
 * @property string $excerpt
 * @property clob $body
 * @property string $url
 * @property integer $image_id
 * @property integer $file_id
 * @property date $date
 * @property integer $created_by
 * @property boolean $is_active
 * @property DmTestCateg $Categ
 * @property DmUser $Author
 * @property DmMedia $Image
 * @property DmMedia $File
 * @property Doctrine_Collection $Tags
 * @property DmUser $CreatedBy
 * @property Doctrine_Collection $DmTestPostTag
 * @property Doctrine_Collection $Comments
 * 
 * @method integer             getCategId()       Returns the current record's "categ_id" value
 * @method integer             getUserId()        Returns the current record's "user_id" value
 * @method string              getTitle()         Returns the current record's "title" value
 * @method string              getExcerpt()       Returns the current record's "excerpt" value
 * @method clob                getBody()          Returns the current record's "body" value
 * @method string              getUrl()           Returns the current record's "url" value
 * @method integer             getImageId()       Returns the current record's "image_id" value
 * @method integer             getFileId()        Returns the current record's "file_id" value
 * @method date                getDate()          Returns the current record's "date" value
 * @method integer             getCreatedBy()     Returns the current record's "created_by" value
 * @method boolean             getIsActive()      Returns the current record's "is_active" value
 * @method DmTestCateg         getCateg()         Returns the current record's "Categ" value
 * @method DmUser              getAuthor()        Returns the current record's "Author" value
 * @method DmMedia             getImage()         Returns the current record's "Image" value
 * @method DmMedia             getFile()          Returns the current record's "File" value
 * @method Doctrine_Collection getTags()          Returns the current record's "Tags" collection
 * @method DmUser              getCreatedBy()     Returns the current record's "CreatedBy" value
 * @method Doctrine_Collection getDmTestPostTag() Returns the current record's "DmTestPostTag" collection
 * @method Doctrine_Collection getComments()      Returns the current record's "Comments" collection
 * @method DmTestPost          setCategId()       Sets the current record's "categ_id" value
 * @method DmTestPost          setUserId()        Sets the current record's "user_id" value
 * @method DmTestPost          setTitle()         Sets the current record's "title" value
 * @method DmTestPost          setExcerpt()       Sets the current record's "excerpt" value
 * @method DmTestPost          setBody()          Sets the current record's "body" value
 * @method DmTestPost          setUrl()           Sets the current record's "url" value
 * @method DmTestPost          setImageId()       Sets the current record's "image_id" value
 * @method DmTestPost          setFileId()        Sets the current record's "file_id" value
 * @method DmTestPost          setDate()          Sets the current record's "date" value
 * @method DmTestPost          setCreatedBy()     Sets the current record's "created_by" value
 * @method DmTestPost          setIsActive()      Sets the current record's "is_active" value
 * @method DmTestPost          setCateg()         Sets the current record's "Categ" value
 * @method DmTestPost          setAuthor()        Sets the current record's "Author" value
 * @method DmTestPost          setImage()         Sets the current record's "Image" value
 * @method DmTestPost          setFile()          Sets the current record's "File" value
 * @method DmTestPost          setTags()          Sets the current record's "Tags" collection
 * @method DmTestPost          setCreatedBy()     Sets the current record's "CreatedBy" value
 * @method DmTestPost          setDmTestPostTag() Sets the current record's "DmTestPostTag" collection
 * @method DmTestPost          setComments()      Sets the current record's "Comments" collection
 * 
 * @package    retest
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7021 2010-01-12 20:39:49Z lsmith $
 */
abstract class BaseDmTestPost extends myDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('dm_test_post');
        $this->hasColumn('categ_id', 'integer', null, array(
             'type' => 'integer',
             'notnull' => true,
             ));
        $this->hasColumn('user_id', 'integer', null, array(
             'type' => 'integer',
             'notnull' => true,
             ));
        $this->hasColumn('title', 'string', 255, array(
             'type' => 'string',
             'notnull' => true,
             'length' => '255',
             ));
        $this->hasColumn('excerpt', 'string', 800, array(
             'type' => 'string',
             'length' => '800',
             ));
        $this->hasColumn('body', 'clob', null, array(
             'type' => 'clob',
             'extra' => 'markdown',
             ));
        $this->hasColumn('url', 'string', 255, array(
             'type' => 'string',
             'extra' => 'link',
             'length' => '255',
             ));
        $this->hasColumn('image_id', 'integer', null, array(
             'type' => 'integer',
             ));
        $this->hasColumn('file_id', 'integer', null, array(
             'type' => 'integer',
             ));
        $this->hasColumn('date', 'date', null, array(
             'type' => 'date',
             'notnull' => true,
             ));
        $this->hasColumn('created_by', 'integer', null, array(
             'type' => 'integer',
             ));
        $this->hasColumn('is_active', 'boolean', null, array(
             'type' => 'boolean',
             'notnull' => true,
             'default' => true,
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('DmTestCateg as Categ', array(
             'local' => 'categ_id',
             'foreign' => 'id',
             'onDelete' => 'CASCADE'));

        $this->hasOne('DmUser as Author', array(
             'local' => 'user_id',
             'foreign' => 'id',
             'onDelete' => 'CASCADE'));

        $this->hasOne('DmMedia as Image', array(
             'local' => 'image_id',
             'foreign' => 'id'));

        $this->hasOne('DmMedia as File', array(
             'local' => 'file_id',
             'foreign' => 'id'));

        $this->hasMany('DmTestTag as Tags', array(
             'refClass' => 'DmTestPostTag',
             'local' => 'post_id',
             'foreign' => 'tag_id'));

        $this->hasOne('DmUser as CreatedBy', array(
             'local' => 'created_by',
             'foreign' => 'id',
             'onDelete' => 'SET NULL'));

        $this->hasMany('DmTestPostTag', array(
             'local' => 'id',
             'foreign' => 'post_id'));

        $this->hasMany('DmTestComment as Comments', array(
             'local' => 'id',
             'foreign' => 'post_id'));

        $timestampable0 = new Doctrine_Template_Timestampable();
        $sortable0 = new Doctrine_Template_Sortable();
        $dmgallery0 = new Doctrine_Template_DmGallery();
        $i18n0 = new Doctrine_Template_I18n(array(
             'fields' => 
             array(
              0 => 'title',
              1 => 'excerpt',
              2 => 'body',
              3 => 'url',
              4 => 'is_active',
             ),
             ));
        $dmversionable1 = new Doctrine_Template_DmVersionable(array(
             'fields' => NULL,
             ));
        $i18n0->addChild($dmversionable1);
        $this->actAs($timestampable0);
        $this->actAs($sortable0);
        $this->actAs($dmgallery0);
        $this->actAs($i18n0);
    }
}