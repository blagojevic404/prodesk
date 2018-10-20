<?php
require '../../__ssn/ssn_boot.php';

pms('admin', 'spec', null, true);


$blocks['janitor'] = [
    'lines' => ['db_arhiver', 'jnt_epgxml', 'jnt_stry_versions', 'jnt_stry_trash', 'jnt_log_sql', 'jnt_log_mdf', 'jnt_log_inout'],
    'title' => $tx[SCTN]['LBL']['janitor'],
];



/*************************** HEADER ****************************/
$header_cfg['subscn'] = 'setz';

$header_cfg['ajax'] = true;
$header_cfg['js'][]  = 'ajax/editdivable.js';

require '../../__inc/_1header.php';
/***************************************************************/


// APP CRUMBS
crumbs_output('open');
crumbs_output('item', $tx['NAV'][1]);
crumbs_output('item', $tx['NAV'][209], 'report.php');
crumbs_output('item', $tx['NAV'][205]);
crumbs_output('close');



form_accordion_output('head', $tx[SCTN]['LBL']['scheduler'], 'scheduler');
echo '<p><a href="/_cron/epgxml.php" target="_blank">epgxml.php</a> &mdash; EPG ProDesk -> ProWeb</p><br>';
echo '<p><a href="/_cron/janitor.php" target="_blank">janitor.php</a> &mdash; '. $tx[SCTN]['LBL']['janitor'].'</p>';
echo '<p><a href="/_cron/epgauto.php" target="_blank">epgauto.php</a> &mdash; EPG auto copy</p>';
form_accordion_output('foot');



admin_cfg_block($blocks);


require '../../__inc/_2footer.php';
