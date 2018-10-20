<?php

define('DATENOW', date("Y-m-d"));
define('TIMENOW', date("Y-m-d H:i:s"));



/* CHANNEL-BASED CONSTS */

// We use session variable only to pass values between scripts. We use constant CHNL within scripts
// (for the reason of simplicity only).
define('CHNL', (isset($_SESSION['channel'])) ? $_SESSION['channel'] : 1);

// It would be easy to define CHNLTYP here too, but the problem is: when user uses two or more tabs in browser,
// and opens scripts from *different* channels, then CHNL session can jump from one channel to another
// and thus is not reliable..)






/* COMMON TXT */

$tx['LBL'] = txarr('labels', 'common');
$tx['MSG'] = txarr('messages', 'common');

$tx['NAV'] = txarr('common.nav');
$tx['DAYS']['months']       = txarr('common.days', 'months');
$tx['DAYS']['wdays']        = txarr('common.days', 'wdays');
$tx['DAYS']['wdays_short']  = txarr('common.days', 'wdays_short');

if (substr($pathz['filename'], 0, 5)=='list_') {
    $tx['LST'] = txarr('labels', 'list');
}


// COMMON Settings
$cfg = cfg_global('varz', 'common');


// Initialize header config array;
$header_cfg = [];





/** Define SECTIONS, i.e. application subprograms.
 *
 * Each section has its own directory. Thus, the section can be always determined from script url.
 * dir - Directory
 * ttl - Section title, to be used in page title tag
 * url - Homepage for the section
*/

$nav_sctnz = [
    'ndx'	=> ['dir' => 'start', 	'ttl' => $tx['NAV'][1], 	'url' => 'index.php'],
    'dsk'   => ['dir' => 'desk', 	'ttl' => $tx['NAV'][2], 	'url' => 'list_stry.php'],
    'epg'   => ['dir' => 'epg', 	'ttl' => $tx['NAV'][7],     'url' => 'epgs.php?typ=real'],
    'hrm'	=> ['dir' => 'hrm', 	'ttl' => $tx['NAV'][3], 	'url' => 'org.php'],
];

if (pms('admin')) {
    $nav_sctnz['admin'] = ['dir' => 'admin', 	'ttl' => $tx['NAV'][8],     'url' => 'report.php'];
}

/* Loop the section array in order to set the SCTN constant, by matching the script directory. */
foreach($nav_sctnz as $k => $v) {
	if ($v['dir'] == $pathz['dir_1st']) {
		define('SCTN', $k);
		break;
	}
}
if (!defined('SCTN')) define('SCTN', '');


/* Define SUBSECTIONS (for a section), i.e. particular application modules/scripts.
 *
 * On top of each script we will set $header_cfg['subscn'], thus connecting it with the subsection of the same key.
 * ttl - Subsection title, to be used in page title tag
 * url - Url of the subsection
 * div - Whether to put vertical divider *after* this item
 * rgt - Whether to float right
*/

$nav_subz = get_nav_subz(SCTN);

function get_nav_subz($sctn) {

    global $tx;

    switch ($sctn) {

        case 'ndx':

            $nav_subz = [
                'index' => ['ttl' => $tx['NAV'][201], 'url' => 'index.php'],
                'setz'  => ['ttl' => $tx['NAV'][202], 'url' => 'settings.php', 'rgt' => true],
            ];
            break;

        case 'hrm':

            $nav_subz = [
                'org'   => ['ttl' => $tx['NAV'][31],  'url' => 'org.php'],
                'uzr'   => ['ttl' => $tx['NAV'][32],  'url' => 'list_uzr.php'],
            ];
            break;

        case 'dsk':

            $nav_subz = [
                'flw' 	=> ['ttl' => $tx['NAV'][29],  'url' => 'list_flw.php', 'div' => true],
                'stry'  => ['ttl' => $tx['NAV'][27],  'url' => 'list_stry.php'],
                'cvr'   => ['ttl' => $tx['NAV'][24],  'url' => 'list_cvr.php', 'div' => true],
                'prgm'  => ['ttl' => $tx['NAV'][22],  'url' => 'list_prgm.php'],
                'tmz'   => ['ttl' => $tx['NAV'][23],  'url' => 'tmz.php'],
                'trash' => ['ttl' => $tx['NAV'][203], 'url' => 'list_trash.php', 'rgt' => true],
                'setz'  => ['ttl' => $tx['NAV'][202], 'url' => 'settings.php', 'rgt' => true],
            ];
            break;

        case 'epg':

            $nav_subz = [
                'real' 	    => ['ttl' => $tx['NAV'][71], 'url' => 'epgs.php?typ=real'],
                'plan' 	    => ['ttl' => $tx['NAV'][70], 'url' => 'epgs.php?typ=plan', 'div' => true],
                'archive'   => ['ttl' => $tx['NAV'][72], 'url' => 'epgs.php?typ=archive'],
                'tmpl' 	    => ['ttl' => $tx['NAV'][73], 'url' => 'epgs.php?typ=tmpl', 'div' => true],
                'film' 	    => ['ttl' => $tx['NAV'][77], 'url' => 'list_film.php'],
                'mkt' 	    => ['ttl' => $tx['NAV'][74], 'url' => 'list_mkt.php'],
                'prm' 	    => ['ttl' => $tx['NAV'][75], 'url' => 'list_prm.php'],
                'clp' 	    => ['ttl' => $tx['NAV'][76], 'url' => 'list_clp.php'],
                'setz'      => ['ttl' => $tx['NAV'][202], 'url' => 'settings.php', 'rgt' => true],
            ];
            break;

        case 'admin':

            $nav_subz = [
                'report' 	=> ['ttl' => $tx['NAV'][80], 'url' => 'report.php', 'div' => true],
                'php'       => ['ttl' => $tx['NAV'][84], 'url' => 'phpinfo.php'],
                'log'       => ['ttl' => $tx['NAV'][82], 'url' => 'log.php'],
                'ad'        => ['ttl' => $tx['NAV'][83], 'url' => 'actdir.php', 'div' => true],
                'ftp'       => ['ttl' => $tx['NAV'][86], 'url' => ((pms('admin', 'spec')) ? 'ftp.php' : '')],
                'lng'       => ['ttl' => $tx['NAV'][81], 'url' => ((pms('admin', 'spec')) ? 'lng.php' : '')],
                'tools'     => ['ttl' => $tx['NAV'][87], 'url' => 'tools.php'],
                'setz'      => ['ttl' => $tx['NAV'][202], 'url' => 'settings.php', 'rgt' => true],
            ];

            if (UZID==UZID_ALFA) {
                $nav_subz['test'] = ['ttl' => $tx['NAV'][85], 'url' => 'tester.php'];
            }

            break;

        default:
            $nav_subz = [];
    }

    return $nav_subz;
}



/* SECTION TXT */
$tx[SCTN]['LBL'] = txarr('labels', SCTN);
$tx[SCTN]['MSG'] = txarr('messages', SCTN);


// SECTION settings
$cfg[SCTN] = cfg_global('varz', SCTN);

