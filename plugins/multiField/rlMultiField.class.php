<?php


/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLMULTIFIELD.CLASS.PHP
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

class rlMultiField extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * @hook ajaxRequest
     * @since 2.0.0
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        if ($request_mode == 'mfNext') {
            $data = $this->getData($request_item);

            $out = array();
            $out['data'] = $data;
            $out['status'] = 'ok';
        }
    }

    /**
     * @deprecated 2.0.0 - Moved to rlGeoFilter
     **/
    public function geoAutocomplete($str = false, $lang = false) {}

    /**
     * @hook tplHeader
     * @since 2.0.0
     */
    public function hookTplHeader()
    {
        if ($this->isPageMf()) {
            echo "<script>lang['any'] = '" . $GLOBALS['lang']['any'] . "';</script>";
        }

        $GLOBALS['rlSmarty']->assign('mf_old_style', true);
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . "multiField" . RL_DS . "tplHeader.tpl");
    }

    /**
     * @hook tplFooter
     * @since 2.0.0
     */
    public function hookTplFooter()
    {
        global $page_info;

        // if (!$this->isPageMF()) {
        //     return false;
        // }

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . "multiField" . RL_DS . "tplFooter.tpl");
    }

    /**
     * @hook staticDataRegister
     * @since 2.0.0
     */
    public function hookStaticDataRegister()
    {
        if (!$this->isPageMF()) {
            return false;
        }

        global $rlStatic;
        $rlStatic->addJS(RL_PLUGINS_URL . 'multiField/static/lib.js');
    }

    /**
     * @hook pageinfoArea
     * @since 2.0.0
     */
    public function hookPageinfoArea()
    {
        global $page_info, $multi_formats;

        if ($this->isPageMf()) {
            $multi_formats = $this->getMultiFormats();

            $GLOBALS['rlSmarty']->assign('multi_formats', $multi_formats);
            $GLOBALS['rlSmarty']->assign('mf_form_prefix', $this->getPostPrefixByPage());
        }
    }

    /**
     * getPostPrefixByPage - return field inputs wrapper prefix f,account
     * @param string $page_controller
     * @since 2.0.0
     */
    private function getPostPrefixByPage($page_controller = null)
    {
        $page_controller = $page_controller ?: $GLOBALS['page_info']['Controller'];

        if (in_array($page_controller,
            array('add_listing', 'edit_listing', 'home', 'listing_type', 'search', 'listings_by_field',
                'compare_listings', 'recently_added', 'my_listings', 'account_type'))) {
            return 'f';
        }

        if (in_array($page_controller, array('registration', 'profile'))) {
            return 'account';
        }

        if (in_array($page_controller, array('search_map'))) {
            return '';
        }
    }

    /**
     * isPageMf - defines if there can be multiField stack on a page
     * @param string $page_controller
     * @since 2.0.0
     */
    private function isPageMf($page_controller = false)
    {
        $page_controller = $page_controller ?: $GLOBALS['page_info']['Controller'];

        if (in_array($page_controller,
            array('add_listing', 'edit_listing', 'home', 'listing_type', 'search', 'listings_by_field',
                'compare_listings', 'recently_added', 'my_listings', 'search_map')
        )
            || in_array($page_controller, array('registration', 'profile', 'account_type'))
        ) {
            return true;
        }

        return false;
    }

    /**
     * getMultiFormats
     * @since 2.0.0
     */
    private function getMultiFormats()
    {
        $sql = "SELECT `T1`.*, `T2`.`Order_type` FROM `{db_prefix}multi_formats` AS `T1` ";
        $sql .= "JOIN `{db_prefix}data_formats` AS `T2` ON `T2`.`Key` = `T1`.`Key` ";
        $sql .= "WHERE `T1`.`Status` = 'active'";

        $multi_formats = $GLOBALS['rlDb']->getAll($sql, 'Key');

        return $multi_formats;
    }

    /**
     * @hook tplListingFieldSelect
     * @since 2.0.0
     */
    public function hookTplListingFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield.tpl');
    }

    /**
     * @hook tplSearchFieldSelect
     * @since 2.0.0
     */
    public function hookTplSearchFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield.tpl');
    }

    /**
     * @hook tplRegFieldSelect
     * @since 2.0.0
     */
    public function hookTplRegFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield_account.tpl');
    }

    /**
     * @hook tplProfileFieldSelect
     * @since 2.0.0
     */
    public function hookTplProfileFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield_account.tpl');
    }

    /**
     * @hook adaptValueBottom
     * @since 2.0.0
     */
    public function hookAdaptValueBottom($param1, $param2, &$param3)
    {
        /*$param1 = $value; $param2 = $field, $out = param3;*/
        if (!$GLOBALS['lang']['data_formats+name+' . $param1] && strpos($param2['Key'], '_level') !== false) {
            $param3 = $GLOBALS['rlDb']->getOne('Value', "`Key` = 'data_formats+name+" . $param1 . "' AND `Code` = '" . RL_LANG_CODE . "'", 'lang_keys');

            if ($param3) {
                $GLOBALS['lang']['data_formats+name+' . $param1] = $param3;
            }
        }
    }

    /**
     * Get data by parent key or ID
     *
     * @since 2.0.0 - $order_type parameter added
     *
     * @param  int|string $parent     - Parent ID or Key
     * @param  bool       $get_path   - Include path data
     * @param  string     $order_type - Order type, 'alphabetic' or 'position'
     * @return array                  - Data array
     */
    public function getData($parent, $get_path = false, $order_type = null)
    {
        global $rlDb;

        $parent_id = is_int($parent)
        ? $parent
        : $rlDb->getOne("ID", "`Key` = '{$parent}'", "data_formats");

        if (!$parent_id) {
            return false;
        }

        $sql = "SELECT `T1`.`Key`, `T1`.`Default`, `T2`.`Value` as `name` ";

        if ($get_path) {
            $sql .=", `T1`.`Path` ";
        }

        $sql .= "FROM `{db_prefix}data_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`) ";

        if (defined('RL_LANG_CODE')) {
            $sql .= "AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        }

        $sql .= "WHERE `T1`.`Parent_ID` = {$parent_id} ";
        $sql .= "AND `T1`.`Status` = 'active' ";

        if ($order_type == 'alphabetic') {
            $sql .= "ORDER BY `T2`.`Value`";
        } elseif ($order_type == 'position') {
            $sql .= "ORDER BY `T1`.`Position`";
        }

        return $rlDb->getAll($sql);
    }

    /**
     * DEPRECATED: USE getData instead
     *
     * @param data mixed - data request (key,path,id)
     * @param get_path   - bool is path necessary or not
     * @param outputMap  - format of array
     */
    public function getMDF($data = false, $get_path = false, $outputMap = false)
    {}

    /**
     * install
     * @since 2.0.0
     */
    public function install()
    {
        global $rlDb;

        $rlDb->createTable(
            'multi_formats',
            "`ID` int(11) NOT NULL auto_increment,
            `Position` int(5) NOT NULL default '0',
            `Levels` INT( 11 ) DEFAULT '0',
            `Key` varchar(255) NOT NULL default '',
            `Default` varchar(255) NOT NULL default '',
            `Geo_filter` ENUM( '0', '1' ) default '0',
            `Status` enum('active','approval') NOT NULL default 'active',
            PRIMARY KEY  (`ID`),
            KEY `Parent_ID` (`Position`)",
            RL_DBPREFIX,
            'CHARSET=utf8;'
        );

        $rlDb->addColumnsToTable(array('Path' => "VARCHAR(255) NOT NULL AFTER `Key`"), 'data_formats');
        $rlDb->addColumnsToTable(array('Parent_IDs' => "VARCHAR(255) NOT NULL AFTER `Parent_ID`"), 'data_formats');

        $sql  = "SELECT GROUP_CONCAT(`ID`) as `ids` ";
        $sql .= "FROM `{db_prefix}pages` ";
        $sql .= "WHERE FIND_IN_SET(`Controller`, 'home,recently_added,listing_type')";
        $page_ids = $rlDb->getRow($sql, 'ids');

        $sql  = "UPDATE `{db_prefix}blocks` ";
        $sql .= "SET `Sticky` = '0', `Position` = 1, `Page_ID` = '{$page_ids}' ";
        $sql .= "WHERE `Key` = 'geo_filter_box' LIMIT 1";
        $rlDb->query($sql);

        $sql = "
            UPDATE `{db_prefix}config` SET `Group_ID` = 0 
            WHERE `Key` IN ('mf_db_version', 'mf_filtering_pages', 'mf_location_url_pages')
        ";
        $rlDb->query($sql);
    }

    /**
     * unInstall
     * @since 2.0.0
     */
    public function unInstall()
    {
        global $rlDb;

        $sql = "SELECT `T1`.*, `T2`.`ID` as `Data_Format_ID` FROM `{db_prefix}multi_formats` AS `T1` ";
        $sql .="LEFT JOIN `{db_prefix}data_formats` AS `T2` ON `T2`.`Key` = `T1`.`Key` ";
        $multi_formats = $rlDb->getAll($sql);

        if ($multi_formats) {
            $GLOBALS['reefless']->loadClass('MultiFieldAP', null, 'multiField');
            
            foreach ($multi_formats as $format) {
                $GLOBALS['rlMultiFieldAP']->deleteFormatChildFields($format['Key'], 'listing');
                $GLOBALS['rlMultiFieldAP']->deleteFormatChildFields($format['Key'], 'account');

                $sql ="UPDATE `{db_prefix}listing_fields` SET `Condition` = '' WHERE `Condition` = '{$format['Key']}'";
                $rlDb->query($sql);

                $sql ="UPDATE `{db_prefix}account_fields` SET `Condition` = '' WHERE `Condition` = '{$format['Key']}'";
                $rlDb->query($sql);

                if (!$format['Plugin']) {
                    $sql = "
                        DELETE `T1`, `T2` FROM `{db_prefix}data_formats` AS `T1`
                        RIGHT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('data_formats+name+', `T1`.`Key`) = `T2`.`Key`
                        WHERE `T1`.`ID` = {$format['Data_Format_ID']}
                        OR `T1`.`Parent_ID` = {$format['Data_Format_ID']}
                    ";
                    $rlDb->query($sql);
                }
            }

            $sql = "DELETE FROM `{db_prefix}data_formats` WHERE `Plugin` = 'multiField'";
            $rlDb->query($sql);

            $GLOBALS['rlCache']->updateDataFormats();
            $GLOBALS['rlCache']->updateForms();
        }
        
        $rlDb->dropTable('multi_formats');
        $rlDb->dropColumnsFromTable(array('Path'), 'data_formats');
    }

    /**
     * adaptCategories faceplate, to remove later.
     */
    public function adaptCategories($categories)
    {
        return $categories;

        if ($GLOBALS['rlGeoFilter']) {
            return $GLOBALS['rlGeoFilter']->adaptCategories($categories);
        } else {
            return $categories;
        }
    }

    /**
     * Get parents - get all parents of item
     *
     * @param string $key - key
     * @param array $parents - parents
     *
     * @return array
     **/
    public function getParents($key = false, $parents = false)
    {
        if (!$key) {
            return false;
        }

        $GLOBALS['reefless']->loadClass('MultiFieldAP', null, 'multiField');

        return $GLOBALS['MultiFieldAP']->getParents($key);
    }

    /**
     * Get Previous Field Key - define parent field key
     * @since 2.0.0
     *
     * @param  $field_key field key
     *
     * @return string
     */
    public function getPrevFieldKey($field_key)
    {
        preg_match("#([a-z0-9_-]+)_level([0-9])#", $field_key, $matches);

        if ($matches[1]) {
            if ($matches[2] == 1) {
                return $matches[1];
            } elseif ($matches[2] > 1) {
                return $matches[1] . '_' . $matches[2];
            } else {
                echo '4to to ne tak';
            }
        } else {
            return false;
        }
    }

    /**
     * @hook hookAddListingPreFields
     * 
     * @since 2.0.0
     */
    public function hookAddListingPreFields()
    {
        global $rlSmarty;

        $controller = $rlSmarty->_tpl_vars['manageListing']->controller;
        $singleStep = $rlSmarty->_tpl_vars['manageListing']->singleStep;

        if ($controller == 'edit_listing'
            || ($controller == 'add_listing' && !$singleStep)
            || isset($_POST['from_post'])
            || !$GLOBALS['multi_formats']
            || isset($_GET['edit'])
        ) {
            return;
        }

        echo '<script class="fl-js-dynamic">
                 $(function(){
                     var mfHandler = new mfHandlerClass();
                     mfHandler.init(mf_prefix, mfFields, mfFieldVals);
                 });
             </script>';
    }

    /**
     * Update to 1.0.2 version
     */
    public function update102()
    {
        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}config` SET `Group_ID` = 0
            WHERE `Key` = 'mf_cache_data_formats' LIMIT 1"
        );
    }

    /**
     * Update to 1.0.3 version
     */
    public function update103()
    {
        global $rlDb;

        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}data_formats` WHERE `Column_name` = 'Key'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}data_formats` ADD INDEX (`Key`)");
        }

        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}lang_keys` WHERE `Column_name` = 'Module'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}lang_keys` ADD INDEX (`Module`)");
        }
    }

    /**
     * Update to 1.2.0 version
     */
    public function update120()
    {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'phpListingsGetMyListings' AND `Plugin` = 'multiField'"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'myListingTop' AND `Plugin` = 'multiField'"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}config`
            WHERE `Key` = 'mf_cache_data_formats' LIMIT 1"
        );

        $rlDb->query(
            "UPDATE `{db_prefix}config` SET `Group_ID` = 0
            WHERE `Key` = 'mf_cache_data_formats_top_level' LIMIT 1"
        );

        $rlDb->query(
            "UPDATE `{db_prefix}config` SET `Group_ID` = 0
            WHERE `Key` = 'mf_cache_data_formats_multi_leveled' LIMIT 1"
        );
    }

    /**
     * Update to 1.2.1 version
     */
    public function update121()
    {
        $GLOBALS['rlDb']->query("UPDATE `{db_prefix}pages` SET `Geo_exclude` = '1' WHERE `Key` = 'view_details'");
    }

    /**
     * Update to 1.3.0 version
     */
    public function update130()
    {
        global $rlDb;

        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}data_formats` WHERE `Column_name` = 'Path'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}data_formats` ADD INDEX (`Path`)");
        }
    }

    /**
     * Update to 1.4.0 version
     */
    public function update140()
    {
        global $rlDb;

        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}data_formats` WHERE `Column_name` = 'Path'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}data_formats` ADD INDEX (`Path`)");
        }
    }

    /**
     * Update to 1.4.4 version
     */
    public function update144()
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'browseMiddle' AND `Plugin` = 'multiField'"
        );
    }

    /**
     * Update to 2.0.0 version
     */
    public function update200()
    {
        global $rlDb;

        // Migrate filtering config values
        $GLOBALS['reefless']->loadClass('MultiFieldAP', null, 'multiField');

        $rlDb->outputRowsMap = array(false, 'Key');

        $in_clause = implode("','", $GLOBALS['rlMultiFieldAP']->getAvailableControllers());
        $geo_pages = $rlDb->fetch(
            array('Key'),
            array('Geo_exclude' => 1),
            "AND `Controller` IN ('{$in_clause}') ORDER BY `Position`",
            NULL, 'pages'
        );

        if ($geo_pages) {
            $sql = "
                UPDATE `{db_prefix}config` SET `Default` = '" . implode(',', $geo_pages) . "'
                WHERE `Key` IN ('mf_filtering_pages', 'mf_location_url_pages')
            ";
            $rlDb->query($sql);
        }

        $rlDb->dropColumnFromTable('Geo_exclude', 'pages');
        $rlDb->addColumnToTable('Parent_IDs', "VARCHAR(255)", 'data_formats');

        // Remove legacy config
        $configs_to_be_removed = array(
            'mf_geo_levels_toshow',
            'mf_cache_client',
            'mf_cache_system',
            'mf_cache_data_formats_multi_leveled',
            'mf_cache_data_formats_top_level',
            'mf_geo_block_list',
            'mf_geo_columns',
            'mf_geo_cookie_lifetime',
            'mf_geo_subdomains_all',
            'mf_rebuild_cache',
            'mf_geo_multileveled',
            'mf_import_per_run',
        );

        $rlDb->query("
            DELETE FROM `{db_prefix}config`
            WHERE `Plugin` = 'multiField'
            AND `Key` IN ('" . implode("','", $configs_to_be_removed) . "')
        ");

        $rlDb->query("
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'multiField'
            AND `Key` IN ('config+name+" . implode("','config+name+", $configs_to_be_removed) . "')
        ");

        // Remove hooks
        $hooks_to_be_removed = array(
            'seoBase',
            'apPhpControlsBottom',
            'phpSubmitProfileEnd',
            'apPhpGetAccountFieldsEnd',
            'phpSmartyClassFetch',
            'apTplPagesForm',
            'apPhpPagesBeforeEdit',
            'apPhpPagesBeforeAdd',
            'apPhpPagesPost',
            'pageTitle'
        );
        $rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'multiField'
            AND `Name` IN ('" . implode("','", $hooks_to_be_removed) . "')
        ");

        // Ungroup configs
        $sql = "
            UPDATE `{db_prefix}config` SET `Group_ID` = 0
            WHERE `Key` IN ('mf_filtering_pages', 'mf_location_url_pages')
        ";
        $rlDb->query($sql);

        // Update position of configs
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 3 WHERE `Key` = 'mf_geo_autodetect'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 6 WHERE `Key` = 'mf_geo_block_autocomplete'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 7 WHERE `Key` = 'mf_geo_autocomplete_limit'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 10 WHERE `Key` = 'mf_geo_subdomains'");

        // Remove legacy files
        $files_to_be_removed = array(
            'geo_box_selectors.tpl',
            'static/jquery.geo_autocomplete.js',
            'static/style.css',
            'autocomplete.inc.php',
            'geo_block.tpl',
            'list_level.tpl',
            'mf_block.tpl',
            'mf_block_account.tpl',
            'mf_reg_js.tpl',
        );

        foreach ($files_to_be_removed as $file) {
            unlink(RL_PLUGINS . 'multiField/' . $file);
        }

        // remove unnecessary phrases
        $phrases = array(
            'mf_field',
            'mf_type',
            'mf_type_new',
            'mf_remove_items',
            'mf_import_progress',
            'mf_geo_path_nogeo',
            'mf_geo_select_location',
            'mf_geo_gobutton',
            'mf_geo_choose_location',
            'mf_geo_remove',
            'mf_collapse',
            'mf_expand',
            'mf_total',
            'mf_geo_show_other_items',
            'mf_geo_path_processing',
            'mf_cache_rebuilt',
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'multiField' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        // Remove index from `Path` field
        if ($rlDb->getRow("SHOW INDEXES FROM `{db_prefix}data_formats` WHERE `Column_name` = 'Path'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}data_formats` DROP INDEX `Path`");
        }

        // copy configuration of old location box to new "Location Filter" box
        $positionBox = $rlDb->getOne('Position', "`Key` = 'geo_filter_block'", 'blocks');
        $pageIDs     = $rlDb->getOne('Page_ID', "`Key` = 'geo_filter_block'", 'blocks');

        $rlDb->query(
            "UPDATE `{db_prefix}blocks`
            SET `Position` = {$positionBox}, `Page_ID` = '{$pageIDs}'
            WHERE `Key` = 'geo_filter_box'"
        );

        // remove old block from DB
        $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` LIKE 'geo_filter_block'");
        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE '%geo_filter_block'");

        // add new hook (Flynax 4.7.1 system cannot add/update hook in DB with same name and another class)
        if (!$rlDb->getOne(
            'ID',
            "`Name` = 'pageinfoArea' AND `Plugin` = 'multiField' AND `Class` = 'MultiField'",
            'hooks'
        )) {
            $rlDb->insertOne(
                array(
                    'Name'   => 'pageinfoArea',
                    'Class'  => 'MultiField',
                    'Plugin' => 'multiField',
                ),
                'hooks'
            );
        }

        if ($GLOBALS['config']['package_name'] === 'general') {
            // remove duplicates of rows
            $rlDb->query(
                "DELETE `{db_prefix}lang_keys` FROM `{db_prefix}lang_keys` INNER JOIN
                    (SELECT  MIN(ID) `MINID`, `Key`, `Module`, `Code`
                        FROM `{db_prefix}lang_keys`
                        GROUP BY `Key` HAVING COUNT(1) > 1) as Duplicates
                        ON (
                            Duplicates.`Key` = `{db_prefix}lang_keys`.`Key`
                            and Duplicates.`Module` = `{db_prefix}lang_keys`.`Module`
                            and Duplicates.`Code`   = `{db_prefix}lang_keys`.`Code`
                            and Duplicates.`MINID`  <> `{db_prefix}lang_keys`.ID
                        )"
            );
        }
    }
}
