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
    <td><label><input type="radio" name="big_thumb" value="1" {if $BIG_THUMB}checked="checked"{/if}> {'Yes'|@translate}</label> &nbsp;
        <label><input type="radio" name="big_thumb" value="0" {if !$BIG_THUMB}checked="checked"{/if}> {'No'|@translate}</label>
    </td>
  </tr>

  <tr>
    <td align="right">{'Cache the big thumbnails (recommended)'|@translate} : &nbsp;&nbsp;</td>
    <td><label><input type="radio" name="cache_big_thumb" value="1" {if $CACHE_BIG_THUMB}checked="checked"{/if}> {'Yes'|@translate}</label> &nbsp;
        <label><input type="radio" name="cache_big_thumb" value="0" {if !$CACHE_BIG_THUMB}checked="checked"{/if}> {'No'|@translate}</label>
    </td>
  </tr>

  <tr>
    <td align="right">{'Scale thumbnails'|@translate} : &nbsp;&nbsp;</td>
    <td><label><input type="radio" name="method" value="crop" {if $METHOD == 'crop'}checked="checked"{/if}> {'Crop'|@translate}</label> &nbsp;
        <label><input type="radio" name="method" value="resize" {if $METHOD == 'resize'}checked="checked"{/if}> {'Resize'|@translate}</label>
    </td>
  </tr>
  
  <tr>
    <td align="right">{'Show thumbnails caption'|@translate} : &nbsp;&nbsp;</td>
    <td><label><input type="radio" name="show_thumbnail_caption" value="1" {if $SHOW_THUMBNAIL_CAPTION}checked="checked"{/if}> {'Yes'|@translate}</label> &nbsp;
        <label><input type="radio" name="show_thumbnail_caption" value="0" {if !$SHOW_THUMBNAIL_CAPTION}checked="checked"{/if}> {'No'|@translate}</label>
    </td>
  </tr>

</table>
</fieldset>

<p>
  <input type="hidden" name="pwg_token" value="{$PWG_TOKEN}">
  <input type="submit" name="submit" value="{'Submit'|@translate}">
  <input type="submit" name="cachedelete" value="{'Purge thumbnails cache'|@translate}" title="{'Delete images in GThumb+ cache.'|@translate}" onclick="return confirm('{'Are you sure?'|@translate}');">
  <input type="button" name="cachebuild" value="{'Pre-cache thumbnails'|@translate}" title="{'Finds images that have not been cached and creates the cached version.'|@translate}" onclick="start()">
</p>
</form>

<fieldset id="generate_cache">
<legend>{'Pre-cache thumbnails'|@translate}</legend>
<p>
	<input id="startLink" value="{'Start'|@translate}" onclick="start()" type="button">
	<input id="pauseLink" value="{'Pause'|@translate}" onclick="pause()" type="button" disabled="disbled">
	<input id="stopLink" value="{'Stop'|@translate}" onclick="stop()" type="button" disabled="disbled">
</p>
<p>
<table>
	<tr>
		<td>Errors</td>
		<td id="errors">0</td>
	</tr>
	<tr>
		<td>Loaded</td>
		<td id="loaded">0</td>
	</tr>
	<tr>
		<td>Remaining</td>
		<td id="remaining">0</td>
	</tr>
</table>
</p>
<div id="feedbackWrap" style="height:{$HEIGHT}px; min-height:{$HEIGHT}px;">
<img id="feedbackImg">
</div>

<div id="errorList">
</div>
</fieldset>

{html_head}{literal}
<style type="text/css">
#GThumb td { padding-bottom: 12px; }
#cacheinfo p, #GThumbProgressbar { text-align:left; line-height:20px; margin:20px }
.ui-progressbar-value { background-image: url(plugins/GThumb/template/pbar-ani.gif); }
#generate_cache { display: none; }
</style>
{/literal}{/html_head}

{combine_script id='iloader' load='footer' path='plugins/GThumb/js/image.loader.js'}

{footer_script require='jquery.ui.effect-slide'}{literal}
jQuery('input[name^="cache"]').tipTip({'delay' : 0, 'fadeIn' : 200, 'fadeOut' : 200});

var loader = new ImageLoader( {onChanged: loaderChanged, maxRequests:1 } )
	, pending_next_page = null
	, last_image_show_time = 0
	, allDoneDfd, urlDfd;

function start() {
	allDoneDfd = jQuery.Deferred();
	urlDfd = jQuery.Deferred();

	allDoneDfd.always( function() {
			jQuery("#startLink").attr('disabled', false).css("opacity", 1);
			jQuery("#pauseLink,#stopLink").attr('disabled', true).css("opacity", 0.5);
		} );

	urlDfd.always( function() {
		if (loader.remaining()==0)
			allDoneDfd.resolve();
		} );

  jQuery('#generate_cache').show();
	jQuery("#startLink").attr('disabled', true).css("opacity", 0.5);
	jQuery("#pauseLink,#stopLink").attr('disabled', false).css("opacity", 1);

	loader.pause(false);
	updateStats();
	getUrls(0);
}

function pause() {
	loader.pause( !loader.pause() );
}

function stop() {
	loader.clear();
	urlDfd.resolve();
}

function getUrls(page_token) {
	data = {prev_page: page_token, max_urls: 500, types: []};
	jQuery.post( '{/literal}{$ROOT_URL}{literal}admin.php?page=plugin-GThumb&getMissingDerivative=',
		data, wsData, "json").fail( wsError );
}

function wsData(data) {
	loader.add( data.urls );
	if (data.next_page) {
		if (loader.pause() || loader.remaining() > 100) {
			pending_next_page = data.next_page;
		}
		else {
			getUrls(data.next_page);
		}
	}
}

function wsError() {
	urlDfd.reject();
}

function updateStats() {
	jQuery("#loaded").text( loader.loaded );
	jQuery("#errors").text( loader.errors );
	jQuery("#remaining").text( loader.remaining() );
}

function loaderChanged(type, img) {
	updateStats();
	if (img) {
		if (type==="load") {
			var now = jQuery.now();
			if (now - last_image_show_time > 3000) {
				last_image_show_time = now;
				var h=img.height, url=img.src;
				jQuery("#feedbackWrap").hide("slide", {direction:'down'}, function() {
					last_image_show_time = jQuery.now();
					if (h > 300 )
						jQuery("#feedbackImg").attr("height", 300);
					else
						jQuery("#feedbackImg").removeAttr("height");
					jQuery("#feedbackImg").attr("src", url);
					jQuery("#feedbackWrap").show("slide", {direction:'up'} );
					} );
			}
		}
		else {
			jQuery("#errorList").prepend( '<a href="'+img.src+'">'+img.src+'</a>' + "<br>");
		}
	}
	if (pending_next_page && 100 > loader.remaining() )	{
		getUrls(pending_next_page);
		pending_next_page = null;
	}
	else if (loader.remaining() == 0 && (urlDfd.isResolved() || urlDfd.isRejected()))	{
		allDoneDfd.resolve();
	}
}
{/literal}{/footer_script}