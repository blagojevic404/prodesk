<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$x = prgm_reader($id);

prgm_pms($x);





/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'prgm';
$header_cfg['bsgrid_typ'] = 'regular';

/*CSS*/
$header_cfg['css'][] = 'modify.css';

/*JS*/
$header_cfg['js'][]  = 'crew.js';

require '../../__inc/_1header.php';
/***************************************************************/





// CRUMBS
crumbs_output('open');
crumbs_output('item', '<span class="label label-primary channel">'.
    channelz(['id' => $x['TEAM']['ChannelID']], true).'</span> ');
crumbs_output('item', $tx['NAV'][2]);
crumbs_output('item', $tx['NAV'][22], 'list_prgm.php');
if (@$x['ID']) {
    crumbs_output('item', sprintf('%04s',$x['ID']), 'prgm_details.php?id='.$x['ID']);
}
crumbs_output('close');





// FORM start
form_tag_output('open', 'prgm_details.php?id='.$x['ID'].'&chnl='.$x['TEAM']['ChannelID']);


if (PMS_MDF) {

    /* MAIN FIELD */
    form_panel_output('head');

    // Caption
    ctrl('form', 'textbox', $tx['LBL']['title'], 'Caption', @$x['Caption'], 'required');

    // Team
    /* For each channel there is a pseudo-team which has ID = CHNL_ID, and Caption = '-'.
     * It is intended for programs that belong to this channel but do not belong to any team.
     * (This pseudo team should be manually added whenever a new channel is added.)
     */
    $arr = rdr_cln('prgm_teams', 'Caption', 'ID>99 AND ChannelID='.$x['TEAM']['ChannelID'], 'Queue, ID ASC'); // Except pseudo-team
    $arr = [$x['TEAM']['ChannelID'] => $tx['LBL']['undefined']] + $arr; // Prepend pseudo-team
    ctrl('form', 'select', $tx['LBL']['team'], 'TeamID', arr2mnu($arr, @$x['TeamID']));

    // IsActive
    ctrl('form', 'radio', $tx['LBL']['state'], 'IsActive', ($x['ID'] ? @$x['IsActive'] : true),
        lng2arr($tx['LBL']['opp_actv'], null, ['reverse' => true]));

    // Production Type
    ctrl('form', 'radio', $tx['LBL']['production'], 'ProdType', @$x['ProdType'], txarr('arrays', 'prod_types'));

    // Dur (desc)
    ctrl('form', 'textbox', $tx['LBL']['duration'].' ('.$tx['LBL']['average'].')', 'DurDesc', @$x['SETS']['DurDesc']);

    // Term (desc)
    ctrl('form', 'textbox', $tx['LBL']['term'], 'TermDesc', @$x['SETS']['TermDesc']);

    // Note
    ctrl('form', 'textbox', $tx['LBL']['note'], 'Note', @$x['SETS']['Note']);

    form_panel_output('foot');

}


if (PMS_MDF_WEB || PMS_MDF) {

    /* WEB FIELD */

    form_accordion_output('head', $tx['LBL']['website'].' ('.$tx['LBL']['epg'].')', 'web');

    // EPG desc title
    ctrl('form', 'textbox', $tx['LBL']['dsc_epg'], 'DscTitle', @$x['SETS']['DscTitle']);

    ctrl('form', 'chk', $tx['LBL']['web_live'], 'WebLIVE', @$x['SETS']['WebLIVE']);

    ctrl('form', 'chk', $tx['LBL']['web_vod'], 'WebVOD', @$x['SETS']['WebVOD']);

    ctrl('form', 'chk', $tx[SCTN]['LBL']['web_hide'], 'WebHide', @$x['SETS']['WebHide']);

    form_accordion_output('foot');
}


if (PMS_MDF) {

    /* EPG FIELD */

    form_accordion_output('head', $tx['NAV'][205], 'settings');

    ctrl('form', 'chk', $tx[SCTN]['LBL']['epg_rerun'], 'EPG_Rerun', @$x['SETS']['EPG_Rerun']);

    ctrl('form', 'chk', $tx[SCTN]['LBL']['epg_skip_dflt_tmpl_auto_import'], 'EPG_Skip_Dflt_Tmpl_Auto_Import',
        @$x['SETS']['EPG_Skip_Dflt_Tmpl_Auto_Import']);

    ctrl('form', 'chk', $tx[SCTN]['LBL']['prgm_security'], 'SecurityStrict', @$x['SETS']['SecurityStrict']);

    form_accordion_output('foot');
}



if (PMS_MDF_CRW) {

    /* CRW FIELD */
    crw_output('mdf', @$x['CRW'], [1], ['collapse'=>true, 'caption'=>$tx['LBL']['crew'].' ('.$tx[SCTN]['LBL']['pms_for'].')']);
}



// SUBMIT BUTTON
form_btnsubmit_output('Submit_PRGM');



// FORM close
form_tag_output('close');




/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
