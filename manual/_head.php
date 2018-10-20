<?php

define('APP_DOMAIN', str_replace('manual.', '', $_SERVER["SERVER_NAME"]));

define('IS_DEV', ((APP_DOMAIN=='prodesk.ura') ? true : false));

define('SCTN', basename($_SERVER['PHP_SELF'], '.php'));

require '_titles.php';

$nav = get_nav_subz(SCTN);



if (SCTN=='video') {
    $video_id = (empty($_GET['v'])) ? '' : preg_replace("/[^a-zA-Z0-9_-]+/", "", $_GET['v']);
}

define('VIDEO_PLAYER', (SCTN=='video' && $video_id));


function print_nav_main($manz) {

    foreach ($manz as $k => $v) {

        if ($k==SCTN) {
            $css = 'active';
        } elseif (isset($v['disable'])) {
            $css = 'disabled';
        } else {
            $css = '';
        }

        echo
            '<li'.($css ? ' class="'.$css.'"' : '').'>'.
                '<a href="'.$k.'.php">'.((isset($v['icon'])) ? $v['icon'] : $v['cpt']).'</a>'.
            '</li>'.PHP_EOL;
    }
}


function print_nav_sctn($sctn, $nav=null) {

    if (!$nav) {
        $nav = get_nav_subz($sctn);
    }

    $filename = ((SCTN!=$sctn) ? $sctn.'.php' : '');

    foreach ($nav as $sct_k => $sct_v) {
        echo '<li><a href="'.$filename.'#sct_'.$sct_k.'" class="section">'.$sct_v['sct'].'</a>';
        if (isset($sct_v['sub'])) {
            echo '<ul class="nav">';
            foreach ($sct_v['sub'] as $sub_k => $sub_v) {
                echo '<li><a href="'.$filename.'#sct_'.$sct_k.'_'.$sub_k.'">'.$sub_v.'</a></li>';
            }
            echo '</ul>';
        }
        echo '</li>';
    }
}




$g_sct = null;

function sct_start($sct) {

    global $nav, $g_sct;

    $g_sct = $sct;

    return '<div class="docs-section"><h1 id="sct_'.$sct.'">'.$nav[$sct]['sct'].'</h1>';
}

function sct_sub() {

    global $nav, $g_sct;

    static $n;

    if (empty($n[$g_sct])) $n[$g_sct] = 0;

    $r = '<h2 id="sct_'.$g_sct.'_'.$n[$g_sct].'">'.$nav[$g_sct]['sub'][$n[$g_sct]].'</h2>';

    $n[$g_sct]++;

    return $r;
}

function sct_end() {

    return '</div>';
}



?>
<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=$title_app.': '.$manz[SCTN]['cpt']?></title>

    <link href="/_pkg/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/_pkg/jquery/jquery.min.js"></script>
    <script src="/_pkg/bootstrap/js/bootstrap.min.js"></script>

    <link href="css_js/manual.css" rel="stylesheet">
    <link href="css_js/bs_callout.css" rel="stylesheet">

    <script src="css_js/onload.js"></script>
    <script>window.onload = js_onload;</script>

</head>
<body <?=((VIDEO_PLAYER) ? 'class="video"' : '')?>>


<nav class="navbar navbar-default <?=((VIDEO_PLAYER) ? '' : 'navbar-fixed-top')?>" role="banner">
    <div class="container">
        <div class="navbar-header pull-right">
            <button class="navbar-toggle" type="button" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a href="http://<?=APP_DOMAIN?>" class="navbar-brand text-uppercase">
                <span class="glyphicon glyphicon-share-alt" style="font-size:80%"></span>
                <?=$title_app?>
            </a>
        </div>
        <nav class="collapse navbar-collapse" role="navigation">
            <ul class="nav navbar-nav"><?php print_nav_main($manz);?></ul>
        </nav>
    </div>
</nav>

<?php

if (!VIDEO_PLAYER):

    if (SCTN=='video') {

        $title_instr = $manz[SCTN]['cpt'].' &ndash; '.$title_instr;

    } elseif (SCTN!='index') {

        $title_instr .= ': '.$manz[SCTN]['cpt'];
    }

?>

<div id="masthead">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <h1 class="title"><?=$title_app?>
                    <p class="lead text-uppercase"><?=$title_instr?></p>
                </h1>
            </div>
            <div class="col-md-5">
                <div class="logo noprint">
                    <img src="<?=$img_logo?>" class="img-responsive">
                </div>
            </div>
        </div>
    </div>
</div>


<div class="container">
    <div class="row">

<?php
endif;



if (SCTN=='index') {


    echo '<div class="col-md-12 contents">';

    $cnt = 0;

    foreach ($manz as $man_k => $man_v) {

        if ($man_k=='index' || $man_k=='video' || !empty($man_v['disable'])) {
            continue;
        }
        echo '<div class="col-md-3">';

        echo '<h3><a href="'.$man_k.'.php">'.$man_v['cpt'].'</a></h3>';

        echo '<ul class="nav nav-stacked contents">';
        print_nav_sctn($man_k);
        echo '</ul>';

        echo '</div>';

        $cnt++;

        if ($cnt==4) {

            echo '</div><div class="col-md-12 contents">';

            $cnt = 0;
        }
    }


} elseif (SCTN=='video') {


    if (VIDEO_PLAYER) {

        echo
            '<div class="text-center">'.
            '<iframe width="1280" height="720" src="https://www.youtube.com/embed/'.$video_id.'?vq=hd720"'.
            ' frameborder="0" allowfullscreen></iframe>'.
            '</div>';

        echo '<div class="video_note text-right">* '.$video_note.'</div>';
    }


    echo '<div class="video_contents'.((VIDEO_PLAYER) ? ' player' : '').'">';

    echo '<div class="container"><div class="row">';


    foreach ($nav as $sct_v) {

        echo '<div class="col-md-3">';

        echo '<h3>'.$sct_v['sct'].'</h3>';

        if (isset($sct_v['sub'])) {

            echo '<ul class="nav nav-stacked contents">';

            echo '<ul class="nav">';

            foreach ($sct_v['sub'] as $sub_v) {

                echo '<li><a href="?v='.$sub_v[1].'">'.$sub_v[0].'</a></li>';
            }

            echo '</ul>';
            echo '</ul>';
        }

        echo '</div>';
    }


    echo '</div></div>';

    echo '</div>';


} else { // normal section (any except *video*)


    echo '<div class="col-md-3" id="leftCol">';

    echo '<ul class="nav nav-stacked" id="sidebar">';
    print_nav_sctn(SCTN, $nav);
    echo '</ul>';

    echo '</div>';

    echo '<div class="col-md-9" id="content">';
}


