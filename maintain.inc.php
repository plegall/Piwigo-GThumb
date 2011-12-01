<?php

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function plugin_install()
{
  include(dirname(__FILE__).'/config_default.inc.php');

  $query = '
INSERT INTO ' . CONFIG_TABLE . ' (param,value,comment)
VALUES ("GThumb" , "'.addslashes(serialize($config_default)).'" , "GThumb plugin parameters");';
  pwg_query($query);
}

function plugin_uninstall()
{
  include_once(dirname(__FILE__).'/functions.inc.php');
  gtdeltree(GTHUMB_CACHE_DIR);
  
  $query = 'DELETE FROM ' . CONFIG_TABLE . ' WHERE param="GThumb" LIMIT 1;';
  pwg_query($query);
}

?>