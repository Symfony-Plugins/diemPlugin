<div class="dm_module_search">
  [?php
    $currentSearch = $sf_user->getAppliedSearchOnModule('<?php echo $this->getModuleName(); ?>');
    printf('<form action="%s" method="get">', url_for1(array('sf_route' => '<?php echo $this->getModule()->getUnderscore(); ?>')));
    printf('<input id="dm_module_search_input" class="ui-corner-left" type="text" title="%s" value="%s" name="search"/>',
      __('Search in %1%', array('%1%' => __("<?php echo $this->getModule()->getPlural(); ?>"))),
      $currentSearch
    );
    printf('<input type="submit" class="dm_submit ui-corner-right" value="%s" />', __('Search'));
    if ($currentSearch)
    {
      printf('<a href="%s" class="s16 s16_cross ml5 mr5" title="%s">&nbsp;</a>', url_for1(array('sf_route' => '<?php echo $this->getModule()->getUnderscore(); ?>')).'?search=', __('Cancel search'));
    }
  ?]
  </form>
  
</div>