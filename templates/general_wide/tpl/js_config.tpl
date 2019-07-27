<script type="text/javascript">
    var rlLangDir       = '{$smarty.const.RL_LANG_DIR}';
    var rlLang          = '{$smarty.const.RL_LANG_CODE|lower}';
    var isLogin         = {if $isLogin}true{else}false{/if};
    var staticDataClass = {if class_exists('rlStatic')}true{else}false{/if};

    var lang                                      = new Array();
    lang['photo']                                 = '{$lang.photo}';
    lang['of']                                    = '{$lang.of}';
    lang['close']                                 = '{$lang.close}';
    lang['add_photo']                             = '{$lang.add_photo}';
    lang['from']                                  = '{$lang.from}';
    lang['to']                                    = '{$lang.to}';
    lang['remove_from_favorites']                 = '{$lang.remove_from_favorites}';
    lang['add_to_favorites']                      = '{$lang.add_to_favorites}';
    lang['notice_listing_removed_from_favorites'] = '{$lang.notice_listing_removed_from_favorites}';
    lang['no_favorite']                           = '{$lang.no_favorite}';
    lang['password_strength_pattern']             = '{$lang.password_strength_pattern}';
    lang['notice_reg_length']                     = '{$lang.notice_reg_length}';
    lang['notice_pass_bad']                       = '{$lang.notice_pass_bad}';
    lang['password']                              = '{$lang.password}';
    lang['loading']                               = '{$lang.loading}';
    lang['password_weak_warning']                 = '{$lang.password_weak_warning}';
    lang['manage']                                = '{$lang.manage}';
    lang['done']                                  = '{$lang.done}';
    lang['cancel']                                = '{$lang.cancel}';
    lang['delete']                                = '{$lang.delete}';
    lang['warning']                               = '{$lang.warning}';
    lang['notice']                                = '{$lang.notice}';
    lang['gateway_fail']                          = '{$lang.notice_payment_gateway_does_not_chose}';
    lang['characters_left']                       = '{$lang.characters_left}';
    lang['notice_bad_file_ext']                   = '{$lang.notice_bad_file_ext}';
    lang['save_search_confirm']                   = '{$lang.save_search_confirm}';
    lang['no_browser_gps_support']                = '{$lang.no_browser_gps_support}';
    lang['gps_support_denied']                    = '{$lang.gps_support_denied}';
    lang['map_listings_request_fail']             = '{$lang.map_listings_request_fail}';
    lang['map_search_limit_warning']              = '{$lang.map_search_limit_warning}';
    lang['show_subcategories']                    = '{$lang.show_subcategories}';
    lang['invalid_file_extension']                = "{$lang.invalid_file_extension}";
    lang['system_error']                          = "{$lang.system_error}";
    lang['count_more_pictures']                   = '{$lang.count_more_pictures}';
    lang['price']                                 = '{$lang.price}';
    lang['nothing_found_for_char']                = '{$lang.nothing_found_for_char}';
    lang['delete_file']                           = '{$lang.delete_file}';
    lang['confirm_notice']                        = '{$lang.confirm_notice}';
    lang['any']                                   = '{$lang.any}';
    lang['account_remove_notice']                 = '{$lang.account_remove_notice}';
    lang['account_remove_notice_pass']            = '{$lang.account_remove_notice_pass}';
    lang['delete_account']                        = '{$lang.delete_account}';
    lang['password_lenght_fail']                  = '{$lang.password_lenght_fail}';
    lang['account_remove_in_process']             = '{$lang.account_remove_in_process}';
    lang['error_max_size']                        = '{$lang.error_maxFileSize}';

    var rlPageInfo           = new Array();
    rlPageInfo['key']        = '{$pageInfo.Key}';
    rlPageInfo['controller'] = '{$pageInfo.Controller}';
    rlPageInfo['path']       = '{if $pageInfo.Path_real}{$pageInfo.Path_real}{else}{$pageInfo.Path}{/if}';

    var rlConfig                                 = new Array();
    rlConfig['seo_url']                          = '{$rlBase}';
    rlConfig['tpl_base']                         = '{$rlTplBase}';
    rlConfig['files_url']                        = '{$smarty.const.RL_FILES_URL}';
    rlConfig['libs_url']                         = '{$smarty.const.RL_LIBS_URL}';
    rlConfig['plugins_url']                      = '{$smarty.const.RL_PLUGINS_URL}';
    rlConfig['ajax_url']                         = '{$smarty.const.RL_URL_HOME}request.ajax.php';
    rlConfig['mod_rewrite']                      = {$config.mod_rewrite};
    rlConfig['sf_display_fields']                = {$config.sf_display_fields};
    rlConfig['account_password_strength']        = {$config.account_password_strength};
    rlConfig['messages_length']                  = {if $config.messages_length}{$config.messages_length}{else}250{/if};
    rlConfig['pg_upload_thumbnail_width']        = {if $config.pg_upload_thumbnail_width}{$config.pg_upload_thumbnail_width}{else}120{/if};
    rlConfig['pg_upload_thumbnail_height']       = {if $config.pg_upload_thumbnail_height}{$config.pg_upload_thumbnail_height}{else}90{/if};
    rlConfig['thumbnails_x2']                    = {if $config.thumbnails_x2}true{else}false{/if};
    rlConfig['template_type']                    = {if $tpl_settings.type}'{$tpl_settings.type}'{else}false{/if};
    rlConfig['domain']                           = '{$domain_info.domain}';
    rlConfig['domain_path']                      = '{$domain_info.path}';
    rlConfig['map_search_listings_limit']        = {if $config.map_search_listings_limit}{$config.map_search_listings_limit}{else}500{/if};
    rlConfig['map_search_listings_limit_mobile'] = {if $config.map_search_listings_limit_mobile}{$config.map_search_listings_limit_mobile}{else}75{/if};
    rlConfig['price_delimiter']                  = {if $config.price_delimiter == '"'}'{$config.price_delimiter}'{else}"{$config.price_delimiter}"{/if};
    rlConfig['price_separator']                  = "{$config.price_separator}";
    rlConfig['random_block_slideshow_delay']     = '{$config.random_block_slideshow_delay}';
    rlConfig['template_name']                    = '{$tpl_settings.name}';
    rlConfig['upload_max_size']                  = {$upload_max_size};

    var rlAccountInfo = new Array();
    rlAccountInfo['ID'] = {if $account_info}{$account_info.ID}{else}null{/if};

    flynax.langSelector();

    var qtip_style = new Object({literal}{{/literal}
        width      : '{if $tpl_settings.qtip.width}{$tpl_settings.qtip.width}{else}auto{/if}',
        background : '#{if $tpl_settings.qtip.background}{$tpl_settings.qtip.background}{else}396932{/if}',
        color      : '#{if $tpl_settings.qtip.color}{$tpl_settings.qtip.color}{else}ffffff{/if}',
        tip        : '{if $tpl_settings.qtip.tip}{$tpl_settings.qtip.tip}{else}bottomLeft{/if}',
        border     : {literal}{{/literal}
            width  : {if $tpl_settings.qtip.b_width}{$tpl_settings.qtip.b_width}{else}7{/if},
            radius : {if $tpl_settings.qtip.b_radius}{$tpl_settings.qtip.b_radius}{else}0{/if},
            color  : '#{if $tpl_settings.qtip.b_color}{$tpl_settings.qtip.b_color}{else}396932{/if}'
        {literal}}
    }{/literal});
</script>

{php}
    if (in_array($GLOBALS['page_info']['Controller'], array('listing_details', 'listing_type'))) {
        $this->assign('navIcons', ' ');
    }
{/php}
