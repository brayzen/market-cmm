<!-- all in one option -->

<tr>
    <td class="name">{$lang.lt_category_search_dropdown}</td>
    <td class="field">
        {assign var='checkbox_field' value='category_search_dropdown'}

        {if $sPost.$checkbox_field == '1'}
            {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
        {elseif $sPost.$checkbox_field == '0'}
            {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
        {else}
            {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
        {/if}
        
        <table>
        <tr>
            <td>
                <input {$category_search_dropdown_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                <input {$category_search_dropdown_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
            </td>
            <td><span class="field_description">{$lang.lt_category_search_dropdown_hint}</span></td>
        </tr>
        </table>
    </td>

    <script>
    {literal}

    var categoryDropdownSearch = function(){
        $('input[name=category_search_dropdown]').attr('disabled', $('input[name=search_form]:checked').val() == '1' ? false : true);
    }

    $(document).ready(function(){
        $('input[name=search_form]').change(function(){
            categoryDropdownSearch();
        });

        categoryDropdownSearch();
    });

    {/literal}
    </script>
</tr>

<!-- all in one option end -->