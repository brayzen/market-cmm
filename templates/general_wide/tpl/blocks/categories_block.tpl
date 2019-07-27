<!-- categories block -->

{if $box_listing_type_key}
    {assign var='current_box_categories' value=$box_categories.$box_listing_type_key}
    {assign var='box_listing_type' value=$listing_types.$box_listing_type_key}
{else}
    {assign var='current_box_categories' value=$box_categories.$types}
    {assign var='box_listing_type' value=$listing_types.$types}
{/if}

{if $current_box_categories}
    {if $box_listing_type.Ablock_visible_number}
    	{math assign='pages_number' equation='ceil(count/num)' count=$current_box_categories|@count num=$box_listing_type.Ablock_visible_number}
    {/if}
	
	<div class="categories{if $box_listing_type.Ablock_show_subcats} sub-categories-exist{/if}" id="categories_{$box_listing_type_key|replace:'_':''}_{$pages_number}">
		<ul class="clearfix">
			<li>
				<div class="{if !$box_listing_type.Ablock_show_subcats}row {/if}categoty-column clearfix">
					
				{foreach from=$current_box_categories item='cat' name='fCats'}
					{rlHook name='tplBetweenCategories'}

					{if $cat.Ablock_position}
						{assign var='type_page_key' value=$cat.Page_key}
					{else}
						{assign var='type_page_key' value=$box_listing_type.Page_key}
					{/if}
					
					<div class="{if !$box_listing_type.Ablock_show_subcats}{if $block.Side == 'middle_left' || $block.Side == 'middle_right'}{if $blocks.left}col-md-12{else}col-md-6{/if}{elseif $block.Side == 'left'}col-md-12{else}col-md-4{/if} col-sm-6 {/if}item{if !$cat.Count} empty-category{/if}">
						<div class="parent-cateory">
							{if !$box_listing_type.Ablock_show_subcats && $box_listing_type.Cat_listing_counter}
								<div class="category-counter">
									<span>{$cat.Count|number_format}</span>
								</div>
							{/if}

							<div class="category-name">
								{rlHook name='tplPreCategory'}
								<a class="category" title="{if $lang[$cat.pTitle]}{$lang[$cat.pTitle]}{else}{$lang[$cat.pName]}{/if}" href="{$rlBase}{if $config.mod_rewrite}{$pages[$type_page_key]}{if $cat.Ablock_position}.html{else}/{$cat.Path}{if $box_listing_type.Cat_postfix}.html{else}/{/if}{/if}{else}?page={$pages[$type_page_key]}{if !$cat.Ablock_position}&category={$cat.ID}{/if}{/if}">{$lang[$cat.pName]}</a>
								{rlHook name='tplPostCategory'}
							</div>

							{if $box_listing_type.Ablock_show_subcats && $box_listing_type.Cat_listing_counter}
								<div class="category-counter">
									<span>{$cat.Count|number_format}</span>
								</div>
							{/if}
						</div>
						
						{if !empty($cat.sub_categories) && $box_listing_type.Ablock_show_subcats}
						<div class="sub_categories">{strip}
							{if $box_listing_type.Ablock_subcat_number}
								{section loop=$cat.sub_categories name='sub_cat' max=$box_listing_type.Ablock_subcat_number}
									<span>
										{rlHook name='tplPreSubCategory'}
										{assign var='subcat_title' value=$cat.sub_categories[sub_cat].pTitle}
										<a title="{if $lang.$subcat_title}{$lang.$subcat_title}{else}{$cat.sub_categories[sub_cat].name}{/if}" href="{$rlBase}{if $config.mod_rewrite}{$pages[$box_listing_type.Page_key]}/{$cat.sub_categories[sub_cat].Path}{if $box_listing_type.Cat_postfix}.html{else}/{/if}{else}?page={$pages[$box_listing_type.Page_key]}&category={$cat.sub_categories[sub_cat].ID}{/if}">{$cat.sub_categories[sub_cat].name}</a>
									</span>
									{if $smarty.section.sub_cat.last && $cat.sub_categories|@count > $box_listing_type.Ablock_subcat_number}
										<span class="more" title="{$lang.show_other_categories}">{$lang.more} &raquo;</span>
									{/if}
								{/section}
								
								<div class="hide other_categories">
									{section loop=$cat.sub_categories name='sub_cat' start=$box_listing_type.Ablock_subcat_number}
										<span>
											{rlHook name='tplPreSubCategory'}
											{assign var='subcat_title' value=$cat.sub_categories[sub_cat].pTitle}
											<a title="{if $lang.$subcat_title}{$lang.$subcat_title}{else}{$cat.sub_categories[sub_cat].name}{/if}" href="{$rlBase}{if $config.mod_rewrite}{$pages[$box_listing_type.Page_key]}/{$cat.sub_categories[sub_cat].Path}{if $box_listing_type.Cat_postfix}.html{else}/{/if}{else}?page={$pages[$box_listing_type.Page_key]}&category={$cat.sub_categories[sub_cat].ID}{/if}">{$cat.sub_categories[sub_cat].name}</a>
										</span>
									{/section}
								</div>
							{else}
								{foreach from=$cat.sub_categories item='sub_cat' name='subCatF'}
									<span>
										{rlHook name='tplPreSubCategory'}
										<a title="{if $lang[$sub_cat.pTitle]}{$lang[$sub_cat.pTitle]}{else}{$sub_cat.name}{/if}" href="{$rlBase}{if $config.mod_rewrite}{$pages.$type_page_key}/{$sub_cat.Path}{if $box_listing_type.Cat_postfix}.html{else}/{/if}{else}?page={$pages.$type_page_key}&category={$sub_cat.ID}{/if}">{$sub_cat.name}</a>
									</span>
								{/foreach}
							{/if}
						{/strip}</div>
						{/if}
					</div>
					
					{if $box_listing_type.Ablock_visible_number && $smarty.foreach.fCats.iteration%$box_listing_type.Ablock_visible_number == 0 && !$smarty.foreach.fCats.last}
						</div>
						{if $box_listing_type.Ablock_visible_number}
							</li><li class="hide">
						{/if}
						<div class="categoty-column categories_{$box_listing_type_key|replace:'_':''}">
					{/if}
				{/foreach}
				</div>
			</li>
		</ul>
	</div>
	
	{if $pages_number > 1}
		<div class="category-slider-bar{if $box_listing_type.Ablock_scrolling} mousewheel{/if}">{strip}
			<span class="arrow"><span class="prev" title="{$lang.show_previous_categories}"></span></span>
			<span class="navigation">
				{math assign='slide_width' equation='100/count' count=$pages_number}
				{section name='slide_page' loop=$pages_number|ceil}
					<span style="width: {$slide_width}%;" accesskey="{$smarty.section.slide_page.iteration}" {if $smarty.section.slide_page.first}class="active"{/if}></span>{/section}
			</span>
			<span class="arrow"><span class="next" title="{$lang.show_next_categories}"></span></span>
		{/strip}</div>
	{/if}

{else}
	{$lang.listing_type_no_categories}
{/if}

<!-- categories block end -->