<!-- Multi-Field Geo Filtering Box -->

{if $config.mf_geo_block_autocomplete && $geo_box_data.levels_data[0]}
    <div class="mf-autocomplete kws-block">
        <input class="mf-autocomplete-input w-100" type="text" maxlength="64" placeholder="{$lang.mf_geo_type_location}" />
        <div class="mf-autocomplete-dropdown hide"></div>
    </div>

    <script class="fl-js-dynamic">
        var mf_apply_url     = '{$geo_filter_data.clean_url}';
        var mf_script_loaded = false;
        var mf_current_key   = {if $geo_filter_data.location_keys}'{$geo_filter_data.location_keys|@end}'{else}null{/if};

        rlPageInfo['Geo_filter'] = {if $geo_filter_data.is_location_url}true{else}false{/if};

        {literal}
        $(function(){
            $('.mf-autocomplete-input').on('focus keyup', function(){
                if (!mf_script_loaded) {
                    flUtil.loadScript(rlConfig['plugins_url'] + 'multiField/static/autocomplete.js');
                    mf_script_loaded = true;
                }
            });
        });
        {/literal}
    </script>
{/if}

<div class="gf-box list-view{if $geo_box_data.levels_data[0]} gf-has-levels{/if}">
    {if $geo_filter_data.location}
        <ul class="list-unstyled gf-current">
            {foreach from=$geo_filter_data.location item='item' key='key'}
                <li>
                    <span class="hborder"></span>
                    {$item.name}
                    <a title="{$item.name}"
                        {if $geo_filter_data.is_location_url}
                            href="{$item.Parent_link}"
                        {else}
                            href="javascript://" class="gf-ajax" data-path="{$item.Parent_path}"
                        {/if}><img class="remove" src="{$rlTplBase}img/blank.gif" />
                    </a>
                </li>
            {/foreach}
        </ul>
    {/if}
    
    {assign var='gf_col_class' value='col-lg-6 col-md-12 col-sm-3'}

    {if $block.Side == 'top' || $block.Side == 'middle' || $block.Side == 'bottom'}
        {if $side_bar_exists}
            {assign var='gf_col_class' value='col-lg-3 col-md-4 col-sm-3'}
        {else}
            {assign var='gf_col_class' value='col-lg-2 col-md-3 col-sm-3'}
        {/if}
    {/if}

    {rlHook name='tplGFGeoBoxColClass'}

    {if $geo_box_data.levels_data[0]}
    <div class="gf-container">
        <ul class="list-unstyled row">
            {assign var="level_data" value=$geo_box_data.levels_data[0]}
            {foreach from=$level_data item="item"}
                <li class="{$gf_col_class}">
                    <a title="{$item.name}"
                        {if $geo_filter_data.is_location_url}
                            href="{$item.Link}"
                        {else}
                            href="javascript://" class="gf-ajax"
                        {/if}
                       data-path="{$item.Path}" data-key="{$item.Key}">{$item.name}</a>
                </li>
            {/foreach}
        </ul>
    </div>
    {elseif !$geo_filter_data.location}
        {$lang.mf_geo_box_default}
    {/if}
</div>

{if $level_data}
<script class="fl-js-dynamic">
{literal}

$(function(){
    $('.gf-box .gf-container').mCustomScrollbar();
});

{/literal}
</script>
{/if}

<script class="fl-js-dynamic">
{literal}

$('.gf-box,.mf-autocomplete-dropdown').on('click', 'a.gf-ajax', function(){
    flUtil.ajax({
        mode: 'mfApplyLocation',
        item: $(this).data('path'),
        key: $(this).data('key')
    }, function(response, status) {
        if (status == 'success' && response.status == 'OK') {
            if (location.href.indexOf('?reset_location') > 0) {
                location.href = location.href.replace('?reset_location', '');
            } else {
                location.reload();
            }
        } else {
            printMessage('error', lang['system_error']);
        }
    });
});

{/literal}
</script>
<!-- Multi-Field Geo Filtering Box end -->
