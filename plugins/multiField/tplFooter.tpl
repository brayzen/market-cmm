{if $multi_formats}
    <script class="fl-js-dynamic">
    var mf_prefix = '{$mf_form_prefix}';
    {literal}
    $(function(){
        var mfHandler = new mfHandlerClass();
        mfHandler.init(mf_prefix, mfFields, mfFieldVals);
    });
    {/literal}
    </script>
{/if}
