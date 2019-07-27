<!-- Multifield custom settings tpl -->

<table class="hide">
<tr id="mf_filtering_settings">
    <td colspan="2">
        <input type="hidden" name="post_config[mf_geo_force][value]" value="1" />

        <table class="form">
        {assign var='new_group' value=false}

        {foreach from=$mf_available_pages item='page' name='pages'}

        {if $page.Controller|in_array:$mf_predefine_controllers && !$new_group}
            {assign var='new_group' value=true}

        <tr>
            <td class="divider_line" colspan="2">
                <div class="inner">{$lang.mf_geo_prefilling_group}</div>
            </td>
        </tr>
        {/if}

        <tr{if $smarty.foreach.pages.iteration%2 == 0} class="highlight"{/if}>
            <td class="name"{if $smarty.foreach.pages.first} style="width: 210px;"{/if}>
                {phrase key='pages+name+'|cat:$page.Key}
            </td>
            <td class="field">
                <div class="inner_margin" style="padding-top: 6px;">
                    <label>
                        <input type="radio"
                               name="mf_config[{$page.Key}][filtration]"
                               value="1"
                               {if $page.Key|in_array:$mf_filtering_pages}
                               checked="checked"
                               {/if} />
                        {$lang.enabled}
                    </label>

                    <label>
                        <input type="radio"
                               name="mf_config[{$page.Key}][filtration]"
                               value="0"
                               {if !$page.Key|in_array:$mf_filtering_pages}
                               checked="checked"
                               {/if} />
                        {$lang.disabled}
                    </label>

                    {if !$page.Controller|in_array:$mf_predefine_controllers && $config.mod_rewrite}
                        <label class="mf-opt-label{if !$page.Key|in_array:$mf_filtering_pages} mf-disabled{/if}">
                            <input type="checkbox"
                                   name="mf_config[{$page.Key}][url]"
                                   value="1"
                                   {if !$page.Key|in_array:$mf_filtering_pages}
                                   disabled="disabled"
                                   {/if}
                                   {if $page.Key|in_array:$mf_location_url_pages}
                                   checked="checked"
                                   {/if} />
                            {$lang.mf_apply_location_to_url}
                        </label>
                    {/if}
                </div>
            </td>
        </tr>
        {/foreach}
        </table>

        <p class="mf-hint"><i>{$lang.mf_preselect_data_hint}</i></p>
    </td>
</tr>
</table>

<script>
var mf_group_id   = {$mf_group_id};
var mf_geo_filter = {if $mf_geo_filter}true{else}false{/if};
lang['mf_no_geo_filtering_format'] = '{$lang.mf_no_geo_filtering_format}';
{literal}

$(function(){
    var $container = $('#mf_filtering_settings');

    $('#larea_' + mf_group_id + ' table.form tbody > tr:last').before($container);
    $container.removeClass('hide');

    $container.find('input[type=radio][name^=mf_config]').change(function(){
        var is_checked = parseInt($(this).filter(':checked').val());
        var $container = $(this).closest('div');

        $container.find('.mf-opt-label')[is_checked
            ? 'removeClass'
            : 'addClass'
        ]('mf-disabled');

        $container.find('.mf-opt-label input').attr('disabled', !is_checked);
    });

    // No geo filtering alert
    if (!readCookie('mf_no_geo_filtering_format') && !mf_geo_filter) {
        $('#ltab_' + mf_group_id).click(function(){
            setTimeout(function(){
                fail_alert('', lang['mf_no_geo_filtering_format']);
                createCookie('mf_no_geo_filtering_format', 1, 1);
            }, 1000);
        });
    }
});

{/literal}
</script>

<!-- Multifield custom settings tpl end -->
