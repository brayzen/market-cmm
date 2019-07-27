<!-- saved search tpl -->

<div class="content-padding" id="saved_search_obj">
	{if !empty($saved_search)}
		<div class="list-table statuses" id="saved_search">
			<div class="header">
				<div class="checkbox" style="width: 40px;"><label><input class="inline all" type="checkbox" /></label></div>
				<div class="center" style="width: 30px;">#</div>
				<div>{$lang.criteria}</div>
				<div style="width: 160px;">{$lang.last_check}</div>
				<div style="width: 80px;">{$lang.status}</div>
			</div>

			{foreach from=$saved_search item='item' name='searchF'}
				{assign var='status_key' value=$item.Status}
				<div class="row" id="item_{$item.ID}">
					<div class="checkbox action no-flex"><label><input class="inline" value="{$item.ID}" type="checkbox" /></label></div>
					<div class="center iteration no-flex">{$smarty.foreach.searchF.iteration}</div>
					<div data-caption="{$lang.criteria}" class="content">
						<table class="table">
						{foreach from=$item.fields item='field'}
							{include file='blocks'|cat:$smarty.const.RL_DS|cat:'saved_search_field.tpl'}
						{/foreach}
						</table>
					</div>
					<div data-caption="{$lang.last_check}" class="date-cell">
						<span class="title">{$lang.last_check}:</span></span>
						<div class="text" style="padding: 0 0 5px;">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
						<a class="do-search" href="javascript:void(0)" id="search_{$item.ID}">{$lang.check_search}</a>
					</div>
					<div data-caption="{$lang.status}" class="status-cell"><span class="title">{$lang.status}:</span> <span id="status_{$item.ID}"><span class="{$status_key}">{$lang.$status_key}</span></span></div>
				</div>
			{/foreach}
		</div>
		<div id="mass_actions" class="hide mass-actions">{strip}
			<a id="activate" href="javascript:void(0);" title="{$lang.activate}">{$lang.activate}</a>
			<a id="deactivate" href="javascript:void(0);" title="{$lang.deactivate}">{$lang.deactivate}</a>
			<a id="delete" href="javascript:void(0);" title="{$lang.delete}">{$lang.delete}</a>
		{/strip}</div>
	
		<script type="text/javascript">
		{literal}
		
		$(document).ready(function(){
			$('a.do-search').click(function(){
				var id = $(this).attr('id').split('_')[1];
				xajax_checkSavedSearch(id);
			});
			
			$('#saved_search input.all').click(function(){
				var status = $(this).is(':checked') ? true : false;
		
				$('#saved_search input').each(function(){
					$(this).attr('checked', status );
				});
			});
			
			$('#saved_search input').click(function(){
				var tab = false;
				
				$('#saved_search input').each(function(){
					if ( $(this).is(':checked') && !$(this).hasClass('all') ) {
						tab = true;
					}
				});
		
				if ( tab == true ) {
					$('#mass_actions').fadeIn('normal');
				}
				else {
					$('#saved_search input.all').attr('checked', false);
					$('#mass_actions').fadeOut('normal');
				}
			});
			
			$('#mass_actions a').click(function(){
				var items = '';
		
				$('#saved_search input').each(function(){
					if ($(this).is(':checked') && $(this).is(':visible')) {
						items += $(this).val()+"|";
					}
				});
		
				var action = $(this).attr('id');
				xajax_massSavedSearch(items, action);
			});
		});
		
		{/literal}
		</script>
	
	</div>
	
{else}
	<div class="info">{$lang.no_saved_search}</div>
{/if}

<!-- saved search tpl end -->