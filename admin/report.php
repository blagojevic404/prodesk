<?php
require '../../__ssn/ssn_boot.php';


pms('admin', null, null, true);



/*************************** HEADER ****************************/
$header_cfg['subscn'] = 'report';

require '../../__inc/_1header.php';
/***************************************************************/



echo '<div class="row"><div class="col-xs-12">';


echo '<h1>'.$tx['NAV'][1].' '.$cfg['app_version'].'</h1>';
echo '<h4>('.$cfg['app_vdate'].')</h4>';
echo '<h4>&nbsp;</h4>';

echo '<h4>PHP: '.PHP_VERSION.'</h4>';
echo '<h4>MySQL: '.mysqli_get_server_info($GLOBALS["db"]).'</h4>';
echo '<h4>&nbsp;</h4>';

echo '<h5>'.$tx[SCTN]['LBL']['cnt_users_active'].': '.cnt_sql('hrm_users', 'IsActive=1').'</h5>';
echo '<h5>'.$tx[SCTN]['LBL']['cnt_users_login'].': '.
    cnt_sql('log_in_out', 'Time between subdate(CURDATE(), 1) and CURDATE()', 'UserID').'/'.
    cnt_sql('log_in_out', 'Time between subdate(CURDATE(), 7) and CURDATE()', 'UserID').'/'.
    cnt_sql('log_in_out', 'Time between subdate(CURDATE(), 30) and CURDATE()', 'UserID').
    '</h5>';


echo '</div></div>';



require '../../__inc/_2footer.php';
