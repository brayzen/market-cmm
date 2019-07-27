<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: SEARCH_MAP.INC.PHP
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

$reefless -> loadClass('Search');

// get search forms
foreach ($rlListingTypes->types as $type_key => $listing_type) {
    if ($listing_type['Search_page']) {
        if ($search_form = $rlSearch->buildSearch($type_key.'_quick')) {
            $form_key = $type_key.'_quick';
            $out_search_forms[$form_key]['data'] = $search_form;
            $out_search_forms[$form_key]['name'] = $lang['search_forms+name+'.$form_key];
            $out_search_forms[$form_key]['listing_type'] = $type_key;
        }
    }

    unset($search_form);
}

$rlSmarty->assign_by_ref('search_forms', $out_search_forms);

// Disable sidebar blocks
$blocks['left'] = false;

$rlSearch->defaultMapAddressAssign();
