<!-- news block tpl -->

{if !empty($all_news)}
	<ul class="news">
	{foreach from=$all_news item='news'}
		<li>
			<div>
				<div class="date">
					{$news.Date|date_format:'%d'} {$news.Date|date_format:'%b'}
				</div>
				
				<a title="{$news.title}" href="{$rlBase}{if $config.mod_rewrite}{$pages.news}/{$news.Path}.html{else}?page={$pages.news}&amp;id={$news.ID}{/if}"><h4>{$news.title}</h4></a>
			</div>			
			<article>{$news.content|strip_tags:false|truncate:$config.news_block_content_length:"":false}{if $news.content|strlen > $config.news_block_content_length}...{/if}</article>
		</li>
	{/foreach}
	</ul>
	<div class="ralign">
		<a title="{$lang.all_news}" href="{$rlBase}{if $config.mod_rewrite}{$pages.news}.html{else}?page={$pages.news}{/if}">{$lang.all_news}</a>
	</div>
{else}
	{$lang.no_news}
{/if}

<!-- news block tpl end -->