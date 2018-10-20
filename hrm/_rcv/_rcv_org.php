<?php

pms('hrm/org', 'mdf', null, true);



$tbl[1] = 'hrm_groups';

$mdf[1]['Title']	= wash('cpt', @$_POST['Title']);
$mdf[1]['ChiefID']  = wash('int', @$_POST['ChiefID']);
$mdf[1]['ParentID'] = wash('int', @$_POST['ParentID']);


if (!@$x['ID']) { // new = insert

    $x['ID'] = receiver_ins($tbl[1], $mdf[1]);

} else { // modify = update

	receiver_upd($tbl[1], $mdf[1], $x);
}


hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?id='.$x['ID']);
