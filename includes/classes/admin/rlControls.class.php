<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLCONTROLS.CLASS.PHP
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

class rlControls extends reefless
{
    /**
     * recount listings number for each category
     *
     * @param string $self - html element selector
     * @param bool $direct - call function as non ajax function
     *
     * @package ajax
     *
     **/
    public function ajaxRecountListings($self = false, $direct = false, $start = false, $category_id = false)
    {
        global $_response, $lang, $rlCache, $rlHook, $config;

        if ($this->checkSessionExpire() === false && !$direct) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        /* account expiration */
        if ($config['membership_module']) {
            $sql = "UPDATE `{db_prefix}accounts` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
            $sql .= "SET `T1`.`Status` = IF(TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) > `T2`.`Plan_period` * 24 AND `T2`.`Plan_period` != 0, 'expired', 'active')";
            $sql .= "WHERE `T1`.`Status` != 'pending' AND `T1`.`Status` != 'incomplete' AND `T1`.`Status` != 'approval'";

            $this->query($sql);
        }

        /* listings expiration */
        $sql = "UPDATE `{db_prefix}listings` AS `T1` ";
        $sql .= "JOIN `{db_prefix}accounts` AS `T2` ON `T2`.`ID` = `T1`.`Account_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T6` ON `T6`.`ID` = `T1`.`Plan_ID` ";
        if ($config['membership_module']) {
            $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T7` ON `T1`.`Plan_ID` = `T7`.`ID` ";
        }
        $sql .= "SET `T1`.`Status` = ";
        $sql .= "IF( ";

        if ($config['membership_module']) {
            $sql .= "(`T1`.`Plan_type` = 'account'  AND (TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) <= `T7`.`Plan_period` * 24 OR `T7`.`Plan_period` = 0)) OR ";
            $sql .= "(`T1`.`Plan_type` != 'account' AND (TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) <= `T6`.`Listing_period` * 24 OR `T6`.`Listing_period` = 0)) ";
            $sql .= "OR `T2`.`Status` = 'expired' ";
        } else {
            $sql .= "(TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) <= `T6`.`Listing_period` * 24 OR `T6`.`Listing_period` = 0) ";
        }

        $rlHook->load('apAjaxRecountListings', $sql);

        $sql .= ", `T1`.`Status`, 'expired') ";
        $sql .= "WHERE `T1`.`Status` != 'pending' AND `T1`.`Status` != 'incomplete' ";
        $this->query($sql);
        /* listings expiration end */

        /* update listing statuses in case inactive category or listing type */
        $sql = "UPDATE `{db_prefix}listings` AS `T1` ";
        $sql .= "JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID` ";
        $sql .= "JOIN `{db_prefix}listing_types` AS `T3` ON `T3`.`Key` = `T2`.`Type` ";

        $sql .= "SET `T1`.`Status` = ";
        $sql .= "IF(`T2`.`Status` != 'active' OR `T3`.`Status` != 'active' ";
        $sql .= ", 'approval', `T1`.`Status` ) ";

        $sql .= "WHERE `T1`.`Status` != 'pending' AND `T1`.`Status` != 'incomplete' ";
        $this->query($sql);
        /* update listing statuses end */

        $GLOBALS['rlCategories']->recountCategories();

        /* recount account listings number */
        $sql = "UPDATE `{db_prefix}accounts` SET `Listings_count` = ";
        $sql .= "(SELECT COUNT(*) FROM `{db_prefix}listings` WHERE `Status` = 'active' AND `Account_ID` = `{db_prefix}accounts`.`ID`) ";
        $this->query($sql);
        /* recount account listings number end */

        $GLOBALS['rlListingTypes']->updateCountListings();

        $rlCache->updateCategories();
        $rlCache->updateListingStatistics();

        if (!$direct) {
            $_response->script("printMessage('notice', '{$lang['listings_recounted']}')");
            $_response->script("$('{$self}').val('{$lang['recount']}');");
        }

        return $_response;
    }

    /**
     * recount carwegories levels
     *
     * @param bool $mode - show notice
     *
     * @package ajax
     *
     **/
    public function ajaxRebuildCatLevels($mode = true, $self = false, $start = false)
    {
        global $_response, $lang, $rlListingTypes;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $start = (int) $start;
        $limit = 100;

        /* get all categories */
        $this->setTable('categories');
        $categories = $this->fetch(array('ID', 'Parent_ID', 'Position', 'Type'), null, "ORDER BY `Parent_ID`", array($start, $limit));

        $this->loadClass('Categories');

        foreach ($categories as $key => $category) {
            $tree = '';
            $level = 0;
            $related_cats = $GLOBALS['rlCategories']->getBreadCrumbs($category['Parent_ID'], false, $rlListingTypes->types[$category['Type']]);
            $related_cats = array_reverse($related_cats);

            foreach ($related_cats as $r_category) {
                $tree .= $r_category['Position'] . '.';
            }
            $tree .= $category['Position'];

            $level = empty($category['Parent_ID']) ? 0 : count($related_cats);

            $parent_ids = array();
            if ($category['Parent_ID']) {
                $parent_ids[] = $category['Parent_ID'];
                if ($parents = $GLOBALS['rlCategories']->getParentIDs($category['Parent_ID'])) {
                    $parent_ids = array_merge($parents, $parent_ids);
                }
                $parent_ids = implode(',', $parent_ids);

                if ($parent_ids) {
                    $sql = "SELECT GROUP_CONCAT(DISTINCT `Key` ORDER BY `Level`) as `Keys` FROM `{db_prefix}categories` ";
                    $sql .= "WHERE FIND_IN_SET(`ID`, '{$parent_ids}')";
                    $parent_keys = $this->getRow($sql, "Keys");

                    $sql = "UPDATE `{db_prefix}categories` SET `Parent_keys` = '{$parent_keys}' ";
                    $sql .= "WHERE `ID` = {$category['ID']}";
                    $this->query($sql);
                }
            }

            $update[] = array(
                'fields' => array(
                    'Level'      => $level,
                    'Tree'       => $tree,
                    'Parent_IDs' => $parent_ids,
                ),
                'where'  => array(
                    'ID' => $category['ID'],
                ),
            );
        }

        if ($update) {
            $GLOBALS['rlActions']->update($update, 'categories');

            // start recursion
            if (count($categories) == $limit) {
                $start += $limit;
                $_response->script("xajax_rebuildCatLevels('{$mode}', '{$self}', {$start});");
                unset($categories);

                return $_response;
            }
        }

        if ((bool) $mode === true) {
            $_response->script("printMessage('notice', '{$lang['levels_rebuilt']}')");
            $_response->script("$('{$self}').val('{$lang['rebuild']}');");
        }

        unset($update, $categories, $related_cats);

        return $_response;
    }

    /**
     * recount carwegories levels
     *
     * @param bool $mode - show notice
     *
     * @package ajax
     *
     **/
    public function ajaxReorderFields($mode = true, $self = false, $start = false)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $start = (int) $start;
        $limit = 500;

        /* get all categories */
        $this->setTable('categories');
        $categories = $this->fetch(array('ID'), null, "ORDER BY `Parent_ID`", array($start, $limit));
        $this->resetTable();

        foreach ($categories as $key => $value) {
            // reorder main form
            $main_form = $this->fetch(array('ID'), array('Category_ID' => $categories[$key]['ID']), "ORDER BY `Position`", null, 'listing_relations');

            foreach ($main_form as $sKey => $sVal) {
                $pos = $sKey + 1;
                $update[$sKey]['where'] = array(
                    'ID' => $main_form[$sKey]['ID'],
                );
                $update[$sKey]['fields'] = array(
                    'Position' => $pos,
                );
            }
            if (!empty($update)) {
                $GLOBALS['rlActions']->update($update, 'listing_relations');
            }

            // reorder main form
            $short_form = $this->fetch(array('ID'), array('Category_ID' => $categories[$key]['ID']), "ORDER BY `Position`", null, 'short_forms');
            unset($update);

            foreach ($short_form as $sKey => $sVal) {
                $pos = $sKey + 1;
                $update[$sKey]['where'] = array(
                    'ID' => $short_form[$sKey]['ID'],
                );
                $update[$sKey]['fields'] = array(
                    'Position' => $pos,
                );
            }
            if (!empty($update)) {
                $GLOBALS['rlActions']->update($update, 'short_forms');
            }

            // reorder listing titles
            $listing_titles = $this->fetch(array('ID'), array('Category_ID' => $categories[$key]['ID']), "ORDER BY `Position`", null, 'listing_titles');
            unset($update);

            foreach ($listing_titles as $sKey => $sVal) {
                $pos = $sKey + 1;
                $update[$sKey]['where'] = array(
                    'ID' => $listing_titles[$sKey]['ID'],
                );
                $update[$sKey]['fields'] = array(
                    'Position' => $pos,
                );
            }
            if (!empty($update)) {
                $GLOBALS['rlActions']->update($update, 'listing_titles');
            }

            // reorder featured form
            $featured_form = $this->fetch(array('ID'), array('Category_ID' => $categories[$key]['ID']), "ORDER BY `Position`", null, 'featured_form');
            unset($update);

            foreach ($featured_form as $sKey => $sVal) {
                $pos = $sKey + 1;
                $update[$sKey]['where'] = array(
                    'ID' => $featured_form[$sKey]['ID'],
                );
                $update[$sKey]['fields'] = array(
                    'Position' => $pos,
                );
            }
            if (!empty($update)) {
                $GLOBALS['rlActions']->update($update, 'featured_form');
            }
        }

        /* get all search forms */
        $this->setTable('search_forms');
        $forms = $this->fetch(array('ID'), null, "ORDER BY `ID`");
        $this->resetTable();

        foreach ($forms as $key => $value) {
            // reorder search form relations
            $search_form = $this->fetch(array('ID'), array('Category_ID' => $forms[$key]['ID']), "ORDER BY `Position`", null, 'search_forms_relations');
            unset($update);

            foreach ($search_form as $sKey => $sVal) {
                $pos = $sKey + 1;
                $update[$sKey]['where'] = array(
                    'ID' => $search_form[$sKey]['ID'],
                );
                $update[$sKey]['fields'] = array(
                    'Position' => $pos,
                );
            }
            if (!empty($update)) {
                $GLOBALS['rlActions']->update($update, 'search_forms_relations');
            }
        }

        // start recursion
        if (count($categories) == $limit) {
            $start += $limit;
            $_response->script("xajax_reorderFields('{$mode}', '{$self}', {$start});");
            unset($categories);

            return $_response;
        }

        if ((bool) $mode === true) {
            $_response->script("printMessage('notice', '{$lang['positions_reordered']}')");
            $_response->script("$('{$self}').val('{$lang['reorder']}');");
        }

        return $_response;
    }

    /**
     * update system cache
     *
     * @param bool $mode - show notice
     *
     * @package ajax
     *
     **/
    public function ajaxUpdateCache($mode = true, $self = false)
    {
        global $_response, $lang, $rlCache;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $rlCache->update();

        if ((bool) $mode === true) {
            $_response->script("printMessage('notice', '{$lang['cache_updated']}')");
            $_response->script("$('{$self}').val('{$lang['update']}');");
        }

        return $_response;
    }

    /**
     * update listing coordinates from text location data
     *
     * @package ajax
     *
     **/

    public function ajaxRefreshCoordinates($start)
    {
        global $_response, $rlCommon, $rlActions, $lang;

        if (!$start) {
            $_SESSION['refloc_stats']['updated'] = 0;
            $_SESSION['refloc_stats']['failed'] = 0;
        }

        // check admin session expire
        if ($this->checkSessionExpire() === false && !$direct) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $sql = "SELECT * FROM `{db_prefix}listing_fields` WHERE `Map` = '1'";
        $fields = $this->getAll($sql);

        $start = (int) $start;
        $limit = 100;

        $sql = "SELECT * FROM `{db_prefix}listings` WHERE `Status` = 'active' LIMIT {$start}, {$limit}";
        $listings = $this->getAll($sql);

        foreach ($listings as $key => $listing) {
            $location = array();
            foreach ($fields as $fKey => $field) {
                if ($listing[$field['Key']]) {
                    $location[] = $rlCommon->adaptValue($field, $listing[$field['Key']]);
                }
            }

            $update_fields = $this->geocodeLocation($location);
            if ($update_fields['Loc_latitude'] && $update_fields['Loc_longitude']) {
                $update['fields'] = $update_fields;
                $update['where']['ID'] = $listing['ID'];
                $_SESSION['refloc_stats']['updated']++;
            } else {
                $_SESSION['refloc_stats']['failed']++;
            }

            if ($update) {
                $rlActions->updateOne($update, 'listings');
            }
        }

        if (count($listings) == $limit) {
            $start += $limit;
            $_response->script("xajax_refreshCoordinates({$start});");

            return $_response;
        }

        if (!$direct) {
            $mess = str_replace(
                array('[updated]', '[failed]'),
                array($_SESSION['refloc_stats']['updated'], $_SESSION['refloc_stats']['failed']),
                $lang['listing_locations_refreshed']
            );

            if ($GLOBALS['config']['geocode_request_limit_reached']) {
                $mess = "<ul><li style=\"list-style:initial\">{$mess}</li>";
                $mess .= "<li style=\"list-style:initial\">{$lang['geocode_request_limit_reached_notice']}</li></ul>";
                $_response->script("printMessage('alert', '{$mess}')");
            } else {
                $_response->script("printMessage('notice', '{$mess}')");
            }

            $_response->script("$('#refresh_location').val('{$lang['rebuild']}')");
        }

        return $_response;
    }

    /**
     * recount listings number for each membership plan
     *
     */
    public function ajaxRecountListingsMP($self = false, $start = 0)
    {
        global $_response, $lang, $rlCache, $rlHook, $config, $rlActions;

        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $start = (int) $start;
        $limit = 100;

        $sql = "SELECT `T1`.`ID`, `T1`.`Featured`, `T1`.`Pay_date`, ";
        $sql .= "`T2`.`Advanced_mode`, `T2`.`Listing_number`, `T2`.`Standard_listings`, `T2`.`Featured_listings`, `T2`.`Plan_period`, ";
        $sql .= "`T3`.`Listings_remains`, `T3`.`Standard_remains`, `T3`.`Featured_remains`, `T3`.`ID` AS `lpID`, ";
        $sql .= "(SELECT COUNT(`TL`.`ID`) FROM `{db_prefix}listings` AS `TL`
                WHERE `TL`.`Account_ID` = `T1`.`ID` AND `TL`.`Status` <> 'pending' AND `TL`.`Status` <> 'trash' AND `TL`.`Plan_type` = 'account' LIMIT 1) AS `ltotal`, ";
        $sql .= "(SELECT COUNT(`TLS`.`ID`) FROM `{db_prefix}listings` AS `TLS`
                WHERE `TLS`.`Account_ID` = `T1`.`ID` AND `TLS`.`Status` <> 'pending' AND `TLS`.`Status` <> 'trash' AND `TLS`.`Plan_type` = 'account'
                AND (`TLS`.`Featured_ID` <= 0 OR `TLS`.`Featured_ID` = '') AND `TLS`.`Featured_date` IS NULL LIMIT 1) AS `standard_total`, ";
        $sql .= "(SELECT COUNT(`TLF`.`ID`) FROM `{db_prefix}listings` AS `TLF`
                WHERE `TLF`.`Plan_ID` = `T1`.`Plan_ID` AND `TLF`.`Status` <> 'pending' AND `TLF`.`Status` <> 'trash' AND `TLF`.`Plan_type` = 'account'
                AND `TLF`.`Featured_ID` > 0 AND `TLF`.`Featured_date` IS NOT NULL LIMIT 1) AS `featured_total` ";
        $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_packages` AS `T3` ON `T1`.`Plan_ID` = `T3`.`Plan_ID` AND `T3`.`Account_ID` = `T1`.`ID` AND `T3`.`Type` = 'account' ";
        $sql .= "WHERE `T1`.`Status` <> 'pending' AND `T1`.`Status` <> 'trash' ";
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "LIMIT {$start},{$limit}";

        $accounts = $this->getAll($sql);

        if ($accounts) {
            foreach ($accounts as $account) {
                $update = array();
                if ($account['Listings_remains'] > $account['Listing_number']) {
                    $update['fields']['Listings_remains'] = $account['Listing_number'] - $account['ltotal'];
                }
                if ($account['Advanced_mode']) {
                    if ($account['Standard_remains'] > $account['Standard_listings']) {
                        $update['fields']['Standard_remains'] = $account['Standard_listings'] - $account['standard_total'];
                    }
                    if ($account['Featured_remains'] > $account['Featured_listings']) {
                        $update['fields']['Featured_remains'] = $account['Featured_listings'] - $account['featured_total'];
                    }
                }
                if ($update) {
                    $update['where'] = array('ID' => $account['lpID']);
                    $rlActions->updateOne($update, 'listing_packages');
                }
            }
        }

        if (count($accounts) == $limit) {
            $start += $limit;
            $_response->script("xajax_recountListingsMP('{$self}', {$start});");

            return $_response;
        } else {
            $_response->script("printMessage('notice', '{$lang['listings_recounted']}')");
            $_response->script("$('{$self}').val('{$lang['recount']}');");
        }

        return $_response;
    }
}
