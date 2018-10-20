<?php
require '../../__ssn/ssn_boot.php';

$x['TYP'] = 'org';

/*************************** HEADER ****************************/

$header_cfg['subscn'] = $x['TYP'];
$header_cfg['bsgrid_typ'] = 'regular';


require '../../__inc/_1header.php';
/***************************************************************/



echo '<div class="row"><div class="btnbar '.$bs_css['panel_w'].'">';

echo '<span class="company_name">'.rdr_cell('hrm_groups', 'Title', 1).'</span>';

btn_output('new', ['itemtyp' => $tx['LBL']['group']]); // BTN: NEW ORG-GROUP

echo '</div></div>';



/* MAIN FIELD */

form_panel_output('head-dtlform');

$org = org_tree_get(1);

org_tree_output('list', $org);

form_panel_output('foot');




/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
