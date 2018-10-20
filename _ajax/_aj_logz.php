<?php
require '../../__ssn/ssn_boot.php';



$x['xid'] = (isset($_GET['xid'])) ? wash('int', $_GET['xid']) : 0; if (!$x['xid']) exit;

$x['tblid'] = (isset($_GET['tblid'])) ? wash('int', $_GET['tblid']) : 0; if (!$x['tblid']) exit;

$x['limit'] = (isset($_GET['limit'])) ? wash('int', $_GET['limit']) : 0; if (!$x['limit']) $x['limit'] = 10;



echo logzer_output('tbl', $x);

