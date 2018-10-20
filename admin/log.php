<?php
require '../../__ssn/ssn_boot.php';

pms('admin', null, null, true);



$fnames = scandir(LOGDIR);

foreach ($fnames as $k => $v) {

    if ($v[0]=='.') {
        unset($fnames[$k]);
    } else {
        $fnames[$k] = substr($v, 0, -4);
    }
}

$fnames = array_values($fnames);

define('FNAME', wash('arr_assoc', @$_GET['p'], $fnames, 'prodesk_error'));
define('FPATH', LOGDIR.FNAME.'.log');



if (pms('admin', 'maestro')) {

    if (!empty($_GET['empt'])) {

        file_put_contents(FPATH, '');

        hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?p='.FNAME);
    }

    if (!empty($_GET['downer'])) {

        // Note: If the file is larger than the allowed memory size (~134MB), then you will get an PHP error
        // In that case, you better try opening it via ssh..

        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="'.basename(FPATH).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: '.filesize(FPATH));
        readfile(FPATH);
        exit;
    }
}



/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'log';

$header_cfg['js'][] = 'admin/common.js';

require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', $tx['NAV'][1]);
crumbs_output('item', $tx['NAV'][209], 'report.php');
crumbs_output('item', $nav_subz[$header_cfg['subscn']]['ttl']);

if (pms('admin', 'maestro')) {

    crumbs_output('item', '<span class="glyphicon glyphicon-remove"></span>',
        '?p='.@$_GET['p'].'&empt=1', 'pull-right righty');

    crumbs_output('item', '<span class="glyphicon glyphicon-save"></span>',
        '?p='.@$_GET['p'].'&downer=1', 'pull-right righty');

    crumbs_output('item', '<span class="glyphicon glyphicon-align-left"></span>',
        'javascript:nowrap_switch()', 'pull-right righty');
}

crumbs_output('close');



echo '<ul class="row nav nav-tabs" style="margin-bottom:20px;">';

foreach ($fnames as $v) {
    echo '<li'.(($v==FNAME) ? ' class="active"' : '').'><a href="?p='.$v.'">'.$v.'</a></li>';
}

echo '</ul>';



$fsize = filesize(FPATH);
$fsize_human = human_filesize($fsize);

echo '<div class="log fsize">'.$fsize_human.'</div>';



if ($fsize > 1048576) { // >1MB

    echo '<h3>'.sprintf($tx[SCTN]['MSG']['err_fsize_large'], $fsize_human).'</h3>';

} else {

    echo '<pre class="row log nowrap">';


    if (FNAME=='prodesk_error') {

        $lines = array_reverse(file(FPATH));

        foreach ($lines as $k => $v) {
            echo str_replace('] PHP', ']'.PHP_EOL.'PHP', $v).PHP_EOL;
        }

    } elseif (FNAME=='mysql') {

        readfile(FPATH);

    } else {

        echo implode('', array_reverse(file(FPATH)));
    }


    echo '</pre>';
}


if (FNAME=='t_exec') {
    echo '<div class="log">log_exec_time_limit='.$cfg['log_exec_time_limit'].'s</div>';
}



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';



