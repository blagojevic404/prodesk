<?php
require '../../__ssn/ssn_boot.php';


$setz['EPG'] = [
    'name'  => 'EPG',
    'type'  => 'chk_list',
    'lines' => ['epg_list_filter_teams', 'epg_calendar', 'epg_notes_hide', 'epg_show_skop_cvr', 'epg_hide_inactives'],
    'title' => $tx['LBL']['epg'],
];

$setz['SCNR'] = [
    'name'  => 'SCNR',
    'type'  => 'chk_list',
    'lines' => ['scnr_prompter_element_labels', 'scnr_recs_vo', 'scnr_list_cam'],
    'title' => $tx['LBL']['rundown'],
];

$setz['FILM'] = [
    'name'  => 'FILM',
    'type'  => 'chk_list',
    'lines' => ['film_list_show_contracts', 'film_list_show_genre', 'film_list_bc_show_all_channels'],
    'title' => $tx['NAV'][77],
];








// RECEIVERS

if ($_SERVER['REQUEST_METHOD']=='POST') {

    foreach($setz as $k => $v) {
        if (@$v['type']=='chk_list' && isset($_POST['Submit_'.$k])) {
            setz_receiver($setz[$k]);
        }
    }

    hop($pathz['www_root'].$_SERVER['SCRIPT_NAME']);
}



/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'setz';
$header_cfg['bsgrid_typ'] = 'halfer';

require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', $tx['NAV'][7]);
crumbs_output('item', $tx['NAV'][205]);
crumbs_output('close');



echo '<div class="row" style="padding-top:20px;">';



/* BLOCKS */

foreach($setz as $k => $v) {
    if (@$v['type']=='chk_list') {
        setz_block_chk($setz[$k]);
    }
}



echo '</div>';

/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
