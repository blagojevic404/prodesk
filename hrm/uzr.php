<?php
require '../../__ssn/ssn_boot.php';



/*************************** HEADER ****************************/

$header_cfg['subscn'] = 'uzr';
$header_cfg['bsgrid_typ'] = 'regular';


require '../../__inc/_1header.php';
/***************************************************************/



echo '<div class="row"><div class="btnbar '.$bs_css['panel_w'].'">';

echo '<span class="company_name">'.rdr_cell('hrm_groups', 'Title', 1).'</span>';

echo '<div class="btn-group btn-group-sm pull-right">'.
    '<a type="button" class="btn btn-default text-uppercase" href="list_uzr.php">'.$tx[SCTN]['LBL']['list'].'</a>'.
    '<a type="button" class="btn btn-default text-uppercase active" href="">'.$tx[SCTN]['LBL']['tree'].'</a>'.
    '</div>';

echo '</div></div>';



/* MAIN FIELD */

form_panel_output('head-dtlform');

$org = org_tree_get(1);

org_tree_output('list_uzr', $org);

form_panel_output('foot');




/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
