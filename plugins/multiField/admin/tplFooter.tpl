<!-- MultiField tpl footer -->

{if $multi_formats}
    <script src="{$smarty.const.RL_PLUGINS_URL}multiField/static/lib.js"></script>

    <script>
    {literal}
    $(function(){
        var mfHandler = new mfHandlerClass();
        mfHandler.init('f', mfFields, mfFieldVals);
    });
    {/literal}
    </script>
{/if}

<!-- MultiField tpl footer end -->
