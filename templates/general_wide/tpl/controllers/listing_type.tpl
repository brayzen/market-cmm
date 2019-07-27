<!-- listing type -->

{rlHook name='browseTop'}

<!-- search results -->
{if $search_results}
	
	{if !empty($listings)}
	
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid_navbar.tpl'}

		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid.tpl' hl=true grid_photo=$listing_type.Photo}
		<script type="text/javascript">flynaxTpl.highlightResults($('#autocomplete').val());</script>
		
		<!-- paging block -->
		{paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url=$search_results_url method=$listing_type.Submit_method}
		<!-- paging block end -->

	{else}
		
		<div class="text-notice">
			{if $listing_type.Admin_only}
				{$lang.no_listings_found_deny_posting}
			{else}
            		
				{assign var='link' value='<a href="'|cat:$add_listing_link|cat:'">$1</a>'}
				{$lang.no_listings_found|regex_replace:'/\[(.+)\]/':$link}

				<br />
				{assign var='link' value='<span name="alter-save-search" class="'|cat:$listing_type.Key|cat:'"><span class="link">$1</span></span>'}
				{$lang.save_search_text|regex_replace:'/\[(.+)\]/':$link}
			{/if}
		</div>
		
	{/if}
	
	<script class="fl-js-dynamic">flynaxTpl.searchFormBox();</script>

<!-- search results end -->
{else}
<!-- browse/search forms mode -->

	{if $advanced_search}
	
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'advanced_search.tpl'}

	{else}
	
		{if !empty($category.des)}
			<div class="category-description">
				{$category.des}
			</div>
		{/if}
		
		{if !empty($listings)}
		
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid_navbar.tpl'}

			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid.tpl' grid_photo=$listing_type.Photo}
			
			<!-- paging block -->
			{if $config.mod_rewrite}
				{paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url=$category.Path}
			{else}
				{paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url='category='|cat:$category.ID}
			{/if}
			<!-- paging block end -->
		
		{else}
			{if $category.Lock}
				{assign var='br_count' value=$bread_crumbs|@count}
				{assign var='br_count' value=$br_count-2}
				
				{if $config.mod_rewrite}
					{assign var='lock_link' value=$rlBase|cat:$bread_crumbs.$br_count.path}
					{if $listing_type.Cat_postfix}
						{assign var='lock_link' value=$lock_link|cat:'.html'}
					{else}
						{assign var='lock_link' value=$lock_link|cat:'/'}
					{/if}
				{else}
					{assign var='lock_link' value=$rlBase|cat:'?page='|cat:$bread_crumbs.$br_count.path}
				{/if}
                {assign var='replace_name' value=`$smarty.ldelim`name`$smarty.rdelim`}
				{assign var='replace' value='<a title="'|cat:$lang.back_to_category|replace:$replace_name:$bread_crumbs.$br_count.name|cat:'" href="'|cat:$lock_link|cat:'">'|cat:$lang.click_here|cat:'</a>'}
				<div class="text-notice">{$lang.browse_category_locked|regex_replace:'/\[(.+)\]/':$replace}</div>
			{else}
				<div class="text-notice">
					{if $listing_type.Admin_only}
						{$lang.no_listings_here_submit_deny}
					{else}
						{assign var='link' value='<a href="'|cat:$add_listing_link|cat:'">$1</a>'}
						{$lang.no_listings_here|regex_replace:'/\[(.+)\]/':$link}
					{/if}
				</div>
			{/if}
		{/if}
		
	{/if}
	
{/if}
<!-- browse mode -->

{rlHook name='browseBottom'}

<!-- listing type end -->
