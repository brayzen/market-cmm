<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: SETTINGS.TPL.PHP
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

/* template settings */
$tpl_settings = array(
	'type' => 'responsive_42', // DO NOT CHANGE THIS SETTING
	'version' => 1.1,
	'name' => 'general_flatty_wide', // _flatty_wide - is necessary postfix
	'inventory_menu' => false,
	'right_block' => false,
	'long_top_block' => false,
	'featured_price_tag' => true,
	'ffb_list' => false, //field bound boxes plugins list
	'fbb_custom_tpl' => true,
	'header_banner' => true,
    'header_banner_size_hint' => '728x90',
	'home_page_gallery' => false,
	'autocomplete_tags' => true,
	'category_banner' => true,
	'shopping_cart_use_sidebar' => true,
	'listing_details_anchor_tabs' => true,
    'home_page_map_search' => false,
	'browse_add_listing_icon' => false,
    'listing_grid_except_fields' => array('title', 'bedrooms', 'bathrooms', 'square_feet', 'time_frame', 'phone', 'pay_period'),
    'category_dropdown_search' => true,
    'sidebar_sticky_pages' => array('listing_details'),
    'sidebar_restricted_pages' => array('search_on_map'),
    'qtip' => array(
        'background' => '396932',
        'b_color'    => '396932',
    ),
);

if ( is_object($rlSmarty) ) {
	$rlSmarty -> assign_by_ref('tpl_settings', $tpl_settings);
}