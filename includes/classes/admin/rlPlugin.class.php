<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLPLUGIN.CLASS.PHP
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

use Flynax\Interfaces\PluginInterface;
use Flynax\Classes\PluginManager;
use Symfony\Component\Filesystem\Exception\IOException;
use Flynax\Utils\Archive;

class rlPlugin extends reefless
{
    public $inTag;
    public $level = 0;
    public $attributes;

    public $key;
    public $title;
    public $description;
    public $version;
    public $uninstall;
    public $hooks;
    public $phrases;
    public $configGroup;
    public $configs;
    public $blocks;
    public $aBlocks;
    public $pages;
    public $emails;
    public $files;
    public $notice;
    public $controller;

    public $updates;
    public $notices;
    public $controllerUpdate;

    public $noVersionTag = false;

    /**
     * install plugin
     *
     * @package xAjax
     *
     * @param string $key - plugin key
     *
     **/
    public function ajaxInstall($key = false, $remote_mode = false)
    {
        global $_response, $rlSmarty, $lang, $controller;

        $this->noVersionTag = true;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$key) {
            return $_response;
        }

        $path_to_install = RL_PLUGINS . $key . RL_DS . 'install.xml';

        if (is_readable($path_to_install)) {
            require_once RL_LIBS . 'saxyParser' . RL_DS . 'xml_saxy_parser.php';

            $rlParser = new SAXY_Parser();
            $rlParser->xml_set_element_handler(array(&$this, "startElement"), array(&$this, "endElement"));
            $rlParser->xml_set_character_data_handler(array(&$this, "charData"));
            $rlParser->xml_set_comment_handler(array(&$this, "commentElement"));

            // parse xml file
            $rlParser->parse(file_get_contents($path_to_install));

            // check compatibility with current version of the software
            if (!$this->checkCompatibilityByVersion($this->compatible)) {
                return $_response->script("printMessage('error', '{$lang['plugin_not_compatible_notice']}');");
            }

            $allLangs = $GLOBALS['languages'];

            $plugin = array(
                'Key'         => $this->key,
                'Class'       => $this->uninstall['class'] ?: '',
                'Name'        => $this->title,
                'Description' => $this->description,
                'Version'     => $this->version,
                'Status'      => 'approval',
                'Install'     => 1,
                'Controller'  => $this->controller,
                'Uninstall'   => $this->uninstall['code'],
                'Files'       => serialize($this->files),
            );

            $this->loadClass("Actions");

            // install plugin
            if ($GLOBALS['rlActions']->insertOne($plugin, 'plugins')) {
                // install language's phrases
                $phrases = $this->phrases;
                if (!empty($phrases)) {
                    unset($lang_keys);
                    foreach ($phrases as $key => $value) {
                        foreach ($allLangs as $lkey => $lval) {
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$lkey]['Code'],
                                'Module' => $phrases[$key]['Module'],
                                'Key'    => $phrases[$key]['Key'],
                                'Value'  => $phrases[$key]['Value'],
                                'Plugin' => $this->key,
                                'Status' => 'approval',
                            );
                        }
                    }
                }

                // install hooks
                $hooks = $this->hooks;
                if (!empty($hooks)) {
                    $GLOBALS['rlActions']->insert($hooks, 'hooks');
                }

                // install configs
                $cGroup = $configGroup = $this->configGroup;
                if (!empty($configGroup)) {
                    $cg_max_poss = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}config_groups` LIMIT 1");
                    unset($cGroup['Name']);
                    $cGroup['Position'] = $cg_max_poss['max'] + 1;

                    $GLOBALS['rlActions']->insertOne($cGroup, 'config_groups');
                    $group_id = $this->insertID();

                    // add config group phrases
                    foreach ($allLangs as $lkey => $lval) {
                        $lang_keys[] = array(
                            'Code'   => $allLangs[$lkey]['Code'],
                            'Module' => 'admin',
                            'Key'    => 'config_groups+name+' . $configGroup['Key'],
                            'Value'  => $configGroup['Name'],
                            'Plugin' => $this->key,
                            'Status' => 'approval',
                        );
                    }
                }
                $group_id = empty($group_id) ? 0 : $group_id;

                $configs = $this->configs;
                if (!empty($configs)) {
                    foreach ($configs as $key => $value) {
                        foreach ($allLangs as $lkey => $lval) {
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$lkey]['Code'],
                                'Module' => 'admin',
                                'Key'    => 'config+name+' . $configs[$key]['Key'],
                                'Value'  => $configs[$key]['Name'],
                                'Plugin' => $this->key,
                                'Status' => 'approval',
                            );

                            if (!empty($configs[$key]['Description'])) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$lkey]['Code'],
                                    'Module' => 'admin',
                                    'Key'    => 'config+des+' . $configs[$key]['Key'],
                                    'Value'  => $configs[$key]['Description'],
                                    'Plugin' => $this->key,
                                    'Status' => 'approval',
                                );
                            }
                        }
                        $position = $key;

                        if ($configs[$key]['Group']) {
                            $max_pos = $this->getRow("SELECT MAX(`Position`) AS `Max` FROM `{db_prefix}config` WHERE `Group_ID` = '{$configs[$key]['Group']}' LIMIT 1");
                            $position = $max_pos['Max'] + $key;
                        }

                        $configs[$key]['Position'] = $position;
                        $configs[$key]['Group_ID'] = !$group_id ? $configs[$key]['Group'] : $group_id;
                        unset($configs[$key]['Name']);
                        unset($configs[$key]['Description']);
                        unset($configs[$key]['Group']);
                        unset($configs[$key]['Version']);
                    }
                    $GLOBALS['rlActions']->insert($configs, 'config');
                }

                // install blocks
                $blocks = $this->blocks;
                if (!empty($blocks)) {
                    foreach ($blocks as $key => $value) {
                        $block_max_poss = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks` LIMIT 1");
                        $blocks[$key]['Position'] = $block_max_poss['max'] + 1;

                        if (in_array(strtolower($blocks[$key]['Type']), array('html', 'php', 'smarty'))) {
                            // add name phrases
                            foreach ($allLangs as $lkey => $lval) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$lkey]['Code'],
                                    'Module' => 'common',
                                    'Key'    => 'blocks+name+' . $blocks[$key]['Key'],
                                    'Value'  => $blocks[$key]['Name'],
                                    'Plugin' => $this->key,
                                    'Status' => 'avtive',
                                );
                            }

                            if (strtolower($blocks[$key]['Type']) == 'html') {
                                foreach ($allLangs as $lkey => $lval) {
                                    $lang_keys[] = array(
                                        'Code'   => $allLangs[$lkey]['Code'],
                                        'Module' => 'common',
                                        'Key'    => 'blocks+content+' . $blocks[$key]['Key'],
                                        'Value'  => $blocks[$key]['Content'],
                                        'Plugin' => $this->key,
                                        'Status' => 'avtive',
                                    );
                                }
                                unset($blocks[$key]['Content']);
                            }

                            unset($blocks[$key]['Name']);
                            unset($blocks[$key]['Version']);
                        } else {
                            unset($blocks[$key]);
                        }
                    }
                    $GLOBALS['rlActions']->insert($blocks, 'blocks');
                }

                // install admin panel blocks
                $aBlocks = $this->aBlocks;
                if (!empty($aBlocks)) {
                    foreach ($aBlocks as $key => $value) {
                        $aBlock_max_poss = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}admin_blocks` WHERE `Column` = 'column{$value['Column']}' LIMIT 1");
                        $aBlocks[$key]['Position'] = $aBlock_max_poss['max'] + 1;

                        // add name phrases
                        foreach ($allLangs as $lkey => $lval) {
                            $lang_keys[] = array(
                                'Code'   => $lval['Code'],
                                'Module' => 'admin',
                                'Key'    => 'admin_blocks+name+' . $value['Key'],
                                'Value'  => $value['Name'],
                                'Plugin' => $this->key,
                                'Status' => 'active',
                            );
                        }

                        $aBlocks[$key]['name'] = $aBlocks[$key]['Name'];
                        $rlSmarty->assign('block', $aBlocks[$key]);

                        unset($aBlocks[$key]['Name']);
                        unset($aBlocks[$key]['name']);
                        unset($aBlocks[$key]['Version']);
                        $aBlocks[$key]['Column'] = 'column' . $aBlocks[$key]['Column'];

                        if ($remote_mode) {
                            // append new block
                            $tpl = 'blocks' . RL_DS . 'homeDragDrop_block.tpl';
                            $_response->append('tmp_dom_blocks_store', 'innerHTML', $rlSmarty->fetch($tpl, null, null, false));
                            $_response->script("
                                $('#tmp_dom_blocks_store div.block').hide();
                                $('td.column{$value['Column']} div.sortable').append($('#tmp_dom_blocks_store div.block'));
                                $('td.column{$value['Column']} div.sortable div.block:last').fadeIn('slow');
                            ");
                            if ($aBlocks[$key]['Ajax']) {
                                $_response->call('aBlockInit');
                            }
                        }
                    }
                    $GLOBALS['rlActions']->insert($aBlocks, 'admin_blocks');
                }

                // install pages
                $pages = $this->pages;
                if (!empty($pages)) {
                    foreach ($pages as $key => $value) {
                        $page_max_poss = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages` LIMIT 1");
                        $pages[$key]['Position'] = $page_max_poss['max'] + 1;

                        if (in_array($pages[$key]['Page_type'], array('system', 'static', 'external'))) {
                            // add name phrases
                            foreach ($allLangs as $lkey => $lval) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$lkey]['Code'],
                                    'Module' => 'common',
                                    'Key'    => 'pages+name+' . $pages[$key]['Key'],
                                    'Value'  => $pages[$key]['Name'],
                                    'Plugin' => $this->key,
                                    'Status' => 'active',
                                );
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$lkey]['Code'],
                                    'Module' => 'common',
                                    'Key'    => 'pages+title+' . $pages[$key]['Key'],
                                    'Value'  => $pages[$key]['Name'],
                                    'Plugin' => $this->key,
                                    'Status' => 'active',
                                );
                            }

                            switch ($pages[$key]['Page_type']) {
                                case 'static':
                                    foreach ($allLangs as $lkey => $lval) {
                                        $lang_keys[] = array(
                                            'Code'   => $allLangs[$lkey]['Code'],
                                            'Module' => 'common',
                                            'Key'    => 'pages+content+' . $pages[$key]['Key'],
                                            'Value'  => $pages[$key]['Content'],
                                            'Plugin' => $this->key,
                                            'Status' => 'active',
                                        );
                                    }
                                    break;
                                case 'system':
                                    $pages[$key]['Controller'] = $pages[$key]['Controller'];
                                    break;
                                case 'external':
                                    $pages[$key]['Controller'] = $pages[$key]['Content'];
                                    break;
                            }
                            unset($pages[$key]['Name']);
                            unset($pages[$key]['Content']);
                            unset($pages[$key]['Version']);
                        } else {
                            unset($pages[$key]);
                        }
                    }
                    $GLOBALS['rlActions']->insert($pages, 'pages');
                }

                // install email templates
                $emails = $this->emails;
                if (!empty($emails)) {
                    foreach ($emails as $key => $value) {
                        $email_max_poss = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}email_templates` LIMIT 1");
                        $emails[$key]['Position'] = $email_max_poss['max'] + 1;

                        // add name phrases
                        foreach ($allLangs as $lkey => $lval) {
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$lkey]['Code'],
                                'Module' => 'email_tpl',
                                'Key'    => 'email_templates+subject+' . $emails[$key]['Key'],
                                'Value'  => $emails[$key]['Subject'],
                                'Plugin' => $this->key,
                                'Status' => 'active',
                            );
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$lkey]['Code'],
                                'Module' => 'email_tpl',
                                'Key'    => 'email_templates+body+' . $emails[$key]['Key'],
                                'Value'  => $emails[$key]['Body'],
                                'Plugin' => $this->key,
                                'Status' => 'active',
                            );
                        }
                        unset($emails[$key]['Subject']);
                        unset($emails[$key]['Body']);
                        unset($emails[$key]['Version']);
                    }
                    $GLOBALS['rlActions']->insert($emails, 'email_templates');
                }

                // add phrases
                if (!empty($lang_keys)) {
                    $GLOBALS['rlActions']->insert($lang_keys, 'lang_keys');
                }

                /**
                 * @since 4.7.0 - Using PluginManager class instead of the internal method
                 * @since 4.6.0
                 */
                try {
                    $instance = PluginManager::getPluginInstance($this->key, $this->install['class']);

                    if ($instance && $instance instanceof PluginInterface) {
                        $instance->install();
                    } elseif ($this->install['code'] !== '') {
                        @eval($this->install['code']);
                    }
                } catch (Exception $e) {
                    $GLOBALS['rlDebug']->logger($e->getMessage());
                }

                // check plugin files exist
                $files = $this->files;
                $files_exist = true;

                foreach ($files as $file) {
                    $file = str_replace(array('\\', '/'), array(RL_DS, RL_DS), $file);

                    if (!is_readable(RL_PLUGINS . $this->key . RL_DS . $file)) {
                        $files_exist = false;

                        $missed_files .= '/plugins/' . $this->key . '/<b>' . $file . '</b><br />';

                        $message = str_replace('{files}', "<br />" . $missed_files, $lang['plugin_files_missed']);
                        $_response->script("printMessage('alert', '{$message}');");
                    }
                }

                // activate plugin
                if ($files_exist === true) {
                    $tables = array('lang_keys', 'hooks', 'blocks', 'admin_blocks', 'pages', 'email_templates');

                    foreach ($tables as $table) {
                        unset($update);
                        $update = array(
                            'fields' => array(
                                'Status' => 'active',
                            ),
                            'where'  => array(
                                'Plugin' => $this->key,
                            ),
                        );
                        $GLOBALS['rlActions']->updateOne($update, $table);
                    }

                    unset($update);
                    $update = array(
                        'fields' => array(
                            'Status' => 'active',
                        ),
                        'where'  => array(
                            'Key' => $this->key,
                        ),
                    );
                    $GLOBALS['rlActions']->updateOne($update, 'plugins');

                    if ($this->notice || is_array($this->notices)) {
                        $post_notice = is_array($this->notices) ? $this->notices[0]['Content'] : $this->notice;
                        $post_install_notice = "<br /><b>" . $lang['notice'] . ":</b> " . $post_notice;
                    }
                    $notice = $lang['notice_plugin_installed'] . $post_install_notice;
                    $_response->script("printMessage('notice', '{$notice}');");

                    /* add menu item */
                    if ($this->controller) {
                        $menu_item = '<div class="mitem" id="mPlugin_' . $this->key . '"><a href="' . RL_URL_HOME . ADMIN . '/index.php?controller=' . $this->controller . '">' . $this->title . '<\/a><\/div>';
                        $_response->script("
                            $('#plugins_section').append('{$menu_item}');
                            apMenu['plugins']['" . $this->key . "'] = new Array();
                            apMenu['plugins']['" . $this->key . "']['Name'] = '" . $this->title . "';
                            apMenu['plugins']['" . $this->key . "']['Controller'] = '" . $this->controller . "';
                            apMenu['plugins']['" . $this->key . "']['Vars'] = '';
                        ");
                    }
                }
            } else {
                trigger_error("Can not install plugin (" . $this->title . "), insert command failed", E_USER_WARNING);
                $GLOBALS['rlDebug']->logger("Can not install plugin (" . $this->title . "), insert command failed");
            }

            if ($remote_mode) {
                $callBack = $controller == 'home'
                ? 'xajax_getPluginsLog()'
                : "$(area).closest('li').fadeOut(function(){
                        $(this).remove();

                        if (!$('ul.browse_plugins li').length) {
                            $('#browse_content').html('{$lang['no_new_plugins']}');
                        }
                    });

                    pluginsGrid.reload();";
                $_response->script("
                    var area = $('div.changelog_item a[name={$this->key}]').closest('div.changelog_item');
                    $(area).next().find('div.progress').html('{$lang['remote_progress_installation_completed']}');
                    setTimeout(function(){ {$callBack} }, 1000);

                    actions_locked = false;
                ");
            } else {
                // reload grid
                $_response->script("pluginsGrid.reload();");
            }
        } else {
            $_response->script("printMessage('error', '{$lang['install_not_found']}');");
        }
        return $_response;
    }

    /**
     * update plugin
     *
     * @package xAjax
     *
     * @param string $plugin_key - plugin key
     * @param boolian $remote_mode - remote mode
     *
     **/
    public function ajaxUpdate($plugin_key = false, $remote_mode = false)
    {
        global $_response, $lang, $rlSmarty;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$plugin_key) {
            return $_response;
        }

        $GLOBALS['rlValid']->sql($plugin_key);
        $current_version = $this->getOne('Version', "`Key` = '{$plugin_key}'", 'plugins');

        $path_to_update = RL_UPLOAD . $plugin_key . RL_DS . 'install.xml';

        if (is_readable($path_to_update)) {
            require_once RL_LIBS . 'saxyParser' . RL_DS . 'xml_saxy_parser.php';

            $rlParser = new SAXY_Parser();
            $rlParser->xml_set_element_handler(array(&$this, "startElement"), array(&$this, "endElement"));
            $rlParser->xml_set_character_data_handler(array(&$this, "charData"));
            $rlParser->xml_set_comment_handler(array(&$this, "commentElement"));

            // parse xml file
            $rlParser->parse(file_get_contents($path_to_update));

            // check compatibility with current version of the software
            if (!$this->checkCompatibilityByVersion($this->compatible)) {
                return $_response->script("printMessage('error', '{$lang['plugin_not_compatible_notice']}');");
            }

            $allLangs = $GLOBALS['languages'];

            $plugin = array(
                'fields' => array(
                    'Name'        => $this->title,
                    'Class'       => $this->uninstall['class'] ?: '',
                    'Description' => $this->description,
                    'Version'     => $this->version,
                    'Controller'  => $this->controller,
                    'Uninstall'   => $this->uninstall['code'],
                    'Files'       => serialize($this->files),
                ),
                'where'  => array(
                    'Key' => $this->key,
                ),
            );

            $this->loadClass("Actions");

            // update plugin
            foreach ($this->updates as $update_index => $update_item) {
                $success = true;

                if (version_compare($update_item['Version'], $current_version) > 0) {
                    $lang_keys_insert = array();
                    $lang_keys_update = array();

                    $hooks_insert = array();
                    $hooks_update = array();

                    $configs_insert = array();
                    $configs_update = array();

                    $update_item['Files'] = rtrim('install.xml,' . $update_item['Files'], ',');

                    /* Copy plugin files */
                    if (!empty($update_item['Files'])) {
                        $update_files = explode(',', $update_item['Files']);

                        foreach ($update_files as $update_file) {
                            $file_to_copy = trim($update_file);
                            $error_message = '';

                            if (!file_exists($source = RL_UPLOAD . $plugin_key . RL_DS . $file_to_copy)) {
                                $error_message = "The '/tmp/upload/{$plugin_key}/{$file_to_copy}' does not exist.";
                            } elseif (!is_writable(RL_PLUGINS . $plugin_key)) {
                                $error_message = "The '/plugins/{$plugin_key}/' directory is not writable.";
                            }

                            if ($error_message) {
                                $GLOBALS['rlDebug']->logger("Plugin updating: {$error_message}");
                                $success = false;
                                break;
                            }

                            $destination = RL_PLUGINS . $plugin_key . RL_DS . $file_to_copy;
                            $catchExceptionFunc = function (IOException $e) use (&$success, $plugin_key) {
                                $GLOBALS['rlDebug']->logger("
                                    Plugin updating: Thrown exception '{$e->getMessage()}' in {$plugin_key} plugin.
                                ");
                                $success = false;
                            };
                            $options = ['override' => true];

                            $filesystem = new \Flynax\Component\Filesystem();
                            $filesystem->copy($source, $destination, $catchExceptionFunc, $options);
                        }
                    }
                    /* Copy plugin files end */

                    if ($success) {
                        // install language's phrases
                        $phrases = $this->phrases;
                        if (!empty($phrases)) {
                            foreach ($phrases as $key => $value) {
                                if (version_compare($value['Version'], $update_item['Version']) == 0) {
                                    foreach ($allLangs as $lkey => $lval) {
                                        if ($this->getOne('ID', "`Key` = '{$phrases[$key]['Key']}' AND `Code` = '{$lval['Code']}'", 'lang_keys')) {
                                            /* update */
                                            $lang_keys_update[] = array(
                                                'fields' => array(
                                                    'Module' => $phrases[$key]['Module'],
                                                    'Value'  => $phrases[$key]['Value'],
                                                ),
                                                'where'  => array(
                                                    'Code' => $lval['Code'],
                                                    'Key'  => $phrases[$key]['Key'],
                                                ),
                                            );
                                        } else {
                                            /* insert */
                                            $lang_keys_insert[] = array(
                                                'Code'   => $lval['Code'],
                                                'Module' => $phrases[$key]['Module'],
                                                'Key'    => $phrases[$key]['Key'],
                                                'Value'  => $phrases[$key]['Value'],
                                                'Plugin' => $this->key,
                                                'Status' => 'active',
                                            );
                                        }
                                    }
                                }
                            }
                        }

                        // update hooks
                        $hooks = $this->hooks;
                        if (!empty($hooks)) {
                            foreach ($hooks as $key => $value) {
                                if (version_compare($value['Version'], $update_item['Version']) == 0) {
                                    if ($this->getOne('ID', "`Name` = '{$value['Name']}' AND `Plugin` = '" . $this->key . "'", 'hooks')) {
                                        /* update */
                                        $hooks_update[] = array(
                                            'fields' => array(
                                                'Class' => $value['Class'],
                                                'Code'  => $value['Code'],
                                            ),
                                            'where'  => array(
                                                'Name'   => $value['Name'],
                                                'Plugin' => $this->key,
                                            ),
                                        );
                                    } else {
                                        /* insert */
                                        $hooks_insert_item = $value;
                                        unset($hooks_insert_item['Version']);
                                        $hooks_insert_item['Status'] = 'active';
                                        $hooks_insert[] = $hooks_insert_item;
                                    }
                                }
                            }

                            if ($hooks_update) {
                                $GLOBALS['rlActions']->update($hooks_update, 'hooks');
                            }

                            if ($hooks_insert) {
                                $GLOBALS['rlActions']->insert($hooks_insert, 'hooks');
                            }
                        }

                        // update configs' group
                        $cGroup = $configGroup = $this->configGroup;
                        if (!empty($configGroup)) {
                            if (version_compare($configGroup['Version'], $update_item['Version']) == 0) {
                                if ($this->getOne('ID', "`Key` = '{$configGroup['Key']}' AND `Plugin` = '" . $this->key . "'", 'config_groups')) {
                                    /* update */
                                    foreach ($allLangs as $lkey => $lval) {
                                        $lang_keys_update[] = array(
                                            'fields' => array(
                                                'Value' => $configGroup['Name'],
                                            ),
                                            'where'  => array(
                                                'Code' => $lval['Code'],
                                                'Key'  => 'config_groups+name+' . $configGroup['Key'],
                                            ),
                                        );
                                    }
                                } else {
                                    /* insert */
                                    $cg_max_poss = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}config_groups` LIMIT 1");
                                    unset($cGroup['Name']);
                                    unset($cGroup['Version']);
                                    $cGroup['Position'] = $cg_max_poss['max'] + 1;

                                    $GLOBALS['rlActions']->insertOne($cGroup, 'config_groups');
                                    $group_id = $this->insertID();

                                    // add config group phrases
                                    foreach ($allLangs as $lkey => $lval) {
                                        $lang_keys_insert[] = array(
                                            'Code'   => $lval['Code'],
                                            'Module' => 'admin',
                                            'Key'    => 'config_groups+name+' . $configGroup['Key'],
                                            'Value'  => $configGroup['Name'],
                                            'Plugin' => $this->key,
                                            'Status' => 'active',
                                        );
                                    }
                                }
                            }
                        }

                        $group_id = empty($group_id) ? 0 : $group_id;

                        // update configs
                        $configs = $this->configs;
                        if (!empty($configs)) {
                            foreach ($configs as $key => $value) {
                                if (version_compare($value['Version'], $update_item['Version']) == 0) {
                                    if ($this->getOne('ID', "`Key` = '{$value['Key']}' AND `Plugin` = '" . $this->key . "'", 'config')) {
                                        /* update */
                                        $configs_update[] = array(
                                            'fields' => array(
                                                'Default'   => $value['Default'],
                                                'Values'    => $value['Values'],
                                                'Type'      => $value['Type'],
                                                'Data_type' => $value['Data_type'],
                                            ),
                                            'where'  => array(
                                                'Key'    => $value['Key'],
                                                'Plugin' => $this->key,
                                            ),
                                        );

                                        foreach ($allLangs as $lkey => $lval) {
                                            if ($this->getOne('ID', "`Key` = 'config+name+{$value['Key']}' AND `Code` = '{$lval['Code']}'", 'lang_keys')) {
                                                /* update */
                                                $lang_keys_update[] = array(
                                                    'fields' => array(
                                                        'Value' => $value['Name'],
                                                    ),
                                                    'where'  => array(
                                                        'Code' => $lval['Code'],
                                                        'Key'  => 'config+name+' . $value['Key'],
                                                    ),
                                                );
                                            } else {
                                                /* insert */
                                                $lang_keys_insert[] = array(
                                                    'Code'   => $lval['Code'],
                                                    'Module' => 'admin',
                                                    'Key'    => 'config+name+' . $value['Key'],
                                                    'Value'  => $value['Name'],
                                                    'Plugin' => $this->key,
                                                    'Status' => 'active',
                                                );
                                            }
                                        }

                                        if (!empty($value['Description'])) {
                                            foreach ($allLangs as $lkey => $lval) {
                                                if (!$this->getOne('ID', "`Key` = 'config+des+{$value['Key']}' AND `Code` = '{$lval['Code']}'", 'lang_keys')) {
                                                    $lang_keys_insert[] = array(
                                                        'Code'   => $lval['Code'],
                                                        'Module' => 'admin',
                                                        'Key'    => 'config+des+' . $value['Key'],
                                                        'Value'  => $value['Description'],
                                                        'Plugin' => $this->key,
                                                        'Status' => 'active',
                                                    );
                                                }
                                            }
                                        }
                                    } else {
                                        /* insert */
                                        foreach ($allLangs as $lkey => $lval) {
                                            $lang_keys_insert[] = array(
                                                'Code'   => $lval['Code'],
                                                'Module' => 'admin',
                                                'Key'    => 'config+name+' . $value['Key'],
                                                'Value'  => $value['Name'],
                                                'Plugin' => $this->key,
                                                'Status' => 'active',
                                            );

                                            if (!empty($value['Description'])) {
                                                $lang_keys_insert[] = array(
                                                    'Code'   => $lval['Code'],
                                                    'Module' => 'admin',
                                                    'Key'    => 'config+des+' . $value['Key'],
                                                    'Value'  => $value['Description'],
                                                    'Plugin' => $this->key,
                                                    'Status' => 'active',
                                                );
                                            }
                                        }
                                        $position = $key;

                                        if ($configs[$key]['Group']) {
                                            $max_pos = $this->getRow("SELECT MAX(`Position`) AS `Max` FROM `{db_prefix}config` WHERE `Group_ID` = '{$value['Group']}' LIMIT 1");
                                            $position = $max_pos['Max'] + $key;
                                        }

                                        if ($configGroup['Key']) {
                                            $group_id = $this->getOne('ID', "`Key` = '{$configGroup['Key']}' AND `Plugin` = '" . $this->key . "'", 'config_groups');
                                        }

                                        $configs_insert[] = array(
                                            'Group_ID'  => !$group_id ? $value['Group'] : $group_id,
                                            'Position'  => $position,
                                            'Key'       => $value['Key'],
                                            'Default'   => $value['Default'],
                                            'Values'    => $value['Values'],
                                            'Type'      => $value['Type'],
                                            'Data_type' => $value['Data_type'],
                                            'Plugin'    => $this->key,
                                        );
                                    }
                                }
                            }

                            if (!empty($configs_update)) {
                                $GLOBALS['rlActions']->update($configs_update, 'config');
                            }

                            if (!empty($configs_insert)) {
                                $GLOBALS['rlActions']->insert($configs_insert, 'config');
                            }
                        }

                        // update blocks
                        $blocks = $this->blocks;
                        if (!empty($blocks)) {
                            foreach ($blocks as $key => $value) {
                                if (version_compare($value['Version'], $update_item['Version']) == 0) {
                                    if (in_array(strtolower($value['Type']), array('html', 'php', 'smarty'))) {
                                        if ($this->getOne('ID', "`Key` = '{$value['Key']}' AND `Plugin` = '" . $this->key . "'", 'blocks')) {
                                            /* update */
                                            $block_update = array(
                                                'fields' => array(
                                                    'Type'     => $value['Type'],
                                                    'Content'  => $value['Content'],
                                                    'Readonly' => $value['Readonly'],
                                                ),
                                                'where'  => array(
                                                    'Key'    => $value['Key'],
                                                    'Plugin' => $this->key,
                                                ),
                                            );

                                            if (strtolower($value['Type']) == 'html') {
                                                unset($block_update['fields']['Content']);
                                            }

                                            $GLOBALS['rlActions']->updateOne($block_update, 'blocks');
                                        } else {
                                            $block_max_poss = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks` LIMIT 1");
                                            $blocks[$key]['Position'] = $block_max_poss['max'] + 1;

                                            // add name phrases
                                            foreach ($allLangs as $lkey => $lval) {
                                                $lang_keys_insert[] = array(
                                                    'Code'   => $lval['Code'],
                                                    'Module' => 'common',
                                                    'Key'    => 'blocks+name+' . $value['Key'],
                                                    'Value'  => $value['Name'],
                                                    'Plugin' => $this->key,
                                                    'Status' => 'active',
                                                );
                                            }

                                            if (strtolower($value['Type']) == 'html') {
                                                foreach ($allLangs as $lkey => $lval) {
                                                    $lang_keys_insert[] = array(
                                                        'Code'   => $lval['Code'],
                                                        'Module' => 'common',
                                                        'Key'    => 'blocks+content+' . $value['Key'],
                                                        'Value'  => $value['Content'],
                                                        'Plugin' => $this->key,
                                                        'Status' => 'active',
                                                    );
                                                }
                                                unset($blocks[$key]['Content']);
                                            }
                                            unset($blocks[$key]['Name']);
                                            unset($blocks[$key]['Version']);
                                            $blocks[$key]['Status'] = 'active';

                                            $GLOBALS['rlActions']->insertOne($blocks[$key], 'blocks');
                                        }
                                    }
                                }
                            }
                        }

                        // update admin panel blocks
                        $aBlocks = $this->aBlocks;
                        if (!empty($aBlocks)) {
                            foreach ($aBlocks as $key => $value) {
                                if (version_compare($value['Version'], $update_item['Version']) == 0) {
                                    if ($this->getOne('ID', "`Key` = '{$value['Key']}' AND `Plugin` = '" . $this->key . "'", 'admin_blocks')) {
                                        /* update */
                                        $aBlock_update = array(
                                            'fields' => array(
                                                'Ajax'    => $value['Ajax'],
                                                'Content' => $value['Content'],
                                                'Fixed'   => $value['Fixed'],
                                            ),
                                            'where'  => array(
                                                'Key'    => $value['Key'],
                                                'Plugin' => $this->key,
                                            ),
                                        );

                                        $GLOBALS['rlActions']->updateOne($aBlock_update, 'admin_blocks');
                                    } else {
                                        $aBlock_max_poss = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}admin_blocks` WHERE `Column` = 'column{$value['Column']}' LIMIT 1");
                                        $aBlocks[$key]['Position'] = $aBlock_max_poss['max'] + 1;

                                        // add name phrases
                                        foreach ($allLangs as $lkey => $lval) {
                                            $lang_keys_insert[] = array(
                                                'Code'   => $lval['Code'],
                                                'Module' => 'admin',
                                                'Key'    => 'admin_blocks+name+' . $value['Key'],
                                                'Value'  => $value['Name'],
                                                'Plugin' => $this->key,
                                                'Status' => 'active',
                                            );
                                        }

                                        $aBlocks[$key]['name'] = $aBlocks[$key]['Name'];
                                        $rlSmarty->assign('block', $aBlocks[$key]);

                                        unset($aBlocks[$key]['Name']);
                                        unset($aBlocks[$key]['name']);
                                        unset($aBlocks[$key]['Version']);
                                        $aBlocks[$key]['Column'] = 'column' . $aBlocks[$key]['Column'];
                                        $aBlocks[$key]['Status'] = 'active';

                                        $GLOBALS['rlActions']->insertOne($aBlocks[$key], 'admin_blocks');

                                        // append new block
                                        $tpl = 'blocks' . RL_DS . 'homeDrugDrop_block.tpl';
                                        $_response->append('tmp_dom_blocks_store', 'innerHTML', $rlSmarty->fetch($tpl, null, null, false));
                                        $_response->script("
                                            $('#tmp_dom_blocks_store div.block').hide();
                                            $('td.column{$value['Column']} div.sortable').append($('#tmp_dom_blocks_store div.block'));
                                            $('td.column{$value['Column']} div.sortable div.block:last').fadeIn('slow');
                                        ");
                                    }
                                }
                            }
                        }

                        // update pages
                        $pages = $this->pages;
                        if (!empty($pages)) {
                            foreach ($pages as $key => $value) {
                                if (in_array($value['Page_type'], array('system', 'static', 'external'))) {
                                    if (version_compare($value['Version'], $update_item['Version']) == 0) {
                                        if ($this->getOne('ID', "`Key` = '{$value['Key']}' AND `Plugin` = '" . $this->key . "'", 'pages')) {
                                            $page_update = array(
                                                'fields' => array(
                                                    'Page_type'  => $value['Page_type'],
                                                    'Get_vars'   => $value['Get_vars'],
                                                    'Controller' => $value['Controller'],
                                                    'Deny'       => $value['Deny'],
                                                    'Tpl'        => $value['Tpl'],
                                                    'Readonly'   => $value['Readonly'],
                                                ),
                                                'where'  => array(
                                                    'Key'    => $key['Key'],
                                                    'Plugin' => $this->key,
                                                ),
                                            );

                                            $GLOBALS['rlActions']->updateOne($page_update, 'pages');
                                        } else {
                                            $page_max_poss = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages` LIMIT 1");
                                            $pages[$key]['Position'] = $page_max_poss['max'] + 1;

                                            // add name phrases
                                            foreach ($allLangs as $lkey => $lval) {
                                                $lang_keys_insert[] = array(
                                                    'Code'   => $lval['Code'],
                                                    'Module' => 'common',
                                                    'Key'    => 'pages+name+' . $value['Key'],
                                                    'Value'  => $value['Name'],
                                                    'Plugin' => $this->key,
                                                    'Status' => 'active',
                                                );

                                                $lang_keys_insert[] = array(
                                                    'Code'   => $lval['Code'],
                                                    'Module' => 'common',
                                                    'Key'    => 'pages+title+' . $value['Key'],
                                                    'Value'  => $value['Name'],
                                                    'Plugin' => $this->key,
                                                    'Status' => 'active',
                                                );
                                            }

                                            switch ($value['Page_type']) {
                                                case 'static':
                                                    foreach ($allLangs as $lkey => $lval) {
                                                        $lang_keys_insert[] = array(
                                                            'Code'   => $lval['Code'],
                                                            'Module' => 'common',
                                                            'Key'    => 'pages+content+' . $value['Key'],
                                                            'Value'  => $value['Content'],
                                                            'Plugin' => $this->key,
                                                            'Status' => 'active',
                                                        );
                                                    }
                                                    break;
                                                case 'system':
                                                    /* reassign to referent :) */
                                                    $pages[$key]['Controller'] = $pages[$key]['Controller'];
                                                    break;
                                                case 'external':
                                                    $pages[$key]['Controller'] = $pages[$key]['Content'];
                                                    break;
                                            }

                                            unset($pages[$key]['Name']);
                                            unset($pages[$key]['Content']);
                                            unset($pages[$key]['Version']);
                                            $pages[$key]['status'] = 'active';

                                            $GLOBALS['rlActions']->insertOne($pages[$key], 'pages');
                                        }
                                    }
                                }
                            }
                        }

                        // update email templates
                        $emails = $this->emails;
                        if (!empty($emails)) {
                            foreach ($emails as $key => $value) {
                                if (version_compare($value['Version'], $update_item['Version']) == 0) {
                                    if (!$this->getOne('ID', "`Key` = '{$value['Key']}' AND `Plugin` = '" . $this->key . "'", 'email_templates')) {
                                        $email_max_poss = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}email_templates` LIMIT 1");
                                        $emails[$key]['Position'] = $email_max_poss['max'] + 1;

                                        // add name phrases
                                        foreach ($allLangs as $lkey => $lval) {
                                            $lang_keys_insert[] = array(
                                                'Code'   => $lval['Code'],
                                                'Module' => 'email_tpl',
                                                'Key'    => 'email_templates+subject+' . $value['Key'],
                                                'Value'  => $value['Subject'],
                                                'Plugin' => $this->key,
                                                'Status' => 'active',
                                            );
                                            $lang_keys_insert[] = array(
                                                'Code'   => $lval['Code'],
                                                'Module' => 'email_tpl',
                                                'Key'    => 'email_templates+body+' . $value['Key'],
                                                'Value'  => $value['Body'],
                                                'Plugin' => $this->key,
                                                'Status' => 'active',
                                            );
                                        }
                                        unset($emails[$key]['Subject']);
                                        unset($emails[$key]['Body']);
                                        unset($emails[$key]['Version']);
                                        $emails[$key]['Status'] = 'active';

                                        $GLOBALS['rlActions']->insertOne($emails[$key], 'email_templates');
                                    }
                                }
                            }
                        }

                        /**
                         * @since 4.7.0 - Using PluginManager class instead of the internal method
                         * @since 4.6.0
                         */
                        try {
                            $instance = PluginManager::getPluginInstance($this->key, $update_item['Class']);

                            if ($instance && $instance instanceof PluginInterface) {
                                $instance->update($update_item['Version']);
                            } elseif ($update_item['Code'] !== '') {
                                @eval($update_item['Code']);
                            }
                        } catch (Exception $e) {
                            $GLOBALS['rlDebug']->logger($e->getMessage());
                        }

                        // add phrases
                        if (!empty($lang_keys_insert)) {
                            $GLOBALS['rlActions']->insert($lang_keys_insert, 'lang_keys');
                        }

                        // update phrases
                        if (!empty($lang_keys_update)) {
                            $GLOBALS['rlActions']->update($lang_keys_update, 'lang_keys');
                        }

                        $plugin_version_update = array(
                            'fields' => array(
                                'Version' => $update_item['Version'],
                            ),
                            'where'  => array(
                                'Key' => $this->key,
                            ),
                        );

                        $GLOBALS['rlActions']->updateOne($plugin_version_update, 'plugins');
                    }
                }
            }

            /* delete unzipped plugin from TMP */
            $this->deleteDirectory(RL_UPLOAD . $this->key . RL_DS);

            if ($success && $GLOBALS['rlActions']->updateOne($plugin, 'plugins')) {
                $update_notice = $lang['plugin_updated'];

                /* print notices */
                if (!empty($this->notices)) {
                    foreach ($this->notices as $key => $value) {
                        if (version_compare($value['Version'], $current_version) > 0) {
                            $plugin_update_notice .= '<li style="list-style:initial"><b>' . $lang['notice'];
                            $plugin_update_notice .= " ({$lang['version']} {$value['Version']}):</b> ";
                            $plugin_update_notice .= $value['Content'] . "</li>";
                        }
                    }
                    $update_notice .= $plugin_update_notice
                    ? "<br /><br /><ul>" . $plugin_update_notice . "</ul>"
                    : "";
                }

                $_response->script("printMessage('notice', '{$update_notice}');");

                /* add menu item */
                if ($this->controller && version_compare($this->controllerUpdate, $current_version) > 0) {
                    $menu_item = '<div class="mitem" id="mPlugin_' . $this->key . '"><a href="' . RL_URL_HOME . ADMIN . '/index.php?controller=' . $this->controller . '">' . $this->title . '<\/a><\/div>';
                    $_response->script("
                        $('#plugins_section').append('{$menu_item}');
                        apMenu['plugins']['" . $this->key . "'] = new Array();
                        apMenu['plugins']['" . $this->key . "']['Name'] = '" . $this->title . "';
                        apMenu['plugins']['" . $this->key . "']['Controller'] = '" . $this->controller . "';
                        apMenu['plugins']['" . $this->key . "']['Vars'] = '';
                    ");
                }

                if ($remote_mode) {
                    $_response->script("
                        var area = $('div.changelog_item a[name=" . $this->key . "]').closest('div.changelog_item');
                        $(area).next().find('div.progress').html('{$lang['remote_progress_update_completed']}');
                        setTimeout(function(){ xajax_getPluginsLog() }, 1000);

                        actions_locked = false;
                    ");

                    return $_response;
                } else {
                    // reload grid
                    $_response->script("
                        pluginsGrid.reload();
                        $('#update_area').fadeOut();
                    ");
                }
            } else {
                $_response->script("printMessage('error', '{$lang['install_fail_files_upload']}');");
                $GLOBALS['rlDebug']->logger("Cannot update plugin (" . $this->title . "), success variable returned FALSE.");
            }
        } else {
            $_response->script("printMessage('error', '{$lang['install_not_found']}');");
            $GLOBALS['rlDebug']->logger("Cannot update plugin (" . $this->title . "), '{$path_to_update}' does not found.");
        }

        $_response->call('hideProgressBar');

        return $_response;
    }

    public function startElement($parser, $name, $attributes)
    {
        $this->level++;
        $this->inTag = $name;
        $this->attributes = $attributes;

        if ($this->inTag == 'plugin' && isset($attributes['name'])) {
            $this->key = $attributes['name'];
        }

        $this->path[] = $name;
    }

    public function endElement($parser, $name)
    {
        $this->level--;
    }

    public function charData($parser, $text)
    {
        switch ($this->inTag) {
            case 'hook':
                $_class = strval($this->attributes['class'] ?: $this->class);

                $this->hooks[] = array(
                    'Name'    => $this->attributes['name'],
                    'Class'   => $_class,
                    'Version' => $this->attributes['version'],
                    'Code'    => empty($_class) ? $text : '',
                    'Plugin'  => $this->key,
                    'Status'  => 'approval',
                );

                if ($this->noVersionTag) {
                    $itemIndex = count($this->hooks) - 1;
                    unset($this->hooks[$itemIndex]['Version']);
                }
                break;

            case 'phrase':
                $this->phrases[] = array(
                    'Key'     => $this->attributes['key'],
                    'Version' => $this->attributes['version'],
                    'Module'  => $this->attributes['module'],
                    'Value'   => $text,
                );
                break;

            case 'configs':
                $this->configGroup = array(
                    'Key'     => $this->attributes['key'],
                    'Version' => $this->attributes['version'],
                    'Name'    => $this->attributes['name'],
                    'Plugin'  => $this->key,
                );

                if ($this->noVersionTag) {
                    unset($this->configGroup['Version']);
                }
                break;

            case 'config':
                $this->configs[] = array(
                    'Key'         => $this->attributes['key'],
                    'Version'     => $this->attributes['version'],
                    'Group'       => $this->attributes['group'],
                    'Name'        => $this->attributes['name'],
                    'Description' => $this->attributes['description'],
                    'Default'     => $text,
                    'Values'      => $this->attributes['values'],
                    'Type'        => $this->attributes['type'],
                    'Data_type'   => $this->attributes['validate'],
                    'Plugin'      => $this->key,
                );
                break;

            case 'block':
                $this->blocks[] = array(
                    'Key'      => $this->attributes['key'],
                    'Version'  => $this->attributes['version'],
                    'Name'     => $this->attributes['name'],
                    'Side'     => $this->attributes['side'],
                    'Type'     => $this->attributes['type'],
                    'Readonly' => (isset($this->attributes['lock']) && $this->attributes['lock'] == 0) ? 0 : 1,
                    'Tpl'      => (int) $this->attributes['tpl'],
                    'Content'  => $text,
                    'Plugin'   => $this->key,
                    'Status'   => 'approval',
                    'Sticky'   => 1,
                    'Header'   => (isset($this->attributes['header']) && $this->attributes['header'] == '0') ? 0 : 1,
                );
                break;

            case 'aBlock':
                $this->aBlocks[] = array(
                    'Key'     => $this->attributes['key'],
                    'Version' => $this->attributes['version'],
                    'Name'    => $this->attributes['name'],
                    'Content' => $text,
                    'Plugin'  => $this->key,
                    'Status'  => 'approval',
                    'Column'  => (int) $this->attributes['column'],
                    'Ajax'    => (int) $this->attributes['ajax'],
                    'Fixed'   => (int) $this->attributes['fixed'],
                );
                break;

            case 'page':
                $this->pages[] = array(
                    'Key'        => $this->attributes['key'],
                    'Version'    => $this->attributes['version'],
                    'Login'      => (int) $this->attributes['login'],
                    'Name'       => $this->attributes['name'],
                    'Page_type'  => $this->attributes['type'],
                    'Path'       => $this->attributes['path'],
                    'Get_vars'   => $this->attributes['get'],
                    'Controller' => $this->attributes['controller'],
                    'Menus'      => $this->attributes['menus'],
                    'Tpl'        => (int) $this->attributes['tpl'],
                    'Content'    => $text,
                    'Plugin'     => $this->key,
                );
                break;

            case 'email':
                $is_valid_type = in_array($this->attributes['type'], array('plain', 'html'));

                $this->emails[] = array(
                    'Key'     => $this->attributes['key'],
                    'Type'    => $is_valid_type ? $this->attributes['type'] : 'plain',
                    'Version' => $this->attributes['version'],
                    'Subject' => $this->attributes['subject'],
                    'Body'    => $text,
                    'Plugin'  => $this->key,
                );
                break;

            case 'update':
                $_class = strval($this->attributes['class'] ?: $this->class);

                $this->updates[] = array(
                    'Version' => $this->attributes['version'],
                    'Files'   => $this->attributes['files'],
                    'Class'   => $_class,
                    'Code'    => $text,
                );
                break;

            case 'notice':
                $this->notices[] = array(
                    'Version' => $this->attributes['version'],
                    'Content' => $text,
                );
                break;

            case 'file';
                $this->files[] = $text;
                break;

            case 'install':
            case 'uninstall':
                $_class = strval($this->attributes['class'] ?: $this->class);

                $this->{$this->inTag} = array(
                    'class' => $_class ?: false,
                    'code'  => $text,
                );
                break;

            case 'version':
            case 'date':
            case 'class':
            case 'title':
            case 'description':
            case 'author':
            case 'owner':
            case 'controller':
                $this->controllerUpdate = $this->attributes['version'];
            case 'notice':
            case 'compatible':
                $this->{$this->inTag} = $text;
                break;
        }
    }

    /**
     * Uninstall plugin
     *
     * @package xAjax
     *
     * @param  string $plugin_key
     * @return object
     */
    public function ajaxUnInstall($plugin_key)
    {
        global $_response, $lang, $rlValid;

        $rlValid->sql($plugin_key);

        $plugin_info = $this->getRow("
            SELECT `Class`, `Uninstall` FROM `{db_prefix}plugins` WHERE `Key` = '{$plugin_key}'
        ");

        $tables = array(
            'lang_keys',
            'hooks',
            'config',
            'config_groups',
            'blocks',
            'admin_blocks',
            'pages',
            'email_templates',
        );
        foreach ($tables as $table) {
            $this->query("DELETE FROM `{db_prefix}{$table}` WHERE `Plugin` = '{$plugin_key}'");
        }
        $this->query("DELETE FROM `{db_prefix}plugins` WHERE `Key` = '{$plugin_key}'");

        /**
         * @since 4.7.0 - Using PluginManager class instead of the internal method
         * @since 4.6.0
         */
        try {
            $instance = PluginManager::getPluginInstance($plugin_key, $plugin_info['Class']);

            if ($instance && $instance instanceof PluginInterface) {
                $instance->uninstall();
            } elseif ($plugin_info['Uninstall'] !== '') {
                @eval($plugin_info['Uninstall']);
            }
        } catch (Exception $e) {
            $GLOBALS['rlDebug']->logger($e->getMessage());
        }

        // Reload grid
        $_response->script('pluginsGrid.reload();');
        $_response->script("printMessage('notice', '{$lang['notice_plugin_uninstalled']}');");

        // Remove menu item
        $_response->script("
            $('#mPlugin_{$plugin_key}').remove();
            apMenu['plugins']['{$plugin_key}'] = false;
        ");

        return $_response;
    }

    /**
    * remote plugin installtion
    *
    * @package xAjax
    *
    * @param string $key - plugin key
    * @param bool $direct - direct install through plugins manager
    *
    **/
    function ajaxRemoteInstall( $key = false, $direct = false )
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        @eval(base64_decode(RL_SETUP));

        if ($key && $license_domain && $license_number) {
            $destination = RL_PLUGINS . $key . '.zip';
            $copy = "https://www.flynax.com/_request/remote-plugin-upload.php";
            $copy .= "?key={$key}";
            $copy .= "&domain={$license_domain}";
            $copy .= "&license={$license_number}";
            $copy .= "&software={$GLOBALS['config']['rl_version']}";
            $copy .= '&php=' . phpversion();
            $target = RL_PLUGINS . $key . '/';

            /* change progress status */
            $_response->script("
                var area = $('div.changelog_item a[name={$key}]').closest('div.changelog_item');
                $(area).next().find('div.progress').html('{$lang['remote_progress_download']}');
            ");

            //copy remote file
            if ($this->copyRemoteFile($copy, $destination)) {
                $this->rlChmod($destination);

                if (is_readable($destination)) {
                    Archive::unpack($destination, $target);

                    if (is_readable("{$target}install.xml")) {
                        /* call direct install method */
                        $_response->script("setTimeout(function(){ continueInstallation('{$key}') }, 1000);");
                    } else {
                        $_response->script("printMessage('error', '{$lang['plugin_download_fail']}');");
                        $GLOBALS['rlDebug']->logger("Unable to use remote plugin downloading wizard, downloading/extracting file fail.");

                        $_response->call('hideProgressBar');
                    }

                    return $_response;
                } else {
                    $_response->script("printMessage('error', '{$lang['plugin_download_fail']}');");
                    $GLOBALS['rlDebug']->logger("Unable to use remote plugin downloading wizard, downloading/extracting file fail.");
                }
            } else {
                $_response->script("printMessage('error', '{$lang['flynax_connect_fail']}');");
                $GLOBALS['rlDebug']->logger("Unable to use remote plugin downloading wizard, connect fail.");
            }
        } else {
            $_response->script("printMessage('alert', '{$lang['plugin_download_deny']}');");
            $GLOBALS['rlDebug']->logger("Unable to use remote plugin downloading wizard, license conflict.");
        }

        $_response->call('hideProgressBar');

        return $_response;
    }

    /**
     * remote plugin update
     *
     * @package xAjax
     *
     * @param string $plugin_key - plugin key
     * @param bool $direct - direct update through plugins manager
     *
     **/
    public function ajaxRemoteUpdate($plugin_key = false, $direct = false)
    {
        global $_response, $lang;

        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$plugin_key) {
            return $_response;
        }

        $GLOBALS['rlValid']->sql($plugin_key);
        @eval(base64_decode(RL_SETUP));

        if ($license_domain && $license_number) {
            /* get plugin info */
            $plugin = $this->fetch(array('Version'), array('Key' => $plugin_key), null, 1, 'plugins', 'row');

            /* backup current plugin version */
            if (is_writable(RL_ROOT . 'backup' . RL_DS . 'plugins' . RL_DS)) {
                $source  = RL_PLUGINS . $plugin_key . RL_DS;
                $archive = RL_ROOT . 'backup' . RL_DS . 'plugins' . RL_DS . $plugin_key;
                $archive .= "({$plugin['Version']})_" . date('d.m.Y') . '.zip';

                Archive::pack($source, $archive);

                /* backup hooks */
                $this->setTable('hooks');
                $backup_hooks = $this->fetch(array('Name', 'Code'), array('Plugin' => $plugin_key));
                if ($backup_hooks) {
                    foreach ($backup_hooks as $index => $backup_hook) {
                        $file_content .= <<< VS
{$backup_hook['Name']}\r\n{$backup_hook['Code']}\r\n\r\n
VS;
                    }

                    $hooks_backup_path = RL_ROOT . 'backup' . RL_DS . 'plugins' . RL_DS . $plugin_key . "({$plugin['Version']})_" . date('d.m.Y') . ".txt";
                    $file = fopen($hooks_backup_path, 'w+');

                    fwrite($file, $file_content);
                    fclose($file);
                }

                $destination = RL_UPLOAD . $plugin_key . '.zip';
                $copy = "https://www.flynax.com/_request/remote-plugin-upload.php";
                $copy .= "?key={$plugin_key}";
                $copy .= "&domain={$license_domain}";
                $copy .= "&license={$license_number}";
                $copy .= "&software={$GLOBALS['config']['rl_version']}";
                $copy .= '&php=' . phpversion();
                $target = RL_UPLOAD . $plugin_key . '/';

                /* copy remote file */
                if ($this->copyRemoteFile($copy, $destination)) {
                    /* change progress status */
                    if ($direct) {
                        $_response->script("$('div#progress div.progress').html('{$lang['remote_progress_download']}')");
                    } else {
                        $_response->script("
                            var area = $('div.changelog_item a[name={$plugin_key}]').closest('div.changelog_item');
                            $(area).next().find('div.progress').html('{$lang['remote_progress_download']}');
                        ");
                    }

                    $this->rlChmod($destination);

                    if (is_readable($destination)) {
                        Archive::unpack($destination, $target);

                        if (is_readable("{$target}install.xml")) {
                            // call direct install method
                            $_response->script("setTimeout(function(){ continueUpdating('{$plugin_key}') }, 1000);");
                        } else {
                            if ($direct) {
                                $_response->script("$('#update_info').fadeIn();");
                            }
                            $_response->script("printMessage('error', '{$lang['plugin_download_fail']}');");
                            $GLOBALS['rlDebug']->logger("Unable to use remote plugin downloading wizard, downloading/extracting file fail.");

                            $_response->call('hideProgressBar');
                        }

                        return $_response;
                    } else {
                        if ($direct) {
                            $_response->script("$('#update_info').fadeIn();");
                        }
                        $_response->script("printMessage('error', '{$lang['plugin_download_fail']}');");
                        $_response->script("setTimeout(function(){ xajax_getPluginsLog() }, 1000);");
                        $GLOBALS['rlDebug']->logger("Unable to use remote plugin downloading wizard, downloading/extracting file fail.");
                    }
                } else {
                    if ($direct) {
                        $_response->script("$('#update_info').fadeIn();");
                    }

                    if ($http_response_header[0] == "HTTP/1.1 403 Forbidden") {
                        $_response->script("printMessage('error', '{$lang['plugin_denied']}')");
                        $_response->script("$('#update_area').fadeOut()");
                    } else {
                        $_response->script("printMessage('error', '{$lang['flynax_connect_fail']}');");
                        $GLOBALS['rlDebug']->logger("Unable to use remote plugin downloading wizard, connect fail.");
                    }
                }
            } else {
                if ($direct) {
                    $_response->script("$('#update_info').fadeIn();");
                }
                $_response->script("printMessage('alert', '{$lang['plugin_backingup_deny']}');");
                $_response->script("setTimeout(function(){ xajax_getPluginsLog() }, 1000);");

                $GLOBALS['rlDebug']->logger("Unable to backup current plugin version.");
            }
        } else {
            if ($direct) {
                $_response->script("$('#update_info').fadeIn();");
            }
            $_response->script("printMessage('alert', '{$lang['plugin_download_deny']}');");
            $GLOBALS['rlDebug']->logger("Unable to use remote plugin downloading wizard, license conflict.");
        }

        $_response->call('hideProgressBar');

        return $_response;
    }

    /**
     * browse plugins
     *
     * @package xAjax
     *
     **/
    public function ajaxBrowsePlugins()
    {
        global $_response, $config, $lang, $rlSmarty;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        /* scan plugins directory */
        $plugins_exist = $this->scanDir(RL_PLUGINS, true);

        /*
         * get available plugins
         * YOU ARE NOT PERMITTED TO MODIFY THE CODE BELOW
         */
        @eval(base64_decode(RL_SETUP));
        $feed_url = $config['flynax_plugins_browse_feed'] . '?domain=' . $license_domain . '&license=' . $license_number;
        $feed_url .= '&software=' . $config['rl_version'] . '&php=' . phpversion();
        $xml = $this->getPageContent($feed_url);
        /* END CODE */

        $this->loadClass('Rss');
        $GLOBALS['rlRss']->items_number = 200;
        $GLOBALS['rlRss']->items = array('key', 'path', 'name', 'version', 'date', 'paid', 'compatible');
        $GLOBALS['rlRss']->createParser($xml);
        $plugins = $GLOBALS['rlRss']->getRssContent();

        if (!$plugins) {
            $fail_msg = strpos($xml, 'access_forbidden') ? $lang['flynax_connect_forbidden'] : $lang['flynax_connect_fail'];
            $_response->script("
                printMessage('error', '{$fail_msg}');
                $('.button_bar > #browse_plugins').html('{$lang['browse']}');
            ");
            return $_response;
        }

        foreach ($plugins as $key => $plugin) {
            if (is_numeric(array_search($plugin['key'], $plugins_exist))) {
                unset($plugins[$key]);
            }
        }

        if (count($plugins)) {
            $rlSmarty->assign_by_ref('plugins', $plugins);
            // build DOM
            $tpl = 'blocks' . RL_DS . 'flynaxPluginsBrowse.block.tpl';
            $_response->assign('browse_content', 'innerHTML', $rlSmarty->fetch($tpl, null, null, false));
        } else {
            $_response->script("$('#browse_content').html('{$lang['no_new_plugins']}');");
        }

        $_response->script("
            $('#update_area, #search_area').slideUp('fast');
            $('#browse_area').slideDown('normal');
            $('.button_bar > #browse_plugins').html('{$lang['more_plugins']}');
            plugins_loaded = true;
        ");

        $_response->call('rlPluginRemoteInstall');

        return $_response;
    }

    /**
     * Checking compatibility between version of the plugin and version of the software
     *
     * @since 4.6.0
     *
     * @param  string $plugin - Key of plugin which need be checked
     * @return bool
     */
    public function checkCompatibility($plugin)
    {
        $compatibility = true;

        if ($plugin) {
            $path_to_install = RL_PLUGINS . $plugin . RL_DS . 'install.xml';

            if (is_readable($path_to_install)) {
                require_once RL_LIBS . 'saxyParser' . RL_DS . 'xml_saxy_parser.php';

                $rlParser = new SAXY_Parser();
                $rlParser->xml_set_element_handler(array(&$this, 'startElement'), array(&$this, 'endElement'));
                $rlParser->xml_set_character_data_handler(array(&$this, 'charData'));
                $rlParser->xml_set_comment_handler(array(&$this, 'commentElement'));
                $rlParser->parse(file_get_contents($path_to_install));

                $compatibility = $this->checkCompatibilityByVersion($this->compatible);

                unset($this->compatible);
            }
        }

        return $compatibility;
    }

    /**
     * Compare needed version and version of the software
     *
     * @since 4.6.0
     *
     * @param  string $version - Version of the plugin
     * @return bool
     */
    public function checkCompatibilityByVersion($version)
    {
        if ($version && version_compare($version, $GLOBALS['config']['rl_version']) > 0) {
            return false;
        }

        return true;
    }

    /**
     * @deprecated 4.7.0
     * @see Flynax\Classes\PluginManager::getPluginInstance
     */
    protected static function getPluginInstance($plugin_key, $plugin_class)
    {
        return PluginManager::getPluginInstance($plugin_key, $plugin_class);
    }
}
