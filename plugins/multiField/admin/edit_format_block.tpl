<!-- edit format form -->

<form onsubmit="editItem('{$item.Key}');$('input[name=item_edit]').val('{$lang.loading}');return false;" action="" method="post">
    <table class="form">
    <tr>
        <td class="name"><span class="red">*</span>{$lang.key}</td>
        <td class="field">
            <input readonly="readonly" class="disabled" type="text" id="ni_key" style="width: 200px;" maxlength="60" value="{$item.Key}" />
        </td>
    </tr>

    {if $geo_filter}
        <tr>
            <td class="name">{$lang.mf_path}</td>

            {if $config.mf_geo_subdomains}
                <td class="field">
                    <span class="field_description_noicon" style="padding: 0;">{strip}
                        {$domain_info.scheme}://
                        {if $parent_path}
                            {$parent_path}
                        {else}
                            <input type="text" id="ei_sub_path" value="{$parent_path}" />
                        {/if}
                        .{$domain_info.host}
                        {if $config.mf_geo_subdomains_type == 'mixed' && $head_level != $smarty.get.parent}
                            /<input type="text" id="ei_path" value="{$item.Path}" />
                        {/if}
                        /
                    {/strip}</span>
                </td>
            {else}
                <td class="field">
                    <span class="field_description_noicon" style="padding: 0;">{strip}
                        {$smarty.const.RL_URL_HOME}
                        {if $parent_path}
                            {$parent_path}/
                        {/if}
                        <input type="text" id="ei_path" style="width: 200px;" value="{$item.Path}" />
                    {/strip}</span>
                </td>
            {/if}
        </tr>
    {/if}

    <tr>
        <td class="name">
            <div><span class="red">*</span>{$lang.value}</span></div>
        </td>
        <td class="field">
            {if $allLangs|@count > 1}
                <ul class="tabs">
                    {foreach from=$allLangs item='language' name='langF'}
                    <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                    {/foreach}
                </ul>
            {/if}
            
            {foreach from=$allLangs item='language' name='langF'}
                {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                <input id="ei_{$language.Code}" type="text" style="width: 250px;" value="{$names[$language.Code].Value}" />
                {if $allLangs|@count > 1}
                    <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                </div>
                {/if}
            {/foreach}
        </td>
    </tr>
    
    {rlHook name='apTplDataFormatsEditItemField'}
    
    <tr>
        <td class="name">{$lang.status}</td>
        <td class="field">
            <select id="ei_status">
                <option value="active" {if $item.Status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                <option value="approval" {if $item.Status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
            </select>
        </td>
    </tr>
    
    <tr>
        <td></td>
        <td class="field">
            <input type="submit" name="item_edit" value="{$lang.edit}" />
            <a href="javascript:void(0)" onclick="$('#edit_item').slideUp('normal')" class="cancel" type="button">{$lang.close}</a>
        </td>
    </tr>
    </table>
</form>

<!-- edit format form end -->
