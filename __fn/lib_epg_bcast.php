<?php
/** Library of functions for epg "BCAST" (broadcast) handling */





/**
 * Receiver for EPG BCAST verification action
 *
 * @param array $verif
 *  - 'schtyp'  Schedule type: 1-epg, 2-scn
 *  - 'id'      Item ID (ID in elements/fragments table)
 *  - 'phase'   Data for saving - phase: 1-green-ok (has run), 2-red-fail (has not run)
 *  - 'term'    Data for saving - Item TimeAir (broadcast term, in TIMESTAMP format)
 * @return void
 */
function epg_bcast_receiver($verif) {

    $where_arr = [
        'SchType' 	=> intval($verif['schtyp']),
        'SchLineID' => intval($verif['id']),
    ];

    $mdf['Phase']		= intval($verif['phase']);
    $mdf['TermStart'] 	= date('Y-m-d H:i:s', intval($verif['term']));
    $mdf['ChannelID'] 	= CHNL;

    $native = rdr_row(
        (($where_arr['SchType']==1) ? 'epg_elements' : 'epg_scnr_fragments'),
        'NativeType, NativeID',
        $where_arr['SchLineID']
    );

    if ($native['NativeType']==12) {
        $native['NativeID'] = rdr_cell('epg_films', 'FilmID', $native['NativeID']);
    }

    if ($native['NativeType']==13) {
        $native['NativeID'] = rdr_cell('epg_films', 'FilmParentID', $native['NativeID']);
    }

    $mdf = array_merge($mdf, $native);

    receiver_mdf('epg_bcasts', $where_arr, $mdf);

    // updating film.BCcur (forth argument means logging difs is off)
    epg_bcast_count_cur($native['NativeType'], $native['NativeID'], CHNL, false);
}





/**
 * Outputs EPG BCAST verification controls (buttons)
 *
 * @param array $verif (same as in the previous function - epg_bcast_receiver())
 * @return void
 */
function epg_bcast_ctrl_output($verif) {

    $href = '?typ=epg&id='.EPGID.'&view='.VIEW_TYP;

    if ($verif) { // VERIFY_SINGLE

        $href .=
            '&verif_schtyp='.$verif['schtyp'].
            '&verif_id='.$verif['id'].
            '&verif_term='.$verif['term'];

    } else { // VERIFY_ALL ($verif==null)

        $href .= '&verif_all=1'; // *verif_all* attribute
    }

    echo '<div>';

    if ($verif['phase']==1) {
        echo '<a role="button" class="btn btn-success btn-xs disabled">&nbsp;&nbsp;</a>';
    } else {
        echo '<a role="button" class="btn btn-success btn-xs" href="'.$href.'&verif_phase=1">'.
            '<span class="glyphicon glyphicon-ok"</span></a>';
    }

    if ($verif['phase']==2) {
        echo '<a role="button" class="btn btn-danger btn-xs disabled">&nbsp;&nbsp;</a>';
    } else {
        echo '<a role="button" class="btn btn-danger btn-xs" href="'.$href.'&verif_phase=2">'.
            '<span class="glyphicon glyphicon-remove"</span></a>';
    }

    echo '</div>';
}





/**
 * Listing both verified (i.e. either confirmed or disconfirmed, manualy)
 * and unverified (i.e. ignored) BCASTs for the specified item
 *
 * @param int $nat_type Native type
 * @param int $nat_id Native ID
 * @param string $rtype Return type: fullterm (for *details* page), ym_term (for *list* page)
 * @param int $chnl Channel ID
 * @param bool $output Whether to output
 * @return void|string $r HTML
 */
function epg_bcast_list($nat_type, $nat_id, $rtype, $chnl=0, $output=false) {

    $r = [];        // return array, will hold an HTML string for each line
    $bc = [];       // bcast data array, will hold an array of data for each line
    $orphans = [];  // UNVERIFIED (ignored) bcasts - we search through epg/scn and compare with data from epg_bcast table

    $epg_viewz = [3 => 8, 4 => 9, 12 => 10, 13 => 10]; // We need this only for links


    $where = [];
    $where[] = 'NativeType='.$nat_type;
    $where[] = 'NativeID='.$nat_id;
    if ($chnl) {
        $where[] = 'ChannelID='.$chnl;
    }




    $sql = 'SELECT ID, SchType, SchLineID, TermStart, Phase FROM epg_bcasts'.
        ' WHERE '.implode(' AND ', $where).
        ' ORDER BY TermStart '.(($rtype=='fullterm') ? 'DESC' : 'ASC');

    $result = qry($sql);
    while ($line = mysqli_fetch_assoc($result)) {

        // SchType & SchLineID refer to ID in elements/fragments table.
        // Schedule type: 1=epg (elements), 2=scnr (fragments)

        if ($line['SchType']==1) { // epg

            if ($line['SchLineID']) {
                $line['EpgID'] = rdr_cell('epg_elements', 'EpgID', $line['SchLineID']);
            }

        } else { // scn

            /* vb2do This will be necessary only when bcasts are implemented for mkt/prm
            $scnid	= rdr_cell('epg_scnr_fragments', 'ScnrID', $line['SchLineID']);

            $elmid = scnr_id_to_elmid($scnid);

            $line['EpgID'] = rdr_cell('epg_elements', 'EpgID', $elmid);
            */
        }

        if (!empty($line['EpgID'])) {
            $href = 'epg.php?typ=epg&view='.$epg_viewz[$nat_type].'&id='.$line['EpgID'].'#tr'.$line['SchLineID'];
        } else {
            $href = '';
        }

        $bc[] = [
            'URL' 		=> $href,
            't' 		=> strtotime($line['TermStart']), // get timestamp because we will later do some time formatting
            'Phase' 	=> $line['Phase'],
            'SchLineID' => $line['SchLineID'],
            'ID'        => $line['ID'],
        ];
    }




    if ($rtype=='fullterm') {	// for *details* page


        // IGNORED BCASTS (we search epgs for unverified broadcasts for this item)

        if ($nat_type==12) { // FILM

            foreach($bc as $v) {
                $bc_keys[] = $v['SchLineID'];
            }

            $elements = rdr_cln('epg_films', 'ID', 'FilmID='.$nat_id);

            if ($elements) {

                foreach($elements as $k => $v) {

                    $sql =
                        'SELECT epg_elements.ID, epg_elements.EpgID, epg_elements.Queue, epg_elements.TimeAir,
                                epg_elements.NativeID, epg_elements.TermEmit '.
                        'FROM epg_elements INNER JOIN epgz ON epg_elements.EpgID = epgz.ID '.
                        'WHERE epg_elements.NativeID='.$v.' AND epg_elements.NativeType='.$nat_type.
                        (($chnl) ? ' AND epgz.ChannelID='.$chnl : '');

                    $element = qry_assoc_row($sql);

                    if (!$element) {  // filter out elements which don't belong to selected channel
                        continue;
                    }

                    if (isset($bc_keys) && in_array($element['ID'], $bc_keys)) {
                        continue;   // already in bc array
                    }

                    $element['DateAir'] = rdr_cell('epgz', 'DateAir', $element['EpgID']);

                    // If element does not have fixed term, we have to calculate it
                    if (!$element['TimeAir'] || $element['TimeAir']=='00:00:00') {

                        $element['TimeAir'] = element_start_calc($element['EpgID'], $element['Queue'],
                            $element['DateAir'], $element['TermEmit']);
                    }

                    $element['FilmID'] = rdr_cell('epg_films', 'ScnrID', $element['NativeID']);
                    $element['MatType'] = rdr_cell('epg_scnr', 'MatType', $element['FilmID']);

                    $orphans[$k] = $element;
                }

                // Add orphans to bc array

                if ($orphans) {

                    foreach($orphans as $v) {

                        $bc[] = [
                            'URL' 		=> 'epg.php?typ=epg&view='.$epg_viewz[$nat_type].'&id='.$element['EpgID'].'#tr'.$element['ID'],
                            't' 		=> strtotime($v['TimeAir']),
                            'Phase' 	=> 0,
                            'SchLineID' => $v['ID'],
                            'MatType' 	=> $v['MatType'],
                        ];
                    }
                }

            }
        }

        $epg_mattyp_signs = txarr('arrays', 'epg_mattyp_signs');

        foreach($bc as $v) {

            switch ($v['Phase']) {
                case 0: $ico = 'minus';  $clr = 'default';  break;
                case 1: $ico = 'ok';     $clr = 'success';  break;
                case 2: $ico = 'remove'; $clr = 'danger';   break;
            }

            // sign
            $html = '<a role="button" class="btn btn-'.$clr.' btn-xs disabled">'.
                '<span class="glyphicon glyphicon-'.$ico.'"></span></a>';

            // datetime
            if ($v['URL']) {
                $html .= '<a href="'.$v['URL'].'">'.date('d.m.Y', $v['t']).'</a>'.date(' H:i:s', $v['t']);
            } else {
                $html .= date('d.m.Y H:i:s', $v['t']);
            }

            // rerun label
            if (@$v['MatType']==3) {
                $html .= '<span class="lblprog3">'.$epg_mattyp_signs[3].'</span>';
            }

            // delete button
            if ($v['Phase']) {
                $html .= '<a class="bc_del" href="?'.$_SERVER["QUERY_STRING"].'&bc_del='.$v['ID'].'">'.
                    '<span class="glyphicon glyphicon-remove"></span></a>';
            }

            $r[$v['t']] = $html;
        }

        krsort($r);
    }


    if ($rtype=='ym_term') {	// for *list* page

        foreach($bc as $v) {

            $r[] =
                '<div class="'.(($v['Phase']==2) ? 'bc_term_fail' : 'bc_term').'">'.
                (($v['URL']) ? '<a href="'.$v['URL'].'">' : '').
                '<span class="bc_yy">'.date('y',$v['t']).'</span>'.'<span class="bc_mm">'.date('m',$v['t']).'</span>'.
                (($v['URL']) ? '</a>' : '').
                '</div>';
        }
    }


    if ($r) {

        switch ($rtype) {

            case 'ym_term':
                $r = '<div class="bc_list">'.implode('', $r).'</div>';
                break;

            case 'fullterm':
                $r 	= '<div class="bc_listvert">'.implode('</div><div class="bc_listvert">', $r).'</div>';
                break;
        }

    } else {
        $r = ''; // we don't want to return empty array, because return value must be of *string* type
    }


    if ($output) {
        echo $r;
        return null;
    } else {
        return $r;
    }
}








/**
 * Display 3-BOX bcast information (cur, max, remains)
 *
 * @param int $nat_type Native type
 * @param int $nat_id Native ID
 * @param int $bcmax BCmax
 * @param int $chnl Channel ID
 * @param bool $output Whether to output
 * @return string $r
 */
function epg_bcast_countbox($nat_type, $nat_id, $bcmax, $chnl=0, $output=false) {

    //if (in_array($nat_type, array(12,13))) $z['BCmax'] = rdr_cell('film', 'BCmax', $nat_id);
    //$z['BCmax'] = intval($z['BCmax']);

    $z['BCmax'] = intval($bcmax);

    $z['BCcur'] = epg_bcast_count_cur($nat_type, $nat_id, $chnl, true);

    $cnt_remain = ($z['BCmax']) ? ($z['BCmax']-$z['BCcur']) : '';
    // In case BCmax is not set, we will print empty string in max-box(#2). Then we also want an empty string in
    // remains-box(#3). This is why we intentionally put an empty string instead of zero here.

    if ($cnt_remain<0) {
        $cnt_remain=0;
    }

    $r =
        '<div class="bc_stat">'.
        '<span class="'.(($z['BCmax'] && $z['BCcur']>$z['BCmax']) ? 'bc_count_excess' : 'bc_count').'">'.$z['BCcur'].'</span>'.
        '<span class="bc_max">'.(($z['BCmax']) ? $z['BCmax'] : '*').'</span>'.
        '<span class="'.((!$cnt_remain && $z['BCmax']) ? 'bc_remain_zero' : 'bc_remain').'">'.
            (($cnt_remain!=='') ? $cnt_remain : '*').'</span>'.
        '</div>';

    if ($output) {
        echo $r;
        return null;
    } else {
        return $r;
    }
}






/**
 * Get BCcur value by counting lines in *epg_bcasts* table, and update *film.BCcur* if necessary
 *
 * @param int $nat_type Native type
 * @param int $nat_id Native ID
 * @param int $chnl Channel ID
 * @param bool $log Whether to log in case of *update* happening (because that would indicate an error somewhere)
 * @return int $bccur_real Correct BCcur value
 */
function epg_bcast_count_cur($nat_type, $nat_id, $chnl=0, $log=false) {

    global $cfg;

    if ($cfg[SCTN]['bcast_cnt_separate']) {

        $bccur_real = cnt_sql('epg_bcasts',
            'NativeType='.$nat_type.' AND NativeID='.$nat_id.' AND Phase=1 AND ChannelID='.$chnl);

        $bccur_saved = rdr_cell('film_cn_bcasts', 'BCcur', 'FilmID='.$nat_id.' AND ChannelID='.$chnl);

        if (intval($bccur_real)!=intval($bccur_saved)) {

            qry('UPDATE film_cn_bcasts SET BCcur='.$bccur_real.' WHERE FilmID='.$nat_id.' AND ChannelID='.$chnl);

            if ($log) {
                log2file('epg-bc', ['ID' => $nat_id, 'BCcur' => $bccur_real.'/'.$bccur_saved]);
            }
        }

    } else {

        $bccur_real = cnt_sql('epg_bcasts',
            'NativeType='.$nat_type.' AND NativeID='.$nat_id.' AND Phase=1');
            // we don't specify channel, thus bcasts from ALL channels will be counted

        $bccur_saved = rdr_cell('film_cn_bcasts', 'BCcur', 'FilmID='.$nat_id);  //.' AND ChannelID IS NULL'
        // In film_cn_bcasts table, ChannelID will be null if separate bcast count is off.
        // Thus it is not necessary to add this filter (ChannelID IS NULL).
        // It could be useful only in case we are migrating from one type of bcast count to other, and we want to test
        // so we have mixed migrated and unmigrated rows together, etc..

        if (intval($bccur_real)!=intval($bccur_saved)) {

            qry('UPDATE film_cn_bcasts SET BCcur='.$bccur_real.' WHERE FilmID='.$nat_id); //.' AND ChannelID IS NULL'

            if ($log) {
                log2file('epg-bc', ['ID' => $nat_id, 'BCcur' => $bccur_real.'/'.$bccur_saved]);
            }
        }

    }

    return $bccur_real;
}



