<?php
/*
Plugin Name: GThumb+
Version: auto
Description: Display thumbnails as patchwork
Plugin URI: auto
Author: P@t
Author URI: http://www.gauchon.com
*/

global $conf;

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

define('GTHUMB_PATH' , PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/');
define('GTHUMB_CACHE_DIR', PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'GThumb');

$conf['GThumb'] = unserialize($conf['GThumb']);

// RV Thumbnails Scroller
if (isset($_GET['rvts']))
{
  $conf['GThumb']['big_thumb'] = false;
  add_event_handler('loc_end_index_thumbnails', 'process_GThumb', 50, 2);
}

add_event_handler('loc_begin_index', 'GThumb_init', 60);
add_event_handler('ws_add_methods', 'add_gthumb_thumbnails_method');
add_event_handler('get_admin_plugin_menu_links', 'GThumb_admin_menu');

function GThumb_init()
{
  global $conf, $user, $page, $template;

  $template->set_prefilter('index', 'GThumb_prefilter');

  add_event_handler('loc_end_index_thumbnails', 'process_GThumb', 50, 2);

  $user['nb_image_page'] = $conf['GThumb']['nb_image_page'];
  $page['nb_image_page'] = $conf['GThumb']['nb_image_page'];
}

function process_GThumb($tpl_vars, $pictures)
{
  global $template, $conf;

  $template->set_filename( 'index_thumbnails', realpath(GTHUMB_PATH.'template/gthumb.tpl'));
  $template->assign('GThumb', $conf['GThumb']);

  include_once(PHPWG_ROOT_PATH.'admin/include/image.class.php');

  foreach ($tpl_vars as $key => &$tpl_var)
  {
    $data = get_gthumb_data($pictures[$key]);

    $tpl_var['TN_SRC'] = $data['src'];
    $tpl_var['TN_WIDTH'] = $data['width'];
    $tpl_var['TN_HEIGHT'] = $data['height'];
  }

  if ($conf['GThumb']['big_thumb'])
  {
    $ft = &$tpl_vars[0];

    // Small thumb data
    $small_thumb = array(
      'id' => $ft['ID'],
      'src' => $ft['TN_SRC'],
      'width' => $ft['TN_WIDTH'],
      'height' => $ft['TN_HEIGHT'],
    );

    if (empty($small_thumb['src']))
    {
      include_once(GTHUMB_PATH.'functions.inc.php');
      $data = get_gthumb_data($pictures[0]);
      $result = make_gthumb_image($pictures[0], $data);
      $small_thumb['src'] = $result['destination'];
    }

    // Big thumb data
    $data = get_gthumb_data($pictures[0], 'big');

    $big_thumb = array(
      'id' => $ft['ID'],
      'src' => $data['src'],
      'width' => $data['width'],
      'height' => $data['height'],
    );
    if (empty($big_thumb['src']))
    {
      if ($conf['GThumb']['cache_big_thumb'])
      {
        include_once(GTHUMB_PATH.'functions.inc.php');
        $result = make_gthumb_image($pictures[0], $data);
        $big_thumb['src'] = embellish_url(get_root_url().$result['destination']);
      }
      else
      {
        $big_thumb['src'] = get_root_url().'ws.php?method=pwg.images.getGThumbPlusThumbnail&image_id='.$ft['ID'].'&size=big&return=true';
      }
    }

    $template->assign(
      array(
        'small_thumb' => $small_thumb,
        'big_thumb' => $big_thumb,
      )
    );
    $ft['TN_SRC'] = $big_thumb['src'];
    $ft['TN_WIDTH'] = $big_thumb['width'];
    $ft['TN_HEIGHT'] = $big_thumb['height'];
  }
  
  return $tpl_vars;
}

function add_gthumb_thumbnails_method($arr)
{
  include_once(GTHUMB_PATH.'functions.inc.php');

  $service = &$arr[0];
  $service->addMethod(
    'pwg.images.getGThumbPlusThumbnail',
    'ws_images_getGThumbPlusThumbnail',
    array(
      'image_id' => array(),
      'size' => array('default'=>'small'),
      'return' => array('default'=>false),
    ),
    'Get thumbnail for GThumb+ plugin. Size parameter can be "small" or "big".'
  );
}

function get_gthumb_data($picture, $size='small')
{
  global $conf;

  $picture_ext = array('jpg', 'jpeg', 'png', 'gif');

  if (!in_array(strtolower(get_extension($picture['path'])), $picture_ext))
  {
    list($width, $height) = getimagesize(get_thumbnail_path($picture));

    return array(
      'src' => get_thumbnail_url($picture),
      'width' => $width,
      'height' => $height,
    );
  }

  $new_height = $size == 'small' ? $conf['GThumb']['height'] : $conf['GThumb']['height'] * 2 + $conf['GThumb']['margin'];
  $file = GTHUMB_CACHE_DIR.'/'.$new_height.'/'.md5($picture['path'].(!empty($picture['md5sum']) ? $picture['md5sum'] : '')).'.'.$picture['tn_ext'];

  if (file_exists($file))
  {
    list($width, $height) = getimagesize($file);

    return array(
      'src' => embellish_url(get_root_url().$file),
      'width' => $width,
      'height' => $height,
    );
  }

  if ( !empty( $picture['tn_ext'] ) )
  {
    $file = substr_replace(get_filename_wo_extension($picture['path']), '/GThumb/',strrpos($picture['path'],'/'),1).'.'.$picture['tn_ext'];
    if (file_exists($file))
    {
      list($width, $height) = getimagesize($file);

      $result = array(
        'src' => embellish_url(get_root_url().$file),
        'width' => $width,
        'height' => $height,
      );
    }
  }

  $width = $picture['width'];
  $height = $picture['height'];
  $use_high = false;

  if ($height < $new_height and $picture['has_high'] == 'true')
  {
    $width = $picture['high_width'];
    $height = $picture['high_height'];
    $use_high = true;
  }

  if ($size == 'big')
  {
    $width = min($width, round(max($height, $new_height) * 1.15));
  }

  $result = pwg_image::get_resize_dimensions($width, $height, 5000, $new_height);
  $result['src'] = '';

  // Test thumbnail size
  list($width, $height) = getimagesize(get_thumbnail_path($picture));
  if ($result['width'] == $width and $result['height'] == $height)
  {
    $result['src'] = get_thumbnail_url($picture);
  }

  $result['use_high'] = $use_high;
  $result['cache_path'] = GTHUMB_CACHE_DIR.'/'.$new_height.'/';
  $result['size'] = $size;

  return $result;
}

function GThumb_prefilter($content, $smarty)
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

?>