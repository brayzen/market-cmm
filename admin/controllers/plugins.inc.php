<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: PLUGINS.INC.PHP
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

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];
        $key = $rlValid->xSql($_GET['key']);

        $files_exist = true;

        if ($field == 'Status' && $id) {
            /* activete/deactivate plugin */
            $plugin_info = $rlDb->fetch(array('Key', 'Files', 'Class'), array('ID' => (int) $id), null, 1, 'plugins', 'row');

            if (empty($plugin_info)) {
                exit;
            }

            if ($value == 'active') {
                $files = unserialize($plugin_info['Files']);
                foreach ($files as $file) {
                    $file = str_replace(array('\\', '/'), array(RL_DS, RL_DS), $file);

                    if (!is_readable(RL_PLUGINS . $plugin_info['Key'] . RL_DS . $file)) {
                        $files_exist = false;
                        $missed_files .= RL_DS . "plugins" . RL_DS . $plugin_info['Key'] . RL_DS . "<b>" . $file . "</b><br />";
                    }
                }
            }

            if ($files_exist === true) {
                $tables = array('lang_keys', 'hooks', 'blocks', 'admin_blocks', 'pages', 'email_templates', 'payment_gateways');

                foreach ($tables as $table) {
                    unset($plugin_update);
                    $plugin_update = array(
                        'fields' => array(
                            'Status' => $value,
                        ),
                        'where'  => array(
                            'Plugin' => $plugin_info['Key'],
                        ),
                    );
                    $rlActions->updateOne($plugin_update, $table);
                }

                /**
                 * @since 4.7.0
                 */
                try {
                    $instance = PluginManager::getPluginInstance($plugin_info['Key'], $plugin_info['Class']);

                    if ($instance && method_exists($instance, 'statusChanged')) {
                        $instance->statusChanged($value);
                    }
                } catch (Exception $exception) {
                    $rlDebug->logger($exception->getMessage());
                }

                if ($plugin_info['Key'] == 'androidConnect' || $plugin_info['Key'] == 'iFlynaxConnect') {
                    $ap_cont_update = array(
                        'fields' => array(
                            'Parent_ID' => $value == 'active' ? 0 : -1,
                        ),
                        'where'  => array(
                            'Key' => $plugin_info['Key'] == 'androidConnect' ? 'android' : 'iFlynaxConnect',
                        ),
                    );
                    $rlActions->updateOne($ap_cont_update, "admin_controllers");
                }
            } else {
                $message = str_replace('{files}', "<br />" . $missed_files, $lang['plugin_files_missed']);
                echo $message;
                unset($missed_files);
            }
        }

        if ($files_exist === true) {
            $updateData = array(
                'fields' => array(
                    $field => $value,
                ),
                'where'  => array(
                    'ID' => $id,
                ),
            );

            $rlHook->load('apExtPluginsUpdate');

            $rlActions->updateOne($updateData, 'plugins');
        }
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $sql = "SELECT * FROM `{db_prefix}plugins` WHERE 1 ";

    // search simulation
    if (array_key_exists('plugin', $_GET)) {
        $plugin = $rlValid->xSql(urldecode($_GET['plugin']));
        $sql .= "AND (`Key` LIKE '%{$plugin}%' OR `Name` LIKE '%{$plugin}%') ";
    }

    $only_not_installed = false;
    if (array_key_exists('status', $_GET)) {
        if ($_GET['status'] == 'not_installed') {
            $only_not_installed = true;
        } else {
            $plugin_status = $rlValid->xSql($_GET['status']);
            $sql .= "AND `Status` = '{$plugin_status}' ";
        }
    }

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $insPlugins[$data[$key]['Key']] = $data[$key];
    }
    unset($data);

    /* scan plugins directory */
    $all_plugins = $reefless->scanDir(RL_PLUGINS, true);
    $plugins_out = array();

    // sort by status
    foreach ($all_plugins as $key => $value) {
        if (isset($insPlugins[$all_plugins[$key]])) {
            if (!$only_not_installed) {
                array_push($plugins_out, $insPlugins[$all_plugins[$key]]);
            }
        } else {
            if (array_key_exists('status', $_GET) && !$only_not_installed) {
                continue;
            }

            array_push($plugins_out, array(
                'Name'        => $all_plugins[$key],
                'Key'         => $all_plugins[$key] . '|not_installed',
                'Version'     => $lang['not_available'],
                'Description' => $lang['not_available'],
                'Status'      => 'not_installed',
            ));
        }
    }

    // sort by name/key if necessary
    if (array_key_exists('plugin', $_GET) && !empty($plugins_out)) {
        $pattern = "/{$plugin}/i";
        foreach ($plugins_out as $key => $value) {
            if (!preg_match($pattern, $value['Key']) && !preg_match($pattern, $value['Name'])) {
                unset($plugins_out[$key]);
            }
        }
    }

    // check not compatible plugins
    $reefless->loadClass('Plugin', 'admin');
    foreach ($plugins_out as $key => $value) {
        if (strpos($value['Key'], 'not_installed')) {
            $plugin_key = explode('|', $value['Key']);
            $plugins_out[$key]['Compatible'] = $rlPlugin->checkCompatibility($plugin_key[0]);

            if (!$plugins_out[$key]['Compatible']) {
                $plugins_out[$key]['Status'] = $lang['plugin_not_compatible'];
            }
        } else {
            $plugins_out[$key]['Compatible'] = true;
        }
    }

    $total_plugins = count($plugins_out);
    $plugins_out = array_slice($plugins_out, $start, $limit);

    $rlHook->load('apExtPluginsData');

    $reefless->loadClass('Json');

    $output['total'] = $total_plugins;
    $output['data'] = $plugins_out;

    echo $rlJson->encode($output);
}
/* ext js action end */

/* ajax action */
elseif ($_REQUEST['q'] == 'ajax') {
    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    $id = (int) $_GET['id'];

    if (empty($id)) {
        exit;
    }

    if ($_REQUEST['action'] == 'check_complete') {
        $plugin_info = $rlDb->fetch(array('Key', 'Files'), array('ID' => (int) $id), null, 1, 'plugins', 'row');

        if (empty($plugin_info)) {
            exit;
        }

        $files = unserialize($plugin_info['Files']);
        foreach ($files as $file) {
            $file = str_replace(array('\\', '/'), array(RL_DS, RL_DS), $file);

            if (!is_readable(RL_PLUGINS . $plugin_info['Key'] . RL_DS . $file)) {
                $files_exist = false;
                $message .= RL_DS . "plugins" . RL_DS . $plugin_info['Key'] . RL_DS . "<b>" . $file . "</b><br />";
            }
        }

        $reefless->loadClass('Json');
        echo $rlJson->encode(empty($message) ? true : str_replace('{files}', "<br />" . $message, $GLOBALS['lang']['plugin_files_missed']));
    }
}
/* ajax action end */

else {
    eval(base64_decode(RL_SETUP));
    eval(base64_decode(RL_ASSIGN));

    $reefless->loadClass('Plugin', 'admin');

    /* register ajax methods */
    $rlXajax->registerFunction(array('install', $rlPlugin, 'ajaxInstall'));
    $rlXajax->registerFunction(array('remoteInstall', $rlPlugin, 'ajaxRemoteInstall'));
    $rlXajax->registerFunction(array('unInstall', $rlPlugin, 'ajaxUnInstall'));
    $rlXajax->registerFunction(array('checkForUpdate', $rlAdmin, 'ajaxCheckForUpdate'));
    $rlXajax->registerFunction(array('remoteUpdate', $rlPlugin, 'ajaxRemoteUpdate'));
    $rlXajax->registerFunction(array('update', $rlPlugin, 'ajaxUpdate'));
    $rlXajax->registerFunction(array('browsePlugins', $rlPlugin, 'ajaxBrowsePlugins'));

    $rlHook->load('apPhpPluginsBottom');
}
