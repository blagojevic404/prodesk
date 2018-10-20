<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$x = stry_reader($id, 'details');


/** Channel type: 1=tv, 2=radio */
define('CHNLTYP', rdr_cell('channels', 'TypeX', $x['ChannelID']));

// Story atoms jargon
define('ATOM_JARGON', setz_get('atom_jargon_'.CHNLTYP));

// We need this later when adding flw-mark for the task-asignee. We need *phase* in order to know whether it is a stry-task.
define('STRY_PHASE', ((!empty($x['Phase'])) ? $x['Phase'] : 0));


if (isset($_POST['Submit_STRY_DSC'])) { // mdf_dsc

    require '_rcv/_rcv_stry_dsc.php';

} elseif (isset($_POST['Submit_STRY_TASK'])) { // mdf_task

    define('STRYNEW_FROM_TASK', true);

    require '_rcv/_rcv_stry_dsc.php';

    require '_rcv/_rcv_stry_atom.php';

} elseif (isset($_POST['Submit_STRY_ATOM']) || isset($_POST['Submit_STRY_ATOM+PHZ'])) { // mdf_atom

    if (!$x['ID']) { // NEW

        define('STRYNEW_2IN1', true);

        require '_rcv/_rcv_stry_dsc.php';

        $x = stry_reader($x['ID'], 'details');
    }

    require '_rcv/_rcv_stry_atom.php';
}


if (!$x['ID']) {
    require '../../__inc/inc_404.php';
}



stry_security($x, 'stry');



// For stories which are moved to TRASH, MDF and DEL pms should be off.

define('PMS', (($x['IsDeleted']) ? false : pms('dsk/stry', 'mdf', $x)));
define('PMS_CVR_NEW', (($x['IsDeleted']) ? false : pms('dsk/cvr', 'new', ['OwnerID' => $x['ID'], 'OwnerType' => 2])));
define('PMS_ATOM_READY', (($x['IsDeleted']) ? false : pms('dsk/stry', 'atom_isready', ['story_id' => $x['ID']])));


if ($x['Phase']) {

    // Phases arrays

    $arr_nwzphzs['pms'] = pms('dsk/stry', 'phase', $x);
    $arr_nwzphzs['txt']	= txarr('arrays', 'dsk_nwz_phases');
    $arr_nwzphzs['clr'] = cfg_global('arrz','dsk_phase_clr');


    // Phases switching

    if (isset($_GET['phase']) &&
        $_GET['phase']!=$x['Phase'] &&
        in_array($_GET['phase'], array_flip($arr_nwzphzs['txt']))) {

        stry_phazer($x, $_GET['phase']);

        // Show warning if someone is currently working on the story (modifying)
        require $pathz['rel_rootpath'].'../__fn/fn_mdflog.php';
        mdflog_conflict(250, $x['ID']);

        hop(((isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : $_SERVER['REQUEST_URI'])); // Reload
    }
}




/*
 * Whether there should be an editing shortcut on the DUR label for the RECS atoms
 */
define('DUR_EDITABLE', (PMS || pms('dsk/stry', 'mdf_recs_dur', $x)));

if (DUR_EDITABLE) { // This code block is receiver for DUR_EDITABLE actions

    if (isset($_GET['mdfid']) && isset($_GET['mdfhms'])) {

        $mdfid = wash('int', $_GET['mdfid']);

        $arr_hms = rcv_hms($_GET['mdfhms']);
        $mdfdur = rcv_datetime('hms_nozeroz', $arr_hms);

        // Update duration
        $log = ['tbl_name' => 'stryz', 'act_id' => 61, 'act' => 'recs'];
        qry('UPDATE stry_atoms SET Duration=\''.$mdfdur.'\' WHERE ID='.$mdfid, $log);

        // EMIT update
        stry_termemit($x['ID']);

        hop($pathz['www_root'].dirname($_SERVER['PHP_SELF']).'/stry_details.php?id='.$x['ID']);
    }
}


define('VERSIONS', (empty($_GET['versions']) ? 0 : 1));




/*************************** CONFIG ****************************/

$header_cfg['subscn'] = (($x['IsDeleted']) ? 'trash' : 'stry');
$header_cfg['bsgrid_typ'] = 'regular';
$header_cfg['logz'] = true;

$footer_cfg['modal'][] = ['deleter'];
$footer_cfg['modal'][] = ['deleter', ['onshow_js' => 'modal_del_onshow']];
$footer_cfg['modal'][] = ['printer', ['ctrl_cam_only' => true]];


/*CSS*/
$header_cfg['css'][] = 'cover.css';
$header_cfg['css'][] = 'details.css';

$header_cfg['css'][] = 'print.css'; // The order of CSS files is important!
if ($_SESSION['BROWSER_TYPE']=='PRINT_1') {
    $header_cfg['css'][] = 'print_old_tweak.css';
}

/*JS*/
$header_cfg['js'][] = 'ifrm.js';
$header_cfg['js'][] = 'dsk/common.js';
$header_cfg['js'][] = 'ajax/editdivable.js';
$header_cfg['js'][] = 'alphconv.js';

if (DUR_EDITABLE) {
    $header_cfg['css'][] = 'hmsedit.css';
    $header_cfg['js'][]  = 'hms_editable.js';
}

// Tooltip on atom duration label - shows text length and speaker speed
$footer_cfg['js_lines'][]  = '$(\'[data-toggle="tooltip"]\').tooltip();';


$cnt_atomz = count($x['ATOMZ']);

// Handling CVR accordions - CVRz are displayed in textboxes, and they need to be height-fixed on load,
// but these are hidden on load, therefore we must fix them on accordion collapse.
for ($i=0; $i<=$cnt_atomz; $i++) {
    $name = ($i==0) ? '_stry' : $i;
    $footer_cfg['js_lines'][]  = "$('#cvr".$name."_collapse').on('shown.bs.collapse', textarea_height);";
}

if (setz_get('dsk_cvr_collapse')) {
    $header_cfg['js_onload'][]  = 'cvr_collapse(0, '.$cnt_atomz.')';
}



require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', '<span class="label label-primary channel">'.channelz(['id' => $x['ChannelID']], true).'</span>');
crumbs_output('item', $tx['NAV'][2]);
crumbs_output('item', $tx['NAV'][27], 'list_stry.php');
crumbs_output('item', sprintf('%04s',$x['ID']));

crumbs_output('item', flw_output(($x['FlwID']), $x['ID'], $x['EPG_SCT_ID']), '', 'pull-right righty');

if ($x['ScnrID']) {

    $element_id = scnr_id_to_elmid($x['ScnrID']);

    if ($element_id) {

        $scnr = scnr_cpt_get($element_id, 'arr');

        crumbs_output('item', '<span class="glyphicon glyphicon-list"></span> '.$scnr['Caption'],
            '/epg/epg.php?typ=scnr&id='.$element_id, 'pull-right righty conn');
    } else {

        $scnr = [];
        log2file('srpriz', ['type' => 'scnr_element_missing', 'stry' => $x['ID']]);
    }
}

if ($x['IsDeleted']) {
    crumbs_output('item', '<span class="label is_deleted text-uppercase">'.txarr('arrays','log_actions',4).'</span>',
        'list_trash.php', 'is_deleted');
}

crumbs_output('close');

if (!empty($scnr)) {
    echo '<div class="row">'.
    '<div class="visible-print-block prgm_cpt"><span>'.$scnr['Caption'].'</span> ('.$scnr['TermEmit'].')'.'</div>'.
    '</div>';
}


// BUTTONS BAR

echo '<div class="row"><div class="btnbar '.$bs_css['panel_w'].'"><div class="btn-toolbar">';


if ($x['Phase']) {

    foreach ($arr_nwzphzs['txt'] as $k => $v) {

        pms_btn( // BTN: PHASE
            (($x['IsDeleted']) ? false : $arr_nwzphzs['pms'][$k]), $v,
            [   'href' => '?id='.$x['ID'].'&phase='.$k,
                'style' => 'background-color:#'.$arr_nwzphzs['clr'][$k],
                'class' => 'stry_phases text-uppercase btn '.(($k==$x['Phase']) ? 'btn-sm sel' : 'btn-xs')    ]
        );
    }

} else { // TASK

    $uid_assignee = crw_reader($x['ID'], 2, 5, 'normal_single');

    if ($uid_assignee) {

        echo '<span class="glyphicon glyphicon-pencil"></span>'.
            '<span class="assignee">'.uid2name($uid_assignee).'</span>';
    }
}

echo '<span class="pull-right btn-toolbar">';


if ($x['Phase']) {

    modal_output('button', 'printer');

    if (CHNLTYP==1) {
        pms_btn( // BTN: COLLAPSE CVRZ
            true, '<span class="glyphicon glyphicon-resize-small"></span>&nbsp;'.$tx['LBL']['cvr'],
            [   'href' => '#',
                'onclick' => 'cvr_collapse(this, '.count($x['ATOMZ']).'); return false;',
                'class' => 'btn btn-default btn-sm text-uppercase cvr_collapser js_starter'    ]
        );
    }
}


pms_btn( // BTN: MODIFY-DSC
    PMS, '<span class="glyphicon glyphicon-cog mdf"></span>'.$tx['LBL']['title'],
    [   'href' => 'stry_modify.php?typ=mdf_dsc&id='.$x['ID'],
        'class' => 'btn btn-info btn-sm text-uppercase'    ]
);

pms_btn( // BTN: MODIFY-ATOMZ
    PMS, '<span class="glyphicon glyphicon-cog mdf"></span>'.$tx['LBL']['content'],
    [   'href' => 'stry_modify.php?typ=mdf_atom&id='.$x['ID'],
        'class' => 'btn btn-info btn-sm text-uppercase'    ]
);


pms_btn( // BTN: MODIFY-COPY
    pms('dsk/stry', 'new'), '<span class="glyphicon glyphicon-duplicate"></span>',
    [   'href' => 'stry_mover.php?id='.$x['ID'].'&act=copy',
        'class' => 'btn btn-info btn-sm text-uppercase'    ]
);

pms_btn( // BTN: MODIFY-CUT
    PMS, '<span class="glyphicon glyphicon-scissors"></span>',
    [   'href' => 'stry_mover.php?id='.$x['ID'].'&act=cut',
        'class' => 'btn btn-info btn-sm text-uppercase'    ]
);


if (!$x['IsDeleted']) { // delete

    $deleter_argz = [
        'pms' => pms('dsk/stry', 'del', $x),
        'button_txt' => '<span class="glyphicon glyphicon-trash"></span>',
        'txt_body_itemtyp' => $tx['LBL']['stry'],
        'txt_body_itemcpt' => $x['Caption'],
        'txt_body_note' => $tx[SCTN]['MSG']['del_note_stry'],
        'submiter_href' => 'delete.php?typ=stry_delete&id='.$x['ID']
    ];

} else { // purge

    $deleter_argz = [
        'pms' => pms('dsk/stry', 'purge_restore', $x),
        'txt_body_itemtyp' => $tx['LBL']['stry'],
        'txt_body_itemcpt' => $x['Caption'],
        'submiter_href' => 'delete.php?typ=stry_purge&id='.$x['ID']
    ];
}

modal_output('button', 'deleter', $deleter_argz);

echo '</span>';

echo '</div></div></div>';



// HEADER BAR

$opt = ['title' => $x['Caption'], 'toolbar' => true, 'toolbar_doprint' => true];
headbar_output('open', $opt);

$dur = dur_handler($x['DurForc'], $x['DurCalc']);
if ($dur['case']<3) {
    $vlu = '<span class="'.$dur['css'].'">'.date('i:s', $dur['time']).'</span>';
} else {
    $vlu = '<span class="'.$dur['css'].'">'.$x['DurCalcTXT']['mmss'].'</span> '.
        '<span class="dur_sec">/'.$x['DurForcTXT']['mmss'].'/</span>';
}

echo '<span class="label label-default durcalc pull-right">'.
    '<span class="opcty3 glyphicon glyphicon-time"></span> '.$vlu.'</span>';

headbar_output('close', $opt);



/* ATOMS */

atom_output('dtl', $x['ATOMZ']);




echo '<div class="hidden-print">';


// STORY CVR

if (CHNLTYP==1) {
    echo '<div name="wraper">';     // Put WRAPER div around, for ifrm JS
    coverz_accordion_output($x['ID'], 2, 'cvr_stry');
    echo '</div>';
}



/* CRW FIELD */

crw_output('dtl', $x['CRW'], [5,6,7], ['collapse'=>false]);



/* NOTE FIELD */

if ($cfg[SCTN]['dsk_use_notes'] && $x['Note']) {

    form_accordion_output('head', $tx['LBL']['note'], 'note', ['type'=>'dtlform']);

    detail_output(['lbl' => $tx['LBL']['note'], 'txt' => @$x['Note']]);

    form_accordion_output('foot');
}



/* COPIES TREE */

if (rdr_cell('stry_copies', 'ID', 'OriginalID='.$x['ID']) || rdr_cell('stry_copies', 'ID', 'CopyID='.$x['ID'])) {

    form_accordion_output('head', $tx[SCTN]['LBL']['copies'], 'copies', ['collapse'=>false]);

    stry_copies($x['ID']);

    form_accordion_output('foot');
}



logzer_output('box', $x, ['versions' => (!VERSIONS)]);



/* VERSIONS */

if (VERSIONS) {

    echo '<div class="row"><div class="well '.$bs_css['panel_w'].'" id="versions"><a name="versions"></a>';

    stry_versions_get($x['ID']);

    echo '</div></div>';
}



echo '</div>'; // close *hidden-print* div




/* IFRAME ROW CLONE - to be *inserted* into table via JS, ifrm_starter() */
ctrl_ifrm('multi_div');


/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';



