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
<p>
{$NB_ELEMENTS}, {$ELEMENTS_SIZE}<br>
<a href="admin.php?page=plugin-GThumb&amp;pwg_token={$PWG_TOKEN}&amp;deletecache=true" onclick="return confirm('{'Are you sure?'|@translate}');">{'Clear the cache'|@translate}</a>
</p>
</fieldset>

{html_head}{literal}
<style type="text/css">
#GThumb td { padding-bottom: 12px; }
#cacheinfo p { text-align:left; line-height:20px; margin:20px }
</style>
{/literal}{/html_head}