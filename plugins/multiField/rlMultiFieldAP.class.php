<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLMULTIFIELDAP.CLASS.PHP
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

use Flynax\Utils\Valid;

class rlMultiFieldAP
{
    /**
     * Admin directory path
     *
     * @since 2.0.0
     * @var string
     */
    public $adminDir = RL_PLUGINS . 'multiField' . RL_DS . 'admin' . RL_DS;

    /**
     * Controllers of pages allowed for geo filtering
     *
     * @since 2.0.0
     * @var array
     */
    private $availableControllers = array(
        'home',
        'listing_type',
        'account_type',
        'recently_added',
    );

    /**
     * Controllers of pages allowed for location fields predefine
     *
     * @since 2.0.0
     * @var array
     */
    private $predefineControllers = array(
        'search',
        'search_map',
        'add_listing',
        'registration',
    );

    /**
     * @hook apTplFieldsFormBottom
     * @since 2.0.0
     */
    public function hookApTplFieldsFormBottom()
    {
        if ($GLOBALS['disable_condition']) {
            echo '<script type="text/javascript">$(document).ready(function(){';
            echo "$('#dd_select_block').attr('disabled', 'disabled').addClass('disabled');";
            echo "$('#dd_select_block').after('<input ";
            echo 'type="hidden" name="data_format" value="' . $GLOBALS['disable_condition']['Condition'] . '"';
            echo "/>');";
            echo '})</script>';
        }
    }

    /**
     * @hook apPhpAccountFieldsTop
     * @since 2.0.0
     */
    public function hookApPhpAccountFieldsTop()
    {
        global $disable_condition;

        $sql = "SELECT `T1`.`Condition` FROM `{db_prefix}account_fields` AS `T1` ";
        $sql .= "JOIN `{db_prefix}multi_formats` AS `T2` ON `T2`.`Key` = `T1`.`Condition` ";
        $sql .= "WHERE `T1`.`Key` = '" . $_GET['field'] . "' AND `T1`.`Key` REGEXP 'level[0-9]'";

        $disable_condition = $GLOBALS['rlDb']->getRow($sql);
    }

    /**
     * @hook apPhpListingFieldsBeforeEdit
     * @since 2.0.0
     */
    public function hookApPhpListingFieldsBeforeEdit()
    {
        global $f_data, $rlDb;

        $f_data['Key'] = $GLOBALS['e_key'];

        $current_format = $rlDb->getOne("Condition", "`Key` = '" . $f_data['Key'] . "'", 'listing_fields');

        $old_multi = $rlDb->getOne("Key", "`Key` = '" . $current_format . "'", 'multi_formats');
        $new_multi = $rlDb->getOne("Key", "`Key` = '" . $f_data['data_format'] . "'", 'multi_formats');

        if ($new_multi && !$old_multi) {
            $this->createSubFields($f_data, 'listing');
        } elseif ($old_multi && !$new_multi) {
            $this->deleteSubFields($f_data, 'listing');
        } elseif ($old_multi && $new_multi && $old_multi != $new_multi) {
            $this->deleteSubFields($f_data, 'listing');
            $this->createSubFields($f_data, 'listing');
        }
    }

    /**
     * @hook apPhpAccountFieldsBeforeEdit
     * @since 2.0.0
     */
    public function hookApPhpAccountFieldsBeforeEdit()
    {
        global $f_data, $rlDb;

        $f_data['Key'] = $GLOBALS['e_key'];

        $current_format = $rlDb->getOne("Condition", "`Key` = '" . $f_data['Key'] . "'", 'account_fields');

        $old_multi = $rlDb->getOne("Key", "`Key` = '" . $current_format . "'", 'multi_formats');
        $new_multi = $rlDb->getOne("Key", "`Key` = '" . $f_data['data_format'] . "'", 'multi_formats');

        if ($new_multi && !$old_multi) {
            $this->createSubFields($f_data, 'account');
        } elseif ($old_multi && !$new_multi) {
            $this->deleteSubFields($f_data, 'account');
        } elseif ($old_multi && $new_multi && $old_multi != $new_multi) {
            $this->deleteSubFields($f_data, 'account');
            $this->createSubFields($f_data, 'account');
        }
    }

    /**
     * @hook apPhpFieldsAjaxDeleteAField
     * @since 2.0.0
     */
    public function hookApPhpFieldsAjaxDeleteAField()
    {
        global $id;
        if ($id) {
            $key = $GLOBALS['rlDb']->getOne('Key', "`ID` = {$id}", 'account_fields');
            $this->deleteFieldChildFields($key, 'account');
        }
    }

    /**
     * @hook apPhpListingFieldsTop
     * @since 2.0.0
     */
    public function hookApPhpListingFieldsTop()
    {
        global $disable_condition;

        $sql = "SELECT `T1`.`Condition` FROM `{db_prefix}listing_fields` AS `T1` ";
        $sql .= "JOIN `{db_prefix}multi_formats` AS `T2` ON `T2`.`Key` = `T1`.`Condition` ";
        $sql .= "WHERE `T1`.`Key` = '" . $_GET['field'] . "' AND `T1`.`Key` REGEXP 'level[0-9]' ";

        $disable_condition = $GLOBALS['rlDb']->getRow($sql);
    }

    /**
     * @hook tplApPhpFieldsAjaxDeleteField
     * @since 2.0.0
     */
    public function hookApPhpFieldsAjaxDeleteField()
    {
        global $field;

        if (!$field['Key'] && $field['ID']) {
            $field['Key'] = $GLOBALS['rlDb']->getOne('Key', "`ID` = {$field['ID']}", 'listing_fields');
        }
        $this->deleteFieldChildFields($field['Key'], 'listing');
    }

    /**
     * @hook apPhpDataFormatsBottom
     * @since 2.0.0
     */
    public function hookApPhpDataFormatsBottom()
    {
        if ($GLOBALS['rlDb']->getOne("Key", "`Key` = '" . $_GET['format'] . "'", 'multi_formats') && $_GET['mode'] == 'manage') {
            $GLOBALS['reefless']->redirect(array("controller" => 'multi_formats', 'parent' => $_GET['format']));
        }
    }

    /**
     * @hook apPhpFormatsAjaxDeleteFormatPreDelete
     * @since 2.0.0
     */
    public function hookApPhpFormatsAjaxDeleteFormatPreDelete()
    {
        global $rlDb;

        $format_id =  $GLOBALS['id'];
        $format_key = Valid::escape($_POST['xjxargs'][0]);

        if ($GLOBALS['config']['trash']) {
            return;
        }

        if (!$rlDb->getOne('ID', "`Key` = '{$format_key}'", 'multi_formats')) {
            return;
        }

        if (!$format_id && is_string($format_key)) {
            $format_id = $rlDb->getOne('ID', "`Key` = '{$format_key}'", "data_formats");
        }

        $this->deleteDF($format_id);
    }

    /**
     * @hook apPhpFormatsAjaxDeleteFormat
     * @since 2.0.2
     */
    public function hookApPhpFormatsAjaxDeleteFormat()
    {
        global $rlDb;

        if (!$GLOBALS['config']['trash']) {
            return;
        }

        $format_id =  $GLOBALS['id'];
        $format_key = Valid::escape($_POST['xjxargs'][0]);

        if (!$rlDb->getOne('ID', "`Key` = '{$format_key}'", 'multi_formats')) {
            return;
        }

        $rlDb->updateOne(array(
            'fields' => array(
                'Zones' => 'data_formats,lang_keys,multi_formats'
            ),
            'where' => array(
                'Zones' => 'data_formats,lang_keys',
                'Key' => $format_key
            )
        ), 'trash_box');
    }

    /**
     * @hook apPhpAccountsTop
     * @since 2.0.0
     */
    public function hookApPhpAccountsTop()
    {
        global $multi_formats, $rlSmarty;

        $multi_formats = $this->getMultiFormats();

        $rlSmarty->assign('multi_formats', $multi_formats);
        $rlSmarty->assign('mf_form_prefix', $this->getPostPrefixByPageAp());
        $rlSmarty->assign('mf_old_style', true);
    }

    /**
     * @hook apPhpAccountsTop
     * @since 2.0.0
     */
    public function hookApPhpListingsTop()
    {
        global $multi_formats, $rlSmarty;

        $multi_formats = $this->getMultiFormats();

        $rlSmarty->assign('multi_formats', $multi_formats);
        $rlSmarty->assign('mf_form_prefix', $this->getPostPrefixByPageAp());
        $rlSmarty->assign('mf_old_style', true);
    }

    /**
     * @hook apTplHeader
     * @since 2.0.0
     */
    public function hookApTplHeader()
    {
        if (!$this->isPageMfAp()) {
            return false;
        }

        $GLOBALS['rlSmarty']->display($this->adminDir . 'tplHeader.tpl');
    }

    /**
     * @hook apPhpSubmitProfileEnd
     * @since 2.0.0
     */
    public function hookApPhpSubmitProfileEnd()
    {
        $multi_formats = $this->getMultiFormats();
        $fields = $GLOBALS['rlSmarty']->_tpl_vars['fields'];
        $js = '';

        foreach ($fields as $field) {
            if ($multi_formats[$field['Condition']]) {
                $js .= <<< JAVASCRIPT
                if (mfFields.indexOf('{$field['Key']}') < 0) {
                    mfFields.push('{$field['Key']}');
                }
JAVASCRIPT;
            }
        }

        if ($js) {
            global $_response;

            $_response->script($js);
            $_response->script("
                var mfHandler = new mfHandlerClass();
                mfHandler.init('f', mfFields, []);
            ");
        }
    }

    /**
     * @hook apAjaxLangExportSelectPhrases - exclude multiField plugin from exporting to avoid many location data exported
     * @since 2.0.0
     *
     * @param   $select      - select fields array that will be passed to rlDb->fetch function
     * @param   $where       - where array to modify
     * @param   $extra_where - extra where string
     */
    public function hookApAjaxLangExportSelectPhrases($select = array(), $where = '', &$extra_where = '')
    {
        $extra_where .= "AND `Plugin` != 'multiField'";
    }

    /**
     * @hook apTplAccountFieldSelect
     * @since 2.0.0
     */
    public function hookApTplAccountFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield_account.tpl');
    }

    /**
     * @hook apTplListingFieldSelect
     * @since 2.0.0
     */
    public function hookApTplListingFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield.tpl');
    }

    /**
     * @hook apPhpListingFieldsAfterAdd
     * @since 2.0.0
     */
    public function hookApPhpListingFieldsAfterAdd()
    {
        global $f_data;

        $f_data['Key'] = $GLOBALS['f_key'];
        $this->createSubFields($f_data, 'listing');
    }

    /**
     * @hook apPhpAccountFieldsAfterAdd
     * @since 2.0.0
     */
    public function hookApPhpAccountFieldsAfterAdd()
    {
        global $f_data, $f_key;

        $f_data['Key'] = $f_key;

        $this->createSubFields($f_data, 'account');
    }

    /**
     * @hook apTplFooter
     * @since 2.0.0
     */
    public function hookApTplFooter()
    {
        global $rlSmarty;

        if ($_GET['controller'] == 'settings') {
            $rlSmarty->display($this->adminDir . 'refreshEntry.tpl');

            $rlSmarty->assign('mf_geo_filter', $this->geoFilterEnabled());
            $rlSmarty->assign('mf_predefine_controllers', $this->predefineControllers);
            $rlSmarty->assign('mf_available_pages', $this->getAvailablePages());
            $rlSmarty->assign('mf_group_id', $GLOBALS['rlDb']->getOne('ID', "`Plugin` = 'multiField'", 'config_groups'));
            $rlSmarty->display($this->adminDir . 'settings.tpl');
        }

        if (!$this->isPageMFAp()) {
            return false;
        }

        $rlSmarty->display($this->adminDir . 'tplFooter.tpl');
    }

    /**
     * @hook apPhpConfigBeforeUpdate
     * @since 2.0.0
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $rlConfig;

        if (isset($_POST['a_config'])) {
            $filtration_page_keys = array();
            $location_url_keys = array();
            foreach ($_POST['mf_config'] as $mf_key => $mf_value) {
                if ($mf_value['filtration']) {
                    $filtration_page_keys[] = $mf_key;
                }
                if ($mf_value['url']) {
                    $location_url_keys[] = $mf_key;
                }
            }
            
            $rlConfig->setConfig('mf_filtering_pages', implode(',', $filtration_page_keys));
            $rlConfig->setConfig('mf_location_url_pages', implode(',', $location_url_keys));
        }
    }

    /**
     * @hook apPhpConfigBottom
     * @since 2.0.0
     */
    public function hookApPhpConfigBottom()
    {
        global $config, $rlSmarty;

        $rlSmarty->assign('mf_filtering_pages', explode(',', $config['mf_filtering_pages']));
        $rlSmarty->assign('mf_location_url_pages', explode(',', $config['mf_location_url_pages']));
    }

    /**
     * @hook apPhpCategoriesBottom
     * @since 2.0.0
     */
    public function hookApPhpCategoriesBottom()
    {
        if ($_GET['action'] != 'edit' || !$this->geoFilterEnabled()) {
            return;
        }

        global $fields, $rlDb;

        $format = $this->getGeoFilterFormat();

        $rlDb->outputRowsMap = array(false, 'Key');
        $format_fields = $rlDb->fetch(
            array('Key'),
            array('Condition' => $format['Format_key']),
            "ORDER BY `Key`",
            null, 'listing_fields'
        );

        if (!$format_fields) {
            return;
        }

        $replace  = 'if location_levelN}{location_levelN}{/if';
        $level    = 1;

        foreach ($fields as &$field) {
            if (in_array($field['Key'], $format_fields)) {
                $field['Key'] = str_replace('N', $level, $replace);
                $field['Order'] = $level + 1;
                $level++;
            } else {
                $field['Order'] = 0;
            }
        }

        // Add common location field
        $fields[] = array(
            'name' => $GLOBALS['lang']['mf_geo_location'],
            'Key' => 'if location}{location}{/if',
            'Order' => 1,
        );

        $GLOBALS['reefless']->rlArraySort($fields, 'Order');

        // Re-assign fields
        $GLOBALS['rlSmarty']->assign('fields', $fields);
    }

    /**
     * Add page to the list of pages available for the data filtering
     * @since 2.0.0
     * @param string $controller - page controller key
     */
    public function addAvailablePage($controller)
    {
        $this->availableControllers[] = $controller;
    }

    /**
     * Get available geo filter data format data
     *
     * @since 2.0.0
     * @return array - format data
     */
    public function getGeoFilterFormat()
    {
        $sql = "
            SELECT `T2`.*, `T1`.`ID` AS `Format_ID`, `T2`.`Key` AS `Format_key`
            FROM `{db_prefix}data_formats` AS `T1`
            JOIN `{db_prefix}multi_formats` AS `T2` ON `T2`.`Key` = `T1`.`Key`
            WHERE `T2`.`Geo_filter` = '1' AND `T2`.`Status` = 'active'
            LIMIT 1
        ";

        return $GLOBALS['rlDb']->getRow($sql);
    }

    /**
     * Get default page controllers list
     * @since 2.0.0
     * @return array - page controllers
     */
    public function getAvailableControllers()
    {
        return $this->availableControllers;
    }

    /**
     * Get avaialble for the filtering pages list
     * @since 2.0.0
     * @return array - pages data
     */
    private function getAvailablePages()
    {
        $GLOBALS['rlHook']->load('apPhpMultifieldGetAvailablePages', $this);

        $order = array_merge($this->availableControllers, $this->predefineControllers);

        $sql = "
            SELECT `ID`, `Key`, `Controller` FROM `{db_prefix}pages`
            WHERE `Status` != 'trash'
            AND (
                `Controller` IN ('" . implode("','", $this->availableControllers) . "')
                OR (
                    `Controller` IN ('" . implode("','", $this->predefineControllers) . "')
                    AND `Plugin` = ''
                )
            )
            ORDER BY FIND_IN_SET(`Controller`, '" . implode(',', $order) . "')
        ";
        $pages = $GLOBALS['rlDb']->getAll($sql);

        return $pages;
    }

    /**
     * isPageMfAp - defines if there can be multiField stack on a page
     * @param string $page_controller
     * @since 2.0.0
     */
    private function isPageMfAp($page_controller = false)
    {
        $page_controller = $page_controller ?: $_GET['controller'];

        if ((in_array($page_controller, array('listings', 'accounts'))
                && in_array($_GET['action'], array('add', 'edit'))
            ) || $page_controller === 'settings'
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getPostPrefixByPage - return field inputs wrapper prefix f,account in admin panel
     * @param string $page_controller
     * @since 2.0.0
     */
    private function getPostPrefixByPageAp($page_controller = false)
    {
        $page_controller = $page_controller ?: $_GET['controller'];

        if (in_array($page_controller,
            array('listings'))) {
            return 'f';
        }

        if (in_array($page_controller, array('accounts'))) {
            return 'f';
        }
    }

    /**
     * get parents - get all parents of item
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

        $GLOBALS['rlValid']->sql($key);

        $sql = "SELECT `T2`.`Key`, `T2`.`Parent_ID` FROM `{db_prefix}data_formats` AS `T1` ";
        $sql .= "JOIN `{db_prefix}data_formats` AS `T2` ON `T1`.`Parent_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Key` = '{$key}' LIMIT 1";
        $parent = $GLOBALS['rlDb']->getRow($sql);

        if ($parent['Parent_ID'] == 0 && $parent['Key']) {
            return $parents;
        } else {
            $parents[] = $parent['Key'];
            return $this->getParents($parent['Key'], $parents);
        }
    }

    /**
     * get level of item
     *
     * @param int $id - id
     * @param int $level - level
     *
     * @return int
     **/
    public function getLevel($id, $level = 0)
    {
        $id = (int) $id;

        if (!$id) {
            return false;
        }

        $parent = $GLOBALS['rlDb']->getOne("Parent_ID", "`ID` = {$id}", "data_formats");

        if ($parent) {
            $level++;
            return $this->getLevel($parent, $level);
        } else {
            return $level;
        }
    }

    /**
     * get total levels of the format
     *
     * @param int $id - id
     * @param int $levels - levels
     *
     * @return int
     **/
    public function getLevels($id, $update_db = false, $head_key = '')
    {
        global $rlDb;

        $head = $head_key ?: $this->getHead($id);

        if ($update_db) {
            $head_id = $rlDb->getOne("ID", "`Key` = '{$head}'", "data_formats");

            $sql = "SELECT `ID` FROM `{db_prefix}data_formats` ";
            $sql .= "WHERE `Key` LIKE '{$head}\_%' ORDER BY `ID` DESC LIMIT 1";

            $deepest_id = $rlDb->getRow($sql, 'ID');
            $loc_id = $deepest_id;

            $levels = 0;
            while ($loc_id && ($loc_id == $deepest_id || $loc_id > $head_id) && $levels < 10) {
                $loc_id = $rlDb->getOne("Parent_ID", "`ID` = {$loc_id}", "data_formats");
                $levels++;
            }

            $sql = "UPDATE `{db_prefix}multi_formats` ";
            $sql .= "SET `Levels` = '{$levels}' WHERE `Key` = '{$head}'";

            $rlDb->query($sql);

            return $levels;
        } else {
            return $rlDb->getOne("Levels", "`Key` = '{$head}'", "multi_formats");
        }
    }

    /**
     * get top level element key of the data/multi format
     *
     * @param int $id - id
     * @param string $key - key
     *
     * @return string
     **/
    public function getHead($id, $key = '')
    {
        global $rlDb;

        if (!$id && !$key) {
            return false;
        }

        $id = (int) $id;
        $GLOBALS['rlValid']->sql($key);

        if ($id) {
            $parent = $rlDb->getOne("Parent_ID", "`ID` = {$id}", "data_formats");
        } elseif ($key) {
            $parent = $rlDb->getOne("Parent_ID", "`Key`='{$key}'", "data_formats");
        } else {
            return false;
        }

        if ($parent) {
            return $this->getHead($parent);
        } else {
            return $rlDb->getOne("Key", "`ID` = {$id}", "data_formats");
        }
    }

    /**
     * create sub fields
     *
     * @param array $field_info - field info
     * @param string $type - type
     *
     **/
    public function createSubFields($field_info, $type = 'listing')
    {
        global $rlDb;

        if (strpos($field_info['Key'], 'level') || !$field_info['Key']) {
            return false;
        }

        $format_id = $rlDb->getOne("ID", "`Key` = '" . $field_info['data_format'] . "'", 'data_formats');
        $head_field_key = $field_info['Key'];

        if (!$format_id) {
            return false;
        }

        $languages = $GLOBALS['languages'] ?: $GLOBALS['rlLang']->getLanguagesList();

        $levels = $this->getLevels($format_id);

        if ($levels < 2) {
            return false;
        }

        for ($level = 1; $level < $levels; $level++) {
            $field_key = $head_field_key . "_level" . $level;
            $prev_fk = $level == 1 ? $head_field_key : ($head_field_key . "_level" . ($level - 1));

            $rlDb->addColumnToTable($field_key, "VARCHAR(255) NOT NULL AFTER `{$prev_fk}`", $type . 's');

            $sql = "SELECT `Key` FROM `{db_prefix}{$type}_fields` ";
            $sql .= "WHERE `Key` = '{$field_key}'";
            $field_exists = $rlDb->getRow($sql);

            if (!$field_exists) {
                $field_insert_info = array(
                    'Key'       => $field_key,
                    'Condition' => $field_info['data_format'],
                    'Type'      => 'select',
                    'Status'    => 'active',
                );

                if ($type == 'listing') {
                    $field_insert_info['Add_page'] = 1;
                    $field_insert_info['Details_page'] = 1;
                    $field_insert_info['Readonly'] = 1;
                }

                preg_match('/country|location|state|region|province|address/i', $head_field_key, $match);
                if ($match) {
                    $field_insert_info['Map'] = 1;
                }

                $rlDb->insertOne($field_insert_info, $type . "_fields");

                $field_id = $rlDb->getOne('ID', "`Key` = '{$field_insert_info['Key']}'", $type . "_fields");
                //$field_id = $rlDb->insertID();

                if ($type == 'listing') {
                    $prev_field_id = $rlDb->getOne("ID", "`Key` = '{$prev_fk}'", 'listing_fields');

                    $sql = "UPDATE `{db_prefix}listing_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$prev_field_id},', ',{$prev_field_id},{$field_id},'))) WHERE FIND_IN_SET('{$prev_field_id}', `Fields`) ";
                    $rlDb->query($sql);

                    $sql = "UPDATE `{db_prefix}search_forms_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$prev_field_id},', ',{$prev_field_id},{$field_id},'))) WHERE FIND_IN_SET('{$prev_field_id}', `Fields`) ";
                    $rlDb->query($sql);
                } elseif ($type == 'account') {
                    $prev_field_id = $rlDb->getOne("ID", "`Key` = '{$prev_fk}'", 'account_fields');

                    $sql = "SELECT `Category_ID`, `Position`, `Group_ID` ";
                    $sql .= "FROM `{db_prefix}account_submit_form` ";
                    $sql .= "WHERE `Field_ID` ={$prev_field_id}";
                    $afields = $rlDb->getAll($sql);

                    foreach ($afields as $afk => $afield) {
                        $sql = "UPDATE `{db_prefix}account_submit_form` ";
                        $sql .= "SET `Position` = `Position`+1 ";
                        $sql .= "WHERE `Position` > {$afield['Position']} ";
                        $sql .= "AND `Category_ID` = {$afield['Category_ID']} ";
                        $rlDb->query($sql);

                        $insert[$afk]['Position'] = $afield['Position'] + 1;
                        $insert[$afk]['Category_ID'] = $afield['Category_ID'];
                        $insert[$afk]['Group_ID'] = $afield['Group_ID'];
                        $insert[$afk]['Field_ID'] = $field_id;
                    }
                    $rlDb->insert($insert, 'account_submit_form');
                }

                $head_field_lkey = $type . '_fields+name+' . $head_field_key;

                $lang_keys = array();
                foreach ($languages as $key => $lang_item) {
                    $head_field_name = $rlDb->getOne("Value", "`Key` ='{$head_field_lkey}' AND `Code` = '{$lang_item['Code']}'", "lang_keys");

                    $lang_keys[] = array(
                        'Code'   => $lang_item['Code'],
                        'Module' => 'common',
                        'Key'    => $type . '_fields+name+' . $field_key,
                        'Value'  => $head_field_name . " Level " . $level,
                        'Plugin' => 'multiField',
                    );
                }
                $rlDb->insert($lang_keys, 'lang_keys');
            }
        }

        $GLOBALS['rlCache']->updateForms();
    }

    /**
     * delete sub fields
     *
     * @param array $field_info - field info
     * @param string $type - type
     **/
    public function deleteSubFields($field_info, $type = 'listing')
    {
        global $rlDb;

        if (strpos($field_info['Key'], 'level')) {
            return false;
        }

        $field_key = $field_info['Key'];

        if (!$field_key) {
            return false;
        }

        $old_format = $rlDb->getOne("Condition", "`Key` = '{$field_key}'", $type . '_fields');

        $sql = "SELECT * FROM `{db_prefix}listing_fields` ";
        $sql .= "WHERE `Condition` = '{$old_format}' AND `Key` REGEXP '{$field_key}_level[0-9]'";
        $fields = $rlDb->getAll($sql);

        if (!$fields) {
            $sql = "SHOW FIELDS FROM `{db_prefix}{$type}s` WHERE `Field` REGEXP '{$field_key}_level[0-9]'";
            $fields_struct = $rlDb->getAll($sql);

            foreach ($fields_struct as $key => $field) {
                $rlDb->dropColumnFromTable($field['Field'], $type . 's');
            }
        }

        foreach ($fields as $key => $field) {
            $rlDb->dropColumnFromTable($field['Key'], $type . 's');

            if ($type == 'listing') {
                $sql = "UPDATE `{db_prefix}listing_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$field['ID']},', ','))) WHERE FIND_IN_SET('{$field['ID']}', `Fields`) ";
                $rlDb->query($sql);

                $sql = "UPDATE `{db_prefix}search_forms_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$field['ID']},', ','))) WHERE FIND_IN_SET('{$field['ID']}', `Fields`) ";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}short_forms` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);
            } elseif ($type == 'account') {
                $sql = "DELETE FROM `{db_prefix}account_search_relations` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}account_short_form` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}account_submit_form` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);
            }
        }

        $sql = "DELETE `T1`, `T2` FROM `{db_prefix}{$type}_fields` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('{$type}_fields+name+', `T1`.`Key`) ";
        $sql .= "WHERE `T1`.`Condition` = '{$old_format}' AND `T1`.`Key` REGEXP '{$field_key}_level[0-9]'";
        $rlDb->query($sql);

        $GLOBALS['rlCache']->updateForms();
    }

    /**
     * add format item
     *
     * @package ajax
     *
     * @param string $key        - key
     * @param array  $names      - names
     * @param string $status     - status
     * @param string $parent_key - parent key
     **/
    public function ajaxAddItem($key, $names, $status, $parent_key, $path = '', $subdomain_path = false)
    {
        global $_response, $lang, $insert, $rlValid, $rlDb;

        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        $key = utf8_is_ascii($key) ? $key : utf8_to_ascii($key);
        $item_key = $rlValid->str2key($key);
        $rlValid->sql($parent_key);

        /* check key */
        if (strlen($item_key) < 3) {
            $errors[] = $lang['incorrect_phrase_key'];
        }

        $item_key = $parent_key . '_' . $item_key;

        if (!utf8_is_ascii($item_key)) {
            $errors[] = $lang['key_incorrect_charset'];
        }

        $key_exist = $rlDb->getOne('ID', "`Key` = '{$item_key}'", 'data_formats');
        if (!empty($key_exist)) {
            $errors[] = str_replace('{key}', "<b>{$item_key}</b>", $lang['notice_item_key_exist']);
        }

        $parent_info = $rlDb->fetch('*', array('Key' => $parent_key), null, null, 'data_formats', 'row');
        $parent_id = $parent_info['ID'];

        $this->getLevels($parent_id, true);

        /*check path*/
        $head = $this->getHead($parent_id);

        $geo_filter = $rlDb->getOne("Geo_filter", "`Key` = '{$head}'", 'multi_formats');

        if ($geo_filter) {
            $path = $rlValid->str2path($path);

            if ($GLOBALS['config']['mf_geo_subdomains']) {
                if ($subdomain_path) {
                    $path = $subdomain_path . '/' . $path;
                }
            }

            $path = $path ? $path : $key;

            if (strlen($path) < 3) {
                $errors[] = $lang['mf_path_short'];
            } else {
                $parent_path = $rlDb->getOne('Path', "`Key` = '{$parent_key}'", 'data_formats');
                $item_path = $parent_path ? ($parent_path . '/' . $path) : $path;
                $path_exist = $rlDb->getOne('ID', "`Path` = '{$item_path}'", 'data_formats');

                if (!$path_exist) {
                    $path_exist = $rlDb->getOne('ID', "`Own_address` = '{$item_path}'", 'accounts');
                }

                if (!$path_exist) {
                    $path_exist = $rlDb->getOne('ID', "`Path` = '{$item_path}'", 'pages');
                }

                if (!$path_exist) {
                    $path_exist = $rlDb->getOne('ID', "`Path` = '{$item_path}'", 'categories');
                }

                if ($path_exist) {
                    $errors[] = $lang['mf_path_exists'];
                }
            }
        }

        /* check names */
        $languages = $GLOBALS['languages'];
        foreach ($languages as $key => $value) {
            if (empty($names[$languages[$key]['Code']])) {
                $names[$languages[$key]['Code']] = $names[$GLOBALS['config']['lang']];
            }

            if (empty($names[$languages[$key]['Code']])) {
                $errors[] = str_replace('{field}', "'<b>{$lang['value']} ({$languages[$key]['name']})</b>'", $lang['notice_field_empty']);
            }
        }

        if ($errors) {
            $out = '<ul>';
            foreach ($errors as $error) {
                $out .= '<li>' . $error . '</li>';
            }
            $out .= '</ul>';

            Valid::escape($out);

            $_response->script("printMessage('error', '{$out}');");
        } else {
            $level = $this->getLevel($parent_id);
            $module = $level >= 1 ? 'formats' : 'common';

            $max_position = $rlDb->getOne("Position", "`Parent_ID` = {$parent_id} ORDER BY `Position` DESC", "data_formats");
            
            $parent_ids ='';
            if ($head != $parent_key) {
                $parent_ids = ($parent_info['Parent_IDs'] ? $parent_info['Parent_IDs'] . ',' : '') . $parent_id;                
            }

            $insert = array(
                'Parent_ID' => $parent_id,
                'Parent_IDs'=> $parent_ids,
                'Key'       => $item_key,
                'Status'    => $status,
                'Position'  => $max_position + 1,
                'Plugin'    => $level ? 'multiField' : '',
            );

            if ($item_path) {
                $insert['Path'] = $item_path;
            }

            /* insert new item */
            if ($GLOBALS['rlActions']->insertOne($insert, 'data_formats')) {
                if ($level) {
                    $listing_fields = $this->createLevelField($parent_id, 'listing');
                    $account_fields = $this->createLevelField($parent_id, 'account');
                }

                if ($listing_fields || $account_fields) {
                    $notice_out = '<ul>';
                    $notice_out .= "<li>" . $lang['item_added'] . "</li>";

                    foreach ($listing_fields as $k => $field) {
                        $href = "index.php?controller=listing_fields&action=edit&field=" . $field;
                        $link = '<a target="_blank" href="' . $href . '">$1</a>';
                        $row = preg_replace('/\[(.+)\]/', $link, $lang['mf_lf_created']);

                        $notice_out .= "<li>" . $row . "</li>";
                    }

                    foreach ($account_fields as $k => $field) {
                        $href = "index.php?controller=account_fields&action=edit&field=" . $field;
                        $link = '<a target="_blank" href="' . $href . '">$1</a>';
                        $row = preg_replace('/\[(.+)\]/', $link, $lang['mf_af_created']);
                        $notice_out .= "<li>" . $row . "</li>";
                    }
                    $notice_out .= '</ul>';
                }

                foreach ($languages as $key => $value) {
                    $lang_keys[] = array(
                        'Code'   => $languages[$key]['Code'],
                        'Module' => $module,
                        'Key'    => 'data_formats+name+' . $item_key,
                        'Value'  => $names[$languages[$key]['Code']],
                        'Plugin' => $level ? 'multiField' : '',
                    );
                }

                if (!$level) {
                    $GLOBALS['rlCache']->updateDataFormats();
                    $GLOBALS['rlCache']->updateSubmitForms();
                }

                if ($GLOBALS['rlActions']->insert($lang_keys, 'lang_keys')) {
                    $mess = $notice_out ? $notice_out : $lang['item_added'];

                    Valid::escape($mess);

                    $_response->script("printMessage('notice', '{$mess}')");
                    $_response->script("itemsGrid.reload();");
                    $_response->script("$('#new_item').slideUp('normal')");
                }
            }
        }

        $_response->script("$('input[name=item_submit]').val('{$lang['add']}');");

        return $_response;
    }

    /**
     * create field
     *
     * check related fields and add listing fields
     * if there are no field yet for this level
     *
     * @param int $parent_id
     * @param string $type - listing or account
     **/
    public function createLevelField($parent_id, $type = 'listing')
    {
        global $languages, $rlDb;

        $out = array();
        $parent_id = (int) $parent_id;
        $multi_format = $this->getHead($parent_id);

        if (!$multi_format) {
            return false;
        }

        $format_id = $rlDb->getOne("ID", "`Key` = '{$multi_format}'", 'data_formats');

        $sql = "SELECT * FROM `{db_prefix}{$type}_fields` ";
        $sql .= "WHERE `Condition` = '{$multi_format}' AND `Key` NOT REGEXP 'level[0-9]'";
        $related_fields = $rlDb->getAll($sql);

        if (!$related_fields) {
            return false;
        }

        $level = $this->getLevel($parent_id);
        $level = $level ? $level : 1;

        foreach ($related_fields as $rlk => $field) {
            $field_key = $field['Key'] . "_level" . $level;
            $prev_fk = $level == 1 ? $field['Key'] : ($field['Key'] . "_level" . ($level - 1));

            $sql = "SHOW FIELDS FROM `{db_prefix}{$type}s` WHERE `Field` = '{$field_key}'";
            $field_exists = $rlDb->getRow($sql);

            if (!$field_exists) {
                $sql = "ALTER TABLE `{db_prefix}{$type}s` ";
                $sql .= "ADD `{$field_key}` VARCHAR(255) NOT NULL AFTER `{$prev_fk}`";
                $rlDb->query($sql);

                $sql = "SELECT `Key` FROM `{db_prefix}{$type}_fields` WHERE `Key` = '{$field_key}'";
                $field_exists = $rlDb->getRow($sql);
            }

            if (!$field_exists) {
                $field_info = array(
                    'Key'       => $field_key,
                    'Condition' => $multi_format,
                    'Type'      => 'select',
                    'Status'    => 'active',
                );

                if ($type == 'listing') {
                    $field_info['Add_page'] = '1';
                    $field_info['Details_page'] = '1';
                }
                $field_info['Readonly'] = '1';

                preg_match('/country|location|state|region|province|address|city/i', $field_key, $match);
                if ($match) {
                    $field_info['Map'] = '1';
                }

                if ($GLOBALS['rlActions']->insertOne($field_info, $type . "_fields")) {
                    $field_id = $rlDb->insertID();

                    if ($type == 'listing') {
                        $prev_field_id = $rlDb->getOne("ID", "`Key` = '{$prev_fk}'", 'listing_fields');

                        $sql = "UPDATE `{db_prefix}listing_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$prev_field_id},', ',{$prev_field_id},{$field_id},'))) WHERE FIND_IN_SET('{$prev_field_id}', `Fields`) ";
                        $rlDb->query($sql);

                        $sql = "UPDATE `{db_prefix}search_forms_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$prev_field_id},', ',{$prev_field_id},{$field_id},'))) WHERE FIND_IN_SET('{$prev_field_id}', `Fields`) ";
                        $rlDb->query($sql);
                    } elseif ($type == 'account') {
                        $prev_field_id = $rlDb->getOne("ID", "`Key` = '{$prev_fk}'", 'account_fields');

                        $sql = "SELECT `Category_ID`, `Position`, `Group_ID` FROM `{db_prefix}account_submit_form` ";
                        $sql .= "WHERE `Field_ID` ={$prev_field_id}";
                        $afields = $rlDb->getAll($sql);

                        foreach ($afields as $afk => $afield) {
                            $sql = "UPDATE `{db_prefix}account_submit_form` SET `Position` = `Position`+1 ";
                            $sql .= "WHERE `Position` > " . $afield['Position'] . " AND `Category_ID` = " . $afield['Category_ID'];
                            $rlDb->query($sql);

                            $insert[$afk]['Position'] = $afield['Position'] + 1;
                            $insert[$afk]['Category_ID'] = $afield['Category_ID'];
                            $insert[$afk]['Group_ID'] = $afield['Group_ID'];
                            $insert[$afk]['Field_ID'] = $field_id;
                        }
                        $GLOBALS['rlActions']->insert($insert, 'account_submit_form');
                    }

                    foreach ($languages as $key => $value) {
                        $lang_keys[] = array(
                            'Code'   => $languages[$key]['Code'],
                            'Module' => 'common',
                            'Key'    => $type . '_fields+name+' . $field_key,
                            'Value'  => $GLOBALS['lang'][$type . '_fields+name+' . $field['Key']] . " Level " . $level,
                            'Plugin' => 'multiField',
                        );
                    }

                    $GLOBALS['rlActions']->insert($lang_keys, 'lang_keys');
                }
                $out[] = $field_key;
            }
        }
        $GLOBALS['rlCache']->updateForms();

        return $out;
    }

    /**
     * deletes automatically added fields (listing fields and account fields) when you delete multi-format
     *
     * @param string $format - multi_format key
     * @param string $type - listing or account
     **/
    public function deleteFormatChildFields($format, $type = 'listing')
    {
        global $rlDb;

        $sql = "SELECT `Key`, `ID` FROM `{db_prefix}{$type}_fields` ";
        $sql .= "WHERE `Condition` = '{$format}' AND `Key` REGEXP 'level[0-9]'";
        $related_fields = $rlDb->getAll($sql);

        foreach ($related_fields as $rlk => $field) {
            $sql = "DELETE `T1`,`T2` FROM `{db_prefix}{$type}_fields` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
            $sql .= "ON (`T2`.`Key` = CONCAT('{$type}_fields+name+', `T1`.`Key`) OR `T2`.`Key` = CONCAT('{$type}_fields+des+', `T1`.`Key`)) ";
            $sql .= "WHERE `T1`.`Key` ='{$field['Key']}'";
            $rlDb->query($sql);

            if ($type == 'listing') {
                $sql = "UPDATE `{db_prefix}listing_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$field['ID']},', ','))) WHERE FIND_IN_SET('{$field['ID']}', `Fields`) ";
                $rlDb->query($sql);

                $sql = "UPDATE `{db_prefix}search_forms_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$field['ID']},', ','))) WHERE FIND_IN_SET('{$field['ID']}', `Fields`) ";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}short_forms` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);
            } else {
                $sql = "DELETE FROM `{db_prefix}account_search_relations` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}account_short_form` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}account_submit_form` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);
            }

            $sql = "SHOW FIELDS FROM `{db_prefix}{$type}s` ";
            $sql .= "WHERE `Field` = '{$field['Key']}'";
            $field_exists = $rlDb->getRow($sql);

            if ($field_exists) {
                $sql = "ALTER TABLE `{db_prefix}{$type}s` DROP `{$field['Key']}`";
                $rlDb->query($sql);
            }
        }
    }

    /**
     * deletes a data format with childs
     *
     * @param int $id - format id
     **/
    public function deleteDF($id)
    {
        global $key, $rlDb;

        $id = (int) $id;
        $key = $rlDb->getOne("Key", "`ID` = {$id}", "data_formats");

        if (!$id || !$key) {
            return false;
        }

        $sql = "DELETE `T1`,`T2` FROM `{db_prefix}data_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
        $sql .= " ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`) ";
        $sql .= "WHERE `T1`.`Key` LIKE '{$key}\_%' ";
        $rlDb->query($sql);

        $sql = "DELETE FROM `{db_prefix}multi_formats` WHERE `Key` = '{$key}'";
        $rlDb->query($sql);

        $sql = "DELETE `T1`, `T2` FROM `{db_prefix}data_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
        $sql .= "ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`) ";
        $sql .= "WHERE `T1`.`Key` = '{$key}'";
        $rlDb->query($sql);

        $GLOBALS['rlCache']->updateDataFormats();
        $GLOBALS['rlCache']->updateForms();

        $GLOBALS['rlHook']->load('apPhpFormatsAjaxDeleteItem');

        return true;
    }

    /**
     * deletes automatically added fields (listing fields and account fields) when you delete field
     *
     * @param string $format - multi_format key
     * @param string $type - listing or account
     **/
    public function deleteFieldChildFields($field_key, $type = 'listing')
    {
        global $rlDb;

        $GLOBALS['rlValid']->sql($field_key);

        if (!$field_key || !$type) {
            return false;
        }

        $sql = "SELECT `Key`, `ID` FROM `{db_prefix}{$type}_fields` ";
        $sql .= "WHERE `Key` REGEXP '{$field_key}_level[0-9]'";
        $related_fields = $rlDb->getAll($sql);

        foreach ($related_fields as $rlk => $field) {
            $sql = "DELETE `T1`,`T2` FROM `{db_prefix}{$type}_fields` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
            $sql .= "ON (`T2`.`Key` = CONCAT('{$type}_fields+name+', `T1`.`Key`) ";
            $sql .= "OR `T2`.`Key` = CONCAT('{$type}_fields+des+', `T1`.`Key`)) ";
            $sql .= "WHERE `T1`.`Key` ='{$field['Key']}'";

            $rlDb->query($sql);

            $sql = "SHOW FIELDS FROM `{db_prefix}{$type}s` ";
            $sql .= "WHERE `Field` = '{$field['Key']}'";
            $field_exists = $rlDb->getRow($sql);

            if ($field_exists) {
                $sql = "ALTER TABLE `{db_prefix}{$type}s` DROP `{$field['Key']}`";
                $rlDb->query($sql);
            }
        }
    }

    /**
     * preparing item editing
     *
     * @package ajax
     * @param string $key - key
     **/
    public function ajaxPrepareEdit($key)
    {
        global $_response, $rlDb;

        $GLOBALS['rlValid']->sql($key);
        if (!$key) {
            return $_response;
        }

        /* get item info */
        $item = $rlDb->fetch(array('ID', 'Key', 'Status', 'Default', 'Path'), array('Key' => $key), null, 1, 'data_formats', 'row');
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
        } else {
            $path_array = explode("/", $item['Path']);
            
            $item['Path'] = current(array_slice($path_array, -1, 1));
            $item['Parent_path'] = implode('/', array_slice($path_array, 0, -1));
        }
        $GLOBALS['rlSmarty']->assign_by_ref('item', $item);

        /* get item names */
        $tmp_names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'data_formats+name+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
        foreach ($tmp_names as $k => $v) {
            $names[$tmp_names[$k]['Code']] = $tmp_names[$k];
        }
        unset($tmp_names);

        $GLOBALS['rlSmarty']->assign_by_ref('names', $names);

        $tpl = RL_PLUGINS . 'multiField' . RL_DS . 'admin' . RL_DS . 'edit_format_block.tpl';

        $_response->assign("prepare_edit_area", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
        $_response->script("flynax.tabs();");

        return $_response;
    }

    /**
     * edit format item
     *
     * @package ajax
     *
     * @since 2.0.1 - Empty string default value added to $path parameter
     *
     * @param string $key - key
     * @param array $names - names
     * @param string $status - status
     * @param string $format - parent format
     *
     **/
    public function ajaxEditItem($key, $names, $status, $format, $path = '', $subdomain_path = false)
    {
        global $_response, $lang, $rlValid, $rlDb;

        $item_key = $rlValid->xSql(trim($key));
        $rlValid->sql($format);

        if (!$item_key || !$format) {
            return $_response;
        }

        /*check path*/
        $item_id = $rlDb->getOne("ID", "`Key` = '{$format}'", 'data_formats');
        $head = $this->getHead($item_id);
        $geo_filter = $rlDb->getOne("Geo_filter", "`Key` = '{$head}'", 'multi_formats');

        if ($geo_filter) {
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            if ($GLOBALS['config']['mf_geo_subdomains']) {
                $subdomain_path = $rlValid->str2path($subdomain_path);
                
                if ($subdomain_path) {
                    $path = $subdomain_path . '/' . $path;
                }
            }

            /* check key */
            if (strlen($path) < 3) {
                $errors[] = $lang['mf_path_short'];
            } else {
                $parent_path = $rlDb->getOne('Path', "`Key` = '{$format}'", 'data_formats');
                $item_path = $parent_path ? ($parent_path . '/' . $path) : $path;

                $path_exist = $rlDb->getOne('Key', "`Path` = '{$item_path}'", 'data_formats');
                if (!$path_exist) {
                    $path_exist = $rlDb->getOne('ID', "`Own_address` = '{$item_path}'", 'accounts');
                }

                if (!empty($path_exist) && $path_exist != $key) {
                    $errors[] = $lang['mf_path_exists'];
                }
            }
        }

        /* check names */
        $languages = $GLOBALS['languages'];
        foreach ($languages as $key => $value) {
            if (empty($names[$languages[$key]['Code']])) {
                $mess = $rlValid->xSql("'<b>{$lang['value']} ({$languages[$key]['name']})</b>'");
                $errors[] = str_replace('{field}', $mess, $lang['notice_field_empty']);
            }
        }

        if ($errors) {
            $out = '<ul>';

            /* print errors */
            foreach ($errors as $error) {
                $out .= '<li>' . $error . '</li>';
            }
            $out .= '</ul>';

            Valid::escape($out);

            $_response->script("printMessage('error', '{$out}');");
        } else {
            $level = $this->getLevel($item_id);

            $update = array(
                'fields' => array(
                    'Status' => $status,
                    'Path'   => $item_path,
                ),
                'where'  => array(
                    'Key' => $item_key,
                ),
            );

            $old_path = $rlDb->getOne("Path", "`Key` ='{$item_key}'", "data_formats");

            if ($item_path != $old_path) {
                $path_update = "UPDATE `{db_prefix}data_formats` ";
                $path_update .= "SET `Path` = REPLACE(`Path`, '{$old_path}/', '{$item_path}/') ";
                $path_update .= "WHERE `Key` LIKE '{$item_key}_%'";
                $rlDb->query($path_update);
            }

            /* update item */
            if ($rlDb->updateOne($update, 'data_formats')) {
                /* update item name */
                foreach ($languages as $key => $value) {
                    if ($rlDb->getOne('ID', "`Key` = 'data_formats+name+{$item_key}' AND `Code` = '{$languages[$key]['Code']}'", 'lang_keys')) {
                        $lang_keys[] = array(
                            'fields' => array(
                                'Value' => $names[$languages[$key]['Code']],
                            ),
                            'where'  => array(
                                'Code' => $languages[$key]['Code'],
                                'Key'  => 'data_formats+name+' . $item_key,
                            ),
                        );
                    } else {
                        $insert_phrase[] = array(
                            'Module' => 'common',
                            'Value'  => $names[$languages[$key]['Code']],
                            'Code'   => $languages[$key]['Code'],
                            'Key'    => 'data_formats+name+' . $item_key,
                        );
                    }
                }

                $action = false;
                if ($lang_keys) {
                    $action = $rlDb->update($lang_keys, 'lang_keys');
                }
                if ($insert_phrase) {
                    $action = $rlDb->insert($insert_phrase, 'lang_keys');
                }

                if ($action) {
                    if (!$level) {
                        $GLOBALS['rlCache']->updateDataFormats();
                        $GLOBALS['rlCache']->updateSubmitForms();
                    }

                    $_response->script("printMessage('notice', '{$lang['item_edited']}')");

                    $_response->script("itemsGrid.reload()");
                    $_response->script("$('#edit_item').slideUp('normal')");
                } else {
                    trigger_error("Can't edit data_format item, MySQL problems.", E_USER_WARNING);
                    $GLOBALS['rlDebug']->logger("Can't edit data_format item, MySQL problems.");
                }
            }
        }

        $_response->script("$('input[name=item_edit]').val('{$lang['edit']}')");

        return $_response;
    }

    /**
     * add format item
     *
     * @package ajax
     * @param string $key - item key
     **/
    public function ajaxDeleteItem($key, $only_childs = false)
    {
        global $_response, $lang, $rlValid, $rlDb;

        $key = $rlValid->xSql($key);
        if (!$key) {
            return $_response;
        }

        $item = $rlDb->fetch(array('ID', 'Parent_ID'), array('Key' => $key), null, null, 'data_formats', 'row');
        $level = $this->getLevel($item['Parent_ID']);

        $sql = "DELETE `T1`, `T2` FROM `{db_prefix}data_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
        $sql .= "ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`) ";
        $sql .= "WHERE `T1`.`Key` LIKE '{$key}\_%' ";
        if (!$only_childs) {
            $sql .= "OR `T1`.`Key` = '{$key}'";
        }
        $rlDb->query($sql);

        if (!$level) {
            $GLOBALS['rlCache']->updateDataFormats();
            $GLOBALS['rlCache']->updateSubmitForms();
        }

        $GLOBALS['rlHook']->load('apPhpFormatsAjaxDeleteItem');

        $_response->script("printMessage('notice', '{$lang['item_deleted']}')");
        $_response->script("$('#loading').fadeOut('normal');");

        $_response->script("itemsGrid.reload()");
        $_response->script("$('#edit_item').slideUp('normal');");
        $_response->script("$('#new_item').slideUp('normal');");

        return $_response;
    }

    /**
     * delete format
     *
     * @package ajax
     * @param string $key - key
     **/
    public function ajaxDeleteFormat($key)
    {
        global $_response, $lang, $rlValid, $rlDb;

        $rlValid->sql($key);
        if (!$key) {
            return $_response;
        }

        $format_id = $rlDb->getOne("ID", "`Key` ='{$key}'", 'data_formats');

        if ($format_id) {
            if ($this->deleteDF($format_id)) {
                $GLOBALS['rlCache']->updateDataFormats();
                $GLOBALS['rlCache']->updateSubmitForms();

                $_response->script("printMessage('notice', '{$lang['item_deleted']}')");
                $_response->script("$('#loading').fadeOut('normal');");

                $_response->script("multiFieldGrid.reload()");
                $_response->script("$('#edit_item').slideUp('normal');");
                $_response->script("$('#new_item').slideUp('normal');");
            }
        }

        return $_response;
    }

    /**
     * delete child items | recursive method
     *
     * @param int $parent_ids -  parent_ids
     * @return boolean
     **/
    public function deleteChildItems($ids)
    {
        global $rlDb;

        $GLOBALS['rlValid']->sql($ids);
        if (!$ids) {
            return false;
        }

        $sql = "SELECT `ID` FROM `{db_prefix}data_formats` ";
        $sql .= "WHERE FIND_IN_SET(`Parent_ID`, '{$ids}')";
        $child_t = $rlDb->getAll($sql);

        $child = '';
        foreach ($child_t as $ck => $cv) {
            $child .= $cv['ID'] . ",";
        }

        $sql = "DELETE `T1`, `T2` FROM `{db_prefix}data_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
        $sql .= "ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`) ";
        $sql .= "WHERE FIND_IN_SET(`T1`.`ID`, '{$ids}')";

        $rlDb->query($sql);

        if ($child) {
            return $this->deleteChildItems(rtrim($child, ","));
        } else {
            return true;
        }
    }

    /**
     * get bread crumbs | recursive method
     *
     * @param int $parent_id -  parent_id
     * @return array
     **/
    public function getBreadCrumbs($parent_id = false, $bc = false)
    {
        $parent_id = (int) $parent_id;

        if (!$parent_id) {
            return false;
        }

        $sql = "SELECT `T1`.`ID`, `T1`.`Parent_ID`, `T1`.`Key`, `T2`.`Value` AS `name` ";
        $sql .= "FROM `{db_prefix}data_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON ";
        $sql .= "CONVERT( `T2`.`Key` USING utf8) = CONCAT('data_formats+name+', `T1`.`Key`) ";
        $sql .= "AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `T1`.`ID` = {$parent_id}";

        $info = $GLOBALS['rlDb']->getRow($sql);

        if (!empty($info)) {
            $bc[] = $info;
        } else {
            $bc = false;
        }

        if (!empty($info['Parent_ID'])) {
            return $this->getBreadCrumbs($info['Parent_ID'], $bc);
        } else {
            return $bc;
        }
    }

    /**
     * After Remote Import
     *
     * @param int $table      - location database key
     * @param int $format_key
     * 
     * @return array
     **/
    public function afterRemoteImport($table, $format_key = '')
    {
        global $rlDb;

        $this->getLevels(null, true, $format_key);

        if (is_numeric(strpos($table, 'location'))) {
            $sql = "UPDATE `{db_prefix}config` SET `Default` = '{$table}' ";
            $sql .= "WHERE `Key` = 'mf_db_version'";
            $GLOBALS['rlDb']->query($sql);
        }

        return false;
    }

    /* @hook apTplControlsForm
     *
     * @since 2.0.0
     */
    public function hookApTplControlsForm()
    {
        global $lang;

        echo '<tr class="body">
            <td class="list_td">' . $lang['mf_rebuild'] . '</td>
            <td class="list_td" align="center">
                <input id="mfRebuildFields" type="button" value="' . $lang['rebuild'] . '" style="margin: 0;width: 100px;" />
            </td>
        </tr>';

        if ($this->geoFilterEnabled()) {
            echo '<tr class="body">
                <td class="list_td">' . $lang['mf_rebuild_path'] . '</td>
                <td class="list_td" align="center">
                    <input id="mfRebuildPaths" type="button" value="' . $lang['mf_refresh'] .'" style="margin: 0;width: 100px;" />
                </td>
            </tr>';
        }

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField/admin/refreshEntry.tpl');
    }

    /**
     * @hook apAjaxRequest
     *
     * @param array  $out    - ajax request response
     * @param string $action - request action
     */
    public function hookApAjaxRequest(&$out, $request_item)
    {
        switch ($_REQUEST['mode']) {
            case 'mfNext':
                $GLOBALS['reefless']->loadClass('MultiField', null, 'multiField');
                $data = $GLOBALS['rlMultiField']->getData($request_item);

                $out['data'] = $data;
                $out['status'] = 'ok';
                break;

            case 'mfRebuildPaths':
                if ($_REQUEST['modify']) {
                    $GLOBALS['config']['mf_geo_subdomains_type'] = $_REQUEST['value'];
                }

                $out = $this->refreshPaths(intval($_REQUEST['start']));
                break;

            case 'mfRebuildFields':
                $this->rebuildMultiField($out);
                break;
        }
    }

    /**
     * Is geo filtering enabled for some data format
     *
     * @since 2.0.0
     *
     * @return int - Geo Filter format ID
     */
    public function geoFilterEnabled()
    {
        static $geoFilterId = null;

        if (null === $geoFilterId) {
            $geoFilterId = $GLOBALS['rlDb']->getOne('ID', "`Geo_filter` = '1'", 'multi_formats');
        }

        return $geoFilterId;
    }

    /**
     * Rebuild related fields 
     *
     * @param int $out  - ajax request $out var to interact with
     **/
    public function rebuildMultiField(&$out)
    {
        global $rlDb, $lang;

        $sql = "SELECT * FROM `{db_prefix}multi_formats` WHERE 1";
        $multi_formats = $rlDb->getAll($sql);

        if (!$multi_formats) {
            $out['status'] = 'error';
            $out['message'] = $lang['mf_rebuild_no_format_configured'];

            return false;
        }

        foreach ($multi_formats as $key => $format) {
            foreach (array('listing', 'account') as $type) {
                $sql = "SELECT `Condition` as `data_format`, `Key` ";
                $sql .= "FROM `{db_prefix}{$type}_fields` ";
                $sql .= "WHERE `Condition` = '{$format['Key']}' ";
                $sql .= "AND `Key` NOT REGEXP 'level[0-9]'";
                $related_fields = $rlDb->getAll($sql);

                foreach ($related_fields as $rfKey => $rfield) {
                    $this->createSubFields($rfield, $type);
                    $rebuilt = true;
                }
            }
        }

        if (!$rebuilt) {
            $out['status'] = 'error';
            $out['message'] = $lang['mf_rebuild_no_fields_configured'];

            return false;
        } else {
            $out['status'] = 'ok';

            return true;
        }
    }

    /**
     * Refresh path initializer
     *
     * @param int $start - start position
     **/
    private function refreshPaths($start = 0)
    {
        global $rlDb;

        $sql = "
            SELECT `T1`.`Key`, `T1`.`Levels`, `T2`.`ID` as `ID`
            FROM `{db_prefix}multi_formats` AS `T1`
            LEFT JOIN `{db_prefix}data_formats` AS `T2`
            ON `T2`.`Key` = `T1`.`Key`
            WHERE `T1`.`Geo_filter` = '1'
        ";

        $format = $rlDb->getRow($sql);

        if (!$format || !$format['ID']) {
            return true;
        }

        if ($start == 0) {
            unset($_SESSION['mf_refresh_path']);

            $sql = "
                UPDATE `{db_prefix}data_formats` SET `Path` = '', `Parent_IDs` = ''
                WHERE `Key` LIKE '{$format['Key']}%'
            ";
            $rlDb->query($sql);

            $sql = "
                SELECT COUNT(*) AS `Count` FROM `{db_prefix}data_formats`
                WHERE `Key` LIKE '{$format['Key']}%'
            ";
            $_SESSION['mf_refresh_path']['total'] = $rlDb->getRow($sql, 'Count');

            // Speed up the duplicate path search query
            if ($GLOBALS['config']['mf_geo_subdomains_type'] == 'unique') {
                $rlDb->query("ALTER TABLE `{db_prefix}data_formats` ADD INDEX(`Path`)");
            }

            $parents = array($format['ID']);
        } else {
            $parents = $_SESSION['mf_refresh_path']['parents'];
        }

        if (!$parents) {
            $msg = 'Path refresh failed, no parent IDs array defined';
            $GLOBALS['rlDebug']->logger('MultiField: ' . $msg);

            return array(
                'status'  => 'ERROR',
                'message' => $msg
            );
        }

        if ($this->updateLocationPath($format, $parents)) {
            return array(
                'status'   => 'next',
                'progress' => floor(($start * 100 * 1000) / $_SESSION['mf_refresh_path']['total'])
            );
        } else {
            if ($GLOBALS['config']['mf_geo_subdomains_type'] == 'unique') {
                $rlDb->query("ALTER TABLE `{db_prefix}data_formats` DROP INDEX `Path`");
            }

            return array(
                'status' => 'completed'
            );
        }
    }
    
    /**
     * Update location path
     *
     * @param array $format  - Geo Location format data
     * @param array $parents - parent IDs to look into
     * @param int   $start   - count of affected items in this ajax session
     **/
    private function updateLocationPath($format, $parents, $count = 0)
    {
        global $rlDb, $config;

        $limit = 1000;
        $from  = $limit - $count;

        $parent_ids = implode("','", $parents);

        $sql = "
            SELECT `T2`.`Value` AS `name`, `T1`.`ID`, `T1`.`Parent_ID`
            FROM `{db_prefix}data_formats` AS `T1`
            LEFT JOIN `{db_prefix}lang_keys` AS `T2`
            ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`)
            AND `T2`.`Code` = '{$config['lang']}'
            WHERE `T1`.`Parent_ID` IN ('{$parent_ids}')
            AND `Path` = ''
            ORDER BY `T1`.`ID`
            LIMIT {$from}
        ";

        $locations = $rlDb->getAll($sql);
        $count     += count($locations);

        if ($locations) {
            foreach ($locations as $location) {
                $path = $this->str2path($location['name']);
                $parent_ids = '';

                if ($location['Parent_ID'] != $format['ID']) {
                    $parent = $rlDb->fetch(
                        array('Path', 'Parent_IDs'),
                        array('ID' => $location['Parent_ID']),
                        null,
                        1,
                        'data_formats',
                        'row'
                    );

                    $parent_ids = $parent['Parent_IDs']
                    ? $location['Parent_ID'] . ',' . $parent['Parent_IDs']
                    : $location['Parent_ID'];

                    if ($config['mf_geo_subdomains_type'] == 'unique') {
                        $path = $this->uniquePath($path, $parent);
                    } else {
                        $path = $parent['Path'] . '/' . $path;
                    }
                }

                $update = array(
                    'fields' => array(
                        'Path'       => $path,
                        'Parent_IDs' => $parent_ids,
                    ),
                    'where' => array(
                        'ID' => $location['ID']
                    )
                );
                $rlDb->update($update, 'data_formats');

                // Save parents to avoid mess
                if (!substr_count($parent_ids, ',')
                    && !in_array($location['ID'], $parents)
                ) {
                    $parents[] = $location['ID'];
                }
            }

            $sql = "
                SELECT COUNT(*) AS `Count` FROM `{db_prefix}data_formats`
                WHERE `Key` LIKE '{$format['Key']}\_%' AND `Path` = ''
            ";

            $total = $rlDb->getRow($sql, 'Count');

            // Completed
            if (!$total) {
                unset($_SESSION['mf_refresh_path']);
                return false;
            }
            // Next session
            elseif ($count >= $limit) {
                $_SESSION['mf_refresh_path']['parents'] = $parents;
                return true;
            }
            // Next stack
            else {
                return $this->updateLocationPath($format, $parents, $count);
            }
        } else {
            $GLOBALS['rlDebug']->logger('MultiField: Unexpected error occured, no locations found during paths refresh');
            return false;
        }

        return false;
    }

    /**
     * Make path unique by adding parent level path
     *
     * @since 2.0.0
     *
     * @param  string $path   - path
     * @param  array  $parent - parent level data
     * @return string         - unique path
     */
    private function uniquePath($path, $parent)
    {
        global $rlDb;

        if ($rlDb->getOne('ID', "`Path` = '{$path}'", "data_formats")) {
            $add_path = explode('/', $parent['Path']);
            $path .= '-' . array_pop($add_path);
        }

        return $path;
    }

    /**
     * local str2path function
     *
     * @param string $str  - string to make path from
     **/
    private function str2path($str)
    {
        return $GLOBALS['rlValid']->str2path($str);
    }

    /**
     * rebuild multi fields - rebuild sub fields
     *
     * @deprecated since 2.0.0
     **/
    public function ajaxRebuildMultiField($self, $mode = false, $no_ajax = false)
    {
    }

    /**
     * @deprecated since 2.0.0
     **/
    public function ajaxRebuildPath($self, $firstrun = false, $no_ajax = false)
    {
    }

    /**
     * @deprecated since 2.0.0
     **/
    public function updatePath($parent, $top_level = false, $nolimit = false)
    {
    }

    /**
     * @deprecated since 2.0.0
     **/
    public function updatePathPlain($parent)
    {
    }

    /**
     * ajaxImportSource - imports data from server
     *
     * @package ajax
     **/
    public function ajaxImportSource($parents = '', $table = false, $one_ignore = false, $resume = false)
    {
        global $_response, $rlDb;

        if (!$table) {
            return $_response;
        }

        if (!$resume) {
            if (empty($parents)) {
                $data = $this->getFData(array("table" => $table));
                $parents = "";
                foreach ($data as $val) {
                    $parents .= $val->Key . ",";
                }
            }

            $one_ignore = !empty($one_ignore) && $one_ignore != "false" ? 1 : 0;
            $parents = explode(",", trim($parents, ","));

            unset($_SESSION['mf_parent_ids']);
            $_SESSION['mf_import']['total'] = count($parents);
            $_SESSION['mf_import']['parents'] = $parents;
            $_SESSION['mf_import']['table'] = $table;
            $_SESSION['mf_import']['one_ignore'] = $one_ignore;
            $_SESSION['mf_import']['top_key'] = $_GET['parent'];
            $_SESSION['mf_import']['parent_id'] = $rlDb->getOne("ID", "`Key` = '{$_GET['parent']}'", "data_formats");
            $_SESSION['mf_import']['per_run'] = 1000;
            $_SESSION['mf_import']['available_rows'] = count($parents);

            $geo_filter = $rlDb->getOne("Geo_filter", "`Key` = '" . $_SESSION['mf_import']['top_key'] . "'", "multi_formats");
            if ($geo_filter) {
                $_SESSION['mf_import']['geo_filter'] = true;
            }
        }

        $_response->script("$('#load_cont').fadeOut();");
        if ($parents) {
            $_response->script("var item_width = width = percent = percent_value = sub_width = sub_item_width = sub_percent = sub_percent_value = sub_percent_to_show = percent_to_show = 0;");
            $_response->script("$('body').animate({ scrollTop: $('#flsource_container').offset().top-90 }, 'slow', function() { MFImport.start(); });");
        } else {
            $_response->script("$('body').animate({ scrollTop: $('#flsource_container').offset().top-90 }, 'slow');");
            $_response->script("printMessage('error', 'nothing selected')");
        }

        return $_response;
    }

    /**
     * ajaxExpandSource - lists available data items
     *
     * @package ajax
     **/
    public function ajaxExpandSource($table)
    {
        global $_response;

        if (!$table) {
            return $_response;
        }

        $data = $this->getFData(array("table" => $table));

        $GLOBALS['rlSmarty']->assign('topdata', $data);
        $GLOBALS['rlSmarty']->assign('table', $table);

        $tpl = RL_PLUGINS . 'multiField' . RL_DS . 'admin' . RL_DS . 'flsource.tpl';
        $_response->assign("flsource_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
        $_response->script("$('#flsource_container').fadeIn('normal')");
        $_response->script("$('html, body').animate({ scrollTop: $('#flsource_container').offset().top-25 }, 'slow');");
        $_response->call('handleSourceActs');

        return $_response;
    }

    /**
     * getFData - get data from flynax source server
     *
     * @param array $params - params to get data
     * @return json string
     **/
    public function getFData($params)
    {
        global $reefless;

        set_time_limit(0);
        $reefless->time_limit = 0;

        $vps = "http://database.flynax.com/index.php?plugin=multiField";
        $vps .= "&domain={$GLOBALS['license_domain']}&license={$GLOBALS['license_number']}";

        foreach ($params as $k => $p) {
            $vps .= "&" . $k . "=" . $p;
        }
        $content = $GLOBALS['reefless']->getPageContent($vps);

        $reefless->loadClass("Json");

        return $GLOBALS['rlJson']->decode($content);
    }

    /**
     * ajaxListSources - lists available on server databases
     *
     * @package ajax
     **/
    public function ajaxListSources()
    {
        global $_response;

        $data = $this->getFData(array("listdata" => true));
        $GLOBALS['rlSmarty']->assign("data", $data);

        $tpl = RL_PLUGINS . 'multiField' . RL_DS . 'admin' . RL_DS . 'flsource.tpl';
        $_response->assign("flsource_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
        $_response->script("$('#flsource_container').removeClass('block_loading');");
        $_response->script("$('#flsource_container').css('height', 'auto').fadeIn('normal')");

        return $_response;
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
     * mass actions with data format items
     *
     * @package xAjax
     *
     * @param string $ids     - item ids
     * @param string $action  - mass action
     **/
    public function ajaxDfItemsMassActions($ids = false, $action = false)
    {
        global $_response, $rlSmarty, $lang, $rlCache, $rlDb;

        if (!$ids || !$action) {
            return $_response;
        }

        $ids = explode('|', $ids);

        $GLOBALS['rlHook']->load('apPhpFormatsAjaxMassActions', $action, $ids);

        if ($action == 'activate' || $action == 'approve') {
            $status = $action == 'activate' ? 'active' : 'approval';
            $sql = "UPDATE `{db_prefix}data_formats` SET `Status` = '{$status}' ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '" . implode(',', $ids) . "') ";
            $rlDb->query($sql);
        } elseif ($action == 'delete') {
            $sql ="SELECT `Key` FROM `{db_prefix}data_formats` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '" . implode(',', $ids) . "') ";
            $keys = $rlDb->getAll($sql, array(false, 'Key'));

            foreach ($keys as $key) {
                $sql = "DELETE `T1`, `T2` FROM `{db_prefix}data_formats` AS `T1` ";
                $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
                $sql .= "ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`) ";
                $sql .= "WHERE `T1`.`Key` LIKE '{$key}%' ";
           
                $rlDb->query($sql);
            }

            $rlCache->updateDataFormats();
            $rlCache->updateForms();
        }

        $_response->script("printMessage('notice', '{$lang['mass_action_completed']}')");

        return $_response;
    }

    /**
     * @hook  apMixConfigItem
     * @since 2.0.0
     *
     * @param array $value
     * @param array $systemSelects - Required configs with "select" type
     */
    public function hookApMixConfigItem(&$value, &$systemSelects)
    {
        if ($value['Key'] !== 'mf_geo_subdomains_type') {
            return;
        }

        // mark field as "required" to remove "- Select -" option
        $systemSelects[] = 'mf_geo_subdomains_type';
    }
}
