<div class="titrePage">
<h2>GThumb+</h2>
</div>

<form action="" method="post">

<fieldset id="GThumb">
<legend>{'Configuration'|@translate}</legend>
<table>
  <tr>
    <td align="right">{'Thumbnails max height'|@translate} : &nbsp;&nbsp;</td>
    <td><input type="text" size="2" maxlength="3" name="height" value="{$HEIGHT}">&nbsp;px</td></td>
  </tr>

  <tr>
    <td align="right">{'Margin between thumbnails'|@translate} : &nbsp;&nbsp;</td>
    <td><input type="text" size="2" maxlength="3" name="margin" value="{$MARGIN}">&nbsp;px</td>
  </tr>

  <tr>
    <td align="right">{'Number of photos per page'|@translate} : &nbsp;&nbsp;</td>
    <td><input type="text" size="2" maxlength="3" name="nb_image_page" value="{$NB_IMAGE_PAGE}"></td>
  </tr>

  <tr>
    <td align="right">{'Double the size of the first thumbnail'|@translate} : &nbsp;&nbsp;</td>
    <td><input type="radio" name="big_thumb" value="1" {if $BIG_THUMB}checked="checked"{/if}> {'Yes'|@translate} &nbsp;
        <input type="radio" name="big_thumb" value="0" {if !$BIG_THUMB}checked="checked"{/if}> {'No'|@translate}
    </td>
  </tr>

  <tr>
    <td align="right">{'Cache the big thumbnails (recommended)'|@translate} : &nbsp;&nbsp;</td>
    <td><input type="radio" name="cache_big_thumb" value="1" {if $CACHE_BIG_THUMB}checked="checked"{/if}> {'Yes'|@translate} &nbsp;
        <input type="radio" name="cache_big_thumb" value="0" {if !$CACHE_BIG_THUMB}checked="checked"{/if}> {'No'|@translate}
    </td>
  </tr>

  <tr>
    <td align="right">{'Scale thumbnails'|@translate} : &nbsp;&nbsp;</td>
    <td><input type="radio" name="method" value="crop" {if $METHOD == 'crop'}checked="checked"{/if}> {'Crop'|@translate} &nbsp;
        <input type="radio" name="method" value="resize" {if $METHOD == 'resize'}checked="checked"{/if}> {'Resize'|@translate}
    </td>
  </tr>

</table>
</fieldset>

<p><input type="submit" name="submit" value="{'Submit'|@translate}"></p>
</form>

<fieldset id="cacheinfo">
<legend>{'Cache Informations'|@translate}</legend>
<p id="cache_data">&nbsp;</p>
<p id="GThumbAction">
  <button onclick="GThumb.deletecache();" title="{'Delete images in GThumb+ cache.'|@translate}">{'Purge thumbnails cache'|@translate}</button>
  <button onclick="GThumb.generatecache();" title="{'Finds images that have not been cached and creates the cached version.'|@translate}">{'Pre-cache thumbnails'|@translate}</button>
</p>
<div id="GThumbProgressbar" style="display:none;">
  {'Generating cache, please wait...'|@translate}<br>
  <div id="progressbar"></div>
  <p><button onclick="GThumb.abort();">{'Cancel'|@translate}</button></p>
</div>
</fieldset>

{html_head}{literal}
<style type="text/css">
#GThumb td { padding-bottom: 12px; }
#cacheinfo p, #GThumbProgressbar { text-align:left; line-height:20px; margin:20px }
.ui-progressbar-value { background-image: url(plugins/GThumb/template/pbar-ani.gif); }
</style>
{/literal}{/html_head}

{combine_script id='jquery.ui.progressbar' load='footer'}
{combine_script id='jquery.ajaxmanager' load='footer' path='themes/default/js/plugins/jquery.ajaxmanager.js'}

{footer_script}
var pwg_token = '{$PWG_TOKEN}';
var confirm_message = '{'Are you sure?'|@translate}';
var nb_files_str  = '{'%d file'|@translate}';
var nb_files_str_plur = '{'%d files'|@translate}';
var lang_info_zero_plural = {if $lang_info.zero_plural}true{else}false{/if};
var cache_size = {$CACHE_SIZE};
var nb_files = {$NB_FILES};

{literal}
var GThumb = {

  total: 0,
  done: 0,

  queue: jQuery.manageAjax.create('queued', { 
    queue: true,  
    cacheResponse: false,
    maxRequests: 3
  }),

  deletecache: function() {
    if (confirm(confirm_message)) {
      window.location = 'admin.php?page=plugin-GThumb&deletecache=true&pwg_token='+pwg_token;
    }
  },

  generatecache: function() {
    GThumb.total = nb_files;
    GThumb.done = nb_files;
    jQuery("#progressbar").progressbar({value: 1});
    jQuery.ajax({
      url: 'admin.php?page=plugin-GThumb&generatecache=request',
      dataType: 'json',
      success: function(data) {
        if (data.length > 0) {
          jQuery("#GThumbProgressbar, #GThumbAction").toggle();
          GThumb.total = data.length + GThumb.done;
          jQuery("#progressbar").progressbar({value: Math.ceil(GThumb.done * 100 / GThumb.total)});
          for (i=0;i<data.length;i++) {
            GThumb.queue.add({
              type: 'GET', 
              url: 'ws.php', 
              data: {
                method: 'pwg.images.getGThumbPlusThumbnail',
                image_id: data[i],
                format: 'json'
              },
              dataType: 'json',
              success: function(data) {
                nb_files++;
                cache_size += data.result.filesize;
                updateCacheSizeAndFiles();
                GThumb.progressbar();
              },
              error: GThumb.progressbar
            });
          }
        } else {
          window.location = 'admin.php?page=plugin-GThumb&generatecache=complete';
        }
      },
      error: function() {
        alert('An error occured');
      }
    });
    return false;
  },

  progressbar: function() {
    jQuery( "#progressbar" ).progressbar({
      value: Math.ceil(++GThumb.done * 100 / GThumb.total)
    });
    if (GThumb.done == GThumb.total) {
      window.location = 'admin.php?page=plugin-GThumb&generatecache=complete';
    }
  },

  abort: function() {
    GThumb.queue.clear();
    GThumb.queue.abort();
    jQuery("#GThumbProgressbar, #GThumbAction").toggle();
  }
};

function updateCacheSizeAndFiles() {
  
  if ( nb_files > 1 || (nb_files == 0 && lang_info_zero_plural)) {
    nbstr = nb_files_str_plur;
  } else {
    nbstr = nb_files_str;
  }

  ret = nbstr.replace('%d', nb_files) + ', ';

  if (cache_size > 1024 * 1024)
    ret += Math.round((cache_size / (1024 * 1024))*100)/100 + ' MB';
  else
    ret += Math.round((cache_size / 1024)*100)/100 + ' KB';

  jQuery("#cache_data").html(ret);
}

updateCacheSizeAndFiles();

jQuery('#GThumbAction button').tipTip({'delay' : 0, 'fadeIn' : 200, 'fadeOut' : 200});
{/literal}{/footer_script}