<!-- account item -->

<article class="col-sm-4{if $dealer.Thumb_width <= 120} col-md-{if $side_bar_exists}6{else}4{/if} col-lg-4{else}{if !$side_bar_exists} col-md-3{/if}{/if}">
	<div class="main-container clearfix{if $dealer.Thumb_width > 120} landscape{/if}{if !$dealer.Photo} no-picture{/if}" {if $dealer.Thumb_width > 120}style="width: {$dealer.Thumb_width}px;"{/if}>
        <a title="{$dealer.Full_name}" href="{$dealer.Personal_address}">
    		<div class="picture" style="width: {$dealer.Thumb_width}px; height: {$dealer.Thumb_height}px;">
				{if $dealer.Photo}
                    <img alt="{$dealer.Full_name}"
                        src="{$smarty.const.RL_FILES_URL}{$dealer.Photo}"
                        {if $dealer.Photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$dealer.Photo_x2} 2x"{/if} />
                {/if}
    		</div>
        </a>

		<div class="statistics">
			<ul>
				<li class="name">
					<a title="{$lang.visit_owner_page}" href="{$dealer.Personal_address}">{if $dealer.company_name}{$dealer.company_name}{else}{$dealer.Full_name}{/if}</a>
				</li>
				{*<li class="date" title="{$lang.join_date}">
					{assign var='type_replace' value=`$smarty.ldelim`account_type`$smarty.rdelim`}
					{assign var='date_replace' value=`$smarty.ldelim`date`$smarty.rdelim`}
					{assign var='date' value=$dealer.Date|date_format:$smarty.const.RL_DATE_FORMAT}

					{$lang.account_type_since_data|replace:$type_replace:$dealer.Type_name|replace:$date_replace:$date}
				</li>*}
				{rlHook name='accountAfterStats'}
			</ul>

			{if $dealer.Listings_count}
				<div class="counter">
					<span>{$dealer.Listings_count}</span>
					<span>{$lang.listings}</span>
				</div>
			{/if}
		</div>
	</div>

	<ul class="info">
		<li class="fields">
			{assign var='phone' value=false}
			{assign var='inline_fields' value=false}

			{foreach from=$dealer.fields item='item' key='field'}
				{if $item.Key == 'First_name' || $item.Key == 'Last_name' || $item.Key == 'company_name'}{continue}{/if}

				{if !empty($item.value) && $item.Details_page}
					{if $item.Key|strpos:'phone' || $item.Type == 'phone'}
						{assign var='phone' value=$item.value}
					{else}
						{assign var='inline_fields' value=$inline_fields|cat:', '|cat:$item.value}
						<span>{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out_value.tpl'}</span>
					{/if}
				{/if}
			{/foreach}

			{rlHook name='accountAfterFields'}
		</li>

		{if $phone}
			<li class="tel">
				<a dir="ltr" href="{if $allow_contacts}tel:{$phone}{else}javascript://{/if}">{$phone}</a>
			</li>
		{/if}
	</ul>

	{if $dealer.Loc_latitude && $dealer.Loc_longitude}
		<script class="fl-js-dynamic">
            var html_container = '<div class="map-balloon-account">';
            html_container += '<div class="picture" style="width: {$dealer.Thumb_width}px;height: {$dealer.Thumb_height}px;">';
            html_container += '<a title="{$dealer.Full_name|escape}" href="{$dealer.Personal_address}">';
            html_container += '<img alt="{$dealer.Full_name}" ';
            html_container += 'src="{if $dealer.Photo}{$smarty.const.RL_FILES_URL}{$dealer.Photo}{else}{$rlTplBase}img/blank.gif{/if}" ';
            html_container += '{if $dealer.Photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$dealer.Photo_x2} 2x"{/if} />';
            html_container += '</a></div>';

            html_container += '<div class="statistics two-inline left clearfix">';
            html_container += '<div>{if $dealer.Listings_count}{$dealer.Listings_count} {$lang.listings}{/if}</div>';
            html_container += '<div style="text-align: {$text_dir_rev};">{$dealer.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div></div>';
            html_container += '<ul class="info">';
            html_container += '<li class="name">';
            html_container += '<a title="{$lang.visit_owner_page}" href="{$dealer.Personal_address}">{$dealer.Full_name}</a>';
            html_container += '</li>';
            html_container += '<li class="fields">{$inline_fields|ltrim:', '}</li>';
            html_container += '{if $phone}<li class="tel"><a href="tel:{$phone}">{$phone}</a></li>{/if}';
            html_container += '</ul></div>';

            accounts_map.push(new Array(
                '{$dealer.Loc_latitude},{$dealer.Loc_longitude}',
                html_container,
                'direct'
            ));
        </script>
	{/if}
</article>

<!-- account item end -->
