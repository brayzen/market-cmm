<?php

use Flynax\Utils\Util;
use Flynax\Utils\Valid;

class rlGeoFilter
{
    public $geo_format      = array();
    public $geo_filter_data = array();
    public $cookieTime      = 0;
    public $detailsPage     = false;

    /**
     * Current listing location data
     * @var array
     */
    public $listing_location_data = array();

    public $debug = false;

    public function __construct()
    {
        global $config;

        $sql = "
            SELECT `T1`.`ID` AS `Format_ID`, `T1`.`Order_type`, `T2`.*
            FROM `{db_prefix}data_formats` AS `T1`
            JOIN `{db_prefix}multi_formats` AS `T2` ON `T2`.`Key` = `T1`.`Key`
            WHERE `T2`.`Geo_filter` = '1' AND `T2`.`Status` = 'active'
        ";

        $this->geo_format = $GLOBALS['rlDb']->getRow($sql);

        if (isset($_GET['gf_debug'])) {
            $this->debug = true;
            unset($_GET['gf_debug']);
        }

        $exp_days = (int) $config['mf_geofilter_expiration'];
        $this->cookieTime = strtotime(
            '+' . ($exp_days > 0
            ? $exp_days
            : 90) . ' days'
        );

        if (($config['mod_rewrite'] && $_GET['listing_id'])
            || (!$config['mod_rewrite'] && $_GET['id'])
        ) {
            $this->detailsPage = true;
        }
    }

    /**
     * @hook init
     * @since 2.0.0
     */
    public function hookInit()
    {
        if ($this->geo_format && !defined('AJAX_FILE') && !defined('CRON_FILE')) {
            $this->rewriteGet(); // rewrite first
        }

        if (defined('AJAX_FILE') && in_array($_REQUEST['mode'], array('manageListing'))) {
            $this->appliedLocation();
            $GLOBALS['rlSmarty']->assign_by_ref('geo_filter_data', $this->geo_filter_data);
        }
    }

    /**
     * @hook phpBeforeLoginValidation
     * @since 2.0.0
     */
    public function hookPhpBeforeLoginValidation()
    {
        $this->init();
    }

    /**
     * @hook sitemapGetListingsBeforeGetAll
     * @since 2.0.0
     */
    public function hookSitemapGetListingsBeforeGetAll()
    {
        global $config;

        if (!$config['mod_rewrite'] || !$config['mf_listing_geo_urls']) {
            return;
        }

        $this->prepareLocationFields();
    }

    /**
     * Fix Rewrite for cases when wildcard rule doesn't work
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
     * Rewrite Get
     *
     * check if there is location in url,
     * if there is location in the url:
     * - save applied location to the >geo_filter_data['applied_location']
     * - remove from GET to allow system define pages and variables as it works by default
     * - GET array after the function should NOT contain any location variables
     */
    public function rewriteGet()
    {
        global $rlDb, $config;

        $this->fixRewrite();

        if ($this->debug) {
            echo '<pre>';
            print_r($_GET);
            echo '</pre>';
        }

        if (isset($_GET['reset_location'])) {
            $this->resetLocation();
            return false;
        }

        // Rewrite rule corrections
        if (isset($_GET['wildcard']) && $_GET['rlVareables'] && strpos($_GET['rlVareables'], '.html')) {
            $_GET['rlVareables'] = str_replace('.html', '', $_GET['rlVareables']);
        }

        if (isset($_GET['wildcard']) && $_GET['wildcard'] == '') {
            unset($_GET['wildcard']);
        }

        if (strlen($_GET['page']) == 2) {
            $rw_lang = $_GET['page'];

            $tmp = explode("/", $_GET['rlVareables']);
            $page = array_splice($tmp, 0, 1)[0];

            if ($rlDb->getOne("Key", "`Path` = '" . $page . "' AND `Status` = 'active'", 'data_formats')) {
                $_GET['page'] = $page;
                $_GET['rlVareables'] = implode("/", $tmp);
            }
        } elseif (substr($_GET['rlVareables'], 2, 1) == '/') {
            $tmp = explode("/", $_GET['rlVareables']);
            $page = array_splice($tmp, 0, 1)[0];

            if ($rlDb->getOne("Code", "`Code` = '" . $page . "'", "languages")) {
                $rw_lang = $page;
                $_GET['rlVareables'] = implode("/", $tmp);
            }
        }

        $get_vars = array();
        if ($_GET['page']) {
            $get_vars[] = $_GET['page'];

            if ($_GET['rlVareables']) {
                foreach (explode('/', $_GET['rlVareables']) as $k => $get_var) {
                    $get_vars[] = $get_var;
                }
            }
        }

        if ($get_vars) {
            $this->geo_filter_data['applied_location'] = $this->prepareGetVars($get_vars);

            if ($this->geo_filter_data['applied_location']) {
                $this->saveLocation($this->geo_filter_data['applied_location']);

                foreach (explode('/', $this->geo_filter_data['applied_location']['Path']) as $k => $v) {
                    $get_pos = array_search($v, $get_vars);
                    unset($get_vars[$get_pos]);
                }

                unset($_GET['page']);
                unset($_GET['rlVareables']);

                if ($get_vars || $rw_lang) {
                    if ($rw_lang) {
                        array_unshift($get_vars, $rw_lang);
                    }

                    foreach (array_values($get_vars) as $k => $v) {
                        if ($k == 0) {
                            $_GET['page'] = $v;
                        } elseif ($k > 0 && strlen($v) > 2) {
                            $_GET['rlVareables'] .= $v . '/';
                        }
                    }

                    if ($_GET['rlVareables']) {
                        $_GET['rlVareables'] = trim($_GET['rlVareables'], '/');
                    }
                }
            } elseif ($rw_lang && strlen($_GET['page']) > 2) {
                $_GET['rlVareables'] = $rw_lang . '/' . $_GET['rlVareables'];
            }
        } elseif ($rw_lang) {
            $_GET['page'] = $rw_lang;
        }

        // It's not possible to affect applied geo filter from Listing Details URL
        if ($_GET['listing_id']) {
            unset($this->geo_filter_data['applied_location']);
        }

        if ($this->debug) {
            echo '<pre> after rewrite';
            print_r($_GET);
            echo '</pre>';
        }
    }

    /**
     * Prepare Get Variables - recursive function
     *
     * Check if there is location in url, remove from given string and return last applied location
     *
     * @param  string get_vars   - all get variables string (including sub-domain)
     * @return string            - applied location array (from data_formats table)
     */
    public function prepareGetVars($get_vars)
    {
        global $rlDb, $config;

        if (!$get_vars) {
            return false;
        }

        if ($config['mf_geo_subdomains']) {
            $_GET['page'] = Valid::escape($_GET['page']);

            if ($config['mf_geo_subdomains_type'] == 'combined') {
                $where = "REPLACE(`Path`, '/', '-') = '{$_GET['page']}'";
            } elseif ($config['mf_geo_subdomains_type'] == 'unique') {
                $where = "`Path`= '{$_GET['page']}'";
            }

            if ($where) {
                $sql = "SELECT * FROM `{db_prefix}data_formats` ";
                $sql .= "WHERE {$where} ";
                $sql .= "AND `Status` = 'active' ";

                return $rlDb->getRow($sql);
            }
        }

        $check = implode($get_vars, '/');

        $data_entry = $GLOBALS['rlDb']->fetch("*", array("Path" => $check), null, null, "data_formats", "row");

        if ($data_entry) {
            return $data_entry;
        } else {
            $get_vars = array_slice($get_vars, 0, -1);
            return $this->prepareGetVars($get_vars);
        }
    }

    /**
     * Initialization
     *
     * Makes necessary variable preparation for the geo filtering
     */
    public function init()
    {
        global $domain_info;

        $this->appliedLocation();

        $this->geo_filter_data['clean_url'] = $this->cleanUrl();

        $clean_url = $this->cleanUrl($domain_info['scheme'] . '://' . $domain_info['host'], $domain_info['path']);
        $clean_url = str_replace('[geo_url]/', '', $clean_url);
        $this->geo_filter_data['base_url_with_subdomain'] = $this->buildGeoLink($this->geo_filter_data['applied_location'], $clean_url, true);
        
        $this->appliedLocation2();

        // Workaround for old version conditions
        $GLOBALS['geo_filter_data'] = $this->geo_filter_data;
        $GLOBALS['geo_filter_data']['geo_url'] = $this->geo_filter_data['applied_location']['Path'];

        $GLOBALS['rlSmarty']->assign_by_ref('geo_filter_data', $this->geo_filter_data);
    }

    /**
     * Applied Location
     *
     * Expand applied location array (that was defined in RewriteGet function)
     */
    private function appliedLocation()
    {
        global $rlDb, $config;

        $this->geo_filter_data['filtering_pages']    = $config['mf_filtering_pages']
        ? explode(',', $config['mf_filtering_pages'])
        : [];

        $this->geo_filter_data['location_url_pages'] = $config['mf_location_url_pages']
        ? explode(',', $config['mf_location_url_pages'])
        : [];

        // Set/get session location
        $this->geo_filter_data['from_session'] = false;
        if ($this->geo_filter_data['applied_location']) {
            $_SESSION['geo_filter_location'] = $this->geo_filter_data['applied_location'];
        } elseif ($_SESSION['geo_filter_location']) {
            $this->geo_filter_data['applied_location'] = $_SESSION['geo_filter_location'];
            $this->geo_filter_data['from_session'] = true;
        } elseif (isset($_COOKIE['mf_geo_location']) && $_COOKIE['mf_geo_location'] != 'reset') {
            $this->geo_filter_data['applied_location'] = json_decode($_COOKIE['mf_geo_location'], true);
            $this->geo_filter_data['from_session'] = true;
        }

        // Get stack of locations from applied location and up to the top level
        $applied_location = $this->geo_filter_data['applied_location'];

        if ($applied_location['Key']) {
            $applied_location_keys = array();
            $applied_location_keys[] = $applied_location['Key'];

            $parent_id = $applied_location['Parent_ID'];

            while ($parent_id != $this->geo_format['Format_ID']) {
                $ap = $rlDb->fetch(
                    array('Key', 'Parent_ID'),
                    array('ID' => $parent_id, 'Status' => 'active'),
                    null, null, 'data_formats', 'row'
                );

                if ($ap) {
                    $applied_location_keys[] = $ap['Key'];
                    $parent_id = $ap['Parent_ID'];
                } else {
                    $this->resetLocation();
                    unset($this->geo_filter_data['applied_location'], $applied_location_keys);

                    $GLOBALS['rlDebug']->logger("MultiField: unable to find format with ID: {$parent_id}");
                    break;
                }
            }

            $this->geo_filter_data['location_keys'] = array_reverse($applied_location_keys);
        }

        $this->prepareLocationFields();
    }

    /**
     * Applied Location
     *
     * Expand applied location array (2nd part) (that was defined in RewriteGet function)
     * Defines Parent paths (clear location links),
     * Its not possible to do in function 1 because clean_urls are defined only now
     */
    private function appliedLocation2()
    {
        $prev_path = '';

        foreach ($this->geo_filter_data['location_keys'] as $location) {
            $sql = "SELECT `T1`.`Key`, `T2`.`Value` AS `name`, `T1`.`Path` ";
            $sql .= "FROM `{db_prefix}data_formats` as `T1` ";
            $sql .= "JOIN `{db_prefix}lang_keys` AS `T2` ";
            $sql .= "ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`) AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
            $sql .= "WHERE `T1`.`Key` = '{$location}'";

            $item_info = $GLOBALS['rlDb']->getRow($sql);

            if ($item_info) {
                $item_info['Parent_link'] = $this->buildGeoLink(array('Path' => $prev_path));
                $item_info['Parent_path'] = $prev_path;

                if (!$prev_path) {
                    $item_info['Parent_link'] .= '?reset_location';
                }

                $this->geo_filter_data['location'][] = $item_info;
                $prev_path = $item_info['Path'];
            } else {
                $this->resetLocation();
                unset(
                    $this->geo_filter_data['applied_location'],
                    $this->geo_filter_data['location_keys']
                );
            }
        }
    }

    /**
     * Prepare location listing and account fields
     * @since 2.0.0
     */
    public function prepareLocationFields()
    {
        global $rlDb;

        if (!$this->geo_format) {
            return;
        }

        $listing_fields = $rlDb->fetch(
            array('Key'),
            array(
                'Condition' => $this->geo_format['Key'],
                'Status'    => 'active',
            ),
            "AND `Key` NOT LIKE 'citizenship%' ORDER BY `Key`",
            null,
            'listing_fields'
        );

        if ($listing_fields) {
            foreach ($listing_fields as $k => $field) {
                if ($value = $this->geo_filter_data['location_keys'][$k]) {
                    $this->geo_filter_data['location_listing_fields'][$field['Key']] = $value;
                } else {
                    $this->geo_filter_data['location_listing_fields'][$field['Key']] = '';
                }
            }
        }

        $account_fields = $rlDb->fetch(
            array('Key'),
            array(
                'Condition' => $this->geo_format['Key'],
                'Status'    => 'active',
            ),
            "AND `Key` NOT LIKE 'citizenship%' ORDER BY `Key`",
            null,
            'account_fields'
        );

        if ($account_fields) {
            foreach ($account_fields as $k => $field) {
                if ($value = $this->geo_filter_data['location_keys'][$k]) {
                    $this->geo_filter_data['location_account_fields'][$field['Key']] = $value;
                } else {
                    $this->geo_filter_data['location_account_fields'][$field['Key']] = '';
                }
            }
        }
    }

    /**
     * Clean Url
     *
     * build 'clean url' that is necessary for geo links building
     * it looks like  domain.com/[geo_url]/page.html
     * or [geo_sub].domain.com/[geo_url]/page.html when location on subdomains enabled
     *
     * @param string $req_host - request host (optional)
     * @param string $req_url  - request url (optional)
     *
     * @return string clean_url
     */
    public function cleanUrl($req_host = false, $req_url = false, $lang_code = false)
    {
        global $rlDb;

        $scheme = $GLOBALS['domain_info']['scheme'] . '://';
        $req_host = $req_host ?: $scheme . $_SERVER['HTTP_HOST'];
        $req_url = $req_url ?: str_replace('?reset_location', '', $_SERVER['REQUEST_URI']);

        // Move RL_DIR from URI to HOST
        if (RL_DIR) {
            $req_host .= '/' . RL_DIR;

            if (substr($req_url, 0, strlen(RL_DIR)) == '/' . rtrim(RL_DIR, '/')) {
                $req_url = substr($req_url, strlen(RL_DIR));
            }
        }

        // Move lang code from URI to HOST
        preg_match('#^/([a-z]{2})/#', $req_url, $matches);
        if ($matches[1]) {
            if ($GLOBALS['languages'][$matches[1]]) {
                $glang = $matches[1];
            }
        }

        if ($glang) {
            $req_host .= '/' . $glang;
            $req_url = substr($req_url, 3);
        } elseif ($lang_code) {
            $req_host .= '/' . $lang_code;
        }

        // Clear applied location from url
        if ($this->geo_filter_data['applied_location']['Path'] && !$this->geo_filter_data['from_session']) {
            $tmp = explode('/', $this->geo_filter_data['applied_location']['Path']);

            if ($GLOBALS['config']['mf_geo_subdomains']) {
                $req_host = str_replace(
                    $tmp[0] . '.',
                    '',
                    $req_host);
                $tmp = array_slice($tmp, 1);
            }

            $geo_url = ($tmp ? implode('/', $tmp) : '');

            if ($geo_url) {
                $req_url = str_replace($geo_url, '', $req_url);
            }
        }

        if ($GLOBALS['config']['mf_geo_subdomains']) {
            $req_host = $GLOBALS['domain_info']['scheme'] . '://[geo_sub].' . $GLOBALS['domain_info']['host'];

            if (RL_DIR) {
                $req_host .= '/' . RL_DIR;
            }

            if ($glang) {
                $req_host .= '/' . $glang;
            } elseif ($lang_code) {
                $req_host .= '/' . $lang_code;
            }
        }

        $clean_url = $req_host . '/[geo_url]' . $req_url;
        $clean_url = $this->fixUrl($clean_url, true);

        return $clean_url;
    }

    /**
     * Build Geo Link
     *
     * Builds link for any item in the geo filter
     * it operates with clean url and passed item (location item from data_formats table or similar)
     *
     * @param array  $item       - request host (optional)
     * @param string $clean_url  - clean url (optional), if param ommitted geo_filter_data['clean_url'] will be used
     * @param bool   $nolocfix   - a trigger says that link will not be used in smarty Fetch.
     *                             LocFix is addition to avoid links to be replaced in SmartyFetchHook
     *
     * @return string clean_url
     */
    public function buildGeoLink($item = false, $clean_url = false, $nolocfix = false)
    {
        $clean_url = $clean_url ?: $this->geo_filter_data['clean_url'];

        if ($GLOBALS['config']['mf_geo_subdomains'] && $item['Path']) {
            switch ($GLOBALS['config']['mf_geo_subdomains_type']) {
                case 'unique':
                    $item['Subdomain_path'] = $item['Path'];
                    $item['Path'] = '';
                    break;

                case 'combined':
                    $item['Subdomain_path'] = $GLOBALS['rlValid']->str2path($item['Path']);
                    $item['Path'] = '';
                    break;

                case 'mixed':
                    $tmp = explode("/", $item['Path']);
                    $item['Subdomain_path'] = array_splice($tmp, 0, 1)[0];
                    $item['Path'] = $tmp ? implode("/", $tmp) : '';
                    break;
            }
        }

        /**
         * Fix duplicate geo location issue
         * @todo remove this condition once hreflang tags issue in core is reworked
         */
        if ($item['Path'] && strpos($clean_url, $item['Path'])) {
            $clean_url = str_replace($item['Path'], '', $clean_url);
        }

        $link = str_replace(array('[geo_url]', '[geo_sub]'),
            array($item['Path'], $item['Subdomain_path']),
            $clean_url
        );

        $link = $this->fixUrl($link, $nolocfix, true);

        return $link;
    }

    /**
     * Fix URL
     *
     * Make url valid - remove duplicated slashes and other things may appear after variables replacing
     *
     * @since 2.0.1 - Added $wwwfix parameter
     *
     * @param string $link       - url to correct
     * @param bool   $nolocfix   - a trigger says that link will not be used in smarty Fetch.
     *                             LocFix is addition to avoid links to be replaced in SmartyFetchHook
     * @param bool   $wwwfix     - remove ".www" part from the host
     *
     * @return string $link      - corrected url
     */
    private function fixUrl($link, $nolocfix = false, $wwwfix = false)
    {
        $locfix = $nolocfix ? '' : 'locfix';

        $parsed = parse_url($link);

        $parsed['host'] = ltrim($parsed['host'], '.');
        $parsed['host'] = $wwwfix ? str_replace('.www', '', $parsed['host']) : $parsed['host'];

        $parsed['path'] = preg_replace('/(\/+)/', '/', $parsed['path']);

        $link = $parsed['scheme'] . '://' . $parsed['host'] . $locfix . $parsed['path'];

        return $link;
    }

    /**
     * @hook specialBlock
     * @since 2.0.0
     */
    public function hookSpecialBlock()
    {
        if ($this->geo_format) {
            $this->boxData();
        }
    }

    /**
     * @hook pageinfoArea
     * @since 2.0.0
     */
    public function hookPageinfoArea()
    {
        global $page_info;

        $this->geo_filter_data['is_filtering'] = in_array($page_info['Key'], $this->geo_filter_data['filtering_pages']);

        if ($GLOBALS['config']['mod_rewrite']) {
            $this->geo_filter_data['is_location_url'] = in_array($page_info['Key'], $this->geo_filter_data['location_url_pages']);
        }

        /**
         * Reset location and try to redirect to the proper URL if saved location path causes 404,
         * but the requested uri is not map,js or css file
         */
        if ($page_info['Key'] == '404'
            && $this->geo_filter_data['applied_location']
            && !preg_match('/\.(map|js|css)$/', $_SERVER['REQUEST_URI'])
        ) {
            $parent = end($this->geo_filter_data['location']);
            $url    = RL_URL_HOME;

            if ($parent['Parent_link']) {
                $url = str_replace('locfix', '', $parent['Parent_link']);
            } else {
                $this->resetLocation();
                unset($this->geo_filter_data['applied_location']);
            }

            Util::redirect($url);
        }
    }

    /**
     * @hook phpMetaTags
     * @since 2.0.0
     */
    public function hookPhpMetaTags()
    {
        global $page_info;

        // Add canonical to pages with geo filter applied.
        if ($this->geo_filter_data['applied_location']
            && $this->geo_filter_data['is_location_url']
        ) {
            $link = $GLOBALS['rlGeoFilter']->buildGeoLink();
            $page_info['canonical'] = $link;
        }
    }

    /**
     * Box Data
     *
     * Prepare Data to the Geo Filtering box - get data based on current location and assigns to Smarty
     */
    public function boxData()
    {
        global $rlDb, $config;

        $this->detectLocation();

        $geo_box_data['levels'] = $this->geo_format['Levels'];

        if ($this->geo_filter_data['applied_location']) {
            $format_id = (int) $this->geo_filter_data['applied_location']['ID'];
        } else {
            $format_id = (int) $this->geo_format['Format_ID'];
        }

        $data = $GLOBALS['rlMultiField']->getData($format_id, true, $this->geo_format['Order_type']);

        foreach ($data as &$item) {
            $item['Link'] = $this->buildGeoLink($item);
            unset($item['Path']);
        }

        $geo_box_data['levels_data'][] = $data;

        $GLOBALS['rlSmarty']->assign('geo_box_data', $geo_box_data);
    }

    /**
     * Modify Where
     *
     * function is to modify sql queries and add addition condition by location
     *
     * @param  $sql   - sql query to add condition to
     * @param  $table - listings or accounts
     */
    public function modifyWhere(&$sql, $table = 'listings')
    {
        if (!$this->geo_filter_data['applied_location']
            || !$this->geo_filter_data['is_filtering']
        ) {
            return;
        }

        if (!$sql) {
            $sql = &$GLOBALS['sql'];
        }

        $data_key = $table == 'accounts' ? 'location_account_fields' : 'location_listing_fields';
        $data     = $this->geo_filter_data[$data_key];

        // Return if location search already performed from the search form
        if (strpos($sql, key($this->geo_filter_data[$data_key]))) {
            return;
        }

        foreach ($data as $field => $value) {
            if ($value) {
                $sql .= "AND `T1`.`{$field}` = '{$value}' ";
            }
        }
    }

    /**
     * Recount account listings based on selected user location
     *
     * @since 2.0.0
     *
     * @param  string &$sql - initial sql query
     */
    public function recountAccountListings(&$sql)
    {
        if (!$this->geo_filter_data['applied_location']
            || !$this->geo_filter_data['is_filtering']
        ) {
            return;
        }

        $data = $this->geo_filter_data['location_listing_fields'];

        if ($data) {
            $count_sql = "
                SELECT COUNT(`ID`) FROM `{db_prefix}listings`
                WHERE `Account_ID` = `T1`.`ID` AND `Status` = 'active'
            ";

            if ($GLOBALS['plugins']['listing_status']) {
                $count_sql .= "AND `Sub_status` <> 'invisible' ";
            }

            foreach ($data as $field => $value) {
                if ($value) {
                    $count_sql .= "AND `{$field}` = '{$value}' ";
                }
            }

            $sub_sql = ", IF (`Listings_count` = 0, 0, ({$count_sql})) AS `Listings_count` ";

            if (strpos($sql, '`Listings_count`')) {
                $sql = str_replace(', `Listings_count`', $sub_sql, $sql);
            } else {
                $sql .= $sub_sql;
            }
        }
    }

    /**
     * Smarty Fetch Hook
     *
     * Function is to replace html (smarty) content and change links based on pages enabled for geo filtering
     *
     * @param string $html - smarty html content
     */
    public function smartyFetchHook(&$html)
    {
        global $config;

        if ($this->geo_filter_data['applied_location']['Path']) {
            $find = $replace = array();
            $home_url = defined('SEO_BASE') ? SEO_BASE : RL_URL_HOME;

            foreach ($this->geo_filter_data['location_url_pages'] as $page_key) {
                $page_path = $GLOBALS['pages'][$page_key];
                $glang = RL_LANG_CODE != $config['lang'] ? RL_LANG_CODE : false;
                $clean_page_url = $this->cleanUrl(false, '/' . $page_path, $glang);

                if ($page_path) {
                    $find[] = $home_url . $page_path;
                    $replace[] = $this->buildGeoLink($this->geo_filter_data['applied_location'], $clean_page_url, true);
                } elseif ($page_path === '') {
                    $html = str_replace(
                        $home_url . '"',
                        $this->buildGeoLink($this->geo_filter_data['applied_location'], $clean_page_url, true) . '"',
                        $html
                    );
                }
            }

            $html = str_replace($find, $replace, $html);

            if (!$config['mf_listing_geo_urls']) {
                if ($config['mf_geo_subdomains']) {
                    switch ($config['mf_geo_subdomains_type']) {
                        case 'unique':
                            $subdomain_path = $this->geo_filter_data['applied_location']['Path'];
                            $path = '';
                            break;

                        case 'combined':
                            $subdomain_path = $GLOBALS['rlValid']->str2path($item['Path']);
                            $path = '';
                            break;

                        case 'mixed':
                            $tmp = explode('/', $this->geo_filter_data['applied_location']['Path']);
                            $subdomain_path = array_splice($tmp, 0, 1)[0];
                            $path = $tmp ? implode('/', $tmp) : '';
                            break;
                    }

                    // Replace path after domain
                    if ($path) {
                        $reg_find = '#' . $path . '/(([^"]*)-[0-9]+\.html)#smi';
                        $reg_replace = '$1';

                        $html = preg_replace($reg_find, $reg_replace, $html);
                    }

                    // Replace subdomain part
                    if ($subdomain_path) {
                        global $domain_info;

                        $reg_find = '#' . $subdomain_path .'.('. $domain_info['host'] .'/([^"]*)-[0-9]+\.html)#smi';
                        $reg_replace = '$1';

                        $html = preg_replace($reg_find, $reg_replace, $html);
                    }

                } else {
                    $reg_find = '#' . $this->geo_filter_data['applied_location']['Path'] . '/(([^"]*)-[0-9]+\.html)#smi';
                    $reg_replace = '$1';

                    $html = preg_replace($reg_find, $reg_replace, $html);
                }
            }
        }

        $html = str_replace('locfix', '', $html);
    }

    /**
     * @hook ajaxRequest
     * @since 2.0.0
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        global $rlDb;

        switch ($request_mode) {
            case 'mfApplyLocation':
                if ($_REQUEST['key']) {
                    $condition = array('Key' => $_REQUEST['key']);
                } elseif ($request_item) {
                    $condition = array('Path' => $request_item);
                } else {
                    $this->resetLocation();
                }

                if ($condition) {
                    $_SESSION['geo_filter_location'] = $rlDb->fetch('*', $condition, null, null, 'data_formats', 'row');
                    $this->saveLocation($_SESSION['geo_filter_location']);
                }

                $out = array(
                    'status' => 'OK',
                );
                break;

            case 'mfGeoAutocomplete':
                $out = array(
                    'status'  => 'OK',
                    'results' => $this->geoAutocomplete($request_item, $request_lang, $_REQUEST['currentLocation']),
                );
                break;
        }
    }

    /**
     * Geo autocomplete handler
     *
     * @since 2.0.0 $current_location parameter added
     *
     * @param  string $query            - autocomplete query
     * @param  string $lang             - requested language code
     * @param  string $current_location - format key of current location
     * @return array                    - match locations
     */
    public function geoAutocomplete($query = '', $lang = false, $current_location = null)
    {
        global $config, $rlDb, $rlLang;

        $query = Valid::escape($query);
        $current_location = Valid::escape($current_location);

        $sql = "
            SELECT `Value`, `Key`, CHAR_LENGTH(`Value`) AS `char_length`,
            IF (`Key` LIKE 'data_formats+name+{$current_location}%', 1, 0) AS `relevance`
            FROM `{db_prefix}lang_keys`
            WHERE `Value` LIKE '{$query}%'
            AND `Key` LIKE 'data_formats+name+{$this->geo_format['Key']}%'
            AND `Code` = '{$config['lang']}' AND `Status` = 'active'
            AND `Key` != 'data_formats+name+{$current_location}'
            ORDER BY `relevance` DESC, `char_length` ASC
            LIMIT {$config['mf_geo_autocomplete_limit']}
        ";

        $locations = $rlDb->getAll($sql);

        if ($locations) {
            $path_data  = array();
            $items_data = array();

            foreach ($locations as &$item) {
                $location_key = str_replace('data_formats+name+', '', $item['Key']);
                $location = $rlDb->fetch(
                    array('Path', 'Parent_ID', 'Parent_IDs'),
                    array('Key' => $location_key),
                    null, 1, 'data_formats', 'row'
                );

                $item['Path'] = $location['Path'];

                if ($GLOBALS['config']['mf_geo_subdomains'] && $item['Path']) {
                    switch ($GLOBALS['config']['mf_geo_subdomains_type']) {
                        case "unique":
                            $item['Subdomain_path'] = $item['Path'];
                            $item['Path'] = '';
                            break;

                        case "combined":
                            $item['Subdomain_path'] = $GLOBALS['rlValid']->str2path($item['Path']);
                            $item['Path'] = '';
                            break;

                        case "mixed":
                            $tmp = explode("/", $item['Path']);
                            $item['Subdomain_path'] = array_splice($tmp, 0, 1)[0];
                            $item['Path'] = $tmp ? implode("/", $tmp) : '';
                            break;
                    }
                }

                if ($location['Parent_IDs']) {
                    $parent_ids = explode(',', $location['Parent_IDs']);
                    $levels = count($parent_ids);

                    $item_names = array();
                    for ($i = 0; $i < $levels; $i++) {
                        $parent_id = $parent_ids[$i];

                        if (!$item_name = $items_data[$parent_id]) {
                            $parent_item = $rlDb->fetch('*', array('ID' => $parent_ids[$i]), null, null, 'data_formats', 'row');
                            $item_name = $rlLang->getPhrase("data_formats+name+{$parent_item['Key']}", $lang, null, true);
                            
                            $items_data[$parent_id] = $item_name;
                        }

                        $item_names[] = $item_name;
                    }

                    if ($item_names) {
                        $item['Value'] .= ', ' . implode(', ', $item_names);
                    }
                }

                $item['Key'] = str_replace('data_formats+name+', '', $item['Key']);
            }
        }

        return $locations;
    }

    /**
     * Reset location and save cookies
     *
     * @since 2.0.0
     */
    private function resetLocation()
    {
        unset($_SESSION['geo_filter_location']);
        $_COOKIE['mf_geo_location'] = 'reset';
        $GLOBALS['reefless']->createCookie('mf_geo_location', 'reset', $this->cookieTime);
    }

    /**
     * Save selected location in cookies
     *
     * @since 2.0.0
     *
     * @param array $location - selected location Key
     */
    private function saveLocation($location)
    {
        if ($this->detailsPage) {
            return;
        }

        $GLOBALS['reefless']->createCookie('mf_geo_location', json_encode($location), $this->cookieTime);
    }

    /**
     * @hook phpCategoriesGetCategoriesCache
     * @since 2.0.0
     */
    public function hookPhpCategoriesGetCategoriesCache(&$param1)
    {
        $this->adaptCategories($param1);
    }

    /**
     * @hook phpCategoriesGetCategories
     * @since 2.0.0
     */
    public function hookPhpCategoriesGetCategories(&$param1)
    {
        $this->adaptCategories($param1);
    }

    /**
     * @hook listingsModifyWhere
     * @since 2.0.0
     */
    public function hookListingsModifyWhere(&$sql)
    {
        $this->modifyWhere($sql);
    }

    /**
     * @hook modifyWhereByAccount
     * @since 2.0.0
     */
    public function hookListingsModifyWhereByAccount(&$sql)
    {
        $this->modifyWhere($sql);
    }

    /**
     * @hook listingsModifyWhereByPeriod
     * @since 2.0.0
     */
    public function hookListingsModifyWhereByPeriod(&$sql)
    {
        $this->modifyWhere($sql);
    }

    /**
     * @hook listingsModifyWhereFeatured
     * @since 2.0.0
     */
    public function hookListingsModifyWhereFeatured(&$sql)
    {
        $this->modifyWhere($sql);
    }

    /**
     * @hook listingsModifyWhereSearch
     * @since 2.0.0
     */
    public function hookListingsModifyWhereSearch(&$sql)
    {
        $this->modifyWhere($sql);
    }

    /**
     * @hook smartyFetchHook
     * @since 2.0.0
     */
    public function hookSmartyFetchHook(&$html)
    {
        if ($this->geo_filter_data
            && $GLOBALS['config']['mod_rewrite']
            && $this->geo_format
            && !defined('REALM')) {
            $this->smartyFetchHook($html);
        }
    }

    /**
     * @hook boot
     * @since 2.0.0
     */
    public function hookBoot()
    {
        $this->adaptPageInfo();

        if (version_compare($GLOBALS['config']['rl_version'], '4.7.1', '>')) {
            return;
        }

        $this->fixHreflang();
    }

    /**
     * Fix hreflang links for language selector
     *
     * @since 2.0.0
     * @todo remove this condition once hreflang tags issue in core is reworked
     */
    public function fixHreflang()
    {
        global $config, $languages, $listing_data, $currentPage;

        if (!$config['mf_geo_subdomains'] || !$config['mod_rewrite'] || count($languages) == 1) {
            return;
        }

        $hreflang = array();
        $url = RL_URL_HOME . $currentPage;

        foreach ($languages as $lang_item) {
            $lang_code = $lang_item['Code'] == $config['lang'] ? '' : $lang_item['Code'];

            if ($this->detailsPage) {
                $hreflang[$lang_item['Code']] = $this->buildListingUrl($url, $listing_data, $lang_code);
            } else {
                $hreflang[$lang_item['Code']] = $this->makeUrlGeo($url, $lang_code);
            }
        }

        $GLOBALS['rlSmarty']->assign('hreflang', $hreflang);
    }

    /**
     * @hook accountsSearchDealerSqlWhere
     * @since 2.0.0
     */
    public function hookAccountsSearchDealerSqlWhere(&$sql)
    {
        $this->modifyWhere($sql, 'accounts');
        $this->recountAccountListings($sql);
    }

    /**
     * @hook accountsGetDealersByCharSqlWhere
     * @since 2.0.0
     */
    public function hookAccountsGetDealersByCharSqlWhere(&$sql)
    {
        $this->modifyWhere($sql, 'accounts');
        $this->recountAccountListings($sql);
    }

    /**
     * @hook listingsModifyPreSelect
     * @since 2.0.0
     */
    public function hookListingsModifyPreSelect(&$dbcount = false)
    {
        if ($this->geo_filter_data['applied_location']) {
            $dbcount = false;
        }
    }

    /**
     * @hook phpRecentlyAddedModifyPreSelect
     * @since 2.0.0
     */
    public function hookPhpRecentlyAddedModifyPreSelect(&$dbcount = false)
    {
        if ($this->geo_filter_data['applied_location']) {
            $dbcount = false;
        }
    }

    /**
     * @hook hookPhpUrlBottom
     * @since 2.0.0
     */
    public function hookPhpUrlBottom(&$url, $mode, $data, $custom_lang)
    {
        global $config;

        if ($config['mf_listing_geo_urls']
            && $config['mod_rewrite']
            && $mode == 'listing'
        ) {
            $url = $this->buildListingUrl($url, $data);
        }
    }

    /**
     * Build listing url with geo location
     * @param  string $url       - default listing url
     * @param  array  $data      - listing data
     * @param  string $lang_code - language code to add to the url
     * @return string            - listing url with location path
     */
    public function buildListingUrl($url, $data, $lang_code = '')
    {
        static $data_formats = array();

        foreach (array_reverse($this->geo_filter_data['location_listing_fields']) as $field_key => $value) {
            if ($data[$field_key]) {
                // Get from the cache
                if ($from_cache = $data_formats[$data[$field_key]]) {
                    $listing_geo_path = $from_cache;
                }
                // Get from the db and put to the cache
                else {
                    $listing_geo_path = $GLOBALS['rlDb']->getOne('Path', "`Key`= '{$data[$field_key]}'", 'data_formats');
                    $data_formats[$data[$field_key]] = $listing_geo_path;
                }
                break;
            }
        }

        if (!$listing_geo_path) {
            return $url;
        }

        $parsed = parse_url($url);
        $listing_clean_url = $this->cleanUrl($parsed['scheme'] . '://' . $parsed['host'], $parsed['path'], $lang_code);

        $url = $this->buildGeoLink(
            array('Path' => $listing_geo_path),
            $listing_clean_url,
            true
        );

        return $url;
    }

    /**
     * @hook phpOriginalUrlRedirect
     * @since 2.0.0
     */
    public function hookPhpOriginalUrlRedirect($request_uri, &$real_uri, $real_base, $request_base)
    {
        if ($this->geo_filter_data['is_location_url']
            && $this->geo_filter_data['applied_location']['Path']
        ) {
            if (!is_numeric(strpos($real_uri, $this->geo_filter_data['applied_location']['Path']))) {
                $real_uri = $this->geo_filter_data['applied_location']['Path'] . '/' . $real_uri;
            }
        }
    }

    /**
     * @hook phpAbstractStepsBuildStepUrl
     * @since 2.0.0
     */
    public function hookPhpAbstractStepsBuildStepUrl(&$url)
    {
        if ($this->geo_filter_data['is_location_url']
            && $this->geo_filter_data['applied_location']['Path']
        ) {
            $url = $this->makeUrlGeo($url);
        }
    }

    /**
     * @hook reeflessRedirctVars
     * @since 2.0.0
     */
    public function hookReeflessRedirctVars(&$request_url, $vars, $http_response_code)
    {
        if ($this->geo_filter_data['is_location_url'] && !defined('REALM')) {
            $request_url = $this->makeUrlGeo($request_url);
        }
    }

    /**
     * @hook utilsRedirectURL
     * @since 2.0.0
     */
    public function hookUtilsRedirectURL(&$url)
    {
        if ($this->geo_filter_data['is_location_url'] && !defined('REALM')) {
            $url = $this->makeUrlGeo($url);
        }
    }

    /**
     * @hook phpValidateUserLocation
     * @since 2.0.0
     */
    public function hookPhpValidateUserLocation($location, &$errors, &$errors_trigger, $wrapper)
    {
        if ($GLOBALS['rlDb']->getOne(
            'ID',
            "`Path` = '{$location}' AND `Key` LIKE '{$this->geo_format['Key']}%'",
            "data_formats"
        )) {
            $errors_trigger = true;
            $errors = $GLOBALS['lang']['personal_address_in_use'];
        }
    }

    /**
     * @hook apPhpAccountsValidate
     * @since 2.0.0
     */
    public function hookApPhpAccountsValidate()
    {
        $location = (string) $GLOBALS['profile_data']['location'];

        if (!$location) {
            return;
        }

        $sql = "SELECT `T1`.`Key` FROM `{db_prefix}data_formats` AS `T1` ";
        $sql .= "JOIN `{db_prefix}multi_formats` AS `T2` ON `T2`.`Key` = `T1`.`Key` ";
        $sql .= "WHERE `T2`.`Geo_filter` = '1' AND `T2`.`Status` = 'active' ";

        $geo_format = $GLOBALS['rlDb']->getRow($sql);

        if ($GLOBALS['rlDb']->getOne(
            'ID',
            "`Path` = '{$location}' AND `Key` LIKE '{$geo_format['Key']}%'",
            "data_formats"
        )) {
            $GLOBALS['errors'][] = $GLOBALS['lang']['personal_address_in_use'];
            $GLOBALS['error_fields'][] = "profile[location]";
        }
    }

    /**
     * @hook phpSearchOnMapDefaultAddress
     * @since 2.0.0
     */
    public function hookPhpSearchOnMapDefaultAddress(&$default_map_location)
    {
        if ($this->geo_filter_data['applied_location'] && $this->geo_filter_data['is_filtering']) {
            $default_map_location = '';
            foreach ($this->geo_filter_data['location'] as $loc_item) {
                $default_map_location .= $loc_item['name'] . ', ';
            }
            $default_map_location = trim($default_map_location, ', ');

            Valid::escape($default_map_location);
        }
    }

    /**
     * @hook phpGetProfileModifyField
     * @since 2.0.0
     */
    public function hookPhpGetProfileModifyField(&$sql, &$edit_mode)
    {
        if ($edit_mode) {
            return;
        }

        $this->recountAccountListings($sql);
    }

    /**
     * Replace location patterns in meta category phrases before rlListings::replaceMetaFields() call,
     * because that method will remove all location patterns from the phrases
     *
     * @hook listingDetailsBeforeMetaData
     */
    public function hookListingDetailsBeforeMetaData()
    {
        global $lang, $cat_bread_crumbs, $listing_data, $rlListingTypes;

        $this->prepareListingLocationData();

        $meta = array('meta_description', 'meta_keywords', 'meta_title');
        foreach ($meta as $area) {
            $pattern_found = false;

            foreach (array_reverse($cat_bread_crumbs) as $category) {
                if ($lang['categories+listing_' . $area . '+' . $category['Key']]) {
                    $this->adaptLocString($lang['categories+listing_' . $area . '+' . $category['Key']]);
                    $pattern_found = true;
                    break;
                }
            }
        }

        // Search in general category
        if (!$pattern_found && $general_id = $rlListingTypes->types[$listing_data['Cat_type']]['Cat_general_cat']) {
            $general_key = $GLOBALS['rlDb']->getOne('Key', "`ID` = {$general_id}", 'categories');

            foreach ($meta as $area) {
                if ($lang['categories+listing_' . $area . '+' . $general_key]) {
                    $this->adaptLocString($lang['categories+listing_' . $area . '+' . $general_key]);
                }
            }
        }
    }

    /**
     * Make Url Geo
     *
     * add location to the normal URL
     */
    public function makeUrlGeo($url, $lang_code = '')
    {
        $parsed = parse_url($url);
        $clean_url = $this->cleanUrl($parsed['scheme'] . '://' . $parsed['host'], $parsed['path'], $lang_code);
        $url = $this->buildGeoLink($this->geo_filter_data['applied_location'], $clean_url, true);

        return $url;
    }

    /**
     * Adapt Page Info
     *
     * Replaces {location} variables in the string according to applied location
     */
    public function adaptPageInfo()
    {
        global $page_info, $bread_crumbs, $lang, $main_menu;

        $areas = array('meta_description', 'meta_keywords', 'meta_title', 'h1', 'title');
        foreach ($areas as $area) {
            $this->adaptLocString($page_info[$area]);
        }

        if ($bread_crumbs) {
            $bc_areas = array('title', 'name');
            foreach ($bread_crumbs as $bk => $bc_item) {
                foreach ($bc_areas as $area) {
                    $this->adaptLocString($bread_crumbs[$bk][$area]);
                }
            }
        }

        if (in_array('home', $this->geo_filter_data['filtering_pages'])) {
            $this->adaptLocString($GLOBALS['config']['site_name']);
            $this->adaptLocString($lang['pages+title+home']);
            $this->adaptLocString($GLOBALS['rss']['title']);
            $this->adaptLocString($GLOBALS['rlSmarty']->_tpl_vars['site_name']);
        }

        foreach ($main_menu as $k => $v) {
            $this->adaptLocString($main_menu[$k]['title']);
        }

        // Recount listings to build proper rel_prev and prev_next tags
        if ($page_info['Controller'] == 'listing_type') {
            $GLOBALS['category']['Count']     = $GLOBALS['rlListings']->calc;
            $GLOBALS['listing_type']['Count'] = $GLOBALS['rlListings']->calc;
        }

        // Add canonical to pages with geo filter applied.
        if ($this->geo_filter_data['applied_location']
            && $this->geo_filter_data['is_location_url']
        ) {
            $link = $GLOBALS['rlGeoFilter']->buildGeoLink();
            $page_info['canonical'] = $link;
        }
    }

    /**
     * Prepare listing location data
     * @since 2.0.0
     */
    public function prepareListingLocationData()
    {
        foreach ($GLOBALS['listing'] as $group) {
            foreach ($group['Fields'] as $field) {
                if (isset($this->geo_filter_data['location_listing_fields'][$field['Key']])) {
                    $this->listing_location_data[] = $field['value'];
                }
            }
        }
    }

    /**
     * Adapt Location String
     *
     * Replaces {location} variables in the string according to applied location
     *
     * @param array $string
     */
    public function adaptLocString(&$string)
    {
        if (!$string) {
            return;
        }

        for ($i = 0; $i < $this->geo_format['Levels']; $i++) {
            $level       = $i + 1;
            $replace     = '{location_level' . $level . '}';
            $pattern     = '{if location_level' . $level . '}(((?!\\/if).)+)+{\\/if}';

            if ($this->detailsPage) {
                $location = $this->listing_location_data[$i];
            } else {
                $location = $this->geo_filter_data['location'][$i]['name'];
            }

            if ($location) {
                $locations[] = $location;
            }

            if (false !== strpos($string, $replace)) {
                if ($location) {
                    $string = str_replace($replace, $location, $string);
                }

                $string = preg_replace("/{$pattern}/smi", $location ? '\\1' : '', $string);
            }
        }

        if ($locations) {
            $string = str_replace('{location}', implode(', ', array_reverse($locations)), $string);
        }

        $string = preg_replace('/\{if location\}(.*)\{\/if\}/smi', $locations ? '\\1' : '', $string);
    }

    /**
     * Adapt Categories
     *
     * Recount categories depending on current location
     * TODO - do it based on new counting system
     *
     * @param array $categories - categories
     **/
    public function adaptCategories(&$categories)
    {
        if (!$this->geo_filter_data['applied_location']
            || !$this->geo_filter_data['is_filtering']
            || ($_POST['xjxfun'] || $GLOBALS['page_info']['Key'] == 'search')
        ) {
            return;
        }

        foreach ($categories as &$category) {
            if (!$category['Count']) {
                continue;
            }

            $sql = "SELECT COUNT(`T1`.`ID`) AS `Count` FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
            $sql .= "WHERE (`T1`.`Category_ID` = {$category['ID']} OR FIND_IN_SET({$category['ID']}, `Crossed`) > 0 ";

            if ($GLOBALS['config']['lisitng_get_children']) {
                $sql .= "OR FIND_IN_SET({$category['ID']}, `T3`.`Parent_IDs`) > 0 ";
            }

            $sql .= ") AND `T1`.`Status` = 'active' ";

            if ($GLOBALS['plugins']['listing_status']) {
                $sql .= "AND `T1`.`Sub_status` <> 'invisible' ";
            }

            foreach ($this->geo_filter_data['location_listing_fields'] as $field => $value) {
                if ($field && $value) {
                    $sql .= "AND `T1`.`{$field}` = '{$value}' ";
                }
            }

            $category['Count'] = $GLOBALS['rlDb']->getRow($sql, 'Count');
        }
    }

    /**
     * User location detection process
     *
     * @return bool
     */
    public function detectLocation()
    {
        global $reefless, $config, $rlValid, $rlDb, $page_info;

        if ($this->geo_filter_data['applied_location']) {
            return false;
        }

        if ($reefless->isBot()
            || $_GET['q'] == 'ext'
            || $_POST['xjxfun']
            || !$config['mf_geo_autodetect']
            || isset($_GET['reset_location'])
            || $_COOKIE['mf_geo_location'] == 'reset'
            || $this->detailsPage
            || strtoupper($_SERVER['REQUEST_METHOD']) == 'POST'
        ) {
            return false;
        }

        $names = array();

        if ($_SESSION['GEOLocationData']->Country_name) {
            $names[] = $_SESSION['GEOLocationData']->Country_name;
        }
        if ($_SESSION['GEOLocationData']->Region) {
            $names[] = $_SESSION['GEOLocationData']->Region;
        }
        if ($_SESSION['GEOLocationData']->City) {
            $names[] = $_SESSION['GEOLocationData']->City;
        }

        $parent_key = $this->geo_format['Key'];

        foreach ($names as $name) {
            Valid::escape($name);

            $sql = "SELECT `Key` ";
            $sql .= "FROM `{db_prefix}lang_keys` ";
            $sql .= "WHERE `Value` = '{$name}' ";
            $sql .= "AND SUBSTRING(`Key`, 19, '" . strlen($parent_key) . "') = '{$parent_key}' ";
            $sql .= "ORDER BY CHAR_LENGTH(`Key`) ASC ";
            $sql .= "LIMIT 1";

            $location = $rlDb->getRow($sql);

            if ($location) {
                $parent_key = $location['Key'] = str_replace('data_formats+name+', '', $location['Key']);
                $locations[] = $location;
            } else {
                break;
            }
        }

        if ($locations) {
            $locations = array_reverse($locations);
            $location_to_apply = $rlDb->fetch('*', array('Key' => $locations[0]['Key'], 'Status' => 'active'), null, null, 'data_formats', 'row');

            // Save automatically detected location for 12 hours
            $reefless->createCookie('mf_geo_location', $location_to_apply['Key'], strtotime('+ 12 hours'));

            if (!$location_to_apply) {
                return false;
            }

            if ($this->geo_filter_data['is_location_url'] && $location_to_apply['Path']) {
                $redirect_url = $this->buildGeoLink($location_to_apply, $this->clean_url, true);

                // Redirect using default header function to avoid utilsRedirectURL hook call
                header("Location: {$redirect_url}", true, 301);
                exit;
            } else {
                $_SESSION['geo_filter_location'] = $location_to_apply;
                header('Refresh: 0');
                exit;
            }
        }
    }

    /* DEPRECATED METHODS */

    /**
     * Adapt Page Title
     * @deprecated 2.0.0
     */
    public function adaptPageTitle(&$title)
    {}

    /**
     * createCookie
     * @deprecated 2.0.0
     */
    public function createCookie($key, $value, $expire_time = '')
    {}
}
