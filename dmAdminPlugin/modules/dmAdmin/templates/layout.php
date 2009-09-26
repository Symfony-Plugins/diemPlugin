<?php

$timer = dmDebug::timerOrNull('dmAdmin/templates/layout');

$helper = $sf_context->get('layout_helper');

echo 
$helper->renderDoctype(),
$helper->renderHtmlTag(),

  "\n<head>\n",
    $helper->renderHttpMetas(),
    $helper->renderMetas(),
    $helper->renderStylesheets(),
    $helper->renderFavicon(),
  "\n</head>\n",
  
  $helper->renderBodyTag(),

    sprintf('<div id="dm_admin_content" class="module_%s action_%s">',
      $sf_request->getParameter('module'),
      $sf_request->getParameter('action')
    ),

      get_partial('dmInterface/breadCrumb'),

      get_partial('dmInterface/flash'),
  
      $sf_content,

    '</div>',
        
    $helper->renderEditBars(),
     
    $helper->renderJavascriptConfig(),
      
    $helper->renderJavascripts(),
      
  '</body>',
'</html>';

$timer && $timer->addTime();