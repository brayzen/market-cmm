<!-- shopping cart tpl | template file -->

{if $config.shc_module && !$config.shc_use_box && $aHooks.shoppingCart}
    {assign var='shc_total' value='0.00'}
    {foreach from=$shcItems item='item'}
        {math equation="total + (price * quantity)" total=$shc_total quantity=$item.Quantity price=$item.Price assign='shc_total'}  
    {/foreach}

    <span class="circle cart-box-container selector">
        <span class="default">
            <span class="button"><span class="count">{$shcItems|@count}</span>&nbsp;{$lang.shc_count_items} / <span class="summary">{$shc_total|number_format} {$config.system_currency}</span></span>
        </span>
        <span class="content hide">
            <ul id="shopping_cart_block" class="cart-items">
                {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart'|cat:$smarty.const.RL_DS|cat:'cart_items_responsive_42.tpl' shcItems=$shcItems}
            </ul>
        </span>
    </span>

    <script type="text/javascript">
        var shc_template = '{$config.template}';

        {literal} 
        $(document).ready(function() {
            $('.clear-cart').live('click', function() {
                $('.clear-cart').flModal({
                    caption: '',
                    content: '{/literal}{$lang.shc_do_you_want_clear_cart}{literal}',
                    prompt: 'xajax_clearShoppingCart',
                    width: 'auto',
                    height: 'auto',
                    click: false
                });
            });
        });
        {/literal} 
    </script>
{/if}

<!-- shopping cart tpl end -->