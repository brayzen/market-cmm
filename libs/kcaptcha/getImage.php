<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: GETIMAGE.PHP
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

require_once( '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'config.inc.php' );

require_once( RL_CLASSES . 'rlDb.class.php' );
require_once( RL_CLASSES . 'reefless.class.php' );

@ini_set('display_errors', false);

$rlDb = new rlDb();
$reefless = new reefless();

$reefless -> connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);

$reefless -> loadClass( 'Debug' );
$reefless -> loadClass( 'Config' );
$reefless -> loadClass( 'Valid' );

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

require_once(RL_LIBS . 'kcaptcha' . RL_DS . 'rlKCaptcha.php');

$domain_info = parse_url(RL_URL_HOME);
$domain_info['domain'] = "." . preg_replace("/^(www.)?/","", $domain_info['host']);
$domain_info['path'] = '/'. trim(RL_DIR, '/');
session_set_cookie_params(0, $domain_info['path'], $domain_info['domain']);

session_start();

$captcha_id = $_GET['id'];
$captcha = new rlKCaptcha($captcha_id);

if ( $captcha_id )
{
    $_SESSION['ses_security_code_'. $captcha_id] = $captcha->getKeyString();
}
else
{
    $_SESSION['ses_security_code'] = $captcha->getKeyString();
}
