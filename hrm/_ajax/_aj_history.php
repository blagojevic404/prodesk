<?php
/**
 * USER LOGIN_HISTORY
 */

require '../../../__ssn/ssn_boot.php';



$x['uid'] = (isset($_GET['uid'])) ? wash('int', $_GET['uid']) : 0; if (!$x['uid']) exit;

$x['limit'] = (isset($_GET['limit'])) ? wash('int', $_GET['limit']) : 0; if (!$x['limit']) $x['limit'] = 40;


uzr_usage($x['uid'], $x['limit']);
