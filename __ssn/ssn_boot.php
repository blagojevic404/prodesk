<?php
/**
 * All scripts (even login.php) require this file. This is the init script.
 */

require 'fn_boot.php';




/* PHP engine config*/

ini_set('error_reporting', E_ALL); // This setting has PHP_INI_ALL changing mode, so it can be changed this way
mb_internal_encoding('UTF-8');




/* CONSTANTS and arrays */


define('UZID_ALFA', 1);

define('CLI_CRON', ((!(bool) $_SERVER['SERVER_PROTOCOL']) ? true : false));
// this server variable should be NULL only if script is invoked from cron
// vbdo: Check which *other* server variables are NULL when the script is invoked from CRON.. e.g. SERVER_NAME is NULL

define('DEV_SERVER', 'prodesk.ura');

define('SERVER_TYPE', ((in_array($_SERVER["SERVER_NAME"], ['localhost', DEV_SERVER])) ? 'dev' : 'prod'));

define('DB_SERVER', '127.0.0.1');
define('DB_SCHEMA', 'prodesk');

define('LOGSKIP', ['log_all_skip' => true]);

$pathz = pathz();

define('LOCALDIR', $pathz['rel_rootpath'].'../_local/');
define('LOGDIR', LOCALDIR.'logs/');

define('SCRIPT_START', getmicrotime());


/* vb2do
require_once $pathz['rel_rootpath'].'../class/ProDesk/Autoloader.php';
$autoloader = new \ProDesk\Autoloader();
spl_autoload_register([$autoloader, 'load']);
*/


$tablez = cfg_global('tablez');
$stz = cfg_global('setz');


// Read data from CFG boot files

$db_boot = cfg_global('boot', 'db');
$cfg_boot = cfg_global('boot', 'cfg');

if (!$db_boot) {
    exit("Invalid SERVER_NAME!");
} else {
    define('DB_USER', $db_boot['u']);
    define('DB_PASS', descrambler($db_boot['p']));
    unset($db_boot);
}

date_default_timezone_set($cfg_boot['Timezone']);
define('APP_LNG', $cfg_boot['APP_LNG']); // Application default language


if (SERVER_TYPE=='dev') { // (development)

    ini_set('display_errors', true);

    define('MULTI_LNG', true);

} else { // prod (production)

    $login = [
        'AD_List' => cfg_global('boot', 'dc'),
        'AD_Domain' => $cfg_boot['AD_Domain'],
        'AD_HTTPS' => $cfg_boot['AD_HTTPS']
    ];

    ini_set('display_errors', (($pathz['filename']!='tester') ? false : true));
    // Don't display errors in browser (but they are still logged)

    define('MULTI_LNG', @$cfg_boot['MULTI_LNG']);
    // Whether to use multi-lingual support (enables user to chose language for interface)
}

unset($cfg_boot);




/* DB connection */

$GLOBALS["db"] = connect_db(DB_SERVER, DB_USER, DB_PASS, DB_SCHEMA);
if (!$GLOBALS["db"]) exit;




/* SESSION start */

session_start();






if (isset($user) && isset($pass)) { // LOGIN ATTEMPT


    require 'fn_login.php';

    /* Whether to use Active Directory authentication */
    define('AD_AUTH', ((SERVER_TYPE=='dev') ? 0 : 1));
    //define('AD_AUTH', ((@$_SERVER["REMOTE_ADDR"]=='10.51.6.57') ? 1 : 0) ); // for testing

    /* Whether to cache AD credentials to local db */
    define('AD_CACHE', 1);

    define('LNG', APP_LNG); // While no user has yet logged in, we must use APP_LNG as the LNG

    $login['Uname'] = $user;
    $login['Pass'] = $pass;

    $error_msg = auth($login);

    unset($login);


} else { // Either NORMAL LOGGED-IN PAGE REQUEST, or ROBOT, or ERROR


	if (isset($_SESSION['UserID']) && !defined('ROBOT')) { // NORMAL LOGGED-IN PAGE REQUEST


        uzzr($_SESSION['UserID']);

        // Channel cbo change updates the channel session variable / CHNL constant
        if (isset($_POST['postCHNL'])) {

            $_SESSION['channel'] = max(1, min(9, intval($_POST['postCHNL'])));
            hop(((empty($_POST['chnl_referer'])) ? $pathz['www_root'].$_SERVER['REQUEST_URI'] : $_SERVER['HTTP_REFERER']));

            // HTTP_REFERER contains url attributes, e.g. http://prodesk.ura/epg/epgs.php?typ=archive
            // REQUEST_URI for the same page would be: /epg/epgs.php?
            // So, if you need to keep the url attributes when reloading the page, use $header_cfg['chnl_referer'].
            // If you specifically want to lose the url attributes, e.g. on LIST pages (because you want to reset the
            // filters), then use REQUEST_URI..
            // In most cases, it is irrelevant..
        }

        // Index-shorcuts also can update the channel session variable / CHNL constant
        if (isset($_GET['getCHNL']) && ($_SESSION['channel']!=$_GET['getCHNL'])) {
            $_SESSION['channel'] = max(1, min(9, intval($_GET['getCHNL'])));
        }


        // Up until this point, you can use only functions from fn_boot.php, because LIB & FN are not loaded yet..

        require $pathz['rel_rootpath'].'../__fn/fn.php';
        require 'ssn_sets.php';
        require $pathz['rel_rootpath'].'../__fn/lib.php';


        if (!setz_get('multi_login')) {
            prevent_multi_login();
        }

        // MDF situation: First, all MODIFY pages contain *modify* in filename. Then, we also check if
        // attribute *id* is set, because if it is not then it is probably NEW instead of MDF situation.
        if (strpos($_SERVER['PHP_SELF'],'modify') && preg_match('/[?&]id=/',$_SERVER['REQUEST_URI'])) {

            require $pathz['rel_rootpath'].'../__fn/fn_mdflog.php';

            $header_cfg['do_mdflog'] = true;
        }


	} elseif (defined('ROBOT') && ROBOT==1) { // ROBOT


        uzzr(6); // 6 = ROBOT user account.

        // ROBOT account is used primarily for CRON scripts. There is also script for TESTER-SYNC.
        // To set ROBOT, just put ROBOT constant definition before calling ssn_boot - "define('ROBOT', 1);"
        // Optionally, also put "log2file('robot-ok')" after calling ssn_boot, to log succesful robot running.

        // NOTE: by using ROBOT account, the script will avoid authentication. Therefore, use this only for scripts
        // which are completely benign, i.e. they do not display any information
        // and running them unexpectedly at any time cannot produce problems.

        require $pathz['rel_rootpath'].'../__fn/fn.php';
        require 'ssn_sets.php';
        require $pathz['rel_rootpath'].'../__fn/lib.php';


	} else { // ERROR


        hop($pathz['www_root']."/login.php"); // bye
    }


}


