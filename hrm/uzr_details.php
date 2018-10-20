<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$x = uzr_reader($id);


if (isset($_POST['Submit_UZR'])) {
    require '_rcv/_rcv_uzr.php';
}


// PASSWORD CHANGER is in the ADMIN section

require '../../__fn/lib_admin.php';

$tx['admin']['LBL'] = txarr('labels', 'admin');
$tx['admin']['MSG'] = txarr('messages', 'admin');

if (isset($_POST['pass']) && SERVER_TYPE!='dev') {

    define('AD_HTTPS', true);
    // To be able to make modifications to Active Directory via the LDAP connector you must bind to the LDAP service over SSL.
    // Otherwise Active Directory provides a mostly readonly connection. You cannot add objects or modify certain properties
    // without LDAPS, e.g. passwords can only be changed using LDAPS connections to Active Directory.

    $ldap = ad_connect();
    define('LDAP_CON', $ldap['con']);

    $dn = ad_fetch_attr($x['ADuser'], 'distinguishedname');
    define('SAM', $x['ADuser']);

    pass_receiver();
}


if (!$x['ID'] || ($x['IsHidden'] && $x['ID']!=UZID)) { // Hidden account cannot be viewed except by the hidden user himself
    require '../../__inc/inc_404.php';
}



/*************************** CONFIG ****************************/

$header_cfg['subscn'] = $x['TYP'];
$header_cfg['bsgrid_typ'] = 'texter';
$header_cfg['logz'] = true;
$header_cfg['alerter_msgz'] = ['uname' => $tx[SCTN]['MSG']['uname_exists']];

/*JS*/
$header_cfg['js'][]  = 'ajax/editdivable.js';


// LOGIN_HISTORY
$ajax = [
    'type' => 'GET',
    'url' => '_ajax/_aj_history.php', // ajax url
    'data' => 'uid='.$x['ID'],
    'fn' => 'placeholder_filler', // js function to be called on ajax success
    'fn_arg' => 'place_holder' // argument to pass along with the js function on ajax success
];
$footer_cfg['js_lines'][] = "
    $('#history_accordion').on('show.bs.collapse', function() {
        ajaxer('{$ajax['type']}', '{$ajax['url']}', '{$ajax['data']}', '{$ajax['fn']}', '{$ajax['fn_arg']}');
    })";


/*CSS*/
$header_cfg['css'][] = 'details.css';

require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', rdr_cell('hrm_groups', 'Title', 1));
crumbs_output('item', $tx['NAV'][32], 'list_uzr.php');
crumbs_output('close');



/* HEADER BAR */

$opt = ['title' => $x['Name1st'].'&nbsp;'.$x['Name2nd'], 'toolbar' => true];
if (!$x['IsActive']) {
    $opt['subtitle'] = lng2arr($tx['LBL']['opp_actv'], 0);
}

headbar_output('open', $opt);

btn_output('mdf');

if ($x['ID']==UZID) {
    pass_modal_output('button');
}

headbar_output('close', $opt);



/* BRANCH FIELD */

form_panel_output('head-dtlform');

// Up-branch
$branch_up = branch_up_get($x['GroupID'], true);
branch_output($branch_up, null, 'up');

form_panel_output('foot');



/* MAIN FIELD */

form_panel_output('head-dtlform');

// Position
detail_output(['lbl' => $tx[SCTN]['LBL']['position'], 'txt' => $x['DATA']['Title']]);

// Contract
if ($cfg[SCTN]['ctrl_contract']) {
    detail_output([ 'lbl' => $tx[SCTN]['LBL']['contract_type'],
        'txt' => txarr('arrays', 'hrm_contract', $x['DATA']['ContractType']) ]);
}

if ($cfg[SCTN]['ctrl_fathername'] || $cfg[SCTN]['ctrl_gender']) {
    echo '<hr>';
}

// FatherName
if ($cfg[SCTN]['ctrl_fathername']) {
    detail_output(['lbl' => $tx[SCTN]['LBL']['fathername'], 'txt' => $x['DATA']['FatherName']]);
}

// Gender
if ($cfg[SCTN]['ctrl_gender']) {
    detail_output([ 'lbl' => $tx[SCTN]['LBL']['gender'],
        'txt' => lng2arr($tx[SCTN]['LBL']['opp_gender'], intval($x['DATA']['Gender'])) ]);
}

form_panel_output('foot');



/* ADMIN FIELD */

if (pms('hrm/uzr', 'admin')) {

    $ajaxdata = [
        'tblid' => $x['TBLID'],
        'itemid' => $x['ID'],
        'cln' => 'ADuser',
        'pms' => 'hrm/uzr.admin',
        'valtyp' => 'cpt',
    ];

    form_accordion_output('head', $tx[SCTN]['LBL']['admin'], 'admin', ['type'=>'dtlform']);

    detail_output([
        'lbl' => $tx['LBL']['account'],
        'txt' => $x['ADuser'],
        'attr' => 'ondblclick="editdivable_line(this); return false;" '.
            'data-ajax="'.implode(',', $ajaxdata).'" data-type="hrm_uname"'
    ]);


    $html = pms_btn( // BTN: AD-ACCOUNT
        true, $tx['NAV'][83].' '.$tx['LBL']['account'],
        ['href' => '/admin/actdir.php?sam='.$x['ADuser'], 'class' => 'btn btn-default btn-sm text-uppercase',
            'id' => 'btn_ad_account'], false
    );

    ctrl('form', 'block', '&nbsp;', '', $html);

    form_accordion_output('foot');
}



/* LOGIN_HISTORY FIELD */

if (pms('hrm/uzr', 'history', $x)) {

    form_accordion_output('head', $tx['LBL']['usage'], 'history', ['type'=>'dtlform', 'collapse' => false]);

    echo '<div id="place_holder"><span class="glyphicon glyphicon-hourglass"></span></div>';

    form_accordion_output('foot');
}



logzer_output('box', $x, ['righty_skip' => true]);


pass_modal_output('poster');

/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
