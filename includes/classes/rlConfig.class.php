<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLCONFIG.CLASS.PHP
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

class rlConfig extends reefless
{
    /**
     * get configuration value by configuration name
     *
     * @param string - configuration variable name
     * @return string - configuration variable value
     *
     **/
    public function getConfig($name)
    {
        if (empty($GLOBALS['config'])) {
            return $this->getOne('Default', "`Key` = '{$name}'", 'config');
        } else {
            return $GLOBALS['config'][$name];
        }
    }

    /**
     * set value for configuration
     *
     * @param string $key   - configuration key
     * @param string $value - new value
     *
     **/
    public function setConfig($key, $value)
    {
        $data = array(
            'fields' => array('Default' => $value),
            'where'  => array('Key' => $key),
        );

        if ($GLOBALS['rlActions']->updateOne($data, 'config')) {
            return true;
        }
        return false;
    }

    /**
     * get all configuration by group id
     *
     * @param string - configuration group id
     * @return array - mixed
     *
     **/
    public function allConfig($group = null)
    {
        if (empty($GLOBALS['config'])) {
            $where = !is_null($group) ? array('Group_ID' => intval($group)) : null;
            $this->outputRowsMap = array('Key', 'Default');
            $configs = $this->fetch($this->outputRowsMap, $where, null, null, 'config');

            $GLOBALS['config'] = &$configs;
            return $configs;
        }
        return $GLOBALS['config'];
    }
}
