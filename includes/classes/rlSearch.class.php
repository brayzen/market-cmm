<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLSEARCH.CLASS.PHP
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

class rlSearch extends reefless
{
    /**
     * @var current form fields
     **/
    public $fields;

    /**
     * @var exclude listing ID in search
     **/
    public $exclude;


    /**
     * Define default Address on map and assign to smarty
     * @since  4.7.1
     **/
    public function defaultMapAddressAssign()
    {
        if ($_POST['loc_lat'] && $_POST['loc_lng']) {
            return false;
        }

        if ($GLOBALS['config']['realty_search_map_location'] && $_SESSION['GEOLocationData']) {
            $default_map_location = $_SESSION['GEOLocationData']->Country_name;    
            $default_map_location .= $_SESSION['GEOLocationData']->Region ? ', ' . $_SESSION['GEOLocationData']->Region : '';
            $default_map_location .= $_SESSION['GEOLocationData']->City ? ', ' . $_SESSION['GEOLocationData']->City : '';
        }

        $GLOBALS['rlHook']->load('phpSearchOnMapDefaultAddress', $default_map_location);
        
        if ($default_map_location) {
            $GLOBALS['rlSmarty']->assign('default_map_location', trim($default_map_location));
        }
    }

    /**
     * build search form
     *
     * @param string $key - search form key
     * @param string $type - listing type | REMOVED SINCE v4.5
     *
     * @return array - form information
     **/
    public function buildSearch($key = false)
    {
        global $rlCache, $config;

        $GLOBALS['rlValid']->sql($key);
        if (!$key) {
            return false;
        }

        $GLOBALS['rlHook']->load('phpSearchBuildSearchTop', $key); // >= v4.3

        /* get form from cache */
        if ($config['cache']) {
            return $rlCache->get('cache_search_forms', $key);
        }

        $sql = "SELECT `T1`.`Category_ID`, `T1`.`Group_ID`, `T1`.`Fields`, ";
        $sql .= "`T2`.`Key` AS `Group_key`, `T2`.`Display`, ";
        $sql .= "`T3`.`Type` AS `Listing_type`, `T3`.`Key` AS `Form_key`, `T3`.`With_picture` ";
        $sql .= "FROM `{db_prefix}search_forms_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_groups` AS `T2` ON `T1`.`Group_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}search_forms` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T3`.`Key` = '{$key}' AND `T3`.`Status` = 'active' ";
        $sql .= "ORDER BY `Position` ";

        $GLOBALS['rlHook']->load('phpSearchBuildSearchGetRelations', $sql, $key); // >= v4.3

        $relations = $this->getAll($sql);

        if (!$relations) {
            return false;
        }

        /* populate field information */
        foreach ($relations as $rKey => $value) {
            $sql = "SELECT `ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, CONCAT('listing_fields+name+', `Key`) AS `pName`, ";
            $sql .= "FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order` ";
            $sql .= "FROM `{db_prefix}listing_fields` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
            $sql .= "ORDER BY `Order`";
            $fields = $this->getAll($sql);

            $relations[$rKey]['pName'] = 'listing_groups+name+' . $value['Group_key'];
            $relations[$rKey]['Fields'] = empty($fields) ? false : $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'listing_fields', $value['Listing_type']);
        }

        $GLOBALS['rlHook']->load('phpSearchBuildSearchBottom', $relations, $key); // >= v4.3

        return $relations;
    }

    /**
     * get general data of search form
     *
     * @param string $key  - search form key
     * @param string $listing_type_key - listing type key
     * @param bool $tab_form - is form splitted by tabs
     *
     * @todo array - form fields list
     **/
    public function getFields($key = false, $listing_type_key = false, $tab_form = false)
    {
        global $rlCache, $config, $rlListingTypes;

        $GLOBALS['rlValid']->sql($key);
        if (!$key) {
            return false;
        }

        $arrange_field = $rlListingTypes->types[$listing_type_key]['Arrange_field'];

        /* get form from cache */
        if ($config['cache']) {
            $fields = $rlCache->get('cache_search_fields', $key);
            $this->fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'listing_fields', array('name', 'default'));

            /* add additional field */
            if ($tab_form && $arrange_field) {
                $a_field = $this->fetch(array('ID', 'Key', 'Type'), array('Key' => $arrange_field), null, 1, 'listing_fields', 'row');
                if ($a_field) {
                    $this->fields[$arrange_field] = $a_field;
                }
            }

            return true;
        }

        $sql = "SELECT `T1`.`Category_ID`, `T1`.`ID`, `T1`.`Fields`, `T2`.`Key` AS `Form_key` ";
        $sql .= "FROM `{db_prefix}search_forms_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}search_forms` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Key` = '{$key}' AND `T2`.`Status` = 'active' ";
        $sql .= "ORDER BY `Position` ";
        $relations = $this->getAll($sql);

        if (!$relations) {
            return false;
        }

        $out = array();
        foreach ($relations as $key => $value) {
            $sql = "SELECT `ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, `Details_page`, ";
            $sql .= "FIELD(`ID`, '{$value['Fields']}') AS `Order` ";
            $sql .= "FROM `{db_prefix}listing_fields` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
            $sql .= "ORDER BY `Order`";
            $fields = $this->getAll($sql, 'Key');

            $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'listing_fields', array('name', 'default'));

            $out += $fields;
            unset($fields);
        }

        $this->fields = $out;

        /* add additional field */
        if ($tab_form && $arrange_field) {
            $a_field = $this->fetch(array('ID', 'Key', 'Type'), array('Key' => $arrange_field), null, 1, 'listing_fields', 'row');
            if ($a_field) {
                $this->fields[$arrange_field] = $a_field;
            }
        }
    }

    /**
     * search listings
     *
     * @param array $data - form data
     * @param string $type - listing type
     * @param int $start - start DB position
     * @param int $limit - listing number per request
     *
     * @return array - listings information
     **/
    public function search($data = false, $type = false, $start = 0, $limit = false)
    {
        global $sql, $custom_order, $config, $rlListings, $lang, $rlCommon;

        $form = $this->fields;

        if (!$form) {
            return false;
        }

        $start = $start > 1 ? ($start - 1) * $limit : 0;
        $hook = '';

        $this->loadClass('Listings');
        $this->loadClass('Common');

        $sql = "SELECT SQL_CALC_FOUND_ROWS ";

        if ($data['keyword_search']) {
            $sql .= " DISTINCT ";
        }

        $sql .= "{hook} ";
        $sql .= "`T1`.*, `T3`.`Path`, `T3`.`Parent_ID`, `T3`.`Key` AS `Cat_key`, `T3`.`Key`, `T3`.`Type` AS `Listing_type`, `T3`.`Parent_keys`, `T3`.`Parent_IDs`, ";

        if ($data['myads_controller']) {
            $sql .= "IF(`T2`.`Price` = 0, 'free', '' ) `Free`, CONCAT('categories+name+', `T3`.`Key`) AS `Cat_key`, ";
            $sql .= "DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY) AS `Plan_expire`, CONCAT('listing_plans+name+', `T2`.`Key`) AS `Plan_key`, ";
            $sql .= "DATE_ADD(`T1`.`Featured_date`, INTERVAL `T4`.`Listing_period` DAY) AS `Featured_expire`, `T3`.`Type` AS `Category_type`, `T2`.`Image` AS `Plan_image`, ";
            $sql .= "`SC`.`ID` AS `Subscription_ID`, `SC`.`Service` AS `Subscription_service`, ";
            $sql .= "`T2`.`Image_unlim`, `T2`.`Video` AS `Plan_video`, `T2`.`Video_unlim`, `T3`.`Type` AS `Listing_type`, `T1`.`Last_step`, ";
        }

        /**
         * @since 4.5.2 - $data, $type, $form
         */
        $GLOBALS['rlHook']->load('listingsModifyFieldSearch', $sql, $data, $type, $form);

        $sql .= "IF(`T1`.`Featured_date`, '1', '0') `Featured` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";

        if ($data['myads_controller']) {
            $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T4` ON `T1`.`Featured_ID` = `T4`.`ID` ";
        }

        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql_lang = "LEFT JOIN `{db_prefix}lang_keys` AS `TL` ON `TL`.`Key` = CONCAT('categories+name+',`T3`.`Key`) AND `TL`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T7` ON `T1`.`Account_ID` = `T7`.`ID` ";

        if ($data['myads_controller']) {
            $sql .= "LEFT JOIN `{db_prefix}subscriptions` AS `SC` ON `T1`.`ID` = `SC`.`Item_ID` AND `SC`.`Status` = 'active' ";
        }

        foreach ($form as $fKey => $fVal) {
            if ($GLOBALS['conversion_rates'][$fVal['Condition']] && $fVal['Condition'] && !$tdfs[$fVal['Condition']]) {
                $sql .= "LEFT JOIN `{db_prefix}data_formats` AS `TDF_{$fVal['Condition']}` ON `TDF_{$fVal['Condition']}`.`Key` = SUBSTRING_INDEX(`T1`.`{$fVal['Key']}`, '|', -1) ";
                $tdfs[$fVal['Condition']] = true;
            }
        }

        $sql .= "{keyword_search_hook} ";

        /**
         * @since 4.5.2 - $data, $type, $form
         */
        $GLOBALS['rlHook']->load('listingsModifyJoinSearch', $sql, $data, $type, $form);

        if ($data['myads_controller']) {
            $sql .= sprintf("WHERE `T1`.`Account_ID` = %d ", intval($GLOBALS['account_info']['ID']));
        } else {
            $sql .= "WHERE `T1`.`Status` = 'active' ";
        }
        $having_sql = false;

        foreach ($form as $fKey => $fVal) {
            $f = $GLOBALS['rlValid']->xSql($data[$fKey]);

            if ($f != '' && ($f != '0' || $form[$fKey]['Type'] == 'bool')) {
                switch ($form[$fKey]['Type']) {
                    case 'mixed':
                        if ($f['df'] && ($f['from'] || $f['to']) && $fVal['Condition'] && $GLOBALS['conversion_rates'][$fVal['Condition']]) {
                            if ($rate = $GLOBALS['conversion_rates'][$fVal['Condition']][$f['df']]) {
                                $f['from'] = $f['from'] / $rate;
                                $f['to'] = $f['to'] / $rate;
                            }

                            if ($f['from']) {
                                $sql .= "AND SUBSTRING_INDEX(`T1`.`{$fKey}`, '|', 1)/IF(`TDF_{$fVal['Condition']}`.`Rate` IS NULL, 1, `TDF_{$fVal['Condition']}`.`Rate`) >= " . $f['from'] . " ";
                            }
                            if ($f['to']) {
                                $sql .= "AND SUBSTRING_INDEX(`T1`.`{$fKey}`, '|', 1)/IF(`TDF_{$fVal['Condition']}`.`Rate` IS NULL, 1, `TDF_{$fVal['Condition']}`.`Rate`) <= " . $f['to'] . " ";
                            }
                            break;
                        }
                    case 'price':
                        if ($f['currency'] && ($f['from'] || $f['to'])) {
                            $sql .= "AND LOCATE('{$f['currency']}', `T1`.`" . $fKey . "`) > 0 ";
                        }
                    case 'number':
                        if ((float) $f['from']) {
                            $sql .= "AND ROUND(`T1`.`{$fKey}`,2) >= '" . (float) $f['from'] . "' ";
                            $sql .= "AND `T1`.`{$fKey}` <> '' ";
                        }
                        if ((float) $f['to']) {
                            $sql .= "AND ROUND(`T1`.`{$fKey}`,2) <= '" . (float) $f['to'] . "' ";
                        }
                        break;

                    case 'text':
                        if ($fKey == 'keyword_search') {
                            $f = trim($f);

                            if ($f && !$this->keywordSearch($f, $data['keyword_search_type'], $type)) {
                                return false;
                            } else {
                                $keyword_search = true;
                            }
                        } else {
                            if (is_array($f)) {
                                // plugin handler
                            } else {
                                $sql .= "AND `T1`.`{$fKey}` LIKE '%" . $f . "%' ";
                            }
                        }
                        break;

                    case 'date':
                        if ($form[$fKey]['Default'] == 'single') {
                            if ($f['from']) {
                                $sql .= "AND UNIX_TIMESTAMP(`T1`.`{$fKey}`) >= UNIX_TIMESTAMP('" . $f['from'] . "') ";
                            }
                            if ($f['to']) {
                                $sql .= "AND UNIX_TIMESTAMP(`T1`.`{$fKey}`) <= UNIX_TIMESTAMP('" . $f['to'] . "') ";
                            }
                        } elseif ($form[$fKey]['Default'] == 'multi') {
                            $sql .= "AND UNIX_TIMESTAMP(`T1`.`{$fKey}`) <= UNIX_TIMESTAMP('" . $f . "') ";
                            $sql .= "AND UNIX_TIMESTAMP(`T1`.`{$fKey}_multi`) >= UNIX_TIMESTAMP('" . $f . "') ";
                        }
                        break;

                    case 'select':
                        if ($form[$fKey]['Condition'] == 'years') {
                            if ($f['from']) {
                                $sql .= "AND `T1`.`{$fKey}` >= '" . (int) $f['from'] . "' ";
                            }
                            if ($f['to']) {
                                $sql .= "AND `T1`.`{$fKey}` <= '" . (int) $f['to'] . "' ";
                            }
                        } elseif ($form[$fKey]['Key'] == 'Category_ID') {
                            $sql .= "AND ((`T1`.`{$fKey}` = '{$f}' OR FIND_IN_SET('{$f}', `T3`.`Parent_IDs`) > 0) OR (FIND_IN_SET('{$f}', `T1`.`Crossed`) > 0)) ";
                            $hook = "IF (FIND_IN_SET('{$f}', `T1`.`Crossed`) > 0, 1, 0) AS `Crossed_listing`, ";
                        } elseif ($form[$fKey]['Key'] == 'posted_by') {
                            $sql .= "AND `T7`.`Type` = '" . $f . "' ";
                        }
                        /* system fields */
                        elseif ($fVal['Key'] == 'sf_status') {
                            $sql .= "AND `T1`.`Status` = '{$f}' ";
                        } elseif ($fVal['Key'] == 'sf_active_till') {
                            $sql .= "AND TIMESTAMPDIFF(DAY, NOW(), DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY)) <= {$f} ";
                        } elseif ($fVal['Key'] == 'sf_plan') {
                            $sql .= "AND `T1`.`Plan_ID` = {$f} ";
                        }
                        /* system fields end */
                        else {
                            $sql .= "AND `T1`.`{$fKey}` = '" . $f . "' ";
                        }
                        break;

                    case 'bool':
                        $state = ($f == 'on' || $f == "1") ? 1 : 0;

                        /* system fields */
                        if ($fVal['Key'] == 'sf_featured') {
                            $having_sql = "HAVING `Featured` = {$state} ";
                        }
                        /* system fields end */
                        else {
                            $sql .= "AND `T1`.`{$fKey}` = '{$state}' ";
                        }
                        break;

                    case 'radio':
                        $sql .= "AND `T1`.`{$fKey}` = '" . $f . "' ";
                        break;

                    case 'checkbox':
                        unset($f[0]);
                        if (!empty($f)) {
                            $sql .= "AND (";
                            foreach ($f as $fI => $fV) {
                                $sql .= "FIND_IN_SET('" . $f[$fI] . "', `T1`.`{$fKey}`) > 0 AND ";
                            }
                            $sql = substr($sql, 0, -4);
                            $sql .= ") ";
                        }
                        break;

                    case 'phone':
                        if (!empty($f['code']) || !empty($f['area']) || !empty($f['number']) || !empty($f['ext'])) {
                            $sql .= "AND (`T1`.`{$fKey}` <> '' ";

                            if (!empty($f['code'])) {
                                $sql .= "AND `T1`.`{$fKey}` LIKE '%c:{$f['code']}%' ";
                            }

                            if (!empty($f['area'])) {
                                $sql .= "AND `T1`.`{$fKey}` LIKE '%a:{$f['area']}%' ";
                            }

                            if (!empty($f['number'])) {
                                $sql .= "AND `T1`.`{$fKey}` LIKE '%n:{$f['number']}%' ";
                            }

                            if (!empty($f['ext'])) {
                                $sql .= "AND `T1`.`{$fKey}` LIKE '%e:{$f['ext']}%' ";
                            }

                            $sql .= ") ";
                        }
                        break;
                }

                $GLOBALS['rlHook']->load('searchSelectArea', $sql, $f, $fVal);
            }
        }

        if ($this->exclude) {
            $sql .= "AND NOT FIND_IN_SET(`T1`.`ID`, '{$this->exclude}') ";
        }

        if ($data['myads_controller']) {
            $sql .= "AND `T1`.`Status` <> 'trash' ";
        }

        if ($type) {
            $sql .= "AND `T3`.`Type` = '{$type}' ";
        }

        if ($data['with_photo']) {
            $sql .= "AND `T1`.`Photos_count` > 0 ";
        }

        /**
         * @since 4.5.2 - $data, $type, $form
         */
        $GLOBALS['rlHook']->load('listingsModifyWhereSearch', $sql, $data, $type, $form);
        $GLOBALS['rlHook']->load('listingsModifyGroupSearch', $sql, $data, $type, $form);

        if (false === strpos($sql, 'GROUP BY')) {
            // $sql .= " GROUP BY `T1`.`ID` ";
        }

        if ($having_sql) {
            $sql .= $having_sql;
        }

        $sql .= "ORDER BY `Featured` DESC ";

        $data['sort_type'] = in_array($data['sort_type'], array('asc', 'desc')) ? $data['sort_type'] : 'asc';

        if ($custom_order) {
            $sql .= ", `{$custom_order}` " . strtoupper($data['sort_type']) . " ";
        } elseif ($form[$data['sort_by']]) {
            switch ($form[$data['sort_by']]['Type']) {
                case 'mixed':
                    if ($GLOBALS['conversion_rates'][$form[$data['sort_by']]['Condition']]) {
                        $sql .= ", SUBSTRING_INDEX(`T1`.`{$form[$data['sort_by']]['Key']}`, '|', 1)/IF(`TDF_{$form[$data['sort_by']]['Condition']}`.`Rate` IS NULL, 1, `TDF_{$form[$data['sort_by']]['Condition']}`.`Rate`) ";
                        $sql .= " " . strtoupper($data['sort_type']) . " ";
                        break;
                    }
                case 'price':
                case 'unit':
                    $sql .= ", ROUND(`T1`.`{$form[$data['sort_by']]['Key']}`, 2) " . strtoupper($data['sort_type']) . " ";
                    break;
                case 'select':
                    if ($form[$data['sort_by']]['Key'] == 'Category_ID') {
                        $sql .= ", `T3`.`Key` " . strtoupper($data['sort_type']) . " ";
                    } elseif ($form[$data['sort_by']]['Key'] == 'Listing_type') {
                        $sql .= ", `T3`.`Type` " . strtoupper($data['sort_type']) . " ";
                    } else {
                        $sql .= ", `T1`.`{$form[$data['sort_by']]['Key']}` " . strtoupper($data['sort_type']) . " ";
                    }
                    break;

                default:
                    $sql .= ", `T1`.`{$form[$data['sort_by']]['Key']}` " . strtoupper($data['sort_type']) . " ";
                    break;
            }
        } else {
            $sql .= ", `T1`.`Date` DESC ";
        }

        $sql .= "LIMIT {$start}, {$limit} ";

        /**
         * @since 4.5.2
         */
        $GLOBALS['rlHook']->load('listingsModifySqlSearch', $sql, $start, $limit);

        /* replace hooks */
        $sql = str_replace('{hook}', $hook, $sql);
        $sql = str_replace('{keyword_search_hook}', $keyword_search ? $sql_lang : '', $sql);

        $listings = $this->getAll($sql);

        $calc = $this->getRow("SELECT FOUND_ROWS() AS `calc`");
        $this->calc = $calc['calc'];

        $listings = $GLOBALS['rlLang']->replaceLangKeys($listings, 'categories', 'name');

        foreach ($listings as &$listing) {
            if ($data['myads_controller'] && !$listing['Featured']) {
                $listing['Featured_expire'] = '';
            }

            $rlCommon->listings[$listing['ID']] = $listing;

            $fields = $rlListings->getFormFields(
                $listing['Category_ID'],
                'short_forms',
                $listing['Listing_type'],
                $listing['Parent_IDs']
            );

            foreach ($fields as &$field) {
                $field['value'] = $rlCommon->adaptValue(
                    $field,
                    $listing[$field['Key']],
                    'listing',
                    $listing['ID'],
                    true,
                    false,
                    false,
                    false,
                    $listing['Account_ID'],
                    null,
                    $listing['Listing_type']
                );
            }

            $listing['fields'] = $fields;
            $listing['listing_title'] = $rlListings->getListingTitle(
                $listing['Category_ID'],
                $listing,
                $listing['Listing_type'],
                null,
                $listing['Parent_IDs']
            );
            $listing['url'] = $this->getListingUrl($listing);
        }

        return $listings;
    }

    /**
     * build keyword search mysql request by requested kewywords string
     *
     * @param string $request - listing type
     * @param int $mode - search mode, 1 - All words, any order; 2 - Any words, any order; 3-  Exact words, exact order; 4 - Exact words, any order
     * @param string $type - listing type key
     *
     * @return array - listings information
     **/
    public function keywordSearch($query = false, $mode = 2, $type = false)
    {
        global $sql;

        $mode = !$mode ? 2 : $mode;
        $query = trim($query);
        $query = preg_replace('/(\\s)\\1+/', ' ', $query);
        $query = preg_replace('/([\[\]\(\)\*\+\|\?]+)/', '', $query);
        $query = str_replace('%', '', $query);
        $query = preg_quote($query);
        $query = str_replace('\\\\', '\\', $query);

        $query_exploded = explode(' ', $query);
        $query_count = count($query_exploded);

        /* remove short words from the query */
        foreach ($query_exploded as $wi => $word) {
            if ($GLOBALS['rlCommon']->strLen($word, '<', 2)) {
                unset($query_exploded[$wi]);
            }
        }

        if (!$query || empty($query_exploded)) {
            return;
        }

        /* save the query to highlight it on the listing details */
        $_SESSION['keyword_search_data']['keyword_search'] = $query;

        /* get system fields */
        $this->setTable('listing_fields');
        $this->outputRowsMap = array('Key', 'Key');
        $fields = $this->fetch(array('Key', 'Type', 'Condition'), array('Status' => 'active'), "AND `Type` IN ('text', 'textarea') AND `Key` <> 'keyword_search' AND `Key` <> 'search_account'");

        if (!$fields) {
            return;
        }

        if (isset($fields[0])) {
            foreach ($fields as $field) {
                $tmp_fields[] = $field['Key'];
            }
            $fields = $tmp_fields;
            unset($tmp_fields);
        }

        switch ($mode) {
            case 1:
            case 2:
                $condition = $mode == 1 ? 'AND' : 'OR';

                foreach ($query_exploded as $query_item) {
                    $sign = strlen($query_item) > 4 ? '?' : '';
                    $sub_sql .= "(CONCAT_WS(' ', `T1`.`" . implode("`, `T1`.`", $fields) . "`, `TL`.`Value`) RLIKE '{$query_item}{$sign}') {$condition} ";
                }
                $sub_sql = preg_replace('/' . $condition . ' $/', '', $sub_sql);
                break;

            case 3;
                $sub_sql = "CONCAT_WS(' ', `T1`.`" . implode("`, `T1`.`", $fields) . "`, `TL`.`Value`) LIKE '%{$query}%' ";
                break;
        }

        $sql .= "AND (";
        $sql .= $sub_sql;
        $sql .= ") ";

        return true;
    }

    /**
     * save search
     *
     * @package xAjax
     * @param string $type - listing type
     *
     **/
    public function ajaxSaveSearch($type = false)
    {
        global $_response, $account_info, $lang, $post_form_key;

        if (!$type) {
            return $_response;
        }

        if (defined('IS_LOGIN')) {
            $content = $_SESSION[$type . '_post'];

            unset($content['sort_type']);
            unset($content['sort_by']);

            foreach ($content as $key => $value) {
                if ($content[$key]['from'] == $lang['from']) {
                    $content[$key]['from'] = "";
                }
                if ($content[$key]['to'] == $lang['to']) {
                    $content[$key]['to'] = "";
                }

                // escort package && availability field
                if (($content[$key]['day'] && intval($content[$key]['day']) < 0)
                    && ($content[$key]['time'] && intval($content[$key]['time']) < 0)) {
                    unset($content[$key]);
                }

                if (empty($content[$key])) {
                    unset($content[$key]);
                }
                if (isset($content[$key]['from']) && (empty($content[$key]['from']) && empty($content[$key]['to']))) {
                    unset($content[$key]);
                }
                if (isset($content[$key][0]) && is_array($content[$key])) {
                    unset($content[$key][0]);

                    if (empty($content[$key])) {
                        unset($content[$key]);
                    }
                }
                if ($content[$key]['distance'] && !$content[$key]['zip']) {
                    unset($content[$key]);
                }
            }

            if (!empty($content)) {
                $content = serialize($GLOBALS['rlValid']->xSql($content));
                $form_key = $_POST['form'];

                $exist = $this->fetch(array('ID'), array('Content' => $content, 'Account_ID' => $account_info['ID']), null, 1, 'saved_search', 'row');

                if (empty($exist)) {
                    $insert = array(
                        'Account_ID'   => $account_info['ID'],
                        'Form_key'     => $post_form_key,
                        'Listing_type' => $type,
                        'Content'      => $content,
                        'Date'         => 'NOW()',
                    );

                    $this->loadClass('Actions');

                    $GLOBALS['rlActions']->rlAllowHTML = true;
                    $GLOBALS['rlActions']->insertOne($insert, 'saved_search');

                    $_response->script("printMessage('notice', '{$lang['search_saved']}')");
                } else {
                    $_response->script("printMessage('error', '{$lang['search_already_saved']}')");
                }
            } else {
                $_response->script("printMessage('error', '{$lang['empty_search_disallow']}')");
            }
        } else {
            $_response->script("printMessage('error', '{$lang['notice_operation_inhibit']}')");
        }

        unset($content, $exist);

        return $_response;
    }

    /**
     * delete saved search
     *
     * @package xAjax
     *
     * @param string $id  - search id
     *
     **/
    public function ajaxDeleteSavedSearch($id)
    {
        global $_response, $account_info, $lang;

        if (defined('IS_LOGIN')) {
            $id = (int) $id;
            $info = $this->fetch(array('ID', 'Account_ID'), array('ID' => $id), null, 1, 'saved_search', 'row');

            if ($info['Account_ID'] == $account_info['ID']) {
                $this->query("DELETE FROM `{db_prefix}saved_search` WHERE `ID` = '{$info['ID']}' LIMIT 1");

                $this->setTable('saved_search');
                $fav = $this->fetch(array('ID'), array('Account_ID' => $account_info['ID']));

                if (empty($fav)) {
                    $empty_mess = '<div class="info">' . $lang['no_saved_search'] . '</div>';
                    $_response->assign('saved_search_obj', 'innerHTML', $empty_mess);
                }

                $_response->script("$('#item_{$id}').fadeOut('slow');");
                $_response->script("printMessage('notice', '{$lang['notice_saved_search_deleted']}');");
            }
        }

        return $_response;
    }

    /**
     * activate/deactivate/delete saved search
     *
     * @package xAjax
     *
     * @param string $id  - search id
     *
     **/
    public function ajaxMassSavedSearch($id = false, $action = 'activate')
    {
        global $_response, $account_info, $lang;

        $items = explode('|', $id);

        if (defined('IS_LOGIN')) {
            $status = $action == 'activate' ? 'active' :
            'approval';

            foreach ($items as $item) {
                if (!empty($item)) {
                    $GLOBALS['rlValid']->sql($item);
                    if ($action == 'delete') {
                        $sql = "DELETE FROM `{db_prefix}saved_search` WHERE `ID` = '{$item}' AND `Account_ID` = '{$account_info['ID']}' LIMIT 1";
                        $this->query($sql);

                        $_response->script("$('#item_{$item}').fadeOut('slow');");
                    } else {
                        $sql = "UPDATE `{db_prefix}saved_search` SET `Status` = '{$status}' WHERE `ID` = '{$item}' AND `Account_ID` = '{$account_info['ID']}' LIMIT 1";
                        $this->query($sql);

                        $html = '<span class="' . $status . '">' . $lang[$status] . '</span>';
                        $_response->assign('status_' . $item, 'innerHTML', $html);
                    }
                }
            }

            if (!empty($sql)) {
                if ($action == 'delete') {
                    $mess = $lang['notice_items_deleted'];
                    if (!$this->getOne("ID", "`Account_ID` = '{$account_info['ID']}'", "saved_search")) {
                        $empty_mess = '<div class="info">' . $lang['no_saved_search'] . '</div>';
                        $_response->assign('saved_search_obj', 'innerHTML', $empty_mess);
                    }
                } else {
                    $mess = $action == 'activate' ? $lang['notice_items_activated'] : $lang['notice_items_deactivated'];
                }

                $_response->script("printMessage('notice', '{$mess}')");
            }
        }

        return $_response;
    }

    /**
     * check saved search
     *
     * @package xAjax
     *
     * @param string $id  - search id
     *
     **/
    public function ajaxCheckSavedSearch($id = false)
    {
        global $_response, $pages, $page_info, $account_info, $config, $rlListingTypes, $search_results_url, $rlActions;

        $id = (int) $id;

        if (defined('IS_LOGIN')) {
            $search = $this->fetch(array('ID', 'Form_key', 'Content', 'Listing_type'), array('ID' => $id, 'Account_ID' => $account_info['ID']), null, 1, 'saved_search', 'row');
            $listing_type = $search['Listing_type'];

            $update = array(
                'fields' => array('Date' => 'NOW()'),
                'where'  => array('ID' => $search['ID']),
            );
            $rlActions->updateOne($update, 'saved_search');

            $data = unserialize($search['Content']);

            $_SESSION['post_form_key'] = $search['Form_key'];
            $_SESSION[$listing_type . '_post'] = $data;

            $url = $config['mod_rewrite'] ? SEO_BASE . $pages[$rlListingTypes->types[$listing_type]['Page_key']] . '/' . $search_results_url . '.html' : SEO_BASE . '?page=' . $pages[$rlListingTypes->types[$listing_type]['Page_key']] . '&' . $search_results_url;
            $_response->redirect($url);
        }

        return $_response;
    }

    /**
     * @since 4.5.0
     *
     * build side bar search forms
     **/
    public function getHomePageSearchForm()
    {
        $this->getSideBarSearchForm('home');
    }

    /**
     * build search forms, depends of the forms count, listing types relations and arrange settings
     *
     * @todo - build forms and assign them to SMARTY
     *
     **/
    public function getSideBarSearchForm($mode = false)
    {
        global $rlListingTypes, $rlSmarty, $rlHook, $out_search_forms, $lang, $category;

        /* get search forms */
        if ($mode == 'home') {
            foreach ($rlListingTypes->types as $type_key => $listing_type) {
                if ($listing_type['Search_home']) {
                    $type_form_number++;
                    $active_form_key = $type_key;
                    $active_type = $rlListingTypes->types[$active_form_key];
                }
            }

            if (!$type_form_number) {
                return false;
            }
        } else {
            foreach ($rlListingTypes->types as $type_key => $listing_type) {
                if (($listing_type['Search_page'] || $listing_type['Search_type']) && $GLOBALS['page_info']['Key'] == 'lt_' . $type_key) {
                    $active_form_key = $type_key;
                    $active_type = $rlListingTypes->types[$active_form_key];
                }
            }

            $type_form_number = 1;
        }

        /* get forms by listing types */
        if ($type_form_number > 1) {
            foreach ($rlListingTypes->types as $type_key => $listing_type) {
                if ($listing_type['Search_home']) {
                    if ($search_form = $this->buildSearch($type_key . '_quick')) {
                        $form_key = $type_key . '_quick';
                        $out_search_forms[$form_key]['data'] = $search_form;
                        $out_search_forms[$form_key]['name'] = $lang['search_forms+name+' . $form_key];
                        $out_search_forms[$form_key]['listing_type'] = $type_key;
                    }
                }
            }
        }
        /* get arranged (optional) search forms by signle type */
        elseif ($type_form_number == 1) {
            if ($active_type['Arrange_field'] && $active_type['Arrange_search']) {
                $arrange_values = explode(',', $active_type['Arrange_values']);

                foreach ($arrange_values as $arrange_value) {
                    $form_key = $active_form_key . '_tab' . $arrange_value;
                    if ($search_form = $this->buildSearch($form_key)) {
                        $out_search_forms[$form_key]['data'] = $search_form;
                        $out_search_forms[$form_key]['name'] = $lang['search_forms+name+' . $form_key];
                        $out_search_forms[$form_key]['listing_type'] = $active_form_key;
                        $out_search_forms[$form_key]['arrange_field'] = $active_type['Arrange_field'];
                        $out_search_forms[$form_key]['arrange_value'] = $arrange_value;
                    }
                }
            } else {
                if ($search_form = $this->buildSearch($active_form_key . '_quick')) {
                    $form_key = $active_form_key . '_quick';
                    $out_search_forms[$form_key]['data'] = $search_form;
                    $out_search_forms[$form_key]['name'] = $lang['search_forms+name+' . $form_key];
                    $out_search_forms[$form_key]['listing_type'] = $active_form_key;
                }
            }
        }

        $rlHook->load('phpHomeSearchForms');
        $rlSmarty->assign_by_ref('search_forms', $out_search_forms);

        // enable "in category search" for template
        if ($category['ID'] > 0) {
            $rlSmarty->assign('in_category_search', true);
        }
    }

    /**
     * @since 4.5.1
     *
     * build in category sidebar search box
     *
     * @param array $category - current category details
     *
     * @todo - avaid the db query which fetchs the search block, also remove `Position` field from the rlCommon::getBLocks
     *
     **/
    public function buildInCategorySidebarForm($category = false)
    {
        global $blocks, $lang, $rlSmarty, $rlDb;

        if (!$category || $category['ID'] <= 0) {
            return;
        }

        $sql = "`Status` = 'active' AND ";

        // prepare condition
        if ($category['Parent_IDs']) {
            $search_categories = explode(',', $category['Parent_IDs']);
            array_unshift($search_categories, $category['ID']);

            $sql .= "`Category_ID` > 0 AND (`Subcategories` = '1' OR `Category_ID` = {$category['ID']}) AND `Category_ID` IN ('" . implode("','", $search_categories) . "') ";
            $sql .= "ORDER BY FIND_IN_SET(`Category_ID`, '" . implode(",", $search_categories) . "') ASC ";
        } else {
            $sql .= "`Category_ID` = {$category['ID']}";
        }

        // get search form
        $form_key = $rlDb->getOne('Key', $sql, 'search_forms');

        if ($form_key && $search_form = $this->buildSearch($form_key)) {
            $form_name = $lang['search_forms+name+' . $form_key];

            $out_search_forms[$form_key]['data'] = $search_form;
            $out_search_forms[$form_key]['name'] = $form_name;
            $out_search_forms[$form_key]['listing_type'] = $category['Type'];
            $rlSmarty->assign_by_ref('search_forms', $out_search_forms);
        } else {
            return;
        }

        // add search block to blocks
        $block_key = 'ltpb_' . $category['Type'];
        $block = $GLOBALS['rlDb']->fetch(array('ID', 'Key', 'Side', 'Type', 'Content', 'Tpl', 'Header', 'Position'), array('Key' => $block_key), null, 1, 'blocks', 'row');

        if (!$block) {
            $GLOBALS['rlDebug']->logger("rlSearch::buildInCategorySidebarForm failed, no search block found for '{$category['Type']}' listing type.");
            return;
        }

        $block['name'] = $form_name;

        $blocks[$block_key] = $block;
        $GLOBALS['reefless']->rlArraySort($blocks, 'Position');
        $GLOBALS['rlCommon']->defineBlocksExist($blocks);

        unset($search_form);

        $rlSmarty->assign('in_category_search', true);
    }
}
