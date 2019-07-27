<!-- home page content tpl -->

<section class="horizontal-search{if $aHooks.search_by_distance} sbd-exists{/if}">
	<div class="point1">
        {if $config.home_page_h1}
            <h1>{if $pageInfo.h1}{$pageInfo.h1}{else}{$pageInfo.name}{/if}</h1>
        {/if}

        {assign var='cdt_count' value=$category_dropdown_types|@count}

        {if $cdt_count == 1}
            {assign var='spage_key' value=$listing_types[$category_dropdown_types.0].Page_key}
            {assign var='spage_path' value=$pages[$spage_key]}
        {elseif $cdt_count > 1}
            {assign var='spage_path' value=`$smarty.ldelim`type`$smarty.rdelim`}
        {/if}

        <form accesskey="{pageUrl key='search'}#keyword_tab" method="post" action="{if $cdt_count == 0}{pageUrl key='search'}{else}{$rlBase}{if $config.mod_rewrite}{$spage_path}/{$search_results_url}.html{else}?page={$spage_path}&{$search_results_url}{/if}{/if}">
            <input type="hidden" name="action" value="search" />
            <input type="hidden" name="form" value="keyword_search" />
            <input type="hidden" name="post_form_key" value="{if $cdt_count == 1}{$category_dropdown_types.0}_{if $listing_types[$category_dropdown_types.0].Advanced_search}advanced{else}quick{/if}{/if}" />

    		<div id="search_area">{strip}
                <div class="search-group">
                    {assign var='any_replace' value=`$smarty.ldelim`field`$smarty.rdelim`}
        			<input class="tags-autocomplete" type="text" placeholder="{$lang.keyword_search_hint}" name="f[keyword_search]" />

                    {if $cdt_count > 0}
                        <select name="f[Category_ID]">
                            <option value=""></option>
                        </select>

                        <script class="fl-js-dynamic">
                        var categoryDropdownTypes = {if $cdt_count == 1}'{$category_dropdown_types.0}'{else}Array('{$category_dropdown_types|@implode:"', '"}'){/if};
                        var categoryDropdownData = null;

                        {if $cdt_count > 1}
                            categoryDropdownData = new Array();
                            {foreach from=$category_dropdown_types item='dropdown_type' name='fSearchForms'}
                                {assign var='type_page_key' value=$listing_types[$dropdown_type].Page_key}

                                categoryDropdownData.push({literal} { {/literal}
                                    ID: '{$dropdown_type}',
                                    Key: '{$dropdown_type}',
                                    Link_type: '{$listing_types[$dropdown_type].Links_type}',
                                    Path: '{$pages.$type_page_key}',
                                    name: '{phrase key='pages+name+lt_'|cat:$dropdown_type}',
                                    Sub_cat: {$smarty.foreach.fSearchForms.iteration},
                                    Advanced_search: {$listing_types[$dropdown_type].Advanced_search}
                                {literal} } {/literal});
                            {/foreach}
                        {/if}

                        {literal}

                        $('section.horizontal-search select[name="f[Category_ID]"]').categoryDropdown({
                            listingTypeKey: categoryDropdownTypes,
                            typesData: categoryDropdownData,
                            phrases: { {/literal}
                                no_categories_available: "{$lang.no_categories_available}",
                                select: "{$lang.any_field_value|replace:$any_replace:$lang.category}",
                                select_category: "{$lang.any_field_value|replace:$any_replace:$lang.category}"
                            {literal} }
                        });

                        {/literal}
                        </script>
                    {/if}
                </div>

                {if $aHooks.search_by_distance}
                    {addJS file='//maps.googleapis.com/maps/api/js?libraries=places&language='|cat:$smarty.const.RL_LANG_CODE|cat:'&key='|cat:$config.google_map_key}
                    {addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/lib.js'}

                    <script class="fl-js-dynamic">
                    var sbd_zip_field = '{$config.sbd_zip_field}';
                    {literal}
                    if (typeof(sbdLocationAutocomplete) != 'undefined') {
                        sbdLocationAutocomplete('.horizontal-search.sbd-exists input#location_search', sbd_zip_field);
                    }{/literal}</script>

                    <div class="location-group">
                        <input type="text" placeholder="{if $config.sbd_search_mode == 'mixed'}{$lang.sbd_location_search_hint}{else}{$lang.sbd_zipcode}{/if}" name="f[{$config.sbd_zip_field}][zip]" id="location_search" />
                        <select name="f[{$config.sbd_zip_field}][distance]">
                            {foreach from=','|explode:$config.sbd_distance_items item='distance'}
                                <option {if $smarty.post.block_distance == $distance}selected="selected"{elseif $distance == $config.sbd_default_distance}selected="selected"{/if} value="{$distance}">{$distance} {if $config.sbd_units == 'miles'}{$lang.sbd_mi}{else}{$lang.sbd_km}{/if}</option>
                            {/foreach}
                        </select>

                        <input type="hidden" name="f[{$config.sbd_zip_field}][lat]" />
                        <input type="hidden" name="f[{$config.sbd_zip_field}][lng]" />
                    </div>
                {/if}

                <div class="submit-group">
                    <input type="submit" value="{$lang.search}" />
                </div>
    		{/strip}</div>
        </form>
	</div>
</section>

{if $tpl_settings.home_page_gallery}
    <section class="features-gallery">
        <div class="point1">
            <div class="row">
                <div class="col-md-8 col-sm-12">
                    <div class="featured_gallery{if $demo_gallery} demo{/if}">
                        {insert name='eval' content=$gallary_content}
                        <div class="preview">
                            <a {if $config.featured_new_window}target="_blank"{/if} title="{$lang.view_details}" href="#"><div></div></a>
                            <div class="fg-title hide"></div>
                            <div class="fg-price hide"></div>
                        </div>
                        {if $demo_gallery}{assign var='demo_gallery' value=false}{/if}
                    </div>
                </div>

                <!-- body style box -->
                <div class="col-md-4 col-sm-12 special-block">
                    {if $home_page_special_block}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$home_page_special_block side='sidebar'}
                    {/if}
                </div>
                <!-- body style box end -->
            </div>
        </div>
    </section>
{/if}

<!-- home page content tpl end -->