<!-- user navigation bar -->

{*assign var='aa_phrase' value='blocks+name+account_area'*}

<span class="circle{if $isLogin} logged-in{/if}{if $new_messages} notify{/if}" id="user-navbar">
	<span class="default"><span>{if $isLogin}{$isLogin}{else}{$lang.login}{/if}</span></span>
	<span class="content {if $isLogin}a-menu{/if} hide">
		{if $isLogin}
			<ul>
			{foreach from=$account_menu item='mItem'}
				{if $mItem.Key == 'my_messages' && !$config.messages_module}{continue}{/if}
				<li>
					<a class="font2{if $page == $mItem.Path} active{/if}" title="{$mItem.title}" href="{if $mItem.Page_type == 'external'}{$mItem.Controller}{else}{$rlBase}{if $config.mod_rewrite}{if $mItem.Path != ''}{$mItem.Path}.html{$mItem.Get_vars}{/if}{else}{if $mItem.Path != ''}?page={$mItem.Path}{$mItem.Get_vars|replace:'?':'&'}{/if}{/if}{/if}">{$mItem.name}</a>
					{if $mItem.Key == 'my_messages' && $new_messages}
						<a class="counter" title="{$lang.new_message_available|replace:'[count]':$new_messages}" href="{$rlBase}{if $config.mod_rewrite}{$pages.my_messages}.html{else}?page={$pages.my_messages}{/if}">{$new_messages}</a>
					{/if}
				</li>
			{/foreach}
				<li><a class="font2 logout" title="{$lang.title_logout}" href="{$rlBase}{if $config.mod_rewrite}{$pages.login}.html?action=logout{else}?page={$pages.login}&amp;action=logout{/if}">{$lang.logout}</a></li>
			</ul>
			
			{rlHook name='tplAfterAccountMenu'}
		{else}
			<span class="user-navbar-container">
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
					
					<input placeholder="{if $config.account_login_mode == 'email'}{$lang.mail}{else}{$lang.username}{/if}" type="text" name="username" maxlength="100" value="{$smarty.post.username}" {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled"{/if} />
					<input placeholder="{$lang.password}" type="password" name="password" maxlength="100" {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled"{/if} />
					
					<div class="nowrap">
					<input type="submit" value="{$lang.login}" {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled"{/if} />
					<span class="hookUserNavbar">{rlHook name='tplUserNavbar'}</span>
					</div>

                    {if $config.remember_me}
                        <div class="remember-me">
                            <label><input type="checkbox" name="remember_me" checked="checked" />{$lang.remember_me}</label>
                        </div>
                    {/if}

					<span class="links">
						<span><a title="{$lang.remind_pass}" class="font2" href="{$rlBase}{if $config.mod_rewrite}{$pages.remind}.html{else}?page={$pages.remind}{/if}">{$lang.forgot_pass}</a></span>
						{if $pages.registration}
							<a title="{$lang.create_account}" class="font2" href="{$rlBase}{if $config.mod_rewrite}{$pages.registration}.html{else}?page={$pages.registration}{/if}">{$lang.registration}</a>
						{/if}
					</span>
				</form>
			</span>
		{/if}
	</span>
	
	{*<span class="mobile-hidden">
		{if $isLogin}
			<span class="font1">{$lang.welcome},</span> <a href="{$rlBase}{if $config.mod_rewrite}{$pages.login}.html{else}?page={$pages.login}{/if}">{$account_info.Username}!</a>
		{else}
			<a title="{$lang.login}" href="{$rlBase}{if $config.mod_rewrite}{$pages.login}.html{else}?page={$pages.login}{/if}">{$lang.login}</a><a title="{$lang.create_account}" href="{$rlBase}{if $config.mod_rewrite}{$pages.registration}.html{else}?page={$pages.registration}{/if}">{$lang.registration}</a>
		{/if}
	</span>*}
</span>

<!-- user navigation bar end -->