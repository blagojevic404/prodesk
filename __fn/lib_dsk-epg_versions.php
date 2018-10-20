<?php

// stry versions







/**
 * Story versions logger
 *
 * @param int $id Story ID
 * @param array $cur_atomz Story ATOMZ array
 * @return void
 */
function stry_versions_put($id, $cur_atomz) {

    $mdf_atomz = atomz_reader($id);

    $cur_texter = stry_atomz_texter($cur_atomz);

    $mdf_texter = stry_atomz_texter($mdf_atomz);

    if ($mdf_texter!=$cur_texter) {

        receiver_ins('stry_versions', ['StoryID' => $id, 'Texter' => $mdf_texter, 'UID' => UZID, 'TermMod' => TIMENOW], LOGSKIP);
    }
}


/**
 * Get combined text of all story atoms (CAM atoms only), to be used in story versions logging
 *
 * @param array $atomz Story ATOMZ array
 * @return string $texter Combined text of all story atoms
 */
function stry_atomz_texter($atomz) {

    $texter = [];

    if ($atomz) {

        foreach ($atomz as $v) {
            if ($v['Texter']) { // was: $v['TypeX']==1 &&
                $texter[] = $v['Texter'];
            }
        }
    }

    $texter = ($texter) ? implode(PHP_EOL.'//'.PHP_EOL, $texter) : '';

    return $texter;
}



/**
 * Output story versions
 *
 * @param int $id Story ID
 * @return void
 */
function stry_versions_get($id) {

    $result = qry('SELECT * FROM stry_versions WHERE StoryID='.$id.' ORDER BY ID ASC');

    while ($x = mysqli_fetch_assoc($result)) {

        $new = $x['Texter'];

        if (!isset($old)) $old = $new;

        echo '<div class="row">'.
            '<div class="col-sm-9">'.htmlDiff($old, $new).'</div>'.
            '<div class="col-sm-3 text-uppercase"><small>'.uid2name($x['UID']).'<br>'.$x['TermMod'].'</small></div>'.
            '</div>';

        $old = $new;
    }
}


//https://github.com/paulgb/simplediff
function htmlDiff($old, $new){
    $ret = '';
    $diff = diff(preg_split("/[\s]+/", $old), preg_split("/[\s]+/", $new));
    foreach($diff as $k){
        if(is_array($k))
            $ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
                (!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
        else $ret .= $k . ' ';
    }
    return $ret;
}
function diff($old, $new){
    $matrix = array();
    $maxlen = 0;
    foreach($old as $oindex => $ovalue){
        $nkeys = array_keys($new, $ovalue);
        foreach($nkeys as $nindex){
            $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
            if($matrix[$oindex][$nindex] > $maxlen){
                $maxlen = $matrix[$oindex][$nindex];
                $omax = $oindex + 1 - $maxlen;
                $nmax = $nindex + 1 - $maxlen;
            }
        }
    }
    if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
    return array_merge(
        diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
        array_slice($new, $nmax, $maxlen),
        diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}


