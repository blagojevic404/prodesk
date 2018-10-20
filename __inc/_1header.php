<?php
/**
 * This is common script which builds and displays HTML up to contents area, for every page.
 * Use $header_cfg array to pass configuration from page to this script:
 * - 'subscn'           string  specify navigation subsection, in order to mark subsection which is ACTIVE
 * - 'css'              array   add specified css file(s) to header
 * - 'style'            array   add <style> tag with specified css line(s) to header
 * - 'js'               array   add specified js file(s) to header
 * - 'js_onload'        array   add js functions to be called on page load
 * - 'js_onsubmit'      array   add js functions to be called on form submit
 * - 'js_onfinito       array   add js functions to be called before page unload
 * - 'js_lines'         array   add js lines to be added within <script> tag
 * - 'js_inc'           array   add specified file(s) (with js scripts) to header with REQUIRE statement
 * - 'nav_hide'         bool    do not display navigation
 * - 'ifrm'             bool    whether the page was called from an IFRAME
 * - 'ifrmtunel'        bool    whether the page was called from an IFRAME which is a *TUNEL-IFRAME*
 * - 'warney'           bool    display warney bar
 * - 'chnl_cbo'         bool    whether section submenu should contain channel combo
 * - 'chnl_referer'     bool    whether to use HTTP_REFERER instead of REQUEST_URI on channel change
 * - 'form_checker'     array   add form submit checker procedure
 * - 'autotab'          array   add autotab js
 * - 'bsgrid_typ'       string  BS grid defaults
 * - 'do_mdflog'        int     whether to turn-on mdflog js-ajax procedure
 * - 'ajax'             bool    include ajax js files
 * - 'mover'            bool    include mover files
 * - 'logz'             bool    include js for logz
 * - 'alerter_msgz'     array   text messages to be used in alerter
 * - 'ajax_uptodate'    bool    whether to add js-ajax uptodate check procedure (used in epg)
 * - 'calendar'         bool    whether we will show and use calendar bar
 * - 'backer_reload'    bool    whether to reload page when user gets to a page using the back button
 */


$cache_reset = '20180904';

if ($cache_reset) {
    $cache_reset = '?'.$cache_reset;
}


if (isset($header_cfg['ifrmtunel']) && $header_cfg['ifrmtunel']) {
    $header_cfg['ifrm'] = true;
}
if (isset($header_cfg['ifrm']) && $header_cfg['ifrm']) {
    $header_cfg['nav_hide'] = true;
    $footer_cfg['foot_hide'] = true;
}

if (isset($header_cfg['do_mdflog']) && $header_cfg['do_mdflog']) {

    $header_cfg['mdflog_id'] = mdflog();

    if ($header_cfg['mdflog_id']) {

        $header_cfg['ajax'] = true;

        $header_cfg['js_onload'][] =
            'setInterval(function(){ajaxer(\'GET\', \'/_ajax/_aj_mdflog.php\', \'id='.$header_cfg['mdflog_id'].'\', '.
            'null, null);}, '.($cfg['mdflog_ajax_interval']*1000).')';

        $header_cfg['js_onfinito'][] = 'ajaxer(\'GET\', \'/_ajax/_aj_mdflog.php\', \'del='.$header_cfg['mdflog_id'].'\', '.'null, null)';
    }
}

if (!empty($header_cfg['logz'])) {
    $header_cfg['ajax'] = true;
    $footer_cfg['js_lines'][] = logzer_output('js', $x);
}

if (isset($header_cfg['alerter_msgz']) && is_array($header_cfg['alerter_msgz'])) {
    foreach($header_cfg['alerter_msgz'] as $k => $v) {
        $header_cfg['js_lines'][]  = 'var g_alerter_'.$k.' = \''.$v.'\';'.PHP_EOL;
    }
    $footer_cfg['modal'][] = 'alerter';
}

if (!empty($header_cfg['mover'])) {
    require '../../__fn/fn_mover.php';
    $header_cfg['ajax'] = true;
    $header_cfg['css'][] = 'mover.css';
}

if (!empty($header_cfg['ajax'])) {
    $header_cfg['js'][] = 'ajax/ajaxer.js';
    $header_cfg['js'][] = 'ajax/ajax_success.js';
}

if (isset($header_cfg['form_checker'])) {
    $header_cfg['js_inc'][] = '/_js/form_checker.php';
    $header_cfg['js'][] = 'form_checker.js';
}

if (isset($header_cfg['autotab'])) {
    $header_cfg['js_inc'][] = '/_js/autotab/autotab.php';
}

if (isset($header_cfg['calendar'])) {
    $header_cfg['js_lines'][]  = '
        var datelead = \''.$header_cfg['calendar']['date_lead'].'\';
        var datenow = \''.$header_cfg['calendar']['date_last'].'\';
        var datecur = \''.$header_cfg['calendar']['date_sel'].'\';';
    $header_cfg['js'][] = '_list/calendar.js';
    $header_cfg['js_onload'][]  = 'redo_calendar(\''.($header_cfg['calendar']['typ']).'\', \''.
        $header_cfg['calendar']['date_submited'].'\')';
}

if (isset($header_cfg['bs_daterange'])) {
    $header_cfg['css'][] = '/_js/bs_daterange/daterangepicker-bs3.css';
    $header_cfg['js_inc'][] = '/_js/bs_daterange/bs_daterange.php';
    if (!empty($header_cfg['bs_daterange']['selector'])) {
        $header_cfg['js_onload'][] = 'bs_dater_selector(\''.$header_cfg['bs_daterange']['selector'].'\')';
    }
}



if (isset($header_cfg['bsgrid_typ'])) {

    // Dimensions array for BS column class

    switch ($header_cfg['bsgrid_typ']) {

        case 'regular': // for details page where all items are one-lined
            $dim_arr = [
                'lg' => ['panel_w' => 11, 'cln_l' => 3, 'cln_r' => 7],
                'md' => ['panel_w' => 12, 'cln_l' => 3, 'cln_r' => 9],
                'sm' => ['panel_w' => 12, 'cln_l' => 3, 'cln_r' => 9],
                'xs' => ['panel_w' => 12, 'cln_l' => 2, 'cln_r' => 10],
            ];
            break;

        case 'texter': // for details page which have multi-line items (textbox)
            $dim_arr = [
                'lg' => ['panel_w' => 11, 'cln_l' => 2, 'cln_r' => 9],
                'md' => ['panel_w' => 12, 'cln_l' => 2, 'cln_r' => 10],
                'sm' => ['panel_w' => 12, 'cln_l' => 2, 'cln_r' => 10],
                'xs' => ['panel_w' => 12, 'cln_l' => 2, 'cln_r' => 10],
            ];
            break;

        case 'ifrm_mdf':
            $dim_arr = [
                'lg' => ['panel_w' => 12, 'cln_l' => 2, 'cln_r' => 9],
                'md' => ['panel_w' => 12, 'cln_l' => 2, 'cln_r' => 9],
                'sm' => ['panel_w' => 12, 'cln_l' => 2, 'cln_r' => 10],
                'xs' => ['panel_w' => 12, 'cln_l' => 2, 'cln_r' => 10],
            ];
            break;

        case 'halfer':
            $dim_arr = [
                'lg' => ['panel_w' => 12, 'half' => 6],
                'md' => ['panel_w' => 12, 'half' => 6],
                'sm' => ['panel_w' => 12, 'half' => 12],
                'xs' => ['panel_w' => 12, 'half' => 12],
            ];
            break;

        case 'full-w':
            $dim_arr = [
                'xs' => ['panel_w' => 12, 'cln_l' => 3, 'cln_r' => 7],
            ];
            break;

        case 'epg-cg':
            $dim_arr = [
                'lg' => ['offset' => 3, 'cln_r' => 8],
                'md' => ['offset' => 3, 'cln_r' => 9],
                'sm' => ['offset' => 0, 'cln_r' => 0],
                'xs' => ['offset' => 0, 'cln_r' => 0, 'panel_w' => 12],
            ];
            break;

        case 'admin_ad':
            $dim_arr = [
                'lg' => ['panel_w' => 12, 'cln_l' => 2, 'cln_r' => 10],
                'xs' => ['panel_w' => 12, 'cln_l' => 3, 'cln_r' => 9],
            ];
            break;
    }

    // Turn dimensions into classname strings

    $bs_css = bs_grid_css($dim_arr); unset($dim_arr);
}

//echo '<pre>';print_r($bs_css);exit;



// TITLE TAG

$ttl = [];

if (!empty($header_cfg['subscn'])) {

    // We have to make the exception for scripts which use span glyph titles
    if ($header_cfg['subscn']=='trash') {
        $ttl[] = $tx['NAV'][208];
    } elseif ($header_cfg['subscn']=='setz') {
        $ttl[] = $tx['NAV'][205];
    } elseif ($header_cfg['subscn']=='index') {
        $ttl[] = $tx['NAV'][206];
    } else {
        $ttl[] = $nav_subz[$header_cfg['subscn']]['ttl'];
    }
}

if (SCTN=='admin') {
    $ttl[] = $tx['NAV'][209];
} elseif (SCTN!='ndx') {
    $ttl[] = $nav_sctnz[SCTN]['ttl'];
}

$ttl[] = $tx['NAV'][1];

$ttl = implode(' < ', $ttl);


header("Last-Modified: " . gmdate ('r')); // Right now!
header("Expires: 0");
header("Pragma: no-cache");
header("Cache-Control: no-cache");

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=$ttl?></title>

<link rel="apple-touch-icon" href="<?=$pathz['www_root']?>/apple-touch-icon.png">
<link rel="apple-touch-icon" sizes="120x120" href="<?=$pathz['www_root']?>/apple-touch-icon.png">
<link rel="apple-touch-icon-precomposed" href="<?=$pathz['www_root']?>/apple-touch-icon.png">
<link rel="apple-touch-icon-precomposed" sizes="120x120" href="<?=$pathz['www_root']?>/apple-touch-icon.png">
<link href="/_pkg/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<script src="/_pkg/jquery/jquery.min.js"></script>



<!-- Custom styles and js-->



<script>


    <?php if (!empty($header_cfg['backer_reload'])) {?>

    if (window.performance && window.performance.navigation.type == window.performance.navigation.TYPE_BACK_FORWARD) {
        location.reload();
    }

    <?php }?>


    var server_clock = new Date(<?=time().'000'?>);
    var local_clock = new Date();
    var clockdif = parseInt(server_clock - local_clock);

    <?php if (isset($show_epg_clock['epg_prg'])) :?>
    //var time_epg_start = new Date(2013, 7, 7, 19, 15, 0);
    //var time_epg_end	 = new Date(2013, 7, 7, 19, 30, 0);
    var time_epg_start 	= new Date(<?=$x['_JS_Start']?>);
    var time_epg_end	= new Date(<?=$x['_JS_End']?>);
    <?php endif;?>

    <?php
        if (isset($header_cfg['js_lines']) && is_array($header_cfg['js_lines'])) {
            echo implode(PHP_EOL, $header_cfg['js_lines']).PHP_EOL;
        }
    ?>


    window.onload = js_onload;

    function js_onload() {
        <?php
            // Do not call show_clock() inside IFRAMES
            if ((!isset($header_cfg['ifrm']) || $header_cfg['ifrm']==false)) {
                $header_cfg['js_onload'][] =
                    'show_clock('.((isset($show_epg_clock)) ? implode(',', $show_epg_clock) : '').')';
            }

            if (isset($header_cfg['js_onload']) && is_array($header_cfg['js_onload'])) {
                echo implode(';'.PHP_EOL, $header_cfg['js_onload']).';'.PHP_EOL;
            }
        ?>
    }

    <?php

        // MDF situation: All MODIFY pages contain *modify* in filename.
        if (strpos($_SERVER['PHP_SELF'],'modify')) {

            $pause_typ = (in_array($pathz['filename'], ['epg_modify_multi', 'stry_modify'])) ? 'long' : '';

            $header_cfg['js_onsubmit'][] = 'prevent_double_submit(\''.$pause_typ.'\')';

            unset($pause_typ);
        }

        if (isset($header_cfg['js_onsubmit']) && is_array($header_cfg['js_onsubmit'])) {
            echo 'function js_onsubmit() {'.PHP_EOL;
            echo implode(';'.PHP_EOL, $header_cfg['js_onsubmit']).';'.PHP_EOL;
            echo '}'.PHP_EOL;
        }
    ?>

    <?php
        if (isset($header_cfg['js_onfinito']) && is_array($header_cfg['js_onfinito'])) {
            echo 'window.onbeforeunload = function(e) {'.implode(';'.PHP_EOL, $header_cfg['js_onfinito']).';}';
        }
    ?>

</script>


<link href="/_css/nav-tweak.css<?=$cache_reset?>" rel="stylesheet">
<link href="/_css/common.css<?=$cache_reset?>" rel="stylesheet">
<script src="/_js/common.js<?=$cache_reset?>"></script>

<?php

echo '<link href="/_css/'.SCTN.'/common.css'.$cache_reset.'" rel="stylesheet">'.PHP_EOL;

if (isset($header_cfg['css']) && is_array($header_cfg['css'])) {
    foreach($header_cfg['css'] as $v) {
        echo '<link href="'.(($v[0]=='/') ? '' : '/_css/' ).$v.$cache_reset.'" rel="stylesheet">'.PHP_EOL;
    }
}

if (isset($header_cfg['js']) && is_array($header_cfg['js'])) {
    foreach($header_cfg['js'] as $v) {
        echo '<script src="'.(($v[0]=='/') ? '' : '/_js/' ).$v.$cache_reset.'"></script>'.PHP_EOL; // .'?z='.time()
    }
}

unset($cache_reset);

if (isset($header_cfg['js_inc']) && is_array($header_cfg['js_inc'])) {
    foreach($header_cfg['js_inc'] as $v) {
        require '..'.$v;
    }
}


if (isset($header_cfg['style']) && is_array($header_cfg['style'])) {
    echo PHP_EOL.'<style type="text/css">'.PHP_EOL;
    echo implode(PHP_EOL, $header_cfg['style']).PHP_EOL;
    echo '</style>'.PHP_EOL;
}

?>


</head>
<body>

<?php

if (!empty($header_cfg['ajax_uptodate'])) {
    $header_cfg['warney'] = true;
}
if (!empty($header_cfg['warney'])) {
    warney_bar();
}

if (@$cfg['BLACKOUT']) { // For scheduled maintenance, e.g. to make sure nobody changes db while backup in progress

    omg_put('warning',  sprintf($tx['MSG']['scheduled_blackout'], $cfg['BLACKOUT']));
    omg_get();

    log2file('srpriz', ['type' => 'blackout_notice']); // Check srpriz log to see which users were online during blackout
    exit;
}

?>


<?php
if (empty($header_cfg['nav_hide'])) :
?>
<nav id="z-navbar-top" class="navbar navbar-default navbar-static-top navbar-sm" role="navigation">
    <div class="container">

        <p class="navbar-text"><?php

            echo $tx['DAYS']['wdays'][intval(date('N'))].', '.date('d').' '.$tx['DAYS']['months'][intval(date('n'))].
                ', <span id="nowtime"></span>'.
                ' <span class="bumper glyphicon glyphicon-option-vertical"></span> '.
                $tx['LBL']['displayed'].' '.date("H:i:s");

            if (!empty($header_cfg['ajax_uptodate'])) {

                if (@$_SESSION['ajax_uptodate']) {
                    echo ' &mdash; <span id="ajax_uptodate">'.date('H:i:s').'</span>';
                }

                $href = '?typ='.TYP.'&id='.intval($_GET['id']).'&view='.VIEW_TYP
                    .'&ajax_uptodate='.((@$_SESSION['ajax_uptodate']) ? 0 : 1);

                echo ' <a title="'.$tx[SCTN]['LBL']['do_ajax_uptodate'].'" href="'.$href.'">'.
                    '<span class="glyphicon glyphicon-refresh"></span></a>';
            }

            ?></p>

        <ul class="nav navbar-nav navbar-right">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?=uid2name(UZID)?> <span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="/hrm/org_details.php?id=<?=UZGRP?>"><?=$tx['NAV'][35]?></a></li>
                    <li><a href="/hrm/uzr_details.php?id=<?=UZID?>"><?=$tx['NAV'][36]?></a></li>
                    <li><a href="/start/settings.php"><?=$tx['NAV'][205]?></a></li>
                </ul>
            </li>
            <li class="logout">
                <a href="/start/index.php?logout" class="text-uppercase"><span class="glyphicon glyphicon-log-out"></span>
                    <?=$tx['NAV'][207]?></a>
            </li>
        </ul>

    </div>
</nav>

<?php

    //omg_put('danger', 'test');

    omg_get();
?>

<nav id="z-navbar-mdl" class="navbar navbar-inverse navbar-static-top" role="navigation" style="z-index:99">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#z-navbar-mdl-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <?='<a class="navbar-brand text-uppercase'.((SCTN=='ndx') ? ' active' : '').'" '.
                'href="/'.$nav_sctnz['ndx']['dir'].'/'.$nav_sctnz['ndx']['url'].'">'.
                '<span class="glyphicon glyphicon-stop logo"></span>'.
                $nav_sctnz['ndx']['ttl'].'</a>'?>
        </div>
        <div class="collapse navbar-collapse" id="z-navbar-mdl-collapse">
            <ul class="nav navbar-nav">
                <?php
                foreach($nav_sctnz as $k => $v) {
                    if ($k=='ndx') continue;
                    echo '<li'.(($k==SCTN) ? ' class="active"' : '').'>'.
                             '<a href="/'.( $v['dir'] ? $v['dir'].'/' : '' ).$v['url'].'">'.$v['ttl'].'</a>'.
                         '</li>';
                }
                ?>
            </ul>

            <ul class="nav navbar-nav navbar-right">
                <li>
                    <a href="http://manual.<?=$_SERVER["SERVER_NAME"]?>" target="_blank" class="text-uppercase"
                       style="padding-bottom:0;">
                        <span class="glyphicon glyphicon-question-sign" style="font-size:125%"></span>
                    </a>
                </li>
            </ul>

        </div>
    </div>
</nav>

<div id="z-navbar-btm">
    <div class="container">
        <nav role="navigation">
            <?php

            if (isset($header_cfg['chnl_cbo']) && $header_cfg['chnl_cbo']==true) {

                echo
                    '<form class="navbar-form pull-right" role="form" method="post" action="?" name="form_channel">'.
                      '<div class="form-group">'.
                        '<select class="form-control" onChange="submit()" name="postCHNL">'.channelz_cbo().'</select>'.
                        ((@$header_cfg['chnl_referer']) ? '<input type="hidden" name="chnl_referer" value="1">' : '').
                      '</div>'.
                    '</form>';
            }


            foreach($nav_subz as $k => $v) {

                $css_tmp = [];
                if ($k==@$header_cfg['subscn']) $css_tmp[] = 'active';
                if (@$v['rgt']) $css_tmp[] = 'pull-right';
                if (!$v['url']) $css_tmp[] = 'disabled';

                echo '<a class="'.implode(' ', $css_tmp).'" href="'.$v['url'].'">'.$v['ttl'].'</a>';
                if (@$v['div']) echo '<span class="divider">|</span>';
            }

            ?>
        </nav>
    </div>
</div>
<?php
endif; //(empty($header_cfg['nav_hide']))


if (isset($header_cfg['ifrmtunel']) && $header_cfg['ifrmtunel']) {

    echo '
    <div class="content ifrm-tunel">
        <div class="container-fluid">
    ';

} else {

    echo '
    <div class="content not-tunel">
        <div class="container">
    ';
}

