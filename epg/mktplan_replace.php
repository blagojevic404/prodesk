<?php
require '../../__ssn/ssn_boot.php';


$x = spice_reader(null, 'item', 'mkt');


pms('epg/mktplan', 'mdf', null, true);


if (isset($_POST['Submit_MKTPLAN_REPLACE'])) {
    require '_rcv/_rcv_mktplan_replace.php';
}


$ifrm = ifrm_setting($x, ['typ' => 'mktplan_item']);



/*************************** CONFIG ****************************/

$header_cfg['subscn'] = $x['EPG_SCT'];
$header_cfg['bsgrid_typ'] = 'full-w';

/*JS*/
$header_cfg['js'][]  = 'epg/epg.js';
$header_cfg['js'][]  = 'epg/mktplan.js';

/*CSS*/
$header_cfg['css'][] = 'epg/epg_spice.css';


// ifrm
$header_cfg['js'][] = 'ifrm.js';
$header_cfg['js_lines'][]  = 'var ifrm_result_typ = "single";';
$header_cfg['js_lines'][]  = 'var ifrm_id = "'.$ifrm['name'].'";';

// submit check
$header_cfg['form_checker'][] = [
    'typ' => 'mktplan_item_replace',
    'cur' => $x['ID'],
    'msg' => $tx[SCTN]['MSG']['mpi_replace_same']
];
$footer_cfg['modal'][] = 'alerter';
$footer_cfg['modal'][] = ['deleter'];
$deleter_argz = [
    'pms' => true,
    'txt_body' => $tx[SCTN]['MSG']['mpi_replace_del'],
    'submiter_href' => 'javascript:document.form1.submit();'
];

require '../../__inc/_1header.php';
/***************************************************************/


crumbs_output('open');
crumbs_output('item', $tx['NAV'][74]);
crumbs_output('item', $tx[SCTN]['LBL'][$x['TYP']]);
crumbs_output('item', $x['Caption']);
crumbs_output('close');



form_tag_output('open', 'mktplan_replace.php?id='.$x['ID']);


form_panel_output('head');

mktplan_item_list($x['ID'], $x['ChannelID'], 'replace');

form_panel_output('foot');


form_panel_output('head');

ifrm_output_lbl('single', $ifrm); // (We handle it in header cfg above, via $header_cfg['form_checker'])

form_panel_output('foot');

ctrl_ifrm('single', $ifrm);


form_btnsubmit_output('Submit_MKTPLAN_REPLACE', ['btn_txt' => $tx['LBL']['replace']]);


form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
