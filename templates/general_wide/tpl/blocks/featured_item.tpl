{strip}{php}
global $page_info;

$block = $this -> get_template_vars('block');
$side_bar_exists = $this -> get_template_vars('side_bar_exists');
$class = 'col-md-3 col-sm-4';

if ( in_array($block['Side'], array('middle', 'bottom', 'top'))) {
    $class = $side_bar_exists ? 'col-sm-4' : 'col-md-3 col-sm-4';
} elseif (in_array($block['Side'], array('middle_left', 'middle_right'))) {
    $class = 'col-md-12 col-sm-4';
}

$this -> assign('box_item_class', $class);
{/php}

{rlHook name='featuredItemTop'}

<li {if $featured_listing.ID}id="fli_{$featured_listing.ID}"{/if} class="{$box_item_class}{if !$featured_listing.Main_photo} no-picture{/if}">
	{if $listing_types.$type.Photo}
        <div class="picture">
    		<a title="{$featured_listing.listing_title}" {if $config.featured_new_window}target="_blank"{/if} href="{$featured_listing.url}">
    			<img src="{if $featured_listing.Main_photo}{$smarty.const.RL_FILES_URL}{$featured_listing.Main_photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}"
                    {if $featured_listing.Main_photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$featured_listing.Main_photo_x2} 2x"{/if}
                    alt="{$featured_listing.listing_title}" />
    		</a>
        </div>
	{/if}

	<ul class="ad-info">
		<li class="title" title="{$featured_listing.fields.title.value|strip_tags}">
			<a {if $config.featured_new_window}target="_blank"{/if} href="{$featured_listing.url}">
				{$featured_listing.listing_title}
			</a>
		</li>

        {if $featured_listing.fields.bedrooms.value || $featured_listing.fields.bathrooms.value || $featured_listing.fields.square_feet.value}
            <li class="services">{strip}
                {if $featured_listing.fields.bedrooms.value}
                    <span title="{$featured_listing.fields.bedrooms.name}" class="badrooms">{$featured_listing.fields.bedrooms.value}</span>
                {/if}
                {if $featured_listing.fields.bathrooms.value}
                    <span title="{$featured_listing.fields.bathrooms.name}" class="bathrooms">{$featured_listing.fields.bathrooms.value}</span>
                {/if}
                {if $featured_listing.fields.square_feet.value}
                    <span title="{$featured_listing.fields.square_feet.name}" class="square_feet">{$featured_listing.fields.square_feet.value}</span>
                {/if}
            {/strip}</li>
        {/if}

		<li class="fields">
			{foreach from=$featured_listing.fields item='item' key='field' name='fieldsF'}
				{if empty($item.value) || !$item.Details_page || ($item.Key == $config.price_tag_field || $item.Key|in_array:$tpl_settings.listing_grid_except_fields)}{continue}{/if}

				<span id="flf_{$featured_listing.ID}_{$item.Key}">{$item.value}</span>
			{/foreach}
		</li>

		<li class="two-inline price_tag">
			<nav id="fav_{$featured_listing.ID}" class="favorite add" title="{$lang.add_to_favorites}">
				<span class="icon"></span></span>
			</nav>

			{if $featured_listing.fields[$config.price_tag_field].value}
				<div>
					<span>{$featured_listing.fields[$config.price_tag_field].value}</span>
					{if $featured_listing.sale_rent == 2 && $featured_listing.fields.time_frame.value}
                        &nbsp;/ {$featured_listing.fields.time_frame.value}
                    {/if}
				</div>
			{/if}
		</li>
		{*rlHook name='tplFeaturedItemPrice'*}
	</ul>
</li>

{rlHook name='featuredItemBottom'}

{/strip}
