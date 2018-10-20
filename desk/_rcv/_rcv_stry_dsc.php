<?php

pms('dsk/stry', (($x['ID']) ? 'mdf' : 'new'), $x, true); // pms for new and mdf differ (mdf requires some data)


$tbl[1] = $x['TBL'];


$mdf[1]['Caption'] = wash('cpt', @$_POST['Caption']);

if (!defined('STRYNEW_2IN1')) {

    $mdf[1]['DurForc'] = rcv_datetime('hms_nozeroz',
        ['hh' => '', 'mm' => @$_POST['DurForcMM'], 'ss' => @$_POST['DurForcSS']]);

} else {

    $mdf[1]['DurForc'] = null;
}


if (!@$x['ID']) { // new = insert

    $mdf[1]['UID'] = UZID;
    $mdf[1]['TermAdd'] = TIMENOW;
    $mdf[1]['ChannelID'] = $x['ChannelID'];
    $mdf[1]['Phase'] = (defined('STRYNEW_FROM_TASK')) ? 0 : 1;

    // When adding new story via shortcut from epg: add the ScnrID and ProgID columns
    if (!empty($_GET['scnid'])) {
        $mdf[1]['ScnrID'] = intval($_GET['scnid']);
        $mdf[1]['ProgID'] = scnr_get_progid($mdf[1]['ScnrID']);
    }

    $x['ID'] = receiver_ins($tbl[1], $mdf[1]);

    flw_put($x['ID'], $x['EPG_SCT_ID'], 3); // FLW for the AUTHOR

    define('PAGE_TYP', 'NEW'); // we need this later, to decide where to redirect

} else { // modify = update

    receiver_upd($tbl[1], $mdf[1], $x);

    define('PAGE_TYP', 'MDF');
}



// When adding new story via shortcut from epg: add the fragment to scnr
if (!empty($_GET['scnid']) && empty($_GET['pending'])) {
    epg_conn_receiver($x['EPG_SCT_ID'], $x['ID'], intval($_GET['scnid']));
}


if (defined('STRYNEW_2IN1')) {
    return;
}


$log = ['tbl_name' => $x['TBL'], 'x_id' => $x['ID']];

if (defined('STRYNEW_FROM_TASK')) {

    $task_uid = wash('int', @$_POST['UID']);

    $post = [0 => ['CrewType' => 5, 'CrewUID' => $task_uid]];

    crw_receiver($x['ID'], 2, null, LOGSKIP, $post); // Add the ASIGNEE to CRW (5=journo)

} else {

    crw_receiver($x['ID'], $x['EPG_SCT_ID'], @$x['CRW'], $log);
}


if ($cfg[SCTN]['dsk_use_notes']) {
    note_receiver($x['ID'], $x['EPG_SCT_ID'], @$x['Note'], $log);
}


// EMIT update

if ($mdf[1]['DurForc']!=@$x['DurForc']) { // If duration differs from previous
    dsk_termemit($x);
}


if (defined('STRYNEW_FROM_TASK')) {
    return;
}


if (PAGE_TYP=='NEW') { // For NEW page, we don't redirect to details, we first go to mdf_atoms..

    $href = $pathz['www_root'].dirname($_SERVER['PHP_SELF']).'/stry_modify.php?typ=mdf_atom&id='.$x['ID'];

} else { // MDF

    $href = $pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?id='.$x['ID'];
}

if (!empty($_GET['scnid'])) {
    $href .= '&scnid='.intval($_GET['scnid']);
}

hop($href);



