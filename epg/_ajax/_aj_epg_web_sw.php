<?php
/**
 * Switching ON/OFF WebLIVE and WebVOD of an element
 */


require '../../../__ssn/ssn_boot.php';


pms('epg', 'mdf_web', null, true);


define('TYP', wash('arr_assoc', $_POST['typ'], ['live', 'vod']));

$sw = intval($_POST['switch']);

if (!TYP || !$sw) {
    exit;
}


$cln = (TYP=='live') ? 'WebLIVE' : 'WebVOD';

qry('UPDATE epg_elements SET '.$cln.' = IF('.$cln.'=1,0,1) WHERE ID='.$sw, ['x_id' => $sw]);

echo '1';
