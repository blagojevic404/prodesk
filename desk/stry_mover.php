<?php
require '../../__ssn/ssn_boot.php';


$act = (isset($_GET['act'])) ? wash('arr_assoc', $_GET['act'], ['cut', 'copy']) : null;

$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0; // stryid



$x = stry_reader($id, 'details');

if (!$x['ID']) {
    require '../../__inc/inc_404.php';
}



if (isset($_GET['scnrid'])) {

    $scnrid_new = wash('int', $_GET['scnrid']);


    if ($act=='cut') {

        pms('dsk/stry', 'mdf', $x, true);

        receiver_upd_short('stryz',['ScnrID' => $scnrid_new, 'ProgID' => scnr_get_progid($scnrid_new)],
            $x['ID'], ['act_id' => 7]);

        // Delete from previous scnr, and update it via duremit/termemit
        qry('DELETE FROM epg_scnr_fragments WHERE NativeType=2 AND NativeID='.$x['ID']);
        if ($x['ScnrID']) {
            $scnr = scnr_reader($x['ScnrID']);
            scnr_duremit($scnr);
        }

    } else { // copy

        $ccid = stry_copy($x['ID'], $scnrid_new);
    }


    // If editor, then copy/move straight to scnr, not only to pending strys
    $editor = pms_scnr($scnrid_new, 1);
    if ($editor) {
        epg_conn_receiver(2, (($act=='cut') ? $x['ID'] : $ccid), $scnrid_new);
        $scnr = scnr_reader($scnrid_new);
        scnr_duremit($scnr);
    }


    omg_put('success', $tx[SCTN]['MSG']['stry_'.$act].' ('.$x['Caption'].')');

    hop($pathz['www_root'].'/epg/epg.php?typ=scnr&id='.scnr_id_to_elmid($scnrid_new));
}




$from_chnl = (isset($_POST['from_chnl'])) ? wash('int', $_POST['from_chnl']) : $x['ChannelID'];


if ($x['ScnrID']) {

    $scnr_epgid = rdr_cell('epg_elements', 'EpgID', 'NativeType=1 AND NativeID='.$x['ScnrID']);

    $scnr = rdr_row('epgz', 'DateAir, ChannelID', $scnr_epgid);

    //If we are not at the same channel as the story belongs to, then we won't show any epg at all at the beginning.
    if ($scnr['ChannelID']!=$from_chnl) {
        $scnr_epgid = 0;
    }

} else { // Story is not associated to any scnr

    $scnr['DateAir'] = date('Y-m-01');

    $scnr_epgid = 0;
}





/*************************** CONFIG ****************************/

$header_cfg['subscn'] = (($x['IsDeleted']) ? 'trash' : 'stry');
$header_cfg['bsgrid_typ'] = 'full-w';
$header_cfg['mover'] = true;

require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', '<span class="label label-primary channel">'.channelz(['id' => $x['ChannelID']], true).'</span>');
crumbs_output('item', $tx['NAV'][2]);
crumbs_output('item', $tx['NAV'][27]);
crumbs_output('item', $x['Caption']);
crumbs_output('close');



receiver_post('opt', 'channel', 1);

echo '<form method="post" name="former" role="form"><div class="form-group">'.
    '<select style="width:auto" class="form-control" name="from_chnl" onChange="submit()">'.
    arr2mnu(channelz(['typ' => [1,2]]), $from_chnl).
    '</select>'.
    '</div></form>';



echo '<div class="row">';


// MOVER: CALENDAR

echo '<div class="col-xs-6" id="wrap_movercalendar">';

$opt = [
    'month' => date('Y-m-01', strtotime($scnr['DateAir'])),
    'chnlid' => $from_chnl,
    'scnr_epgid' => $scnr_epgid,
    'href_query' => '&objid='.$x['ID'].'&act='.$act,
];

mover_calendar('framer', $opt);

echo '</div>';


// MOVER: EPG

echo '<div class="col-xs-6" id="wrap_moverepg">';

if ($scnr_epgid) {
    mover_epg($scnr_epgid, 'cutcopy', '&id='.$x['ID'].'&act='.$act);
}

echo '</div>';


echo '</div>';


/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';



