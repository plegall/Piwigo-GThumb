<?php

function ws_images_getGThumbPlusThumbnail($params, &$service)
{
  global $conf;

  if (empty($params['image_id']))
  {
    return new PwgError(403, "image_id is required");
  }

  include_once(PHPWG_ROOT_PATH.'admin/include/image.class.php');

  $forbidden = get_sql_condition_FandF(
    array(
      'forbidden_categories' => 'ic.category_id',
      'visible_categories' => 'ic.category_id',
      'visible_images' => 'i.id'
    ),
    'AND'
  );

  $query = 'SELECT i.*
FROM '.IMAGES_TABLE.' AS i
  INNER JOIN '.IMAGE_CATEGORY_TABLE.' AS ic ON i.id = ic.image_id
  INNER JOIN '.CATEGORIES_TABLE.' AS c ON ic.category_id = c.id
WHERE i.id = '.$params['image_id'].'
'.$forbidden.'
;';

  $picture = pwg_db_fetch_assoc(pwg_query($query));

  if (empty($picture))
  {
    return new PwgError(404, "image not found");
  }

  $data = get_gthumb_data($picture, $params['size']);
  $result = array();
 
  if (empty($data['src']))
  {
    $result = make_gthumb_image($picture, $data);
    $file = $result['destination'];
  }
  else
  {
    $file = $data['src'];
    $result['width'] = $data['width'];
    $result['height'] = $data['height'];
  }

  if ($params['return'])
  {
    switch (get_extension($file))
    {
      case 'jpg':
      case 'jpeg':
        header('Content-type: image/jpeg'); break;
      case 'gif':
        header('Content-type: image/gif'); break;
      case 'png':
        header('Content-type: image/png'); break;
      default:
        header('Content-type: unknow'); break;
    }  
    
    header('Last-Modified: '.date('r', filemtime($file)));
    readfile($file);
    if (!$conf['GThumb']['cache_big_thumb'])
    {
      @unlink($file);
    }
    exit();
  }

  return array(
    'id' => $picture['id'],
    'src' => $file,
    'width' => $result['width'],
    'height' => $result['height'],
  );
}

function make_gthumb_image($picture, $data)
{
  global $conf;

  $cache_dir = GTHUMB_CACHE_DIR.'/';
  if ($data['size'] == 'small' or $conf['GThumb']['cache_big_thumb'])
  {
    $cache_dir = $data['cache_path'];
  }
  $file = $cache_dir.md5($picture['path'].(!empty($picture['md5sum']) ? $picture['md5sum'] : '')).'.'.$picture['tn_ext'];

  if (!is_dir($cache_dir))
  {
    mkgetdir($cache_dir, MKGETDIR_DEFAULT&~MKGETDIR_DIE_ON_ERROR);
    if (!is_writable($cache_dir))
    {
      die("Give write access (chmod 777) to $cache_dir directory at the root of your Piwigo installation");
    }
  }

  $filepath = $picture['path'];
  if ($data['use_high'])
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');
    $filepath = file_path_for_type($filepath, 'high');
  }
  $img = new pwg_image($filepath);
  $result = $img->pwg_resize($file, $data['width'], $data['height'], $conf['upload_form_thumb_quality'], false, true, ($data['size'] == 'big'), false);
  $img->destroy();

  return $result;
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

function gtdirsize($path, &$size=0, &$nb_files=0)
{
  if (is_dir($path))
  {
    $fh = opendir($path);
    while ($file = readdir($fh))
    {
      if ($file != '.' and $file != '..' and $file != 'index.htm')
      {
        $pathfile = $path . '/' . $file;
        if (is_dir($pathfile))
        {
          $data = gtdirsize($pathfile, $size, $nb_files);
        }
        else
        {
          $size += filesize($pathfile);
          $nb_files++;
        }
      }
    }
    closedir($fh);
  }
  return array(
    'size' => $size,
    'nb_files' => $nb_files,
  );
}

?>