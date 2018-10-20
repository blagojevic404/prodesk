<?php

pms('dsk/tmz', (($id) ? 'mdf' : 'new'), $x, true);



$tbl[1] = 'prgm_teams';

$mdf[1]['Caption']	= wash('cpt', @$_POST['Caption']);
$mdf[1]['GroupID'] = wash('int', @$_POST['GroupID']);
$mdf[1]['PMS_loose'] = wash('bln', @$_POST['PMS_loose']);


if (!@$x['ID']) { // new = insert

    $mdf[1]['ChannelID'] = $x['ChannelID'];

    $x['ID'] = receiver_ins($tbl[1], $mdf[1]);

} else { // modify = update

	receiver_upd($tbl[1], $mdf[1], $x);
}




crw_receiver($x['ID'], $x['TBLID'], @$x['CRW'], ['tbl_name' => $x['TBL'], 'x_id' => $x['ID']]);




hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?id='.$x['ID']);
