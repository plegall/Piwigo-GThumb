{if !empty($thumbnails)}
{foreach from=$thumbnails item=thumbnail}
{assign var=derivative value=$pwg->derivative($GThumb_derivative_params, $thumbnail.src_image)}
<li class="gthumb">
  <a href="{$thumbnail.URL}">
    <img class="thumbnail" {if !$derivative->is_cached()}data-{/if}src="{$derivative->get_url()}" alt="{$thumbnail.TN_ALT}" title="{$thumbnail.TN_TITLE}" {$derivative->get_size_htm()}>
  </a>
</li>
{/foreach}

{combine_css path="plugins/GThumb/template/gthumb.css"}
{combine_script id='jquery.ajaxmanager' path='themes/default/js/plugins/jquery.ajaxmanager.js' load='footer'}
{combine_script id='thumbnails.loader' path='themes/default/js/thumbnails.loader.js' require='jquery.ajaxmanager' load='footer'}
{combine_script id='jquery.ba-resize' path='plugins/GThumb/js/jquery.ba-resize.min.js' load="footer"}
{combine_script id='gthumb' require='jquery,jquery.ba-resize' path='plugins/GThumb/js/gthumb.js' load="footer"}

{footer_script require="gthumb"}
GThumb.max_height = {$GThumb.height};
GThumb.margin = {$GThumb.margin};
GThumb.method = '{$GThumb.method}';

{if isset($GThumb_big)}
{assign var=gt_size value=$GThumb_big->get_size()}
GThumb.big_thumb = {ldelim}id:{$GThumb_big->src_image->id},src:'{$GThumb_big->get_url()}',width:{$gt_size[0]},height:{$gt_size[1]}{rdelim};
{/if}

GThumb.build();
jQuery(window).bind('RVTS_loaded', GThumb.build);
jQuery('#thumbnails').resize(GThumb.process);
{/footer_script}

{html_head}
<style type="text/css">#thumbnails .gthumb {ldelim} margin:0 0 {$GThumb.margin}px {$GThumb.margin}px !important; }</style>
<!--[if IE 8]>
<style type="text/css">#thumbnails .gthumb a {ldelim} right: 0px; }</style>
<![endif]-->
{/html_head}
{/if}