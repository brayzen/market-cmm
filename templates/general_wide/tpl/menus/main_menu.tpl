<!-- main menu block -->

{strip}
<div>
	<span class="menu-button hide" title="{$lang.menu}"><span></span><span></span><span></span></span>
	<span class="mobile-menu-header hide"><span>{$lang.menu}</span><span></span></span>

	{foreach name='mMenu' from=$main_menu item='mainMenu'}
		{if $mainMenu.Key == 'add_listing'}
			<a class="add-property button" {if $mainMenu.No_follow || $mainMenu.Login}rel="nofollow" {/if}title="{$mainMenu.title}" href="{$rlBase}{if $pageInfo.Controller != 'add_listing' && !empty($category.Path) && !$category.Lock}{if $config.mod_rewrite}{$mainMenu.Path}/{$category.Path}/{$steps.plan.path}.html{else}?page={$mainMenu.Path}&amp;step={$steps.plan.path}&amp;id={$category.ID}{/if}{else}{if $config.mod_rewrite}{$mainMenu.Path}.html{$mainMenu.Get_vars}{else}?page={$mainMenu.Path}{/if}{/if}">{$mainMenu.name}</a>
			{break}
		{/if}
	{/foreach}
	<ul class="menu">
		{foreach name='mMenu' from=$main_menu item='mainMenu'}
			{if $mainMenu.Key == 'add_listing'}{assign var='add_listing_button' value=$mainMenu}{continue}{/if}
			<li {if $pageInfo.Key == $mainMenu.Key}class="active"{/if}>
				<a {if $mainMenu.No_follow || $mainMenu.Login}rel="nofollow" {/if}title="{$mainMenu.title}" href="{if $mainMenu.Page_type != 'external'}{$rlBase}{/if}{if $pageInfo.Controller != 'add_listing' && $mainMenu.Controller == 'add_listing' && !empty($category.Path) && !$category.Lock}{if $config.mod_rewrite}{$mainMenu.Path}/{$category.Path}/{$steps.plan.path}.html{else}?page={$mainMenu.Path}&amp;step={$steps.plan.path}&amp;id={$category.ID}{/if}{else}{if $mainMenu.Page_type == 'external'}{$mainMenu.Controller}{else}{if $config.mod_rewrite}{if $mainMenu.Path != ''}{$mainMenu.Path}.html{$mainMenu.Get_vars}{/if}{else}{if $mainMenu.Path != ''}?page={$mainMenu.Path}{$mainMenu.Get_vars|replace:'?':'&amp;'}{/if}{/if}{/if}{/if}">{$mainMenu.name}</a>
			</li>
		{/foreach}
		<li class="more" style="display: none;"><span><span></span><span></span><span></span></span></li>
	</ul>
</div>
{/strip}

<ul id="main_menu_more"></ul>

<!-- main menu block end -->