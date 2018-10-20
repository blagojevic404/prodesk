<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$x = team_reader($id);


pms('dsk/tmz', (($id) ? 'mdf' : 'new'), $x, true);




/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'tmz';
$header_cfg['bsgrid_typ'] = 'regular';

/*CSS*/
$header_cfg['css'][] = 'modify.css';
$header_cfg['css'][] = 'hrm/common.css';

/*JS*/
$header_cfg['js'][]  = 'crew.js';

require '../../__inc/_1header.php';
/***************************************************************/





// CRUMBS
crumbs_output('open');
crumbs_output('item', '<span class="label label-primary channel">'.channelz(['id' => $x['ChannelID']], true).'</span> ');
crumbs_output('item', $tx['NAV'][2]);
crumbs_output('item', $tx['NAV'][23], 'tmz.php');
crumbs_output('close');





// FORM start
form_tag_output('open', 'tmz.php?id='.$x['ID'].'&chnl='.$x['ChannelID']);


/* MAIN FIELD */
form_panel_output('head');

// Caption
ctrl('form', 'textbox', $tx['LBL']['title'], 'Caption', @$x['Caption'], 'required');

// PMS_loose
ctrl('form', 'radio', $tx[SCTN]['LBL']['pms'], 'PMS_loose', @$x['PMS_loose'], lng2arr($tx[SCTN]['LBL']['opp_pms_loose']));

form_panel_output('foot');


/* CRW FIELD */
crw_output('mdf', @$x['CRW'], [1], ['collapse'=>true, 'caption'=>$tx['LBL']['crew'].' ('.$tx[SCTN]['LBL']['pms_for'].')']);


/* GROUP FIELD */

form_accordion_output('head', $tx['LBL']['group'], 'group', ['collapse'=>false]);

$org = org_tree_get();

org_tree_output('ctrl', $org, ['ctrl' => ['name' => 'GroupID', 'select_id' => @$x['GroupID']]]);

form_accordion_output('foot');


// SUBMIT BUTTON
form_btnsubmit_output('Submit_TMZ');



// FORM close
form_tag_output('close');




/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
