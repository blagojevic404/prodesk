<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$x = org_reader($id);


if (isset($_POST['Submit_ORG'])) {
    require '_rcv/_rcv_org.php';
}


if (!$x['ID']) {
    require '../../__inc/inc_404.php';
}


define('PMS', pms('hrm/org','mdf'));


// Set group CHIEF via shortcut button
if (PMS && isset($_GET['chief'])) {

    $chief_id = intval($_GET['chief']);

    qry('UPDATE hrm_groups SET ChiefID='.$chief_id.' WHERE ID='.$x['ID'], ['x_id' => $x['ID']]);

    hop($_SERVER['HTTP_REFERER']);
}



/*************************** CONFIG ****************************/

$header_cfg['subscn'] = $x['TYP'];
$header_cfg['bsgrid_typ'] = 'regular';
$header_cfg['logz'] = true;

$footer_cfg['modal'][] = ['deleter'];

/*CSS*/
$header_cfg['css'][] = 'details.css';

require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', rdr_cell('hrm_groups', 'Title', 1));
crumbs_output('item', $tx['NAV'][31], 'org.php');

$deleter_argz = [
    'pms' => pms('hrm/org', 'del', $x),
    'txt_body_itemtyp' => $tx['LBL']['group'],
    'txt_body_itemcpt' => $x['Title'],
    'submiter_href' => 'delete.php?typ=org&id='.$x['ID'],
    'button_css' => 'btn-xs pull-right'
];
modal_output('button', 'deleter', $deleter_argz);

btn_output('mdf', ['css' => 'btn-xs pull-right']);

crumbs_output('close');



/* HEADER BAR */

$opt = ['title' => $x['Title']];
headbar_output('open', $opt);
headbar_output('close', $opt);



/* MAIN FIELD */

form_panel_output('head-dtlform');

// Up/Down-branch
$branch_up = branch_up_get($x['ID']);
branch_output($branch_up, $x);

form_panel_output('foot');



/* WORKERS FIELD */

form_accordion_output('head', $tx['NAV'][32], 'workers');

uzr_list($x['ID'], ['chief_id' => $x['ChiefID']]);

form_accordion_output('foot');



logzer_output('box', $x, ['righty_skip' => true]);



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
