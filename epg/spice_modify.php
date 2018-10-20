<?php
require '../../__ssn/ssn_boot.php';

$tx[SCTN]['LBL']['agent'] = $tx[SCTN]['LBL']['client'];
$tx[SCTN]['LBL']['agents'] = $tx[SCTN]['LBL']['clients'];


$x = spice_reader();


pms('epg/'.$x['EPG_SCT'], 'mdf', $x, true);


define('CC', !empty($_GET['cc'])); // Copy



if ($x['TYP']=='block' && !empty($_GET['rfrtyp']) && !$x['ID']) { // Case: NEW block from epg. Pre-select CTG and caption.

    $x['CtgID'] = intval($_GET['rfrctg']);

    $x['Caption'] = date('Ymd/H:i', $_GET['t']);
}


if ($x['TYP']=='item' && !empty($_GET['TYPX']) && !$x['ID']) { // Case: NEW block from list_prm with selected TYPX. Pre-select CTG.

    $x['CtgID'] = intval($_GET['TYPX']);
}



/*************************** CONFIG ****************************/

$header_cfg['subscn'] = $x['EPG_SCT'];
$header_cfg['bsgrid_typ'] = 'regular';


/*CSS*/

$header_cfg['css'][] = 'modify.css';
$header_cfg['css'][] = 'epg/epg_spice.css';


/*JS*/

if ($x['TYP']=='block') {

    $header_cfg['js'][] = 'epg/spiceblock.js';
    $header_cfg['js_onload'][] = 'SPICE_dur_sum()';

    $header_cfg['js'][]  = 'crew.js';

    // ifrm
    $header_cfg['js'][] = 'ifrm.js';
    $header_cfg['js_lines'][]  = 'var ifrm_result_typ = "spice";';

    // tablednd
    $header_cfg['js'][]  = 'tablednd/tablednd.js';
    $header_cfg['js'][]  = 'tablednd/tablednd_custom.js';
    $header_cfg['js_onload'][]  = 'tableDnDOnLoad()';
    $header_cfg['js_onsubmit'][]  = 'tablednd_queuer(\'spiceblock\')';
}

if ($x['TYP']=='item' && $x['EPG_SCT']!='clp') {
    $header_cfg['bs_daterange'] = ['single' => true, 'submit' => false, 'name' => ['DateStart', 'DateExpire']];
}

if (!$x['ID']) { // If NEW, then focus to Caption textbox on page load.
    $header_cfg['js_onload'][]  = 'document.getElementById(\'Caption\').focus()';
}


if (in_array($x['TYP'], ['item','block'])) {
    $header_cfg['autotab'][] = '#DurForcHH, #DurForcMM, #DurForcSS, #DurForcFF';
}
$header_cfg['autotab'][] = '#tc-in-hh, #tc-in-mm, #tc-in-ss';
$header_cfg['autotab'][] = '#tc-out-hh, #tc-out-mm, #tc-out-ss';


require '../../__inc/_1header.php';
/***************************************************************/





// CRUMBS

crumbs_output('open');

$tmp = ['mkt'=>74, 'prm'=>75, 'clp'=>76];
crumbs_output('item', $tx['NAV'][$tmp[$x['EPG_SCT']]]);

if ($x['EPG_SCT']!='clp') {
    crumbs_output('item', $tx[SCTN]['LBL'][$x['TYP'].'s'], 'list_'.$x['EPG_SCT'].'.php?typ='.$x['TYP']);
}

if ($x['ID'] && !CC) {
    crumbs_output('item', sprintf('%04s',$x['ID']), 'spice_details.php?sct='.$x['EPG_SCT'].'&typ='.$x['TYP'].'&id='.$x['ID']);
}

crumbs_output('close');




// FORM start

$href = 'spice_details.php?sct='.$x['EPG_SCT'].'&typ='.$x['TYP'].'&chnl='.$x['ChannelID'];

if (!CC) $href .= '&id='.$x['ID'];

if (!empty($_GET['rfrtyp'])) { // NEW BLOCK, opened from epg spicer.. Pass GET-attrz to rcv
    $href .= '&rfrtyp='.$_GET['rfrtyp'].'&rfrelm='.$_GET['rfrelm'].'&rfrepg='.$_GET['rfrepg'].'&rfrid='.$_GET['rfrid'];
}

form_tag_output('open', $href);



form_panel_output('head');

// CAPTION
ctrl('form', 'textbox', $tx['LBL']['title'], 'Caption', @$x['Caption'], 'required');

if ($x['TYP']=='block') {

    // PRM CATEGORY
    if ($x['EPG_SCT']=='prm') {
        ctrl('form', 'select-txt', $tx['LBL']['type'], 'CtgID', @$x['CtgID'],
            ['ctg_name'=>'epg_'.$x['EPG_SCT'].'_ctgz']);
    }

    // DUR-FORC
    $html = '<div class="dur">'.form_hms_output('spice', 'DurForc', @$x['DurForcTXT'], 'dur').'</div>';
    ctrl('form', 'block', $tx['LBL']['duration'].' ('.$tx['LBL']['forced'].')', 'DurForcHH', $html, 'form-inline');

    // DUR-CALC
    $html = '<div class="dur durcalc">'.form_hms_output('spice-label', 'DurCalc', @$x['DurCalcTXT']).'</div>';
    ctrl('form', 'block', $tx['LBL']['duration'].' ('.$tx['LBL']['calc'].')', 'DurCalcHH', $html, 'form-inline');
}

if ($x['TYP']=='item') {

    // DURATION
    $html = '<div class="dur durcalc">'.form_hms_output('spice', 'DurForc', @$x['DurForcTXT'], 'dur', true).'</div>';
    ctrl('form', 'block', $tx['LBL']['duration'], 'DurForcHH', $html, 'form-inline');

    // MKT AGENCY & VIDEO_ID
    if ($x['EPG_SCT']=='mkt') {

        if ($cfg[SCTN]['use_mktitem_video_id']) {
            ctrl('form', 'textbox', $tx[SCTN]['LBL']['video_id'], 'VideoID', @$x['VideoID']);
        }

        ctrl('form', 'select-db', $tx[SCTN]['LBL']['agent'], 'AgencyID', @$x['AgencyID'],
            ['sql' => 'SELECT ID, Caption FROM epg_market_agencies WHERE ChannelID='.$x['ChannelID'].' ORDER BY Caption ASC',
                'zero_txt' => $tx['LBL']['undefined']]);
    }

    // PRM CATEGORY
    if ($x['EPG_SCT']=='prm') {
        ctrl('form', 'select-txt', $tx['LBL']['type'], 'CtgID', @$x['CtgID'],
            ['ctg_name' => 'epg_prm_ctgz', 'allow_none' => false]);
    }

    // CLP PLACING & TARGET
    if ($x['EPG_SCT']=='clp') {

        // TARGET filter in clip list will be pre-selected depending on where it is initiated from (mkt, prm, scnr, epg).
        ctrl('form', 'select-txt', $tx[SCTN]['LBL']['target'], 'CtgID', @$x['CtgID'],
            ['ctg_name' => 'epg_clp_ctgz', 'allow_none' => true]);

        ctrl('form', 'select-txt', $tx[SCTN]['LBL']['place'], 'Placing', @$x['Placing'],
            ['ctg_name' => 'epg_clp_place', 'allow_none' => true]);
    }

    // DATE START/FINISH
    if ($x['EPG_SCT']!='clp') {

        ctrl('form', 'textbox', $tx['LBL']['date'].': '.$tx[SCTN]['LBL']['start'],
            'DateStart', @$x['DateStartTXT']['ymd']);

        ctrl('form', 'textbox', $tx['LBL']['date'].': '.$tx[SCTN]['LBL']['finish'],
            'DateExpire', @$x['DateExpireTXT']['ymd']);
    }
}

form_panel_output('foot');



if ($x['EPG_SCT']=='mkt' && $x['TYP']=='item') {

    form_accordion_output('head', $tx[SCTN]['LBL']['other'], 'other', ['collapse'=>false]);

    // GRATIS
    ctrl('form', 'radio', $tx[SCTN]['LBL']['gratis'], 'IsGratis', @$x['IsGratis'], lng2arr($tx['LBL']['opp_yesno']));

    // MKT BUMPER
    ctrl('form', 'radio', $tx[SCTN]['LBL']['bumper'], 'IsBumper', @$x['IsBumper'], lng2arr($tx['LBL']['opp_yesno']));

    form_accordion_output('foot');
}





if ($x['TYP']=='block') {


    form_panel_output('head');

    // BLOCK CONTENT
    block_content($x['ID'], 'mdf', $x['EPG_SCT'], @$x['CtgID']);


    // BUTTONS - IFRAME TRIGGERS

    echo '<div class="ifrmbuttons col-lg-9 col-md-10 col-md-offset-1">';

    $tmp =
        '<a class="btn btn-info text-uppercase btn-sm" href="#" '.
            'onClick="ifrm_starter(\'normal\', \'ifrmtunel\', \'%s\'); return false;">
            <span class="glyphicon glyphicon-circle-arrow-right"></span>%s</a>';

    $url = 'list_'.$x['EPG_SCT'].'.php?typ=item&ifrm=1'.((!empty($x['CtgID'])) ? '&TYPX='.$x['CtgID'] : '');
    printf($tmp, $url, $tx[SCTN]['LBL']['item']);

    $url = 'list_clp.php?ifrm=1&TYPX='.(($x['EPG_SCT']=='mkt') ? 1 : 2);
    printf($tmp, $url, $tx[SCTN]['LBL']['clp']);

    // Note: CTG in both iframes is pre-selected if possible

    echo '</div>';

    form_panel_output('foot');


    // IFRAME CONTROL
    ctrl_ifrm('single_js');

    // MOS & CRW
    mos_output('mdf', @$x['MOS'], false);
    crw_output('mdf', @$x['CRW'], [7], ['collapse'=>false]);
}



// CLONE ITEM (will be picked up from JS:SPICE_item_add())
echo '<div id="clone_block_item" style="display:none">';
block_content_item('mdf', 0, 0, '', '', true);
echo '</div>';



// SUBMIT BUTTON
form_btnsubmit_output('Submit_'.$x['EPG_SCT']);



// FORM close
form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
