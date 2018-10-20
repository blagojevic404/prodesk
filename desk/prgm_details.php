<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$x = prgm_reader($id);


if (isset($_POST['Submit_PRGM'])) {
    require '_rcv/_rcv_prgm.php';
}


if (!$x['ID']) {
    require '../../__inc/inc_404.php';
}



prgm_pms($x, 'dtl'); // // We need this for btn_output('mdf')



/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'prgm';
$header_cfg['bsgrid_typ'] = 'regular';
$header_cfg['logz'] = true;

$footer_cfg['modal'][] = ['deleter'];

/*CSS*/
$header_cfg['css'][] = 'details.css';

require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', '<span class="label label-primary channel">'.
    channelz(['id' => $x['TEAM']['ChannelID']], true).'</span> ');
crumbs_output('item', $tx['NAV'][2]);
crumbs_output('item', $tx['NAV'][22], 'list_prgm.php');
crumbs_output('item', sprintf('%04s',$x['ID']));
crumbs_output('close');



/* HEADER BAR */

$opt = ['title' => $x['Caption'], 'toolbar' => true];
headbar_output('open', $opt);

btn_output('mdf');

$deleter_argz = btn_output('del', ['itemtyp'=>$tx['LBL']['program']]);

headbar_output('close', $opt);



/* MAIN FIELD */

form_panel_output('head-dtlform');

// Team
detail_output(['lbl' => $tx['LBL']['team'], 'txt' => $x['TEAM']['Caption'],
    'tag' => 'a', 'attr' => 'href="tmz.php?id='.$x['TEAM']['ID'].'"']);

// IsActive
detail_output(
    [   'lbl' => $tx['LBL']['state'],
        'txt' => '<span style="color:'.(($x['IsActive']) ? '#070' : '#b00').';">'.
            lng2arr($tx['LBL']['opp_actv'], intval($x['IsActive'])).'</span>'     ]
);

// Production Type
detail_output(['lbl' => $tx['LBL']['production'],
    'txt' => ($x['ProdType']) ? txarr('arrays', 'prod_types', $x['ProdType']) : '-']);

// Dur (desc)
detail_output(['lbl' => $tx['LBL']['duration'].' ('.$tx['LBL']['average'].')', 'txt' => $x['SETS']['DurDesc']]);

// Term (desc)
detail_output(['lbl' => $tx['LBL']['term'], 'txt' => $x['SETS']['TermDesc']]);

// Note
detail_output(['lbl' => $tx['LBL']['note'], 'txt' => $x['SETS']['Note']]);

form_panel_output('foot');



/* WEB FIELD */

form_accordion_output('head', $tx['LBL']['website'].' ('.$tx['LBL']['epg'].')', 'web', ['type'=>'dtlform', 'collapse'=>true]);

if ($x['SETS']['WebHide']) {
    detail_output(['lbl' => $tx[SCTN]['LBL']['web_hide'], 'val' => intval($x['SETS']['WebHide'])]);
}

if ($x['SETS']['DscTitle']) {
    detail_output(['lbl' => $tx['LBL']['dsc_epg'], 'txt' => $x['SETS']['DscTitle']]);
}

detail_output(['lbl' => $tx['LBL']['web_live'], 'val' => intval($x['SETS']['WebLIVE'])]);

detail_output(['lbl' => $tx['LBL']['web_vod'], 'val' => intval($x['SETS']['WebVOD'])]);

form_accordion_output('foot');



/* EPG FIELD */

form_accordion_output('head', $tx['NAV'][205], 'settings', ['type'=>'dtlform', 'collapse'=>true]);

detail_output(['lbl' => $tx[SCTN]['LBL']['epg_rerun'], 'val' => intval($x['SETS']['EPG_Rerun'])]);

detail_output(['lbl' => $tx[SCTN]['LBL']['epg_skip_dflt_tmpl_auto_import'],
    'val' => intval($x['SETS']['EPG_Skip_Dflt_Tmpl_Auto_Import'])]);

detail_output(['lbl' => $tx[SCTN]['LBL']['prgm_security'], 'val' => intval($x['SETS']['SecurityStrict'])]);

form_accordion_output('foot');



/* CRW FIELD */

$tmp_collapse =  ($x['CRW']) ? true : false;
crw_output('dtl', $x['CRW'], [1],
    ['collapse'=>$tmp_collapse, 'caption'=>$tx['LBL']['crew'].' ('.$tx[SCTN]['LBL']['pms_for'].')']);



/* PRGM/EPG USAGE FIELD */

form_accordion_output('head', $tx['LBL']['usage'].' ('.$tx['LBL']['epg'].')', 'usage');

prgm_usage($x['ID']);

form_accordion_output('foot');



logzer_output('box', $x, ['righty_skip' => true]);



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
