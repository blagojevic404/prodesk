<?php
/**
 * Set atom IsReady value to 1. The script is called via ajax.
 */

require '../../../__ssn/ssn_boot.php';


$id = (isset($_POST['id'])) ? wash('int', $_POST['id']) : 0;

$stry_id = (isset($_POST['stry_id'])) ? wash('int', $_POST['stry_id']) : 0;

if (!$id) exit;




pms('dsk/stry', 'atom_isready', ['story_id' => $stry_id], true);




$log = ['tbl_name' => 'stryz', 'x_id' => $stry_id, 'act_id' => 61, 'act' => 'mos'];

qry('UPDATE cn_mos SET IsReady=1 WHERE NativeType=20 AND NativeID='.$id, $log);


echo '1';