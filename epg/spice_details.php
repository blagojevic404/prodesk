<?php
require '../../__ssn/ssn_boot.php';

$tx[SCTN]['LBL']['agent'] = $tx[SCTN]['LBL']['client'];
$tx[SCTN]['LBL']['agents'] = $tx[SCTN]['LBL']['clients'];


$x = spice_reader();


if (isset($_POST['Submit_'.$x['EPG_SCT']])) {
    require '_rcv/_rcv_spice.php';
}

if (isset($_POST['MKTPLAN_MDFID'])) {
    require '_rcv/_rcv_mktplan_item.php';
}


if (!$x['ID']) {
    require '../../__inc/inc_404.php';
}

define('PMS', pms('epg/'.$x['EPG_SCT'], 'mdf', $x));






// MKT PLAN

if ($x['TYP']=='item' && $x['EPG_SCT']=='mkt') {

    $mkt_positions = explode(',', $cfg[SCTN]['mktplan_positions']);

    $x['Caption'] = mkt_cpt_agency($x['Caption'], $x['AgencyID']);

    if ($x['IsBumper']) {
        $x['Caption'] .= ' <span>{'.$tx[SCTN]['LBL']['bumper'].'}</span>';
    }

    if ($x['IsGratis']) {
        $x['Caption'] .= ' <span>{'.$tx[SCTN]['LBL']['gratis'].'}</span>';
    }
}




/*************************** CONFIG ****************************/

$header_cfg['subscn'] = $x['EPG_SCT'];
$header_cfg['bsgrid_typ'] = 'regular';
$header_cfg['logz'] = true;

$footer_cfg['modal'][] = ['deleter'];

if ($x['TYP']=='item' && $x['EPG_SCT']=='mkt') { // MKT PLAN

    define('PMS_MKT_MDF', pms('epg/mktplan', 'mdf'));

    $footer_cfg['modal'][] = ['deleter', ['onshow_js' => 'modal_del_onshow']];

    $footer_cfg['modal'][] = ['poster', ['onshow_js' => 'mktplan_modal_poster_onshow', 'name_prefix' => 'mdfplan']];
    $header_cfg['js_lines'][]  = 'var g_mktplan_zero_pos = '.array_search(0, $mkt_positions).';';

    $header_cfg['js'][]  = 'epg/mktplan.js';

    $header_cfg['bs_daterange'] = ['single' => true, 'submit' => false, 'name' => 'DateEPG'];

    if ($cfg[SCTN]['mktplan_use_notes']) {
        $footer_cfg['js_lines'][] = '$(\'[data-toggle="tooltip"]\').tooltip();'; // Tooltip for MKTPLANITEM NOTE
    }


    if (isset($_GET['mktplanitem'])) { // DISCONTINUED: Open MDF modal for specified mktplanitem

        $mktplanitem = intval($_GET['mktplanitem']);

        if ($mktplanitem) {

            $header_cfg['js_lines'][]  = 'var g_mktplanitem = '.$mktplanitem.';'.PHP_EOL;

            $header_cfg['js_onload'][]  = '$(\'#mdfplan_ONSHOW_JS_Modal\').modal()';
        }
    }

    if (!empty($_GET['ifrm'])) { // Using ID ctrl in mkt list which is opened in iframe (from mktplan_modify_single.php)

        $r = "['".$x['ID']."','".$x['EPG_SCT_ID']."','".$x['DurForc']."','".$x['Caption']."']";

        $header_cfg['js_onload'][] = 'window.parent.ifrm_result('.$r.')';
    }
}

/*CSS*/
$header_cfg['css'][] = 'details.css';
$header_cfg['css'][] = 'epg/epg_spice.css';
$header_cfg['css'][] = 'epg/epg_bcast.css';


require '../../__inc/_1header.php';
/***************************************************************/





// CRUMBS

$tmp = ['mkt'=>74, 'prm'=>75, 'clp'=>76];
crumbs_output('open');

if ($x['EPG_SCT']!='clp') {
    crumbs_output('item', $tx['NAV'][$tmp[$x['EPG_SCT']]]);
    crumbs_output('item', $tx[SCTN]['LBL'][$x['TYP'].'s'], 'list_'.$x['EPG_SCT'].'.php?typ='.$x['TYP']);
} else {
    crumbs_output('item', $tx['NAV'][$tmp[$x['EPG_SCT']]], 'list_clp.php');
}

crumbs_output('item', sprintf('%04s',$x['ID']));
crumbs_output('close');




// HEADER BAR

$opt = ['title' => $x['Caption'], 'toolbar' => true];
headbar_output('open', $opt);

pms_btn( // BTN: MODIFY
    PMS, '<span class="glyphicon glyphicon-cog mdf"></span>'.$tx['LBL']['modify'],
    [   'href' => 'spice_modify.php?sct='.$x['EPG_SCT'].'&typ='.$x['TYP'].'&id='.$x['ID'],
        'class' => 'btn btn-info btn-sm text-uppercase'    ]
);

pms_btn( // BTN: MODIFY-COPY
    PMS, '<span class="glyphicon glyphicon-duplicate"></span>',
    [   'href' => 'spice_modify.php?sct='.$x['EPG_SCT'].'&typ='.$x['TYP'].'&id='.$x['ID'].'&cc=1',
        'class' => 'btn btn-info btn-sm text-uppercase'    ]
);

$deleter_argz = [
    'pms' => pms('epg/'.$x['EPG_SCT'], 'del', $x),
    'txt_body_itemtyp' => $tx[SCTN]['LBL'][$x['EPG_SCT']].(($x['EPG_SCT']!='clp') ? ' '.$tx[SCTN]['LBL'][$x['TYP']] : ''),
    'txt_body_itemcpt' => $x['Caption'],
    'submiter_href' => 'delete.php?typ=epg_'.$x['EPG_SCT'].'_'.$x['TYP'].'&id='.$x['ID']
];
modal_output('button', 'deleter', $deleter_argz);

headbar_output('close', $opt);



if ($x['TYP']!='agent') {

    form_panel_output('head-dtlform');

    if ($x['TYP']=='block') {

        // PRM CATEGORY
        if ($x['EPG_SCT']=='prm') {
            detail_output([ 'lbl' => $tx['LBL']['type'], 'txt' => @$x['CtgTXT'] ]);
        }

        // DUR-FORC
        detail_output([ 'lbl' => $tx['LBL']['duration'].' ('.$tx['LBL']['forced'].')', 'txt' => @$x['DurForcTXT']['mmss'] ]);

        // DUR-CALC
        detail_output(
            [   'lbl' => $tx['LBL']['duration'].' ('.$tx['LBL']['calc'].')',
                'txt' => @$x['DurCalcTXT']['mmss'],
                'css' => $x['DurCalcCSS']   ]
        );
    }

    if ($x['TYP']=='item') {

        // DURATION
        $dur = @$x['DurForcTXT']['mmss'];
        if ($cfg['dur_use_milli']) {
            $dur .= '<span class="hms_ff">.'.$x['DurForcTXT']['ff'].'</span>';
        }
        detail_output([ 'lbl' => $tx['LBL']['duration'], 'txt' => $dur ]);

        // MKT AGENCY
        if ($x['EPG_SCT']=='mkt') {

            if ($cfg[SCTN]['use_mktitem_video_id']) {
                detail_output([ 'lbl' => $tx[SCTN]['LBL']['video_id'], 'txt' => @$x['VideoID'] ]);
            }

            if (!empty($x['AgencyTXT'])) {
                $x['AgencyTXT'] = '<a href="?sct=mkt&typ=agent&id='.$x['AgencyID'].'">'.$x['AgencyTXT'].'</a>';
            }

            detail_output(
                [   'lbl' => $tx[SCTN]['LBL']['agent'],
                    'txt' => @$x['AgencyTXT']     ]
            );
        }

        // PRM CATEGORY
        if ($x['EPG_SCT']=='prm') {
            detail_output([ 'lbl' => $tx['LBL']['type'], 'txt' => @$x['CtgTXT'] ]);
        }

        // CLP PLACING & TARGET
        if ($x['EPG_SCT']=='clp') {
            detail_output([ 'lbl' => $tx[SCTN]['LBL']['target'], 'txt' => @$x['CtgTXT'] ]);
            detail_output([ 'lbl' => $tx[SCTN]['LBL']['place'], 'txt' => @$x['PlacingTXT'] ]);
        }

        // DATE START/FINISH
        if ($x['EPG_SCT']!='clp') {

            detail_output(
                [   'lbl' => $tx['LBL']['date'],
                    'txt' => @$x['DateStartTXT']['dmy'].'&nbsp;&#8212;&nbsp;'.@$x['DateExpireTXT']['dmy'] ]
            );
        }
    }

    form_panel_output('foot');
}



if ($x['TYP']=='block') {


    // BLOCK CONTENT

    form_panel_output('head');

    block_content($x['ID'], 'dtl', $x['EPG_SCT']);

    form_panel_output('foot');


    // MOS
    $tmp_collapse =  ($x['MOS']['IsReady'] || $x['MOS']['Duration'] || $x['MOS']['Label'] || $x['MOS']['Path'] ||
        $x['MOS']['TCin'] || $x['MOS']['TCout']) ? true : false;
    mos_output('dtl', $x['MOS'], $tmp_collapse);

    // CRW
    $tmp_collapse =  ($x['CRW']) ? true : false;
    crw_output('dtl', $x['CRW'], [7], ['collapse'=>$tmp_collapse]);


    // BLOCK BROADCAST

    /*
     * vb2do I will finish this up after I check whether rtrs-mkt wants to use it at all.. It should be similar to film scripts..
     * (search: bc_del)
     *
    form_accordion_output('head');

    detail_output(
        [   'lbl' => $tx[SCTN]['LBL']['bc_list'],
            'txt' => epg_bcast_list($x['EPG_SCT_ID'], $x['ID'], 'fullterm')   ]
    );

    form_accordion_output('foot');
    */
}








// MKT PLAN

if ($x['TYP']=='item' && $x['EPG_SCT']=='mkt') {

    $plan_exists = (bool)rdr_id('epg_market_plan', 'ItemID='.$x['ID']);

    if ($plan_exists) {

        $btn_cpt = '<span class="glyphicon glyphicon-cog mdf"></span>'.$tx['LBL']['modify'];
        $btn_css = 'btn-info';

    } else {

        $btn_cpt = '<span class="glyphicon glyphicon-plus-sign new"></span>'.$tx[SCTN]['LBL']['mkt_plan'];
        $btn_css = 'btn-success';
    }

    $opt['collapse'] = $plan_exists;

    // Buttonz on the right upper side of accordion header
    $opt['righty'] = '';

    if ($plan_exists) {

        $opt['righty'] .= // ARCHIVE btn
            pms_btn(true, $tx['LBL']['archive'],
                [   'href' => '?sct=mkt&typ=item&show_all=1&id='.$x['ID'],
                    'class' => 'btn btn-default btn-xs text-uppercase btn_panel_head btn-grey'   ], false);

        $opt['righty'] .= // REPLACE btn
            pms_btn(PMS_MKT_MDF, '<span class="glyphicon glyphicon-search mdf"></span>'.$tx['LBL']['replacing'],
                [   'href' => 'mktplan_replace.php?id='.$x['ID'],
                    'class' => 'btn btn-info btn-xs text-uppercase btn_panel_head'   ], false);
    }

    $opt['righty'] .=
        pms_btn(PMS_MKT_MDF, $btn_cpt, // MDF-MULTI btn
            [   'href' => 'mktplan_modify_multi.php?id='.$x['ID'],
                'class' => 'btn '.$btn_css.' btn-xs text-uppercase btn_panel_head'   ], false);

    $opt['righty'] .=
        modal_output('button', 'poster', // NEW btn
            [
                'onshow_js' => true,
                'pms' => PMS_MKT_MDF,
                'name_prefix' => 'mdfplan',
                'button_txt' => '<span class="glyphicon glyphicon-plus-sign"></span>',
                'button_css' => 'btn-success btn-xs btn_panel_head'
            ],
            false);

    form_accordion_output('head', $tx[SCTN]['LBL']['mkt_plan'], 'plan', $opt);

    mktplan_item_list($x['ID'], $x['ChannelID'], 'list');

    form_accordion_output('foot');
}






// BLOCK USAGE

if ($x['TYP']=='block' ||
    ($x['EPG_SCT']=='clp' && $x['TYP']=='item') ) {
    // CLIP can be directly part of epg/scenario (which we handle here), or part of block (which we handle with ITEM USAGE)

    form_accordion_output('head', $tx['LBL']['usage'].' ('.$tx['LBL']['epg'].')', 'usage_block', ['collapse'=>false]);

    spcblock_usage($x);

    form_accordion_output('foot');
}


// AGENCY USAGE

if ($x['TYP']=='agent') {

    form_accordion_output('head', $tx[SCTN]['LBL']['items'], 'agency');

    spice_agency_listing($x['ID']);

    form_accordion_output('foot');
}


// ITEM USAGE

if ($x['TYP']=='item' && $x['EPG_SCT']!='clp') {

    form_accordion_output('head', $tx['LBL']['usage'].' ('.$tx['LBL']['epg'].')', 'usage_item', ['type'=>'tbl', 'collapse'=>false]);

    echo '<table class="table item_usage">
            <tr><th>'.$tx[SCTN]['LBL']['block'].'</th><th>'.$tx['LBL']['term'].'</th></tr>';

    spcitem_usage($x);

    echo '</table>';

    form_accordion_output('foot', null, null, ['type'=>'tbl']);
}






logzer_output('box', $x);




/* MKTPLANITEM MDF MODAL */

if ($x['TYP']=='item' && $x['EPG_SCT']=='mkt') {

    mktplan_item_modalmdf($x['ChannelID']);
}




/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
