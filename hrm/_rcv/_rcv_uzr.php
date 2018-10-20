<?php

pms('hrm/uzr', 'mdf', null, true);


$is_new = (@$x['ID']) ? false : true;


$tbl[1] = 'hrm_users';
$tbl[2] = 'hrm_users_data';


$mdf[1]['Name1st'] = wash('cpt', @$_POST['Name1st']);
$mdf[1]['Name2nd'] = wash('cpt', @$_POST['Name2nd']);
$mdf[1]['GroupID'] = wash('int', @$_POST['GroupID']);
$mdf[1]['IsActive'] = wash('bln', @$_POST['IsActive']);

if ($is_new) {
    $mdf[1]['ADuser'] = create_username($mdf[1]['Name1st'], $mdf[1]['Name2nd']);
}


$mdf[2]['Title'] = wash('cpt', @$_POST['Title']);

if ($cfg[SCTN]['ctrl_contract'])    $mdf[2]['ContractType'] = wash('int', @$_POST['ContractType']);
if ($cfg[SCTN]['ctrl_fathername'])  $mdf[2]['FatherName']   = wash('cpt', @$_POST['FatherName']);
if ($cfg[SCTN]['ctrl_gender'])      $mdf[2]['Gender']       = wash('int', @$_POST['Gender']);


if ($is_new) { // new = insert

    $x['ID'] = receiver_ins($tbl[1], $mdf[1]);

} else { // modify = update

	receiver_upd($tbl[1], $mdf[1], $x);
}


receiver_mdf($tbl[2], ['ID' => $x['ID']], $mdf[2], ['tbl_name' => $tbl[1], 'x_id' => $x['ID']]);



if (!$is_new) { // MDF

    // When the group or active state changes, we check whether this user is the chief in any group and break that.

    if ($mdf[1]['IsActive']!=$x['IsActive'] || $mdf[1]['GroupID']!=$x['GroupID']) {

        qry('UPDATE hrm_groups SET ChiefID=0 WHERE ChiefID='.$x['ID']);
    }
}



hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?id='.$x['ID']);
