<!-- listing details sidebar -->

{rlHook name='listing_details_sidebar'}

<!-- seller info -->
{if !$pageInfo.Listing_details_inactive}
<section class="side_block no-header seller-short{if !$seller_info.Photo} no-picture{/if}">
	<div>
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_seller.tpl' sidebar=true}
	</div>
</section>
{/if}
<!-- seller info end -->

<!-- map -->
{if $config.map_module && $location && (!$listing_type.Photo || !$photos)}
	<section title="{$lang.expand_map}" class="side_block no-style map-capture">
		<img alt="{$lang.expand_map}" src="{$rlTplBase}img/blank.gif" flstyle="background-image: url('https://maps.googleapis.com/maps/api/staticmap?markers=color:blue|{if $location.direct}{$location.direct}{else}{$location.search}{/if}&zoom={$config.map_default_zoom}&size=480x180&scale=|ratio|{if $config.google_map_key}&key={$config.google_map_key}{/if}&language={$smarty.const.RL_LANG_CODE}');" />
		<span class="media-enlarge"><span></span></span>
	</section>
{/if}

<!-- listing details sidebar end -->
