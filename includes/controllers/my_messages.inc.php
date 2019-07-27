<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: MY_MESSAGES.INC.PHP
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

use Flynax\Utils\StringUtil;

if ($config['messages_module']) {
    $reefless->loadClass('Message');

    $id = (int) $_GET['id'];
    $visitor_mail = $rlValid->xSql($_GET['visitor_mail']);

    if ($id) {
        // get contact information
        if ($id == -1) {
            // visitor mode
            $contact = array('ID' => -1, 'Username' => 'visitor');
        } elseif (isset($_GET['administrator'])) {
            $contact = $rlDb->fetch(array('ID', 'Name', 'Email'), array('ID' => $id), null, 1, 'admins', 'row');
            $contact['Full_name'] = $contact['Name'] ? $contact['Name'] : $lang['administrator'];
            $contact['Admin'] = 1;
        } else {
            $contact = $rlAccount->getProfile($id);
            $blocks['account_page_info']['name'] = str_replace('{account_type}', $lang['account_types+name+' . $contact['Type']], $lang['account_type_details']);

            // get short form details in case if own page option disabled
            $owner_short_details = $rlAccount->getShortDetails($contact, $contact['Account_type_ID']);
            if ($account_info['ID'] != $contact['ID']) {
                $rlMembershipPlan->fakeValues($owner_short_details);
                $rlMembershipPlan->fakeValues($contact['Fields']);
            }
            $rlSmarty->assign_by_ref('owner_short_details', $owner_short_details);
        }
        $rlSmarty->assign_by_ref('contact', $contact);

        // define location details
        $location = $rlAccount->mapLocation;

        if ($config['map_module'] && $contact['Loc_latitude'] && $contact['Loc_longitude']) {
            $location['direct'] = $contact['Loc_latitude'] . ',' . $contact['Loc_longitude'];
            $rlSmarty->assign_by_ref('location', $location);
        } else {
            unset($blocks['account_page_location']);
        }

        // get contact messages
        $messages = $rlMessage->getMessages($id, false, $visitor_mail, $contact['Admin'] ? true : false);

        if ($id == -1) {
            reset($messages);
            $first_message = current($messages);
            $contact['Full_name'] = $first_message['Visitor_name'];

            if ($first_message['Visitor_mail']) {
                $field['Details_page'] = '1';
                $field['name'] = $lang['mail'];
                $field['value'] = '<a href="mailto:' . $first_message['Visitor_mail'] . '">' . $first_message['Visitor_mail'] . '</a>';
                $owner_short_details[] = $field;
            }

            // as chat grouped by email, phones and names can be different
            foreach ($messages as $key => $value) {
                $visitor_names[] = $value['Visitor_name'];
                if ($value['Visitor_phone']) {
                    $visitor_phones[] = '<a href="tel:' . $value['Visitor_phone'] . '">' . $value['Visitor_phone'] . '</a>';
                }
            }

            $visitor_names = array_unique($visitor_names);
            $visitor_phones = array_unique($visitor_phones);

            if ($visitor_names) {
                $field['Details_page'] = '1';
                $field['name'] = $lang['name'];
                $field['value'] = implode(", ", $visitor_names);
                $owner_short_details[] = $field;
            }

            if ($visitor_phones) {
                $field['Details_page'] = '1';
                $field['name'] = $lang['contact_phone'];
                $field['value'] = implode(", ", $visitor_phones);
                $owner_short_details[] = $field;
            }

            if ($owner_short_details) {
                $rlSmarty->assign_by_ref('owner_short_details', $owner_short_details);
            }

            $rlMembershipPlan->is_contact_allowed = true;
        }

        if (empty($messages)) {
            $sError = true;
        } else {
            $rlSmarty->assign_by_ref('messages', $messages);

            $page_title = $id == -1
            ? StringUtil::replaceAssoc($lang['message_from'], array('{name}' => $contact['Full_name']))
            : $lang['chat_with'] . ' ' . $contact['Full_name'];

            // redefine bread crumbs
            $bread_crumbs[] = array(
                'name' => $page_title,
            );
            $page_info['name'] = $page_title;
            $page_info['name'] .= $contact['Admin'] ? ' (' . $lang['website_admin'] . ')' : '';
        }

        // check new messages one more time
        $message_info = $rlCommon->checkMessages();
        if (!empty($message_info)) {
            $rlSmarty->assign_by_ref('new_messages', $message_info);
        }

        $rlHook->load('messagesBottom');

        /* register ajax methods */
        $rlXajax->registerFunction(array('sendMessage', $rlMessage, 'ajaxSendMessage'));
        $rlXajax->registerFunction(array('refreshMessagesArea', $rlMessage, 'ajaxRefreshMessagesArea'));
        $rlXajax->registerFunction(array('removeMsg', $rlMessage, 'ajaxRemoveMsg'));
    } else {
        $contacts = $rlMessage->getContacts();
        $rlSmarty->assign_by_ref('contacts', $contacts);

        $rlXajax->registerFunction(array('removeContacts', $rlMessage, 'ajaxRemoveContacts'));
    }
} else {
    $sError = true;
}
