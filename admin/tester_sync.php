<?php
/**
 * This script performs synchronisation of tester_prod.php script from DEV to PROD server.
 *
 * It is called from PROD side, via AJAX on tester.php.
 * It is called two times:
 * - on first call it fetches contents of tester_prod.php script from DEV side.
 * - on second call it writes these contents to tester_prod.php script on PROD side.
 */

define('ROBOT',1);

require '../../__ssn/ssn_boot.php';

log2file('robot-ok'); // for troubleshooting



header('Access-Control-Allow-Origin: https://prodesk.jrt.tv'); // We need this in order to enable AJAX access from PROD side

define('TEST_INC', '../../_local/test/tester_prod.php');



if (SERVER_TYPE=='dev') { // DEV server

    $tester_inc = file_get_contents(TEST_INC, null, null, 6); // We offset 6 chars for "<?php\n"

    echo $tester_inc;

} elseif (isset($_POST['src'])) { // PROD server

    file_put_contents(TEST_INC, '<?php'.PHP_EOL.$_POST['src']);

    echo 1;
}
