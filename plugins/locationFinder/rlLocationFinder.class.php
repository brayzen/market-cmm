<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLLOCATIONFINDER.CLASS.PHP
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

class rlLocationFinder
{
    /**
     * general url of loading google map
     *
     * @since 3.1.3
     * @var string
     */
    public $google_map_src = '';

    /**
     * class construct
     *
     */
    public function __construct()
    {
        global $config;

        $this->google_map_src = '//maps.googleapis.com/maps/api/js?libraries=places&language=' . RL_LANG_CODE . '&key=' . $config['google_map_key'];
    }

    /**
     * Plugin installer
     * 
     * @since 4.0.0
     */
    public function install()
    {
        global $rlDb, $config, $rlConfig;

        $rlDb->addColumnToTable('lf_zoom', "INT(2) NOT NULL", 'listings');

        $sql  = "
            INSERT INTO `{db_prefix}config` (`Group_ID`, `Key`, `Default`, `Type`, `Plugin`) VALUES 
            (0, 'locationFinder_db_version', '', 'text', 'locationFinder'),
            (0, 'locationFinder_default_location', '37.7577627,-122.4726194', 'text', 'locationFinder')
        ";
        $rlDb->query($sql);

        $rlDb->addColumnToTable('Location_finder', "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`", 'listing_types');
    }

    /**
     * Remove related fields
     *
     * @since 4.0.0
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropColumnFromTable('lf_zoom', 'listings');
        $rlDb->dropColumnFromTable('Location_finder', 'listing_types');
    }

    /**
     * Display map on "Add Listing" page
     * 
     * @since 4.0.0
     * @hook addListingPreFields
     */
    public function hookAddListingPreFields()
    {
        $this->displayMap();
    }

    /**
     * Display map on "Edit Listing" page
     * 
     * @since 4.0.0
     * @hook editListingPreFields
     */
    public function hookEditListingPreFields()
    {
        $this->displayMap();
    }

    /**
     * Display map
     *
     * @since 4.0.0
     */
    private function displayMap()
    {
        global $rlSmarty;

        if (!$rlSmarty->_tpl_vars['manageListing']->listingType['Location_finder']) {
            return;
        }

        $rlSmarty->display(RL_PLUGINS  . 'locationFinder' . RL_DS . 'map.tpl');
    }

    /**
     * Update location data in the listing
     *
     * @since 4.0.3 - 4th parameter (&$plan_info) removed
     * @since 4.0.0
     * @hook afterListingEdit
     */
    public function hookAfterListingEdit(&$manageListing, &$info, &$data)
    {
        if ($manageListing->listingType['Location_finder']) {
            $this->assignLocation($manageListing->listingID, $data);
        }
    }

    /**
     * Update location data in the listing
     *
     * @since 4.0.3 - 4th parameter (&$plan_info) removed
     * @since 4.0.0
     * @hook afterListingCreate
     */
    public function hookAfterListingCreate(&$manageListing, &$info, &$data)
    {
        if ($manageListing->listingType['Location_finder']) {
            $this->assignLocation($manageListing->listingID, $data);
        }
    }

    /**
     * Post data simulation on the "Add Listing" page
     * 
     * @since 4.0.0
     * @hook addListingPostSimulation
     */
    public function hookAddListingPostSimulation(&$manageListing)
    {
        $this->postSimulation($manageListing);
    }

    /**
     * Post data simulation on the "Edit Listing" page
     * 
     * @since 4.0.0
     * @hook editListingPostSimulation
     */
    public function hookEditListingPostSimulation(&$manageListing)
    {
        $this->postSimulation($manageListing);
    }

    /**
     * Set map zoom
     * 
     * @since 4.0.0
     * @hook listingDetailsBottom
     */
    public function hookListingDetailsBottom()
    {
        global $config, $listing_data;

        if ($listing_data['lf_zoom']) {
            $config['map_default_zoom'] = $listing_data['lf_zoom'];
        }
    }

    /**
     * Static data registration
     * 
     * @since 4.0.0
     * @hook staticDataRegister
     */
    public function hookStaticDataRegister()
    {
        $GLOBALS['rlStatic']->addJS(
            $this->google_map_src,
            array('add_listing', 'edit_listing')
        );
    }

    /**
     * Ajax requests handler
     * 
     * @since 4.0.0
     * @hook ajaxRequest
     */
    public function hookAjaxRequest(&$out, &$request_mode)
    {
        global $config, $rlDb;

        if ($request_mode != 'locationFinder'
            || !$config['locationFinder_db_version']
        ) {
            return;
        }

        if ($rlDb->columnExists('Place_ID', 'geo_mapping')) {
            // Neighborhood lookup
            if ($_POST['neighborhoodPlaceID'] && $config['locationFinder_use_neighborhood']) {
                $mapping = $this->getEntry($_POST['neighborhoodPlaceID']);
            }

            // City lookup
            if (!$mapping && $_POST['cityPlaceID']) {
                $mapping = $this->getEntry($_POST['cityPlaceID']);
            }

            if ($mapping['Format_key']) {
                // Fix format key prefix
                if ($config['locationFinder_mapping_country']) {
                    $format_key = $rlDb->getOne('Condition', "`Key` = '{$config['locationFinder_mapping_country']}'", 'listing_fields');
                    $mapping['Format_key'] = preg_replace(
                        "/^countries/",
                        $format_key,
                        $mapping['Format_key']
                    );
                }

                // Get format data
                if ($parent_ids = $rlDb->getOne('Parent_IDs', "`Key` = '{$mapping['Format_key']}' AND `Status` = 'active'", 'data_formats')) {
                    $rlDb->outputRowsMap = array(false, 'Key');
                    $format = $rlDb->fetch(
                        array('Key'),
                        array('Status' => 'active'),
                        "AND FIND_IN_SET(`ID`, '{$parent_ids}') > 0 ORDER BY FIND_IN_SET(`ID`, '{$parent_ids}') DESC",
                        null,
                        'data_formats'
                    );

                    $format[] = $mapping['Format_key'];
                }
            }
        } else {
            $message = "Please update the geo mapping database to 2.0 version";
        }

        $out = array(
            'status' => $format ? 'OK' : 'ERROR',
            'results' => array(
                'keys' => $format
            ),
            'message' => $message
        );
    }

    /**
     * Display styles
     *
     * @since 4.0.0
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        global $page_info;

        if (in_array($page_info['Controller'], array('add_listing', 'edit_listing'))) {
            echo <<< HTML
            <style>
            .lf-location-search {
                right: 50px !important;
                bottom: 23px !important;
                width: 300px;
                max-width: 70%;
            }
            </style>
HTML;
        }
    }

    /**
     * Get location data by Place ID
     *
     * @since 4.0.0
     * 
     * @param  string $place_id - Goolge place ID
     * @return array            - Location data
     */
    private function getEntry($place_id)
    {
        return $GLOBALS['rlDb']->fetch(
            array('Format_key', 'Target'),
            array(
                'Place_ID' => $place_id,
                'Verified' => 1
            ),
            null, 1, 'geo_mapping', 'row'
        );
    }

    /**
     * Post data simulation
     * 
     * @since 4.0.0
     *
     * @param object $manageListing - object of the manage listing process
     */
    public function postSimulation(&$manageListing)
    {
        if (!$manageListing->listingType['Location_finder']) {
            return;
        }

        $listing = $manageListing->listingData;

        $_POST['f']['lf'] = array(
            'lat'  => $listing['Loc_latitude'],
            'lng'  => $listing['Loc_longitude'],
            'zoom' => $listing['lf_zoom']
        );
    }

    /**
     * @deprecated 4.0.0
     */
    public function clear() {}

    /**
     * Assign location data
     * 
     * @param  integer $listing_id - listing ID
     * @param  array   &$data      - listing post data
     */
    public function assignLocation($listing_id, &$data)
    {
        global $rlDb;

        if (!$listing_id) {
            $GLOBALS['rlDebug']->logger('Location Finder Error: No listing ID specified in ' . __METHOD__ . '()');
            return;
        }

        if (!$data['lf']) {
            return;
        }

        $update = array(
            'fields' => array(
                'Loc_latitude'  => $data['lf']['lat'],
                'Loc_longitude' => $data['lf']['lng'],
                'lf_zoom'       => $data['lf']['zoom']
            ),
            'where' => array(
                'ID' => $listing_id
            )
        );
        
        $rlDb->update($update, 'listings');
    }

    /**
     * @deprecated 4.0.0
     */
    public function multifieldBuild() {}

    /**
     * @deprecated 4.0.0 - See self::hookStaticDataRegister()
     */
    public function hookTplFooter() {}

    /**
     * @deprecated 4.0.0 - See self::hookStaticDataRegister()
     */
    public function hookBoot() {}
}
