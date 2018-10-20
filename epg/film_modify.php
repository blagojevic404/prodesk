<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$typ = (isset($_GET['typ'])) ? $_GET['typ'] : '';
if ($typ && !in_array($typ, ['item', 'contract', 'agent'])) {
    $typ = '';
}

$x = film_reader($id, $typ);


if (isset($_GET['contractid'])) {

    $x['Contract']['ID'] = wash('int', $_GET['contractid']);

    $x['Contract'] = film_reader($x['Contract']['ID'], 'contract');

    // User wanted this convenience: when adding a new film from the contract page, then pre-set license start & expire
    // controls, because they are usually same for all items of contract.
    $x['LicenceStartTXT'] = t2boxz($x['Contract']['LicenceStart'], 'date');
    $x['LicenceExpireTXT'] = t2boxz($x['Contract']['LicenceExpire'], 'date');
}


pms('epg/film', 'mdf', $x, true);



/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'film';
$header_cfg['bsgrid_typ'] = 'regular';


/* SUBMIT CHECK*/
$cz = channelz(['typ' => 1]); foreach($cz as $k => $v) {$cz[$k] = 'Channels['.$k.']';}
$header_cfg['form_checker'][] = ['typ'=>'chk_group', 'cpt'=>$tx[SCTN]['LBL']['channels'], 'element'=>$cz];
unset($cz);
$footer_cfg['modal'][] = 'alerter';


/*CSS*/

$header_cfg['css'][] = 'modify.css';
$header_cfg['css'][] = 'epg/epg_film.css';
$header_cfg['css'][] = 'epg/epg_bcast.css';


/*JS*/

if ($x['TYP']=='item') {

    // ifrm
    $ifrm = ifrm_setting($x, ['typ' => 'film_contract']);
    $header_cfg['js'][] = 'ifrm.js';
    $header_cfg['js_lines'][]  = 'var ifrm_result_typ = "single";';
    $header_cfg['js_lines'][]  = 'var ifrm_id = "'.$ifrm['name'].'";';

    $header_cfg['js'][] = 'epg/film_genres_flip.js';
    $header_cfg['js_onload'][] = 'film_genres_flip()';

    $header_cfg['autotab'][] = '#DurApproxHH, #DurApproxMM, #DurApproxSS';
    $header_cfg['autotab'][] = '#DurRealHH, #DurRealMM, #DurRealSS';
}

if (in_array($x['TYP'], ['item','contract'])) {

    $header_cfg['bs_daterange'] = ['single' => true, 'submit' => false];
    if ($x['TYP']=='item') 		$header_cfg['bs_daterange']['name'] = ['LicenceStart', 'LicenceExpire'];
    if ($x['TYP']=='contract') 	$header_cfg['bs_daterange']['name'] = ['DateContract', 'LicenceStart', 'LicenceExpire'];
}

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

if (@$x['ID']) {
    crumbs_output('item', sprintf('%04s',$x['ID']), 'film_details.php?typ='.$x['TYP'].'&id='.$x['ID']);
}

crumbs_output('close');




// FORM start
form_tag_output('open', 'film_details.php?typ='.$x['TYP'].'&id='.$x['ID']);




if ($x['TYP']=='item') {


    /* MAIN FIELD */

    form_panel_output('head');

    // TITLE
    ctrl('form', 'textbox', $tx['LBL']['title'], 'Title', @$x['Title'], 'required');

    // TYPE
    form_ctrl_hidden('TypeID', $x['TypeID']);
    detail_output(['lbl' => $tx[SCTN]['LBL']['format'], 'txt' => txarr('arrays', 'film_types', $x['TypeID'])]);

    // SECTION
    ctrl('form', 'select-txt', $tx['LBL']['type'], 'SectionID', @$x['SectionID'],
        ['ctg_name'=>'film_sections', 'allow_none'=>false]);

    // LICENCE START
    ctrl('form', 'textbox', $tx[SCTN]['LBL']['licence'].': '.$tx[SCTN]['LBL']['start'],
        'LicenceStart', @$x['LicenceStartTXT']['ymd']);

    // LICENCE FINISH
    ctrl('form', 'textbox', $tx[SCTN]['LBL']['licence'].': '.$tx[SCTN]['LBL']['finish'],
        'LicenceExpire', @$x['LicenceExpireTXT']['ymd']);

    if ($x['TypeID']!=1) { // film serial

        // EPISODE COUNT
        ctrl('form', 'number', $tx[SCTN]['LBL']['episode_count'], 'EpisodeCount', @$x['EpisodeCount'], 'min="2"');

        // DUR-AVERAGE
        ctrl('form', 'textbox',  $tx['LBL']['duration'].' ('.$tx['LBL']['average'].')', 'DurDesc', @$x['DurDesc']);

    } else { // film movie

        // DUR-APPROX
        $html = '<div class="dur durapprox">'.form_hms_output('film', 'DurApprox', @$x['DurApproxTXT'], 'dur').'</div>';
        ctrl('form', 'block', $tx['LBL']['duration'].' ('.$tx[SCTN]['LBL']['approx'].')', 'DurApproxHH', $html, 'form-inline');

        // DUR-REAL
        $html = '<div class="dur durreal">'.form_hms_output('film', 'DurReal', @$x['DurRealTXT'], 'dur').'</div>';
        ctrl('form', 'block', $tx['LBL']['duration'].' ('.$tx[SCTN]['LBL']['correct'].')', 'DurRealHH', $html, 'form-inline');
    }

    // DELIVERED
    ctrl('form', 'radio', $tx[SCTN]['LBL']['delivered'], 'IsDelivered', @$x['IsDelivered'], lng2arr($tx['LBL']['opp_yesno']));

    // PRODUCTION TYPE
    ctrl('form', 'radio', $tx['LBL']['production'], 'ProdType', @$x['ProdType'], txarr('arrays', 'prod_types'));

    // CHANNELS
    ctrl('form', 'chk-list', $tx[SCTN]['LBL']['channels'], 'Channels', @$x['Channels'], channelz(['typ' => 1]));



    // BROADCAST MAX

    if ($cfg[SCTN]['bcast_cnt_separate']) { // each channel has its own bcmax

        ctrl('form', 'textbox-list', $tx[SCTN]['LBL']['bc_max'], 'BCmax', @$x['BCmax_arr'], channelz(['typ' => 1]));

    } else { // only one bcmax is defined and it is divided between multiple channels

        ctrl('form', 'number', $tx[SCTN]['LBL']['bc_max'], 'BCmax', @$x['BCmax']);
    }



    // IFRAME LABEL
    ifrm_output_lbl('single', $ifrm);

    form_panel_output('foot');


    // IFRAME CONTROL
    ctrl_ifrm('single', $ifrm);



    /* NOTE FIELD */

    form_accordion_output('head', $tx['LBL']['note'], 'note', ['collapse'=>false]);

    ctrl('form', 'textbox', $tx['LBL']['note'], 'Note', @$x['Note']);

    form_accordion_output('foot');



    /* DSC FIELD */

    form_accordion_output('head', $tx['LBL']['dsc'], 'dsc', ['collapse'=>false]);

    ctrl('form', 'textbox', $tx[SCTN]['LBL']['title_original'], 'OriginalTitle', @$x['DSC']['OriginalTitle']);

    ctrl('form', 'select-txt', $tx['LBL']['language'], 'LanguageID', @$x['DSC']['LanguageID'],
        ['ctg_name'=>'film_languages', 'allow_none'=>true]);

    ctrl('form', 'textbox', $tx[SCTN]['LBL']['country'], 'Country', @$x['DSC']['Country']);

    ctrl('form', 'textbox', $tx[SCTN]['LBL']['year'], 'Year', @$x['DSC']['Year']);

    ctrl('form', 'textbox', $tx[SCTN]['LBL']['director'], 'Director', @$x['DSC']['Director']);

    ctrl('form', 'textbox', $tx[SCTN]['LBL']['writer'], 'Writer', @$x['DSC']['Writer']);

    ctrl('form', 'textarea', $tx[SCTN]['LBL']['actors'], 'Actors', @$x['DSC']['Actors'], 3);

    ctrl('form', 'textarea', $tx[SCTN]['LBL']['dsc_short'], 'DscShort', @$x['DSC']['DscShort'], 3);

    ctrl('form', 'textarea', $tx[SCTN]['LBL']['dsc_long'], 'DscLong', @$x['DSC']['DscLong'], 3);

    ctrl('form', 'textbox', $tx['LBL']['dsc_epg'], 'DscTitle', @$x['DSC']['DscTitle']);

    if ($cfg['lbl_parental_filmbased']) {
        ctrl('form', 'number', $tx[SCTN]['LBL']['parental'], 'Parental', @$x['DSC']['Parental'], 'min="12" max="18", step="2"');
    }

    if ($x['TypeID']!=1) { // not movie
        ctrl('form', 'textbox', $tx[SCTN]['LBL']['seasons'], 'Seasons_arr', @$x['DSC']['Seasons_arr']);
    }

    // GENRES

    $genres = txarr('arrays', 'film_genres');
    $genres1 = array_slice_assoc($genres, [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,30,31,32]);
    $genres2 = array_slice_assoc($genres, [21,22,23,24,25]);

    echo '<div id="genres1">';
    ctrl('form', 'chk-list', $tx[SCTN]['LBL']['genre'], 'Genres', @$x['Genres'], $genres1);
    echo '</div>';

    echo '<div id="genres2">';
    ctrl('form', 'chk-list', $tx[SCTN]['LBL']['genre'], 'Genres', @$x['Genres'], $genres2);
    echo '</div>';

    form_accordion_output('foot');
}





if ($x['TYP']=='contract') {

    form_panel_output('head');

    // CODE LABEL
    ctrl('form', 'textbox', $tx['LBL']['label'], 'CodeLabel', @$x['CodeLabel'], 'required');

    // AGENCY
    ctrl('form', 'select-db', $tx[SCTN]['LBL']['agent'], 'AgencyID', @$x['AgencyID'],
        ['sql' => 'SELECT ID, Caption FROM film_agencies ORDER BY Caption ASC', 'zero_txt' => $tx['LBL']['undefined']]);

    // DATE
    ctrl('form', 'textbox', $tx['LBL']['date'], 'DateContract', @$x['DateContractTXT']['ymd']);

    // LICENCE TYPE
    ctrl('form', 'textbox', $tx[SCTN]['LBL']['licence_type'], 'LicenceType', @$x['LicenceType']);

    // LICENCE START
    ctrl('form', 'textbox', $tx[SCTN]['LBL']['licence'].': '.$tx[SCTN]['LBL']['start'],
        'LicenceStart', @$x['LicenceStartTXT']['ymd']);

    // LICENCE FINISH
    ctrl('form', 'textbox', $tx[SCTN]['LBL']['licence'].': '.$tx[SCTN]['LBL']['finish'],
        'LicenceExpire', @$x['LicenceExpireTXT']['ymd']);

    // MONEY
    $html = ctrl('form', 'textbox', null, 'PriceSum', @$x['PriceSum'], null, ['nowrap' => true, 'rtyp' => 'return']).
            ctrl('form', 'select-txt', null, 'PriceCurrencyID', @$x['PriceCurrencyID'],
                ['ctg_name'=>'currencies', 'src_type'=>'CFGARR_LOCAL', 'allow_none'=>false],
                ['nowrap' => true, 'rtyp' => 'return']);
    ctrl('form', 'block', $tx[SCTN]['LBL']['value'], 'PriceSum', $html, 'form-inline');

    form_panel_output('foot');
}





if ($x['TYP']=='agent') {

    form_panel_output('head');

    // CAPTION
    ctrl('form', 'textbox', $tx['LBL']['title'], 'Caption', @$x['Caption'], 'required');

    form_panel_output('foot');
}






// SUBMIT BUTTON
form_btnsubmit_output('Submit_FILM');



// FORM close
form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
