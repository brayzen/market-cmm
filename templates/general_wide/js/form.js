
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: FORM.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2019 | All copyrights reserved.
 *  
 *  http://www.flynax.com/
 ******************************************************************************/

var flynaxForm = function(){
    this.auth = function(){
        $reg_inputs   = $('div.auth div.cell:first input:not([type=hidden])');
        $login_inputs = $('div.auth div.cell:last input:not([type=hidden])');

        $reg_inputs.on('keydown', function(){
            $login_inputs.val('');
        });
        $login_inputs.on('keydown', function(){
            $reg_inputs.val('');
        });
    }

    this.fields = function(){
        if (Object.keys(window.textarea_fields).length) {
            for (var name in window.textarea_fields) {
                if (window.textarea_fields[name].type == 'html') {
                    if (typeof CKEDITOR.instances[name] == 'undefined') {
                        flynax.htmlEditor(
                            [name],
                            textarea_fields[name].length
                            ?   [[
                                    'wordcount',
                                    {
                                        showParagraphs    : false,
                                        showWordCount     : false,
                                        showCharCount     : true,
                                        maxCharCount      : textarea_fields[name].length,
                                        countSpacesAsChars: true,
                                    }
                                ]]
                            : []
                        );
                    }
                } else {
                    if (!$('#' + name).next().hasClass('textarea_counter_default')) {
                        $('#' + name).textareaCount({
                            maxCharacterSize: window.textarea_fields[name].length,
                            warningNumber: 20
                        })
                    }
                }
            }
        }

        $('.numeric').numeric({decimal:rlConfig['price_separator']});
        flynax.phoneField();
    }

    this.typeQTip = function(){
        $('[name="register[type]"]').change(function() {
            $('img.qtip').hide();
            $('img.sc_' + $(this).val()).show();
        });
    }

    /**
    * Assign account location data to the same fields in listing form
    **/
    this.accountFieldSimulation = function(){
        var $switcher = $('input[name="f[account_address_on_map]"]');
        var $on_map = $('.on_map');

        if (!$switcher.length) {
            return;
        }

        var handler = function(edit_mode){
            var option = $switcher.filter(':checked').val();
            if (option == '1') {
                $('.on_map_data').each(function(){
                    var key = $(this).data('field-key');
                    $element = $('*[name="f[' + key + ']"]');

                    if (key.indexOf('_level') > 0) {
                        $element.find('option:gt(0)').remove();
                        var option = $('<option>')
                            .attr('selected', true)
                            .text($(this).val())
                            .val($(this).val());

                        $element.append(option);
                    } else {
                        $element.val($(this).val());
                    }

                    $on_map.find('input, textarea, select').attr('disabled', true).addClass('disabled');
                });
            } else if (option == '0' && !edit_mode) {
                $on_map.find('input, textarea').val('');
                $on_map.find('select').val(0);

                $on_map.find('input, textarea, select').attr('disabled', false).removeClass('disabled');
            }
        }

        $switcher.change(function(){
            handler();
        });

        handler(true);
    };

    /**
     * Applys custom action to file inputs with custom style
     */
    this.fileFieldAction = function(){
        // File input click handler
        $('.file-input input[type=file]')
            .unbind('change')
            .bind('change', function(){
                var path = $(this).val().split('\\');
                $(this).parent().find('input[type=text]')
                    .removeClass('error')
                    .val(path[path.length - 1]);
            });

        // Uploaded file remove handler
        flUtil.loadScript(rlConfig['tpl_base'] + 'components/popup/_popup.js', function(){
            var $interface = $('<span>')
                .text(lang['confirm_notice']);

            $('.file-data .remove-file')
                .unbind('click')
                .bind('click', function(){
                    var $container = $(this).closest('.file-data');
                    var field      = $container.data('field');
                    var value      = $container.data('value');
                    var type       = $container.data('type');
                    var parent     = $container.data('parent');

                    $(this).popup({
                        click: false,
                        content: $interface,
                        caption: lang['delete_file'],
                        navigation: {
                            okButton: {
                                text: lang['delete'],
                                onClick: function(popup){
                                    var $button = $(this);

                                    $button
                                        .addClass('disabled')
                                        .attr('disabled', true)
                                        .val(lang['loading']);

                                    if (value && type) {
                                        var data = {mode: 'deleteFile', field: field, value: value, type: type};
                                    } else {
                                        var data = {mode: 'deleteTmpFile', field: field, parent: parent};
                                    }

                                    flUtil.ajax(data, function(response, status){
                                        if (status == 'success' && response.status == 'OK') {
                                            $container.remove();
                                        } else {
                                            $button
                                                .removeClass()
                                                .attr('disabled', false)
                                                .val(lang['save']);

                                            printMessage('error', lang['system_error']);
                                        }

                                        popup.close();
                                    }, true);
                                }
                            },
                            cancelButton: {
                                text: lang['cancel'],
                                class: 'cancel'
                            }
                        }
                    });
                });
        });
    };
}

var flForm = new flynaxForm();
