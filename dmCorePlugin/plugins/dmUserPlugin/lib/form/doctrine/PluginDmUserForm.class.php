<?php

/**
 * PluginDmUser form.
 *
 * @package    sfDoctrineGuardPlugin
 * @subpackage form
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: PluginDmUserForm.class.php 23536 2009-11-02 21:41:21Z Kris.Wallsmith $
 */
abstract class PluginDmUserForm extends BaseDmUserForm
{
  /**
   * @see sfForm
   */
  public function setup()
  {
    parent::setup();

    unset(
      $this['last_login'],
      $this['created_at'],
      $this['updated_at'],
      $this['salt'],
      $this['algorithm'],
      $this['groups_list'],
      $this['permissions_list'],
      $this['is_active'],
      $this['is_super_admin']
    );

    $this->widgetSchema['password'] = new sfWidgetFormInputPassword();
    $this->validatorSchema['password']->setOption('required', $this->object->isNew());
    $this->widgetSchema['password_again'] = new sfWidgetFormInputPassword();
    $this->validatorSchema['password_again'] = clone $this->validatorSchema['password'];

    $this->widgetSchema->moveField('password_again', 'after', 'password');

    $this->validatorSchema['username'] = new sfValidatorAnd(array(
      $this->validatorSchema['username'],
      new sfValidatorRegex(array('pattern' => '/^[\w\d\-\s@\.]+$/')),
    ));

    $this->changeToEmail('email');

    $this->mergePostValidator(new sfValidatorSchemaCompare('password', sfValidatorSchemaCompare::EQUAL, 'password_again', array(), array('invalid' => 'The two passwords must be the same.')));
  }
}
