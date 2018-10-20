<?php


// LOGIN








/**
 * Performs authentication
 *
 * @param array $login Login data:
 * - login credentials: Uname, Pass
 * - AD data: AD_List, AD_Domain, AD_HTTPS
 * @return string $error_msg Error message to be displayed to user on failure
 */
function auth($login) {

    if (AD_AUTH) {

        $ad_error_code = auth_ad($login);
        $error_msg = auth_log($ad_error_code, 'ad', $login);

        switch ($ad_error_code) {

            case 404: // AD_SERVER404: AD unavailable, try DB AUTH

                $db_error_code = auth_db($login, 'db_only');
                $error_msg = auth_log($db_error_code, 'db', $login);
                break;

            case 0: // AD_CONFIRMS: proceed with DB AUTH to compare AD credentials with db credentials

                $db_error_code = auth_db($login, 'compare_with_ad');
                $error_msg = auth_log($db_error_code, 'db', $login);
                break;
        }

    } else { // !AD_AUTH

        $db_error_code = auth_db($login, 'compare_with_ad');
        $error_msg = auth_log($db_error_code, 'db', $login);
    }

    return $error_msg;
}



/**
 * Logs auth failures and returns appropriate error message for output to user
 *
 * @param int $error_code Error code
 *   1=>'DB_UNAME',
 *   2=>'DB_DISABLED',
 *   3=>'DB_PASS',
 *   8=>'AD_REJECTS',
 *   404=>'AD_SERVER404'
 *  (9 - Empty credentials)
 * @param string $typ Call type: (ad, db) - this is currently not used, because that info is sent through $error_code
 * @param array $z Data
 * @return string $error_msg Error message to be displayed to user
 */
function auth_log($error_code, $typ, $z) {

    $fields_arr = [
        1=>'DB_UNAME',
        2=>'DB_DISABLED',
        3=>'DB_PASS',
        8=>'AD_REJECTS',
        404=>'AD_SERVER404',
    ];


    if (in_array($error_code, [1,2,3,8,404])) {

        log2file('login', ['field'=>$fields_arr[$error_code], 'uname'=>$z['Uname']]);
    }

    $error_msg = '';

    $tx['MSG']  = txarr('messages', 'common');


    if (in_array($error_code, [1,3,8,9])) {

        $error_msg = $tx['MSG']['login_fail_creds'];
    }

    if (in_array($error_code, [2])) {

        $error_msg = $tx['MSG']['login_fail_disabled'];
    }

    return $error_msg;
}



/**
 * Checks validity of login credentials with AD server
 *
 * @param array $z All data, including DC addresses, settings, and username/password
 * @return int Error code: 0 - AD_CONFIRMS, 8 - AD_REJECTS, 9 - Empty credentials, 404 - AD_SERVER404
 */
function auth_ad($z) {

    // if either of the credentials is EMPTY, abort (i.e. return code 3)..
    if (!$z['Uname'] || !$z['Pass']) return 9;

    $z['Port'] = ($z['AD_HTTPS']) ? 636 : 389;

    $z['UnameFDN'] = htmlspecialchars($z['Uname']).'@'.$z['AD_Domain']; // Full Domain Name

    $z['Pass'] = htmlspecialchars($z['Pass']);


    // find DC that is certainly available

    foreach ($z['AD_List'] as $dc_host) {
        if (auth_server_ping($dc_host, $z['Port']) == true) {
            break;
        } else {
            log2file('login', ['field'=>'AD_SERVER404', 'dc'=>$dc_host.':'.$z['Port']]);
            $dc_host = false;
        }
    }


    if (@$dc_host) {

        // We now really connect to DC (over LDAP) and check the credentials

        if (!$z['AD_HTTPS']) {
            $dc_con = ldap_connect($dc_host, $z['Port']);
        } else {
            $dc_con = ldap_connect('ldaps://'.$dc_host);
        }

        if (!$dc_con) {

            return 404;
            // Server unavailable. This should never happen here, because we've already pinged the server, but anyway..

        } else {

            $dc_bind = @ldap_bind($dc_con, $z['UnameFDN'], $z['Pass']); // this is the actual DC-LOGIN attempt
            // We put @ to avoid logging a php error each time someone fails his password

            $r = ($dc_bind) ? 0 : 8;

            ldap_close($dc_con);

            return $r;
        }


    } else {

        return 404; // We can't connect to DCs
    }

}



/**
 * Checks validity of login credentials with DB cache
 *
 * @param array $z Username/Password
 * @param string $typ Call type: (compare_with_ad, db_only)
 * @return int Error code: 0=>DB_CONFIRMS, 1=>DB_UNAME, 2=>DB_DISABLED, 3=>DB_PASS
 */
function auth_db($z, $typ='db_only') {

    $z['Uname'] = htmlspecialchars($z['Uname']);

    $z['Pass'] = htmlspecialchars($z['Pass']);

    $user = qry_assoc_row('SELECT ID, ADpass, IsActive, GroupID FROM hrm_users WHERE ADuser=\''.$z['Uname'].'\'');

    if (!$user['ID'])           return 1;   // (DB_UNAME)
    if (!$user['IsActive'])     return 2;   // (DB_DISABLED)

    $z['KGBpass'] = kgb($z['Pass']);

    if ($user['ADpass']!=$z['KGBpass']) {

        if ($typ=='db_only') { // db_only

            return 3;   // (error: DB_PASS)

        } else { // compare_with_ad

            if (AD_CACHE) { // update db cache credentials (pass)

                qry('UPDATE hrm_users SET ADpass=\''.$z['KGBpass'].'\' WHERE ID='.$user['ID']);
            }
        }
    }

    $user['ChannelID'] = get_user_channel($user['ID'], $user['GroupID']);

    // If we got until here, it means we had no errors, so we login.
    auth_login($user);

    return 0;
}



/**
 * Get default channel for specified user
 *
 * @param int $user_id User ID
 * @param int $user_gid User's group ID
 *
 * @return int $user_chnl
 */
function get_user_channel($user_id, $user_gid) {

    $user_chnl = qry_numer_var('SELECT ChannelID FROM hrm_users_data WHERE ID='.$user_id);

    if ($user_chnl) {
        return $user_chnl;
        // Channel will be specified in db, unless it is a new user's first login.
    }


    // Loop channels, and check whether this user's GroupID is within any channel's GroupID sub-branch

    $channels = qry_numer_arr('SELECT ID, GroupID FROM channels WHERE GroupID ORDER BY ID ASC');

    if ($channels) {

        foreach($channels as $chnlid => $gid) {

            if (!isset($chnl_default)) {
                $chnl_default = $chnlid; // First channel will be considered as *default*
            }

            while ($gid) {

                if ($gid==$user_gid) {

                    $user_chnl = $chnlid;
                    break 2;
                }

                $gid = qry_numer_var('SELECT ParentID FROM hrm_groups WHERE ID='.$gid);
            }
        }
    }

    // If the loop didn't help, then use *default* channel
    if (!$user_chnl) {
        $user_chnl = $chnl_default;
    }

    // Update db so we don't have to do this again.
    qry('UPDATE hrm_users_data SET ChannelID='.$user_chnl.' WHERE ID='.$user_id);

    return $user_chnl;
}


/**
 * Get user security level and save it to session
 */
function user_sec_level() {

    $ancestor_groups = group_ancestors(UZGRP);


    $journo_groups = explode(',', cfg_local('arrz', 'descendant_permissions', 'Journo'));
    $itech_groups = explode(',', cfg_local('arrz', 'descendant_permissions', 'Itech'));
    $spec_groups = explode(',', cfg_local('arrz', 'group_permissions', 'StrySecurity1'));

    $ok_groups = array_merge($journo_groups, $itech_groups, $spec_groups);

    foreach ($ancestor_groups as $v) {
        if (in_array($v, $ok_groups)) {
            $_SESSION['UserSecLevel'] = 1;
            return;
        }
    }


    $marketing_groups = explode(',', cfg_local('arrz', 'group_permissions', 'Marketing'));
    $promoter_groups = explode(',', cfg_local('arrz', 'group_permissions', 'Promoter'));
    $spec_groups = explode(',', cfg_local('arrz', 'group_permissions', 'StrySecurity2'));

    $ok_groups = array_merge($marketing_groups, $promoter_groups, $spec_groups);

    foreach ($ancestor_groups as $v) {
        if (in_array($v, $ok_groups)) {
            $_SESSION['UserSecLevel'] = 2;
            return;
        }
    }


    $_SESSION['UserSecLevel'] = 0;
}




/**
 * Pings DC server to check if it's available
 *
 * @param string $host Server IP address
 * @param int $port Port
 * @param int $timeout Connection timeout, in seconds.
 * @return bool
 */
function auth_server_ping($host, $port=389, $timeout=1) {

    $fp = fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$fp) {
        return false; // DC is N/A
        // This will also log a php error, but that's ok because I want to know if any of DCs happens to have problems
    } else {
        fclose($fp);
        return true; // DC is up & running, we can safely connect with ldap_connect
    }
}



/**
 * Performs login, i.e. saves basic user data (which was fetched from db) to session, and writes some log data
 *
 * This user-reader function is called only on login. (Also see function:uzzr() which is called on each script init.)
 *
 * @param array $user User data from db (we need ID, ChannelID, GroupID)
 *
 * @return void
 */
function auth_login($user) {

    global $pathz;


    $_SESSION['UserID'] = $user['ID'];
    // Save User-ID to session, and we will later never change this session variable, it is the basic variable.

    uzzr($_SESSION['UserID']);

    user_sec_level();

    $_SESSION['LastLogin'] = date('Y-m-d H:i:s');
    // Save current time to LastLogin session and save it to db. We will never change it.
    // On each script init we will fetch this value from db (function:uzzr()) and compare it with session variable,
    // to assure that another login with the same credentials didn't occur. If it does occur, we logout..

    $_SESSION['channel'] = $user['ChannelID'];
    // Here we set *initial* value for CHANNEL session. This session variable can be changed by any channel cbo.
    // We save it to CHNL constant, for easier access..

    qry("INSERT INTO log_in_out (UserID, ActionID, Time, IP) VALUES ".
        "({$_SESSION['UserID']}, 1, '{$_SESSION['LastLogin']}', '{$_SERVER['REMOTE_ADDR']}')", LOGSKIP);


    hop($pathz['www_root'].'/start/index.php');
}








