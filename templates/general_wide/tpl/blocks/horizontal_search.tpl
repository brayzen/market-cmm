<!-- home page search box tpl -->

<!-- tabs -->
{if $search_forms|@count > 1}
    <ul class="tabs tabs-hash {if $search_forms|@count < 5} tabs_count_{$search_forms|@count}{/if}">
        {foreach from=$search_forms item='search_form' key='sf_key' name='stabsF'}
            <li id="tab_{$sf_key}" class="{if $smarty.foreach.stabsF.first}active{/if}">
                <a href="#{$sf_key}" data-target="{$sf_key}">{$search_form.name}</a>
            </li>
        {/foreach}
    </ul>
{/if}
<!-- tabs end -->

<div class="horizontal-search">
    <div class="search-block-content">
        {foreach from=$search_forms item='search_form' key='sf_key' name='sformsF'}
            {assign var='spage_key' value=$listing_types[$search_form.listing_type].Page_key}
            {assign var='listing_type' value=$listing_types[$search_form.listing_type]}
            {assign var='post_form_key' value=$sf_key}
            
            <div id="area_{$sf_key}" class="search_tab_area{if !$smarty.foreach.sformsF.first} hide{/if}">
                <form name="map-search-form" action="post" target="" accesskey="{$search_form.listing_type}">{strip}
                    <input type="hidden" name="post_form_key" value="{$post_form_key}" />

                    {foreach from=$search_form.data item='item'}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_horizontal.tpl' fields=$item.Fields}
                    {/foreach}

                    <div class="search-form-cell submit">
                        <div>
                            <span></span>
                            <div>
                                <input type="submit" value="{$lang.search}" />
                            </div>
                        </div>
                    </div>
                {/strip}</form>
            </div>
        {/foreach}
    </div>
</div>

<!-- home page search box tpl end -->
