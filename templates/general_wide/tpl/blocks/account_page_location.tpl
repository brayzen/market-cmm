<!-- contact person location tpl -->

{if $contact}
{assign var='account' value=$contact}
{/if}

<script>
{literal}
var map_data = {
	addresses: [['{/literal}{if $location.direct}{$location.direct}{else}{$location.search}{/if}', '{$location.show}', '{if $location.direct}direct{else}geocoder{/if}{literal}']],
	phrases: {
		hide: '{/literal}{$lang.hide}{literal}',
		show: '{/literal}{$lang.show}{literal}',
		notFound: '{/literal}{$lang.location_not_found|escape:"javascript"}{literal}'
	},
	zoom: {/literal}{$config.map_default_zoom}{if $config.map_amenities && $amenities},{literal}
	localSearch: {
		caption: '{/literal}{$lang.local_amenity}{literal}',
		services: [{/literal}
			{foreach from=$amenities item='amenity' name='amenityF'}
			['{$amenity.Key}', '{$amenity.name}', {if $amenity.Default}'checked'{else}false{/if}]{if !$smarty.foreach.amenityF.last},{/if}
			{/foreach}
		{literal}]
	}
	{/literal}{/if}{literal}
};
{/literal}
</script>

<div class="location-cont clearfix">
	<div class="location-info">
		{foreach from=$account.Fields item='item' name='fListings'}
			{if $item.Map && !empty($item.value) && $item.Details_page}
				{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl' small=true}
				{assign var='map_fields' value=true}
			{/if}
		{/foreach}
	</div>

	{if $config.map_module && $location}
		<div title="{$lang.expand_map}" class="map-capture">
			<img alt="{$lang.expand_map}" src="{$rlTplBase}img/blank.gif" flstyle="background-image: url('https://maps.googleapis.com/maps/api/staticmap?markers=color:blue|{if $location.direct}{$location.direct}{else}{$location.search}{/if}&zoom={$config.map_default_zoom}&size=480x219{if $config.google_map_key}&key={$config.google_map_key}{/if}&language={$smarty.const.RL_LANG_CODE}&scale=|ratio|');" />
			<span class="media-enlarge"><span></span></span>
		</div>
	{else}
		{if !$map_fields}
			<div title="{$lang.expand_map}" class="map-capture">{$lang.no_account_location}</div>
		{/if}
	{/if}
</div>

<!-- contact person location tpl end -->
