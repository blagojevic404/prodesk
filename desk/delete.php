<?php
require '../../__ssn/ssn_boot.php';

require '../../__fn/fn_del.php';


$id  = (isset($_GET['id'])) ? intval($_GET['id']) : 0;
$typ = (isset($_GET['typ'])) ? wash('cpt', $_GET['typ']) : '';
// Types: cvr, stry_delete, stry_purge, stry_restore, tmz, prgm

if (!$id || !$typ) redirector('args');



$typ_arr = explode('_', $typ);

if (!isset($typ_arr[1])) {
    $typ_arr[1] = 'delete';
}


$typ = $typ_arr[0];

$opt['act'] = $typ_arr[1];



deleter($typ, $id, $opt);






