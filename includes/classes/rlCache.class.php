<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLCACHE.CLASS.PHP
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

/**
 * cache class
 *
 * available cache recourses:
 * | cache_submit_forms          - submit forms
 * | cache_categories_by_type    - categories by listing type, full list
 * | cache_categories_by_parent  - categories by listing type, by parent includes subcategories
 * | cache_categories_by_id      - categories by id, full list
 * | cache_search_forms          - search forms by form key
 * | cache_search_fields         - search fields list by form key
 * | cache_featured_form_fields  - featured form fields by category id
 * | cache_listing_titles_fields - listing titles form fields by category id
 * | cache_short_forms_fields    - short form fields by category id
 * | cache_sorting_forms_fields  - sorting form fields by category id
 * | cache_data_formats          - data formats by key
 * | cache_listing_statistics    - listing statistics by listing type
 *
 **/
class rlCache
{
    /**
     * Memcache object
     * @var object
     */
    public $memcache_obj;

    /**
     * System cache keys
     * @var array
     * @since 4.7.2
     */
    public $cacheKeys = array(
        'cache_submit_forms',
        'cache_categories_by_type',
        'cache_categories_by_parent',
        'cache_categories_by_id',
        'cache_search_forms',
        'cache_search_fields',
        'cache_featured_form_fields',
        'cache_listing_titles_fields',
        'cache_short_forms_fields',
        'cache_sorting_forms_fields',
        'cache_data_formats',
        'cache_listing_statistics',
    );

    /**
     * Cache keys using for cache dividing
     * @var array
     */
    public $divided_caches = array(
        'cache_short_forms_fields',
        'cache_listing_titles_fields',
        'cache_data_formats',
        'cache_submit_forms',
        'cache_sorting_forms_fields',
    );

    /**
     * Previous get request cache key
     * @var string
     */
    private $prev_key = null;

    /**
     * class constructor
     **/
    public function __construct()
    {
        global $reefless;

        $cache_method = $GLOBALS['config']['cache_method'] ?: $GLOBALS['rlConfig']->getConfig('cache_method');

        if ($cache_method == 'memcached') {
            $this->memcacheConnect();
        }

        $reefless->loadClass('Categories');
        $reefless->loadClass('Common');
    }

    /**
     * Connect to memcache server
     * 
     * @since 4.5.0
     *
     * @param string $host
     * @param int    $port
     *
     * @return bool
     */
    public function memcacheConnect($host = RL_MEMCACHE_HOST, $port = RL_MEMCACHE_PORT)
    {
        $success = false;

        if (extension_loaded('memcached')) {
            $this->memcache_obj = new Memcached();
            $success = $this->memcache_obj->addServer($host, $port);
        }

        return $success;
    }

    /**
     * @since 4.6.0
     *
     * Get Cache Key
     *
     * Defines the cache key to get data from; depending on listing type settings and cache availability
     *
     * @param  string $key        - Cache key
     * @param  int    $id         - Category ID
     * @param  string $type       - Listing type data
     * @param  array  $parent_ids - Parent ids
     * @return string             - Cache key
     */
    public function getCacheKey($key, $id, $type = false, $parent_ids)
    {
        global $config;

        $cache_key = $config[$key];

        if ($id
            && $config['cache_divided']
            && in_array($key, $this->divided_caches)
        ) {
            if ($type['Cat_general_only']) {
                $cache_key .= '_' . $type['Cat_general_cat'];
            } else {
                if (is_numeric($id)) {
                    if ($this->isCacheFileExists($cache_key, $id)) {
                        $cache_key .= '_' . $id;
                    } else {
                        if ($parent_ids) {
                            foreach ($parent_ids as $parent_id) {
                                if ($this->isCacheFileExists($cache_key, $parent_id)) {
                                    $cache_key .= '_' . $parent_id;
                                    break;
                                }
                            }
                        } else {
                            $cache_key .= '_' . $type['Cat_general_cat'];
                        }
                    }
                } else {
                    $cache_key .= '_' . $id;
                }
            }
        }

        return $cache_key;
    }

    /**
     * Check is the cache file exists
     *
     * @since 4.7.1
     *
     * @param  string  $key - Cache key
     * @param  integer $id  - Cache item ID
     * @return boolean      - Exists status
     */
    private function isCacheFileExists($key, $id)
    {
        return is_readable(RL_CACHE . $key . '_' . $id);
    }

    /**
     * Get cache item
     *
     * @since 4.7.1 - $parent_ids parameter added
     *
     * @param  string       $key        - Cache item key
     * @param  integer      $id         - Cache item id
     * @param  array        $type       - Listing type data
     * @param  array|string $parent_ids - Parent ids as array or string of comma separated ids: 12,51,61
     * @return array                    - Cache data
     **/
    public function get($key = false, $id = false, $type = false, $parent_ids = null)
    {
        static $content = null;

        global $config;

        if ($parent_ids && is_string($parent_ids)) {
            $parent_ids = explode(',', $parent_ids);
        }

        $cache_key = $this->getCacheKey($key, $id, $type, $parent_ids);

        if (!$key || !$cache_key) {
            return false;
        }

        switch ($config['cache_method']) {
            case 'apc':
                $content = apc_fetch($cache_key);
                break;
            case 'memcached':
                $content = $this->memcache_obj->get($cache_key);
                break;
            case 'file':
            default:
                $file = RL_CACHE . $cache_key;

                if (!is_readable($file) && $id) {
                    $file = RL_CACHE . $config[$key];
                }

                if (!is_readable($file)) {
                    return false;
                }

                if ($this->prev_key != $key || $config['cache_divided']) {
                    $content = file_get_contents($file);
                    $content = json_decode($content, true);
                }

                $this->prev_key = $key;
                break;
        }

        if ($id === false) {
            $out = $content;
        } elseif ($config['cache_divided'] && in_array($key, $this->divided_caches)) {
            $out = $content;
        } else {
            $out = $content[$type['Key']] ? $content[$type['Key']][$id] : $content[$id];

            if ($type
                && !$out
                && in_array(
                    $key,
                    array(
                        'cache_featured_form_fields',
                        'cache_listing_titles_fields',
                        'cache_short_forms_fields',
                        'cache_submit_forms',
                        'cache_sorting_forms_fields',
                    )
                )
            ) {
                if ($type['Cat_general_only']) {
                    $out = $content[$type['Cat_general_cat']];
                } elseif (isset($parent_ids)) {
                    foreach ($parent_ids as $parent_id) {
                        if ($out = $content[$parent_id]) {
                            break;
                        }
                    }
                } else {
                    $main_content = $content;
                    $categories_by_type = $this->get('cache_categories_by_type', false, $type);
                    $categories_by_type = $categories_by_type[$type['Key']];
                    $out = $this->matchParent($id, 'Parent_ID', $categories_by_type, $main_content);
                    $content = $main_content;
                    unset($main_content, $categories_by_type);
                }

                if (!$out) {
                    $out = $content[$type['Cat_general_cat']];
                }
            }
        }

        return $out;
    }

    /**
     * Remove cache files by cache item key
     *
     * @since 4.7.2 - Default value added to $key parameter
     * @since 4.7.1
     *
     * @param  string $key - Cache item key
     */
    public function removeFiles($key = null)
    {
        global $config;

        if ($config['cache_method'] != 'file') {
            return;
        }

        foreach ($GLOBALS['reefless']->scanDir(RL_CACHE) as $file) {
            if ($key) {
                if (strpos($file, $key) === 0) {
                    unlink(RL_CACHE . $file);
                }
            } else {
                foreach ($this->cacheKeys as $cache_key) {
                    if (strpos($file, $cache_key) === 0) {
                        unlink(RL_CACHE . $file);
                        break 1;
                    }
                }
            }
        }
    }

    /**
     * match parent
     *
     * @param string $key - cache srouce
     * @param string $field - parent field name
     * @param array $search - search resurce
     * @param array $content - main content from cache
     *
     **/
    public function matchParent(&$id, $field = false, &$search, &$content)
    {
        if (!$id || !$field || !$search || !$content) {
            return false;
        }

        if ($search[$id][$field]) {
            if (!empty($content[$search[$id][$field]])) {
                return $content[$search[$id][$field]];
            } else {
                return $this->matchParent($search[$id][$field], $field, $search, $content);
            }
        }

        return false;
    }

    /**
     *
     * update submit forms
     * | cache_submit_forms
     *
     **/
    public function updateSubmitForms()
    {
        global $config, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $this->removeFiles('cache_submit_forms');

        /* submit forms cache */
        $sql = "SELECT `T1`.`Group_ID`, `T1`.`ID`, `T2`.`ID` AS `Category_ID`, `T3`.`Key` AS `Key`, `T3`.`Display` AS `Display`, ";
        $sql .= "`T1`.`Fields`, CONCAT('listing_groups+name+', `T3`.`Key`) AS `pName`, `T2`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listing_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_groups` AS `T3` ON `T1`.`Group_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`Group_ID` = '' OR `T3`.`Status` = 'active' ";
        $sql .= "ORDER BY `T1`.`Position`";

        $rows = $rlDb->getAll($sql);

        if (!$rows) {
            return false;
        }

        foreach ($rows as $key => $value) {
            if (!empty($value['Fields'])) {
                $sql = "SELECT *, FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order`, ";
                $sql .= "CONCAT('listing_fields+name+', `Key`) AS `pName`, CONCAT('listing_fields+description+', `Key`) AS `pDescription`, ";
                $sql .= "CONCAT('listing_fields+default+', `Key`) AS `pDefault`, `Multilingual` ";
                $sql .= "FROM `{db_prefix}listing_fields` ";
                $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
                $sql .= "ORDER BY `Order`";
                $fields = $rlDb->getAll($sql, 'Key');

                if (empty($fields)) {
                    unset($rows[$key]);
                } else {
                    $rows[$key]['Fields'] = $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'listing_fields', $value['Listing_type']);
                }
            } else {
                $rows[$key]['Fields'] = false;
            }

            unset($field_ids, $fields, $field_info);

            // reassign to form, collect by category ID
            $set = count($form[$value['Category_ID']]) + 1;
            $index = $value['Key'] ? $value['Key'] : 'nogroup_' . $set;
            $form[$value['Category_ID']][$index] = $rows[$key];
        }

        unset($rows);

        $this->set('cache_submit_forms', $form);
    }

    /**
     *
     * update categories by listing type
     * | cache_categories_by_type
     *
     **/
    public function updateCategoriesByType()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        if ($rlListingTypes->types) {
            foreach ($rlListingTypes->types as $key => $value) {
                $sql = "SELECT *, CONCAT('categories+name+', `Key`) AS `pName`, CONCAT('categories+title+', `Key`) AS `pTitle` ";
                $sql .= "FROM `{db_prefix}categories` ";
                $sql .= "WHERE `Type` = '{$value['Key']}' AND `Status` = 'active' ";
                if ($value['Cat_hide_empty']) {
                    $sql .= "AND `Count` > 0 ";
                }
                $out[$value['Key']] = $rlDb->getAll($sql, 'ID');
            }

            $this->set('cache_categories_by_type', $out);
        }
    }

    /**
     *
     * update categories by listing type, organized by parent
     * | cache_categories_by_parent
     *
     **/
    public function updateCategoriesByParent()
    {
        global $config, $rlListingTypes;

        if (!$config['cache']) {
            return false;
        }

        if ($rlListingTypes->types) {
            foreach ($rlListingTypes->types as $key => $value) {
                $out[$value['Key']] = $this->getChildCat(array(0), $value);
            }
            $this->set('cache_categories_by_parent', $out);
        }
    }

    /**
     *
     * update categories by id, full list
     * | cache_categories_by_id
     *
     **/
    public function updateCategoriesByID()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $sql = "SELECT *, `Modified`, CONCAT('categories+name+', `Key`) AS `pName`, CONCAT('categories+title+', `Key`) AS `pTitle` ";
        $sql .= "FROM `{db_prefix}categories` ";
        $sql .= "WHERE `Status` = 'active'";
        $categories = $rlDb->getAll($sql, 'ID');

        $this->set('cache_categories_by_id', $categories);
    }

    /**
     * call all methods relatead to categories
     **/
    public function updateCategories()
    {
        $this->updateCategoriesByType();
        $this->updateCategoriesByParent();
        $this->updateCategoriesByID();
    }

    /**
     * get children categories by parent | recursive method
     *
     * @param array $parent - parent category ids
     * @param array $type - listing type info
     *
     **/
    public function getChildCat($parent = array(0), $type = false, $data = false)
    {
        global $rlDb;

        foreach ($parent as $parent_id) {
            $parent_id = (int) $parent_id;

            $sql = "SELECT *, `Modified` ";
            $sql .= "FROM `{db_prefix}categories` ";
            $sql .= "WHERE `Type` = '{$type['Key']}' AND `Status` = 'active' AND `Parent_ID` = '{$parent_id}'";
            if ($type['Cat_hide_empty']) {
                $sql .= "AND `Count` > 0 ";
            }
            $sql .= "ORDER BY `Position`";

            if ($tmp_categories = $rlDb->getAll($sql)) {
                foreach ($tmp_categories as $cKey => $cValue) {
                    $ids[] = $cValue['ID'];

                    $categories[$cValue['ID']] = $cValue;
                    $categories[$cValue['ID']]['pName'] = 'categories+name+' . $cValue['Key'];
                    $categories[$cValue['ID']]['pTitle'] = 'categories+title+' . $cValue['Key'];

                    /* get subcategories */
                    if ($type['Cat_show_subcats']) {
                        // TODO - add this condition in output if needs
                        $rlDb->calcRows = true;
                        $subCategories = $rlDb->fetch(array('ID', 'Count', 'Path`, CONCAT("categories+name+", `Key`) AS `pName`, CONCAT("categories+title+", `Key`) AS `pTitle', 'Key'), array('Status' => 'active', 'Parent_ID' => $cValue['ID']), "ORDER BY `Position`", null, 'categories');
                        $rlDb->calcRows = false;

                        if (!empty($subCategories)) {
                            $categories[$cValue['ID']]['sub_categories'] = $subCategories;
                            $categories[$cValue['ID']]['sub_categories_calc'] = $rlDb->foundRows;
                        }

                        unset($subCategories);
                    }
                }
                unset($tmp_categories);

                $data[$parent_id] = $categories;

                unset($categories);
            } else {
                continue;
            }
        }

        if ($parent) {
            return $this->getChildCat($ids, $type, $data);
        } else {
            return $data;
        }
    }

    /**
     *
     * update search forms by form key
     * | cache_search_forms
     *
     **/
    public function updateSearchForms()
    {
        global $config, $rlListingTypes, $reefless, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $sql = "SELECT `T1`.`Category_ID`, `T1`.`Group_ID`, `T1`.`Fields`, ";
        $sql .= "`T2`.`Key` AS `Group_key`, `T2`.`Display`, ";
        $sql .= "`T3`.`Type` AS `Listing_type`, `T3`.`Key` AS `Form_key`, `T3`.`With_picture` ";
        $sql .= "FROM `{db_prefix}search_forms_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_groups` AS `T2` ON `T1`.`Group_ID` = `T2`.`ID` AND `T2`.`Status` = 'active' ";
        $sql .= "LEFT JOIN `{db_prefix}search_forms` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T3`.`Status` = 'active' ";
        $sql .= "ORDER BY `Position` ";

        $GLOBALS['rlHook']->load('phpCacheUpdateSearchFormsGetRelations', $sql); // >= v4.3

        $relations = $rlDb->getAll($sql);

        if (!$relations) {
            $out = array(1);
        }

        $reefless->loadClass('Categories');

        /* populate field information */
        foreach ($relations as $key => $value) {
            if (!$value) {
                continue;
            }

            $sql = "SELECT `ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, CONCAT('listing_fields+name+', `Key`) AS `pName`, ";
            $sql .= "`Multilingual`, `Opt1`, `Opt2`, FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order` ";
            $sql .= "FROM `{db_prefix}listing_fields` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
            $sql .= "ORDER BY `Order`";
            $fields = $rlDb->getAll($sql);

            if ($value['Group_key']) {
                $relations[$key]['pName'] = 'listing_groups+name+' . $value['Group_key'];
            }
            $relations[$key]['Fields'] = empty($fields) ? false : $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'listing_fields', $value['Listing_type']);

            $out[$value['Form_key']][] = $relations[$key];
        }

        $GLOBALS['rlHook']->load('phpCacheUpdateSearchFormsBeforeSave', $out, $relations); // >= v4.3

        unset($relations);

        $this->set('cache_search_forms', $out);
    }

    /**
     *
     * update search fields list by form key
     * | cache_search_fields
     *
     **/
    public function updateSearchFields()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $sql = "SELECT `T1`.`Category_ID`, `T1`.`ID`, `T1`.`Fields`, `T2`.`Key` AS `Form_key` ";
        $sql .= "FROM `{db_prefix}search_forms_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}search_forms` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Status` = 'active' ";
        $sql .= "ORDER BY `Position` ";
        $relations = $rlDb->getAll($sql);

        if (!$relations) {
            return false;
        }

        foreach ($relations as $key => $value) {
            $sql = "SELECT `ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, `Details_page`, `Opt1`, `Opt2`, ";
            $sql .= "`Multilingual`, FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order` ";
            $sql .= "FROM `{db_prefix}listing_fields` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
            $sql .= "ORDER BY `Order`";
            $fields = $rlDb->getAll($sql, 'Key');

            $out[$value['Form_key']] = array_merge($out[$value['Form_key']] ?: array(), $fields);
            unset($fields);
        }
        unset($relations);

        $this->set('cache_search_fields', $out);
    }

    /**
     *
     * update featured form fields by category id
     * | cache_featured_form_fields
     *
     **/
    public function updateFeaturedFormFields()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $rlDb->setTable('categories');
        $categories = $rlDb->fetch(array('ID', 'Key'));

        foreach ($categories as $key => $value) {
            $sql = "SELECT `T2`.`Key`, `T2`.`Type`, `T2`.`Default`, `T2`.`Condition`, `T2`.`Details_page`, `T2`.`Multilingual`, `T2`.`Opt1`, `T2`.`Opt2`, `T2`.`Contact` ";
            $sql .= "FROM `{db_prefix}featured_form` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Category_ID` = '{$value['ID']}' ORDER BY `T1`.`Position`";

            $fields = $rlDb->getAll($sql, 'Key');
            if ($fields) {
                $out[$value['ID']] = $fields;
            }
        }
        unset($categories);

        $this->set('cache_featured_form_fields', $out);
    }

    /**
     *
     * update listing title form fields by category id
     * | cache_listing_titles_fields
     *
     **/
    public function updateTitlesFormFields()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $this->removeFiles('cache_listing_titles_fields');

        $tables = array('', '', 'short_forms');
        $rlDb->setTable('categories');
        $categories = $rlDb->fetch(array('ID', 'Key'));

        foreach ($categories as $key => $value) {
            $sql = "SELECT `T2`.`Key`, `T2`.`Type`, `T2`.`Default`, `T2`.`Condition`, `T2`.`Details_page`, `T2`.`Multilingual`, `T2`.`Opt1`, `T2`.`Opt2`, `T2`.`Contact` ";
            $sql .= "FROM `{db_prefix}listing_titles` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Category_ID` = '{$value['ID']}' ORDER BY `T1`.`Position`";

            $fields = $rlDb->getAll($sql, 'Key');
            if ($fields) {
                $out[$value['ID']] = $fields;
            }
        }
        unset($categories);

        $this->set('cache_listing_titles_fields', $out);
    }

    /**
     *
     * update listing title form fields by category id
     * | cache_short_forms_fields
     *
     **/
    public function updateShortFormFields()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $this->removeFiles('cache_short_forms_fields');

        $rlDb->setTable('categories');
        $categories = $rlDb->fetch(array('ID', 'Key'));

        foreach ($categories as $key => $value) {
            $sql = "SELECT `T2`.`Key`, `T2`.`Type`, `T2`.`Default`, `T2`.`Condition`, `T2`.`Details_page`, `T2`.`Multilingual`, `T2`.`Opt1`, `T2`.`Opt2`, `T2`.`Contact` ";
            $sql .= "FROM `{db_prefix}short_forms` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Category_ID` = '{$value['ID']}' ORDER BY `T1`.`Position`";

            $fields = $rlDb->getAll($sql, 'Key');
            if ($fields) {
                $out[$value['ID']] = $fields;
            }
        }
        unset($categories);

        $this->set('cache_short_forms_fields', $out);
    }

    /**
     * Update listing sorting form fields by category id
     * @cache - cache_sorting_forms_fields
     * @since 4.5.2
     **/
    public function updateSortingFormFields()
    {
        global $rlDb;

        if (!$GLOBALS['config']['cache']) {
            return false;
        }

        $this->removeFiles('cache_sorting_forms_fields');

        $rlDb->setTable('categories');

        if ($categories = $rlDb->fetch(array('ID', 'Key'))) {
            foreach ($categories as $value) {
                $category_id = (int) $value['ID'];

                $sql = "SELECT `T2`.`Key`, `T2`.`Type`, `T2`.`Default`, `T2`.`Condition`, `T2`.`Details_page`, ";
                $sql .= "`T2`.`Multilingual`, `T2`.`Opt1`, `T2`.`Opt2`, `T2`.`Contact` ";
                $sql .= "FROM `{db_prefix}sorting_forms` AS `T1` ";
                $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
                $sql .= "WHERE `T1`.`Category_ID` = {$category_id} ORDER BY `T1`.`Position`";

                if ($fields = $rlDb->getAll($sql, 'Key')) {
                    $out[$category_id] = $fields;
                }
            }

            $this->set('cache_sorting_forms_fields', $out);
        }
    }

    /**
     * call all methods related to forms
     **/
    public function updateForms()
    {
        $this->updateSubmitForms();
        $this->updateSearchForms();
        $this->updateSearchFields();
        $this->updateFeaturedFormFields();
        $this->updateTitlesFormFields();
        $this->updateShortFormFields();
        $this->updateSortingFormFields();
    }

    /**
     *
     * update data formats by key
     * | cache_data_formats
     *
     **/
    public function updateDataFormats()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $this->removeFiles('cache_data_formats');

        $rlDb->setTable('data_formats');

        /* DO NOT SET ANOTHER FIELD FOR ORDER, ID ONLY */
        $data = $rlDb->fetch(array('ID', 'Parent_ID', 'Key`, CONCAT("data_formats+name+", `Key`) AS `pName', 'Position', 'Default'), array('Status' => 'active', 'Plugin' => ''), "ORDER BY `ID`, `Key`");

        foreach ($data as $key => $value) {
            if (!$value['Key']) {
                continue;
            }

            if (!array_key_exists($data[$key]['Key'], $out) && empty($data[$key]['Parent_ID'])) {
                $out[$data[$key]['Key']] = array();
                $df_info[$data[$key]['ID']] = $data[$key]['Key'];
            } else {
                if (!$df_info[$data[$key]['Parent_ID']]) {
                    continue;
                }

                $out[$df_info[$data[$key]['Parent_ID']]][] = $data[$key];
            }
        }

        unset($data, $df_info);
        $this->set('cache_data_formats', $out);
    }

    /**
     *
     * update listing statistics
     * | cache_listing_statistics
     *
     * @param string $listing_type - listing type key
     *
     **/
    public function updateListingStatistics($listing_type = false)
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $types = $listing_type && $rlListingTypes->types[$listing_type] ? array($rlListingTypes->types[$listing_type]) : $rlListingTypes->types;

        foreach ($types as $type) {
            if ($type['Status'] == 'approval') {
                continue;
            }

            $new_period = $config['new_period'];
            $field = $type['Arrange_field'] ? ", `T1`.`{$type['Arrange_field']}` " : '';

            $sql = "SELECT COUNT(*) AS `Count` FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T2`.`Type` = '{$type['Key']}' AND `T1`.`Status` = 'active' ";

            if ($type['Arrange_field']) {
                $values = explode(',', $type['Arrange_values']);
                foreach ($values as $value) {
                    $c_sql = $sql . "AND `T1`.`{$type['Arrange_field']}` = '{$value}' ";

                    /* get total */
                    $data = $rlDb->getRow($c_sql);
                    $total[$value] = $data['Count'];
                    $total['total'] += $data['Count'];

                    /* get today */
                    $t_sql = $c_sql . "AND UNIX_TIMESTAMP(`T1`.`Pay_date`) BETWEEN UNIX_TIMESTAMP(CURDATE()) AND UNIX_TIMESTAMP(NOW()) ";
                    $data = $rlDb->getRow($t_sql);
                    $today[$value] = $data['Count'];
                    $today['total'] += $data['Count'];
                }

                $out[$type['Key']]['total'] = $total;
                $out[$type['Key']]['today'] = $today;
            } else {
                /* get total */
                $data = $rlDb->getRow($sql);
                $out[$type['Key']]['total'] = $data['Count'];

                /* today new */
                $t_sql = $sql . "AND UNIX_TIMESTAMP(`T1`.`Pay_date`) BETWEEN UNIX_TIMESTAMP(CURDATE()) AND UNIX_TIMESTAMP(NOW()) ";
                $data = $rlDb->getRow($t_sql);
                $out[$type['Key']]['today'] = $data['Count'];
            }
        }

        unset($data, $sql, $c_sql, $n_sql, $t_sql);

        $this->set('cache_listing_statistics', $out, $listing_type);
    }

    /**
     * @since 4.5.0
     *
     * function set
     * @param string $key - cache item key
     * @param id $data - data array
     * @param string $listing_type - used when need to update only specific listing type in bunch of listing types
     *
     **/

    public function set($key, $data, $listing_type = null)
    {
        global $config, $rlDebug, $rlConfig, $reefless, $rlDb;

        if (!$key || !$data) {
            return true;
        }

        if (empty($config[$key])) {
            $hash = $reefless->generateHash();
            if (!$hash) {
                $rlDebug->logger("Can't create cache file, generateHash() doesn't generate anything.");
            } else {
                $cache_key = $key . '_' . $hash;
                if ($config['cache_method'] == 'file' && (!file_exists(RL_CACHE . $config[$key]) || !is_writable(RL_CACHE . $config[$key]))) {
                    $file_dir = RL_CACHE . $cache_key;

                    $fh = fopen($file_dir, 'w') or $rlDebug->logger("Can't create new file, fopen() fail.");
                    fclose($fh);

                    $reefless->rlChmod($file_dir);
                }

                /* save file name */
                $rlConfig->setConfig($key, $cache_key);
                $config[$key] = $cache_key;
            }
        }

        /* save only one and don't affect others */
        if ($listing_type) {
            if ($listing_type && $GLOBALS['rlListingTypes']->types[$listing_type]) {
                $tmp = $this->get($key);
                $tmp[$listing_type] = $data[$listing_type];
                $data = $tmp;
            }
        }

        switch ($config['cache_method']) {
            case 'apc':
                /* store cache as parts (by id) to retrieve only these parts and not whole data */
                if ($config['cache_divided'] && in_array($key, $this->divided_caches)) {
                    apc_delete($config[$key]);

                    foreach ($data as $item_id => $item) {
                        if ($item) {
                            apc_store($config[$key] . '_' . $item_id, $item);
                        }
                    }
                } else {
                    apc_store($config[$key], $data);
                }
                break;
            case 'memcached':
                /* store cache as parts (by id) to retrieve only these parts and not whole data */
                if ($config['cache_divided'] && in_array($key, $this->divided_caches) && is_array($data)) {
                    foreach ($data as $item_id => $item) {
                        if ($item) {
                            $this->memcache_obj->set($config[$key] . '_' . $item_id, $item);
                        }
                    }
                } else {
                    $this->memcache_obj->set($config[$key], $data);
                }
                break;
            case 'file':
            default:
                if ($config['cache_divided'] && in_array($key, $this->divided_caches)) {
                    foreach ($data as $item_id => $item) {
                        if ($item) {
                            $file = RL_CACHE . $config[$key] . '_' . $item_id;

                            $fh = fopen($file, 'w');
                            fwrite($fh, json_encode($item));
                            fclose($fh);
                        }
                    }
                } else {
                    $file = RL_CACHE . $config[$key];

                    $fh = fopen($file, 'w');
                    fwrite($fh, json_encode($data));
                    fclose($fh);
                }
                break;
        }

        unset($data);
    }

    /**
     *
     * update all system cache
     *
     **/
    public function update()
    {
        if ($GLOBALS['config']['cache_method'] == 'memcached') {
            $this->memcache_obj->flush();
        } elseif ($GLOBALS['config']['cache_method'] == 'apc') {
            apc_clear_cache('user');
        }

        $this->removeFiles();

        $this->updateDataFormats();

        $this->updateSubmitForms();
        $this->updateCategoriesByType();
        $this->updateCategoriesByParent();
        $this->updateCategoriesByID();
        $this->updateSearchForms();
        $this->updateSearchFields();

        $this->updateFeaturedFormFields();
        $this->updateTitlesFormFields();
        $this->updateShortFormFields();
        $this->updateSortingFormFields();

        $this->updateListingStatistics();
    }
}
