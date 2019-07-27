<!-- listing grid -->

{if !$grid_mode}
	{assign var='grid_mode' value=$smarty.cookies.grid_mode}
{/if}
{if !$grid_mode}
	{assign var='grid_mode' value='list'}
{/if}

{if $listing_type && !$listing_type.Photo}
	{assign var='grid_mode' value='list'}
{/if}

{if $periods}
	{assign var='cur_date' value=false}
	{assign var='grid_mode' value='list'}
	{assign var='replace_patter' value=`$smarty.ldelim`day`$smarty.rdelim`}
{/if}

<script>var listings_map = new Array();</script>
<section id="listings" class="{$grid_mode} {if $listing_type && !$listing_type.Photo}no-image{/if} row">
	{foreach from=$listings item='listing' key='key' name='listingsF'}
		{if $periods && $listing.Post_date != $cur_date}
			{if $listing.Date_diff == 1}
				{assign var='divider_name' value=$lang.today}
			{elseif $listing.Date_diff == 2}
				{assign var='divider_name' value=$lang.yesterday}
			{elseif $listing.Date_diff > 2 && $listing.Date_diff < 8}
				{assign var='divider_name' value=$lang.days_ago_pattern|replace:$replace_patter:$listing.Date_diff-1}
			{else}
				{assign var='divider_name' value=$listing.Post_date|date_format:$smarty.const.RL_DATE_FORMAT}
			{/if}
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'divider.tpl' name=$divider_name}
			{assign var='cur_date' value=$listing.Post_date}
		{/if}
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing.tpl' hl=$hl grid_photo=$grid_photo}

        {if $smarty.foreach.listingsF.iteration % $config.banner_in_grid_position == 0 && !$smarty.foreach.listingsF.last}
            <div class="banner-in-grid col-sm-12">
                {if $blocks.integrated_banner}
                    {showIntegratedBanner blocks=$blocks pageinfo=$pInfo listings=$listings|@count}
                {else}
                    <div class="banner-space">{$lang.banner_in_grid_phrase}</div>
                {/if}
            </div>
        {/if}
	{/foreach}
</section>

<section id="listings_map" class="hide" {if $config.map_height}style="height: {$config.map_height}px;"{/if}></section>
{addJS file='//maps.googleapis.com/maps/api/js?libraries=places&language='|cat:$smarty.const.RL_LANG_CODE|cat:'&key='|cat:$config.google_map_key}

<!-- listing grid end -->
