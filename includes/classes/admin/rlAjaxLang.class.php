<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLAJAXLANG.CLASS.PHP
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

class rlAjaxLang extends reefless
{
    /**
     * set language as default
     *
     * @package ajax
     *
     * @param string $object - DOM object id
     * @param string $code - language code
     *
     **/
    public function ajaxSetDefault($object, $code)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if ($GLOBALS['rlConfig']->setConfig('lang', $code)) {
            $_response->script("languagesGrid.reload();");
            $_response->script("printMessage('notice', '{$lang['changes_saved']}')");
        } else {
            trigger_error("Can not set default language, MySQL problems", E_WARNING);
            $GLOBALS['rlDebug']->logger("Can not set default language, MySQL problems");
        }

        return $_response;
    }

    /**
     * add new language (copy from exist)
     *
     * @package ajax
     *
     * @param array $data - new language data
     *
     **/
    public function ajax_addLanguage($data)
    {
        global $_response;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');
        $lang_name = $lang_key = $GLOBALS['rlValid']->xSql(str_replace(array('"', "'"), array('', ''), $data[0][1]));

        if (empty($lang_name)) {
            $error[] = $GLOBALS['lang']['name_field_empty'];
        }

        if (!utf8_is_ascii($lang_name)) {
            $lang_key = utf8_to_ascii($lang_name);
        }

        $lang_key = strtolower(str_replace(array('"', "'"), array('', ''), $lang_key));

        $iso_code = strtolower($GLOBALS['rlValid']->xSql($data[1][1]));

        if (!utf8_is_ascii($iso_code)) {
            $error[] = $GLOBALS['lang']['iso_code_incorrect_charset'];
        } else {
            if (strlen($iso_code) != 2) {
                $error[] = $GLOBALS['lang']['iso_code_incorrect_number'];
            }

            //check language exist
            $lang_exist = $this->fetch('*', array('Code' => $iso_code), null, null, 'languages');

            if (!empty($lang_exist)) {
                $error[] = $GLOBALS['lang']['iso_code_incorrect_exist'];
            }
        }

        /* check direction */
        $direction = $data[4][1];

        if (!in_array($direction, array('rtl', 'ltr'))) {
            $error[] = $GLOBALS['lang']['text_direction_fail'];
        }

        $locale = $data[5][1];
        if ($locale) {
            $locale = str_replace('-', '_', $locale);
            if (!preg_match('/[a-z]{2}_[A-Z]{2}/', $locale)) {
                $error[] = $GLOBALS['lang']['locale_code_incorrect'];
            }
        }

        /* check date format */
        $date_format = $GLOBALS['rlValid']->xSql($data[2][1]);

        if (empty($date_format) || strlen($date_format) < 5) {
            $error[] = $GLOBALS['lang']['language_incorrect_date_format'];
        }

        if (!empty($error)) {
            /* print errors */
            $error_content = '<ul>';
            foreach ($error as $err) {
                $error_content .= "<li>{$err}</li>";
            }
            $error_content .= '</ul>';
            $_response->script('printMessage("error", "' . $error_content . '")');
        } else {
            $source_code = $GLOBALS['rlValid']->xSql($data[3][1]);
            $this->setTable('lang_keys');

            $sql = "INSERT INTO `{db_prefix}lang_keys` (`Code`, `Module`, `Key`, `Value`, `Plugin`, `Status`) ";
            $sql .= "(SELECT '{$iso_code}' as `Code`, `Module`, `Key`, `Value`, `Plugin`, `Status` FROM `{db_prefix}lang_keys` WHERE `Code` = '{$source_code}') ";

            if ($this->query($sql)) {
                $additional_row = array(
                    'Code'   => $iso_code,
                    'Module' => 'common',
                    'Key'    => 'languages+name+' . $lang_key,
                    'Value'  => $lang_name,
                    'Status' => 'active',
                );

                $GLOBALS['rlActions']->insertOne($additional_row, 'lang_keys');
            } else {
                $GLOBALS['rlDebug']->logger("Failed to copy phrases - mysql problem");
            }

            if (!empty($error)) {
                /* print errors */
                $_response->script("printMessage('error', '{$error}')");
            } else {
                $insert = array(
                    'Code'        => $iso_code,
                    'Direction'   => $direction,
                    'Key'         => $lang_key,
                    'Locale'      => $locale,
                    'Status'      => 'active',
                    'Date_format' => $date_format,
                );
                $GLOBALS['rlActions']->insertOne($insert, 'languages');

                /* print notice */
                $_response->script("
                    printMessage('notice', '{$GLOBALS['lang']['language_added']}');
                    show('lang_add_container');
                    languagesGrid.reload();
                ");
            }
        }

        $_response->script("$('#lang_add_load').fadeOut('slow');");

        return $_response;
    }

    /**
     * add new language phrase
     *
     * @package ajax
     *
     * @param array $data - new phrase data
     *
     **/
    public function ajax_addPhrase($data, $values)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        $key = str_replace(array('"', "'"), array("", ""), $data[0][1]);
        $key = $GLOBALS['rlValid']->xSql(trim($key));

        if (strlen($key) < 3) {
            $error[] = $lang['incorrect_phrase_key'];
        }

        if (!utf8_is_ascii($key)) {
            $error[] = $lang['key_incorrect_charset'];
        }

        $key = $GLOBALS['rlValid']->str2key($key);

        //check key exists
        $key_exist = $this->fetch('ID', array('Key' => $key), null, null, 'lang_keys', 'row');

        if (!empty($key_exist)) {
            $error[] = str_replace('{key}', "'<b>{$key}</b>'", $lang['notice_key_exist']);
        }

        $side = $GLOBALS['rlValid']->xSql($data[1][1]);

        if (!empty($error)) {
            /* print errors */
            $error_content = '<ul>';
            foreach ($error as $err) {
                $error_content .= "<li>{$err}</li>";
            }
            $error_content .= '</ul>';
            $_response->script('printMessage("error", "' . $error_content . '")');
        } else {
            foreach ($values as $index => $field) {
                $phrase[] = array('Code' => $values[$index][0], 'Value' => $values[$index][1], 'Module' => $side, 'Key' => $key, 'Status' => 'active');
            }

            if ($GLOBALS['rlActions']->insert($phrase, 'lang_keys')) {
                /* hide add phrase block */
                $_response->script("
                    show('lang_add_phrase');
                    $('#lang_add_phrase textarea').val('');
                    $('#lang_add_phrase input').val('');
                ");

                /* print notice */
                $_response->script("printMessage('notice', '{$lang['lang_phrase_added']}')");
            }
        }

        $_response->script("$('#add_phrase_submit').val('{$lang['add']}');");

        return $_response;
    }

    /**
     * delete language
     *
     * @package ajax
     *
     * @param int $id - language ID
     *
     **/
    public function ajaxDeleteLang($id)
    {
        global $_response, $config, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $id = (int) $id;
        $code = $this->getOne('Code', "`ID` = '{$id}'", 'languages');

        if (!$code || !$id) {
            return $_response;
        }

        /*handle multilingual fields - remove all tags if only one lang left*/
        if (count($GLOBALS['languages']) == 2) {
            $multilang_fields_listings = $this->fetch(array('Key'), array('Multilingual' => '1'), null, null, 'listing_fields');
            foreach ($multilang_fields_listings as $ml_key => $ml_field) {
                $custom_sql = "UPDATE `{db_prefix}listings` SET `{$ml_field['Key']}` = IF (LOCATE('{|/', `{$ml_field['Key']}`) > 0, IF (LOCATE('{|{$config['lang']}|}', `{$ml_field['Key']}`) > 0, SUBSTRING(`{$ml_field['Key']}` FROM LOCATE('{|{$config['lang']}|}', `{$ml_field['Key']}`)+6 FOR LOCATE('{|/{$config['lang']}|}', `{$ml_field['Key']}`) - LOCATE('{|{$config['lang']}|}', `{$ml_field['Key']}`)-6), SUBSTRING(`{$ml_field['Key']}` FROM 7 FOR LOCATE('{|/', `{$ml_field['Key']}`)-7)), `{$ml_field['Key']}`) WHERE `{$ml_field['Key']}` IS NOT NULL";
                $this->query($custom_sql);
            }
            $multilang_fields_accounts = $this->fetch(array('Key'), array('Multilingual' => '1'), null, null, 'account_fields');
            foreach ($multilang_fields_accounts as $ml_key => $ml_field) {
                $custom_sql = "UPDATE `{db_prefix}accounts` SET `{$ml_field['Key']}` = IF (LOCATE('{|/', `{$ml_field['Key']}`) > 0, IF (LOCATE('{|{$config['lang']}|}', `{$ml_field['Key']}`) > 0, SUBSTRING(`{$ml_field['Key']}` FROM LOCATE('{|{$config['lang']}|}', `{$ml_field['Key']}`)+6 FOR LOCATE('{|/{$config['lang']}|}', `{$ml_field['Key']}`) - LOCATE('{|{$config['lang']}|}', `{$ml_field['Key']}`)-6), SUBSTRING(`{$ml_field['Key']}` FROM 7 FOR LOCATE('{|/', `{$ml_field['Key']}`)-7)), `{$ml_field['Key']}`) WHERE `{$ml_field['Key']}` IS NOT NULL";
                $this->query($custom_sql);
            }
        }

        if ($config['lang'] != $code) {
            $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Code` = '{$code}'");
            $this->query("DELETE FROM `{db_prefix}languages` WHERE `Code` = '{$code}'");

            $_response->script("
                printMessage('notice', '{$lang['language_deleted']}');
                languagesGrid.reload();
            ");
        } else {
            trigger_error("The default language disabled for deleting", E_USER_WARNING);
            $GLOBALS['rlDebug']->logger("The default language disabled for deleting");
        }

        return $_response;
    }

    /**
     * Copy languages's phrases
     *
     * @since 4.7.2 - Remove unnecessary parameter "$name"
     *              - Added "$xAjax" parameter
     *
     * @package ajax
     *
     * @param bool $xAjax - Detect package which initial request (ajax|xAjax)
     * @param int  $from  - Language code 1
     * @param int  $to    - Language code 2
     *
     * @return object|bool
     */
    public function ajaxCopyPhrases($from = 0, $to = 0, $xAjax = true)
    {
        global $_response, $lang;

        // check admin session expire
        if ($xAjax && $this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . '/index.php';
            $redirect_url .= empty($_SERVER['QUERY_STRING'])
            ? '?session_expired'
            : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $from_lang = $_SESSION['lang_' . $from];
        $to_lang   = $_SESSION['lang_' . $to];

        if (!$from || !$to) {
            return $xAjax ? $_response : false;
        }

        $sql = "INSERT INTO `{db_prefix}lang_keys` (`Code`, `Module`, `Key`, `Value`, `Plugin`, `Status`) ";
        $sql .= "SELECT '{$to_lang}', `Module`, `Key`, `Value`, `Plugin`, `Status` FROM `{db_prefix}lang_keys` ";
        $sql .= "WHERE `Code` = '{$from_lang}' ";
        $sql .= "AND `Key` NOT IN (SELECT `Key` FROM `{db_prefix}lang_keys` ";
        $sql .= "WHERE `Code` = '{$to_lang}' AND `Status` = 'active')";

        $GLOBALS['rlDb']->query($sql);

        if ($xAjax) {
            $_response->script("printMessage('notice', '{$lang['compare_phrases_copied']}')");
            $_response->script("$('#copy_button_{$from}').slideUp('slow');");
            $_response->script("$('#loading_{$from}').fadeOut('fast');");
            $_response->script("compareGrid{$from}.reload();");
            return $_response;
        } else {
            return true;
        }
    }

    /**
     * mass delete phrases
     *
     * @package xAjax
     *
     * @param string $ids - phrases ids
     * @param string $code - language code
     * @param int $gridNumber - grid number | Compare mode
     *
     **/
    public function ajaxMassDelete($ids, $code = false, $gridNumber = false)
    {
        global $_response, $lang;

        //$where = !empty($code) ? "AND `Code` = '{$code}'" : "";

        $tmp_phrases = $_SESSION['source_' . $gridNumber];

        foreach ($tmp_phrases as $key => $val) {
            $phrases[$tmp_phrases[$key]['ID']] = $tmp_phrases[$key];
        }
        unset($tmp_phrases);

        $ids = explode('|', $ids);

        foreach ($ids as $id) {
            $id = (int) $id;
            $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `ID` = '{$id}' {$where} LIMIT 1");
            unset($phrases[$id]);
        }
        $_SESSION['source_' . $gridNumber] = $_SESSION['compare_' . $gridNumber] = $phrases;

        if (empty($phrases)) {
            $_response->script("$('#compare_area_{$gridNumber}').slideUp('slow')");
        }

        $_response->script("compareGrid{$gridNumber}.reload();");
        $_response->script("printMessage('notice', '{$lang['notice_items_deleted']}')");

        unset($phrases);

        return $_response;
    }

    /**
     * export language
     *
     * @package xAjax
     *
     * @param int $id - export language ID
     *
     **/
    public function exportLanguage($id = false)
    {
        global $lang, $config, $rlSmarty, $rlHook, $rlDb;

        if (!$id) {
            return false;
        }

        $info = $rlDb->fetch(
            array('Code', 'Key', 'Direction', 'Date_format', 'Locale'),
            array('ID' => $id),
            null,
            1,
            'languages',
            'row'
        );
        $name = $rlDb->getOne('Value', "`Key` = 'languages+name+{$info['Key']}'", 'lang_keys');

        $select = array('Value', 'Module', 'Key', 'Plugin', 'Status');
        $where  = array('Code' => $info['Code']);
        $extra_where  = '';
        
        /**
        * @since 4.7.1
        */
        $rlHook->load('apAjaxLangExportSelectPhrases', $select, $where, $extra_where);

        $phrases = $rlDb->fetch($select, $where, $extra_where, null, 'lang_keys');

        if ($phrases) {
            $insert = "INSERT INTO `{prefix}lang_keys` (`Code`, `Module`, `Key`, `Value`, `Plugin`, `Status`) VALUES " . PHP_EOL;
            $lang_name = strtoupper($name) . " (" . strtoupper($info['Code']) . ")";

            $content = "-- Flynax Classifieds Software" . PHP_EOL
            . "-- Direction: " . strtoupper($info['Direction']) . PHP_EOL
            . "-- Export date: " . date('Y.m.d') . PHP_EOL
                . "-- version: {$config['rl_version']}" . PHP_EOL
                . "-- Language SQL Dump: {$lang_name}" . PHP_EOL
                . "-- https://www.flynax.com/flynax-software-eula.html" . PHP_EOL . PHP_EOL
                . "INSERT INTO `{prefix}languages` (`Code`, `Key`, `Status`, `Date_format`, `Direction`, `Locale`) VALUES ('{$info['Code']}', '{$info['Key']}', 'active', '{$info['Date_format']}', '{$info['Direction']}', '{$info['Locale']}');" . PHP_EOL . PHP_EOL;

            $content .= $insert;
            foreach ($phrases as $key => $value) {
                $value['Value'] = str_replace(array("'", "\r\n"), array("''", '\r\n'), $value['Value']);
                $tmp = <<<VS
('{$info['Code']}', '{$value['Module']}', '{$value['Key']}', '{$value['Value']}', '{$value['Plugin']}', '{$value['Status']}')
VS;

                if (count($phrases) - 1 == $key) {
                    $content .= $tmp . ';';
                } else {
                    if ($key % 500 == 0 && $key != 0) {
                        $content .= $tmp . ';' . PHP_EOL . $insert;
                    } else {
                        $content .= $tmp . ',' . PHP_EOL;
                    }
                }
            }
            
            /**
            * @since 4.7.1
            */
            $rlHook->load('phpApAjaxLangExportBeforeOutput', $content, $info, $phrases);

            header('Content-Type: application/download');
            header('Content-Disposition: attachment; filename=' . ucfirst($info['Key']) . '(' . strtoupper($info['Code']) . ').sql');
            echo $content;
            exit;
        } else {
            $alerts[] = $lang['lang_export_empty_alert'];
            $rlSmarty->assign_by_ref('alerts', $alerts);

            return false;
        }
    }
}
