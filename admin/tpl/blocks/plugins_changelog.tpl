<!-- plugins changelog block -->

<script type="text/javascript">//<![CDATA[
var actions_locked              = false;
var plugin_is_installing_phrase = '{$lang.plugin_is_installing|escape:"javascript"}';
var plugin_is_updating_phrase   = '{$lang.plugin_is_updating|escape:"javascript"}';
var plugin_obj                  = false;
var plugin_install_notice       = '{$lang.remote_plugin_install_notice|escape:"javascript"}';
var plugin_connect_phrase       = '{$lang.remote_progress_connect|escape:"javascript"}';
var plugin_installing_phrase    = '{$lang.remote_progress_installing|escape:"javascript"}';
var plugin_updating_phrase      = '{$lang.remote_progress_updating|escape:"javascript"}';
var plugin_update_notice        = '{$lang.remote_plugin_update_notice|escape:"javascript"}';
var plugin_update_backingup     = '{$lang.remote_progress_backingup|escape:"javascript"}';
{literal}

var rlPluginRemoteInstall = function(){
    // install links handler
    $('.changelog_install a.install_icon').click(function(){
        if ( !actions_locked )
        {
            plugin_obj = this;
            rlConfirm(plugin_install_notice, 'startInstallation');
        }
    });
    
    // update links handler
    $('.changelog_update a.update_icon').click(function(){
        if ( !actions_locked )
        {
            plugin_obj = this;
            rlConfirm(plugin_update_notice, 'startUpdating');
        }
    });

    // buy button handler
    $('a.buy_icon').click(function(){
        startBuying($(this), $(this).text());
    });
};

var buyingInterval;
var startBuying = function(button, caption){
    var key = button.attr('name');
    
    button.flModal({
        click: false,
        width: 515,
        height: 532,
        caption: caption,
        onClose: function(){
            setTimeout(function(){
                clearInterval(buyingInterval);
                clearTimeout(buyingInterval);
            }, 10000);
        },
        content: '<iframe name="buy-plugin" src="https://www.flynax.com/buy-plugin.html?key='+key+'&domain={/literal}{$license_domain}&license={$license_number}{literal}" frameborder="0" scrolling="auto" width="100%" height="100%"></iframe>'
    });

    $('iframe[name=buy-plugin]').on('load', function(e){
        setTimeout(function(){
            buyingInterval = setInterval('updatePluginStatus("'+key+'")', 10000);
        }, 10000);
    });
}

var updatePluginStatus = function(key){
    // track paid plugins
    $.getJSON(rlUrlHome+'request.ajax.php', {item: 'updatePluginStatus', key: key, {/literal}domain: '{$license_domain}', license: '{$license_number}'{literal}}, function(status){
        // stop checking
        if ( status.status == 'paid' || status.status == 'fail' ) {
            clearInterval(buyingInterval);
        }

        // start plugin installation
        if ( status.status == 'paid' ) {
            $('div.modal-window > div:first > span:last').click();
            plugin_obj = $('.changelog_install a.buy_icon[name='+key+']');
            startInstallation();
        }
    });
}

var startInstallation = function(){
    actions_locked = true;
    
    hideNotices();
    
    var key = $(plugin_obj).attr('name');
    var area = $(plugin_obj).closest('div.changelog_item');
    var name = $(area).find('a:first').html();
    var id = $(area).attr('id');
    var height = $(area).height()-16-2;
    height = height < 55 ? 'auto' : height;
    var width = $(area).width();
    
    /* set fixed height for main container */
    $(area).parent().height($(area).height());
    
    /* prepare HTML DOM */
    var html = ' \
    <div style="margin: 0 0 16px 0;height: '+ height +'px;width: '+ width +'px;position: absolute;padding: 0;" class="hide grey_area" id="'+ id +'_tmp"> \
        <div style="padding: 8px 10px 10px;"> \
            <div class="dark_13"><b>'+ name +'</b> '+ plugin_is_installing_phrase +'</div> \
            <div class="progress static" style="padding: 5px 0 0;"></div> \
        </div> \
    </div>';
    
    /* show progress bar */
    $(area).after(html);
    $(area).css({width: $(area).width(), position: 'absolute'}).fadeOut();
    $(area).next().fadeIn('normal', function(){
        $(area).css('position', 'relative');
        $(this).css({position: 'relative', width: 'auto'});
        $(this).find('.progress').html(plugin_connect_phrase);
        xajax_remoteInstall(key);
    });
};

var startUpdating = function(){
    if (actions_locked) {
        return;
    }

    actions_locked = true;
    
    hideNotices();
    
    var key = $(plugin_obj).attr('name');
    var area = $(plugin_obj).closest('div.changelog_item');
    var name = $(area).find('a:first').html();
    var id = $(area).attr('id');
    var height = $(area).height()-16-2;
    height = height < 55 ? 'auto' : height;
    var width = $(area).width();
    
    /* set fixed height for main container */
    $(area).parent().height($(area).height());
    
    /* prepare HTML DOM */
    var html = ' \
    <div style="margin: 0 0 16px 0;height: '+ height +'px;width: '+ width +'px;position: absolute;padding: 0;" class="hide grey_area" id="'+ id +'_tmp"> \
        <div style="padding: 8px 10px 10px;"> \
            <div class="dark_13"><b>'+ name +'</b> '+ plugin_is_updating_phrase +'</div> \
            <div class="progress static" style="padding: 5px 0 0;"></div> \
        </div> \
    </div>';
    
    /* show progress bar */
    $(area).after(html);
    $(area).css({width: $(area).width(), position: 'absolute'}).fadeOut();
    $(area).next().fadeIn('normal', function(){
        $(area).css('position', 'relative');
        $(this).css({position: 'relative', width: 'auto'});
        $(this).find('.progress').html(plugin_update_backingup);
        xajax_remoteUpdate(key);
    });
};

var continueInstallation = function(key){
    var area = $('div.changelog_item a[name='+ key +']').closest('div.changelog_item');
    $(area).next().find('div.progress').html(plugin_installing_phrase);
    
    xajax_install(key, 'true');
};

var continueUpdating = function(key){
    var area = $('div.changelog_item a[name='+ key +']').closest('div.changelog_item');
    $(area).next().find('div.progress').html(plugin_updating_phrase);
    
    xajax_update(key, 'true');
};

var hideProgressBar = function(){
    var area = $(plugin_obj).closest('div.changelog_item');
    
    /* hide progress bar */
    $(area).next().fadeOut('normal', function(){
        $(area).css({width: 'auto'}).fadeIn('normal', function(){
            $(this).css('position', 'relative');
        });
    });
    
    actions_locked = false;
};

var ab_plugins_log_load = false;

$(document).ready(function(){
    var load = false;
    var key = 'plugins_log';
    var func = 'xajax_getPluginsLog()';
    
    if ( $('.block div[lang='+key+']').is(':visible') )
    {
        eval(func);
    }
    else
    {
        $('.block div[lang='+key+']').prev().find('div.collapse').click(function(){
            if ( !load )
            {
                eval(func);
                load = true;
            }
        });
    }

    $('input#apsblock\\\:'+key).click(function(){
        if ( !load && $(this).attr('checked') && $('.block div[lang='+key+']').is(':visible') )
        {
            eval(func);
            load = true;
        }
    });
});

{/literal}
//]]>
</script>

<!-- plugins changelog block end -->
