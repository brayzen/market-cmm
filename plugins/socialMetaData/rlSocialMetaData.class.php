<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLSOCIALMETADATA.CLASS.PHP
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

class rlSocialMetaData
{
    /**
     * @hook  boot
     * @since 1.0.3
     */
    public function hookBoot()
    {
        global $page_info, $listing_data, $config, $lang, $rlSmarty, $photos, $account;

        $smd_logo = '';

        // collect meta data by page
        switch ($page_info['Controller']) {
            case 'listing_details':
                // add price to meta data
                if ($listing_data[$config['smd_price_key']]) {
                    $price = explode('|', $listing_data[$config['smd_price_key']]);
                    $price = array(
                        'currency_code' => $price[1],
                        'currency'      => $price[1] ? $lang['data_formats+name+' . $price[1]] : '',
                        'value'         => $GLOBALS['rlValid']->str2money($price[0]),
                        'og_value'      => $price[0] . '.00',
                    );

                    $rlSmarty->assign('smd_price', $price);
                }

                // add second field of product to meta data
                if ($short_info = $GLOBALS['rlListings']->getShortDetails($listing_data['ID'])) {
                    $count_fields = 1;

                    foreach ($short_info['fields'] as $key => $field) {
                        if ($count_fields >= 2 && $key != $config['smd_price_key']) {
                            $smd_second_field['key']   = $field['name'];
                            $smd_second_field['value'] = $field['value'];
                            break;
                        }

                        $count_fields++;
                    }

                    if ($smd_second_field['key'] && $smd_second_field['value']) {
                        $rlSmarty->assign('smd_second_field', $smd_second_field);
                    }
                }

                // legacy photo data (for < 4.6.0 version)
                $photos = $photos ?: $GLOBALS['media'];

                // add large main photo of listing
                if ($listing_data['Main_photo'] && is_array($photos[0])) {
                    $rlSmarty->assign('smd_logo', $smd_logo = $photos[0]['Photo']);
                } else {
                    if ($config['smd_logo'] && file_exists(RL_PLUGINS . "socialMetaData/{$config['smd_logo']}")) {
                        $rlSmarty->assign(
                            'smd_logo',
                            $smd_logo = RL_PLUGINS_URL . "socialMetaData/{$config['smd_logo']}"
                        );
                    }
                }

                // add default meta description if it not exist
                if (!$page_info['meta_description'] && $lang['pages+meta_description+view_details']) {
                    $page_info['meta_description'] = $lang['pages+meta_description+view_details'];
                }
                break;

            case 'account_type':
                if (!empty($account) && !empty($account['Photo']) && file_exists(RL_FILES . RL_DS . $account['Photo'])) {
                    $rlSmarty->assign('smd_logo', $smd_logo = RL_FILES_URL . $account['Photo']);
                } else {
                    if ($config['smd_logo'] && file_exists(RL_PLUGINS . "socialMetaData/{$config['smd_logo']}")) {
                        $rlSmarty->assign(
                            'smd_logo',
                            $smd_logo = RL_FILES_URL . RL_PLUGINS_URL . "socialMetaData/{$config['smd_logo']}"
                        );
                    }
                }
                break;

            default:
                // add custom logo for all pages except listing details
                if ($config['smd_logo'] && file_exists(RL_PLUGINS . 'socialMetaData' . RL_DS . $config['smd_logo'])) {
                    $rlSmarty->assign(
                        'smd_logo',
                        $smd_logo = RL_PLUGINS_URL . "socialMetaData/{$config['smd_logo']}"
                    );
                }
                break;
        }

        // get image properties for Facebook crawler
        if ($smd_logo) {
            $smd_logo_properties = getimagesize($smd_logo);

            if ($smd_logo_properties[0] && $smd_logo_properties[1] && $smd_logo_properties['mime']) {
                $smd_logo_properties = array(
                    'width'  => $smd_logo_properties[0],
                    'height' => $smd_logo_properties[1],
                    'mime'   => $smd_logo_properties['mime'],
                );

                $rlSmarty->assign('smd_logo_properties', $smd_logo_properties);
            }
        }
    }

    /**
     * @hook  tplHeaderCommon
     * @since 1.0.3
     */
    public function hookTplHeaderCommon()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'socialMetaData' . RL_DS . 'social_meta_data.tpl');
    }
}
