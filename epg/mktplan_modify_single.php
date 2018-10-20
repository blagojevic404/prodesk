<?php
/**
 * We had to make this separate page because of IFRM for MKTITEM.
 * It is used in two cases:
 * - NEW-IN-THE-BLOCK - Add new mktplanitem to mktplan block (called via *new* btn in the block row (mktplan-epg list)).
 * - MDF-ITEM - mdf mktplanitem (called via link in MDF-MODAL). It differs from mdf-modal only by having IFRM for MKTITEM,
 *   so the user can change the mktitem for a specific mktplanitem.
 */


require '../../__ssn/ssn_boot.php';


pms('epg/mktplan', 'mdf', null, true);


if (isset($_POST['Submit_MKTPLAN'])) {
    require '_rcv/_rcv_mktplan_item.php';
}


$source_id = (isset($_GET['source_id'])) ? wash('int', $_GET['source_id']) : 0;

$source = rdr_row('epg_market_plan', '*', $source_id);
if (!$source['ID']) redirector('args');

$source['BlockTermEPG'] = ($source['BlockTermEPG']) ? hms2hm($source['BlockTermEPG']) : null;


$x = spice_reader($source['ItemID'], 'item', 'mkt');

$mkt_positions = explode(',', $cfg[SCTN]['mktplan_positions']);

$casetyp = (($_GET['case']=='block') ? 'mktplan_block' : 'mktplan_item');

$ifrm = ifrm_setting($x, ['typ' => $casetyp]);



/*************************** CONFIG ****************************/

$header_cfg['subscn'] = $x['EPG_SCT'];
$header_cfg['bsgrid_typ'] = 'regular';

/*CSS*/
$header_cfg['css'][] = 'modify.css';
$header_cfg['css'][] = 'epg/epg_spice.css';

/*JS*/
$header_cfg['js'][]  = 'epg/mktplan.js';
$header_cfg['bs_daterange'] = ['single' => true, 'submit' => false, 'name' => 'DateEPG'];

// Open mkt-item ifrm on load
if ($casetyp=='mktplan_block') {
    $header_cfg['js_onload'][]  = 'document.getElementById(\'ifrm_label\').click()';
}

// ifrm
$header_cfg['js'][] = 'ifrm.js';
$header_cfg['js_lines'][]  = 'var ifrm_result_typ = "single";';
$header_cfg['js_lines'][]  = 'var ifrm_id = "'.$ifrm['name'].'";';

// submit check
$header_cfg['form_checker'][] = ['typ' => 'ifrm', 'cpt' => $tx[SCTN]['LBL']['item']];
$footer_cfg['modal'][] = 'alerter';

require '../../__inc/_1header.php';
/***************************************************************/


crumbs_output('open');
crumbs_output('item', $tx['NAV'][74]);
crumbs_output('item', $source['DateEPG'], 'epg.php?typ=epg&view=8&id='.epgid_from_date($source['DateEPG'], $x['ChannelID']));
crumbs_output('close');



form_tag_output('open');

form_panel_output('head');

ifrm_output_lbl('single', $ifrm); // *required* (We handle it in header cfg above, via $header_cfg['form_checker'])

form_panel_output('foot');

ctrl_ifrm('single', $ifrm);

form_panel_output('head');

ctrl('form', 'textbox', $tx['LBL']['epg'], 'DateEPG', $source['DateEPG'], 'required');

ctrl('form', 'textbox', $tx['LBL']['term'], 'BlockTermEPG', $source['BlockTermEPG'], 'required maxlength="5"');

ctrl('form', 'select', $tx['LBL']['position'], 'BlockPos', arr2mnu(txarr('arrays', 'epg_mktblc_positions'), $source['BlockPos']));

ctrl('form', 'select-prg', $tx['LBL']['program'], 'BlockProgID', $source['BlockProgID'], $x['ChannelID'], ['prg-mktplan' => true]);

ctrl('form', 'radio', $tx[SCTN]['LBL']['in_block_pos'], 'Position', array_search(0, $mkt_positions), $mkt_positions);

if ($cfg[SCTN]['mktplan_use_notes']) {
    ctrl('form', 'textbox', $tx['LBL']['note'], 'Note', null);
}


form_ctrl_hidden('case', $casetyp);

if ($casetyp=='mktplan_item') {
    form_ctrl_hidden('MKTPLAN_MDFID', $source['ID']);
}


form_panel_output('foot');

form_btnsubmit_output('Submit_MKTPLAN');

form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
