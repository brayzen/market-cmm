<!-- grid navigation bar for accounts -->

{assign var='grid_mode' value=$smarty.cookies.grid_mode_account}
{if !$grid_mode}
	{assign var='grid_mode' value='grid'}
{/if}

{php}
	$types = array('asc' => 'ascending', 'desc' => 'descending'); $this -> assign('sort_types', $types);
	$sort = array('price', 'number', 'custom', 'date'); $this -> assign('sf_types', $sort);
{/php}

<div class="grid_navbar">
	<div class="switcher">
		<div class="hook">{rlHook name='accountGridNavBar'}</div>
		<div class="buttons">{strip}
			<div class="grid{if $grid_mode == 'grid'} active{/if}" title="{$lang.gallery_view}"><div><span></span><span></span><span></span><span></span></div></div>
			<div class="map{if $grid_mode == 'map'} active{/if}" title="{$lang.map}"><div><span></span></div></div>
		{/strip}</div>
	</div>

	{if $sorting}
		<div class="sorting">
			<div class="current{if $grid_mode == 'map'} disabled{/if}">
				{$lang.sort_by}: 
				<span class="link">{if $sort_by}{$sorting[$sort_by].name}{else}{$lang.date}{/if}</span>
				<span class="arrow"></span>
			</div>
			<ul class="fields">
			{foreach from=$sorting item='field_item' key='sort_key' name='fSorting'}
				{if $field_item.Type|in_array:$sf_types}
					{foreach from=$sort_types key='st_key' item='st'}
						<li><a rel="nofollow" {if ($sort_by == $sort_key && $sort_type == $st_key) || ($field_item.default && !$sort_by && $st_key == 'asc')}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name} ({$lang[$st]})" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type={$st_key}">{$field_item.name} ({$lang[$st]})</a></li>
					{/foreach}
				{else}
					<li><a rel="nofollow" {if $sort_by == $sort_key || ($field_item.default && !$sort_by)}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name}" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type=asc">{$field_item.name}</a></li>
				{/if}
			{/foreach}
			{rlHook name='accountAfterSorting'}
			</ul>
		</div>
	{/if}
</div>

<!-- grid navigation bar for accounts end -->