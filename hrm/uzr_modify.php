<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$x = uzr_reader($id);


pms('hrm/uzr', 'mdf', null, true);




/*************************** CONFIG ****************************/

$header_cfg['subscn'] = $x['TYP'];
$header_cfg['bsgrid_typ'] = 'regular';

$header_cfg['alerter_msgz'] = ['fullname' => $tx[SCTN]['MSG']['fullname_exists']];

/*CSS*/
$header_cfg['css'][] = 'modify.css';

if (!@$x['ID']) {
    $header_cfg['ajax'] = true; // We need ajax for *unique_fullname* check when adding new user
}

require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', rdr_cell('hrm_groups', 'Title', 1));
crumbs_output('item', $tx['NAV'][32], 'list_uzr.php');
if (@$x['ID']) {
    crumbs_output('item', sprintf('%04s',$x['ID']), 'uzr_details.php?id='.$x['ID']);
}
crumbs_output('close');



// FORM start
form_tag_output('open', 'uzr_details.php?id='.$x['ID']);



/* MAIN FIELD */
form_panel_output('head');

// Name1
ctrl('form', 'textbox', $tx['LBL']['name1'], 'Name1st', @$x['Name1st'],
    'onblur="unique_fullname_front();" required');

// Name2
ctrl('form', 'textbox', $tx['LBL']['name2'], 'Name2nd', @$x['Name2nd'],
    'onblur="unique_fullname_front();" required');

// Position
ctrl('form', 'textbox', $tx[SCTN]['LBL']['position'], 'Title', @$x['DATA']['Title'], 'required');


// Contract
if ($cfg[SCTN]['ctrl_contract']) {
    ctrl('form', 'radio', $tx[SCTN]['LBL']['contract_type'], 'ContractType', @$x['DATA']['ContractType'],
        txarr('arrays', 'hrm_contract'));
}

if ($cfg[SCTN]['ctrl_fathername'] || $cfg[SCTN]['ctrl_gender']) {
    echo '<hr>';
}

// FatherName
if ($cfg[SCTN]['ctrl_fathername']) {
    ctrl('form', 'textbox', $tx[SCTN]['LBL']['fathername'], 'FatherName', @$x['DATA']['FatherName']);
}

// Gender
if ($cfg[SCTN]['ctrl_gender']) {
    ctrl('form', 'radio', $tx[SCTN]['LBL']['gender'], 'Gender', @$x['DATA']['Gender'],
        lng2arr($tx[SCTN]['LBL']['opp_gender'], null, ['reverse' => true]));
}


// IsActive
echo '<hr>';
ctrl('form', 'radio', $tx['LBL']['state'], 'IsActive', ($x['ID'] ? @$x['IsActive'] : true),
    lng2arr($tx['LBL']['opp_actv'], null, ['reverse' => true]));

form_panel_output('foot');



/* PARENT-GROUP FIELD */

form_accordion_output('head', $tx['LBL']['group'], 'group', ['collapse'=>((@$x['ID']) ? false : true)]);

$org = org_tree_get();

org_tree_output('ctrl', $org, ['ctrl' => ['name' => 'GroupID', 'select_id' => @$x['GroupID']]]);

form_accordion_output('foot');



// SUBMIT BUTTON
form_btnsubmit_output('Submit_UZR');



// FORM close
form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
