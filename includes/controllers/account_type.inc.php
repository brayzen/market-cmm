<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: ACCOUNT_TYPE.INC.PHP
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

/* define account type */
$account_type_key = str_replace('at_', '', $page_info['Key']);
$account_type = $rlAccount->getTypeDetails($account_type_key);

$reefless->loadClass('MembershipPlan');

$rlHook->load('accountTypeTop');

if ($account_type && $account_type['Page']) {
    $rlSmarty->assign_by_ref('account_type', $account_type);

    /* register ajax methods */
    $reefless->loadClass('Message');
    $rlXajax->registerFunction(array('contactOwner', $rlMessage, 'ajaxContactOwner'));

    /* request account details */
    $account_id = (int) $_GET['id'] ? (int) $_GET['id'] : $_GET['nvar_1'];
    $account = $rlAccount->getProfile($account_id);

    if ($account) {
        if ($config['mod_rewrite'] && (bool) preg_match('/\\.html$/', $_SERVER['REQUEST_URI'])) {
            Util::redirect($account['Personal_address']);
        }

        // re-assign is_contact_allowed value in case if the logged in user is owner of the page
        if ($account_info['ID'] == $account['ID']) {
            $rlMembershipPlan->is_contact_allowed = true;
        }

        // get short form details in case if own page option disabled
        $owner_short_details = $rlAccount->getShortDetails($account, $account['Account_type_ID']);
        if ($account_info['ID'] != $account['ID']) {
            $rlMembershipPlan->fakeValues($owner_short_details);
            $rlMembershipPlan->fakeValues($account['Fields']);
        }
        $rlSmarty->assign_by_ref('owner_short_details', $owner_short_details);

        // define "is owner"
        $rlSmarty->assign('is_owner', $account_info['ID'] == $account_id);

        $page_info['meta_description'] = $rlAccount->replaceAccountMetaFields($account_type, $account, 'description');
        $page_info['meta_keywords'] = $rlAccount->replaceAccountMetaFields($account_type, $account, 'keywords');

        /* assign account details */
        $rlSmarty->assign_by_ref('account', $account);

        /* populate tabs */
        $tabs = array(
            'details'  => array(
                'key'  => 'details',
                'name' => $lang['account_info'],
            ),
            'listings' => array(
                'key'  => 'listings',
                'name' => $lang['account_listings'],
            ),
            'map'      => array(
                'key'  => 'map',
                'name' => $lang['map'],
            ),
        );
        $rlSmarty->assign_by_ref('tabs', $tabs);

        $title = $account['Full_name'];
        if ($account['company_name']) {
            $title = $account['Fields']['company_name']['value'];
        }

        /* add bread crumbs step */
        $bread_crumbs[] = array(
            'name' => $title,
        );
        $page_info['name'] = $title;

        $blocks['account_page_info']['name'] = str_replace('{account_type}', $account_type['name'], $lang['account_type_details']);

        if ($blocks['account_page_info']) {
            $blocks['account_page_info']['Tpl'] = 1;
        }

        if ($blocks['account_page_location']) {
            $blocks['account_page_location']['Tpl'] = 1;
        }

        /* fields for sorting */
        $sorting = array(
            'date'     => array(
                'name'  => $lang['date'],
                'field' => 'date',
                'Type'  => 'date',
            ),
            'category' => array(
                'name'  => $lang['category'],
                'field' => 'Category_ID',
            ),
        );
        $rlSmarty->assign_by_ref('sorting', $sorting);

        /* define sort field */
        $sort_by = $_SESSION['account_sort_by'] = $_REQUEST['sort_by'] ? $_REQUEST['sort_by'] : $_SESSION['account_sort_by'];

        if ($_REQUEST['sort_by']) {
            $rlSmarty->assign('sorting_mode', true);
        }
        $sort_by = $sort_by ? $sort_by : 'date';
        if (!empty($sorting[$sort_by])) {
            $order_field = $sorting[$sort_by]['Key'];
            $data['sort_by'] = $sort_by;
            $rlSmarty->assign_by_ref('sort_by', $sort_by);
        }

        /* define sort type */
        $sort_type = $_SESSION['account_sort_type'] = $_REQUEST['sort_type'] ? $_REQUEST['sort_type'] : $_SESSION['account_sort_type'];
        $sort_type = $sort_type ? $sort_type : 'desc';

        if ($sort_type) {
            $data['sort_type'] = $sort_type = in_array($sort_type, array('asc', 'desc')) ? $sort_type : false;
            $rlSmarty->assign_by_ref('sort_type', $sort_type);
        }

        $pInfo['current'] = (int) $_GET['pg'];

        if (!is_int($account_id)) {
            $account_id = $account['ID'];
        }

        /* get account listings */
        $reefless->loadClass('Listings');
        $listings = $rlListings->getListingsByAccount($account['ID'], $sort_by, $sort_type, $pInfo['current'], $config['listings_per_page']);
        $rlSmarty->assign_by_ref('listings', $listings);

        // do 301 redirect to the first page if no listings found for requested page
        if (!$listings && $pInfo['current'] > 1) {
            $redirect_url = $account_type['Own_location']
            ? $account['Own_address'] . '.' . SEO_BASE
            : SEO_BASE . '/' . $account['Own_address'];
            Util::redirect($redirect_url);
        }

        $pInfo['calc'] = $rlListings->calc;
        $rlSmarty->assign_by_ref('pInfo', $pInfo);

        /* get amenties */
        if ($config['map_amenities']) {
            $rlDb->setTable('map_amenities');
            $amenities = $rlDb->fetch(array('Key', 'Default'), array('Status' => 'active'), "ORDER BY `Position`");
            $amenities = $rlLang->replaceLangKeys($amenities, 'map_amenities', array('name'));
            $rlSmarty->assign_by_ref('amenities', $amenities);
        }

        /* enable rss/xml listings feed for account */
        if ($listings) {
            /* build rss */
            $rss = array(
                'item'  => 'account-listings',
                'id'    => $account['Own_address'],
                'title' => str_replace('{name}', $account['Full_name'], $lang['account_rss_feed_caption']),
            );
            $rlSmarty->assign_by_ref('rss', $rss);
        }

        /* define fields for Google Map */
        $location = $rlAccount->mapLocation;

        if ($account['Loc_latitude'] && $account['Loc_longitude']) {
            $location['direct'] = $account['Loc_latitude'] . ',' . $account['Loc_longitude'];
        }

        if (!empty($location) && $config['map_module']) {
            $rlSmarty->assign_by_ref('location', $location);
        } else {
            unset($tabs['map']);
        }

        $rlHook->load('accountTypeAccount');
    }
    /* account search */
    else {
        /* clear saved data */
        if (!$_GET['nvar_1'] && !isset($_GET[$search_results_url])) {
            if ($_SESSION['at_data_' . $account_type_key]) {
                $_POST = $_SESSION['at_data_' . $account_type_key];
                unset($_SESSION['at_data_' . $account_type_key]);
            }
        }

        /* populate tabs */
        $tabs = array(
            'characters' => array(
                'key'  => 'characters',
                'name' => $lang['alphabetic_search'],
            ),
            'search'     => array(
                'key'  => 'search',
                'name' => $lang['advanced_search'],
            ),
        );
        $rlSmarty->assign_by_ref('tabs', $tabs);

        /* advanced search */
        $fields = $rlAccount->buildSearch($account_type['ID']);
        $rlSmarty->assign_by_ref('fields', $fields);

        /* alphabet bar */
        $alphabet = explode(',', $lang['alphabet_characters']);
        $alphabet = array_map('trim', $alphabet);
        $rlSmarty->assign_by_ref('alphabet', $alphabet);

        /* advanced search results */
        if ($_GET['nvar_1'] == $search_results_url || isset($_GET[$search_results_url])) {
            /* add link to nav bar */
            $return_link = SEO_BASE;
            $return_link .= $config['mod_rewrite'] ? $page_info['Path'] . '.html#modify' : '?page=' . $page_info['Path'] . '#modify';
            $navIcons[] = '<a title="' . $lang['modify_search_criterion'] . '" href="' . $return_link . '">&larr; ' . $lang['modify_search_criterion'] . '</a>';

            $rlSmarty->assign_by_ref('navIcons', $navIcons);

            /* add bread crumbs step */
            $bread_crumbs[] = array(
                'name' => $lang['search_results'],
            );

            $rlSmarty->assign('search_results', 'search');

            /* build sorting fields */
            $sorting = array_reverse($fields);
            $sorting['join_date'] = array(
                'Key'  => 'Date',
                'name' => $lang['join_date'],
            );
            $sorting = array_reverse($sorting);
            $rlSmarty->assign_by_ref('sorting', $sorting);

            /* get accounts */
            $data = $_SESSION['at_data_' . $account_type_key] = $_POST['f'] ? $_POST['f'] : $_SESSION['at_data_' . $account_type_key];
            $pInfo['current'] = (int) $_GET['pg'];

            // re-assign POST for refine search block
            if ($_POST['f']) {
                $_POST = $_POST['f'];
            }

            // simulate post method
            $listing_type['Submit_method'] = 'post';
            $rlSmarty->assign_by_ref('listing_type', $listing_type);

            /* define sort field */
            $sort_by = $_SESSION[$account_type_key . '_sort_by'] = $_REQUEST['sort_by'] ? $_REQUEST['sort_by'] : $_SESSION[$account_type_key . '_sort_by'];
            $sort_by = $sort_by ? $sort_by : 'join_date';
            if (!empty($sorting[$sort_by])) {
                $order_field = $sorting[$sort_by]['Key'];
                $data['sort_by'] = $sort_by;
                $rlSmarty->assign_by_ref('sort_by', $sort_by);
            }

            /* define sort type */
            $sort_type = $_SESSION[$account_type_key . '_sort_type'] = $_REQUEST['sort_type'] ? $_REQUEST['sort_type'] : $_SESSION[$account_type_key . '_sort_type'];
            $sort_type = $sort_type ? $sort_type : 'desc';
            if ($sort_type) {
                $data['sort_type'] = $sort_type = in_array($sort_type, array('asc', 'desc')) ? $sort_type : false;
                $rlSmarty->assign_by_ref('sort_type', $sort_type);
            }

            $dealers = $rlAccount->searchDealers($data, $fields, $config['dealers_per_page'], $pInfo['current'], $account_type);
            $rlSmarty->assign_by_ref('dealers', $dealers);

            $pInfo['calc'] = $rlAccount->calc;
            $rlSmarty->assign_by_ref('pInfo', $pInfo);

            // Change page name and title
            $page_info['name']  = str_replace(array('{number}'), array($pInfo['calc']), $lang['accounts_found']);
            $page_info['title'] = $lang['search_results'];
        } else {
            // define requested char
            if (RL_LANG_CODE == $config['lang']) {
                $_GET['nvar_1'] = $_GET['nvar_1'] === '0' || $_GET['listing_id'] === '9' ? '0-9' : $_GET['nvar_1'];
            } else {
                $_GET['nvar_1'] = $_GET['rlVareables'] === '0' && $_GET['listing_id'] === '9' ? '0-9' : $_GET['nvar_1'];
            }

            $char = in_array($_GET['nvar_1'], $alphabet) ? $_GET['nvar_1'] : $_REQUEST['character'];

            // Assume the language has changed and the char is from another alphabet - do redirect
            if (count($languages) > 1 && !$char && $_GET['nvar_1']) {
                $reefless->redirect(null, SEO_BASE . $page_info['Path'] . '.html');
            }

            $request_char = $char ? true : false;
            $rlSmarty->assign('alphabet_mode', $request_char);

            $char = $char ? $char : $alphabet[0];
            $rlSmarty->assign_by_ref('char', $char);

            // Wrong trailing slash redirect
            if ($config['mod_rewrite']
                && $char
                && !is_numeric(strpos($_SERVER['REQUEST_URI'], '?'))
                && !(bool) preg_match('/\\.html$/', $_SERVER['REQUEST_URI'])
            ) {
                $url = SEO_BASE . trim($_SERVER['REQUEST_URI'], '/') . '.html';

                $reefless->redirect(null, $url);
            }

            // Add the properly urls to account type page for other languages without character in url
            if ($request_char && count($languages) > 1) {
                $hreflang = [];

                foreach ($languages as $language) {
                    if ($language['Code'] !== RL_LANG_CODE) {
                        $hreflang[$language['Code']] = $reefless->getPageUrl(
                            $page_info['Key'],
                            null,
                            $language['Code']
                        );
                    }
                }

                $rlSmarty->assign('hreflang', $hreflang);
                unset($language, $hreflang);
            }

            if ($request_char) {
                $pInfo['current'] = (int) $_GET['pg'];
            }

            // generate sorting fields
            $sorting = array(
                'date'           => array(
                    'name'    => $lang['join_date'],
                    'Key'     => 'Date',
                    'default' => true,
                    'Type'    => 'custom',
                ),
                'alphabet'       => array(
                    'name' => $lang['dealer_name_a_z'],
                    'Key'  => 'alphabet',
                ),
                'listings_count' => array(
                    'name' => $lang['greater_listing_number'],
                    'Key'  => 'Listings_count',
                ),
            );

            $rlSmarty->assign_by_ref('sorting', $sorting);

            // define sort field
            $sort_by = $_SESSION['alphabet_sort_by'] = $_REQUEST['sort_by'] ? $_REQUEST['sort_by'] : $_SESSION['alphabet_sort_by'];
            if ($sorting[$sort_by]) {
                $rlSmarty->assign_by_ref('sort_by', $sort_by);
            } else {
                unset($sort_by);
            }

            // define sort type
            $sort_type = $_SESSION['alphabet_sort_type'] = $_REQUEST['sort_type'] ? $_REQUEST['sort_type'] : $_SESSION['alphabet_sort_type'];
            $sort_type = $sort_type ? $sort_type : 'desc';
            if (in_array($sort_type, array('asc', 'desc'))) {
                $rlSmarty->assign_by_ref('sort_type', $sort_type);
            } else {
                unset($sort_type);
            }

            // get dealers by requested char
            $alphabet_dealers = $rlAccount->getDealersByChar($char, $config['dealers_per_page'], $pInfo['current'], $account_type, $sorting, $sort_by, $sort_type);
            $rlSmarty->assign_by_ref('alphabet_dealers', $alphabet_dealers);

            $pInfo['calc_alphabet'] = $rlAccount->calc_alphabet;
            $rlSmarty->assign_by_ref('pInfo', $pInfo);

            if ($request_char && $alphabet[0] != $char) {
                $alp_title = str_replace('{char}', $char, $lang['search_by']);
                $page_info['name']   = $alp_title;
                $page_info['title'] .= ' | ' . $alp_title;

                $bread_crumbs[] = array(
                    'title' => $alp_title,
                    'name'  => $lang['alphabetic_search'],
                );
            }
        }

        $rlHook->load('accountTypeAccountsList');
    }
} else {
    $errors[] = $lang['account_type_page_access_restricted'];
}
