<?php

if (!defined('STDIN'))
{
  die('This tool is designed to be run from the command line.');
}

chdir(dirname(__FILE__).'/dmCorePlugin/test/project');

$_SERVER['argv'] = array(
  $_SERVER['argv'][0],
  'test:unit'
);

require_once dirname(__FILE__).'/symfony/lib/command/cli.php';

return 0;