{if $isLogin}
	<div class="welcome">{$lang.welcome}, {$isLogin}!</div>
	
	<ul class="account-menu-content">
		{foreach from=$account_menu item='mItem'}
			{if $mItem.Key == 'my_messages' && !$config.messages_module}{else}
				<li>
					<a {if $page == $mItem.Path}class="font1"{/if} title="{$mItem.title}" href="{$rlBase}{if $config.mod_rewrite}{if $mItem.Path != ''}{$mItem.Path}.html{$mItem.Get_vars}{/if}{else}{if $mItem.Path != ''}?page={$mItem.Path}{$mItem.Get_vars|replace:'?':'&amp;'}{/if}{/if}">{$mItem.name}</a>
					{if $mItem.Key == 'my_messages' && $new_messages}
						<a class="counter" title="{$lang.new_message_available|replace:'[count]':$new_messages}" href="{$rlBase}{if $config.mod_rewrite}{$pages.my_messages}.html{else}?page={$pages.my_messages}{/if}">{$new_messages}</a>
					{/if}
				</li>
			{/if}
		{/foreach}
		
		{rlHook name='afterAccountMenu'}
	
		<li><a title="{$lang.title_logout}" href="{$rlBase}{if $config.mod_rewrite}{if $mItem.Path != ''}{$pages.login}.html?action=logout{/if}{else}{if $mItem.Path != ''}?page={$pages.login}&amp;action=logout{/if}{/if}">{$lang.logout}</a></li>
	</ul>
	
{else}
	<form {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}onsubmit="return false;"{/if} class="login-form" action="{$rlBase}{if $config.mod_rewrite}{$pages.login}.html{else}?page={$pages.login}{/if}" method="post">
		<input type="hidden" name="action" value="login" />
		
		{if $loginAttemptsLeft > 0 && $config.security_login_attempt_user_module}
			<div class="attention">
				{$loginAttemptsMess}
			</div>
		{elseif $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}
			<div class="attention">
				{assign var='periodVar' value=`$smarty.ldelim`period`$smarty.rdelim`}
				{assign var='replace' value='<b>'|cat:$config.security_login_attempt_user_period|cat:'</b>'}
				{assign var='regReplace' value='<span class="red">$1</span>'}
				{$lang.login_attempt_error|replace:$periodVar:$replace|regex_replace:'/\[(.*)\]/':$regReplace}
			</div>
		{/if}
		
		{if isset($request_page)}
			<input type="hidden" name="regirect" value="{$request_page}" />
		{/if}

		<input {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled" class="disabled"{/if} name="username" type="text" maxlength="35" value="{$smarty.post.username}" placeholder="{if $config.account_login_mode == 'email'}{$lang.mail}{else}{$lang.username}{/if}" />
		
		<input {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled" class="disabled"{/if} name="password" type="password" maxlength="35" placeholder="{$lang.password}" />
		
		<div class="button">
			<input {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled" class="disabled"{/if} type="submit" value="{$lang.login}" />
			{if $aHooks.facebookConnect && $config.facebookConnect_module && $config.facebookConnect_appid && $config.facebookConnect_secret && $config.facebookConnect_account_type}
				<a title="{$lang.fConnect_login_title}" class="fb-connect" onclick="fcLogin();" href="javascript://">{$lang.fConnect_connect}</a>
			{/if}
		</div>
        
        {if $config.remember_me}
            <div class="remember-me">
                <label><input type="checkbox" name="remember_me" checked="checked" />{$lang.remember_me}</label>
            </div>
        {/if}

		{$lang.forgot_pass} <a title="{$lang.remind_pass}" href="{$rlBase}{if $config.mod_rewrite}{$pages.remind}.html{else}?page={$pages.remind}{/if}">{$lang.remind_pass}</a>
        {if $pages.registration}
            <div class="divider">{$lang.new_here} <a title="{$lang.create_account}" href="{$rlBase}{if $config.mod_rewrite}{$pages.registration}.html{else}?page={$pages.registration}{/if}">{$lang.create_account}</a></div>
        {/if}
    </form>
{/if}