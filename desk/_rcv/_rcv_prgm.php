<?php

prgm_pms($x);


$tbl[1] = 'prgm';
$tbl[2] = 'prgm_settings';


if (PMS_MDF) {

    $mdf[1]['Caption'] = wash('cpt', @$_POST['Caption']);
    $mdf[1]['TeamID'] = wash('int', @$_POST['TeamID']);
    $mdf[1]['IsActive'] = wash('bln', @$_POST['IsActive']);
    $mdf[1]['ProdType'] = wash('int', @$_POST['ProdType']);

    $mdf[2]['EPG_Rerun'] = wash('bln', @$_POST['EPG_Rerun']);
    $mdf[2]['EPG_Skip_Dflt_Tmpl_Auto_Import'] = wash('bln', @$_POST['EPG_Skip_Dflt_Tmpl_Auto_Import']);
    $mdf[2]['SecurityStrict'] = wash('bln', @$_POST['SecurityStrict']);

    $mdf[2]['DurDesc'] = wash('cpt', @$_POST['DurDesc']);
    $mdf[2]['TermDesc'] = wash('cpt', @$_POST['TermDesc']);
    $mdf[2]['Note'] = wash('cpt', @$_POST['Note']);

    $mdf[2]['WebLIVE'] = wash('bln', @$_POST['WebLIVE']);
    $mdf[2]['WebVOD'] = wash('bln', @$_POST['WebVOD']);
    $mdf[2]['WebHide'] = wash('bln', @$_POST['WebHide']);
    $mdf[2]['DscTitle'] = wash('cpt', @$_POST['DscTitle']);


    if (!@$x['ID']) { // new = insert

        $prgm_exists = rdr_id($tbl[1], 'Caption=\''.$mdf[1]['Caption'].'\' '.
            'AND TeamID IN (SELECT ID FROM prgm_teams WHERE ChannelID='.$x['TEAM']['ChannelID'].')');

        if ($prgm_exists) {

            omg_put('danger', $tx[SCTN]['MSG']['err_prgm_exists']);

            hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?id='.$prgm_exists);
        }

        $x['ID'] = receiver_ins($tbl[1], $mdf[1]);

    } else { // modify = update

        receiver_upd($tbl[1], $mdf[1], $x);
    }
}



if (PMS_MDF_WEB) {

    $mdf[2]['WebLIVE'] = wash('bln', @$_POST['WebLIVE']);
    $mdf[2]['WebVOD'] = wash('bln', @$_POST['WebVOD']);
    $mdf[2]['WebHide'] = wash('bln', @$_POST['WebHide']);
    $mdf[2]['DscTitle'] = wash('cpt', @$_POST['DscTitle']);
}



if (PMS_MDF || PMS_MDF_WEB) {

    receiver_mdf($tbl[2], ['ID' => $x['ID']], $mdf[2], ['tbl_name' => $tbl[1], 'x_id' => $x['ID']]);
}



if (PMS_MDF_CRW) {

    crw_receiver($x['ID'], $x['TBLID'], @$x['CRW'], ['tbl_name' => $x['TBL'], 'x_id' => $x['ID']]);
}



hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?id='.$x['ID']);
