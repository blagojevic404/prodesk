<?php

require '../../__ssn/ssn_boot.php';


/** Schedule type: epg, scnr */
define('TYP', (isset($_GET['epg'])) ? 'epg' : 'scnr');

// Take note: *epg* schedule type will actually open an ELEMENT, and *scnr* type will open a FRAGMENT

if (TYP=='epg') {

	$x = element_reader_front('mdf');

    if (!$x['EpgID']) {
        require '../../__inc/inc_404.php';
    }

    $href_submit = 'epg.php?typ='.TYP.
        (($x['ID']) ? '&id='.$x['ID'] : '&qu='.$x['Queue']).
        '&epg='.$x['EpgID'].
        '&ref='.((@$_GET['ref']=='scn') ? 'scn' : 'epg').
        ((isset($_GET['view'])) ? '&view='.intval($_GET['view']) : '').
        '&linetyp='.$x['NativeType'];

    define('PMS', pms('epg', 'mdf_single', $x, true));     // we need return value for NEW STORY button

} else { // scn

	$x = fragment_reader_front();

    define('TMPL', ((empty($x['EPG'])) ? true : false));

	$href_submit = 'epg.php?typ='.TYP.
        (($x['ID']) ? '&id='.$x['ID'] : '&qu='.$x['Queue']).
        '&scnr='.$x['ScnrID'].
        '&linetyp='.$x['NativeType'];

    define('PMS', pms('epg', 'mdf_scn_fragment', $x, true));
}




/** Whether term type represents RELATIVE (rel) or ABSOLUTE (fixed) values.
 * For SCNRs except those of LIVEAIR(1) type, terms (TimeAir column) represent RELATIVE values, instead absolute,
 * i.e. they are relative to the scnr start.. Otherwise, terms are always absolute.
*/
define('TERM_TYP', (TYP=='scnr' && $x['SCNR']['MatType']>1) ? 'rel' : 'fixed');


/** Whether IFRAME will be used depends on the linetype. */
define('DO_IFRM', (in_array($x['NativeType'], [2,3,4,5,12,13,14])));

if (DO_IFRM) {
    $ifrm = ifrm_setting($x);
}



/*************************** HEADER ****************************/


$header_cfg['subscn'] = epg_get_subscn_title($x);
$header_cfg['bsgrid_typ'] = 'regular';


/*CSS*/

$header_cfg['css'][] = 'modify.css';


/*JS*/

$header_cfg['js'][]  = 'epg/epg.js';

if (DO_IFRM) {
    // ifrm
    $header_cfg['js'][]  = 'ifrm.js';
    $header_cfg['js_lines'][]  = 'var ifrm_result_typ = "single";';
    $header_cfg['js_lines'][]  = 'var ifrm_id = "'.$ifrm['name'].'";';
}

if ($x['NativeType']==1) {
    $header_cfg['js'][]  = 'crew.js';
    $footer_cfg['js_lines'][]  = help_output('popover');
    $header_cfg['js_onload'][]  = 'mosbox_switch()';
}


$header_cfg['autotab'][0] = '#term-hh, #term-mm, #term-ss'; $header_cfg['autotab_isterm'][0] = true;
$header_cfg['autotab'][] = '#dur-hh, #dur-mm, #dur-ss';

//$header_cfg['autotab'][] = '#mosdur-hh, #mosdur-mm, #mosdur-ss';
//$header_cfg['autotab'][] = '#tc-in-hh, #tc-in-mm, #tc-in-ss';
//$header_cfg['autotab'][] = '#tc-out-hh, #tc-out-mm, #tc-out-ss';


require '../../__inc/_1header.php';
/***************************************************************/





/* HEADER BAR - BREADCRUMBS */

if (TYP=='epg') {

    $caption['sch_title'] = $tx['LBL']['epg'];

    if (!$x['EPG']['IsTMPL']) { // !TMPL

        $caption['sch'] = epg_cptdate($x['EPG']['DateAir'], 'date');

    } else { // TMPL

        $caption['sch_title'] .= ' '.$tx[SCTN]['LBL']['template'];

        $caption['sch'] = $x['EPG']['Caption'];
    }

} else { // scn

    $caption['sch_title'] = $tx['LBL']['rundown'];

    $caption['sch'] = scnr_cpt_get($x['ELEMENT']['ID']);

    if (TMPL) {

        $caption['sch_title'] .= ' '.$tx[SCTN]['LBL']['template'];

        $caption['sch'] .= ' ('.$x['SCNR']['Caption'].')';
    }
}



// CRUMBS
crumbs_output('open');
crumbs_output('item', $caption['sch_title'], null, 'text-uppercase');
crumbs_output('item', $caption['sch'], 'epg.php?typ='.TYP.'&id='.((TYP=='epg') ? $x['EpgID'] : $x['ELEMENT']['ID']));
crumbs_output('item', txarr('arrays', 'epg_line_types', $x['NativeType']));

if ($x['NativeType']==1) {
    help_output('button', ['content' => txarr('blocks', 'help_mdfprg')]);
}

crumbs_output('close');




// FORM start
form_tag_output('open', $href_submit);





form_panel_output('head');


/* PROG CTRLZ */

if (in_array($x['NativeType'], [1])) { // prog

    // ProgID cbo
    ctrl('form', 'select-prg', $tx['LBL']['program'], 'ProgID', @$x['PRG']['ProgID'], $x['EPG']['ChannelID']);

    // Caption
    ctrl('form', 'textbox', $tx[SCTN]['LBL']['theme'], 'Caption', @$x['PRG']['Caption']);

    // IsReady
    ctrl('form', 'radio', $tx['LBL']['state'], 'IsReady', @$x['PRG']['IsReady'], lng2arr($tx['LBL']['opp_ready']));
}


/* TERM */

if (!in_array($x['NativeType'], [2,8,10])) { // not: note, segmenter, story

    if (TYP=='epg' && @$x['OnHold']) {
        $x['TimeAirTXT'] = ['hh' => '*', 'mm' => '*', 'ss' => '*'];
    }

    $cpt = $tx['LBL']['term'].' ('.$tx[SCTN]['LBL'][TERM_TYP].')';
    $html = '<div class="term-'.TERM_TYP.'">'.form_hms_output('normal', 'term', @$x['TimeAirTXT'], 'term').'</div>';
    ctrl('form', 'block', $cpt, 'term-hh', $html, 'form-inline');
}

/* DUR */

if (!in_array($x['NativeType'], [2,8,10,14])) { // not: note, segmenter, linker, story

    $label = in_array($x['NativeType'], [1,7,12,13]) ? $tx['LBL']['forced'] : $tx[SCTN]['LBL']['fixed'];

    $cpt = $tx['LBL']['duration'].' ('.$label.')';
    $html = '<div class="dur">'.form_hms_output('normal', 'dur', @$x['DurForcTXT'], 'dur').'</div>';
    ctrl('form', 'block', $cpt, 'dur-hh', $html, 'form-inline');
}


/* MATTYPE GROUP ( + parental & rec4rerun) */

if (in_array($x['NativeType'], [1,12,13]) && @$_GET['ref']!='scn') { // program, film

    $epg_material = txarr('arrays', 'epg_material_types');

    if (!isset($x['PRG']['MatType'])) { // Default value for MatType
        $x['PRG']['MatType'] = ($x['NativeType']==1) ? $cfg[SCTN]['epg_mattyp_def'] : 2;
    }

    $arr_disabled = ($x['NativeType']!=1) ? [1=>1] : null; // Films(12,13) cannot be liveair

    $html = btngroup_builder('MatType', $epg_material, @$x['PRG']['MatType'], null, $arr_disabled, 'mosbox_switch();');

    ctrl('form', 'block', $tx[SCTN]['LBL']['material_type'], 'MatType', $html);


    // parental
    if (!$cfg['lbl_parental_filmbased']) {
        ctrl('form', 'textbox', $tx[SCTN]['LBL']['parental'], 'Parental', @$x['Parental'], 'maxlength="2"');
    }
    // rec4rerun
    if (!$cfg['lbl_rec4rerun_prgbased']) {
        ctrl('form', 'radio', $tx[SCTN]['LBL']['record'], 'Record', @$x['Record'], lng2arr($tx['LBL']['opp_yesno']));
    }
}


/* NOTE CAPTION */

if (in_array($x['NativeType'], [7,8,9,10])) {

    ctrl('form', 'textbox', $tx['LBL']['body'], 'Note', @$x['NOTE']['Note'], 'maxlength="90"');
}


/* NOTE TYPE */

if (in_array($x['NativeType'], [8])) {

    ctrl('form', 'select-txt', $tx['LBL']['type'], 'NoteType', @$x['NOTE']['NoteType'], ['ctg_name'=>'epg_note_types']);
}


/* AttrA: PROMO TYPE */

if ($x['NativeType']==4) {

    ctrl('form', 'select-txt', $tx['LBL']['type'], 'AttrA', @$x['AttrA'], ['ctg_name'=>'epg_prm_ctgz']);
}


/* AttrA: PROG Team */

if ($x['NativeType']==1 && @$x['PRG']['ProgID']) {

    $team_id = rdr_cell('prgm', 'TeamID', $x['PRG']['ProgID']);    // Get program's team ID

    if ($team_id<99) { // pseudo-team

        $chnl_id = rdr_cell('prgm_teams', 'ChannelID', $team_id);	// Get the ChannelID for the specified team

        $arr = rdr_cln('prgm_teams', 'Caption', 'ID>99 AND ChannelID='.$chnl_id,  'Queue, ID ASC'); // Except pseudo-team

        ctrl('form', 'select', $tx['LBL']['team'], 'AttrA', arr2mnu($arr, $x['AttrA'], $tx['LBL']['undefined']));
    }
}


/* AttrB: PROD Type */

if ($x['NativeType']==1 && @$x['PRG']['ProgID']) {

    $prod_typ = rdr_cell('prgm', 'ProdType', $x['PRG']['ProgID']);    // Get program's ProdType

    if (!$prod_typ) { // not set

        ctrl('form', 'select-txt', $tx['LBL']['production'], 'AttrB', @$x['AttrB'],
            ['ctg_name'=>'prod_types', 'allow_none'=>true]);
    }
}


/* BUTTON: NEW */

if (!$x['ID'] && $x['NativeType']==2) {

    $html = pms_btn( // BTN: NEW STORY
        PMS, '<span class="glyphicon glyphicon-plus-sign new"></span>'.$tx['LBL']['stry'],
        [   'href' => '/desk/stry_modify.php?typ=mdf_'.(($cfg['strynew_2in1']) ? 'atom' : 'dsc').
            '&scnid='.$x['ScnrID'].'&qu='.$x['Queue'],
            'class' => 'btn btn-success btn-sm text-uppercase'    ], false
    );

    ctrl('form', 'block', '&nbsp;', '', $html);
}


/* IFRAME LABEL */

if (DO_IFRM) {
    ifrm_output_lbl('single', $ifrm);
}


form_panel_output('foot');


/* IFRAME CONTROL */

if (DO_IFRM) {
    ctrl_ifrm('single', $ifrm);
}



/* MOS & CRW */

if (in_array($x['NativeType'], [1])) {

    $cpt = $tx['LBL']['crew'].': ';

    define('CHNLTYP', rdr_cell('channels', 'TypeX', $x['EPG']['ChannelID']));

    crw_output('mdf', @$x['CRW'], ((CHNLTYP==1) ? [12,13,1,2] : [1,2]),
        ['collapse'=>(@$_GET['ref']=='scn'), 'name'=>'crw_desk', 'caption'=>$cpt.$tx['NAV'][2]]);

    crw_output('mdf', @$x['CRW'], ((CHNLTYP==1) ? [3] : [8]), // [3,6,7,8,9,10,11]
        ['collapse'=>false, 'name'=>'crw_master', 'caption'=>$cpt.$tx[SCTN]['LBL']['controlroom']]);

    /*
    crw_output('mdf', @$x['CRW'], ((CHNLTYP==1) ? [12,13,1,2,4,5] : [1,2]),
        ['collapse'=>(@$_GET['ref']=='scn'), 'name'=>'crw_desk', 'caption'=>$cpt.$tx['NAV'][2]]);

    crw_output('mdf', @$x['CRW'], ((CHNLTYP==1) ? [3,10] : [8]), // [3,6,7,8,9,10,11]
        ['collapse'=>(@$_GET['ref']=='scn'), 'name'=>'crw_master', 'caption'=>$cpt.$tx[SCTN]['LBL']['controlroom']]);
     */

    mos_output('mdf', @$x['MOS'], false);
}



/* WEB */

if (TYP=='epg' && in_array($x['NativeType'], [1,12,13,14])) {

	$status_arr = lng2arr($tx['LBL']['opp_yesno'], null, ['reverse' => true]);
	
	if (in_array($x['NativeType'], [12,13]) || ($x['ID'] && $x['PRG']['ProgID'])) {
        // either FILM, or PROG/LINK MODIFY (i.e. not NEW prog)

        switch ($x['NativeType']) {

            case 1: // prog: read default values for every specific prog from its data in DB

                $deflt['WebLIVE'] = $x['PRG']['SETZ']['WebLIVE'];
                $deflt['WebVOD']  = $x['PRG']['SETZ']['WebVOD'];
                break;

            case 14: // link: read default values for every specific prog from its data in DB

                $deflt['WebLIVE'] = $x['LINK']['PRG']['SETZ']['WebLIVE'];
                $deflt['WebVOD']  = $x['LINK']['PRG']['SETZ']['WebVOD'];
                break;

            case 12: // film: read default values from CFG file
            case 13:

                $deflt['WebLIVE'] = $cfg[SCTN]['film_weblive_def'];
                $deflt['WebVOD']  = $cfg[SCTN]['film_webvod_def'];
                break;
		}
		
	} else { // NEW PROG/LINK: we cannot know default values because we still don't have the prog ID..
		
		$x['WebLIVE'] = $x['WebVOD'] = 2; // we put 2 to set both buttons unselected
	}
	
	if (!isset($x['WebLIVE'])) {
        $x['WebLIVE'] = $deflt['WebLIVE'];
    }
	if (!isset($x['WebVOD'])) {
        $x['WebVOD'] = $deflt['WebVOD'];
    }


    form_accordion_output('head', $tx['LBL']['website'], 'web', ['collapse'=>false]);

    ctrl('form', 'radio', $tx['LBL']['web_live'], 'WebLIVE', $x['WebLIVE'], $status_arr);
    ctrl('form', 'radio', $tx['LBL']['web_vod'], 'WebVOD', $x['WebVOD'], $status_arr);

    form_accordion_output('foot');
}


/* TIES */

if (TYP=='epg' && in_array($x['NativeType'], [1,12,13])) {

	if (!$x['EPG']['IsTMPL'] && $x['ID'] && $x['PRG']['MatType']==3) {
	
		$x['TIES'] = [0 => $tx['LBL']['noth']] + epg_tie($x, 'list');

        form_accordion_output('head', $tx[SCTN]['LBL']['premiere'], 'ties');

        ctrl('form', 'radio-vert', '', 'Premiere', $x['TIE']['ID'], $x['TIES']);

        form_accordion_output('foot');
    }

}




form_ctrl_hidden('NativeType', $x['NativeType']);


// SUBMIT BUTTON
form_btnsubmit_output('Submit_SING');





// FORM close
form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';

