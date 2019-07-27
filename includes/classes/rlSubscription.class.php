<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLSUBSCRIPTION.CLASS.PHP
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

class rlSubscription extends reefless
{
    protected $xajax_response;

    protected $account_info;

    /**
     * constructor
     *
     */
    public function __construct()
    {
        global $_response, $account_info;

        $this->loadClass('Actions');
        $this->xajax_response = &$_response;
        $this->account_info = &$account_info;
    }

    /**
     * get subscription plan
     *
     * @param mixed $service
     * @param integer $plan_id
     * @return data
     */
    public function getPlan($service = false, $plan_id = false)
    {
        if (!$service || !$plan_id) {
            return false;
        }
        $sql = "SELECT * FROM `{db_prefix}subscription_plans` WHERE `Service` = '{$service}' AND `Plan_ID` = '{$plan_id}' LIMIT 1";
        $plan_info = $this->getRow($sql);

        return $plan_info;
    }

    /**
     * get subscription
     *
     * @param integer $subscription_id
     * @return data
     */
    public function getSubscription($subscription_id = false)
    {
        if (!$subscription_id) {
            return false;
        }

        $sql = "SELECT * FROM `{db_prefix}subscriptions` WHERE `ID` = '{$subscription_id}' LIMIT 1";
        return $this->getRow($sql);
    }

    /**
     * cancel subscription
     *
     * @package AJAX
     *
     * @param mixed $service
     * @param integer $item_id
     * @param integer $subscription_id
     */
    public function ajaxCancelSubscription($service = false, $item_id = false, $subscription_id = false, $page = false)
    {
        if (!$service || !$item_id) {
            return $this->xajax_response;
        }

        $service = $GLOBALS['rlValid']->xSql($service);
        $item_id = (int) $item_id;
        $subscription_id = (int) $subscription_id;

        $this->loadClass('PaymentFactory');

        // get subscription
        $subscription_info = $this->getSubscription($subscription_id);

        // get subscription plan
        $plan_info = $this->getPlan($service, $subscription_info['Plan_ID']);
        if ($plan_info) {
            $sql = "SELECT * FROM `{db_prefix}payment_gateways` WHERE `ID` = '{$subscription_info['Gateway_ID']}' LIMIT 1";
            $gateway_info = $this->getRow($sql);

            $GLOBALS['rlHook']->load('phpPreCancelSubscription');
            $rlGateway = $GLOBALS['rlPaymentFactory']->create($gateway_info['Key'], $gateway_info['Plugin']);
            $response = $rlGateway->cancelSubscription($subscription_info, $plan_info);

            $GLOBALS['rlHook']->load('phpPostCancelSubscription');

            if ($response) {
                $sql = "UPDATE `{db_prefix}subscriptions` SET `Status` = 'canceled' WHERE `ID` = '{$subscription_id}' LIMIT 1";
                $this->query($sql);

                if (filter_var($response, FILTER_VALIDATE_URL)) {
                    $this->xajax_response->redirect($response);
                }
            }
            $this->xajax_response->script("printMessage('notice', '{$GLOBALS['lang']['cancel_subscription_success']}');");

            if ($page == 'upgrade_listing') {
                $redirect_url = SEO_BASE;
                $redirect_url .= $GLOBALS['config']['mod_rewrite'] ? $GLOBALS['pages'][$page] . '.html?id=' . $item_id : '?page=' . $GLOBALS['pages'][$page] . '&amp;id=' . $item_id;

                $this->xajax_response->script("
                        $('#unsubscription-{$item_id}').parent().html('<a class=\"button\" href=\"{$redirect_url}\">{$GLOBALS['lang']['continue']} >></a>');
                        printMessage('notice', '{$GLOBALS['lang']['cancel_subscription_success']}');
                    ");
            }

            $GLOBALS['rlHook']->load('phpCancelSubscription');

            if (!$page) {
                $this->xajax_response->script("$('#unsubscription-{$item_id}').remove();");
            }
        }

        return $this->xajax_response;
    }

    /**
     * get subscription options to plan
     *
     * @return array
     *
     */
    public function getPlanOptions()
    {
        $response = array();

        $sql = "SHOW COLUMNS FROM `{db_prefix}subscription_plans` WHERE `Field` RLIKE 'sop_(.*)$'";
        $fields = $this->getAll($sql);

        if ($fields) {
            foreach ($fields as $fKey => $fVal) {
                if ($fVal['Field']) {
                    $type = $this->getFieldType($fVal['Type'], $fVal['Field']);
                    $response[] = array(
                        'Key'    => $fVal['Field'],
                        'Type'   => is_array($type) ? $type[0] : $type,
                        'name'   => $GLOBALS['lang']['subscription_plans+name+' . strtolower($fVal['Field'])],
                        'values' => is_array($type) ? $type[1] : '',
                    );
                }
            }
        }

        return $response;
    }

    /**
     * save plan options
     *
     * @param mixed $service
     * @param integer $plan_id
     * @param float $total
     */
    public function savePlanOptions($service = false, $plan_id = false, $total = false)
    {
        if ($_POST) {
            $plan_id = (int) $plan_id;
            $service = $GLOBALS['rlValid']->xSql($service);
            $period = $GLOBALS['rlValid']->xSql($_POST['period']);
            $period_total = (int) $_POST['period_total'];

            $sql = "SELECT * FROM `{db_prefix}subscription_plans` WHERE `Service` = '{$service}' AND `Plan_ID` = '{$plan_id}' LIMIT 1";
            $plan_info = $this->getRow($sql);

            $data = $_POST['sop'];
            $fields = $this->getPlanOptions();

            if (!empty($plan_info['ID'])) {
                foreach ($fields as $fKey => $fValue) {
                    if (isset($data[$fValue['Key']])) {
                        $update['fields'][$fValue['Key']] = $data[$fValue['Key']];
                    }
                }
                $update['fields']['Status'] = $_POST['subscription'] ? 'active' : 'approval';
                $update['fields']['Total'] = (float) $total;
                $update['fields']['Period'] = $period;
                $update['fields']['Period_total'] = $period_total;

                $update['where'] = array(
                    'ID' => $plan_info['ID'],
                );
                $GLOBALS['rlActions']->updateOne($update, 'subscription_plans');
            } else {
                if ($_POST['subscription']) {
                    $insert = array(
                        'Service'      => $service,
                        'Plan_ID'      => (int) $plan_id,
                        'Total'        => (float) $total,
                        'Period'       => $period,
                        'Period_total' => $period_total,
                    );

                    foreach ($fields as $fKey => $fValue) {
                        if (isset($data[$fValue['Key']])) {
                            $insert[$fValue['Key']] = $data[$fValue['Key']];
                        }
                    }
                    $GLOBALS['rlActions']->insertOne($insert, 'subscription_plans');
                }
            }
        }
    }

    /**
     * get field type of subscription option
     *
     * @param string $type
     * @param string $field
     */
    protected function getFieldType($type = false, $field = false)
    {
        if (!$type) {
            return false;
        }

        $option_type = false;

        if (substr_count($type, 'varchar') > 0) {
            $option_type = 'text';
        } elseif (substr_count($type, 'double') > 0 || substr_count($type, 'int') > 0) {
            $option_type = 'numeric';
        } elseif (substr_count($type, 'enum') > 0) {
            $enum = trim(str_replace(array("'", "(", ")", "enum"), "", $type));
            $enum = explode(",", $enum);

            if (count($enum) == 2 && in_array(0, $enum) && in_array(1, $enum)) {
                $option_type = 'bool';
                $enum_list = array();
            } else {
                foreach ($enum as $eKey => $eValue) {
                    $enum_list[] = array(
                        'key'  => $eValue,
                        'name' => $GLOBALS['lang'][str_replace('sop_', '', $field) . '_' . $eValue],
                    );
                }
                $option_type = array('select', $enum_list);
            }
        }

        return $option_type;
    }

    /**
     * get subscription details
     *
     * @param mixed $subscription_id
     * @return array
     */
    public function getSubscriptionDetails($subscription_id = false)
    {
        if (!$subscription_id) {
            return false;
        }

        $sql = "SELECT `T1`.*, `T3`.`Key` AS `Gateway`, ";
        $sql .= "IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `Full_name` ";
        $sql .= "FROM `{db_prefix}subscriptions` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}payment_gateways` AS `T3` ON `T1`.`Gateway_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$subscription_id}' ";
        $sql .= "LIMIT 1";

        $subscription_info = $this->getRow($sql);

        if ($subscription_info) {
            switch ($subscription_info['Service']) {
                case 'package':
                case 'listing':
                    $sql = "SELECT * FROM `{db_prefix}listing_plans` WHERE `ID` = '{$subscription_info['Plan_ID']}' LIMIT 1";
                    $plan_info = $this->getRow($sql);

                    if ($plan_info) {
                        $plan_info['name'] = $GLOBALS['lang']['listing_plans+name+' . $plan_info['Key']];
                        $subscription_info['plan'] = $plan_info;
                    }
                    break;
                case 'membership':
                    $sql = "SELECT * FROM `{db_prefix}membership_plans` WHERE `ID` = '{$subscription_info['Plan_ID']}' LIMIT 1";
                    $plan_info = $this->getRow($sql);

                    if ($plan_info) {
                        $plan_info['name'] = $GLOBALS['lang']['membership_plans+name+' . $plan_info['Key']];
                        $subscription_info['plan'] = $plan_info;
                    }
                    break;
            }
            $subscription_info['Service'] = $GLOBALS['lang'][$subscription_info['Service'] == 'listing' || $subscription_info['Service'] == 'package' ? $subscription_info['Service'] . '_plan' : $subscription_info['Service']];
            $subscription_info['Gateway'] = $GLOBALS['lang']['payment_gateways+name+' . $subscription_info['Gateway']];

            $GLOBALS['rlHook']->load('phpSubscriptionDetails');
        }
        return $subscription_info;
    }

    /**
     * get active subscription of specific item
     *
     * @param integer $item_id
     * @param string $service
     * @return boolean
     */
    public function getActiveSubscription($item_id = false, $service = false)
    {
        if (!$item_id || !$service) {
            return false;
        }

        $item_id = (int) $item_id;
        $service = $GLOBALS['rlValid']->xSql($service);

        $sql = "SELECT * FROM `{db_prefix}subscriptions`
                WHERE `Item_ID` = '{$item_id}'
                AND `Account_ID` = '{$this->account_info['ID']}'
                AND `Status` = 'active'
                AND `Service` = '{$service}'
                LIMIT 1";
        $subscription = $this->getRow($sql);

        if (!empty($subscription['ID'])) {
            return $subscription;
        }

        return false;
    }

    /**
     * get the exact service name
     *
     * @param string $service
     */
    public function getService($service = false)
    {
        if (!$service) {
            return false;
        }
        if (in_array($service, array('package', 'featured'))) {
            $service = 'listing';
        }

        return $service;
    }

    /**
     * get plans (for all services)
     *
     */
    public function getAllPlans()
    {
        $plans = array();

        // get listing plans
        $this->loadClass('Plan');
        $l_plans = $GLOBALS['rlPlan']->getPlans(array('listing', 'package', 'featured'));

        if ($l_plans) {
            foreach ($l_plans as $lpKey => $plValue) {
                if ($plValue['Price'] > 0) {
                    $plans['listing'][] = array(
                        'ID'    => $plValue['ID'],
                        'name'  => $plValue['name'],
                        'Type'  => $plValue['Type'],
                        'Price' => $plValue['Price'],
                    );
                }
            }
        }

        $GLOBALS['rlHook']->load('phpSubscriptionGetPlans', $plans);

        return $plans;
    }

    public function ajaxGetSubscribersByPlan($plan_id = false, $service = 'listing')
    {
        if (!$service || !$plan_id) {
            return $this->xajax_response;
        }

        $this->xajax_response->script("
                $('#subscribers_area').html('');
                $('#subscription_no').next().next('img').remove();
            ");

        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T3`.`Key` AS `Gateway`, ";
        $sql .= "IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `Full_name` ";
        $sql .= "FROM `{db_prefix}subscriptions` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}payment_gateways` AS `T3` ON `T1`.`Gateway_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`Plan_ID` = '{$plan_id}' AND `T1`.`Service` = '{$service}'";
        $sql .= "LIMIT 10";

        $subscribers = $this->getAll($sql);

        // get total subscribers
        $calc = $this->getRow("SELECT FOUND_ROWS() AS `calc`");
        $total_subscribers = (int) $calc['calc'];

        if ($subscribers) {
            $GLOBALS['rlSmarty']->assign_by_ref('count_subscribers', str_replace('{count}', count($subscribers), $GLOBALS['lang']['subscribers_by_plan']));
            $GLOBALS['rlSmarty']->assign_by_ref('total_subscribers', $total_subscribers);
            $GLOBALS['rlSmarty']->assign_by_ref('subscribers', $subscribers);
            $this->xajax_response->assign('subscribers_area', 'innerHTML', $GLOBALS['rlSmarty']->fetch('blocks' . RL_DS . 'plan_subscribers.tpl', null, null, false));
            $this->xajax_response->script("$('#subscribers_area').removeClass('hide');");
        }

        return $this->xajax_response;
    }

    public function deletePlan($service = false, $plan_id = false)
    {
        if (!$service || $plan_id) {
            return;
        }

        $sql = "SELECT * FROM `{db_prefix}subscription_plans` WHERE `Service` = '{$service}' AND `Plan_ID` = '{$plan_id}' LIMIT 1";
        $plan_info = $this->getRow($sql);

        if ($plan_info) {
            $sql = "DELETE FROM `{db_prefix}subscription_plans` WHERE `ID` = '{$plan_info['ID']}' LIMIT 1";
            $this->query($sql);
        }
    }
}
