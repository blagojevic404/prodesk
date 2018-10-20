<?php
/**
 * Add or remove an item to FOLLOWZ table. The script is called via ajax.
 */

require '../../../__ssn/ssn_boot.php';

$sw = (isset($_POST['sw'])) ? wash('int', $_POST['sw']) : 0;

$item_id = (isset($_POST['item_id'])) ? wash('int', $_POST['item_id']) : 0;

$item_typ = (isset($_POST['item_typ'])) ? wash('int', $_POST['item_typ']) : 0;

if (!$item_id || !$item_typ) exit;


$flw_id = flw_checker($item_id, $item_typ);



if (!$flw_id && $sw) {

    flw_put($item_id, $item_typ, 1);

} elseif ($flw_id) {

    qry('DELETE FROM stry_followz WHERE ID='.$flw_id);
}



echo '1';