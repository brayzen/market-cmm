<!-- custom field bound boxes -->

{if !empty($options)}
	{if !$icons}
		<div class="categories">
			<li>
	{/if}

	<ul class="row special-bound-box{if !$icons} list{/if}">
		{foreach from=$options item='option' name='fCats'}{strip}
			{if $icons}
				<li class="col-lg-3 col-md-4 col-sm-2 col-xs-3 {$icons_position}{if $show_count && !$option.Count} empty{/if}">
					<a title="{$lang[$option.pName]}" {if $icons}class="hint"{/if} href="{$rlBase}{if $config.mod_rewrite}{$path}/{$option.Key}{if $html_postfix}.html{else}/{/if}{else}?page={$pages.listings_by_field}&{$path}={$option.Key}{/if}">
						{if $option.Icon}
							<img style="{if $icons_width}width: {$icons_width}px;{/if}{if $icons_height}height: {$icons_height}px;{/if}" src="{$smarty.const.RL_FILES_URL}{$option.Icon}" alt="{$lang[$option.pName]}" />
						{else}
							{$lang[$option.pName]}
						{/if}
						{if $show_count}<span class="count">{$option.Count}</span>{/if}
					</a>
				</li>
			{else}
				{php}
				global $page_info;

				$block = $this -> get_template_vars('block');
				$class = 'col-md-3 col-sm-4';

				if ($page_info['Key'] == 'home') {
					if (in_array($block['Side'], array('middle_left', 'middle_right'))) {
						$class = 'col-md-6 col-sm-4';
					}
				} else {
					if ( in_array($block['Side'], array('middle', 'bottom', 'top'))) {
						$class = 'col-md-3 col-sm-4';
					} elseif (in_array($block['Side'], array('middle_left', 'middle_right'))) {
						$class = 'col-md-12 col-sm-4';
					}
				}

				$this -> assign('box_item_class', $class);
				{/php}

				<div class="{$box_item_class} item{if $show_count && !$option.Count} empty-category{/if}">
					<div class="parent-cateory">
						{if $show_count}
							<div class="category-counter">
								<span>{$option.Count}</span>
							</div>
						{/if}
						<div class="category-name">
							<a class="category" title="{$lang[$option.pName]}" {if $icons}class="hint"{/if} href="{$rlBase}{if $config.mod_rewrite}{$path}/{$option.Key}{if $html_postfix}.html{else}/{/if}{else}?page={$pages.listings_by_field}&{$path}={$option.Key}{/if}">{$lang[$option.pName]}</a>
						</div>
					</div>
				</div>
			{/if}
		{/strip}{/foreach}
	</ul>
	
	{if !$icons}
			</li>
		</div>
	{/if}
{/if}

<!-- custom field bound boxes end -->