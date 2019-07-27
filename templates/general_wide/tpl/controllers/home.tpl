<!-- home tpl -->{strip}

{rlHook name='homeTop'}

{rlHook name='homeBottomTpl'}

<!-- removing account popup -->
{assign var='remove_account_variable' value='remove-account'}
{if isset($smarty.request.$remove_account_variable) && $smarty.request.id && $smarty.request.hash}
    {addCSS file=$rlTplBase|cat:'components/popup/popup.css'}
    {addJS file=$rlTplBase|cat:'components/popup/_popup.js'}
    {addJS file=$rlTplBase|cat:'components/account-removing/_account-removing.js'}

    <script class="fl-js-dynamic">
    $(function(){literal}{{/literal}
        flAccountRemoving.init('{$smarty.request.id}', '{$smarty.request.hash}');
    {literal}}{/literal});
    </script>
{/if}
<!-- removing account popup end -->

{/strip}
<!-- home tpl end -->
