<?php
/**
 * The three frames are (the third one applies only to *import* case):
 * - CALENDAR: Displays EPG calendar.
 * - EPG: Displays SCENARIOS listing for the selected epg/date.
 * - SCNR: Displays FRAGMENTS checklist for the selected scenario. User then picks fragments to import.
 */

require '../../__ssn/ssn_boot.php';


$act = (isset($_GET['act'])) ? wash('arr_assoc', $_GET['act'], ['import', 'linker']) : null;
// linker: epg prog linking
// import: scnr stry import (importing stories from one scnr to another)



// RCV for scnr import: copy selected stories to target scnr as *pending* stories
if ($act=='import' && isset($_POST['fragz'])) {

    $scnrid = (isset($_GET['scnrid'])) 	? wash('int', $_GET['scnrid']) : 0;

    if ($scnrid && !empty($_POST['fragz'])) {

        $arr_actv_fragz = array_keys($_POST['fragz']);

        foreach ($arr_actv_fragz as $v) {

            $stryid = rdr_cell('epg_scnr_fragments', 'NativeID', $v);

            stry_copy($stryid, $scnrid);
        }

        omg_put('success', $tx['LBL']['import']);
    }

    hop($pathz['www_root'].'/epg/epg.php?typ=scnr&id='.scnr_id_to_elmid($scnrid));
}



if ($act=='import') {

    $scnr_elmid = (isset($_GET['scnelmid'])) ? wash('int' ,$_GET['scnelmid']) : 0;

    $epgid = rdr_cell('epg_elements', 'EpgID', $scnr_elmid);

} else { // linker

    $epgid = (isset($_GET['epgid']) ? wash('int' ,$_GET['epgid']) : 0);
}

$epg = rdr_row('epgz', 'DateAir, ChannelID', $epgid);

$from_chnl = (isset($_POST['from_chnl'])) ? wash('int', $_POST['from_chnl']) : $epg['ChannelID'];

//If we are not at the same channel as the epg belongs to, then we won't show any epg at all at the beginning.
if ($epg['ChannelID']!=$from_chnl) {
    $epgid = 0;
}



/*************************** HEADER ****************************/

$real_epgid = get_real_epgid();

$header_cfg['subscn'] = ($real_epgid==$epgid) ? 'real' : 'plan'; // Actually important only for *linker*
$header_cfg['mover'] = true;

if ($act=='import') {

    $header_cfg['bsgrid_typ'] = 'full-w';

} else { // linker

    $header_cfg['ifrm'] = true; // in *linker* case, the script is called from within an iframe
    $header_cfg['js'][]  = 'ifrm.js';
}

require '../../__inc/_1header.php';
/***************************************************************/



if ($act=='import') { // *import* case needs crumbs, *linker* case doesnot as it is opened within iframe

    $datecpt = epg_cptdate($epg['DateAir'], 'date_wday');

    $x['PRG'] = qry_assoc_row('SELECT ProgID, Caption FROM epg_scnr WHERE ID='.scnrid_prog($scnr_elmid));

    if ($x['PRG']['ProgID']) { // normal behaviour (some program WAS selected in the prog cbo)

        $x['PRG']['ProgCPT'] = prg_caption($x['PRG']['ProgID']);

    } else { // EMPTY prog, i.e. none of the programs from the prog cbo was selected

        list($x['PRG']['ProgCPT'], $x['PRG']['Caption']) = prgcpt_explode($x['PRG']['Caption']);
    }

    // CRUMBS
    crumbs_output('open');
    crumbs_output('item', '<span class="label label-primary channel">'.channelz(['id' => $epg['ChannelID']], true).'</span>');
    crumbs_output('item', $tx['NAV'][7]);
    crumbs_output('item', $datecpt['date'].' ('.$datecpt['wday'].')');
    crumbs_output('item', $x['PRG']['ProgCPT']);
    crumbs_output('close');
}



receiver_post('opt', 'channel', 1);

echo '<form method="post" name="former" role="form"><div class="form-group">'.
    '<select style="width:auto" class="form-control" name="from_chnl" onChange="submit()">'.
    arr2mnu(channelz(['typ' => [1,2]]), $from_chnl).
    '</select>'.
    '</div></form>';




echo '<div class="row">';



// MOVER: CALENDAR

echo '<div class="col-xs-'.(($act=='import') ? '3' : '6').'" id="wrap_movercalendar">';

$opt = [
    'month' => date('Y-m-01', strtotime($epg['DateAir'])),
    'chnlid' => $from_chnl,
    'scnr_epgid' => $epgid,
    'layout' => (($act=='import') ? 'vertical' : 'horizontal'),
    'href_query' => (($act=='import') ? '&act=import&objid='.$scnr_elmid : '&act=linker'),
];

mover_calendar('framer', $opt);

echo '</div>';



// MOVER: EPG

echo '<div class="col-xs-'.(($act=='import') ? '5' : '6').'" id="wrap_moverepg">';

if ($epgid) {

    $href_query = ($act=='import') ? '&objid='.$scnr_elmid : null;

    mover_epg($epgid, $act, $href_query);
}

echo '</div>';



if ($act=='import') {

    // MOVER: SCNR

    echo '<div class="col-xs-4" id="wrap_moverscnr"><div id="moverscnr"></div></div>';
}


echo '</div>';





/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';



