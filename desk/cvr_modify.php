<?php
require '../../__ssn/ssn_boot.php';

define('IFRM', (intval(@$_GET['ifrm'])));


$id         = 	(isset($_GET['id'])) 	    ? wash('int', $_GET['id']) 	        : 0; // for MDF
$owner_typ 	= 	(isset($_GET['owner_typ'])) ? wash('int', $_GET['owner_typ']) 	: 0; // only for NEW
$owner_id   = 	(isset($_GET['owner_id'])) 	? wash('int', $_GET['owner_id']) 	: 0; // only for NEW



$x = cover_reader($id, ['new_ownertyp' => $owner_typ, 'new_ownerid' => $owner_id]);



if ($id && !$x['ID']) { // CVR has been deleted before clicking on the link
    require '../../__inc/inc_404.php';
}


pms('dsk/cvr', (($x['ID']) ? 'mdf' : 'new'), $x, true);




$tx['MOS']	= txarr('labels', 'mos');
$arr_status = lng2arr($tx['LBL']['opp_ready']);




/*************************** CONFIG ****************************/

$header_cfg['ifrmtunel'] = (IFRM) ? true : false;

$header_cfg['subscn'] = $x['TYP'];
$header_cfg['bsgrid_typ'] = (!$header_cfg['ifrmtunel']) ? 'texter' : 'ifrm_mdf';

$footer_cfg['modal'][] = 'alphconv';

/*CSS*/
$header_cfg['css'][] = 'modify.css';
$header_cfg['css'][] = 'cover.css';

$header_cfg['autotab'][] = '#TCinHH, #TCinMM, #TCinSS';
$header_cfg['autotab'][] = '#TCoutHH, #TCoutMM, #TCoutSS';

require '../../__inc/_1header.php';
/***************************************************************/








// CRUMBS

if (!$header_cfg['ifrmtunel']) {

    crumbs_output('open');
    crumbs_output('item', $tx['NAV'][2]);
    crumbs_output('item', $tx['NAV'][24], 'list_cvr.php');

    if (@$x['ID']) {
        crumbs_output('item', sprintf('%04s',$x['ID']), 'cvr_details.php?id='.$x['ID']);
    }

    crumbs_output('item', modal_output('button', 'alphconv', null, false), null, 'pull-right righty');

    crumbs_output('close');
}




// FORM start
form_tag_output('open', 'cvr_details.php?id='.$x['ID'].(IFRM ? '&ifrm='.IFRM : '').cvr_referer());



form_panel_output('head', 'cgmdf');

// Type
ctrl('form', 'select-txt', $tx['LBL']['type'], 'TypeX', @$x['TypeX'],
    ['ctg_name'=>'epg_cover_types', 'allow_none'=>false]);

// Dsc
ctrl('form', (IFRM ? 'textarea_no_js' : 'textarea'), $tx['LBL']['body'], 'Texter', @$x['Texter'], 10);

// TC in/out
$html =
    '<span><span style="margin-right:8px">'.$tx['MOS']['tc-in'].'</span>'.
    form_hms_output('cover', 'TCin', @$x['TCinTXT']).
    '</span>'.
    '<span class="pull-right"><span style="margin-right:8px">'.$tx['MOS']['tc-out'].'</span>'.
    form_hms_output('cover', 'TCout', @$x['TCoutTXT']).
    '</span>';
ctrl('form', 'block', $tx['MOS']['tc-in-out'], 'TCinHH', $html, 'form-inline');


if (pms('dsk/cvr', 'proofer')) {

    echo '<hr>';

    // Proofer
    ctrl('form', 'radio', txarr('arrays', 'dsk_nwz_phases', 3), 'Proofer', 1, $arr_status);
    // Used to be: "((@$x['ProoferUID']) ? 1 : 0)" for *current value*, but then we simply put "1", to switch it on by default
}


if (pms('dsk/cvr', 'kargen')) {

    echo '<hr>';

    // Page label
    ctrl('form', 'textbox', $tx['LBL']['label'], 'PageLabel', @$x['PageLabel'], 'maxlength="'.$cfg['cvr_pagelbl_maxlen'].'"');

    // IsReady
    ctrl('form', 'radio', $tx['MOS']['prep'], 'IsReady', @$x['IsReady'], $arr_status);
}

form_panel_output('foot');



form_ctrl_hidden('OwnerType', $x['OwnerType']);
form_ctrl_hidden('OwnerID', $x['OwnerID']);



// SUBMIT BUTTON
form_btnsubmit_output('Submit_CVR');



// FORM close
form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
