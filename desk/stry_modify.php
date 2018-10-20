<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$typ = (isset($_GET['typ'])) ? $_GET['typ'] : '';
if ($typ && !in_array($typ, ['mdf_dsc', 'mdf_atom', 'mdf_task'])) {
    $typ = 'mdf_dsc';
}

$x = stry_reader($id, $typ);

/** Channel type: 1=tv, 2=radio */
define('CHNLTYP', rdr_cell('channels', 'TypeX', $x['ChannelID']));


// Story atoms jargon
define('ATOM_JARGON', setz_get('atom_jargon_'.CHNLTYP));



pms('dsk/stry', (($x['ID']) ? 'mdf' : 'new'), $x, true);    // pms for new and mdf differ (mdf requires some data)



if ($x['PG_TYP']=='mdf_atom') {

    $tx['MOS']	= txarr('labels','mos');

    $arr_atom_typz = txarr('arrays', 'atom_jargons.'.ATOM_JARGON);
}


if ($x['PG_TYP']=='mdf_dsc') {

    $lbl_crumb = $tx['LBL']['dsc'];
    $typ_submit = 'DSC';

} elseif ($x['PG_TYP']=='mdf_atom') {

    $lbl_crumb = $tx['LBL']['content'];
    $typ_submit = 'ATOM';

} else { // mdf_task

    $lbl_crumb = $tx['LBL']['task'];
    $typ_submit = 'TASK';
}


/*************************** CONFIG ****************************/

$header_cfg['subscn'] = $x['TYP'];
$header_cfg['bsgrid_typ'] = 'regular';

$footer_cfg['modal'][] = 'alphconv';

/*CSS*/
$header_cfg['css'][] = 'modify.css';

/*JS*/

if ($x['PG_TYP']=='mdf_dsc') {

    $header_cfg['js'][]  = 'crew.js';

} elseif ($x['PG_TYP']=='mdf_atom') {

    $header_cfg['js'][] = 'dsk/atoms.js';

    // tablednd
    $header_cfg['js'][]  = 'tablednd/tablednd.js';
    $header_cfg['js'][]  = 'tablednd/tablednd_custom.js';
    $header_cfg['js_onload'][]  = 'tableDnDOnLoad()';
    $header_cfg['js_onsubmit'][]  = 'tablednd_queuer()';

    $header_cfg['js_onsubmit'][]  = 'clone_cleaner(1,3)';

    $footer_cfg['modal'][] = 'alerter';
}

if (in_array($x['PG_TYP'], ['mdf_dsc', 'mdf_task'])) {
    $header_cfg['autotab'][] = '#DurForcMM, #DurForcSS';
}

require '../../__inc/_1header.php';
/***************************************************************/






// CRUMBS

crumbs_output('open');
crumbs_output('item', $tx['NAV'][2]);
crumbs_output('item', $tx['NAV'][27], 'list_stry.php');

if (@$x['ID']) {
    crumbs_output('item', sprintf('%04s',$x['ID']), 'stry_details.php?id='.$x['ID']);
}

crumbs_output('item', $lbl_crumb);

crumbs_output('item', modal_output('button', 'alphconv', null, false), null, 'pull-right righty');

crumbs_output('close');




// FORM start

$href = 'stry_details.php?id='.$x['ID'];

// This is for adding new story from epg.
if (!empty($_GET['scnid'])) {
    $href .= '&scnid='.intval($_GET['scnid']);
}
if (isset($_GET['qu'])) {
    $href .= '&qu='.intval($_GET['qu']);
}
if (!empty($_GET['pending'])) {
    $href .= '&pending=1';
}

form_tag_output('open', $href);


if (in_array($x['PG_TYP'], ['mdf_dsc', 'mdf_task'])) {


    /* MAIN FIELD */

    form_panel_output('head');

    // Caption
    ctrl('form', 'textbox', $tx['LBL']['title'], 'Caption', @$x['Caption'], 'required');

    // DUR-FORC
    $html = '<div class="dur">'.form_hms_output('desk', 'DurForc', @$x['DurForcTXT'], 'dur').'</div>';
    ctrl('form', 'block', $tx['LBL']['duration'].' ('.$tx['LBL']['forced'].')', 'DurForcHH', $html, 'form-inline');

    // DUR-CALC
    if (@$x['ID']) {
        $html = '<div class="dur durcalc">'.form_hms_output('desk-label', 'DurCalc', @$x['DurCalcTXT']).'</div>';
        ctrl('form', 'block', $tx['LBL']['duration'].' ('.$tx['LBL']['calc'].')', 'DurCalcHH', $html, 'form-inline');
    }


    if ($x['PG_TYP']=='mdf_task') {

        // Story type

        $stry_def = $cfg[SCTN]['strytyp_default_'.CHNLTYP];

        $arr = cfg_local('arrz', 'dsk_story_type_formula', null, ['queue' => true]);
        foreach ($arr as $k => $v) {
            $arr[$k] = stry_type_txt($v);
        }

        ctrl('form', 'select', $tx['LBL']['type'], 'StoryType', arr2mnu($arr, $stry_def));


        // Task UID

        $vlu = users2mnu(crw_groups(5), null, ['zero-txt' => $tx['LBL']['undefined']]);
        ctrl('form', 'select', txarr('arrays', 'epg_crew_types', 5), 'UID', $vlu, 'required');
    }


    form_panel_output('foot');


    if ($x['PG_TYP']=='mdf_dsc') {

        /* CRW FIELD */
        crw_output('mdf', @$x['CRW'], [5,6,7], ['collapse'=>false]);

        /* NOTE FIELD */
        if ($cfg[SCTN]['dsk_use_notes']) {
            form_accordion_output('head', $tx['LBL']['note'], 'note', ['collapse'=>(@$x['Note'] ? true : false)]);
            ctrl('form', 'textbox', $tx['LBL']['note'], 'Note', @$x['Note']);
            form_accordion_output('foot');
        }
    }


} else { // mdf_atom


    if (!$x['ID']) { // NEW: show CAPTION textbox

        echo '<div class="row"><div class="well well-sm '.$bs_css['panel_w'].'">';

        echo
            '<div class="col-sm-12"><input type="text" name="Caption" id="Caption" class="form-control input-lg" '.
            'value="'.@$x['Caption'].'" placeholder="'.$tx['LBL']['title'].'" required autofocus></div>';

        echo '</div></div>';
    }

    // TABLE

    echo '<div class="row"><table id="dndtable" class="'.$bs_css['panel_w'].'">';

    atom_output('mdf', (@$x['ATOMZ']) ? $x['ATOMZ'] : stry_defaults($cfg[SCTN]['strytyp_default_'.CHNLTYP]));

    echo '</table></div>';


    // Clones
    atom_output('mdf_clone', stry_defaults(0)); // 0 is for '1-2-3' story formula, because we need all atom types (1,2,3)


    // ADD-ATOM BUTTONS

    echo '<div class="row"><div class="btnbar stry '.$bs_css['panel_w'].'"><div class="btn-toolbar">';

    foreach ($arr_atom_typz as $k => $v) {

        echo '<a type="button" class="text-uppercase btn btn-success btn-sm" href="#" '.
            'onClick="atom_clone('.$k.'); return false;">'.
            '<span class="glyphicon glyphicon-plus-sign new"></span>'.$v.'</a>';
    }

    echo '</div></div></div>';

}


// SUBMIT BUTTON

$name = 'Submit_STRY_'.$typ_submit;

if ($x['PG_TYP']=='mdf_atom' && @$x['Phase']!=4) {

    echo
        '<div class="row"><div class="'.$bs_css['panel_w'].'" style="padding:0">'.
            '<div class="col-xs-6" style="padding-left:0">'.
                form_btnsubmit_output($name, ['type' => 'btn_only', 'css' => 'btn-lg col-xs-12'], false).
        '</div>'.
            '<div class="col-xs-6" style="padding-right:0">'.
                form_btnsubmit_output($name.'+PHZ',
                    [   'type' => 'btn_only',
                        'css' => 'btn-lg col-xs-12',
                        'btn_txt' => $tx['LBL']['save'].' & '.$tx['LBL']['send'],
                        'sec' => true   ],
                    false).
            '</div>'.
        '</div></div>';

} else {

    form_btnsubmit_output($name);
}


// FORM close
form_tag_output('close');





/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';

