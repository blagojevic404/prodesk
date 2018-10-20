<?php
require '../../__ssn/ssn_boot.php';


pms('admin', null, null, true);



if (isset($_GET['scrambler'])) {
    $scrambler = ($_GET['scrambler']==1) ? descrambler($_GET['txt']) : scrambler($_GET['txt']);
} else {
    $scrambler = '';
}


/*************************** HEADER ****************************/
$header_cfg['subscn'] = 'tools';
$header_cfg['bsgrid_typ'] = 'halfer';

$header_cfg['js'][] = 'admin/common.js';

require '../../__inc/_1header.php';
/***************************************************************/


// APP CRUMBS
crumbs_output('open');
crumbs_output('item', $tx['NAV'][1]);
crumbs_output('item', $tx['NAV'][209], 'report.php');
crumbs_output('item', $nav_subz[$header_cfg['subscn']]['ttl']);
crumbs_output('close');


echo '<div class="row" style="padding-top: 20px;">';

echo
    '<div class="'.$bs_css['half'].'"><div class="panel panel-default">'.
    '<div class="panel-heading"><h3 class="panel-title">'.$tx[SCTN]['LBL']['scrambler'].'</h3></div>'.
    '<div class="panel-body">'.
        '<div class="input-group">'.
            '<input type="text" class="form-control input-lg" maxlength=30 value="'.$scrambler.'">'.
            '<span class="input-group-btn">'.
                '<a class="btn btn-success btn-lg" type="button" onclick="scrambler(this,1)">'.
                    '<span class="glyphicon glyphicon-eye-open"></span></a>'.
                '<a class="btn btn-danger btn-lg" type="button" onclick="scrambler(this,2)">'.
                    '<span class="glyphicon glyphicon-eye-close"></span></a>'.
            '</span>'.
        '</div>'.
    '</div></div></div>';

echo '</div>';



require '../../__inc/_2footer.php';
