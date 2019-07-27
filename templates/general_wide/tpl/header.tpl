{include file='head.tpl'}

	<div class="main-wrapper">
		<header class="page-header{if $pageInfo.Key == 'search_on_map'} fixed-menu{/if}">
			<div class="point1 clearfix">
				<div class="top-navigation">
					<div class="point1">
						{include file='blocks'|cat:$smarty.const.RL_DS|cat:'lang_selector.tpl'}

						<div class="fright">
							{include file='blocks'|cat:$smarty.const.RL_DS|cat:'shopping_cart.tpl'}
							{include file='blocks'|cat:$smarty.const.RL_DS|cat:'user_navbar.tpl'}
							{rlHook name='tplHeaderUserNav'}
						</div>
					</div>
				</div>
				<section class="main-menu">
					<div class="point1">
						<div id="logo">
							<a href="{$rlBase}" title="{$config.site_name}">
								<img alt="{$config.site_name}" 
                                    src="{$rlTplBase}img/logo.png" 
                                    srcset="{$rlTplBase}img/@2x/logo.png 2x" />
							</a>
						</div>

						<nav class="menu">
							{include file='menus'|cat:$smarty.const.RL_DS|cat:'main_menu.tpl'}
						</nav>
					</div>
				</section>
			</div>
		</header>
