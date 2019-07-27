{if $multi_formats[$field.Condition]}
    {assign var='field_key' value=$field.Key}
    {assign var='data_key' value='location_listing_fields'}
    {if $data_mode == 'account'}
        {assign var='data_key' value='location_account_fields'}
    {/if}

    <script>
        if ( mfFields.indexOf('{$field_key}') < 0)
        {literal} { {/literal}
        mfFields.push('{$field_key}');
        {literal} } {/literal}

        {if $smarty.post.$mf_form_prefix[$field_key]}
            mfFieldVals['{$field_key}'] = '{$smarty.post.$mf_form_prefix[$field_key]}';
            {assign var='mf_data_source' value='post'}
        {elseif $smarty.post.$field_key}
            mfFieldVals['{$field_key}'] = '{$smarty.post.$field_key}';
            {assign var='mf_data_source' value='post'}
        {elseif $geo_filter_data.applied_location
            && $geo_filter_data[$data_key][$field_key]
            && $geo_filter_data.is_filtering
            && $mf_data_source != 'post'}
            mfFieldVals['{$field_key}'] = '{$geo_filter_data[$data_key][$field_key]}';
        {/if}
    </script>
{/if}
