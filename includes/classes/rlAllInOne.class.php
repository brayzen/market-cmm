<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLALLINONE.CLASS.PHP
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

use Flynax\Utils\Profile;

class rlAllInOne
{
    /**
    * @var int - vacancy category id
    **/
    private $vacancy_category_id = 105;

    /**
    * @var string - employer account type key
    **/
    private $employer_type_key = 'employer';

    /**
    * @var bool - job replaced configs store
    **/
    private $job_replaced_configs = false;

    /**
    * @since 4.5.1
    *
    * enable listing type photos option in grids if it's jobs
    *
    **/
    public function hookBrowseTop()
    {
        global $rlSmarty;

        if ($rlSmarty->_tpl_vars['listing_type']['Key'] == 'jobs') {
            $rlSmarty->_tpl_vars['listing_type']['Photo'] = true;
        }
    }

    /**
    * @since 4.5.1
    *
    * enable photos option for listing type and change orice tag to salary if it's jobs
    *
    **/
    public function hookListingTop()
    {
        global $rlSmarty;

        if ($rlSmarty->_tpl_vars['listing']['Listing_type'] == 'jobs') {
            // save configs
            $this->job_replaced_configs = array(
                'Photo' => $rlSmarty->_tpl_vars['listing_types']['jobs']['Photo'],
                'price_tag_field' => $rlSmarty->_tpl_vars['config']['price_tag_field'],
                'grid_photos_count' => $rlSmarty->_tpl_vars['config']['grid_photos_count']
            );

            // enable Photos option
            $rlSmarty->_tpl_vars['listing_types']['jobs']['Photo'] = true;

            // replace "price" field with "salary"
            $rlSmarty->_tpl_vars['config']['price_tag_field'] = 'salary';

            // disable "photos_count" option in grid
            $rlSmarty->_tpl_vars['config']['grid_photos_count'] = false;

            // replace "time frame" field with "pay period"
            if ($rlSmarty->_tpl_vars['listing']['fields']['pay_period']['value']) {
                $rlSmarty->_tpl_vars['listing']['sale_rent'] = 2;
                $rlSmarty->_tpl_vars['listing']['fields']['time_frame']['value'] = $rlSmarty->_tpl_vars['listing']['fields']['pay_period']['value'];
            }
        } elseif (is_array($this->job_replaced_configs)) {
            // restore configs
            $rlSmarty->_tpl_vars['listing_types']['jobs']['Photo'] = $this->job_replaced_configs['Photo'];
            $rlSmarty->_tpl_vars['config']['price_tag_field'] = $this->job_replaced_configs['price_tag_field'];
            $rlSmarty->_tpl_vars['config']['grid_photos_count'] = $this->job_replaced_configs['grid_photos_count'];
        }
    }

    /**
    * @since 4.5.1
    *
    * enable listing type photos option on my listings page if it's jobs
    *
    **/
    public function hookMyListingTop()
    {
        $GLOBALS['rlSmarty']->_tpl_vars['listing_types']['jobs']['Photo'] = true;
    }

    /**
    * @since 4.5.1
    *
    * enable listing type photos option in featured ad grids if it's jobs
    *
    **/
    public function hookFeaturedTop()
    {
        $GLOBALS['rlSmarty']->_tpl_vars['listing_types']['jobs']['Photo'] = true;
    }

    /**
    * @since 4.5.2
    *
    * replace "price" and "rent_time_frame" fields in featured ad grids if it's jobs
    *
    **/
    public function hookFeaturedItemTop()
    {
        global $rlSmarty;

        if ($rlSmarty->_tpl_vars['featured_listing']['Listing_type'] == 'jobs') {
            // replace "price" field with "salary"
            $GLOBALS['price_tag_field'] = $rlSmarty->_tpl_vars['config']['price_tag_field'];
            $rlSmarty->_tpl_vars['config']['price_tag_field'] = 'salary';

            // replace "time frame" field with "pay period"
            if ($rlSmarty->_tpl_vars['featured_listing']['fields']['pay_period']['value']) {
                $rlSmarty->_tpl_vars['featured_listing']['sale_rent'] = 2;
                $rlSmarty->_tpl_vars['featured_listing']['fields']['time_frame']['value'] = $rlSmarty->_tpl_vars['featured_listing']['fields']['pay_period']['value'];
            }
        }
    }

    /**
    * @since 4.5.2
    *
    * enable listing type photos option in featured ad grids if it's jobs
    *
    **/
    public function hookFeaturedItemBottom()
    {
        global $rlSmarty;
        
        if ($GLOBALS['price_tag_field']) {
            $rlSmarty->_tpl_vars['config']['price_tag_field'] = $GLOBALS['price_tag_field'];
            unset($GLOBALS['price_tag_field']);
        }
    }

    /**
    * @since 4.5.2
    *
    * replace "time frame" field with "pay period" on job details
    *
    **/
    public function hookListingDetailsTopTpl()
    {
        global $rlSmarty;

        if ($rlSmarty->_tpl_vars['listing_data']['Listing_type'] == 'jobs' && $rlSmarty->_tpl_vars['listing']['common']['Fields']['pay_period']['value']) {
            $rlSmarty->_tpl_vars['listing_data']['sale_rent'] = 2;
            $rlSmarty->_tpl_vars['listing']['common']['Fields']['time_frame']['value'] = $rlSmarty->_tpl_vars['listing']['common']['Fields']['pay_period']['value'];
        }
    }

    /**
    * @since 4.6.0
    *
    * set profile thumbnail as main ad photo for jobs ads after ad created
    *
    **/
    public function hookAfterListingDone(&$instance)
    {
        $this->setEmployerListingThumbnails($instance->listingID, $instance->listingType);
    }

    /**
     * Set profile thumbnail as main ad photo for jobs ads after user changed the profile image
     *
     * @since 4.7.0 - Second parameter changed from $main_photo to $data (now it can have x2 thumbnail too)
     * @since 4.5.1
     *
     * @param int    $id   - Requested account id
     * @param array  $data - Profile data which will be updated
     */
    public function hookAjaxRequestAccountThumbnailBeforeInsert($id, $data)
    {
        $this->updateEmployerListingThumbnails($id, $data['Photo'], $data['Photo_x2']);
    }

    /**
    * @since 4.5.1
    *
    * delete thumbnail from main ad photo for jobs ads after user removed the profile image
    *
    * @param array $update - update data from the related method
    *
    **/
    public function hookPhpAjaxDelProfileThumbnailBeforeUpdate(&$update)
    {
        $this->clearEmployerListingThumbnails($update['where']['ID']);
    }

     /**
    * @since 4.5.1
    *
    * change phrase values for all in one package
    *
    **/
    public function hookGetPhrase(&$params, &$phrase)
    {
        global $listing_data, $lang;

        // namespace filter
        switch ($params['key']) {
            case 'account_listings':
            case 'contact_owner':
                $category_ids = explode(',', $listing_data['Parent_IDs']);
                array_unshift($category_ids, $listing_data['Category_ID']);

                if (!in_array($this->vacancy_category_id, $category_ids)) {
                    return;
                }
                break;

            default:
                return;
                break;
        }

        // replace phrase key
        switch ($params['key']) {
            case 'account_listings':
                $phrase_key = 'company_jobs';
                break;

            case 'contact_owner':
                $phrase_key = 'apply_now';
                break;
        }

        if ($phrase_key) {
            $phrase = $lang[$phrase_key];
        }
    }

    /**
    * @since 4.5.1
    *
    * display attache resume field in "Apply Now" form
    *
    **/
    public function hookContactSellerFormAboveMessage()
    {
        global $listing_data, $account, $page_info;

        $category_ids = explode(',', $listing_data['Parent_IDs']);
        array_unshift($category_ids, $listing_data['Category_ID']);

        if ($page_info['Controller'] == 'listing_details' && !in_array($this->vacancy_category_id, $category_ids)) {
            return;
        } elseif ($page_info['Controller'] == 'account_type' && $account['Type'] != $this->employer_type_key) {
            return;
        }

        $GLOBALS['rlSmarty']->display('blocks' . RL_DS . 'attache_resume_file.tpl');
    }

    /**
    * @since 4.5.1
    *
    * save resume file
    *
    **/
    public function hookAjaxRequest(&$out, &$mode)
    {
        global $reefless;

        if ($mode != 'attachResume') {
            return;
        }

        $reefless->loadClass('Actions');

        $file_name = 'attached-resume-' . mt_rand();
        if ($file_name = $GLOBALS['rlActions']->upload('resume', $file_name)) {
            $_SESSION['last_attached_resume'] = $file_name;
            $out = array(
                'status' => 'OK',
                'results' => $file_name
            );
        } else {
            $GLOBALS['rlDebug']->logger('Unable to upload user resume file through the rlActions::upload');

            $out = array(
                'status' => 'ERROR',
                'message' => $GLOBALS['system_error']
            );
        }
    }

    /**
    * @since 4.5.1
    *
    * attache resume file
    *
    **/
    public function hookRlMessagesAjaxContactOwnerSend()
    {
        global $attach_file;

        if ($_SESSION['last_attached_resume']) {
            $attach_file = RL_FILES . $_SESSION['last_attached_resume'];
        }
    }

    /**
    * @since 4.5.1
    *
    * remove resume file
    *
    **/
    public function hookRlMessagesAjaxContactOwnerAfterSend()
    {
        if ($_SESSION['last_attached_resume']) {
            unlink(RL_FILES . $_SESSION['last_attached_resume']);
        }
    }

    /**
    * @since 4.5.1
    *
    * simulate the additional category box
    *
    **/
    function hookSimulateCatBlocks(&$blocks, &$categories, &$cat_blocks)
    {
        global $rlListingTypes;

        foreach ($cat_blocks as $side => $types) {
            if (count($types) > 1) {
                reset($types);
                $main_key = current($types);
                $new_categories = array();

                // add listing types as the first level of categories
                foreach ($types as $listing_type_key) {
                    $type_data = $rlListingTypes->types[$listing_type_key];
                    $type_data['sub_categories'] = array_values($categories[$listing_type_key]);
                    $type_data['pName'] = 'listing_types+name+' . $listing_type_key;

                    $type_data['Count'] = 0;
                    foreach ($type_data['sub_categories'] as $sub_cat) {
                        $type_data['Count'] += $sub_cat['Count'];
                    }

                    $new_categories[] = $type_data;
                    unset($type_data);
                }

                $categories[$main_key] = $new_categories;
                unset($new_categories);

                // fix box content
                if ($blocks['ltcb_' . $main_key]) {
                    $blocks['ltcb_' . $main_key]['Content'] = '{include file="blocks"|cat:$smarty.const.RL_DS|cat:"categories_block.tpl" box_listing_type_key=' . $main_key . '}';
                }
            }
        }
    }

    /**
    * @since 4.5.1
    *
    * simulate special configurations
    *
    **/
    function hookApMixConfigItem(&$param1)
    {
        global $rlDb, $lang, $rlLang;

        if ($param1['Key'] != 'realty_search_map_location_zoom')
            return;

        $param1['Values'] = array();

        foreach (range(1, 19) AS $item) {
            $set_name = $item;
            switch($item) {
                case 1:
                    $set_name = '1 (World)';
                    break;

                case 11:
                    $set_name = '11 (City)';
                    break;

                case 19:
                    $set_name = '19 (Street)';
                    break;
            }
            $param1['Values'][] = array('ID' => $item, 'name' => $set_name);
        }
    }

    /**
    * @since 4.5.1
    *
    * add special configuration views
    *
    **/
    function hookApTplContentBottom()
    {
        global $controller, $config;

        if ($controller != 'settings') return;

        if (!defined('AP_SETTINGS_GOOGLE_MAPS_INCLUDED')) {
            $js = '<script src="//maps.googleapis.com/maps/api/js?libraries=places';

            if (RL_LANG_CODE != '' && RL_LANG_CODE != 'en') {
                $js .= '&language='.RL_LANG_CODE;
            }
            if ($config['google_server_map_key']) {
                $js .= '&key=' . $config['google_server_map_key'];
            }
            $js .= '"></script>';
            echo $js;

            define('AP_SETTINGS_GOOGLE_MAPS_INCLUDED', true);
        }

        $GLOBALS['rlSmarty']->display(RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'ap.js.tpl');
    }

    /**
    * @since 4.5.1
    *
    * redefine configs before update
    *
    **/
    function hookApPhpConfigBeforeUpdate()
    {
        global $update;

        $set_value = $_POST['post_config']['realty_search_map_location_name']['value'] ? $_POST['search_map_default']['lat'].','.$_POST['search_map_default']['lng'] : '';
        $row['where']['Key'] = 'realty_search_map_location';
        $row['fields']['Default'] = $set_value;

        if ($set_value != $GLOBALS['config']['realty_search_map_location']) {
        array_push($update, $row);
    }
    }

    /**
    * @since 4.5.1
    *
    * redefine coordiantes in fields select
    *
    **/
    function hookListingsModifyFieldSearch(&$sql, &$data, &$type, &$form)
    {
        global $coordinates, $tpl_settings, $group_search;

        // keyword search statement
        if ($_POST['form'] == 'keyword_search') {
            // add keyword search field to the list array to allow keyword search form work out properly
            if (!$form['keyword_search']) {
                $form['keyword_search'] = array(
                    'Key' => 'keyword_search',
                    'Type' => 'text'
                );
            }
        }

        // search on map statement
        if (!defined('RL_SEARCH_ON_MAP')) {
            return;
        }

        $sql .= "ROUND(`T1`.`Loc_latitude`, 5) AS `Loc_latitude`, ROUND(`T1`.`Loc_longitude`, 5) AS `Loc_longitude`, ";

        if ($group_search) {
            return;
        }

        $sql .= "COUNT(*) AS `Group_count`, ";
    }

    /**
    * @since 4.5.1
    *
    * add where conditions
    *
    **/
    function hookListingsModifyWhereSearch(&$sql)
    {
        global $coordinates, $tpl_settings, $group_search, $group_lat, $group_lng;

        if (!defined('RL_SEARCH_ON_MAP')) return;

        if ($group_search) {
            $sql .= "AND (ROUND(`T1`.`Loc_latitude`, 5) = {$group_lat} AND ROUND(`T1`.`Loc_longitude`, 5) = {$group_lng})";
        } else {
            $sql .= "AND `T1`.`Loc_latitude` != 0 AND `T1`.`Loc_longitude` != 0 AND (`T1`.`Loc_latitude` BETWEEN {$coordinates['southWestLat']} AND {$coordinates['northEastLat']})";
            if ($coordinates['northEastLng'] < $coordinates['southWestLng']) {
                $sql .= "AND (`T1`.`Loc_longitude` BETWEEN {$coordinates['southWestLng']} AND 180 OR `T1`.`Loc_longitude` BETWEEN -180 AND {$coordinates['northEastLng']}) ";
            } else {
                $sql .= "AND (`T1`.`Loc_longitude` BETWEEN {$coordinates['southWestLng']} AND {$coordinates['northEastLng']}) ";
            }
        }
    }

    /**
    * @since 4.5.1
    *
    * add group statement
    *
    **/
    function hookListingsModifyGroupSearch()
    {
        global $sql, $group_search;

        if (!defined('RL_SEARCH_ON_MAP')) return;

        if ($group_search) return;

        if (false === strpos($sql, 'GROUP BY')) {
            $sql .= " GROUP BY `Loc_latitude`, `Loc_longitude` ";
        } else {
            $sql = str_replace("COUNT(*) AS `Group_count`, ", '', $sql);
        }
    }

    /**
    * @since 4.5.1
    *
    * disable default search form fetching for the home page
    *
    **/
    function hookPhpSearchBuildSearchGetRelations(&$sql)
    {
        global $tpl_settings;

        if (!$tpl_settings['home_page_map_search'])
          return;

        $dbt = debug_backtrace();
        if ($dbt[3]['function'] == 'getHomePageSearchForm') {
            $sql = "SELECT 1;";
        }
    }

    /**
    * @since 4.5.2
    *
    * sale/rent switcher in admin panel
    *
    **/
    function hookApTplFooter()
    {
        if ($_GET['controller'] == 'listings' && in_array($_GET['action'], array('add', 'edit'))) {
            $script = <<< VS
            <script>
            var apPropertyForHandler = function() {
                if ($('#sale_rent_table input:checked').val() == 2) {
                    $('#time_frame_table').closest('tr').fadeIn();
                } else {
                    $('#time_frame_table').closest('tr').fadeOut();
                    $('#time_frame_table input').removeAttr('checked');
                }
            }
            $(document).ready(function(){
                apPropertyForHandler();

                $('#sale_rent_table input').change(function(){
                    apPropertyForHandler();
                });
            });
            </script>
VS;
            echo $script;
        }
    }

    /**
    * @since 4.5.2
    *
    * add new option to the listing type
    *
    **/
    function hookApTplListingTypesFormSearch()
    {
        if ($GLOBALS['tpl_settings']['category_dropdown_search']) {
            $GLOBALS['rlSmarty']->display(RL_ADMIN . 'tpl' . RL_DS . 'blocks' . RL_DS . 'lt_category_search_option.tpl');
        }
    }

    /**
    * @since 4.5.2
    *
    * listing type option support
    *
    **/
    function hookApPhpListingTypesPost()
    {
        $_POST['category_search_dropdown'] = $GLOBALS['type_info']['Category_search_dropdown'];
    }

    /**
    * @since 4.5.2
    *
    * listing type option support
    *
    **/
    function hookApPhpListingTypesBeforeAdd()
    {
        $GLOBALS['data']['Category_search_dropdown'] = (int) $_POST['category_search_dropdown'];
    }

    /**
    * @since 4.5.2
    *
    * listing type option support
    *
    **/
    function hookApPhpListingTypesBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['Category_search_dropdown'] = (int) $_POST['category_search_dropdown'];
    }

    /**
    * @since 4.5.2
    *
    * collect listing types allowed for category search dropdown
    *
    **/
    function hookSeoBase()
    {
        foreach($GLOBALS['rlListingTypes']->types as &$type) {
            if ($type['Category_search_dropdown']) {
                $category_dropdown[] = $type['Key'];
            }
        }

        $GLOBALS['rlSmarty']->assign_by_ref('category_dropdown_types', $category_dropdown);
    }

    /**
    * @since 4.5.2
    *
    * update employer vacancies thumbnail on employer thumbnail update
    *
    **/
    function hookPhpEditProfileBeforeUpdate(&$data)
    {
        $account_id = (int) $_GET['account'];
        $main_photo = $data['fields']['Photo'];

        $this->updateEmployerListingThumbnails($account_id, $main_photo);
    }

    /**
     * Update thumbnail of the employer listings with type equals 'Jobs'
     *
     * @since 4.7.0 - Added $main_photo_x2 parameter
     * @since 4.5.2
     *
     * @param int    $id            - Employer account id
     * @param string $main_photo    - New main photo for listings
     * @param string $main_photo_x2 - New main photo for listings with x2 resolution
     */
    function updateEmployerListingThumbnails($id = 0, $main_photo = '', $main_photo_x2 = '')
    {
        $id = (int) $id;

        if (!$id || !$main_photo) {
            if (!$id) {
                $GLOBALS['rlDebug']->logger('No account id variable passed in AllInOne::' . __METHOD__);
            }

            return;
        }

        $sql = "UPDATE `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "SET `T1`.`Main_photo` = '{$main_photo}'";

        if ($GLOBALS['config']['thumbnails_x2'] && $main_photo_x2) {
            $sql .= ", `T1`.`Main_photo_x2` = '{$main_photo_x2}'";
        }

        $sql .= " WHERE `T1`.`Account_ID` = {$id} AND `T2`.`Type` = 'jobs'";

        $GLOBALS['rlDb']->query($sql);
    }

    /**
    * @since 4.5.2
    *
    * update employer vacancies thumbnail on employer thumbnail update
    *
    **/
    function hookPhpAjaxDelAccountFileBeforeUpdate(&$key, &$account_id)
    {
        if ($key != 'Photo') {
            return;
        }

        $this->clearEmployerListingThumbnails($account_id);
    }

    /**
    * Clear employer listing thumbnails
    *
    * Clear thumbnail of the employer listings with type equals 'Jobs' 
    *
    * @since 4.5.2
    *
    * @param int $account_id - employer account id
    *
    */
    function clearEmployerListingThumbnails(&$account_id)
    {
        if (!$account_id) {
            $GLOBALS['rlDebug']->logger('No account_id variable passed in AllInOne::' . __METHOD__);
            return;
        }

        $sql = "UPDATE `" . RL_DBPREFIX . "listings` AS `T1` ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "SET `T1`.`Main_photo` = '' ";
        $sql .= "WHERE `T1`.`Account_ID` = '{$account_id}' AND `T2`.`Type` = 'jobs'";

        $GLOBALS['rlDb']->query($sql);
    }

    /**
    * @since 4.5.2
    *
    * set employer vacancies listing thumbnail after listing has been created
    *
    **/
    function hookApPhpListingsAfterAdd()
    {
        $this->setEmployerListingThumbnails();
    }

    /**
     * Set thumbnail of the employer listings with type equals 'Jobs' after listing gas been created
     *
     * @since 4.5.2
     *
     * @param int   $listing_id
     * @param array $listing_type
     */
    public function setEmployerListingThumbnails($listing_id = 0, $listing_type = array())
    {
        global $account_info, $rlDb, $config;

        // AP mode
        if (!$listing_id) {
            global $listing_id, $listing_type;
        }

        $accountID = (int) $account_info['ID'];
        $photoData = Profile::getProfilePhotoData($accountID);

        if (!$listing_id || !$accountID || $listing_type['Key'] != 'jobs' || $listing_type['Photo']) {
            if ($listing_type['Key'] == 'jobs' && !$listing_id) {
                $GLOBALS['rlDebug']->logger('No enough data available to proceed the action in AllInOne::' . __METHOD__);
            }

            return;
        }

        $update = array(
            'fields' => array('Main_photo' => $photoData['Photo']),
            'where'  => array('ID'         => $listing_id)
        );

        if ($config['thumbnails_x2'] && $photoData['Photo_x2']) {
            $update['fields']['Main_photo_x2'] = $photoData['Photo_x2'];
        }

        $rlDb->updateOne($update, 'listings');
    }
}
