[?php use_stylesheets_for_form($form) ?]
[?php use_javascripts_for_form($form) ?]

<div class="sf_admin_form">

  [?php $formActions = get_partial('<?php echo $this->getModuleName() ?>/dm_form_action_bar', array('<?php echo $this->getSingularName() ?>' => $<?php echo $this->getSingularName() ?>, 'form' => $form, 'configuration' => $configuration, 'helper' => $helper, 'nearRecords' => $nearRecords)); ?]

  [?php echo form_tag_for($form, '@<?php echo $this->params['route_prefix'] ?>') ?]

  <div class="dm_form_action_bar dm_form_action_bar_top clearfix">
    [?php echo $formActions; ?]
  </div>

    <div class="sf_admin_form_inner ui-widget ui-accordion">

    [?php echo $form->renderHiddenFields(false) ?]

    [?php if ($form->hasGlobalErrors()): ?]
      [?php echo $form->renderGlobalErrors() ?]
    [?php endif; ?]

    [?php foreach ($configuration->getFormFields($form, $form->isNew() ? 'new' : 'edit') as $fieldset => $fields): ?]
      [?php include_partial('<?php echo $this->getModuleName() ?>/form_fieldset', array('<?php echo $this->getSingularName() ?>' => $<?php echo $this->getSingularName() ?>, 'form' => $form, 'fields' => $fields, 'fieldset' => $fieldset)) ?]
    [?php endforeach; ?]

    </div>

  <div class="dm_form_action_bar dm_form_action_bar_bottom clearfix">
    [?php echo $formActions; ?]
  </div>

  </form>

</div>