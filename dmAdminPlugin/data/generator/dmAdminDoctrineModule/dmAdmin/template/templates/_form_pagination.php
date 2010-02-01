<div class="dm_form_pagination">
  [?php
  if ($nearRecords)
  {
    if ($previousRecord = $nearRecords['prev'])
    {
      echo _link($helper->getRouteArrayForAction('edit', $previousRecord))->textTitle($previousRecord)->set('.previous.s16block.s16_previous');
    }
    else
    {
      echo _tag('span.disabled.s16block.s16_previous');
    }

    if ($nextRecord = $nearRecords['next'])
    {
      echo _link($helper->getRouteArrayForAction('edit', $nextRecord))->textTitle($nextRecord)->set('.next.s16block.s16_next');
    }
    else
    {
      echo _tag('span.disabled.s16block.s16_next');
    }
  }
  ?]
</div>