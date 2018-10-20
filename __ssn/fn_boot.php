<?php


// Put here only the functions which are necessary for boot and login process.





/**
 * Defines constants based on logged-on user account
 *
 * This user-reader function is called on each script init. (Also see function:auth_login() which is called only on login.)
 *
 * @param int $id Logged-on user ID
 *
 * @return void
 */
function uzzr($id) {

    $id = intval($id);

    $sql = 'SELECT ID, GroupID FROM hrm_users WHERE ID='.$id;
    $x = qry_assoc_row($sql);

    if (!$x['ID']) {
        exit; // This should never happen
    }

    // We put these values in constants, so that we could use them in functions without passing the values as arguments.
    define('UZID', $x['ID']);
    define('UZGRP', $x['GroupID']);

    if (!defined('LNG')) {
        $user_lng = MULTI_LNG ? qry_numer_var('SELECT LanguageID FROM hrm_users_data WHERE ID='.UZID) : 0;
        define('LNG', (($user_lng) ? $user_lng : APP_LNG));
    }
}


/**
 * Assure that another login with the same credentials didn't occur. If it does occur, we logout..
 */
function prevent_multi_login() {

    global $pathz;

    $sql = 'SELECT Time AS LastLogin FROM log_in_out WHERE UserID='.UZID.' AND ActionID=1 ORDER BY ID DESC LIMIT 1';
    $last_login = qry_numer_var($sql);

    if (($_SESSION['LastLogin']!=$last_login)) {
        hop($pathz['www_root'].'/login.php');
    }
}



/**
 * Get all ANCESTOR GroupIDs (i.e. array which contains specified GID and GIDs of all its parent_groups
 *
 * @param int $gid GroupID
 * @return array
 */
function group_ancestors($gid) {

    $gid = intval($gid);

    $arr = [];

    while ($gid) {
        $arr[] = $gid;
        $gid = qry_numer_var('SELECT ParentID FROM hrm_groups WHERE ID='.$gid);
    }

    return $arr;
}





/**
 * Fetch text from language file
 */
function txarr($fname, $sect='', $r_key=null, $frmt=null) {

    return txt_rdr('lng', $fname, $sect, $r_key, $frmt);
}

/**
 * Fetch cfg
 */
function cfg_global($fname, $sect='', $r_key=null, $frmt=null) {

    return txt_rdr('cfg', $fname, $sect, $r_key, $frmt);
}



/**
 * Fetch text from cfg (or language) file
 *
 * @param string $typ (cfg, lng)
 * @param string $fname Filename (or part of it)
 * @param string $sect Section within cfg file
 * @param string $r_key Return specific key within section within cfg file (returns variable instead of an array)
 * @param string $frmt Formatting (uppercase, lowercase)
 * @param array $opt
 *  - cfg_skip_local (bool): Whether to skip overwriting CFG_VARZ *global* (txt) version with *local* (db) version
 *
 * @return array|string
 */
function txt_rdr($typ, $fname, $sect='', $r_key=null, $frmt=null, $opt=null) {

    global $pathz;


    if ($typ=='cfg') {

        $fpath = $pathz['rel_rootpath'].'../'.(($fname=='boot') ? '_local/' : '').'cfg/cfg_'.$fname.'.php';

        if ($fname=='tablez' || $fname=='setz') {
            $sect = $fname;
        }

    } else { // lng

        $fpath = $pathz['rel_rootpath'].'../_txt/'.LNG.'/'.$fname.'.txt';
    }


    $lines = @file($fpath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Trim lines
    if ($lines) {
        foreach ($lines as $k => $v) {
            $lines[$k] = rtrim($v);
        }
    } else {
        return false;
    }

    if ($sect) {
        $s1 = array_search('['.$sect.']', $lines);
        $s2 = array_search('[/'.$sect.']', $lines);
        if ($s1||$s2) {
            $lines = array_slice($lines, $s1+1, $s2-$s1-1);
            if (!$lines) return false;
        } else {
            return false;
        }
    }


    if ($typ=='lng' && $fname=='blocks') {
        return implode(PHP_EOL, $lines);
    }


    $r = [];

    foreach ($lines as $line) {

        // Skip lines which begin with specific characters ('/' is for the comments, '[' is for the section tags)
        if (in_array($line[0], ['/', '#', '['])) {
            continue;
        }

        $x_key  = strstr($line, ' ', true);             // name: the part before the needle (spacer)
        $x_value = trim(strstr($line, ' ', false));     // value: the part after the needle (spacer)

        // Clean comments (separator is '//') from lines
        $n = strpos($x_value, '//');                    // value: the part before the needle (// - comment sign)
        if ($n) {
            $x_value = substr($x_value, 0, $n-1);
        }

        // Format
        if ($frmt) {
            if ($frmt=='uppercase') {
                $x_value = mb_strtoupper($x_value);
            } elseif ($frmt=='lowercase') {
                $x_value = mb_strtolower($x_value);
            }
        }

        // OLA!
        $r[$x_key] = $x_value;

        // If return key is specified, return it as soon as you find it
        if ($r_key!==null && $x_key==$r_key) {
            return $r[$x_key];
        }
    }


    // For CFG_VARZ, *local* (db) version overwrites *global* (txt) version
    if ($typ=='cfg' && $fname=='varz' && empty($opt['cfg_skip_local'])) {
        $r_local = cfg_local('varz', $sect);
        if ($r_local) {
            $r = array_merge($r, $r_local);
        }
    }


    if ($r_key===null) {
        return $r;
    } else {
        return null;
    }

}



/**
 * Get LOCAL CFG (from DB)
 *
 * @param string $typ Type (varz, arrz) - whether we are fetching from *varz* table or *arrz* table
 * @param string $sct Section
 * @param int $r_key Return key (when we want to fetch not the whole section array but only a specific value from it)
 *                  (only for *arr* type)
 * @param array $opt
 *  - queue (bool): Sort by Queue column (only for *arr* type)
 *
 * @return array|string $r
 */
function cfg_local($typ, $sct, $r_key=null, $opt=null) {

    $r = [];

    if ($typ=='varz') {

        if ($r_key) {

            $r = qry_numer_var('SELECT Value FROM cfg_varz WHERE Section=\''.$sct.'\' AND Name=\''.$r_key.'\'');

        } else {

            $result = qry('SELECT Name, Value FROM cfg_varz WHERE Section=\''.$sct.'\'');
            while ($line = mysqli_fetch_assoc($result)) {
                $r[$line['Name']] = $line['Value'];
            }
        }


    } elseif ($typ=='arrz') {

        $clnz = ($r_key) ? 'Value' : 'Name, Value';

        $where[] = 'Section=\''.$sct.'\'';

        if ($r_key) {
            $where[] = 'Name=\''.$r_key.'\'';
        }

        $sql = 'SELECT '.$clnz.' FROM cfg_arrz WHERE '.implode(' AND ', $where);

        if (!$r_key && !empty($opt['queue'])) {
            $sql .= ' ORDER BY Queue ASC';
        }

        if ($r_key) {

            $r = qry_numer_var($sql);

        } else {

            $result = qry($sql);
            while ($line = mysqli_fetch_assoc($result)) {
                $r[$line['Name']] = $line['Value'];
            }
        }
    }

    return $r;
}



/**
 * Establish a connection to the MySQL Server
 *
 * @param string $server Server host name
 * @param string $user MySQL user name
 * @param string $pass MySQL user pass
 * @param string $db DB name
 *
 * @return object $dbcon Connection to MySQL Server
 */
function connect_db($server, $user, $pass, $db) {

    $dbcon = mysqli_connect($server, $user, $pass, $db);

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

    if (!$dbcon) return null;

    //mysqli_query($dbcon, "SET CHARACTER SET utf8");
    //mysqli_query($dbcon, "SET NAMES utf8");

    mysqli_set_charset($dbcon, "utf8");

    return $dbcon;
}




/**
 * Add record to textual log file
 *
 * @param string $typ Type of the performed action which we want to log
 * @param array $argz Arguments array
 * @return void
 */
function log2file($typ, $argz=null){

    global $tx;

    $glue = ' | ';

    $a = [];
    $a[] = date('Y-m-d H:i:s');
    //$a[] = strtoupper($typ);

    if (defined('UZID')) {
        $a[] = 'UID:'.UZID;
    }

    switch ($typ) {

        case 'srpriz': // Surprise! Not errors, but unexpected situations. Stuff which should never happen.
            foreach($argz as $k => $v) {
                $a[] = strtoupper($k).': '.$v;
            }
            $a[] = 'REF:'.@$_SERVER["HTTP_REFERER"];
            $a[] = 'REQ:'.@$_SERVER["REQUEST_URI"];
            break;

        case 'mysql': // mysql errors
            $a[] = 'REF: '.@$_SERVER["HTTP_REFERER"];
            $a[] = 'REQ: '.@$_SERVER["REQUEST_URI"];
            $a[] = 'ERR: ('.mysqli_errno($GLOBALS['db']).') '.mysqli_error($GLOBALS['db']);
            $a[] = 'SQL: '.$argz['sql'];
            $a[] = '---';
            $glue = PHP_EOL;
            break;

        case 't_exec': // Script execution time
            $a[] = 'T: '.$argz['T'];
            $a = [implode($glue, $a)];
            $a[] = 'REQ:'.@$_SERVER["REQUEST_URI"];
            $a[] = 'REF:'.@$_SERVER["HTTP_REFERER"];
            $a = array_reverse($a);
            $glue = PHP_EOL;
            break;

        case 'access':
        case 'args':
            $a[] = 'REF:'.@$_SERVER["HTTP_REFERER"];
            $a[] = 'REQ:'.@$_SERVER["REQUEST_URI"];
            break;

        case 'actdir':
            $a[] = $argz['ACT'];
            $a[] = $argz['SAM'];
            break;

        case 'epg-bc':
            $a[] = 'FILM-ID:'.$argz['ID'];
            $a[] = 'REAL/SAVED:'.$argz['BCcur'];
            break;

        case 'login':
            $a[] = $argz['field'];
            $a[] = 'UNAME:'.$argz['uname'];
            $a[] = 'IP:'.@$_SERVER["REMOTE_ADDR"];
            break;

        case 'robot':
            $a[] = $argz['field'];
            $a[] = $argz['code'];
            break;

        case 'robot-ok':
            $a[] = @$_SERVER["REQUEST_URI"];
            $a[] = 'IP:'.@$_SERVER["REMOTE_ADDR"];
            break;
    }


    $msg = implode($glue, $a).PHP_EOL.PHP_EOL;

    $fname = $typ.'.log';

    file_put_contents(LOGDIR.$fname, $msg, FILE_APPEND);


    switch ($typ) {

        case 'mysql':
            omg_put('danger', $tx['MSG']['db_error'], '[SQL Error]');
            break;

        case 'access':
            omg_put('warning', $tx['MSG']['no_permissions'], $tx['MSG']['access_denied']);
            break;

        case 'args':
            omg_put('warning', $tx['MSG']['page_request']);
            break;

        case 'epg-bc':
            omg_put('info', $tx[SCTN]['MSG']['bccur_correction']);
            break;
    }
}
















/**
 * Performs a DB query and handles logging procedure
 *
 * @param string $sql SQL query
 * @param array $log Logging data and instructions
 *  - log_all_skip (bool) - Whether to skip all logging procedures. For DEL/UPD if x_id is not set, log_all_skip is set to true.
 *  - log_sql_skip (bool) - Whether to skip sql log
 *  - tbl_id (int) - Table ID, if we don't want to use *real* TableID (which we get from *tbl_name*)
 *  - act_id (int) - Action ID, if we don't want to use *default* action IDs
 *  - x_id (int) - Target ID, i.e. id of the row which is the subject of the query
 *                 (Required only for UPD and DEL, as INS produces ID itself, and SELECT is not logged.)
 * @param bool $arhv Whether to target ARHV server
 *
 * @return mixed $r On success: resource; On failure: false; On INSERT: ID of the new row
 */
function qry($sql, $log=null, $arhv=false) {

    if (!isset($log['log_all_skip']))       $log['log_all_skip'] = false;
    if (!isset($log['log_sql_skip']))       $log['log_sql_skip'] = false;

    if (!$arhv) {
        $dbcon = $GLOBALS['db'];
    } else {
        $dbcon = $GLOBALS['db_arhv'];
        $log['log_all_skip'] = true;
    }


    $r = mysqli_query($dbcon, $sql);

    /* ERROR LOG */

    if (!$r) { // Error. Write query to mysql error log.
        log2file('mysql', ['sql' => $sql]);
        return false;
    }


    if ($sql[0]=='S') { // to speed-up SELECT queries
        return $r;
    }


    $log['act'] = strtolower(substr(ltrim($sql), 0, 3));

    if (!in_array($log['act'], ['ins', 'upd', 'del', 'sel'])) {
        log2file('srpriz', ['type' => '_qry__unexpected_sql_action', 'act' => $log['act'], 'sql' => $sql]);
        // AFAIK, queries in this app always start with one of those four actions
    }


    // For INSERT: get ID for the return!

    if ($log['act']=='ins') {

        $r = $ins_id = mysqli_insert_id($dbcon);   // For INSERT actions fetch and return ID of the inserted row

        if (!isset($log['x_id'])) {
            $log['x_id'] = $ins_id;
        }
    }


    /* SUCCESS LOGZ */

    // Logging is skipped altogether if 'log_all_skip' is set true
    // Also, we don't log UPDATE and DELETE unless 'x_id' is specified. (INSERT determines x_id itself.)
    // Also, no need to log SELECT queries.
    // Also, never log actions of a robot.

    if (!$log['log_all_skip'] && $log['act']=='sel' || (defined('ROBOT') && ROBOT==1)) {
        $log['log_all_skip'] = true;
    }

    if (!$log['log_all_skip'] && empty($log['x_id'])) { // For DEL/UPD if x_id is not set, log_all_skip is set to true.
        $log['log_all_skip'] = true;
    }

    if (!$log['log_all_skip']){
        qry_log($sql, $log);
    }


    return $r;
}




/**
 * Logs succesful DB query into QUERY LOG table
 *
 * @param string $sql SQL query
 * @param array $log Logging data and instructions
 *  - log_sql_skip (bool) - Whether to skip sql log
 *  - act (string) - Action
 *  - tbl_name (string) - Table name, when we don't want to use *real* Table name
 *  - tbl_id (int) - Table ID, when we don't want to use *real* TableID (which we get from *tbl_name*)
 *  - act_id (int) - Action ID, when we don't want to use *default* action IDs
 *  - x_id (int) - Target ID, i.e. id of the row which is the subject of the query
 *                 (Needed only for UPD and DEL, as INS produces its ID.)
 * @return void
 */
function qry_log($sql, $log=null) {


    if ($sql==null) {
        $log['log_sql_skip'] = true;
    }


    if (!isset($log['tbl_name'])) {

        // Extract TABLE-NAME from sql

        $start = ($log['act']=='upd') ? 7 : 12;      // UPDATE x, INSERT INTO x, DELETE FROM x

        $log['tbl_name'] = substr($sql, $start, strpos($sql, ' ', $start) - $start);
    }


    // Get TABLE-ID for extracted table-name if it is not specificaly set through data array

    if (!isset($log['tbl_id'])) {

        $log['tbl_id'] = tablez('id', $log['tbl_name']);

        if (!$log['tbl_id']) {
            log2file('srpriz', ['type' => '_qry__tbl_name404', 'tbl_name' => $log['tbl_name']]);
        }
    }


    // Get ACTION-ID if it is not specificaly set through data array

    if (!isset($log['act_id'])) {

        switch ($log['act']) {

            case 'ins': $log['act_id'] = 1; break;
            case 'upd': $log['act_id'] = 2; break;
            case 'del': $log['act_id'] = 3; break;

            // DEFAULT action IDs
            // (for complete list see: TXT-ARR [log_actions])
        }
    }


    // Write log to QUERY LOG table

    $q = 'INSERT INTO log_qry (XID, Action, TableName, Section, Script, UID, TermAdd, TableID, ActionID) '.
        'VALUES ('.$log['x_id'].', \''.$log['act'].'\', \''.$log['tbl_name'].'\', \''.SCTN.'\', '.
        '\''.$_SERVER['PHP_SELF'].'\', '.UZID.', now()'.', '.$log['tbl_id'].', '.$log['act_id'].')';

    mysqli_query($GLOBALS['db'], $q);

    $log['qry_id'] = mysqli_insert_id($GLOBALS['db']);

    if (!$log['qry_id']) {
        log2file('srpriz', ['type' => '_qry__log_qry_fail', 'sql' => $q]);
        $log['log_sql_skip'] = true;
    }


    // Write sql to SQL LOG table

    if (!$log['log_sql_skip']){
        $q = 'INSERT INTO log_sql (ID, qrySQL) VALUES ('.$log['qry_id'].', \''.$sql.'\')';
        mysqli_query($GLOBALS['db'], $q);
    }


}






/**
 * Wraper for one-row mysqli_fetch_assoc()
 */
function qry_assoc_row($sql, $log=null) {
    $result = qry($sql, $log);
    if ($result) {
        $r = mysqli_fetch_assoc($result);
    } else {
        $r = null;
    }
    return $r;
}


/**
 * Wraper for multi-row mysqli_fetch_assoc()
 */
function qry_assoc_arr($sql, $log=null) {
    $r = [];
    $result = qry($sql, $log);
    while ($line = mysqli_fetch_assoc($result)) {
        $r[] = $line;
    }
    return $r;
}


/**
 * Wraper for one-row mysqli_fetch_row()
 */
function qry_numer_row($sql, $log=null) {
    $result = qry($sql, $log);
    if ($result) {
        $r = mysqli_fetch_row($result);
    } else {
        $r = null;
    }
    return $r;
}


/**
 * Wraper for multi-row mysqli_fetch_row() - Fetch numerical array from db
 *
 * @param string $sql SQL query
 * @param array $log Logging data and instructions (see: qry())
 *
 * @return array $r Data fetched from sql query to db
 */
function qry_numer_arr($sql, $log=null) {

    $r = [];
    $result = qry($sql, $log);

    while ($line = mysqli_fetch_row($result)) {

        switch (count($line)) {

            case 1: // If only ONE column is required, then index for the return array will be simply incremental.
                $r[] = $line[0];
                break;

            case 2: // For TWO columns, first value will be used for index, and the second as the value for the return array.
                $r[$line[0]] = $line[1];
                break;

            default: // More than two columns: incremental index + array
                $r[] = $line;
                break;
        }
    }

    return $r;
}


/**
 * Fetch one cell from db
 */
function qry_numer_var($sql, $log=null) {
    list($r) = qry_numer_row($sql, $log);
    return $r;
}










/**
 * Returns an array with server/file paths
 *
 * @return array
 * - doc_root       - document (app) root, relative to web server htfiles root dir	(e.g. /jrt/www/prodesk)
 * - dir_path       - current directory path, relative to app root	                (e.g. /test/sub)
 * - dir_1st        - first-level directory, which determines SCTN	                (e.g. test)
 * - rel_rootpath   - relative path to app root	                                    (e.g. ../../)
 * - www_root       - www root	                                                    (e.g. https://desk.jrt.tv)
 * - filename       - filename of the currently executing script                    (e.g. tester)
 */

function pathz() {

    // CRON seems to be executing php scripts from IP address, instead DNS. Thus we have to adjust the paths for CRON
    if (CLI_CRON) {
        $doc_root = $_SERVER['HOME'].'/404'; // vbdo
    } else {
        $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'],'/');
    }

    $dir_path = str_replace($doc_root, '', dirname($_SERVER['SCRIPT_FILENAME']));
    // $_SERVER['SCRIPT_FILENAME'] - absolute pathname of the currently executing script

    if ($dir_path) {

        $dir_1st = trim($dir_path,'/');
        if (strpos($dir_1st,'/')) $dir_1st = strchr($dir_1st, '/', true); //first_level directory

    } else { //root
        $dir_1st = $dir_path;
    }

    $rel_rootpath = str_repeat('../', substr_count($dir_path,'/'));

    $www_root = ((@$_SERVER["HTTPS"]=='on') ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'];

    $filename = basename($_SERVER['PHP_SELF'], '.php');


    return [
        'doc_root'			=> $doc_root,
        'dir_path' 			=> $dir_path,
        'dir_1st' 			=> $dir_1st,
        'rel_rootpath' 	    => $rel_rootpath,
        'www_root' 			=> $www_root,
        'filename' 			=> $filename,
    ];

}



/**
 * Redirects script to specified script using header() function.
 *
 * Additionally, logs this as an error, and/or adds a note to OMG variable
 *
 * @param string $typ Type of the action which triggered the call
 * @param string $target Script to which the redirection will be targeted (if it cannot return to referer)
 * @return void
 */
function redirector($typ='', $target=''){

    global $tx, $pathz;


    switch ($typ) {


        case 'access':
        case 'args':

            log2file($typ);

            if (isset($_SERVER["HTTP_REFERER"]) && basename($_SERVER["HTTP_REFERER"])!=basename($_SERVER["REQUEST_URI"])) {
                $target = $_SERVER["HTTP_REFERER"];
            } else {
                $target = $pathz['www_root'].dirname($_SERVER['PHP_SELF']).'/'.$target;
            }

            break;


        case 'delete':
        case 'restore':
        case 'purge':

            omg_put('info', $tx['MSG'][$typ.'d']);

            // If script directory is not defined in the target url, add CURRENT directory.
            if ($target[0]!='/') {
                $target = dirname($_SERVER['PHP_SELF']).'/'.$target;
            }
            $target = $pathz['www_root'].$target;

            break;
    }

    hop($target);
}








/**
 * Add a message to OMG session variable
 *
 * @param string $typ Message type: (danger, warning, info, success)
 * @param string $msg Message text
 * @param string $lbl Label text
 * @return void
 */
function omg_put($typ, $msg='', $lbl=null) {

    global $tx;

    if (!in_array($typ, ['danger', 'warning', 'info', 'success'])) {
        $typ = 'danger';
    }

    if ($lbl===null) {
        $lbl = $tx['MSG'][$typ];
    }

    $_SESSION['omg'][] = [$typ, $msg, $lbl];
}



/**
 * Read and print messages from OMG session variable
 *
 * @return void
 */
function omg_get() {

    if (!isset($_SESSION['omg'])) {
        return;
    }

    echo '<div id="omg" class="omg navbar navbar-static-top"><div class="container">';

    // Close button
    echo '<a class="pull-right" href="#" onClick="javascript:document.all.omg.style.display=\'none\'; return false;">'.
        '<span class="glyphicon glyphicon-remove"></span></a>';

    foreach($_SESSION['omg'] as $omg) {

        if ($omg[2]) $omg[2] = ' '.$omg[2]; // label

        echo
            '<div>'.
            '<span class="text-uppercase label label-'.$omg[0].'"><span class="glyphicon glyphicon-flash"></span>'.$omg[2].'</span>'.
            '<span class="dsc">'.$omg[1].'</span>'.
            '</div>';
    }

    echo '</div></div>';

    unset($_SESSION['omg']);
}




/**
 * Warney bar output
 *
 * @return void
 */
function warney_bar() {

    global $tx, $cfg;

    echo '<div id="warney" class="omg navbar navbar-fixed-top"><div class="container">';

    // Close button
    echo '<a class="pull-right" href="#" onClick="warney_cancel(); return false;">'.
        '<span class="glyphicon glyphicon-remove"></span></a>';

    // Restart button
    echo '<a id="restarter" class="pull-right text-uppercase" href="#" onClick="location.reload(true);">'.
        $tx['LBL']['restart'].'</a>';

    echo
        '<span class="text-uppercase label">'.
            '<span class="glyphicon glyphicon-flash"></span> '.$tx['LBL']['update'].'</span>'.
            '<span class="dsc">'.
                '<span>'.$tx[SCTN]['MSG']['epg_restart'].'&nbsp;</span>'.
                '<span id="warney_cntdown">'.$cfg[SCTN]['epguptd_warney_cnt'].'</span>..'.
            '</span>'.
        '</span>';

    echo '</div></div>';
}




/** Used for counting the script execution time */
function getmicrotime() {
    list($usec, $sec) = explode(' ',microtime());
    return ((float)$usec + (float)$sec);
}







/**
 * Wrapper for header()
 */
function hop($target) {

    t_exec();

    header('Location: '.$target);
    exit;
}



/**
 * Measure script execution time
 */
function t_exec() {

    global $cfg;

    if (empty($cfg['log_exec_time_limit'])) {
        return;
    }

    $script_time = substr((getmicrotime() - SCRIPT_START), 0, 5);

    if ($script_time > $cfg['log_exec_time_limit']) {

        log2file('t_exec', ['T' => $script_time]);
    }

    return $script_time;
}


