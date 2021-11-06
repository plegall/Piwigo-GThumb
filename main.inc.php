<?php
/*
Plugin Name: GThumb+
Version: auto
Description: Display thumbnails as patchwork
Plugin URI: auto
Author: P@t
Author URI: http://www.gauchon.com
Has Settings: true
*/

global $conf;

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

if (mobile_theme()) return;

define('GTHUMB_PATH' , PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/');

$conf['GThumb'] = unserialize($conf['GThumb']);

// RV Thumbnails Scroller
if (isset($_GET['rvts']))
{
  $conf['GThumb']['big_thumb'] = false;
  add_event_handler('loc_end_index_thumbnails', 'process_GThumb', 50, 2);
}

add_event_handler('init', 'GThumb_init');
add_event_handler('loc_begin_index', 'GThumb_index', 60);
add_event_handler('loc_end_index', 'GThumb_remove_thumb_size');
add_event_handler('get_admin_plugin_menu_links', 'GThumb_admin_menu');

function GThumb_init()
{
  global $conf, $user, $page, $stripped;
  
  // new param in 2.4.c
  if (!isset($conf['GThumb']['show_thumbnail_caption']))
  {
    $conf['GThumb']['show_thumbnail_caption'] = true;
    conf_update_param('GThumb', serialize($conf['GThumb']));
  }

  // new param in 2.8.a
  if (!isset($conf['GThumb']['show_score_in_caption']))
  {
    $conf['GThumb']['show_score_in_caption'] = false;
    conf_update_param('GThumb', serialize($conf['GThumb']));
  }

  $user['nb_image_page'] = $conf['GThumb']['nb_image_page'];
  $page['nb_image_page'] = $conf['GThumb']['nb_image_page'];
  $stripped['maxThumb'] = $conf['GThumb']['nb_image_page'];
  $conf['show_thumbnail_caption'] = $conf['GThumb']['show_thumbnail_caption'];
}

function GThumb_index()
{
  global $template;
  
  $template->set_prefilter('index', 'GThumb_prefilter');

  add_event_handler('loc_end_index_thumbnails', 'process_GThumb', 50, 2);
}

function process_GThumb($tpl_vars, $pictures)
{
  global $template, $conf;

  $template->set_filename( 'index_thumbnails', realpath(GTHUMB_PATH.'template/gthumb.tpl'));
  $template->assign('GThumb', $conf['GThumb']);

  $template->assign('GThumb_derivative_params', ImageStdParams::get_custom(9999, $conf['GThumb']['height']));

  if ($conf['GThumb']['big_thumb'] and !empty($tpl_vars[0]))
  {
    $derivative_params = ImageStdParams::get_custom(9999, 2 * $conf['GThumb']['height'] + $conf['GThumb']['margin']);
    $template->assign('GThumb_big', new DerivativeImage($derivative_params, $tpl_vars[0]['src_image']));
  }

  return $tpl_vars;
}

function GThumb_prefilter($content)
{
  $pattern = '#\<div.*?id\="thumbnails".*?\>\{\$THUMBNAILS\}\</div\>#';
  $replacement = '<ul id="thumbnails">{$THUMBNAILS}</ul>';

  return preg_replace($pattern, $replacement, $content);
}

function GThumb_admin_menu($menu)
{
  array_push($menu,
    array(
      'NAME' => 'GThumb+',
      'URL' => get_root_url().'admin.php?page=plugin-'.basename(dirname(__FILE__)),
    )
  );
  return $menu;
}

function GThumb_remove_thumb_size()
{
  global $template;
  $template->clear_assign('image_derivatives');
}

?>