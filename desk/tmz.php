<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

if ($id || isset($_POST['Submit_TMZ'])) {

    $x = team_reader($id);

} else {

    $x['TYP'] = 'tmz';
    $x['ChannelID'] = CHNL;
}

if (isset($_POST['Submit_TMZ'])) {
    require '_rcv/_rcv_tmz.php';
}


/*************************** HEADER ****************************/

$header_cfg['subscn'] = 'tmz';
$header_cfg['bsgrid_typ'] = (!$id) ? 'full-w' : 'regular';

if (!$id) {

    $header_cfg['chnl_cbo'] = true;

} else {

    $footer_cfg['modal'][] = ['deleter'];

    $header_cfg['logz'] = true;
    $header_cfg['css'][] = 'details.css';
}


require '../../__inc/_1header.php';
/***************************************************************/





// CRUMBS
crumbs_output('open');
crumbs_output('item', '<span class="label label-primary channel">'.channelz(['id' => $x['ChannelID']], true).'</span> ');
crumbs_output('item', $tx['NAV'][2]);
crumbs_output('item', $nav_subz[$header_cfg['subscn']]['ttl'], (($id) ? '?' : ''));

if (!$id) { // BTN: NEW TEAM
    btn_output('new', ['itemtyp' => $tx['LBL']['team'], 'css' => 'btn-xs']);
}

crumbs_output('close');







if ($id) { // Specified team


    /* HEADER BAR */

    $opt = [
        'title' => $x['Caption'],
        'toolbar' => true
    ];
    headbar_output('open', $opt);

    btn_output('mdf');

    $deleter_argz = btn_output('del', ['itemtyp'=>$tx['LBL']['team']]);

    headbar_output('close', $opt);


    /* PROGRAMS FIELD */

    form_accordion_output('head', $tx['NAV'][22], 'listing');

    $progs_arr = prg_array('from_team', $x['ID']);

    foreach ($progs_arr as $k =>$v) {
        echo '<p><a href="prgm_details.php?id='.$k.'">'.$v.'</a></p>';
    }

    form_accordion_output('foot');


    /* CRW FIELD */
    $tmp_collapse =  ($x['CRW']) ? true : false;
    crw_output('dtl', $x['CRW'], [1],
        ['collapse'=>$tmp_collapse, 'caption'=>$tx['LBL']['crew'].' ('.$tx[SCTN]['LBL']['pms_for'].')']);


    /* GROUP FIELD */

    $grp_title = ($x['GroupID']) ? rdr_cell('hrm_groups', 'Title', $x['GroupID']) : '';

    form_panel_output('head-dtlform');

    detail_output(['lbl' => $tx['LBL']['group'], 'txt' => $grp_title,
        'tag' => 'a', 'attr' => 'href="/hrm/org_details.php?id='.$x['GroupID'].'"']);

    form_panel_output('foot');


    logzer_output('box', $x, ['righty_skip' => true]);


} else { // Teams LIST (no team is selected)


    echo '<div class="row"><div class="col-lg-8 col-md-10 col-sm-12">';

    $tmz_arr = rdr_cln('prgm_teams', 'Caption', 'ChannelID='.$x['ChannelID'], 'Queue, ID ASC');

    echo '<div class="list-group" style="margin-top:10px">';
    foreach ($tmz_arr as $k => $v) {
        echo '<a href="?id='.$k.'" class="list-group-item">'.
            '<span class="badge">'.cnt_sql('prgm', 'TeamID='.$k.' AND IsActive=1').'</span>'.
            '<h4>'.$v.'</h4></a>';
    }
    echo '</div>';

    echo '</div></div>';
}








/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
