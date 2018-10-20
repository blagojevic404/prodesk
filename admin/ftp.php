<?php
require '../../__ssn/ssn_boot.php';

pms('admin', 'spec', null, true);


if (!isset($_SESSION['ftp_limit'])) {
    $_SESSION['ftp_limit'] = 1;
}

if (isset($_GET['limit'])) {

    $_SESSION['ftp_limit'] = 1 - ($_SESSION['ftp_limit'] ? 1 : 0);

    hop($_SERVER['PHP_SELF']); // Reload the requesting page
}


$rootdir = (!$cfg[SCTN]['ftp_wwwdir_limit'] || !$_SESSION['ftp_limit']) ? '/' : dirname(getcwd(), 2);
// Whether to limit ftp only to www root directory, or allow browsing entire server dir structure.


if (!empty($_GET['p'])) {

    $curdir = wash('cpt', $_GET['p']);

    if ($curdir=='/') {
        $curdir = '';
    }

    $curdir = $rootdir.$curdir;

    if (!is_dir($curdir) && !is_file($curdir)){
        unset($curdir);
    }
}

if (empty($curdir)) {
    $curdir = $rootdir;
}


$curdir_rel = substr($curdir, strlen($rootdir));


$subdirs = explode('/', $curdir_rel);
$subdirs[0] = '(root)';





/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'ftp';

require '../../__inc/_1header.php';
/***************************************************************/



// APP CRUMBS
crumbs_output('open');
crumbs_output('item', $tx['NAV'][1]);
crumbs_output('item', $tx['NAV'][209], 'report.php');
crumbs_output('item', $nav_subz[$header_cfg['subscn']]['ttl'], '?limit=1', 'ftp_limit_sw');
crumbs_output('close');




// DIR CRUMBS
crumbs_output('open');
foreach ($subdirs as $k => $v) {
    $href = '?p=/'.implode('/', array_slice($subdirs, 1, $k));
    crumbs_output('item', $v, $href);
}
crumbs_output('close');




if (is_file($curdir)) { // Display file contents


    echo '<div class="row code">';

    echo '<div class="head col-xs-12">'.
            '<span class="glyphicon glyphicon-file"></span>'.end($subdirs).
         '</div>';

    echo '<div class="col-xs-12">';

    highlight_file($curdir);

    echo '</div>';

    echo '</div>';


} else { // Display FTP tree


    $scan = scandir($curdir);

    // We have to move these to the front, because names starting with "+" would be sorted before them.
    $r['dir'][] = '.';
    $r['dir'][] = '..';

    foreach ($scan as $v) {

        if (in_array($v, ['.', '..'])) {
            continue;
        }

        if (is_dir($curdir.'/'.$v)) {
            $r['dir'][] = $v;
        } else {
            $r['file'][] = $v;
        }
    }

    $cnt_dir = (!empty($r['dir'])) ? count($r['dir'])-2 : 0;
    $cnt_file = (!empty($r['file'])) ? count($r['file']) : 0;

    echo '<div class="row ftp">';

    echo
        '<div class="head col-xs-12">'.
            '<span class="glyphicon glyphicon-folder-close"></span>'.end($subdirs).
            '<span class="cnt">'.
                (($cnt_dir) ? $cnt_dir.'<span class="glyphicon glyphicon-folder-close"></span>' : '').
                (($cnt_file) ? $cnt_file.'<span class="glyphicon glyphicon-file"></span>' : '').
            '</span>'.
        '</div>';


    foreach ($r as $typ => $item) {

        foreach ($item as $k => $v) {

            echo '<div class="col-xs-12 row">';

            $href = $curdir_rel.'/'.$v;
            $css = '';
            $html = $v;

            if ($typ=='dir') {

                if ($v=='.') {

                    $html = '<span class="glyphicon glyphicon-step-backward"></span>'.$html;
                    $href = '';
                    $css = ($curdir==$rootdir) ? 'disabled' : '';

                } elseif ($v=='..') {

                    $html = '<span class="glyphicon glyphicon-triangle-left"></span>'.$html;
                    $href = substr(dirname($curdir), strlen($rootdir));
                    $css = ($curdir==$rootdir) ? 'disabled' : '';

                } else {

                    $html = '<span class="glyphicon glyphicon-folder-close"></span>'.$html;
                }
            }

            echo '<div class="col-xs-3 name"><a href="?p='.$href.'" class="'.$css.'">'.$html.'</a></div>';

            if ($typ=='file') { // FILE

                $f_path = $curdir.'/'.$v;

                $f_size = filesize($f_path).' B';

            } elseif (!in_array($v, ['.', '..'])) { // DIR: exclude '.', '..'

                $f_path = $rootdir.$curdir_rel.'/'.$v;

                $f_size = '';
            }

            if (isset($f_path)) {

                $f_perms = ftp_perms_prettify(fileperms($f_path));

                $a_owner = posix_getpwuid(fileowner($f_path));
                $f_owner = $a_owner['name'].' ('.$a_owner['uid'].')';

                $a_group = posix_getgrgid(filegroup($f_path));
                $f_group = $a_owner['name'].' ('.$a_owner['uid'].')';

                $f_modif = date('Y-m-d H:i:s', filemtime($f_path));

                echo '<div class="col-xs-2 text-right">'.$f_size.'</div>';

                echo '<div class="col-xs-2">'.$f_modif.'</div>';

                echo '<div class="col-xs-2">'.$f_perms.'</div>';

                echo '<div class="col-xs-3">'.$f_owner.(($f_owner!=$f_group) ? ' / '.$f_group : '').'</div>';
            }

            echo '</div>';
        }
    }

    echo '</div>';
}




/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
