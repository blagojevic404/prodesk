<?php

require '../../__ssn/ssn_boot.php';




$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$typ = (isset($_GET['typ'])) ? $_GET['typ'] : '';
if ($typ && !in_array($typ, ['item', 'contract', 'agent'])) {
    $typ = '';
}

$x = film_reader($id, $typ);

define('PMS', pms('epg/film', 'mdf'));
define('PMS_BC', pms('epg/film', 'bcast_mdf'));




if ($x['TYP']=='item' && $x['TypeID']!=1) { // If film serial, read the episodes.

	$x['Episodes'] = rdr_cln('film_episodes', 'Ordinal', 'ParentID='.$x['ID']);
}

if (isset($_POST['Submit_FILM'])) {
	
	require '_rcv/_rcv_film.php';

} elseif (isset($_POST['Submit_FILM_EP'])) {

    require '_rcv/_rcv_film_ep.php';

} elseif (isset($_POST['Submit_FILM_EP_USAGE']) || isset($_POST['Submit_FILM_EP_USAGE_AUTO'])) {

    require '_rcv/_rcv_film_ep_usage.php';

} elseif (isset($_GET['bc_add']) || isset($_GET['bc_del'])) {

    require '_rcv/_rcv_film_bc.php';

} else {

    if (!$x['ID']) {
        require '../../__inc/inc_404.php';
    }
}






/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'film';
$header_cfg['bsgrid_typ'] = 'regular';
$header_cfg['logz'] = true;

$footer_cfg['modal'][] = ['deleter'];


/*CSS*/
$header_cfg['css'][] = 'details.css';
$header_cfg['css'][] = 'epg/epg_film.css';
$header_cfg['css'][] = 'epg/epg_bcast.css';


require '../../__inc/_1header.php';
/***************************************************************/





// CRUMBS

crumbs_output('open');

crumbs_output('item', $tx['NAV'][77]);

crumbs_output(
    'item',
    $tx[SCTN]['LBL'][$x['TYP'].'s'],
    'list_film.php?typ='.$x['TYP'] . (($x['TYP']=='item') ? '&cluster[1]='.$x['TypeID'].'&cluster[2]='.$x['SectionID'] : '')
);

crumbs_output('item', sprintf('%04s',$x['ID']));

crumbs_output('close');








// HEADER BAR

switch ($x['TYP']) {
    case 'item': $cpt = $x['Title']; break;
    case 'contract': $cpt = $x['CodeLabel']; break;
    case 'agent': $cpt = $x['Caption']; break;
}

$opt = ['title' => $cpt, 'toolbar' => true];
headbar_output('open', $opt);

pms_btn( // BTN: MODIFY
    PMS, '<span class="glyphicon glyphicon-cog mdf"></span>'.$tx['LBL']['modify'],
    [   'href' => 'film_modify.php?typ='.$x['TYP'].'&id='.$x['ID'],
        'class' => 'btn btn-info btn-sm text-uppercase'    ]
);

$deleter_argz = [
    'pms' => pms('epg/film', 'del', $x),
    'txt_body_itemtyp' => $tx['NAV'][77].(($x['TYP']!='item') ? ' '.$tx[SCTN]['LBL'][$x['TYP']] : ''),
    'txt_body_itemcpt' => $cpt,
    'submiter_href' => 'delete.php?typ=epg_film_'.$x['TYP'].'&id='.$x['ID'].
        (($x['TYP']=='item') ? '&filmtyp='.$x['TypeID'].'&filmsct='.$x['SectionID'] : '')
];
modal_output('button', 'deleter', $deleter_argz);

headbar_output('close', $opt);








if ($x['TYP']=='item') {


    /* MAIN FIELD */

    form_panel_output('head-dtlform');

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['format'], 'txt' => txarr('arrays', 'film_types', $x['TypeID']) ]);

    detail_output([ 'lbl' => $tx['LBL']['type'], 'txt' => txarr('arrays', 'film_sections', $x['SectionID']) ]);

    detail_output(
        [   'lbl' => $tx[SCTN]['LBL']['licence'],
            'txt' => @$x['LicenceStartTXT']['dmy'].'&nbsp;&#8212;&nbsp;'.@$x['LicenceExpireTXT']['dmy'] ]
    );

    if ($x['TypeID']!=1) { // film serial
        detail_output([ 'lbl' => $tx[SCTN]['LBL']['episode_count'], 'txt' => @$x['EpisodeCount'] ]);
    }

    // DURATION
    if ($x['TypeID']==1) { // film movie
        $cpt = $tx['LBL']['duration'];
        $txt = '';
        if (@$x['DurApprox'] && $x['DurApprox']!='00:00:00') {
            $txt .= '<span class="durapprox">&#8776;&nbsp;'.@$x['DurApproxTXT']['hhmmss'].'</span>';
        }
        if (@$x['DurReal'] && $x['DurReal']!='00:00:00') {
            $txt .= '<span class="durreal" style="margin-left:7px">'.@$x['DurRealTXT']['hhmmss'].'</span>';
        }
    } else { // film serial
        $cpt = $tx['LBL']['duration'].' ('.$tx['LBL']['average'].')';
        $txt = @$x['DurDesc'];
    }
    detail_output([ 'lbl' => $cpt, 'txt' => $txt ]);

    detail_output(
        [   'lbl' => $tx[SCTN]['LBL']['delivered'],
            'txt' => '<span style="color:'.(($x['IsDelivered']) ? '#070' : '#b00').';">'.
                lng2arr($tx['LBL']['opp_yesno'], intval($x['IsDelivered'])).'</span>'     ]
    );

    // Production Type
    detail_output(['lbl' => $tx['LBL']['production'], 'txt' => txarr('arrays', 'prod_types', $x['ProdType'])]);

    // CHANNELS
    $tmp_arr = [];
    $chnl_arr = channelz(['typ' => 1]);
    foreach ($x['Channels'] as $v) {
        $tmp_arr[] = $chnl_arr[$v];
    }
    detail_output([ 'lbl' => $tx[SCTN]['LBL']['channels'], 'txt' => implode(', ', $tmp_arr) ]);

    form_panel_output('foot');




    /* BCAST FIELD */

    form_accordion_output('head', $tx[SCTN]['LBL']['bcasts'], 'bcfield', ['type'=>'dtlform']);

    if (!$cfg[SCTN]['bcast_cnt_separate']) {
        detail_output(
            [   'lbl' => $tx[SCTN]['LBL']['bc_numbers'],
                'txt' => epg_bcast_countbox($x['EPG_SCT_ID'], $x['ID'], $x['BCmax'])    ]
        );
    }

    detail_output_broken('open', [ 'lbl' => $tx[SCTN]['LBL']['bc_list'], 'css' => 'bc_field' ]);

    foreach ($x['Channels'] as $v) {

        echo '<h4>';

        if ($cfg[SCTN]['bcast_cnt_separate']) {
            epg_bcast_countbox($x['EPG_SCT_ID'], $x['ID'], $x['BCmax_arr'][$v], $v, true);
        }

        echo '<span class="label label-default channel">'.channelz(['id' => $v]).'</span>';

        echo '</h4>';

        epg_bcast_list($x['EPG_SCT_ID'], $x['ID'], 'fullterm', $v, true);

        modal_output('button', 'poster',
            [   'pms' => PMS_BC,
                'button_txt' => '<span class="glyphicon glyphicon-plus"></span>',
                'button_css' => 'bc_add',
                'name_prefix' => 'bc'.$v,
                'data_varz' => ['channel' => $v]]);
    }

    $html_modal_body = '
        <div class="form-group">
            <label for="TermStart" class="control-label">'.$tx['LBL']['date'].':</label>
            <div class="form-inline">
                <div style="display:inline">
                    <input type="hidden" name="ChannelID" id="ChannelID" value="%d">
                    <input type="text" class="form-control" id="TermStart" name="TermStart" placeholder="dd.mm.yyyy.">
                </div>
                <div style="display:inline">'.
        btngroup_builder('Phase',
            [1=>'<span class="glyphicon glyphicon-ok"></span>', '<span class="glyphicon glyphicon-remove"></span>'],
            1, 'form-inline').'
                </div>
            </div>
        </div>';

    foreach ($x['Channels'] as $v) {

        $poster_argz[$v] = [
            'pms' => PMS_BC,
            'name_prefix' => 'bc'.$v,
            'submiter_href' => '?'.$_SERVER["QUERY_STRING"].'&bc_add=1',
            'cpt_header' => $tx[SCTN]['MSG']['bc_add_manually'],
            'txt_body' => sprintf($html_modal_body, $v)
        ];

    }

    // Button to switch all MODIFY buttons on (they are hidden at first)
    echo
        '<a class="opcty2 pull-right" onclick="display_set(\'bc_del\',\'inline\'); display_set(\'bc_add\',\'inline\');">
            <span class="glyphicon glyphicon-cog"></span>
        </a>';

    detail_output_broken('close');

    form_accordion_output('foot');




    /* CONTRACT FIELD */

    form_accordion_output('head', $tx[SCTN]['LBL']['contract'], 'contract', ['type'=>'dtlform', 'collapse'=>true]);

    detail_output(
        [   'lbl' => $tx[SCTN]['LBL']['contract'],
            'txt' => '<a href="?typ=contract&id='.$x['Contract']['ID'].'">'.@$x['Contract']['CodeLabel'].'</a>'     ]
    );

    detail_output(
        [   'lbl' => $tx[SCTN]['LBL']['agent'],
            'txt' => '<a href="?typ=agent&id='.@$x['Contract']['AgencyID'].'">'.@$x['Contract']['AgencyTXT'].'</a>'     ]
    );

    detail_output([ 'lbl' => $tx['LBL']['date'], 'txt' => @$x['Contract']['DateContractTXT']['dmy'] ]);

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['licence_type'], 'txt' => @$x['Contract']['LicenceType'] ]);

    form_accordion_output('foot');



    /* NOTE FIELD */

    if ($x['Note']) {

        form_accordion_output('head', $tx['LBL']['note'], 'note', ['type'=>'dtlform']);

        detail_output([ 'lbl' => $tx['LBL']['note'], 'txt' => @$x['Note'] ]);

        form_accordion_output('foot');
    }



    /* DSC FIELD */

    form_accordion_output('head', $tx['LBL']['dsc'], 'dsc', ['type'=>'dtlform', 'collapse'=>false]);

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['title_original'], 'txt' => $x['DSC']['OriginalTitle'] ]);

    $tmp_arr = [];
    $genres = txarr('arrays', 'film_genres');
    foreach ($x['Genres'] as $v) $tmp_arr[] = $genres[$v];
    detail_output([ 'lbl' => $tx[SCTN]['LBL']['genre'], 'txt' => implode(', ', $tmp_arr) ]);

    detail_output(
        [   'lbl' => $tx['LBL']['language'],
            'txt' => (($x['DSC']['LanguageID']) ? txarr('arrays', 'film_languages', $x['DSC']['LanguageID']) : '') ]
    );

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['country'], 'txt' => $x['DSC']['Country'] ]);

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['year'], 'txt' => $x['DSC']['Year'] ]);

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['director'], 'txt' => $x['DSC']['Director'] ]);

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['writer'], 'txt' => $x['DSC']['Writer'] ]);

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['actors'], 'txt' => $x['DSC']['Actors'] ]);

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['dsc_short'], 'txt' => $x['DSC']['DscShort'] ]);

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['dsc_long'], 'txt' => $x['DSC']['DscLong'] ]);

    detail_output([ 'lbl' => $tx['LBL']['dsc_epg'], 'txt' => $x['DSC']['DscTitle'] ]);

    if ($cfg['lbl_parental_filmbased']) {
        detail_output([ 'lbl' => $tx[SCTN]['LBL']['parental'], 'txt' => $x['DSC']['Parental'] ]);
    }

    if ($x['TypeID']!=1) { // not movie
        detail_output([ 'lbl' => $tx[SCTN]['LBL']['seasons'], 'txt' => $x['DSC']['Seasons_arr'] ]);
    }

    form_accordion_output('foot');



    /* EPISODES FIELD */

    if ($x['TypeID']!=1) {

        form_accordion_output('head', $tx[SCTN]['LBL']['episodes'], 'episodes', ['collapse'=>false]);

        echo '<div class="pull-right hidden-print btn-toolbar" style="margin-bottom:15px">';
        pms_btn( // BTN: MODIFY
            PMS, $tx['LBL']['modify'],
            [   'href' => 'film_modify_epi.php?id='.$x['ID'],
                'class' => 'btn btn-info btn-xs text-uppercase'    ]
        );
        echo '</div>';

        film_episodes_listing('details', $x);

        form_accordion_output('foot');
    }



    /* FILM USAGE FIELD */

    form_accordion_output('head', $tx['LBL']['usage'].' ('.$tx['LBL']['epg'].')', 'usage', ['collapse'=>false]);

    if ($x['TypeID']==1) { // MOVIE

        film_usage($x);

    } else { // (MINI)SERIAL

        echo '<div class="pull-right hidden-print btn-toolbar" style="margin-bottom:15px">';
        pms_btn( // BTN: MODIFY
            PMS, $tx['LBL']['modify'],
            [   'href' => 'film_modify_epiusage.php?id='.$x['ID'],
                'class' => 'btn btn-info btn-xs text-uppercase'    ]
        );
        echo '</div>';

        film_serial_usage($x);
    }

    form_accordion_output('foot');
}











if ($x['TYP']=='contract') {

    $currencies = cfg_local('arrz', 'currencies');


    /* MAIN FIELD */

    form_panel_output('head-dtlform');

    detail_output(
        [   'lbl' => $tx[SCTN]['LBL']['agent'],
            'txt' => '<a href="?typ=agent&id='.$x['AgencyID'].'">'.@$x['AgencyTXT'].'</a>'  ]
    );

    detail_output([ 'lbl' => $tx['LBL']['date'], 'txt' => @$x['DateContractTXT']['dmy'] ]);

    detail_output([ 'lbl' => $tx[SCTN]['LBL']['licence_type'], 'txt' => @$x['LicenceType']  ]);

    detail_output(
        [   'lbl' => $tx[SCTN]['LBL']['licence'],
            'txt' => @$x['LicenceStartTXT']['dmy'].'&nbsp;&#8212;&nbsp;'.@$x['LicenceExpireTXT']['dmy'] ]
    );

    detail_output(
        [   'lbl' => $tx[SCTN]['LBL']['value'],
            'txt' => ((@$x['PriceSum']) ? @$x['PriceSum'].'&nbsp;'.$currencies[@$x['PriceCurrencyID']] : '')  ]
    );

    form_panel_output('foot');


    /* LISTING FIELD */

    form_accordion_output('head', $tx[SCTN]['LBL']['items'], 'listing');

    echo '<div class="pull-right hidden-print btn-toolbar" style="margin-bottom:15px">';

    $film_types = txarr('arrays', 'film_types');
    foreach ($film_types as $k => $v) {

        pms_btn( // BTN: NEW
            PMS, '<span class="glyphicon glyphicon-plus-sign new"></span>'.$v,
            [   'href' => 'film_modify.php?typ=item&filmsct=1&filmtyp='.$k.'&contractid='.$x['ID'],
                'class' => 'btn btn-success btn-xs text-uppercase opcty3'    ]
        );
    }

    echo '</div>';

    film_contract_listing($x['ID']);

    form_accordion_output('foot');
}







if ($x['TYP']=='agent') {

    /* MAIN FIELD - LISTING */

    form_accordion_output('head', $tx[SCTN]['LBL']['contracts'], 'listing');

    film_agency_listing($x['ID']);

    form_accordion_output('foot');
}








logzer_output('box', $x);


if (isset($poster_argz) && is_array($poster_argz)) {
    foreach ($x['Channels'] as $v) {
        modal_output('modal', 'poster', $poster_argz[$v]);
    }
}


/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
