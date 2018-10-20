<?php
/** Library of functions for epg USAGE
 *
 * Usage functions are used in two cases:
 * - 'terms': to give ID/terms list to display-page, so we can print list of epgs or scenarios where the item was used
 * - 'IDs': to give ID list to receiver, so we can run emit-update of all epgs/elements which contain the item (in case
 *   its duration has changed)
 */






/**
 * Story usage list
 *
 * @param array $x Story array
 * @param string $rtyp Return type: (terms, IDs). We will need IDs when calling from receivers.
 * @return array|void $r (depends on return type)
 */
function stry_usage($x, $rtyp='terms') {


    if ($rtyp=='IDs') {

        $clnz = ['epg_scnr_fragments.ScnrID'];

    } else { // $rtyp=='terms'

        $clnz = ['epg_scnr_fragments.ScnrID', 'epg_scnr_fragments.TermEmit', 'epg_scnr.ProgID', 'epg_scnr.Caption'];
    }


    $sql = 'SELECT '.implode(', ', $clnz).
        ' FROM epg_scnr_fragments INNER JOIN epg_scnr'.
        ' ON epg_scnr_fragments.ScnrID = epg_scnr.ID'.
        ' WHERE epg_scnr_fragments.NativeID='.$x['ID'].' AND epg_scnr_fragments.NativeType='.$x['EPG_SCT_ID'].
        ' ORDER BY epg_scnr_fragments.ID DESC';


    if ($rtyp=='IDs') {

        $r = usage_ids_array($sql);
        return $r;

    } else { // $rtyp=='terms'

        $r = [];

        $result = qry($sql); if (!$result) return null;
        while ($line = mysqli_fetch_assoc($result))  {

            // epg_scnr_fragments.TermEmit refers to time from the beginning of the program (which is in epg_elements.TermEmit),
            // not the real time. So we have to sum these two to get the real TermEmit for the fragment.
            // We also add EpgID, although currently we do not use it for the display/print (but that might change).
            // Also, we want to overwrite ScnrID with epg_elements.ID, to have the correct href for the SCN
            // And again, we want to exclude elements which belong to TMPL epgz.
            $sql = 'SELECT epg_elements.ID AS ScnrID, epg_elements.TermEmit'.
                ' FROM epg_elements INNER JOIN epgz'.
                ' ON epg_elements.EpgID = epgz.ID'.
                ' WHERE epg_elements.NativeID='.$line['ScnrID'].' AND epg_elements.NativeType=1 AND epgz.IsTMPL=0'.
                ' ORDER BY epg_elements.ID DESC';
            $frag_elemparent_line = qry_assoc_row($sql);

            if ($frag_elemparent_line) {

                // Start of the parent element, i.e. parent program
                $frag_elemparent_line['PARENT_TermEmit'] = $frag_elemparent_line['TermEmit'];

                // Start of the fragment. We calculate it by adding the fragment TermEmit to its parent program's start
                $frag_elemparent_line['TermEmit'] = add_dur2term($frag_elemparent_line['TermEmit'], $line['TermEmit']);

                $line = array_merge($line, $frag_elemparent_line);
                // If the input arrays have the same string keys, then the later value for that key will overwrite the previous one.
            }

            $r[] = $line;
        }

        stry_usage_print($r);
    }

}





/**
 * Program usage list
 *
 * @param int $progid Program ID
 * @return void
 */
function prgm_usage($progid) {

    $clnz = ['epg_elements.ID', 'epg_elements.EpgID', 'epg_elements.TermEmit', 'epg_scnr.ID AS ScnrID'];

    $sql = 'SELECT '.implode(', ', $clnz).
        ' FROM epg_elements'.
        ' INNER JOIN epgz ON epg_elements.EpgID = epgz.ID'.
        ' INNER JOIN epg_scnr ON epg_scnr.ID = epg_elements.NativeID'.
        ' WHERE epg_scnr.ProgID='.$progid.
        ' AND epg_elements.NativeType=1 AND epgz.IsTMPL=0'.
        ' ORDER BY epg_elements.TermEmit DESC';

    $r = qry_assoc_arr($sql);

    prgm_usage_print($r);
}




/**
 * Film usage list
 *
 * @param array $x Film array
 * @param string $rtyp Return type: (terms, IDs). We will need IDs when calling from receivers.
 * @return void|array $r (depends on return type)
 */
function film_usage($x, $rtyp='terms') {


    if ($rtyp=='IDs') {

        $clnz = ['epg_elements.EpgID'];

    } else { // $rtyp=='terms'

        $clnz = ['epg_elements.ID', 'epg_elements.EpgID', 'epg_elements.TermEmit', 'epg_films.ScnrID'];
    }


    $sql = 'SELECT '.implode(', ', $clnz).
        ' FROM epg_elements'.
        ' INNER JOIN epgz ON epg_elements.EpgID = epgz.ID'.
        ' INNER JOIN epg_films ON epg_films.ID = epg_elements.NativeID'.
        ' WHERE epg_films.FilmID='.$x['ID'].' AND epg_films.FilmParentID'.((@$x['ParentID']) ? '='.$x['ParentID'] : ' IS NULL').
            ' AND epg_elements.NativeType='.$x['EPG_SCT_ID'].' AND epgz.IsTMPL=0'.
        ' ORDER BY epg_elements.TermEmit DESC';


    if ($rtyp=='IDs') {

        $r = usage_ids_array($sql);
        return $r;

    } else { // $rtyp=='terms'

        $r = qry_assoc_arr($sql);

        prgm_usage_print($r);
    }

}




/**
 * Spice block usage list
 *
 * @param array $x Block (must have ID, BlockType)
 * @param string $rtyp Return type: (terms, IDs). We will need IDs when calling from receivers.
 * @param bool $do_print Whether to print the result or to return it as an array
 * @return array|void $r (depends on $do_print)
 */
function spcblock_usage($x, $rtyp='terms', $do_print=true) {


    if (!isset($x['BlockType'])) {
        $x['BlockType'] = $x['EPG_SCT_ID'];
    }



    if ($rtyp=='IDs') {

        // Get the list of ELEMENTS which contain the specified BLOCK, but we need only element's EPGID
        $clnz[0] = ['epg_elements.EpgID'];

        // Get the list of FRAGMENTS which contain the specified BLOCK, but we need only fragment's ScnrID
        $clnz[1] = ['epg_scnr_fragments.ScnrID'];

    } else { // $rtyp=='terms'

        // Get the list of ELEMENTS which point to the specified BLOCK. We need only element EPGID (for URL) and TermEmit
        // (for sorting the results). Also, we want to exclude elements which belong to TMPL epgz.
        $clnz[0] = ['epg_elements.EpgID', 'epg_elements.TermEmit', 'epg_elements.ID'];

        // Next, also get the list of FRAGMENTS which point to the specified BLOCK. As with the elements, we need fragment
        // ScnrID and TermEmit.
        $clnz[1] = ['epg_scnr_fragments.ScnrID', 'epg_scnr_fragments.TermEmit', 'epg_scnr_fragments.ID', 'epg_scnr.IsFilm'];
    }

    $sql[0] = 'SELECT '.implode(', ', $clnz[0]).
        ' FROM epg_elements INNER JOIN epgz'.
        ' ON epg_elements.EpgID = epgz.ID'.
        ' WHERE epg_elements.NativeID='.$x['ID'].' AND epg_elements.NativeType='.$x['BlockType'].' AND epgz.IsTMPL=0'.
        ' ORDER BY epg_elements.ID DESC';

    $sql[1] = 'SELECT '.implode(', ', $clnz[1]).
        ' FROM epg_scnr_fragments INNER JOIN epg_scnr'.
        ' ON epg_scnr_fragments.ScnrID = epg_scnr.ID'.
        ' WHERE epg_scnr_fragments.NativeID='.$x['ID'].' AND epg_scnr_fragments.NativeType='.$x['BlockType'].
        ' ORDER BY epg_scnr_fragments.ID DESC';



    if ($rtyp=='IDs') {


        // First return array key will hold IDs of EPGs which contain this block,
        // other key will hold IDs of SCNRs which contain this block.

        $r[0] = usage_ids_array($sql[0]);
        $r[1] = usage_ids_array($sql[1]);

        return $r;


    } else { // $rtyp=='terms'


        $r = [];

        $elem_result = qry($sql[0]);
        while ($elem_line = mysqli_fetch_assoc($elem_result))  {
            $r['USAGE'][] = $elem_line;
        }

        $frag_result = qry($sql[1]);
        while ($frag_line = mysqli_fetch_assoc($frag_result)) {

            $elm = scnr_elm_native(['ID' => $frag_line['ScnrID'], 'IsFilm' => $frag_line['IsFilm']]);

            // epg_scnr_fragments.TermEmit refers to time from the beginning of the program (which is in epg_elements.TermEmit),
            // not the real time. So we have to sum these two to get the real TermEmit for the fragment.
            // We also add EpgID, although currently we do not use it for the display/print (but that might change).
            // Also, we want to send epg_elements.ID, to have the correct href for the SCN
            // And again, we want to exclude elements which belong to TMPL epgz.
            $sql = 'SELECT epg_elements.ID AS ElementID, epg_elements.EpgID, epg_elements.TermEmit'.
                ' FROM epg_elements INNER JOIN epgz'.
                ' ON epg_elements.EpgID = epgz.ID'.
                ' WHERE epg_elements.NativeID='.$elm['NativeID'].' AND epg_elements.NativeType='.$elm['NativeType'].
                ' AND epgz.IsTMPL=0'.
                ' ORDER BY epg_elements.ID DESC';
            $frag_elemparent_line = qry_assoc_row($sql);

            if ($frag_elemparent_line) {

                // Start of the parent element, i.e. parent program
                $frag_elemparent_line['PARENT_TermEmit'] = $frag_elemparent_line['TermEmit'];

                // Start of the fragment. We calculate it by adding the fragment TermEmit to its parent program's start
                $frag_elemparent_line['TermEmit'] = add_dur2term($frag_elemparent_line['TermEmit'], $frag_line['TermEmit']);

                $z = array_merge($frag_line, $frag_elemparent_line);
                // If the input arrays have the same string keys, then the later value for that key will overwrite the previous one.

                $r['USAGE'][] = $z;
            }
        }


        if (isset($r['USAGE'])) { // get TERMEMIT for each line and then construct USAGE_ORDER array, i.e QUEUE

            foreach ($r['USAGE'] as $k => $v) {
                $r['USAGE_ORDER'][strtotime($v['TermEmit'])] = $k;
            }

            krsort($r['USAGE_ORDER']);
            // Sort an array by key in reverse order

            if ($do_print) {

                spcblock_usage_print($r);

            } else {

                return $r;
            }
        }

        // The result may be used in array_merge() function (within spcitem_usage()), so we have to return at least the empty array.
        return $r;

    }

}




/**
 * Spice item usage list
 *
 * @param array $x Item
 * @param string $rtyp Return type: (terms, IDs). We will need IDs when calling from receivers.
 * @return array|void $r (depends on return type)
 */
function spcitem_usage($x, $rtyp='terms') {

    if ($rtyp=='IDs') {

        // Get the list of blocks which contain the specified item. We want to exclude INACTIVE items.
        $sql = 'SELECT BlockID FROM epg_cn_blocks'.
            ' WHERE IsActive AND NativeID='.$x['ID'].' AND NativeType='.$x['EPG_SCT_ID'].
            ' ORDER BY Queue';

    } else { // $rtyp=='terms'

        // Get the list of blocks which contain the specified item. Additionaly, we need to know whether
        // the item is ACTIVE within that block, or not (because we will mark the inactive ones with different css).
        $sql = 'SELECT BlockID, IsActive FROM epg_cn_blocks'.
            ' WHERE NativeID='.$x['ID'].' AND NativeType='.$x['EPG_SCT_ID'].
            ' ORDER BY Queue';
    }


    if ($rtyp=='IDs') {

        $r = usage_ids_array($sql);
        return $r;


    } else { // $rtyp=='terms'

        $r = [];

        $result = qry($sql);
        while ($line = mysqli_fetch_assoc($result)) {

            // Get the block CAPTION, and ID&TYP, and save it into BLOCK index. We need these for BLOCK LINK later on.
            $line['BLOCK'] = qry_assoc_row('SELECT ID, Caption, BlockType FROM epg_blocks WHERE ID='.$line['BlockID']);

            // Get usage for each block, and add these few more info we fetched in previous step.
            $tmp = spcblock_usage($line['BLOCK'], 'terms', false);

            $line['BLOCK'] = array_merge($line['BLOCK'], $tmp);

            $r[] = $line;
        }

        spcitem_usage_print($r);
    }
}













/**
 * Prints story usage list
 *
 * @param array $z Data array
 * @return void
 */
function stry_usage_print($z) {

    foreach ($z as $v) {

        echo '<div>'.
            $v['PARENT_TermEmit'].
            ' <a href="/epg/epg.php?typ=scnr&id='.$v['ScnrID'].'">'.prgcpt_get($v['ProgID'], $v['Caption']).'</a> '.
            '('.date('H:i:s', strtotime($v['TermEmit'])).')'.
            '</div>';
    }
}


/**
 * Prints usage list for programs and films
 *
 * @param array $z Data array
 * @return void
 */
function prgm_usage_print($z) {

    $rerun_sign = txarr('arrays', 'epg_mattyp_signs', 3);

    foreach ($z as $v) {

        $mattyp = rdr_cell('epg_scnr', 'MatType', $v['ScnrID']);

        $href = '/epg/epg.php?typ=scnr&id='.$v['ID']; // Point to SCNR
        // $href = '/epg/epg.php?typ=epg&id='.$v['EpgID'].'#tr'.$v['ID']; // point to EPG

        echo '<div>'.
                '<a href="'.$href.'">'.$v['TermEmit'].'</a>'.
                (($mattyp==3) ? '<span class="usage_rerun">'.$rerun_sign.'</span>' : '').
            '</div>';
    }
}





/**
 * Film serial usage list
 *
 * @param array $x Film array
 * @param string $typ Type: (dtl, mdf)
 *
 * @return void
 */
function film_serial_usage($x, $typ='dtl') {

    $clnz = ['epg_elements.ID', 'epg_elements.EpgID', 'epg_elements.TermEmit', 'epg_elements.NativeID',
        'epg_films.FilmID', 'epg_films.ScnrID'];

    $sql = 'SELECT '.implode(', ', $clnz).
        ' FROM epg_elements'.
        ' INNER JOIN epgz ON epg_elements.EpgID = epgz.ID'.
        ' INNER JOIN epg_films ON epg_films.ID = epg_elements.NativeID'.
        ' WHERE epg_films.FilmParentID='.$x['ID'].' AND epg_elements.NativeType=13 AND epgz.IsTMPL=0'.
            //(($typ=='mdf') ? ' AND epgz.DateAir>= DATE_SUB(\''.DATENOW.'\', INTERVAL 1 DAY)' : '').
        ' ORDER BY epg_elements.TermEmit ASC';

    $z = qry_assoc_arr($sql);

    film_serial_usage_print($z, $x, $typ);
}



/**
 * Prints usage list for film serials
 *
 * @param array $z Usage data array
 * @param array $x Film array
 * @param string $typ Type: (dtl, mdf)
 *
 * @return void
 */
function film_serial_usage_print($z, $x, $typ) {

    global $tx;

    $x['Episodes'] = rdr_cln('film_episodes', 'Ordinal', 'ParentID='.$x['ID']);

    $rerun_sign = txarr('arrays', 'epg_mattyp_signs', 3);

    echo '<table class="table table-hover table-condensed listingz serial_usage '.$typ.'">';
    echo '<tr>'.
            '<th>'.$tx['LBL']['term'].'</th>'.
            '<th>'.$tx[SCTN]['LBL']['episodes'].'</th>';

    if ($typ=='mdf') {
        echo '<th>'.$tx['LBL']['modify'].'</th><th>';
        form_btnsubmit_output('Submit_FILM_EP_USAGE_AUTO',
            ['type' => 'btn_only', 'css' => 'btn-xs', 'btn_txt' => $tx[SCTN]['LBL']['auto-correct'], 'sec' => true]);
        echo '</th>';
    }

    echo '</tr>';

    foreach ($z as $k => $v) {

        echo '<tr>';

        $ep = rdr_cell('film_episodes', 'Ordinal', $v['FilmID']);

        $mattyp = rdr_cell('epg_scnr', 'MatType', $v['ScnrID']);

        if (!isset($ep_prev)) {

            $ep_ok = $ep;

        } else {

            $ep_ok = ($mattyp==3) ? $ep_prev : ($ep_prev+1);

            if ($ep_ok > $x['EpisodeCount']) { // Another broadcast cycle
                $ep_ok = 1;
            }
        }

        $ep_err = ($ep!=$ep_ok) ? true : false;

        $href = '/epg/epg.php?typ=scnr&id='.$v['ID']; // Point to SCNR
        // $href = '/epg/epg.php?typ=epg&id='.$v['EpgID'].'#tr'.$v['ID']; // point to EPG

        echo '<td class="termemit"><a href="'.$href.'">'.$v['TermEmit'].'</a></td>'.
            '<td class="ep_cur'.(($ep_err) ? ' err' : '').'">'.$ep.
                (($mattyp==3) ? '<span class="usage_rerun">'.$rerun_sign.'</span>' : '').
            '</td>';

        if ($typ=='mdf') {

            echo '<td class="ctrl">';

            ctrl('form', 'select', null, 'ID['.$v['NativeID'].']',
                arr2mnu($x['Episodes'], array_search($ep, $x['Episodes'])),
                null, ['nowrap' => true]);

            if (!isset($ep_ideal)) {

                $ep_ideal = $ep;

            } elseif ($mattyp!=3) {

                $ep_ideal++;

                if ($ep_ideal > $x['EpisodeCount']) { // Another broadcast cycle
                    $ep_ideal = 1;
                }
            }

            form_ctrl_hidden('AUTO['.$v['NativeID'].']', array_search($ep_ideal, $x['Episodes']));

            echo '</td>';

            echo '<td class="ideal">'.$ep_ideal.'</td>';
        }

        $ep_prev = $ep;

        echo '</tr>';
    }

    echo '</table>';
}




/**
 * Prints spice item usage list
 *
 * @param array $z Data array
 * @return void
 */
function spcitem_usage_print($z) {

    foreach ($z as $v) {

        echo
            '<tr'.(($v['IsActive']) ? '' : ' class="sleep"').'>'.

            '<td>'. // BLOCK LINK
            '<a href="spice_details.php?sct='.(($v['BLOCK']['BlockType']==3) ? 'mkt':'prm').
            '&typ=block&id='.$v['BLOCK']['ID'].'">'.$v['BLOCK']['Caption'].'</a>'.
            '</td>'.

            '<td>';

        if (isset($v['BLOCK']['USAGE'])) {

            spcblock_usage_print($v['BLOCK']);

        } else {

            echo '&nbsp;';
        }

        echo '</td>'.
            '</tr>';
    }
}





/**
 * Prints spice block usage list
 *
 * @param array $z Data array. Contains USAGE array and USAGE_ORDER array
 * @return void
 */
function spcblock_usage_print($z) {

    /*
     * USAGE_ORDER array has timestamps (TermEmit) for keys, and *USAGE array keys* for values. Its only purpose is for
     * sorting the USAGE array by TermEmit.
     *
     * [USAGE_ORDER] => Array
        (
            [1398848100] => 0
            [1398841010] => 1
            [1398834162] => 8
            ...
        )
     *
     * USAGE array has varying number of keys, which depends on whether the listed member belongs to an EPG or to a SCN.
     * All members have [EpgID] key, but members which belong to a SCN, also have [ElementID] key.
     * We list Bcast times - [TermEmit], and we use either [EpgID] or [ElementID] for href.
     * If it is a member which belongs to a SCN, then we also mark the CAPTION and TERMEMIT of the SCNR.
     */

    foreach ($z['USAGE_ORDER'] as $k) { // loop USAGE_ORDER array, in order to have chronologicaly QUEUED list

        $n = $z['USAGE'][$k];
        $typ = ((isset($n['ElementID'])) ? 'scnr' : 'epg');

        echo '<div class="block_usage">';

        echo '<a href="epg.php?typ='.$typ.'&id='.(($typ=='epg') ? $n['EpgID'] : $n['ElementID']).'#tr'.$n['ID'].'">'.
            $n['TermEmit'].'</a>';

        if ($typ=='scnr') {

            echo '<span>('.scnr_cpt_get($n['ElementID']).' '.
                date('H:i:s', strtotime($n['PARENT_TermEmit'])).')</span>';
        }

        echo '</div>';
    }
}












/**
 * Get IDs array, to be used in receivers
 *
 * @param string $sql
 * @return array $r
 */
function usage_ids_array($sql) {

    $r = [];

    $result = qry($sql);

    while ($line = mysqli_fetch_row($result))  {
        if (!in_array($line[0], $r)) { // to avoid repeating
            $r[] = $line[0];
        }
    }

    return $r;
}