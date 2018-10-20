<?php
require '../../__ssn/ssn_boot.php';


define('IFRM', (intval(@$_GET['ifrm'])));



$id = (isset($_GET['id'])) ? wash('int', $_GET['id']) : 0;


if (isset($_POST['Submit_CVR'])) {
    require '_rcv/_rcv_cvr.php';
}


$x = cover_reader($id, ['get_ownerdata' => true]);

if (!$x['ID']) {
    require '../../__inc/inc_404.php';
}


define('PMS', pms('dsk/cvr', 'mdf', $x));





$tx['MOS']	= txarr('labels','mos');




/*************************** CONFIG ****************************/

$header_cfg['subscn'] = $x['TYP'];
$header_cfg['bsgrid_typ'] = 'texter';
$header_cfg['logz'] = true;

$footer_cfg['modal'][] = ['deleter'];


/*CSS*/

$header_cfg['css'][] = 'cover.css';
$header_cfg['css'][] = 'details.css';


/*JS*/
$header_cfg['js_onload'][]  = 'textarea_height()';


require '../../__inc/_1header.php';
/***************************************************************/





// CRUMBS
crumbs_output('open');
crumbs_output('item', $tx['NAV'][2]);
crumbs_output('item', $tx['NAV'][24], 'list_cvr.php');
crumbs_output('item', sprintf('%04s',$x['ID']));
crumbs_output('item', $x['OwnerTypeTXT'], $x['OwnerHREF'], 'pull-right righty');
crumbs_output('close');





// HEADER BAR

$opt = ['title' => $x['TypeXTXT'], 'toolbar' => true];

headbar_output('open', $opt);

btn_output('mdf');

$deleter_argz = btn_output('del', ['itemtyp'=>$x['TypeXTXT'].' &mdash; '.$x['OwnerTypeTXT']]);

headbar_output('close', $opt);




/* MAIN FIELD */

form_panel_output('head-dtlform');


// Owner
detail_output(
    [   'lbl' => $x['OwnerTypeTXT'],
        'txt' => '<a href="'.$x['OwnerHREF'].'">'.$x['Caption'].'</a>'.
            ((!empty($x['CaptionSub'])) ? ' <small class="text-muted">/'.$x['CaptionSub'].'/</small>' : '')    ]
);

// Type
detail_output(['lbl' => $tx['LBL']['type'], 'txt' => @$x['TypeXTXT']]);

// Dsc
detail_output(['lbl' => $tx['LBL']['body'], 'txt' => nl2br(@$x['Texter']), 'css' => 'cgtxt']);
/*
detail_output(
    [   'lbl' => $tx['LBL']['body'],
        'txt' => @$x['Texter'],
        'css' => 'form-control no_vert_scroll cgtxt',
        'tag' => 'textarea'  ]
);
*/

// TC in/out
detail_output(
    [   'lbl' => $tx['MOS']['tc-in-out'],
        'txt' => $x['TCinTXT']['hhmmss'].'&nbsp;/&nbsp;'.$x['TCoutTXT']['hhmmss']   ]
);


echo '<hr>';

// Proofer
detail_output(['lbl' => txarr('arrays', 'dsk_nwz_phases', 3), 'txt' => uid2name($x['ProoferUID'])]);


echo '<hr>';

// Page label
detail_output(['lbl' => $tx['LBL']['label'], 'txt' => @$x['PageLabel']]);

// IsReady
detail_output(['lbl' => $tx['MOS']['prep'], 'txt' => lng2arr($tx['LBL']['opp_ready'], intval(@$x['IsReady']))]);

form_panel_output('foot');





logzer_output('box', $x);



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
