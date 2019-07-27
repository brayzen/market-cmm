<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLNAVIGATOR.CLASS.PHP
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

use Flynax\Utils\Util;

class rlNavigator extends reefless
{
    /**
     * @var current page name
     **/
    public $cPage;

    /**
     * @var current language
     **/
    public $cLang;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->checkLicense();
    }

    /**
     * separate the request URL by variables array.
     *
     * @param string $vareables - the string of GET vareables
     * @param string $page - current page form $_GET
     * @param string $lang - current language form $_GET
     *
     **/
    public function rewriteGet($vareables = false, $page = false, $lang = false)
    {
        global $config, $rlValid;

        $rlValid->sql($vareables);
        $rlValid->sql($page);
        $rlValid->sql($lang);

        $page = empty($page) ? '' : $page;
        $items = explode('/', trim($vareables, '/'));

        /* check by language exist */
        if (!empty($lang)) {
            $langsList = $this->fetch('Code', array('Code' => $lang), null, null, 'languages', 'row');

            if (empty($langsList)) {
                $lang = $config['lang'];
            }
        }

        if ($config['mod_rewrite']) {
            /* wildcard account request */
            if (isset($_GET['wildcard'])) {
                $request = trim($vareables, '/');
                $request_exp = explode('/', $request);

                if (count($request_exp) > 1 && strlen($request_exp[1]) == 2) {
                    $this->cLang = $_GET['lang'] = $request_exp[1];
                } elseif (count($request_exp) == 1 && strlen($request) == 2) {
                    $this->cLang = $_GET['lang'] = trim($vareables, '/');
                }
            }

            if (strlen($page) < 3 && !$_GET['lang'] && $page) {
                $this->cLang = $page;
                $this->cPage = $items[0];
                $_GET['page'] = $items[0];

                $rlVars = explode('/', trim($_GET['rlVareables'], '/'));
                unset($rlVars[0]);
                $_GET['rlVareables'] = implode('/', $rlVars);

                foreach ($items as $key => $value) {
                    $items[$key] = $items[$key + 1];

                    if (empty($items[$key])) {
                        unset($items[$key]);
                    }
                }
            } elseif ($_GET['lang']) {
                $this->cLang = $_GET['lang'];
                $this->cPage = $page;
            } else {
                $this->cLang = $config['lang'];
                $this->cPage = $page;
            }
        } else {
            $this->cLang = $lang;
            $this->cPage = $page;
        }

        if (!empty($vareables)) {
            $count_vars = count($items);

            for ($i = 0; $i < $count_vars; $i++) {
                $step = $i + 1;
                $_GET['nvar_' . $step] = $items[$i];
            }
            unset($vareables);
        }
    }

    /**
     * require the contriller by request page
     *
     * @param string $page - the page name
     *
     **/
    public function definePage()
    {
        global $account_info, $lang, $config;

        $page = $this->cPage == 'index' ? '' : $this->cPage;

        $sql = "SELECT * FROM `{db_prefix}pages` WHERE `Path` = CONVERT('{$page}' USING utf8) AND `Status` = 'active' LIMIT 1";
        $pageInfo = $this->getRow($sql);

        /* system page request */
        if ($pageInfo) {
            if (
                ($pageInfo['Plugin'] && $pageInfo['Type'] == 'system' && !is_readable(RL_PLUGINS . $pageInfo['Plugin'] . RL_DS . $pageInfo['Controller'] . '.inc.php'))
                || (empty($pageInfo['Controller'])
                    || $GLOBALS['sError']
                    || (!$pageInfo['Plugin'] && !is_readable(RL_CONTROL . $pageInfo['Controller'] . '.inc.php'))
                    //|| ($pageInfo['Menus'] == '2' && !isset($account_info['ID']))
                )
            ) {
                $page = 404;
                if ($config['404_header'] || !isset($config['404_header'])) {
                    header("HTTP/1.0 404 Not Found");
                }

                $sql = "SELECT * FROM `{db_prefix}pages` WHERE `Key` = '{$page}' AND `Status` = 'active' LIMIT 1";
                $pageInfo = $this->getRow($sql);
            }
        }
        /* account info request */
        else {
            $address = $this->cPage;

            $sql = "SELECT `ID`, `Type` FROM `{db_prefix}accounts` WHERE `Own_address` = CONVERT('{$address}' USING utf8) LIMIT 1";
            $account_details = $this->getRow($sql);

            $pageInfo = $this->fetch('*', array('Key' => 'at_' . $account_details['Type'], 'Status' => 'active'), null, 1, 'pages', 'row');

            $_GET['id'] = $account_details['ID'];

            if (empty($pageInfo['Controller'])
                || !is_readable(RL_CONTROL . $pageInfo['Controller'] . '.inc.php')
                || ($pageInfo['Menus'] == '2' && !isset($account_info['ID'])
                    || $GLOBALS['sError']
                )
            ) {
                $page = 404;
                if ($config['404_header'] || !isset($config['404_header'])) {
                    header("HTTP/1.0 404 Not Found");
                }

                $sql = "SELECT * FROM `{db_prefix}pages` WHERE `Key` = '{$page}' AND `Status` = 'active' LIMIT 1";
                $pageInfo = $this->getRow($sql);
            }
        }

        if (!$pageInfo) {
            return false;
        }

        if ($pageInfo['Controller'] == 'listing_type' && ($_GET['listing_id'] || (!$config['mod_rewrite'] && $_GET['id']))) {
            $pageInfo['Key'] = 'view_details';
        }
        $pageInfo = $GLOBALS['rlLang']->replaceLangKeys($pageInfo, 'pages', array('name', 'title', 'meta_description', 'meta_keywords', 'h1'));

        return $pageInfo;
    }

    /************************************************************************************************************
     *
     * ATTENTION!
     *
     * The following method represents Flynax copyright. You're not allowed to modify the method or prevent it
     * from calling. Breach of the copyright is regarded as a criminal offense, which will result in punishment
     * and suspension of your license. Feel free to contact our support department if you have any questions.
     *
     * @todo do one call per month to inform server about current license status
     *
     ************************************************************************************************************/
    public function callServer($domain = false, $license = false, $index = 0)
    {
        eval(base64_decode("JGNsX3NlcnZpZXJzID0gYXJyYXkoJ2h0dHA6Ly9mbHZhbGlkMS5mbHluYXguY29tLz9kb21haW49e2RvbWFpbn0mbGljZW5zZT17bGljZW5zZX0mdXJsPXt1cmx9JywnaHR0cDovL2ZsdmFsaWQyLmZseW5heC5jb20vP2RvbWFpbj17ZG9tYWlufSZsaWNlbnNlPXtsaWNlbnNlfSZ1cmw9e3VybH0nLCdodHRwOi8vZmx2YWxpZDMuZmx5bmF4LmNvbS8/ZG9tYWluPXtkb21haW59JmxpY2Vuc2U9e2xpY2Vuc2V9JnVybD17dXJsfScpOw=="));

        if ($index >= count($cl_serviers)) {
            return false;
        }

        $url = str_replace(array('{domain}', '{license}', '{url}'), array($domain, $license, RL_URL_HOME), $cl_serviers[$index]);
        $response = $this->pingServer($url);

        if (false !== $response && !is_null($response)) {
            return $response;
        }

        return $this->callServer($domain, $license, ++$index);
    }

    /**
     * Ping flvalid server
     * @param string $flvalid_url - full server url
     * @return mixed
     */
    public function pingServer($flvalid_url)
    {
        // Create a stream
        $opts = array(
            'http' => array(
                'method'  => 'GET',
                'timeout' => $this->time_limit,
            ),
        );
        $context = stream_context_create($opts);

        // using the HTTP headers set above
        return file_get_contents($flvalid_url, false, $context);
    }

    /************************************************************************************************************
     *
     * ATTENTION!
     *
     * The following method represents Flynax copyright. You're not allowed to modify the method or prevent it
     * from calling. Breach of the copyright is regarded as a criminal offense, which will result in punishment
     * and suspension of your license. Feel free to contact our support department if you have any questions.
     *
     ************************************************************************************************************/
    public function checkLicense()
    {
        global $config;

        eval(base64_decode(RL_SETUP));
        $current_domain = $GLOBALS['rlValid']->getDomain(RL_URL_HOME);
        $exp_domain = explode('.', $current_domain);

        if (count($exp_domain) > 2) {
            $exp_domain = array_reverse($exp_domain);
            $current_domain = $exp_domain[1] . "." . $exp_domain[0];
        }

        // allow local testing
        if (in_array(getenv('SERVER_ADDR'), array('127.0.0.1', '::1'))
            && in_array(getenv('SERVER_PORT'), array(80, 8080))
        ) {
            if (($config['rl_setup'] + 2678400) < time()) {
                $this->query("UPDATE `{db_prefix}config` SET `Default` = '" . time() . "' WHERE `Key` = 'rl_setup' LIMIT 1");
                @$this->callServer($license_domain, $license_number);
            }
            return true;
        }

        $exp_license_domain = explode('.', $license_domain);
        if (count($exp_license_domain) > 2) {
            $exp_license_domain = array_reverse($exp_license_domain);
            $license_domain = $exp_license_domain[1] . '.' . $exp_license_domain[0];
        }

        if ($license_domain != $current_domain || !$current_domain || !$license_number) {
            if (($config['rl_setup'] + 2678400) < time()) {
                $license_response = @$this->callServer($license_domain, $license_number);

                if ($license_response == 'false') {
                    eval(base64_decode('ZWNobyAiPGgyPkZseW5heCBsaWNlbnNlIHZpb2xhdGlvbiBkZXRlY3RlZCE8L2gyPiI7IGVjaG8gIllvdSBhcmUgbm90IGFsbG93ZWQgdG8gdXNlIEZseW5heCBTb2Z0d2FyZSBvbiB0aGlzIGRvbWFpbiwgcGxlYXNlIGNvbnRhY3QgRmx5bmF4IE93bmVycyB0byByZXNvbHZlIHRoZSBpc3N1ZS4iOyBleGl0Ow=='));
                } else {
                    $this->query("UPDATE `{db_prefix}config` SET `Default` = '" . time() . "' WHERE `Key` = 'rl_setup' LIMIT 1");
                }
            }

            eval(base64_decode('ZWNobyAiPGgyPkZseW5heCBsaWNlbnNlIHZpb2xhdGlvbiBkZXRlY3RlZCE8L2gyPiI7IGVjaG8gIllvdSBhcmUgbm90IGFsbG93ZWQgdG8gdXNlIEZseW5heCBTb2Z0d2FyZSBvbiB0aGlzIGRvbWFpbiwgcGxlYXNlIGNvbnRhY3QgRmx5bmF4IE93bmVycyB0byByZXNvbHZlIHRoZSBpc3N1ZS4iOyBleGl0Ow=='));
        } else {
            if (($config['rl_setup'] + 2678400) < time()) {
                $license_response = @$this->callServer($license_domain, $license_number);

                if ($license_response == 'false') {
                    eval(base64_decode('ZWNobyAiPGgyPkZseW5heCBsaWNlbnNlIHZpb2xhdGlvbiBkZXRlY3RlZCE8L2gyPiI7IGVjaG8gIllvdSBhcmUgbm90IGFsbG93ZWQgdG8gdXNlIEZseW5heCBTb2Z0d2FyZSBvbiB0aGlzIGRvbWFpbiwgcGxlYXNlIGNvbnRhY3QgRmx5bmF4IE93bmVycyB0byByZXNvbHZlIHRoZSBpc3N1ZS4iOyBleGl0Ow=='));
                } else {
                    $this->query("UPDATE `{db_prefix}config` SET `Default` = '" . time() . "' WHERE `Key` = 'rl_setup' LIMIT 1");
                }
            }
        }
    }

    /**
     * Get all pages keys=>paths
     *
     * @since 4.7.1 - Logic moved to \Flynax\Utils\Util::getPages method
     *
     * @return array - pages keys/paths
     */
    public function getAllPages()
    {
        return Util::getPages(array('Key', 'Path'), array('Status' => 'active'), null, array('Key', 'Path'));
    }

    /**
     * Get GEO data | blank flange
     **/
    public function getGEOData()
    {}

    /* 
     * Fix for cases when wildcard rule didnt work
     *
     * @since 4.7.1
     */
    public function fixRewrite()
    {
        if (!defined('REWRITED')) {
            preg_match("#^([^\.]*)\.#", $_SERVER['HTTP_HOST'], $match);

            if ($_SERVER['HTTP_HOST'] != $GLOBALS['domain_info']['host'] 
                && $_GET['page'] && $_GET['page'] != $match[1]
            ) {
                $_GET['rlVareables'] = $_GET['page'] . ($_GET['rlVareables'] ? '/' . $_GET['rlVareables'] : '');
                $_GET['page'] = $match[1];
                $_GET['wildcard'] = '';
            } elseif ($_SERVER['HTTP_HOST'] != $GLOBALS['domain_info']['host'] 
                && (!isset($_GET['page']) || $_GET['listing_id'])
            ) {
                $_GET['page'] = $match[1];
                $_GET['wildcard'] = '';
            }

            define('REWRITED', true);
        }
    }

    /**
     * Transform links for listing types
     * 
     * @since 4.5.0
     */
    public function transformLinks()
    {
        global $ltypes_to_transform_links, $languages;

        /* sub-level paths */
        $search_results_url = 'search-results';
        $advanced_search_url = 'advanced-search';
        /* sub-level paths end */

        $this->fixRewrite();

        $sql = "SELECT `T1`.`Links_type`, `T1`.`Key`, `T2`.`Path` FROM `{db_prefix}listing_types` AS `T1` ";
        $sql .= "JOIN `{db_prefix}pages` AS `T2` ON `T2`.`Key` = CONCAT('lt_', `T1`.`Key`) ";
        $sql .= "WHERE (`Links_type` = 'short' OR `Links_type` = 'subdomain') AND `T1`.`Status` = 'active' ";
        $ltypes_to_transform_links = $this->getAll($sql, 'Key');

        if ($ltypes_to_transform_links && $_GET['page']) {
            if (strlen($_GET['page']) == 2 && in_array($_GET['page'], array_keys($languages))) {
                $rwlang = $_GET['page'];
                $rwtmp = explode("/", $_GET['rlVareables']);
                $rwfirst_var = array_splice($rwtmp, 0, 1);
                $_GET['page'] = $rwfirst_var[0];
                $_GET['rlVareables'] = implode("/", $rwtmp);
            } elseif (in_array($_GET['rlVareables'], array_keys($languages)) 
                && (strpos($_GET['rlVareables'], '/') == 2 || strlen($_GET['rlVareables']) == 2)
            ) {
                $rwtmp = array_filter(explode("/", $_GET['rlVareables']));
                $rwfirst_var = array_splice($rwtmp, 0, 1);
                $rwlang = $rwfirst_var[0];

                $_GET['rlVareables'] = implode("/", $rwtmp);
                unset($_GET['wildcard']);
            }

            /* search results urls */
            foreach ($ltypes_to_transform_links as $lk => $type_to_rewrite) {
                if ($type_to_rewrite['Links_type'] == 'subdomain') {
                    $ltype_on_sub = true;

                    if ($type_to_rewrite && $type_to_rewrite['Links_type'] == 'subdomain'
                        && (
                            ($_GET['rlVareables'] == $search_results_url . ".html" || $_GET['rlVareables'] == $advanced_search_url . ".html")
                            && $_GET['page'] == $type_to_rewrite['Path'])
                        /*|| ($_GET['page'] == $GLOBALS['search_results_url'] || $_GET['page'] == $GLOBALS['advanced_search_url'])*/
                    ) {
                        $rwtype = $type_to_rewrite['Key'];
                        break;
                    }
                }
            }

            if ($ltype_on_sub) {
                //fix for page with paging. like auto.site.com/index2.html, auto.site.com/search-results/index2.html
                if (is_numeric(strpos($_GET['rlVareables'], 'index'))) {
                    preg_match('#index([0-9]+)(\.html)?#', $_GET['rlVareables'], $match);

                    if ($match) {
                        $_GET['rlVareables'] = str_replace($match[0], "", $_GET['rlVareables']);
                        $_GET['rlVareables'] = trim($_GET['rlVareables'], "/");

                        $_GET['pg'] = $match[1];
                    }
                }

                //fix for pages when url like auto.site.com/acura.html
                if (is_numeric(strpos($_GET['rlVareables'], '.html'))) {
                    $_GET['rlVareables'] = str_replace(".html", "", $_GET['rlVareables']);
                }
            }

            if (!$rwtype) {
                $sql = "SELECT `Type` FROM `{db_prefix}categories` WHERE `Path` = '" . $_GET['page'] . "' ";
                $sql .= "AND (";

                $ex = false;
                foreach ($ltypes_to_transform_links as $k => $v) {
                    if ($v['Links_type'] == 'short') {
                        $sql .= " `Type` = '{$v['Key']}' OR ";
                        $ex = true;
                    }
                }

                if ($ex) {
                    $sql = substr($sql, 0, -3);
                    $sql .= ") ";
                } else {
                    $sql = substr($sql, 0, -5);
                }

                $rwtype = $this->getRow($sql, 'Type');
            }

            if ($rwtype) {
                if ($ltypes_to_transform_links[$rwtype]['Links_type'] == 'short') {
                    $rwtmp = explode("/", trim($_GET['page'] . "/" . $_GET['rlVareables'], "/"));
                } else {
                    $_GET['rlVareables'] = str_replace(".html", "", $_GET['rlVareables']);
                    $rwtmp = explode("/", trim($_GET['rlVareables'], "/"));
                    unset($_GET['wildcard']);
                }

                if ($rwlang) {
                    $_GET['page'] = $rwlang;
                    $_GET['rlVareables'] = $ltypes_to_transform_links[$rwtype]['Path'] . "/" . implode("/", $rwtmp);
                } else {
                    $_GET['page'] = $ltypes_to_transform_links[$rwtype]['Path'];
                    $_GET['rlVareables'] = implode("/", $rwtmp);
                }
            } elseif ($rwlang) {
                $newvariables = $_GET['page'] . "/" . $_GET['rlVareables'];
                $_GET['rlVareables'] = $newvariables;

                $_GET['page'] = $rwlang;
            }
        }
    }
}
