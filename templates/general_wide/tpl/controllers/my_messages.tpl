<!-- saved search tpl -->

{if !empty($contact)}	
	{if empty($messages)}
	
		<div class="text-message">{$lang.no_messages}</div>
		
	{else}

		<div id="messages_cont">
			<ul id="messages_area">
				{include file='blocks'|cat:$smarty.const.RL_DS|cat:'messages_area.tpl'}
			</ul>
		</div>

		{if $contact.Username != 'visitor'}
		<div class="send-controls">
			<textarea rows="4" cols="" id="message_text"></textarea>
			<div><input onclick="xajax_sendMessage('{$contact.ID}', $('#message_text').val(), {if $contact.Admin}1{else}0{/if});" type="button" value="{$lang.send}" /></div>
		</div>		
		{/if}

		{addJS file='//maps.googleapis.com/maps/api/js?libraries=places&language='|cat:$smarty.const.RL_LANG_CODE|cat:'&key='|cat:$config.google_map_key}

		<script type="text/javascript">
		var period = {$config.messages_refresh};
		var message_count = 0;

		{if $contact.ID >= 0}//disable refresh in visitor mode
		{literal}
		setInterval(function(){
			{/literal}xajax_refreshMessagesArea('{$contact.ID}', 0, 0, {if $contact.Admin}1{else}0{/if});{literal}
		}, period*1000);
		{/literal}
		{/if}
		{literal}
		
		$(document).ready(function(){
			message_count = $('ul#messages_area > li').length;

			$('#messages_cont').mCustomScrollbar({
				advanced: {	updateOnContentResize: true }
			});
			$('#messages_cont').mCustomScrollbar('scrollTo', 'bottom');
			
			messageRemoveHandler();

			$('#message_text').textareaCount({
				'maxCharacterSize': rlConfig['messages_length'],
				'warningNumber': 20
			});
			
			$('#message_text').keydown( function(e) {
				if ( e.ctrlKey && e.keyCode == 13 ) {
					{/literal}xajax_sendMessage('{$contact.ID}', $(this).val(), {if $contact.Admin}1{else}0{/if});{literal}
				}
			});

			flynaxTpl.accountDetails();
		});
		

		var messageRemoveHandler = function() {
			$('#messages_area li > span').each(function(){
				var id = $(this).parent().attr('id').split('_')[1];
				$(this).flModal({
					caption: '{/literal}{$lang.warning}{literal}',
					content: '{/literal}{$lang.remove_message_notice}{literal}',
					prompt: 'mRemoveMsg('+id+')',
					width: 'auto',
					height: 'auto'
				});
			});
		}

		var checkboxControl = function(){
			messageRemoveHandler();

			var length = $('ul#messages_area > li').length;

			if ( length > message_count ) {
				$('#messages_cont').mCustomScrollbar('scrollTo', 'bottom');
			}

			message_count = length;
		}

		var mRemoveMsg = function(id) {			
			if ( id ) {
				{/literal}xajax_removeMsg(id, {$contact.ID}, {if $contact.Admin}1{else}0{/if});{literal}
			}
		}
		
		{/literal}
		</script>
		
	{/if}

{else}

	{if !empty($contacts)}
		<div class="content-padding">
    		<table class="list contacts-list">
    		<tr class="header">
    			<td class="last">
    				<label><input class="inline" type="checkbox" id="check_all" /></label>
    			</td>
    			<td class="user">{$lang.user}</td>
    			<td>{$lang.message}</td>
    		</tr>

    		{foreach from=$contacts item='item' name='searchF' key='contact_id'}
    			{assign var='status_key' value=$item.Status}
    			<tr class="body" id="item_{$contact_id|replace:'@':''|replace:'.':''}">
    				<td>
    					<label><input type="checkbox" name="del_mess" class="inline del_mess {if $item.Admin}admin{/if}" id="contact_{$item.From}" {if $item.Visitor_mail}attr="{$item.Visitor_mail}"{/if} /></label>
    				</td>
    				<td valign="top">
    					<div class="picture{if !$item.Photo} no-picture{/if}">
    						<a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html?id={$item.From}{else}?page={$pageInfo.Path}&id={$item.From}{/if}{if $item.Admin}&administrator{/if}{if $item.Visitor_mail}&visitor_mail={$item.Visitor_mail}{/if}" title="{$lang.chat_with} {$item.Full_name}">
    							<img class="account-picture"
                                    style="{strip}
                                        width:{if $item.Thumb_width}{$item.Thumb_width}{else}110{/if}px;
                                        height:{if $item.Thumb_height}{$item.Thumb_height}{else}100{/if}px;
                                    {/strip}" 
                                    alt="{$item.Full_name}" 
                                    src="{if $item.Photo}{$smarty.const.RL_FILES_URL}{$item.Photo}{else}{$rlTplBase}img/blank.gif{/if}"
                                    {if $item.Photo_x2}
                                    srcset="{$smarty.const.RL_FILES_URL}{$item.Photo_x2} 2x"
                                    {/if}
                                    />
    							{if $item.Status == 'new' && $item.Count > 0}<span title="{$item.Count} {$lang.new_message}" class="new"></span>{/if}
    						</a>
    					</div>
    				</td>
    				<td class="info">
    					<div class="name">{$item.Full_name}{if $item.Admin} <span>({$lang.website_admin})</span>{/if}{if $item.Visitor_mail} <span>({$lang.website_visitor})</span>{/if} {if $item.Status == 'new'}<span title="{$item.Count} {$lang.new_message}" class="new"></span>{/if}</div>
    					<div class="date">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>

    					<a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html?id={$item.From}{else}?page={$pageInfo.Path}&id={$item.From}{/if}{if $item.Admin}&administrator{/if}{if $item.Visitor_mail}&visitor_mail={$item.Visitor_mail}{/if}">{$item.Message|nl2br|replace:'\n':'<br />'|truncate:120}</a>
    				</td>
    			</tr>
    		{/foreach}
    		</table>

    		<div class="mass-actions">
    			<a class="close remove_contacts" href="javascript:void(0)" title="{$lang.remove_selected_messages}">{$lang.remove_selected}</a>
    		</div>
		</div>

		<script type="text/javascript">{literal}
		$(document).ready(function(){
			$('.del_mess').click(function(){
				if ( $('.del_mess:checked').length == 0 ) {
					$('#check_all').attr('checked', false);
				}
			});

			$('#check_all').click(function(){
				if ( $(this).is(':checked') ) {
					$('.del_mess').attr('checked', true);
				}
				else {
					$('.del_mess').attr('checked', false);
				}
			});

            $('.remove_contacts').click(function(){
                var ids = '';
                var admin = false;

                $('.del_mess').each(function(){
                    if ($(this).is(':checked')) {
                        if ($(this).attr('attr')) {
                            ids += ids ? ',' + $(this).attr('attr') : $(this).attr('attr');
                        } else {
                            admin = $(this).hasClass('admin');

                            ids += ids 
                                ? ',' + $(this).attr('id').split('_')[1] + (admin ? '_admin' : '')
                                : $(this).attr('id').split('_')[1] + (admin ? '_admin' : '');
                        }
                    }
                });

                if (ids != '') {
                    $(this).flModal({
                        caption: '{/literal}{$lang.warning}{literal}',
                        content: '{/literal}{$lang.remove_contact_notice}{literal}',
                        prompt: 'xajax_removeContacts("' + ids + '")',
                        width: 'auto',
                        height: 'auto',
                        click: false
                    });
                }
            });
		});
		{/literal}</script>
	{else}
		<div class="text-message">{$lang.no_messages}</div>
	{/if}
{/if}

<!-- saved search tpl end -->
