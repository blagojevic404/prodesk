<?php
require '../../__ssn/ssn_boot.php';

pms('admin', null, null, true);


$lngz = txarr('arrays','languages');


$fnames = ['labels', 'arrays', 'messages', 'common.days', 'common.nav', 'login'];
define('FNAME', wash('arr_assoc', @$_GET['p'], $fnames, 'labels'));




if ($_SERVER['REQUEST_METHOD']=='POST' && pms('admin', 'lng')) {
    lng_write();
}

$txt = lng_txt();


if (@$_GET['yu-conv']) {
    foreach ($txt[2] as $k => $v) {
        $txt[2][$k] = text_convert($txt[1][$k], 'cyr', 'lat');
    }
}




/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'lng';

/*CSS*/
$header_cfg['css'][] = 'admin/lng.css';
$header_cfg['css'][] = 'floater.css';

/*JS*/
if (pms('admin', 'lng')) {
    $header_cfg['js'][] = 'klikle.js';
    $header_cfg['js'][] = 'admin/lng.js';
    $header_cfg['js_onload'][] = 'lngedit_page_eventz()';
}

require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', $tx['NAV'][1]);
crumbs_output('item', $tx['NAV'][209], 'report.php');
crumbs_output('item', $nav_subz[$header_cfg['subscn']]['ttl']);
crumbs_output('item', '<span class="glyphicon glyphicon-ok"></span>',
    (pms('admin', 'lng') ? 'javascript:klk_submit(\'lng\')' : null),
    'pull-right righty');
crumbs_output('close');



echo '<ul class="row nav nav-tabs" style="margin-bottom:20px;">';

foreach ($fnames as $v) {
    echo '<li'.(($v==FNAME) ? ' class="active"' : '').'><a href="?p='.$v.'&t='.time().'">'.$v.'</a></li>';
}

echo '<li class="disabled"><a href="#">blocks</a></li>';
echo '</ul>';




// FORM start
form_tag_output('open', '');


echo '<div class="row">';
echo '<table class="table table-condensed lng klikle"><tbody>';

lng_table('header');

lng_table('normal');

lng_table('bottom');

echo '</tbody></table>';
echo '</div>';


// FORM close
form_tag_output('close');

lng_table('cloner');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';







function lng_write() {

    global $lngz, $pathz;


    $txt = lng_txt('post');

    $post = lng_post();

    foreach ($lngz as $lng_k => $lng_v) {

        if ($txt[$lng_k]!=$post[$lng_k]) {

            $new_txt = implode(PHP_EOL, $post[$lng_k]);

            $fpath = $pathz['rel_rootpath'].'../_txt/'.$lng_k.'/'.FNAME.'.txt';

            file_put_contents($fpath, $new_txt);
        }
    }

    hop($_SERVER['HTTP_REFERER']);
}






function lng_post() {

    global $lngz;


    foreach ($_POST as $k => $v) {

        $i = explode('_', $k);

        if ($i[2]=='1') {

            $n = strpos($v, '//');      // (// - comment sign)
            if ($n) {
                $note = substr($v, $n+2);
                $v = trim(substr($v, 0, $n));
            } else {
                $note = '';
            }

            foreach ($lngz as $lng_k => $lng_v) {

                $r[$lng_k][($i[1]-1)] = $v;
            }

        } else {

            if (!$v) {
                continue;
            }

            if ($note) {
                $v .= ' //'.$note;
            }

            $r[($i[2]-1)][($i[1]-1)] .= ' '.$v;
        }
    }

    return $r;
}





function lng_txt($typ='normal') {

    global $lngz, $pathz;


    foreach ($lngz as $lng_k => $lng_v) {

        $fpath = $pathz['rel_rootpath'].'../_txt/'.$lng_k.'/'.FNAME.'.txt';

        $lines = @file($fpath);

        // Trim lines
        if ($lines) {
            foreach ($lines as $k => $v) {
                $lines[$k] = rtrim($v);
            }
        }

        if ($typ=='post') {

            $txt[$lng_k] = $lines;
            continue;
        }


        $r = [];

        foreach ($lines as $line) {

            $x = [];

            // Skip MDF for lines which begin with specific characters ('/' is for the comments, '[' is for the section tags)

            if (!$line) {

                $r[] = ['key' => ''];

            } elseif (in_array($line[0], ['/', '#', '['])) {

                $r[] = ['key' => '', 'val' => $line];

            } else {

                $x['key']  = strstr($line, ' ', true);             // name: the part before the needle (spacer)
                $x['val'] = trim(strstr($line, ' ', false));     // value: the part after the needle (spacer)

                if ($x['key']=='') {
                    $x['key'] = $line;
                }

                // Clean comments (separator is '//') from lines
                $n = strpos($x['val'], '//');                    // value: the part before the needle (// - comment sign)
                if ($n) {
                    $x['note'] = substr($x['val'], $n+2);
                    $x['val'] = trim(substr($x['val'], 0, $n));
                }

                $r[] = $x;
            }
        }

        $txt[$lng_k] = $r;
    }


    return $txt;
}



function lng_table($typ='normal') {

    global $txt, $lngz;

    $lngz_cnt = count($lngz);


    $btn_squiz =
        '<div class="floater-outside"><div class="floater-inside left">'.
        '<a href="#" class="text-muted squiz" onclick="lngedit_insert(this); return false;">'.
        '<span class="glyphicon glyphicon-circle-arrow-right"></span></a>'.
        '</div></div>';

    $btn_mdfdel =
        '<div class="floater-outside"><div class="floater-inside right">'.
        '<a class="text-muted" href="#" onclick="lngedit_delete(this); return false;">'.
        '<span class="glyphicon glyphicon-remove"></span></a>'.
        '</div></div>';


    switch ($typ) {


        case 'normal':

            foreach ($txt[1] as $k => $v) {

                echo '<tr name="tr">';

                echo '<td class="ghost"><span class="linenumber">'.sprintf('%03d', $k+1).'</span>'.$btn_squiz.'</td>';

                if ($v['key']=='') {

                    echo '<td name="klk" class="klk bg-warning" colspan="'.($lngz_cnt+1).'">'.
                        ((@$v['val']) ? $v['val'] : '').'</td>';

                } else {

                    echo '<td name="klk" class="klk bg-info">'.
                        $v['key'].((@$v['note']) ? ' //'.$v['note'] : '').'</td>';

                    $empty_by_intent = false;

                    foreach ($lngz as $lng_k => $lng_v) {

                        if (empty($txt[$lng_k][$k]['val'])) {

                            if ($lng_k==1) {
                                $empty_by_intent = true;
                            }

                            $css = (!$empty_by_intent) ? 'empty_err' : 'empty_ok';

                        } else {

                            $css = 'bg-success';
                        }

                        echo '<td name="klk" class="klk '.$css.'">'.@$txt[$lng_k][$k]['val'].'</td>';
                    }
                }

                echo '<td class="ghost">'.$btn_mdfdel.'</td>';

                echo '</tr>';
            }

            break;


        case 'bottom':

            echo '<tr name="tr">'.
                '<td class="ghost">'.$btn_squiz.'</td>'.
                '<td colspan="'.($lngz_cnt+1).'" style="border-bottom:none"></td>'.
                '<td class="ghost"></td>'.
                '</tr>';

            break;


        case 'header':

            echo '<tr class="bg-primary"><th class="ghost"></th><th>&nbsp;</th>';

            foreach ($lngz as $v) {
                echo '<th>'.$v.'</th>';
            }

            echo '<th class="ghost"></th></tr>';

            break;


        case 'cloner':

            echo '<table id="cloner" style="display:none">';

            echo '<tr name="tr" class="bg-danger">';

            echo '<td class="ghost">'.$btn_squiz.'</td>';

            echo '<td name="klk" class="klk"></td>';

            foreach ($lngz as $lng_k => $lng_v) {

                echo '<td name="klk" class="klk"></td>';
            }

            echo '<td class="ghost">'.$btn_mdfdel.'</td>';

            echo '</tr>';

            echo '</table>';

            break;
    }

}

