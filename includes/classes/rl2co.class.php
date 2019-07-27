<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RL2CO.CLASS.PHP
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

class rl2co extends rlGateway
{
    /**
     * API host
     *
     * @var string
     */
    protected $api_host;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->api_host = 'https://www.2checkout.com/checkout/purchase';
    }

    /**
     * Start payment process
     */
    public function call()
    {
        global $rlPayment, $config;

        $this->setOption('sid', $config['2co_id']);
        $this->setOption('cart_order_id', $rlPayment->getTransactionID());
        $this->setOption('item_number', $rlPayment->buildItemData());
        $this->setOption('product_id', $rlPayment->getOption('item_id'));
        $this->setOption('total', $rlPayment->getOption('total'));
        $this->setOption('quantity', '1');
        $this->setOption('credit_card_processed', 'Y');
        $this->setOption('x_receipt_link_url', $rlPayment->getNotifyURL() . '?gateway=2co');
        $this->setOption('c_name', $rlPayment->getOption('item_name'));
        $this->setOption('c_description', $rlPayment->getOption('item_name'));

        if ($config['2co_testmode']) {
            $this->setOption('demo', 'Y');
        }
        $this->buildPage();
    }

    /**
     * Complete payment process
     */
    public function callBack()
    {
        global $reefless, $config;

        // save response to log
        if ($config['2co_testmode']) {
            $file = fopen(RL_TMP . 'response.log', 'a');
            if ($file) {
                $line = "\n\n" . date('Y.m.d H:i:s') . ":\n";
                fwrite($file, $line);
                foreach ($_REQUEST as $p_key => $p_val) {
                    $line = "{$_SERVER['REQUEST_METHOD']}: {$p_key} => {$p_val}\n";
                    fwrite($file, $line);
                }
            }
        }
        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post'
            || strtolower($_SERVER['REQUEST_METHOD']) == 'get'
        ) {
            $errors = false;

            if ($_REQUEST['item_number']) {
                $total = (float) $_REQUEST['total'];
                $txn_gateway = $GLOBALS['rlValid']->xSql($_REQUEST['order_number']);
                $hash = strtoupper(md5(
                    $config['2co_secret_word'] .
                    $config['2co_id'] .
                    ($config['2co_testmode'] || $_REQUEST['demo'] == 'Y' ? '1' : $_REQUEST['order_number']) .
                    $_REQUEST['total']
                ));
                $items = $this->explodeItems($_REQUEST['item_number']);

                $crypted_sum = $items[3];
                $callback_class = $items[4];
                $callback_method = $items[5];
                $cancel_url = $items[6];
                $success_url = $items[7];
                $lang_code = $items[8];
                $callback_plugin = $items[9] ? $items[9] : false;

                $response = array(
                    'plan_id'     => $items[0],
                    'item_id'     => $items[1],
                    'account_id'  => $items[2],
                    'total'       => $total,
                    'txn_id'      => (int) $items[10],
                    'txn_gateway' => $txn_gateway,
                    'params'      => $items[12],
                );

                if (trim($_REQUEST['key']) != $hash) {
                    $errors = true;
                    $GLOBALS['rlDebug']->logger("2checkout: Hash code invalid [{$_REQUEST['key']} != {$hash}]");
                }

                if (empty($items[1]) || empty($total)) {
                    $errors = true;
                }

                if (!$errors) {
                    $GLOBALS['rlPayment']->complete($response, $callback_class, $callback_method, $callback_plugin);
                    $reefless->redirect(null, $success_url);
                } else {
                    $reefless->redirect(null, $cancel_url);
                }
            } else {
                $cancel_url = SEO_BASE;
                $cancel_url .= $config['mod_rewrite']
                ? $GLOBALS['pages']['payment'] . '/' . rlPayment::FAIL_URL . '.html'
                : '?page=' . $GLOBALS['pages']['payment'] . '&rlVareables=' . rlPayment::FAIL_URL;
                $reefless->redirect(null, $cancel_url);
            }
        }
    }

    /**
     * Check if 2co configured
     *
     * @return boolean
     */
    public function isConfigured()
    {
        if ($GLOBALS['config']['2co_id']) {
            return true;
        }
        return false;
    }
}
