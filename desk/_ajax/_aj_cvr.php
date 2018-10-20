<?php
/**
 * Set cvr IsReady value to 1. The script is called via ajax.
 */

require '../../../__ssn/ssn_boot.php';


$id = (isset($_POST['id'])) ? wash('int', $_POST['id']) : 0;

if (!$id) exit;


if (empty($_POST['proofer'])) {

    pms('dsk/cvr', 'kargen', null, true);
    $change = 'IsReady=1';

} else {

    pms('dsk/cvr', 'proofer', null, true);
    $change = 'ProoferUID='.UZID;
}


qry('UPDATE epg_coverz SET '.$change.' WHERE ID='.$id, ['x_id' => $id]);


echo (empty($_POST['proofer'])) ? '1' : '2';
