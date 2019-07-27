<!-- listing details -->

{if !$errors}

{if $config.map_module && $location}
    {addJS file='//maps.googleapis.com/maps/api/js?libraries=places&language='|cat:$smarty.const.RL_LANG_CODE|cat:'&key='|cat:$config.google_map_key}

	<script class="fl-js-dynamic">{literal}
	var map_data = {
		addresses: [['{/literal}{if $location.direct}{$location.direct}{else}{$location.search|escape:"javascript"}{/if}', '{$location.show}', '{if $location.direct}direct{else}geocoder{/if}{literal}']],
		phrases: {
			hide: '{/literal}{$lang.hide|escape:"javascript"}{literal}',
			show: '{/literal}{$lang.show|escape:"javascript"}{literal}',
			notFound: '{/literal}{$lang.location_not_found|escape:"javascript"}{literal}'
		},
		ready: function(base){
			base.map.set('mapTypeControl', true);
			base.map.set('mapTypeControlOptions', {
				style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
				position: google.maps.ControlPosition.BOTTOM_CENTER
			});
		},
		scrollWheelZoom: false,
		zoom: {/literal}{$config.map_default_zoom}{if $config.map_amenities && $amenities},{literal}
		localSearch: {
			caption: '{/literal}{$lang.local_amenity|escape:"javascript"}{literal}',
			services: [{/literal}
				{foreach from=$amenities item='amenity' name='amenityF'}
				['{$amenity.Key}', '{$amenity.name|escape:"javascript"}', {if $amenity.Default}'checked'{else}false{/if}]{if !$smarty.foreach.amenityF.last},{/if}
				{/foreach}
			{literal}]
		}
		{/literal}{/if}{literal}
	};
	{/literal}</script>
{/if}

<div class="listing-details details {if $config.map_module && $location}loc-exists{/if}">

	{rlHook name='listingDetailsTopTpl'}

	<section class="main-section">
		<div class="top-navigation">
			<div class="icons">{strip}
				{if $listing_data.Account_ID == $account_info.ID}
					<a class="button low" href="{$rlBase}{if $config.mod_rewrite}{$pages.edit_listing}.html?id={$listing_data.ID}{else}?page={$pages.edit_listing}&id={$listing_data.ID}{/if}">{$lang.edit_listing}</a>
				{else}
					{rlHook name='listingDetailsNavIcons'}

					<a rel="nofollow" target="_blank" href="{$rlBase}{if $config.mod_rewrite}{$pages.print}.html?item=listing&id={$listing_data.ID}{else}?page={$pages.print}&item=listing&id={$listing_data.ID}{/if}" title="{$lang.print_page}" class="print"><span></span></a>
					<span id="fav_{$listing_data.ID}" class="favorite add" title="{$lang.add_to_favorites}"><span class="icon"></span></span>
				{/if}
			{/strip}</div>
		</div>

		{if $photos}
			<div class="gallery{if !$photos} no-picture{/if}">
				<script class="fl-js-dynamic">
				var fb_slideshow = {if $config.gallery_slideshow}{literal}{}{/literal}{else}false{/if};
				var fb_slideshow_delay = {if $config.gallery_slideshow_delay}{$config.gallery_slideshow_delay}*1000{else}5000{/if};

				var photos_source = new Array();
				{foreach from=$photos item='photo' name='photosFJ'}
					photos_source.push({literal} { {/literal}
						large: '{$photo.Thumbnail}',
						locked: {if $allow_photos || $smarty.foreach.photosFJ.first}false{else}true{/if},
						{if $photo.Type == 'video'}
							href: '{$photo.href}',
							type: 'iframe',
							local: {if $photo.Original == 'youtube'}false{else}true{/if}
						{else}
							href: '{if $smarty.foreach.photosFJ.first || $allow_photos}{$photo.Photo}{else}{$rlTplBase}img/locked-large.png{/if}',
							title: '{if $photo.Description}{$photo.Description|escape:"javascript"}{else}{$pageInfo.name|escape:"javascript"}{/if}',
							type: 'image'
						{/if}
					{literal} } {/literal});
				{/foreach}
				</script>

				<div id="media" class="gallery{if $photos.0.Type == 'video'} video{/if}">
					<div class="preview">
						<iframe width="" height="" src="{if $photos.0.Type == 'video' && $photos.0.Original == 'youtube'}{$photos.0.href}{/if}" frameborder="0" allowfullscreen></iframe>
						<video id="player" class="hide" controls>
                            <source src="" type="video/mp4">
                        </video>
						<img title="{if $photos.0.Description}{$photos.0.Description}{else}{$pageInfo.name}{/if}" 
                            src="{if $photos.0.Photo}{$photos.0.Photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}" />
						{if !$allow_photos}
							<div id="picture_locked" class="hide">
								<div>
									<div class="restricted-content">
									<img src="{$rlTplBase}img/blank.gif" />
									{if $isLogin}
										<p class="picture-hint hide">{$lang.view_picture_not_available}</p>
										<p class="video-hint hide">{$lang.watch_video_not_available}</p>
										<span>
											<a class="button" title="{$lang.registration}" href="{pageUrl key='my_profile'}#membership">{$lang.change_plan}</a>
										</span>
									{else}
										<p class="picture-hint hide">{$lang.view_picture_hint}</p>
										<p class="video-hint hide">{$lang.watch_video_hint}</p>
										<span>
											<a href="javascript://" class="button login">{$lang.sign_in}</a> <span>{$lang.or}</span> <a title="{$lang.registration}" href="{pageUrl key='registration'}">{$lang.sign_up}</a>
										</span>
									{/if}
									</div>
								</div>
							</div>
						{/if}
					</div>
					<div class="map-container hide"></div>

					<div class="nav-buttons">
						<span class="nav-button zoom">{$lang.view_larger}</span>
						<span class="map-group">
							<span class="nav-button gallery">{$lang.gallery}</span>
							{if $config.map_module && $location}
								<span class="nav-button map">{$lang.map}</span>
							{/if}
							{if $aHooks.street_view && $location}
								<span class="nav-button street-view">{$lang.street_view_tab}</span>
							{/if}
						</span>
					</div>
				</div>

				<div class="thumbs{if $photos|@count == 1} hide{/if}">
					<div title="{$lang.prev}" class="prev disabled"></div>
					<div title="{$lang.next}" class="next"></div>
					<div class="slider">
						<ul class="swiper-wrapper">
							{assign var='replace_key' value=`$smarty.ldelim`key`$smarty.rdelim`}

							{foreach from=$photos item='photoItem' name='photosF'}{strip}
							<li class="swiper-slide{if $smarty.foreach.photosF.first} active{/if}{if $photoItem.Type == 'video' && $allow_photos} video{/if}{if !$allow_photos && !$smarty.foreach.photosF.first} locked{/if}">
								<img title="{if $photoItem.Description}{$photoItem.Description}{else}{$pageInfo.name}{/if}" 
                                    alt="{if $photoItem.Description}{$photoItem.Description}{else}{$pageInfo.name}{/if}" 
                                    src="{if $photoItem.Thumbnail && ($allow_photos || $smarty.foreach.photosF.first)}{$photoItem.Thumbnail}{else}{$rlTplBase}img/blank_10x7.gif{/if}" 
                                    {if $photoItem.Thumbnail_x2 && ($allow_photos || $smarty.foreach.photosF.first)}srcset="{$photoItem.Thumbnail_x2} 2x"{/if}
                                    {if $allow_photos || $smarty.foreach.photosF.first}
                                        data-background="{if $photoItem.Type == 'video'}{if $photoItem.Original == 'youtube'}{$l_youtube_thumbnail_hq|replace:$replace_key:$photoItem.Photo}{else}{$photoItem.Thumbnail}{/if}{else}{$photoItem.Photo}{/if}" 
                                        class="swiper-lazy"
                                    {/if} />

                                {if $photoItem.Type == 'video'}<span class="play"></span>{/if}

								{if !$allow_photos && !$smarty.foreach.photosF.first}
									<div class="restricted-content hide">
										{if $isLogin}
											<p>{if $photoItem.Type == 'video'}{$lang.watch_video_not_available}{else}{$lang.view_picture_not_available}{/if}</p>
											<span>
												<a class="button" title="{$lang.registration}" href="{pageUrl key='my_profile'}#membership">{$lang.change_plan}</a>
											</span>
										{else}
											<p>{if $photoItem.Type == 'video'}{$lang.watch_video_hint}{else}{$lang.view_picture_hint}{/if}</p>
											<span>
												<a href="javascript://" class="button login">{$lang.sign_in}</a> <span>{$lang.or}</span> <a title="{$lang.registration}" href="{pageUrl key='registration'}">{$lang.sign_up}</a>
											</span>
										{/if}
									</div>
								{/if}
							</li>
							{/strip}{/foreach}
						</ul>
					</div>
				</div>
			</div>
		{/if}
	</section>

	<section class="content-section clearfix">
		<!-- tabs -->
		{php}
			global $listing_type, $photos;

			if ($listing_type.Photo && $photos) {
				$tabs = $this -> get_template_vars('tabs');
				unset($tabs['streetView']);
				$this -> assign('tabs', $tabs);
			}
		{/php}

		{if $tabs|@count > 1}
			<ul class="tabs tabs-hash">
				{foreach from=$tabs item='tab' name='tabF'}{strip}
					<li {if $smarty.foreach.tabF.first}class="active"{/if} id="tab_{$tab.key}">
                        <a href="#{$tab.key}" data-target="{$tab.key}">{$tab.name}</a>
                    </li>
				{/strip}{/foreach}
			</ul>
		{/if}
		<!-- tabs end -->

		<!-- listing details -->
		<div id="area_listing" class="tab_area">
			<h1>{$pageInfo.name}</h1>

			<div class="two-inline clearfix">
				<!-- price tag -->
				{if $price_tag_value}
					<div class="price-tag" id="df_field_price">
                        <span>{$price_tag_value}</span>
                        {if $listing_data.sale_rent == 2 && $listing.common.Fields.time_frame.value}
                            / {$listing.common.Fields.time_frame.value}
                        {/if}
                    </div>
				{/if}
				<!-- price tag end -->

				{if $listing.common.Fields.bedrooms.value || $listing.common.Fields.bathrooms.value || $listing.common.Fields.square_feet.value}
					<ul class="ad-info">
						<li class="services">{strip}
							{if $listing.common.Fields.bedrooms.value}
								<span title="{$listing.fields.bedrooms.name}" class="badrooms">{$listing.common.Fields.bedrooms.value}</span>
							{/if}
							{if $listing.common.Fields.bathrooms.value}
								<span title="{$listing.fields.bathrooms.name}" class="bathrooms">{$listing.common.Fields.bathrooms.value}</span>
							{/if}
							{if $listing.common.Fields.square_feet.value}
								<span title="{$listing.fields.square_feet.name}" class="square_feet">{$listing.common.Fields.square_feet.value}</span>
							{/if}
						{/strip}</li>
					</ul>
				{/if}
			</div>
		
			{rlHook name='listingDetailsPreFields'}

            <div class="listing-fields">
			{foreach from=$listing item='group'}
				<div class="{if $group.Key}{$group.Key}{else}no-group{/if}{if $group.Key == 'common'} row{/if}">
					{if $group.Group_ID && $group.Key != 'common'}
						{assign var='hide' value=false}
						{if !$group.Display}
							{assign var='hide' value=true}
						{/if}
				
						{assign var='value_counter' value='0'}
						{foreach from=$group.Fields item='group_values' name='groupsF'}
							{if $group_values.value == '' || !$group_values.Details_page}
								{assign var='value_counter' value=$value_counter+1}
							{/if}
						{/foreach}
				
						{if !empty($group.Fields) && ($smarty.foreach.groupsF.total != $value_counter)}
							{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$group.ID name=$group.name hide=$hide}
							
							{foreach from=$group.Fields item='item' key='field' name='fListings'}
								{if !empty($item.value) && $item.Details_page}
									{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
								{/if}
							{/foreach}
							
							{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
						{/if}

						{assign var='main_section_no_group' value=false}
					{else}
						{if $group.Fields}
							{foreach from=$group.Fields item='item'}
								{if !empty($item.value) && $item.Details_page}
									{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
								{/if}
							{/foreach}
						{/if}
					{/if}
				</div>
			{/foreach}
            </div>

			<!-- statistics area -->
			<section class="statistics clearfix">
				<ul class="controls">
					<li>
						<!-- AddThis Button BEGIN -->
						<div class="addthis_toolbox addthis_default_style addthis_32x32_style">
						<a class="addthis_button_preferred_1"></a>
						<a class="addthis_button_preferred_2"></a>
						<a class="addthis_button_preferred_3"></a>
						<a class="addthis_button_preferred_4"></a>
						<a class="addthis_button_compact"></a>
						<a class="addthis_counter addthis_bubble_style"></a>
						</div>
						<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=xa-52d66a9b40223211"></script>
						<!-- AddThis Button END -->
					</li>
					{rlHook name='listingDetailsAfterStats'}
				</ul>
				<ul class="counters">
					{if $config.count_listing_visits}<li><span class="count">{$listing_data.Shows}</span> {$lang.shows}</li>{/if}
					{if $listing_data.comments_count}<li><a href="#comments"><span class="count">{$listing_data.comments_count}</span> {$lang.comment_tab}</a></li>{/if}
					{rlHook name='listingDetailsCounters'}
				</ul>
			</section>
			<!-- statistics area end -->
		</div>
		<!-- listing details end -->	

		{if $config.tell_a_friend_tab}
			<!-- tell a friend tab -->
			<div id="area_tell_friend" class="tab_area hide">
				<div class="content-padding">
					<div class="submit-cell">
						<div class="name">{$lang.friend_name} <span class="red">*</span></div>
						<div class="field"><input class="wauto" type="text" id="friend_name" name="friend_name" maxlength="50" size="30" value="{$smarty.post.friend_name}" /></div>
					</div>

					<div class="submit-cell">
						<div class="name">{$lang.friend_email} <span class="red">*</span></div>
						<div class="field"><input class="wauto" type="text" id="friend_email" name="friend_email" maxlength="50" size="30" value="{$smarty.post.friend_email}" /></div>
					</div>

					<div class="submit-cell">
						<div class="name">{$lang.your_name}</div>
						<div class="field"><input class="wauto" type="text" id="your_name" name="your_name" maxlength="100" size="30" value="{$account_info.Full_name}" /></div>
					</div>

					<div class="submit-cell">
						<div class="name">{$lang.your_email}</div>
						<div class="field"><input class="wauto" type="text" id="your_email" name="your_email" maxlength="30" size="30" value="{$account_info.Mail}" /></div>
					</div>

					<div class="submit-cell">
						<div class="name">{$lang.message}</div>
						<div class="field"><textarea id="message" name="message" rows="6" cols="50">{$smarty.post.message}</textarea></div>
					</div>

					{if $config.security_img_tell_friend}
					<div class="submit-cell">
						<div class="name">{$lang.security_code} <span class="red">*</span></div>
						<div class="field">
							{include file='captcha.tpl' no_caption=true}
						</div>
					</div>
					{/if}

					<div class="submit-cell buttons">
						<div class="name"></div>
						<div class="field"><input onclick="xajax_tellFriend($('#friend_name').val(), $('#friend_email').val(), $('#your_name').val(), $('#your_email').val(), $('#message').val(), $('#security_code').val(), '{$print.id}');$(this).val('{$lang.loading}');" type="button" name="finish" value="{$lang.send}" /></div>
					</div>
				</div>
			</div>
			<!-- tell a friend tab end -->
		{/if}

		<!-- tabs content -->
		{rlHook name='listingDetailsBottomTpl'}
		<!-- tabs content end -->	
	</section>

	<script class="fl-js-dynamic">
	{if isset($smarty.get.highlight)}
		flynaxTpl.highlightResults("{$smarty.session.keyword_search_data.keyword_search}", true);
	{/if}

	var ld_inactive = {if $pageInfo.Listing_details_inactive}'{$lang.ld_inactive_notice}'{else}false{/if};

	{literal}
		if ($('#df_field_vin .value').length > 0) {
			var html = '<a style="font-size: 14px;" href="javascript:void(0);">{/literal}{if $lang.check_vin}{$lang.check_vin}{else}Check Vin{/if}{literal}</a>';
			var vin = trim( $('#df_field_vin .value').text() );
			var frame = '<iframe scrolling="auto" height="600" frameborder="0" width="100%" src="http://www.carfax.com/cfm/check_order.cfm?vin='+vin+'" style="border: 0pt none;overflow-x: hidden; overflow-y: auto;background: white;"></iframe>';
			var source = '';
		}	
	{/literal}
	</script>

	{rlHook name='listingDetailsBottomJs'}

	<script class="fl-js-static">
	{literal}
	$(document).ready(function(){
		if (ld_inactive) {
			printMessage('warning', ld_inactive, false, true);
		}

		$('#df_field_vin .value').append(html);

		$('#df_field_vin .value a').flModal({
			content: frame,
			source: source,
			width: 900,
			height: 640
		});
	});
	{/literal}
	</script>

</div>
{else}
	<!-- TODO HERE -->
{/if}

<!-- listing details end -->
