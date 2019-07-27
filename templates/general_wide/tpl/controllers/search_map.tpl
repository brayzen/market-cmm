<!-- search on map tpl -->

<div class="search-map-container">
	<div id="map_area">
        <div id="map_listings" class="map-listings-container">
            <div id="listings_area">
                <div id="search_area">
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'horizontal_search.tpl'}
                </div>

                <div id="listings_cont">
                    <header class="progress">
                        <div class="caption"></div>
                        <div class="second-caption"><span class="link"></span><span class="group-count"></span></div>
                        <div class="loading">{$lang.loading}</div>
                    </header>
                    <div class="wrapper">
                        <div class="clearfix"></div>
                        <footer>
                            {include file='footer_data.tpl' no_rss=true}
                        </footer>
                    </div>
                </div>
            </div>

            <div class="control btn"></div>
        </div>
		<div class="map-search">
            <input id="pac-input" class="hide" type="text" placeholder="{$lang.enter_a_location}">
			<div id="map_container"></div>
			<div class="controls">
				<div class="point1">
					<div class="buttons">
						<span class="bg" id="zoom_in"></span>
						<span class="bg" id="zoom_out"></span>
						<span class="bg" id="full_screen"></span>
						<span class="bg" id="my_location"></span>
					</div>
				</div>
				<span class="loading"><span class="loading-spinner"></span></span>
			</div>
		</div>
		<div class="mobile-navigation hide"><div class="search"></div><div class="list"></div><div class="map active"></div></div>
	</div>
</div>

{assign var='count_replace' value=`$smarty.ldelim`count`$smarty.rdelim`}
<script id="tmplListing" type="text/x-jquery-tmpl">
{literal}

${($data.count_in_location = '{/literal}{$lang.count_properties_in_location|replace:$count_replace:'[count]'}{literal}'),''}
${($data.group_location_hint = '{/literal}{$lang.group_location_hint}{literal}'),''}
${($data.seo_base = '{/literal}{$smarty.const.SEO_BASE}{literal}'),''}

<article class="item col-sm-6{{if fd == 1 && gc == 1}} featured{{/if}}{{if gc > 1}} group{{/if}}" id="map_ad_${ID}">
	<div class="main-column relative clearfix">
		<a {{if gc == 1}}target="_blank"{{/if}} href="{{if gc > 1}}javascript://{{else}}${lu}{{/if}}">
			<div class="picture{{if !mp}} no-picture{{/if}}">
				<img src="{{if mp}}{/literal}{$smarty.const.RL_FILES_URL}{literal}${mp}{{else}}{/literal}{$rlTplBase}{literal}img/blank_10x7.gif{{/if}}" 
                {{if mpx2}}srcset="{/literal}{$smarty.const.RL_FILES_URL}{literal}${mpx2} 2x"{{/if}} />
                {{if fd == 1 && gc == 1}}<div class="label" title="{/literal}{$lang.featured}{literal}">{/literal}{$lang.featured}{literal}</div>{{/if}}
				{{if gc > 1}}<mark class="group"><span>{{html String(count_in_location).replace(/(\[count\])/gi, gc)}}</span></mark>{{/if}}
			</div>
		</a>
		<ul class="ad-info">
			{{if gc > 1}}
				<li class="group-info">
					${group_location_hint}
				</li>
				{{else}}
				<li class="title{{if gc == 1}} two-inline{{/if}}">
					{{if gc == 1}}<div id="fav_${ID}" class="favorite add"><span class="icon"></span></div>{{/if}}
					<a target="_blank" href="${lu}" title="${lt}" class="link-large">${lt}</a>
				</li>
				{{if bds > 0 || bts > 0 || sf}}
				<li class="services">
					{{if bds > 0}}<span title="" class="badrooms">${bds}</span>{{/if}}{{if bts > 0}}<span title="" class="bathrooms">${bts}</span>{{/if}}{{if sf}}<span title="" class="square_feet">${sf}</span>{{/if}}
				</li>
				{{/if}}
				<li class="fields">
					{{each fields_data}}
						<span>${name}</span>
					{{/each}}
				</li>
				{{if price}}
				<li class="system">
					<span class="price-tag">
						<span>${price}</span>
						{{if srk == 2 && tf}}/ ${tf}{{/if}}
					</span>
				</li>
				{{/if}}
			{{/if}}
		</ul>
	</div>
</article>

{/literal}
</script>

<script id="tmplPagination" type="text/x-jquery-tmpl">
{literal}

<ul class="pagination">
	<li class="navigator ls"><a title="{/literal}{$lang.previous_page}{literal}" class="button" href="javascript://">‹</a></li>
	<li class="transit">
        <span>{/literal}{$lang.page}{literal} </span>
        <input maxlength="4" type="text" value="1" size="3" />
        <span>{/literal}{$lang.of}{literal} ${pages}</span>
    </li>
    <li class="navigator rs"><a title="{/literal}{$lang.next_page}{literal}" class="button" href="javascript://">›</a></li>
</ul>

{/literal}
</script>

{addJS file='//maps.googleapis.com/maps/api/js?libraries=places&language='|cat:$smarty.const.RL_LANG_CODE|cat:'&key='|cat:$config.google_map_key}
{addJS file=$rlTplBase|cat:'js/map_utility.js'}
<script class="fl-js-dynamic">
var default_map_location = '{$default_map_location}';
var default_map_coordinates = '{if $smarty.post.loc_lat && $smarty.post.loc_lng}{$smarty.post.loc_lat},{$smarty.post.loc_lng}{else}{$config.realty_search_map_location}{/if}';
var default_map_zoom = {if $config.realty_search_map_location_zoom}{$config.realty_search_map_location_zoom}{else}14{/if};

lang['count_properties'] = '{$lang.count_properties}';
lang['number_property_found'] = '{$lang.number_property_found}';
lang['no_properties_found'] = '{$lang.no_properties_found}';
lang['map_listings_request_empty'] = '{$lang.map_listings_request_empty}';
lang['short_price_k'] = '{$lang.short_price_k}';
lang['short_price_m'] = '{$lang.short_price_m}';
lang['short_price_b'] = '{$lang.short_price_b}';

flynaxTpl.mapSearch('map_container', default_map_location);
</script>

<!-- search on map tpl end -->
