<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: UTIL.PHP
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

namespace Flynax\Utils;

/**
 * @since 4.6.0
 */
class Util
{
    /**
     * Redirect to the url
     *
     * @since 4.7.0 - Added the $http_response_code parameter
     * 
     * @param string  $url                - url to redirect
     * @param boolean $exit               - is exit after redirect flag
     * @param integer $http_response_code - HTTP response code
     */
    public static function redirect($url, $exit = true, $http_response_code = 301)
    {
        global $rlHook;

        if (!$url) {
            return;
        }

        /**
         * @since 4.7.0 - Added the $exit and $http_response_code parameters
         * @since 4.6.0
         */
        $rlHook->load('utilsRedirectURL', $url, $exit, $http_response_code);

        header("Location: {$url}", true, $http_response_code);

        if ($exit) {
            exit;
        }
    }

    /**
     * Sort associative array by item
     *
     * @param array    - array to sort
     * @param string   - field name to sort by
     * @param constant - sorting type (array_multisort() function default params)
     *
     **/
    public static function arraySort(&$array, $field, $sort_type = SORT_ASC)
    {
        if (!$array || !$field) {
            return $array;
        }

        foreach ($array as &$value) {
            $sort[] = strtolower($value[$field]);
        }

        array_multisort($sort, $sort_type, $array);
        unset($sort);
    }

    /**
     * Get client IP address
     *
     * @return string - IP
     */
    public static function getClientIP()
    {
        static $clientIP = null;

        if (is_null($clientIP)) {
            $potential_keys = array(
                'HTTP_X_REAL_IP',
                'HTTP_CLIENT_IP',
                'REMOTE_ADDR',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
            );

            foreach ($potential_keys as $key) {
                if (array_key_exists($key, $_SERVER) === true) {
                    foreach (explode(',', $_SERVER[$key]) as $ip) {
                        if ((bool) preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip)) {
                            $clientIP = $ip;
                            break 2; // Exit both foreach
                        }
                    }
                }
            }
        }

        return $clientIP;
    }

    /**
     * Get page key by url
     *
     * @param  string $url    - requested page url
     * @param  array  &$pages - pages mapping data (key => path)
     * @return string         - requested page key
     */
    public static function getPageKeyFromURL($url, &$pages)
    {
        if (!$url) {
            return false;
        }

        $path = false;
        $url = str_replace(RL_URL_HOME, '', $url);
        $pattern = $GLOBALS['config']['mod_rewrite'] ? '/^([^\/]+)/' : '/page\=([^\=\&]+)/';

        preg_match($pattern, $url, $matches);

        if ($matches[1]) {
            $path = $matches[1];

            if (is_array($pages) && $key = array_search($path, $pages)) {
                return $key;
            } else {
                return $GLOBALS['rlDb']->getOne('Key', "`Path` = '{$path}'", 'pages');
            }
        } else {
            return false;
        }
    }

    /**
     * Get content by URL
     *
     * @param string $url     - source url
     * @param int $time_limit - time limit
     *
     * @return string - content
     **/
    public static function getContent($url, $time_limit = 10)
    {
        $content = null;
        $user_agent = 'Flynax Bot';

        if (extension_loaded('curl')) {
            $ch = curl_init();

            // localhost usage mode
            if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time_limit);
            curl_setopt($ch, CURLOPT_TIMEOUT, $time_limit);
            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($ch, CURLOPT_REFERER, RL_URL_HOME);
            $content = curl_exec($ch);
            curl_close($ch);
        } elseif (ini_get('allow_url_fopen')) {
            $default = ini_set('default_socket_timeout', $time_limit);
            $stream = fopen($url, "r");
            ini_set('default_socket_timeout', $default);

            if ($stream) {
                while (!feof($stream)) {
                    $content .= fgets($stream, 4096);
                }
                fclose($stream);
            }
        } else {
            $GLOBALS['rlDebug']->logger("Unable to get content from: {$url}");
            return 'Unable to get content from: ' . $url;
        }

        return $content;
    }

    /**
     * Prepare error response and write it to logs
     *
     * @since 4.6.1
     *
     * @param  string $msg - error message
     * @param  bool   $log - write the message to the logs
     * @return array       - error response
     */
    public static function errorResponse($msg, $log = true)
    {
        if ($log && $msg && is_object($GLOBALS['rlDebug'])) {
            $GLOBALS['rlDebug']->logger($msg);
        }

        $msg = $msg ?: 'No error response message specified';

        return array(
            'status'  => 'ERROR',
            'message' => $msg,
        );
    }

    /**
     * Convert string size to bytes, etc: 2M to 2097152
     *
     * @since 4.6.1
     *
     * @param  string $size - Size string
     * @return integer      - Converted size
     */
    public static function stringToBytes($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit{0})));
        } else {
            return round($size);
        }
    }

    /**
     * Get upload max file size depending of the server limits
     *
     * @since 4.6.1
     *
     * @return integer - Max upload size bytes
     */
    public static function getMaxFileUploadSize()
    {
        return min(
            self::stringToBytes(ini_get('post_max_size')),
            self::stringToBytes(ini_get('upload_max_filesize'))
        );
    }

    /**
     * Generate random number with selected length
     *
     * @since 4.7.0
     * 
     * @param  int $length        - Length of needed number
     * @param  int $excluded_rand - Excluded number from result
     * @return int                - Random number
     */
    public static function getRandomNumber($length = 3, $excluded_rand = 0)
    {
        $rand = '';

        for ($i = 1; $i <= $length; $i++) {
            $rand .= mt_rand($i > 1 ? 0 : 1, 9);
        }

        $rand          = (int) $rand;
        $excluded_rand = (int) $excluded_rand;

        if ($excluded_rand && $excluded_rand === $rand) {
            return self::getRandomNumber($length, $excluded_rand);
        }

        return $rand;
    }

    /**
     * Get list of pages
     *
     * @since 4.7.1
     *
     * @param array  $select        - List of necessary data
     * @param array  $where         - Condition of selection pages from database
     * @param string $options       - Additional SQL condition, like "ORDER BY" and etc.
     * @param array  $outputRowsMap - Mapping in output ['Key' => 'Value']
     *
     * @return array
     */
    public static function getPages($select = '*', $where = array(), $options = "", $outputRowsMap = array())
    {
        global $rlDb;

        $GLOBALS['rlHook']->load('phpGetPages', $select, $where, $options, $outputRowsMap);
        $rlDb->outputRowsMap = $outputRowsMap;

        return (array) $rlDb->fetch($select, $where, $options, null, 'pages');
    }

    /**
     * Get pages with "Active" status only
     *
     * @since 4.7.1
     *
     * @return array
     */
    public static function getActivePages()
    {
        return self::getPages('*', array('Status' => 'active'));
    }

    /**
     * Get pages with "Active" status and low priority in system.
     * They are not required the login of users and from list excluded major system pages.
     *
     * @since 4.7.1
     *
     * @return array
     */
    public static function getMinorPages()
    {
        $select       = array('ID', 'Key');
        $where        = array('Status' => 'active', 'Login'  => '0');
        $excludedKeys = array(
            'add_listing',
            'edit_listing',
            'remind',
            'confirm',
            'payment',
            'upgrade_listing',
            'listing_remove',
            'payment_history',
            '404',
            'view_details',
            'my_favorites',
            'print',
            'rss_feed',
        );

        $GLOBALS['rlHook']->load('phpGetMinorPages', $select, $where, $excludedKeys);

        return self::getPages($select, $where, "AND `Key` NOT IN ('" . implode("', '", $excludedKeys) . "')");
    }
}
