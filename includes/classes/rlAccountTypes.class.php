<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: RLACCOUNTTYPES.CLASS.PHP
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

class rlAccountTypes extends reefless
{
    /**
     * @var account types
     **/
    public $types;

    /**
     * class constructor
     *
     * @param $active - use active type only
     *
     **/
    public function __construct($active = false)
    {
        $this->get($active);
    }

    /**
     * get account types
     *
     * @param $active - get active type only
     *
     * @return array
     **/
    private function get($active = false)
    {
        global $rlSmarty;

        $sql = "SELECT `T1`.* ";
        $sql .= "FROM `{db_prefix}account_types` AS `T1` ";
        $sql .= $active ? "WHERE `T1`.`Status` = 'active' " : '';
        $sql .= "ORDER BY `Position`";

        $GLOBALS['rlHook']->load('accountTypesGetModifySql', $sql);

        $types = $this->getAll($sql);
        $types = $GLOBALS['rlLang']->replaceLangKeys($types, 'account_types', array('name', 'desc'));

        foreach ($types as $type) {
            $type['Type'] = $type['Key'];
            $type['Page_key'] = 'at_' . $type['Type'];
            $this->types[$type['Key']] = $type;
        }

        $GLOBALS['rlHook']->load('accountTypesGetAdaptValue', $this->types);

        unset($types);

        if (is_object($rlSmarty)) {
            $rlSmarty->assign_by_ref('account_types', $this->types);
        }
    }
}
