<?php
/** Library of functions for LISTING */






/**
 * Prints film contract listing - lists all films that belong to specified contract
 *
 * @param int $id Contract ID
 * @return void
 */
function film_contract_listing($id) {

    global $tx, $cfg;

    $arr_types = txarr('arrays', 'film_types');
    $arr_sections = txarr('arrays', 'film_sections');

    $sql = '
        SELECT film.*, film_cn_bcasts.BCmax FROM film
        INNER JOIN film_cn_contracts ON film.ID = film_cn_contracts.FilmID
        LEFT JOIN film_cn_bcasts ON film.ID = film_cn_bcasts.FilmID
        WHERE film_cn_contracts.ContractID='.$id.'
        ORDER BY film.ID DESC';


    echo '<table class="table table-hover table-condensed listingz">';

    echo
        '<tr>'.
        '<th>'.$tx['LBL']['title'].'</th>'.
        '<th>'.$tx[SCTN]['LBL']['format'].'</th>'.
        '<th>'.$tx['LBL']['type'].'</th>'.
        '<th>'.$tx['LBL']['duration'].'</th>'.
        '<th>'.$tx[SCTN]['LBL']['licence'].'</th>'.
        (($cfg[SCTN]['bcast_cnt_separate']) ? '' : '<th>&nbsp;</th>').
        '</tr>';


    $result = qry($sql);
    while ($z = mysqli_fetch_assoc($result)) {

        echo
            '<tr>'.
            '<td>'.
            '<a href="?typ=item&id='.$z['ID'].'">'.c_output('cpt', rdr_cell('film_description', 'Title', $z['ID'])).'</a>'.
            (($z['TypeID']!=1) ? '<span> ['.$z['EpisodeCount'].']</span>' : '').
            '</td>'.
            '<td>'.$arr_types[$z['TypeID']].'</td>'.
            '<td>'.$arr_sections[$z['SectionID']].'</td>'.
            '<td>'.film_dur($z['DurApprox'], $z['DurReal'], $z['DurDesc']).'</td>'.
            '<td>'.$z['LicenceStart'].'&nbsp;&#8212;&nbsp;'.$z['LicenceExpire'].'</td>'.
            (($cfg[SCTN]['bcast_cnt_separate']) ? '' :
                '<td>'.epg_bcast_countbox((($z['TypeID']==1) ? 12 : 13), $z['ID'], $z['BCmax']).'</td>').
            '</tr>';
    }

    echo '</table>';
}




/**
 * Prints film agency listing - lists all contracts that belong to specified agency
 *
 * @param int $id Agency ID
 * @return void
 */
function film_agency_listing($id) {

    global $tx;

    $sql = 'SELECT ID, CodeLabel, DateContract, LicenceType FROM film_contracts WHERE AgencyID='.$id;


    echo '<table class="table table-hover table-condensed listingz">';

    echo
        '<tr>'.
        '<th>'.$tx['LBL']['label'].'</th>'.
        '<th>'.$tx['LBL']['date'].'</th>'.
        '<th>'.$tx[SCTN]['LBL']['licence_type'].'</th>'.
        '</tr>';


    $result = qry($sql);
    while ($z = mysqli_fetch_assoc($result)) {

        echo
            '<tr>'.
            '<td><a href="?typ=contract&id='.$z['ID'].'">'.$z['CodeLabel'].'</a></td>'.
            '<td>'.$z['DateContract'].'</td>'.
            '<td>'.$z['LicenceType'].'</td>'.
            '</tr>';
    }

    echo '</table>';
}







/**
 * Prints marketing agency listing - lists all marketing items that belong to specified agency
 *
 * @param int $id Agency ID
 * @return void
 */
function spice_agency_listing($id) {

    global $tx;

    $sql = 'SELECT ID, Caption, DurForc FROM epg_market WHERE AgencyID='.$id.' ORDER BY ID DESC';


    echo '<table class="table table-hover table-condensed listingz">';

    echo
        '<tr>'.
        '<th>&nbsp;</th>'.
        '<th>'.$tx['LBL']['title'].'</th>'.
        '<th>'.$tx['LBL']['duration'].'</th>'.
        '</tr>';


    $result = qry($sql);
    while ($z = mysqli_fetch_assoc($result)) {

        echo
            '<tr>'.
            '<td>'.sprintf('%04s',$z['ID']).'</td>'.
            '<td><a href="spice_details.php?sct=mkt&typ=item&id='.$z['ID'].'">'.$z['Caption'].'</a></td>'.
            '<td>'.$z['DurForc'].'</td>'.
            '</tr>';
    }

    echo '</table>';
}








/**
 * Prints film episodes listing
 *
 * @param string $typ Page type (frame, details)
 * @param array $x Film array
 * @return void
 */
function film_episodes_listing($typ, $x) {

    global $tx;


    echo '<table class="table table-hover table-condensed'.(($typ=='details') ? ' listingz' : '').'" id="episodes_tbl">';

    echo
        '<tr>'.
        '<th class="col-xs-1">#</th>'.
        '<th class="col-xs-7">'.$tx['LBL']['title'].'</th>'.
        '<th>(t) '.$tx[SCTN]['LBL']['approx'].'</th>'.
        '<th>(t) '.$tx[SCTN]['LBL']['correct'].'</th>'.
        '</tr>';

    for ($i=1; $i<=$x['EpisodeCount']; $i++) {

        $y = episode_reader($i, $x);

        if ($typ=='details') {

            $td_title = '<td>'.$y['Title'].'</td>';

        } else { // frame

            if (!$y['ID']){

                $td_title = '<td class="tdcpt">'.$y['Title'].'</td>';

            } else {

                $dur = film_dur($y['DurApprox'], $y['DurReal'], '', 'arr');

                $href = '<a href="#">'.(($y['Title']) ? $y['Title'] : '-').'</a>';

                $cpt = c_output('cpt', $x['DSC']['Title'].' ('.$i.')'.($y['Title'] ? ' - '.$y['Title'] : ''));

                $r = "['".$y['ID']."','".$x['EPG_SCT_ID']."','".$dur['Duration']."','".$cpt."']";

                $td_title = '<td class="tdcpt" onClick="window.parent.ifrm_result('.$r.');return false;">'.$href.'</td>';
            }
        }

        echo
            '<tr>'.
            '<td>'.$i.'</td>'.
            $td_title.
            '<td>'.((@$y['DurApprox']) ? '<span class="durapprox">&#8776;&nbsp;'.$y['DurApproxTXT']['hhmmss'].'</span>' : '').'</td>'.
            '<td>'.((@$y['DurReal']) ? '<span class="durreal">'.$y['DurRealTXT']['hhmmss'].'</span>' : '').'</td>'.
            '</tr>';
    }

    echo '</table>';
}








/**
 * Prints film episodes MODIFY form
 *
 * @param array $x Film array
 * @return void
 */
function film_episodes_listing_mdf($x) {

    global $tx;


    echo '<table class="table table-hover table-condensed" id="mdf_tbl">';

    echo
        '<tr>'.
        '<th style="width:40px">#</th>'.
        '<th>'.$tx['LBL']['title'].'</th>'.
        '<th style="width:172px">(t) '.$tx[SCTN]['LBL']['approx'].'</th>'.
        '<th style="width:172px">(t) '.$tx[SCTN]['LBL']['correct'].'</th>'.
        '</tr>';

    $show_master_hms = true;

    for ($i=1; $i<=$x['EpisodeCount']; $i++) {

        $y = episode_reader($i, $x);

        if ($show_master_hms && $y['DurReal']) {

            $show_master_hms = false;

            // We display master hms DurReal controls only if ALL hms DurReal values are empty
        }

        echo
            '<tr>'.
            '<td class="ordinal">'.
                '<input type="hidden" name="Ordinal['.$i.']" value="'.$i.'">'.
                $i.
            '</td>'.
            '<td>'.
                '<input type="text" class="form-control" name="Title['.$i.']" value="'.$y['Title'].'">'.
            '</td>'.
            '<td>'.
                '<div class="form-inline dur durapprox">'.
                form_hms_output('film_episodes', 'DurApprox', @$y['DurApproxTXT'], 'none').
                '</div>'.
            '</td>'.
            '<td>'.
                '<div class="form-inline dur durreal">'.
                form_hms_output('film_episodes', 'DurReal', @$y['DurRealTXT'], 'none').
                '</div>'.
            '</td>'.
            '</tr>';
    }

    echo '<tr id="master_controls">'.
        '<td colspan="2" class="text-right">'.$tx[SCTN]['MSG']['episodes_master_controls'].'</td>'.
        '<td><div class="form-inline dur durapprox">'.
            form_hms_output('film_episodes_master', 'DurApprox', null, 'none').'</div></td>'.
        '<td>';

    if ($show_master_hms) {
        echo '<div class="form-inline dur durreal">'.
            form_hms_output('film_episodes_master', 'DurReal', null, 'none').'</div>';
    }

    echo '</td></tr>';

    echo '</table>';
}



