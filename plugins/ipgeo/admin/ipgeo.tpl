<!-- ipgeo tpl -->

<style>
{literal}

.ipgeo-p {
    font-size: 14px;
    line-height: 20px;
}
.ipgeo .red {
    font-size: 14px;
}
.ipgeo .loading-interface {
    border-top: 1px #cccccc solid;
    margin-top: 10px;
    padding-top: 18px;
    display: none;
}
.ipgeo .progress-bar {
    max-width: 600px;
    height: 5px;
    background: #e2e2e2;
    margin: 10px 0;
}
.ipgeo .progress-bar > div {
    height: 100%;
    width: 0;
    background: #748645;
    transition: width 0.2s ease;
}
.ipgeo .progress-error-message {
    margin-top: 15px;
    display: none;
}
.ipgeo .progress-error-message > li:not(:first-child) {
    padding-top: 2px;
}

{/literal}
</style>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
{assign var='compared_version' value=$config.ipgeo_database_version|version_compare:'1.3.0'}

<div class="ipgeo">
    <p class="ipgeo-p">
        {if $compared_version < 0}
            {$lang.ipgeo_remote_install_text}
        {else}
            {$lang.ipgeo_remote_update_text}
        {/if}
    </p>

    <p class="ipgeo-p" style="padding: 10px 0 10px;">
        <span class="red"><b>{$lang.notice}:</b></span>  {$lang.ipgeo_remote_update_notice}
    </p>

    {assign var='replace_var' value=`$smarty.ldelim`percent`$smarty.rdelim`}

    <div><input id="install_database" {if $compared_version >= 0}accesskey="update"{/if} type="button" value="{if $compared_version < 0}{$lang.install}{else}{$lang.update}{/if}" /></div>
    <div class="loading-interface">
        <div class="progress">{$lang.ipgeo_preparing}</div>
        <div class="progress-bar"><div></div></div>
        <div class="progress-info">{$lang.ipgeo_remote_update_status|replace:$replace_var:'<span>0</span>'}</div>
        <ul class="progress-error-message red"></ul>
    </div>
</div>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

<script>
lang['update'] = '{$lang.update}';
lang['ipgeo_too_many_failed_requests'] = '{$lang.ipgeo_too_many_failed_requests}';

{literal}

$(document).ready(function(){
    var loading_interface = $('.ipgeo .loading-interface');
    var progress_bar = loading_interface.find('.progress-bar > div');
    var error_area = loading_interface.find('.progress-error-message');
    var progress_dump = loading_interface.find('.progress');
    var progress_info = loading_interface.find('.progress-info > span');
    var current_file = 1;
    var total_files = 0;
    var in_progress = false;
    var timeout = 0; // current timeout, no timeout
    var timeout_step = 1000; // 1 second
    var fail_timeout = 60000; // 60 seconds
    var fail_request = 0; // count of the failed requests
    var fail_request_count_to_stop = 15;

    $.ajaxSetup({cache: false});

    var ipGeoUploadFile = function(){
        $.post(rlConfig['tpl_base']+'request.ajax.php', {item: 'ipgeoUploadFile', file: current_file}, function(response){
            if (response.status == 'OK') {
                progress_dump.text(lang['ipgeo_file_upload_info'].replace('{files}', total_files).replace('{file}', current_file));
                ipGeoImport();
            } else {
                ipGeoError(response.data);
            }
        }, 'json');
    }

    var ipGeoImport = function(){
        $.post(rlConfig['tpl_base']+'request.ajax.php', {item: 'ipgeoImport'}, function(response){
            if (response['error']) {
                if (response['retry'] && fail_request < fail_request_count_to_stop) {
                    fail_request++;

                    setTimeout(function(){
                        ipGeoImport();
                    }, fail_timeout);
                } else {
                    ipGeoError(response['error']);
                }
            } else if (response['action'] == 'next_stack') {
                setTimeout(function(){
                    ipGeoImport();
                }, timeout);

                response['progress'] = response['progress'] > 100 ? 100 : response['progress'];
                progress_bar.width(response['progress']+'%');
                progress_info.text(response['progress']);
            } else if (response['action'] == 'next_file') {
                current_file++;
                progress_dump.text(lang['ipgeo_file_download_info'].replace('{files}', total_files).replace('{file}', current_file));

                ipGeoUploadFile();
            } else if (response['action'] == 'end') {
                in_progress = false;
                printMessage('notice', lang['ipgeo_import_completed']);
                progress_dump.text(lang['ipgeo_import_completed']);
            }
        }, 'json').fail(function() {
            // Offline mode, retry in 20 seconds
            if (!navigator.onLine) {
                setTimeout(function(){
                    ipGeoImport();
                }, fail_timeout);
            }
            // Online mode
            else {
                if (fail_request >= fail_request_count_to_stop) {
                    ipGeoError(lang['ipgeo_too_many_failed_requests']);
                } else {
                    timeout += timeout_step;
                    fail_request++;

                    setTimeout(function(){
                        ipGeoImport();
                    }, fail_timeout + timeout);
                }
            }
        });
    }

    var ipGeoError = function(data){
        error_area
            .append($('<li>').text(data))
            .show();
        progress_bar.css('width', '0');

        in_progress = false;
    }

    $('#install_database').click(function(){
        // update mode
        if ($(this).attr('accesskey') == 'update') {
            $(this).val(lang['loading']);
            var self = this;

            $.post(rlConfig['tpl_base']+'request.ajax.php', {item: 'ipgeoCheckUpdate'}, function(response){
                if (response.data == 'NO') {
                    $(self).val(lang['update']);
                    printMessage('notice', lang['ipgeo_db_uptodate']);
                } else {
                    $(self).removeAttr('accesskey');
                    $(self).trigger('click');
                }
            }, 'json');
        }
        // import mode
        else {
            $(this).parent().fadeOut(function(){
                loading_interface.fadeIn(function(){
                    $.post(rlConfig['tpl_base']+'request.ajax.php', {item: 'ipgeoPrepare'}, function(response){
                        if (response.status == 'OK') {
                            in_progress = true;

                            total_files = response.data.calc;
                            progress_dump.text(lang['ipgeo_file_download_info'].replace('{files}', total_files).replace('{file}', current_file));
                            ipGeoUploadFile();
                        } else {
                            ipGeoError(response.data);
                        }
                    }, 'json');
                });
            });
        }
    });

    $(window).bind('beforeunload', function() {
        if (in_progress) {
            return 'Uploading the data is in process; closing the page will stop the process.';
        }
    });
});

{/literal}
</script>

<!-- ipgeo tpl end -->
