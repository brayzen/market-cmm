<!-- grid navigation bar -->

{assign var='grid_mode' value=$smarty.cookies.grid_mode}

{if !$grid_mode}
	{assign var='grid_mode' value='list'}
{/if}

{if $listing_type && !$listing_type.Photo}
	{assign var='grid_mode' value='list'}
{/if}

{php}
	$types = array('asc' => 'ascending', 'desc' => 'descending'); $this -> assign('sort_types', $types);
	$sort = array('price', 'number', 'date', 'mixed'); $this -> assign('sf_types', $sort);
{/php}

<div class="grid_navbar listings-area">
	<div class="switcher">{strip}
		<div class="hook">{rlHook name='browseGridNavBar'}</div>
		<div class="buttons">
			<div class="list{if $grid_mode == 'list'} active{/if}" title="{$lang.list_view}"><div><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
			{if $listing_type && !$listing_type.Photo}{else}
				<div class="grid{if $grid_mode == 'grid'} active{/if}" title="{$lang.gallery_view}"><div><span></span><span></span><span></span><span></span></div></div>
			{/if}
			<div class="map{if $grid_mode == 'map'} active{/if}" title="{$lang.map}"><div><span></span></div></div>
		{/strip}</div>
	</div>

	{if $sorting}
		<div class="sorting">
			<div class="current{if $grid_mode == 'map'} disabled{/if}">
				{$lang.sort_by}:
				<span class="link">{$sorting[$sort_by].name}</span>
				<span class="arrow"></span>
			</div>
			<ul class="fields">
			{foreach from=$sorting item='field_item' key='sort_key' name='fSorting'}
				{if isset($field_item.Details_page) && $field_item.Details_page == '0' || $field_item.Type == 'checkbox'}{continue}{/if}
				
				{if $field_item.Type|in_array:$sf_types}
					{foreach from=$sort_types key='st_key' item='st'}
						<li><a rel="nofollow" {if $sort_by == $sort_key && $sort_type == $st_key}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name} ({$lang[$st]})" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type={$st_key}">{$field_item.name} ({$lang[$st]})</a></li>
					{/foreach}
				{else}
					<li><a rel="nofollow" {if $sort_by == $sort_key}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name}" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type=asc">{$field_item.name}</a></li>
				{/if}
			{/foreach}
			{rlHook name='browseAfterSorting'}
			</ul>
		</div>
	{/if}
</div>

<!-- grid navigation bar end -->