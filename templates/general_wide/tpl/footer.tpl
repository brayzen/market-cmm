		<footer class="page-footer">
			<div class="point1 clearfix">
				<span class="scroll-top"></span>

				<nav class="footer-menu">
					{include file='menus'|cat:$smarty.const.RL_DS|cat:'footer_menu.tpl'}
				</nav>

				{include file='footer_data.tpl'}
			</div>
		</footer>
		
		{rlHook name='tplFooter'}

	</div>

	{if !$isLogin}
		<div id="login_modal_source" class="hide">
			<div class="tmp-dom">
				<div class="caption_padding">{$lang.login}</div>
				
				{if $loginAttemptsLeft > 0 && $config.security_login_attempt_user_module}
					<div class="attention">{$loginAttemptsMess}</div>
				{elseif $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}
					<div class="attention">
						{assign var='periodVar' value=`$smarty.ldelim`period`$smarty.rdelim`}
						{assign var='replace' value='<b>'|cat:$config.security_login_attempt_user_period|cat:'</b>'}
						{assign var='regReplace' value='<span class="red">$1</span>'}
						{$lang.login_attempt_error|replace:$periodVar:$replace|regex_replace:'/\[(.*)\]/':$regReplace}
					</div>
				{/if}
				
				<form {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}onsubmit="return false;"{/if} action="{$rlBase}{if $config.mod_rewrite}{$pages.login}.html{else}?page={$pages.login}{/if}" method="post">
					<input type="hidden" name="action" value="login" />

					<div class="submit-cell">
						<div class="name">{$lang.username}</div>
						<div class="field">
							<input {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled" class="disabled"{/if} type="text" name="username" maxlength="35" value="{$smarty.post.username}" />
						</div>
					</div>
					<div class="submit-cell">
						<div class="name">{$lang.password}</div>
						<div class="field">
							<input {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled" class="disabled"{/if} type="password" name="password" maxlength="35" />
						</div>
					</div>

					<div class="submit-cell buttons">
						<div class="name"></div>
						<div class="field">
							<input {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled" class="disabled"{/if} type="submit" value="{$lang.login}" />

							<div style="padding: 10px 0 0 0;">{$lang.forgot_pass} <a title="{$lang.remind_pass}" class="brown_12" href="{$rlBase}{if $config.mod_rewrite}{$pages.remind}.html{else}?page={$pages.remind}{/if}">{$lang.remind}</a></div>
						</div>
					</div>
				</form>
			</div>
		</div>
	{/if}

	{displayCSS mode='footer'}

	{displayJS}
</body>
</html>