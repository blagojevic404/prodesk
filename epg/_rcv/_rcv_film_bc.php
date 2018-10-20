<?php

pms('epg/film', 'bcast_mdf', null, true);

$tbl = 'epg_bcasts';



if (isset($_GET['bc_del'])) {
    qry('DELETE FROM '.$tbl.' WHERE ID='.wash('int', $_GET['bc_del']));
}


if (isset($_GET['bc_add'])) {

    $mdf['TermStart']	= wash('ymd', @$_POST['TermStart']);
    $mdf['Phase']		= wash('int', @$_POST['Phase']);
    $mdf['ChannelID']	= wash('int', @$_POST['ChannelID']);

    $mdf['SchType']		= 1;
    $mdf['SchLineID']	= 0;
    $mdf['NativeType']	= $x['EPG_SCT_ID'];
    $mdf['NativeID']	= $x['ID'];

    if ($mdf['TermStart']) {

        receiver_ins($tbl, $mdf);

    } else {

        omg_put('danger', $tx['MSG']['err_data_input']);
    }
}



hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?typ='.$x['TYP'].'&id='.$x['ID']);



