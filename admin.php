<?php

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $template, $conf;

load_language('plugin.lang', GTHUMB_PATH);
include_once(GTHUMB_PATH.'functions.inc.php');
include(dirname(__FILE__).'/config_default.inc.php');
$params = $conf['GThumb'];

// Delete cache
if (isset($_GET['deletecache']))
{
  check_pwg_token();                             
  gtdeltree(GTHUMB_CACHE_DIR);
  redirect('admin.php?page=plugin-GThumb');
}

// Save configuration
if (isset($_POST['submit']))
{
  $params  = array(
    'height'          => $_POST['height'],
    'margin'          => $_POST['margin'],
    'nb_image_page'   => $_POST['nb_image_page'],
    'big_thumb'       => !empty($_POST['big_thumb']),
    'cache_big_thumb' => !empty($_POST['cache_big_thumb']),
    'method'          => $_POST['method'],
  );

  if (!is_numeric($params['height']))
  {
    array_push($page['errors'], 'Thumbnails max height must be an integer.');
  }
  if (!is_numeric($params['margin']))
  {
    array_push($page['errors'], 'Margin between thumbnails must be an integer.');
  }
  if (!is_numeric($params['nb_image_page']))
  {
    array_push($page['errors'], 'Number of photos per page must be an integer.');
  }

  if (empty($page['errors']))
  {
    $query = '
  UPDATE ' . CONFIG_TABLE . '
    SET value="' . addslashes(serialize($params)) . '"
    WHERE param="GThumb"
    LIMIT 1';
    pwg_query($query);
    
    array_push($page['infos'], l10n('Information data registered in database'));
  }
}

// Configuration du template
$template->assign(
  array(
    'HEIGHT'          => $params['height'],
    'MARGIN'          => $params['margin'],
    'NB_IMAGE_PAGE'   => $params['nb_image_page'],
    'BIG_THUMB'       => $params['big_thumb'],
    'CACHE_BIG_THUMB' => $params['cache_big_thumb'],
    'METHOD'          => $params['method'],
  )
);

// Informations
$data = gtdirsize(GTHUMB_CACHE_DIR);
if ($data['size'] > 1024 * 1024)
  $data['size'] = round($data['size'] / (1024 * 1024), 2).' MB';
else
  $data['size'] = round($data['size'] / 1024, 2).' KB';

$template->assign(
  array(
    'NB_ELEMENTS' => l10n_dec('%d file', '%d files', $data['nb_files']),
    'ELEMENTS_SIZE' => $data['size'],
    'PWG_TOKEN' => get_pwg_token(),
  )
);

$template->set_filenames(array('plugin_admin_content' => dirname(__FILE__) . '/template/admin.tpl'));
$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');

?>