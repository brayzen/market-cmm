<!-- accounts tpl -->

{if $account_type}
	
	<!-- account details -->
	{if $account}
		<!-- account listings -->
		{if !empty($listings)}
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid_navbar.tpl'}
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid.tpl'}

			<!-- paging block -->
            {if $config.mod_rewrite}
                {paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page custom=$account.Own_address full=true}
            {else}
                {paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page var="id" url=$account.ID full=true}
            {/if}
			<!-- paging block end -->
		
		{else}
			{if $config.map_module && $location}
                {addJS file='//maps.googleapis.com/maps/api/js?libraries=places&language='|cat:$smarty.const.RL_LANG_CODE|cat:'&key='|cat:$config.google_map_key}
			{/if}

			<div class="info">{$lang.no_dealer_listings}</div>
		{/if}
		<!-- account listings end -->

	{else}
		{if $alphabet_dealers}
			{assign var='dealers' value=$alphabet_dealers}
		{/if}

		<!-- dealers list -->
		{if $dealers}
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid_navbar_account.tpl'}

			<script>var accounts_map = new Array();</script>
			<section id="accounts" class="grid row">
				{foreach from=$dealers item='dealer' key='key' name='dealersF'}
					{include file='blocks'|cat:$smarty.const.RL_DS|cat:'dealer.tpl'}
				{/foreach}
			</section>

			<section id="accounts_map" class="hide" {if $config.map_height}style="height: {$config.map_height}px;"{/if}></section>
            {addJS file='//maps.googleapis.com/maps/api/js?libraries=places&language='|cat:$smarty.const.RL_LANG_CODE|cat:'&key='|cat:$config.google_map_key}

			{if $alphabet_dealers}
				{paging calc=$pInfo.calc_alphabet total=$dealers|@count current=$pInfo.current per_page=$config.dealers_per_page url=$char var='character'}
			{else}
				{paging calc=$pInfo.calc total=$dealers|@count current=$pInfo.current per_page=$config.dealers_per_page url=$search_results_url}
			{/if}
		{else}
			<div class="info">{$lang.no_dealers}</div>
		{/if}
		<!-- dealers list end -->
	{/if}

{/if}

<!-- accounts tpl end -->