<?php
require '../../__ssn/ssn_boot.php';


if (isset($_GET['logout'])) {

    $sql = 'INSERT INTO log_in_out (UserID, ActionID, Time, IP) VALUES ('.UZID.', 2, now(), \''.$_SERVER['REMOTE_ADDR'].'\')';
    qry($sql, LOGSKIP);

    unset($_SESSION['UserID']);
    session_destroy();

    hop($pathz['www_root'].$_SERVER['SCRIPT_NAME']);
}



if (!isset($_SESSION['BROWSER_TYPE'])) {

    $_SESSION['BROWSER_TYPE'] = is_chromium_good();

    /*
    if ($_SESSION['BROWSER_TYPE']=='PRINT_1') {

        omg_put('info', $tx[SCTN]['MSG']['old_chromium']);

    } elseif ($_SESSION['BROWSER_TYPE']=='NOT_CHROME') {

        $footer_cfg['modal'][] = 'alerter';

        $header_cfg['js_onload'][]  = 'alerter("'.$tx[SCTN]['MSG']['not_chromium'].'")';

        omg_put('danger', $tx[SCTN]['MSG']['not_chromium']);
    }
    */
}



/*************************** HEADER ****************************/
$header_cfg['subscn'] = 'index';
$header_cfg['chnl_cbo'] = true;

require '../../__inc/_1header.php';
/***************************************************************/

echo '<div class="row"><div class="col-xs-12">';




$channelz = channelz();


foreach($nav_sctnz as $name => $sctn) {

    if ($name=='ndx') continue;

    $nav_subz[$name] = get_nav_subz($name);

    foreach($nav_subz[$name] as $k => $v) {
        $nav_subz[$name][$k]['dir'] = $sctn['dir'];
    }
}

$nav_subz['epg']['real']['ttl'] = '<small>'.$tx['LBL']['epg'].'</small><br>'.$nav_subz['epg']['real']['ttl'];
$nav_subz['epg']['plan']['ttl'] = '<small>'.$tx['LBL']['epg'].'</small><br>'.$nav_subz['epg']['plan']['ttl'];


$shortz_arr[1] = [
    $nav_subz['epg']['real'],
    $nav_subz['epg']['plan'],
    $nav_subz['dsk']['prgm'],
    $nav_subz['dsk']['stry'],
    $nav_subz['dsk']['flw'],
    $nav_subz['epg']['film'],
    $nav_subz['epg']['mkt'],
    $nav_subz['epg']['prm'],
];
$shortz_arr[2] = [
    $nav_subz['epg']['real'],
    $nav_subz['epg']['plan'],
    $nav_subz['dsk']['prgm'],
    $nav_subz['dsk']['stry'],
];
$shortz_arr[4] = [
    $nav_subz['epg']['real'],
    $nav_subz['epg']['plan'],
    $nav_subz['dsk']['prgm'],
    $nav_subz['dsk']['stry'],
];


echo '<table id="ndxshortz">';


foreach($shortz_arr as $chnl => $shortz) {

    echo '<tr>';

    echo '<td class="channel">'.
            '<a href="?getCHNL='.$chnl.'" type="button" class="btn btn-default btn-block'.((CHNL==$chnl) ? ' active' : '').'">'.
                '<span>'.$channelz[$chnl].'</span></a>'.
        '</td>';

    foreach($shortz as $v) {

        $href = '/'.$v['dir'].'/'.$v['url'].(strpos($v['url'], '?') ? '&' : '?').'getCHNL='.$chnl;

        echo '<td><a href="'.$href.'" type="button" class="btn btn-primary btn-block">'.
            '<span>'.$v['ttl'].'</span></a></td>';
    }

    echo '</tr>';
}


echo '<tr class="other">';

echo '<td><a href="http://manual.'.$_SERVER["SERVER_NAME"].'" target="_blank" type="button" '.
    'class="btn btn-info btn-block text-uppercase"><span>'.$tx['NAV'][204].'</span></a></td>';

if (UZID==UZID_ALFA) {
    echo '<td><a href="/admin/log.php" type="button" class="btn btn-info btn-block text-uppercase"><span>'.$tx['NAV'][82].
        '</span></a></td>';
}

echo '</tr>';


echo '</table>';






echo '</div></div>';


//echo ini_get("session.gc_maxlifetime");


require '../../__inc/_2footer.php';