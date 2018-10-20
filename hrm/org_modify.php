<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$x = org_reader($id);


pms('hrm/org', 'mdf', null, true);




/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'org';
$header_cfg['bsgrid_typ'] = 'regular';

/*CSS*/
$header_cfg['css'][] = 'modify.css';

require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', rdr_cell('hrm_groups', 'Title', 1));
crumbs_output('item', $tx['NAV'][31], 'org.php');
if (@$x['ID']) {
    crumbs_output('item', sprintf('%04s',$x['ID']), 'org_details.php?id='.$x['ID']);
}
crumbs_output('close');



// FORM start
form_tag_output('open', 'org_details.php?id='.$x['ID']);



/* MAIN FIELD */
form_panel_output('head');

// Title
ctrl('form', 'textbox', $tx['LBL']['title'], 'Title', @$x['Title'], 'required');

// Chief
if (@$x['ID']) {
    $vlu = users2mnu([@$x['ID']], @$x['ChiefID'], ['zero-txt' => $tx['LBL']['undefined']]);
    ctrl('form', 'select', $tx[SCTN]['LBL']['chief'], 'ChiefID', $vlu);
}

form_panel_output('foot');



/* PARENT-GROUP FIELD */

form_accordion_output('head', $tx[SCTN]['LBL']['parent_group'], 'parent_group', ['collapse'=>false]);

$org = org_tree_get();

org_tree_output('ctrl', $org, ['ctrl' => ['name' => 'ParentID', 'disable_id' => @$x['ID'], 'select_id' => @$x['ParentID']]]);

form_accordion_output('foot');



// SUBMIT BUTTON
form_btnsubmit_output('Submit_ORG');



// FORM close
form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
