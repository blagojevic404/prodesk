<?php
/**
 * TESTER (Dev + Prod)
 *
 * How to use PROD side:
 * - Open the DEV side application using HTTPS (it is necessary for the PROD-DEV connection)
 * - Open PROD side (on the same computer)
 * - When you make changes to /_local/test/tester_prod.php (on DEV side), and then open this tester script on PROD side,
 *   you can upload your changes to PROD simply by clicking the blue button in upper right corner (with a datetime on it).
 *   Note: Perhaps you will have to refresh the page to see the changes.
 *
 * You can use $_GET['empt'] to delete/reset tester on PROD side
 *
 */


require '../../__ssn/ssn_boot.php';

pms('admin', 'maestro', null, true);


define('TEST_INC', LOCALDIR.'test/tester_'.SERVER_TYPE.'.php'); // SERVER_TYPE = dev/prod


/* You can use $_GET['empt'] to delete/reset tester on PROD side */

if (!empty($_GET['empt']) && SERVER_TYPE!='dev') {

    file_put_contents(TEST_INC, '');
}


/*************************** HEADER ****************************/

$header_cfg['subscn'] = 'test';
$header_cfg['bsgrid_typ'] = 'admin_ad';

/*CSS*/
$header_cfg['css'][] = 'details.css';


if (SERVER_TYPE!='dev') {

    $header_cfg['js'][] = 'admin/tester.js';
    $header_cfg['ajax'] = true;
    $header_cfg['js_lines'][]  = 'var g_dev_server = \''.DEV_SERVER.'\';';
}

require '../../__inc/_1header.php';
/***************************************************************/



echo '<div class="row"><div class="col-xs-12">';


printf('<p class="clearfix">%s'.SERVER_TYPE.' / '.date('Y-m-d H:i:s', filemtime(TEST_INC)).'%s</p>',
    ((SERVER_TYPE!='dev') ? '<a class="mod_time" href="javascript:testsync_init()">' : '<span class="mod_time">'),
    ((SERVER_TYPE!='dev') ? '</a>' : '</span>'));


require TEST_INC;



echo '</div></div>';

require '../../__inc/_2footer.php';
