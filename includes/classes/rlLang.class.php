<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLLANG.CLASS.PHP
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

class rlLang extends reefless
{
    /**
     * get language values by keywords
     *
     * @param string/array $data - data for replacing
     * @param string $module - system module
     * @param string/array $fields - fields names for execute
     * @param string $langCode  - language code, possible values: any lang code or * (like all languages)
     * @param string $side      - language side, possible values: frontEnd or admin
     * @param string $status    - language phrases status
     *
     * @return languages values instead of languages keys
     **/
    public function replaceLangKeys($data = null, $module = '', $fields = null, $langCode = RL_LANG_CODE, $side = 'frontEnd', $status = 'active')
    {
        if (!$data) {
            return array();
        }

        $this->setTable('lang_keys');

        if (is_array($data)) {
            if ($side == 'frontEnd' || ($side == 'admin' && defined('REALM') && REALM == 'admin')) {
                if (!$GLOBALS['lang']) {
                    $GLOBALS['lang'] = $this->getLangBySide($side, $langCode, $status);
                }

                $lang_values = &$GLOBALS['lang'];
            } else {
                $lang_values = $this->getLangBySide($side, $langCode, $status);
            }

            foreach ($data as &$item) {
                if (is_string($fields)) {
                    $fields_tmp = $fields;
                    $fields = array();
                    $fields[0] = $fields_tmp;

                    unset($fields_tmp);
                }

                foreach ($fields as &$field) {
                    if (is_array($item)) {
                        $item[$field] = $lang_values[$module . '+' . $field . '+' . $item['Key']];
                    } else {
                        $data[$field] = $lang_values[$module . '+' . $field . '+' . $data['Key']];
                    }
                }
            }
        } elseif ($data) {
            return $this->getOne('Value', "`Code` = '{$langCode}' AND `Key` = '{$data}'", 'lang_keys');
        }

        return $data;
    }

    /**
     * Select all languages value by module
     *
     * @param string $module   - Languages values module: frontEnd, admin, ext, formats, email_tpl
     * @param string $langCode - Language code
     * @param string $status   - Language status
     *
     * @return - Languages values instead of languages keys
     */
    public function getLangBySide($module = 'frontEnd', $langCode = RL_LANG_CODE, $status = 'active')
    {
        global $rlDb;

        $rlDb->setTable('lang_keys');

        $options = "WHERE (`Module` = '{$module}' ";
        $options .= in_array($module, array('admin', 'frontEnd')) ? "OR `Module` = 'common'" : '';
        $options .= ") ";
        $options .= $langCode != '*' ? "AND `Code` = '{$langCode}' " : '';
        $options .= $status != 'all' ? "AND `Status` = '{$status}'" : '';

        $rlDb->outputRowsMap = array('Key', 'Value');

        $replace_pattern = '/(<script.*<\/script>)/sm';

        foreach ($rlDb->fetch(array('Key', 'Value'), null, $options) as $key => $phrase_tmp) {
            $phrase = $phrase_tmp;
            $js = array();

            if (false !== strpos($phrase, "<script")) {
                preg_match($replace_pattern, $phrase, $js);
                $phrase  = preg_replace($replace_pattern, '{js-script}', $phrase);
            }

            // Replace quotes
            if (false !== strpos($phrase, "'")) {
                $phrase = preg_replace('/(\')(?=[^>]*(<|$))/', '&rsquo;', $phrase);
            }

            if (false !== strpos($phrase, '"')) {
                $phrase = preg_replace('/(")(?=[^>]*(<|$))/', '&quot;', $phrase);
            }

            if ($js) {
                $phrase = str_replace('{js-script}', $js[0], $phrase);
            }

            // Replace NL in boxes content
            if (0 === strpos($key, 'blocks+name+')) {
                $phrase = nl2br($phrase);
            }

            $phrases[$key] = $phrase;
        }

        // Set home page title as site name
        $GLOBALS['config']['site_name'] = $phrases['pages+title+home'];

        return $phrases;
    }

    /**
     * define site language
     *
     * @param sting $language - language code
     *
     * @return set define site language
     **/
    public function defineLanguage($language = false)
    {
        global $config, $languages;

        /* fix for links with wrong language in url */
        if ($GLOBALS['rlNavigator']->cLang && $GLOBALS['rlNavigator']->cLang != $config['lang']) {
            if (!$languages[$GLOBALS['rlNavigator']->cLang]) {
                $GLOBALS['sError'] = true;
            }
        }
        /* fix for links with wrong language in url end */

        $count = count($languages);

        $cookie_lang = defined('REALM') ? "rl_lang_" . REALM : "rl_lang_front";

        if ($count > 1) {
            if (!empty($language)) {
                $GLOBALS['rlValid']->sql($language);
                $this->createCookie($cookie_lang, $language, time() + ($config['expire_languages'] * 86400));
                if ($languages[$language]) {
                    define('RL_LANG_CODE', $language);
                } else {
                    define('RL_LANG_CODE', $config['lang']);
                }
            } elseif (isset($_COOKIE[$cookie_lang])) {
                $GLOBALS['rlValid']->sql($_COOKIE[$cookie_lang]);

                if ($languages[$_COOKIE[$cookie_lang]]) {
                    define('RL_LANG_CODE', $_COOKIE[$cookie_lang]);
                } else {
                    define('RL_LANG_CODE', $config['lang']);
                }
            } else {
                define('RL_LANG_CODE', $config['lang']);
            }
        } else {
            define('RL_LANG_CODE', $config['lang']);
        }

        define('RL_LANG_DIR', $languages[RL_LANG_CODE]['Direction']);
    }

    /**
     * define site language (for EXT)
     *
     * @package EXT JS
     *
     * @return set define site language
     **/
    public function extDefineLanguage()
    {
        global $config;

        $cookie_lang = defined('REALM') ? "rl_lang_" . REALM : "rl_lang_front";

        if (isset($_COOKIE[$cookie_lang])) {
            $GLOBALS['rlValid']->sql($_COOKIE[$cookie_lang]);
            $user_lang = $this->fetch(array('ID', 'Date_format'), array('Status' => 'active', 'Code' => $_COOKIE[$cookie_lang]), null, null, 'languages', 'row');

            define('RL_DATE_FORMAT', $user_lang['Date_format']);

            if (!empty($user_lang)) {
                define('RL_LANG_CODE', $_COOKIE[$cookie_lang]);
            } else {
                define('RL_LANG_CODE', $config['lang']);
            }
        } else {
            $user_lang = $this->fetch(array('Date_format'), array('Status' => 'active', 'Code' => $config['lang']), null, null, 'languages', 'row');
            define('RL_DATE_FORMAT', $user_lang['Date_format']);

            define('RL_LANG_CODE', $config['lang']);
        }
    }

    /**
     * Get system available languages
     *
     * @param sting $status - languages status
     * @return array - languages list
     **/
    public function getLanguagesList($status = 'active')
    {
        if (empty($GLOBALS['languages']) || $status == 'all') {
            $where = array('Status' => $status);
            $options = null;

            if ($status == 'all') {
                $where = null;
                $options = "WHERE `Status` <> 'trash'";
            }

            $_order_row = 'Code`, IF(`Code` = "' . $GLOBALS['config']['lang'] . '", 1, 0) AS `Order';
            $_rows = array($_order_row, 'Key', 'Direction', 'Locale', 'Date_format', 'Status');

            $this->setTable('languages');
            $this->outputRowsMap = 'Code';
            $languages = $this->fetch($_rows, $where, $options . ' ORDER BY `Order` DESC');

            foreach ($languages as &$language) {
                $language['name'] = $this->getPhrase(array(
                    'key'  => 'languages+name+' . $language['Key'],
                    'lang' => $language['Code'],
                ));
            }
        }
        return $languages;
    }

    /**
     * modify langs list for fronEnd
     *
     * @param sting $langList - languages status
     *
     * @return array - modified languages list
     **/
    public function modifyLanguagesList(&$langList)
    {
        global $page_info;

        foreach ($langList as $key => $value) {
            if ($langList[$key]['Code'] == $GLOBALS['config']['lang'] && $page_info['Controller'] != 'home') {
                $langList[$key]['dCode'] = "";
            } else {
                $langList[$key]['dCode'] = $langList[$key]['Code'] . "/";
            }

            if ($langList[$key]['Code'] == RL_LANG_CODE) {
                define('RL_DATE_FORMAT', $langList[$key]['Date_format']);
            }
        }
    }

    /**
     * @since 4.5.0
     *
     * redirect user to preffered language (by browser) for the first visit
     *
     * @param array $available_languages
     **/
    public function preferedLanguageRedirect($available_languages)
    {
        global $config;

        if ($_COOKIE['language_detected'] || $_GET['page'] || !$config['preffered_lang_redirect'] || IS_BOT) {
            return false;
        }

        if ($_COOKIE['rl_lang_front'] && in_array($_COOKIE['rl_lang_front'], array_keys($available_languages))) {
            $redirect_lang = $_COOKIE['rl_lang_front'];
        } else {
            foreach ($available_languages as $k => $item) {
                $available_codes[] = strtolower($item['Code']);
                $return_codes[strtolower($item['Code'])] = $item['Code'];
            }

            $http_accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';

            preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
                "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
                $http_accept_language, $hits, PREG_SET_ORDER);

            $bestlang = $available_codes[0];
            $bestqval = 0;

            foreach ($hits as $arr) {
                $langprefix = strtolower($arr[1]);
                if (!empty($arr[3])) {
                    $langrange = strtolower($arr[3]);
                    $language = $langprefix . "-" . $langrange;
                } else {
                    $language = $langprefix;
                }

                $qvalue = 1.0;
                if (!empty($arr[5])) {
                    $qvalue = floatval($arr[5]);
                }

                if (in_array($language, $available_codes) && ($qvalue > $bestqval)) {
                    $bestlang = $language;
                    $bestqval = $qvalue;
                } else if (in_array($langprefix, $available_codes) && (($qvalue * 0.9) > $bestqval)) {
                    $bestlang = $langprefix;
                    $bestqval = $qvalue * 0.9;
                }
            }

            $redirect_lang = $return_codes[$bestlang] ?: $bestlang;
        }

        $expire_days = 1;
        $this->createCookie('language_detected', true, time() + ($expire_days) * 86400);

        if ($redirect_lang != RL_LANG_CODE) {
            $home_url = RL_URL_HOME;
            if ($GLOBALS['config']['lang'] != $redirect_lang && $GLOBALS['config']['mod_rewrite']) {
                $home_url .= $redirect_lang . '/';
            }

            $this->redirect(null, $home_url, 302);
        }
    }

    /**
     * Get phrase
     *
     * Get phrases by key and language code (optional),
     * It's also possible to pass params separately i.e. getPhrase($key, $lang_code, $assign, $dbcheck);
     *
     * @since 4.5.0
     *
     * @param  string $params['key']      - Phrase key
     * @param  string $params['lang']     - ISO language code (optional)
     * @param  string $params['assign']   - Assign to variable
     * @param  string $params['db_check'] - Force database check
     * @return string                     - Phrase
     */
    public function getPhrase($params)
    {
        if (!is_array($params)) {
            $tmp = array();
            list($tmp['key'], $tmp['lang'], $tmp['assign'], $tmp['db_check']) = func_get_args();
            $tmp['assign'] = false;
            $params = $tmp;
        }

        if (!$params['key']) {
            return 'No phrase key specified';
        }

        $lang_code = $params['lang'] ?: RL_LANG_CODE;
        $set_key = $params['key'];
        $phrase = false;

        // phrase by requested languahe
        if ($params['lang'] && RL_LANG_CODE != $params['lang']) {
            $params['db_check'] = true;
            $set_key = $params['lang'] . '_' . $set_key;
        }

        // lookup phrase
        if ($GLOBALS['lang'][$set_key]) {
            $phrase = $GLOBALS['lang'][$set_key];
        } elseif (!$GLOBALS['lang'][$set_key] && $params['db_check']) {
            $_where = "`Key` = '{$params['key']}' AND `Code` = '{$lang_code}'";
            $phrase = $GLOBALS['rlDb']->getOne('Value', $_where, 'lang_keys');

            if ($GLOBALS['lang']) {
                $GLOBALS['lang'][$set_key] = $phrase;
            }
        } else {
            $phrase = 'No phrase found by "' . $params['key'] . '" key';
        }

        $GLOBALS['rlHook']->load('getPhrase', $params, $phrase);

        // assign phrase to the requested smarty variable
        if ($params['assign'] && is_object($GLOBALS['rlSmarty'])) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $phrase);
        }
        // return variable
        else {
            return $phrase;
        }
    }
}
