<!-- footer data tpl -->

<div class="footer-data">
	<div class="icons">
		{if $pages.rss_feed}
			<a class="rss" title="{$lang.subscribe_rss}" href="{getRssUrl mode='footer'}" target="_blank"></a>
		{/if}
		<a class="facebook" target="_blank" title="{$lang.join_us_on_facebook}" href="{$config.facebook_page}"></a>
		<a class="twitter" target="_blank" title="{$lang.join_us_on_twitter}" href="{$config.twitter_page}"></a>
	</div>
	
	<div>
		&copy; {$smarty.now|date_format:'%Y'}, {$lang.powered_by}
		<a title="{$lang.powered_by} {$lang.copy_rights}" href="{$lang.flynax_url}">{$lang.copy_rights}</a>
	</div>
</div>

<!-- footer data tpl end -->