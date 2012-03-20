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
  if (is_dir(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'GThumb'))
  {
    gtdeltree(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'GThumb');
  }
  
  $query = 'DELETE FROM ' . CONFIG_TABLE . ' WHERE param="GThumb" LIMIT 1;';
  pwg_query($query);
}

function plugin_activate($plugin_id, $version)
{
  if (is_dir(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'GThumb'))
  {
    gtdeltree(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'GThumb');
  }
}

function gtdeltree($path)
{
  if (is_dir($path))
  {
    $fh = opendir($path);
    while ($file = readdir($fh))
    {
      if ($file != '.' and $file != '..')
      {
        $pathfile = $path . '/' . $file;
        if (is_dir($pathfile))
        {
          gtdeltree($pathfile);
        }
        else
        {
          @unlink($pathfile);
        }
      }
    }
    closedir($fh);
    return @rmdir($path);
  }
}

?>