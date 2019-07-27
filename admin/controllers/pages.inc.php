<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: PAGES.INC.PHP
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

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtPagesUpdate');

        $rlActions->updateOne($updateData, 'pages');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name` ";
    $sql .= "FROM `{db_prefix}pages` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('pages+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";

    // add search criteria
    if ($_GET['action'] == 'search') {
        foreach (array('Name', 'Page_type', 'Status') as $item) {
            if ($_GET[$item] && $s_value = $rlValid->xSql($_GET[$item])) {
                switch ($item) {
                    case 'Name':
                        $sql .= "AND `T2`.`Value` LIKE '%{$s_value}%' ";
                        break;

                    default:
                        $sql .= "AND `T1`.`{$item}` = '{$s_value}' ";
                        break;
                }
            }
        }
    }

    if ($sort) {
        $sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtPagesSql');

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Page_type'] = $lang[$data[$key]['Page_type']];
        $data[$key]['Login'] = $data[$key]['Login'] ? $lang['yes'] : $lang['no'];
        $data[$key]['No_follow'] = $data[$key]['No_follow'] ? $lang['yes'] : $lang['no'];
        $data[$key]['Status'] = $lang[$data[$key]['Status']];
    }

    $rlHook->load('apExtPagesData');

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $reefless->loadClass('Json');

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo $rlJson->encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpPagesTop');

    /* get account types */
    $reefless->loadClass('Account');
    $account_types = $rlAccount->getAccountTypes();
    $rlSmarty->assign_by_ref('account_types', $account_types);

    /* additional bread crumb step */
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_page'] : $lang['edit_page'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        /* get all pages */
        $all_pages = $rlDb->fetch(array('ID', 'Key'), array('Status' => 'active'), "AND `Key` <> 'home' ORDER BY `Key`", null, 'pages');
        $all_pages = $rlLang->replaceLangKeys($all_pages, 'pages', array('name'));
        $rlSmarty->assign_by_ref('all_pages', $all_pages);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $key = $rlValid->xSql($_GET['page']);

            // get current page info
            $info = $rlDb->fetch('*', array('Key' => $key), "AND `Status` <> 'trash'", null, 'pages', 'row');

            $_POST['key'] = $info['Key'];
            $_POST['status'] = $info['Status'];
            $_POST['login'] = $info['Login'];
            $_POST['page_type'] = $info['Page_type'];
            $_POST['path'] = $info['Path'];
            $_POST['deny'] = explode(',', $info['Deny']);
            $_POST['tpl'] = $info['Tpl'];
            $_POST['no_follow'] = $info['No_follow'];

            $aMenus = explode(',', $info['Menus']);
            foreach ($aMenus as $amKey => $amVal) {
                $_POST['menus'][$amVal] = $amVal;
            }

            // get names
            $names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+name+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($names as $nKey => $nVal) {
                $_POST['name'][$names[$nKey]['Code']] = $names[$nKey]['Value'];
            }

            // get titles
            $titles = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+title+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($titles as $tKey => $tVal) {
                $_POST['title'][$titles[$tKey]['Code']] = $titles[$tKey]['Value'];
            }

            // get h1 heading
            $h1_headings = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+h1+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($h1_headings as $tKey => $tVal) {
                $_POST['h1_heading'][$h1_headings[$tKey]['Code']] = $h1_headings[$tKey]['Value'];
            }

            // get meta description
            $meta_description = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+meta_description+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($meta_description as $tKey => $tVal) {
                $_POST['meta_description'][$meta_description[$tKey]['Code']] = $meta_description[$tKey]['Value'];
            }

            // get meta keywords
            $meta_keywords = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+meta_keywords+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($meta_keywords as $tKey => $tVal) {
                $_POST['meta_keywords'][$meta_keywords[$tKey]['Code']] = $meta_keywords[$tKey]['Value'];
            }

            // content
            $content = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+content+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($content as $cKey => $cVal) {
                $_POST['content_' . $content[$cKey]['Code']] = $content[$cKey]['Value'];
            }

            if ($info['Page_type'] == 'system') {
                $_POST['controller'] = $info['Controller'];
            } elseif ($info['Page_type'] == 'external') {
                $_POST['external_url'] = $info['Controller'];
            }

            $rlHook->load('apPhpPagesPost');
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $f_key = $_POST['key'];
            $f_page_type = $_POST['page_type'];
            $f_menus = $_POST['menus'];

            /* check key exist (in add mode only) */
            if ($_GET['action'] == 'add') {
                /* check key */
                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }

                if (strlen($f_key) < 3) {
                    $errors[] = $lang['incorrect_phrase_key'];
                    $error_fields[] = 'key';
                }

                $exist_key = $rlDb->fetch(array('Key', 'Status'), array('Key' => $f_key), null, null, 'pages', 'row');

                if (!empty($exist_key)) {
                    $exist_error = str_replace('{key}', "<b>\"{$f_key}\"</b>", $lang['notice_page_exist']);

                    if ($exist_key['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $errors[]       = $exist_error;
                    $error_fields[] = 'key';
                }
            }

            $f_key = $rlValid->str2key($f_key);

            /* check name */
            $f_name = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "name[{$lval['Code']}]";
                }

                $f_names[$allLangs[$lkey]['Code']] = $f_name[$allLangs[$lkey]['Code']];
            }

            /* check title */
            $f_title = $_POST['title'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_title[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['title'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "title[{$lval['Code']}]";
                }

                $f_titles[$allLangs[$lkey]['Code']] = $f_title[$allLangs[$lkey]['Code']];
            }

            /* check h1 */
            $f_h1_heading = $_POST['h1_heading'];

            foreach ($allLangs as $lkey => $lval) {
                $f_h1_heading[$allLangs[$lkey]['Code']] = $f_h1_heading[$allLangs[$lkey]['Code']];
            }

            /* check path */
            if ($f_page_type != 'external') {
                $f_path = $_POST['path'];

                if ($f_key != 'home') {
                    if (!utf8_is_ascii($f_path)) {
                        $f_path = utf8_to_ascii($f_path);
                    }

                    if (strlen($f_path) < 3) {
                        $errors[] = $lang['incorrect_page_address'];
                        $error_fields[] = "path";
                    }
                }

                $f_path = $rlValid->str2path($f_path);

                $exist_path = $rlDb->fetch(
                    array('Key', 'Status'),
                    array('Path' => $f_path),
                    "AND `Key` != '{$f_key}'",
                    null,
                    'pages',
                    'row'
                );

                if (!empty($exist_path)) {
                    $exist_error = str_replace('{path}', "<b>\"" . $f_path . "\"</b>", $lang['notice_page_path_exist']);

                    if ($exist_path['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $errors[]       = $exist_error;
                    $error_fields[] = "path";
                } elseif (substr($f_key, 0, 3) == 'lt_') {
                    $exist_path = $rlDb->fetch(array('Key'), array('Path' => $f_path), null, null, 'categories', 'row');

                    if (!empty($exist_path)) {
                        $errors[] = str_replace('{path}', "<b>\"" . $f_path . "\"</b>", $lang['notice_page_path_exist']);
                        $error_fields[] = "path";
                    }
                }

                preg_match('/\-[0-9]+$/', $f_path, $matches);
                if (!empty($matches)) {
                    $errors[] = $lang['category_url_listing_logic'];
                    $error_fields[] = "path";
                }
            } else {
                $f_path = 'external';
            }

            /* check page type */
            if (empty($f_page_type)) {
                $errors[] = $lang['notice_no_type_chose'];
                $error_fields[] = "page_type";
            }

            if ($f_page_type == 'system') {
                $f_controller = $_POST['controller'];

                if ($_GET['action'] == 'edit') {
                    $info = $rlDb->fetch('*', array('Key' => $f_key), "AND `Status` <> 'trash'", null, 'pages', 'row');
                }

                if ($info['Plugin']) {
                    $inc_file = RL_PLUGINS . $info['Plugin'] . RL_DS . $f_controller . ".inc.php";
                    $tpl_controller_file = RL_PLUGINS . $info['Plugin'] . RL_DS . $f_controller . ".tpl";
                } else {
                    $inc_file = RL_CONTROL . $f_controller . '.inc.php';

                    $tpl_controller_dir = RL_ROOT . 'templates' . RL_DS . $config['template'];
                    $tpl_controller_dir .= RL_DS . 'controllers' . RL_DS . $f_controller . '/';

                    $tpl_controller_file = RL_ROOT . 'templates' . RL_DS . $config['template'];
                    $tpl_controller_file .= RL_DS . 'tpl' . RL_DS . 'controllers' . RL_DS;
                    $tpl_controller_file .= $f_controller . '.tpl';

                    $tpl_controller_file_core = RL_ROOT . 'templates' . RL_DS . 'template_core';
                    $tpl_controller_file_core .= RL_DS . 'tpl' . RL_DS . 'controllers' . RL_DS;
                    $tpl_controller_file_core .= $f_controller . '.tpl';

                    $tpl_controller_dir_core = RL_ROOT . 'templates' . RL_DS . 'template_core';
                    $tpl_controller_dir_core .= RL_DS . 'controllers' . RL_DS . $f_controller . '/';
                }

                $inc_file_n = str_replace(RL_DS, '/', substr($inc_file, strlen(RL_ROOT)));
                $tpl_file_n = str_replace(RL_DS, '/', substr($tpl_controller_file, strlen(RL_ROOT)));

                if (empty($f_controller)) {
                    $errors[] = str_replace('{field}', '<b>"' . $lang['page_controller'] . '"</b>', $lang['notice_field_empty']);
                } elseif (
                    !is_file($inc_file)
                    || (!is_file($tpl_controller_file)
                        && !is_file($tpl_controller_file_core)
                        && !is_dir($tpl_controller_dir)
                        && !is_dir($tpl_controller_dir_core)
                    )
                ) {
                    $errors[] = str_replace(array('{inc_file}', '{tpl_file}'), array($inc_file_n, $tpl_file_n), $lang['notice_controller_no_files']);
                    $_POST['controller'] = $rlDb->getOne("Controller", "`Key` = '" . $f_key . "'", "pages");
                }
            } elseif ($f_page_type == 'external') {
                $f_external = $_POST['external_url'];

                if (!$rlValid->isUrl($f_external)) {
                    $errors[] = str_replace('{field}', '<b>"' . $lang['external_url'] . '"</b>', $lang['notice_field_incorrect']);
                    $error_fields[] = "external";
                }
            } elseif ($f_page_type == 'static') {
                foreach ($allLangs as $lkey => $lval) {
                    if (empty($_POST['content_' . $allLangs[$lkey]['Code']])) {
                        $errors[] = str_replace('{field}', "<b>" . $lang['page_content'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    }

                    $f_content[$allLangs[$lkey]['Code']] = $_POST['content_' . $allLangs[$lkey]['Code']];
                }
            }

            $rlHook->load('apPhpPagesValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // get max position
                    $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`");

                    // write main page information
                    $data = array(
                        'Key'       => $f_key,
                        //'Parent_ID' => $_POST['parent_id'],
                        'Status'    => $_POST['status'],
                        'Position'  => $position['max'] + 1,
                        'Page_type' => $f_page_type,
                        'Login'     => $_POST['login'],
                        'Path'      => $f_path,
                        'Tpl'       => $f_page_type == 'system' ? '1' : $_POST['tpl'],
                        'Menus'     => implode(',', $f_menus),
                        'Deny'      => implode(',', $_POST['deny']),
                        'Modified'  => 'NOW()',
                        'No_follow' => $_POST['no_follow'],
                    );

                    if ($f_page_type == 'system') {
                        $data['Controller'] = $f_controller;
                    } elseif ($f_page_type == 'external') {
                        $data['Controller'] = $f_external;
                    } elseif ($f_page_type == 'static') {
                        $data['Controller'] = 'static';
                    }

                    $rlHook->load('apPhpPagesBeforeAdd');

                    if ($action = $rlActions->insertOne($data, 'pages')) {
                        $rlHook->load('apPhpPagesAfterAdd');

                        // save phrases
                        foreach ($allLangs as $key => $value) {
                            // save names
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'pages+name+' . $f_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );

                            // save titles
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'pages+title+' . $f_key,
                                'Value'  => $f_titles[$allLangs[$key]['Code']],
                            );

                            // save h1s
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'pages+h1+' . $f_key,
                                'Value'  => $f_h1_heading[$allLangs[$key]['Code']],
                            );
                            // save meta description
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'pages+meta_description+' . $f_key,
                                'Value'  => $_POST['meta_description'][$allLangs[$key]['Code']],
                            );

                            // aave meta keywords
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'pages+meta_keywords+' . $f_key,
                                'Value'  => $_POST['meta_keywords'][$allLangs[$key]['Code']],
                            );

                            // save static content
                            if ($f_page_type == 'static') {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+content+' . $f_key,
                                    'Value'  => $f_content[$allLangs[$key]['Code']],
                                );
                            }
                        }

                        $rlActions->insert($lang_keys, 'lang_keys');

                        $message = $lang['page_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new page (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new page (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    // Enable/Disable "design" option for page
                    if ($f_page_type == 'system') {
                        $f_tpl = in_array($f_key, ['rss_feed', 'print']) ? '0' : '1';
                    } else {
                        $f_tpl = $_POST['tpl'];
                    }

                    $update_data = array(
                        'fields' => array(
                            'Status'    => $_POST['status'],
                            'Page_type' => $f_page_type,
                            'Login'     => $_POST['login'],
                            'Path'      => $f_path,
                            'Tpl'       => $f_tpl,
                            'Menus'     => implode(',', $f_menus),
                            'Deny'      => implode(',', $_POST['deny']),
                            'Modified'  => 'NOW()',
                            'No_follow' => $_POST['no_follow'],
                        ),
                        'where'  => array('Key' => $f_key),
                    );

                    if ($f_page_type == 'system') {
                        $update_data['fields']['Controller'] = $f_controller;
                    } elseif ($f_page_type == 'external') {
                        $update_data['fields']['Controller'] = $f_external;
                    } elseif ($f_page_type == 'static') {
                        $update_data['fields']['Controller'] = 'static';
                    }

                    $rlHook->load('apPhpPagesBeforeEdit');

                    $action = $GLOBALS['rlActions']->updateOne($update_data, 'pages');

                    $rlHook->load('apPhpPagesAfterEdit');

                    // edit name's values
                    foreach ($allLangs as $key => $value) {
                        if ($rlDb->getOne('ID', "`Key` = 'pages+name+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit name
                            $update_names = array(
                                'fields' => array(
                                    'Value' => $f_name[$allLangs[$key]['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key'  => 'pages+name+' . $f_key,
                                ),
                            );
                            // update
                            $rlActions->updateOne($update_names, 'lang_keys');
                        } else {
                            // insert names
                            $insert_names = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Key'    => 'pages+name+' . $f_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_names, 'lang_keys');
                        }

                        if ($rlDb->getOne('ID', "`Key` = 'pages+title+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit title
                            $update_titles = array(
                                'fields' => array(
                                    'Value' => $f_titles[$allLangs[$key]['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key'  => 'pages+title+' . $f_key,
                                ),
                            );

                            // update
                            $GLOBALS['rlActions']->updateOne($update_titles, 'lang_keys');
                        } else {
                            // insert titles
                            $insert_titles = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Key'    => 'pages+title+' . $f_key,
                                'Value'  => $f_titles[$allLangs[$key]['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_titles, 'lang_keys');
                        }

                        if ($rlDb->getOne('ID', "`Key` = 'pages+h1+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit title
                            $update_h1s = array(
                                'fields' => array(
                                    'Value' => $f_h1_heading[$allLangs[$key]['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key'  => 'pages+h1+' . $f_key,
                                ),
                            );

                            // update
                            $GLOBALS['rlActions']->updateOne($update_h1s, 'lang_keys');
                        } else {
                            // insert titles
                            $insert_h1s = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Key'    => 'pages+h1+' . $f_key,
                                'Value'  => $f_h1_heading[$allLangs[$key]['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_h1s, 'lang_keys');
                        }
                        // edit meta description
                        $exist_meta_description = $rlDb->fetch(array('ID'), array('Key' => 'pages+meta_description+' . $f_key, 'Code' => $allLangs[$key]['Code']), null, null, 'lang_keys', 'row');
                        if (!empty($exist_meta_description)) {
                            $lang_keys_meta_description['where'] = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Key'  => 'pages+meta_description+' . $f_key,
                            );
                            $lang_keys_meta_description['fields'] = array(
                                'Value' => $_POST['meta_description'][$allLangs[$key]['Code']],
                            );

                            // update
                            $GLOBALS['rlActions']->updateOne($lang_keys_meta_description, 'lang_keys');
                        } else {
                            $lang_keys_meta_description = array(
                                'Value' => $_POST['meta_description'][$allLangs[$key]['Code']],
                                'Code'  => $allLangs[$key]['Code'],
                                'Key'   => 'pages+meta_description+' . $f_key,
                            );
                            // insert
                            $GLOBALS['rlActions']->insertOne($lang_keys_meta_description, 'lang_keys');
                        }

                        // edit meta keywords
                        $exist_meta_keywords = $rlDb->fetch(array('ID'), array('Key' => 'pages+meta_keywords+' . $f_key, 'Code' => $allLangs[$key]['Code']), null, null, 'lang_keys', 'row');
                        if (!empty($exist_meta_keywords)) {
                            $exist_meta_keywords['where'] = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Key'  => 'pages+meta_keywords+' . $f_key,
                            );
                            $exist_meta_keywords['fields'] = array(
                                'Value' => $_POST['meta_keywords'][$allLangs[$key]['Code']],
                            );

                            // update
                            $GLOBALS['rlActions']->updateOne($exist_meta_keywords, 'lang_keys');
                        } else {
                            $exist_meta_keywords = array(
                                'Value' => $_POST['meta_keywords'][$allLangs[$key]['Code']],
                                'Code'  => $allLangs[$key]['Code'],
                                'Key'   => 'pages+meta_keywords+' . $f_key,
                            );
                            // insert
                            $GLOBALS['rlActions']->insertOne($exist_meta_keywords, 'lang_keys');
                        }

                        if ($f_page_type == 'static') {
                            $exist_content = $rlDb->fetch(array('ID'), array('Key' => 'pages+content+' . $f_key, 'Code' => $allLangs[$key]['Code']), null, null, 'lang_keys', 'row');
                            if (!empty($exist_content)) {
                                // edit content
                                $lang_keys_content['where'] = array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key'  => 'pages+content+' . $f_key,
                                );
                                $lang_keys_content['fields'] = array(
                                    'Value' => $f_content[$allLangs[$key]['Code']],
                                );

                                // update
                                $GLOBALS['rlActions']->updateOne($lang_keys_content, 'lang_keys');
                            } else {
                                $lang_keys_content = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+content+' . $f_key,
                                    'Value'  => $f_content[$allLangs[$key]['Code']],
                                );
                                $rlActions->insertOne($lang_keys_content, 'lang_keys');
                            }
                        }
                    }

                    $message = $lang['page_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }

    /* register ajax methods */
    $rlXajax->registerFunction(array('deletePage', $rlAdmin, 'ajaxDeletePage'));

    $rlHook->load('apPhpPagesBottom');
}
