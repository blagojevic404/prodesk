<?php

// epg





require 'lib_epg_spices.php';
require 'lib_epg_film.php';
require 'lib_epg_bcast.php';
require 'lib_epg_usage.php';
require 'lib_epg_listing.php';
require 'lib_epg_output.php';
require 'lib_epgz_output.php';



if (SCTN=='epg') {
    require 'lib_epg_mktplan.php';
    require 'lib_epg_mktplan_item.php';
    require 'lib_epg_mktplan_sync.php';
    require 'lib_epg_mktplan_epg.php';
    require 'lib_epg_mktplan_uptd.php';
}













/**
 * Reads EPG data from db.
 *
 * @param int $id EPG ID
 * @param bool $is_tmpl Whether this is template. Used necessary only when calling from element_reader(),
 *                      i.e. only for SCN, because in that case there is no other way to determine whether
 *                      it is element which belongs to a TMPL or to normal EPG.
 * @return array $x EPG array
 */
function epg_reader($id, $is_tmpl=false) {

	if ($id) $x = qry_assoc_row('SELECT * FROM epgz WHERE ID='.$id);
	$x['ID'] = intval(@$x['ID']);


	if (!$x['ID']) {

		$x['IsReady'] = 0;
        $x['IsTMPL'] = $is_tmpl;

        $x['ChannelID'] = (!empty($_GET['chnl'])) ? intval($_GET['chnl']) : CHNL;

        return $x;
	}


	if ($x['IsTMPL']) {

        // we had to put template CAPTIONS into another table
		$x['Caption'] = rdr_cell('epg_templates', 'Caption', $x['ID']);
    }

    if (!$x['IsTMPL']) { // If not TMPL, then it is epg, and we should determine whether it is REAL or PLAN

        $x['IsReal'] = ($x['DateAir']==get_real_dateair()) ? 1 : 0;
    }

 return $x;
}






/**
 * Wraper for ELEMENT reader. Uses GET variables or default values to call element_reader() function.
 * The only purpose of this function is to avoid repeating this code in multiple places where the element reader is called.
 *
 * @param string $rtyp Return type (dtl, rcv)
 *
 * @return array $x Element array
 */
function element_reader_front($rtyp='dtl') {

	$id = (isset($_GET['id'])) ? wash('int', $_GET['id']) : 0; // for MDF and DTL

    if (!$id) {

        $new['epgid']   = (isset($_GET['epg']))     ? wash('int', $_GET['epg']) 		: 0; // only for NEW
        $new['qu']      = (isset($_GET['qu']))      ? wash('int', $_GET['qu']) 			: 0; // only for NEW
        $new['linetyp'] = (isset($_GET['linetyp'])) ? wash('int', $_GET['linetyp']) 	: 0; // only for NEW

    } else {

        $new = null;
    }

	$x = element_reader($id, $rtyp, $new);

	return $x;
}

/**
 * ELEMENT reader.
 *
 * Fetches data about specified element id, or if it is NEW ELEMENT then simply copies attributes into array
 * and adds few default empty time values.
 *
 * @param int $id Element ID. The only attribute which we send, if it is MODIFY or DETAILS page.
 * @param string $rtyp Return type (dtl, rcv)
 * @param array $new Data for NEW element (if it is NEW page, then we don't have element ID, but we send this instead)
 *
 * @return array $x Element array
 */
function element_reader($id, $rtyp='dtl', $new=null) {

	global $cfg;

	if ($id) $x = qry_assoc_row('SELECT * FROM epg_elements WHERE ID='.$id);
    // epg_elements: ID, EpgID, NativeType, NativeID, Queue, TimeAir, DurForc, IsActive, WebLIVE, WebVOD, TermEmit

    $x['ID'] = intval(@$x['ID']);


	if (!$x['ID']) { // for NEW..

        // copy function attributes into array members

		$x['EpgID'] 		= $new['epgid']; // we will fetch EPG array for this ID before return
		$x['Queue'] 		= $new['qu'];
		$x['NativeType'] 	= $new['linetyp'];

        // default empty time values

		$x['TimeAirTXT'] 	= t2boxz('', 'time', '');
		$x['DurForcTXT'] 	= t2boxz('', 'time');

		$x['MOS']['tc-in'] = $x['MOS']['tc-out'] = t2boxz('', 'time');
	}

    // We have to determine this before calling epg_reader() because there is no way to determine within that function
    // whether the calling element belongs to TMPL or EPG.
    $is_tmpl = ($x['ID'] && !$x['EpgID']) ? 1 : 0;

	$x['EPG'] = epg_reader($x['EpgID'], $is_tmpl);

	if (!$x['ID']) {
        return $x;
    }


	if (!strtotime($x['TimeAir'])) {
        $x['TimeAir'] = '';
    }
	//if ($x['TimeAir']=='0000-00-00 00:00:00') $x['TimeAir'] = '';
	//if ($x['TimeAir']=='00:00:00') $x['TimeAir'] = '';

	$x['TimeAirTXT'] = t2boxz($x['TimeAir'], 'time', '');
	$x['DurForcTXT'] = t2boxz($x['DurForc'], 'time');


    switch ($x['NativeType']) {

        case 1: // prog

            $x['PRG'] = qry_assoc_row('SELECT * FROM epg_scnr WHERE ID='.$x['NativeID']);
            // epg_scnr: ID, ProgID, Caption, MatType, IsFilm, DurEmit
            // 'Caption' is THEME of the specific program broadcast

            if ($x['PRG']['ProgID']) { // normal behaviour (some program WAS selected in the prog cbo)

                // 'ProgCPT' is a program caption, i.e. program name. We read it from the db.
                $x['PRG']['ProgCPT'] = prg_caption($x['PRG']['ProgID']);

                $x['PRG']['SETZ'] = rdr_row('prgm_settings', '*', $x['PRG']['ProgID']);

                /* RECORD FOR RERUN label configuration.
                 * Normally we use RECORD-FOR-RERUN settings from the PROG settings. In that case, we just fetch it from db.
                 * Otherwise, we will have to add RECORD input checkbox throughout the MDF scripts.
                 */
                if ($cfg['lbl_rec4rerun_prgbased']) {

                    // LNG-ARRAY: epg_material_types (3=rerun). If this IS rerun, then obviously we won't record it for rerun.
                    $x['Record'] = ($x['PRG']['MatType']!=3) ? $x['PRG']['SETZ']['EPG_Rerun'] : 0;
                }

            } else { // EMPTY prog, i.e. none of the programs from the prog cbo was selected

                /* When it is some kind of a special program, which doesn't exist in programs table, then the user will not
                 * select the program (from prog cbo), but instead just write the PROGRAM NAME in the TITLE input field.
                 * This will be used as the program name. If the user also wants to add the regular TITLE/THEME,
                 * then he will put that together with the program name, but separate it with the '|' or '~'.
                 */

                if ($rtyp=='dtl') {

                    list($x['PRG']['ProgCPT'], $x['PRG']['Caption']) = prgcpt_explode($x['PRG']['Caption']);
                    // 'ProgCPT' is program caption, i.e. PROGRAM NAME.
                    // 'Caption' is subcaption, i.e. PROGRAM THEME
                }

            }

            $x['MOS'] = mos_reader($x['NativeID'], $x['NativeType']);

            $x['CRW'] = crw_reader($x['NativeID'], $x['NativeType']);

            $x['PRG']['DurCalc'] = scnr_durcalc( // Calculate summary duration of fragments
                ['ID' => $x['PRG']['ID'], 'MatType' => $x['PRG']['MatType'], 'NativeType' => 1, 'MOS' => $x['MOS']]
            );

            $x['DurForcTXT'] = t2boxz(@$x['DurForc'], 'time');
            $x['DurCalcTXT'] = t2boxz(@$x['PRG']['DurCalc'], 'time');

            $x['DurCalcCSS'] = dur_handler($x['DurForc'], $x['PRG']['DurCalc'], 'css');

            break;

        case 3: // mkt
        case 4: // prm

            $x['BLC']  = qry_assoc_row('SELECT ID, Caption, DurForc FROM epg_blocks WHERE ID='.$x['NativeID']);
            $x['BLC']['DurCalc'] = epg_durcalc('block', $x['BLC']['ID']);
            break;

        case 5: // clp

            $x['CLP'] = qry_assoc_row('SELECT ID, Caption, DurForc, CtgID, Placing FROM epg_clips WHERE ID='.$x['NativeID']);
            break;

        case 8: // note
        case 9: // space

            $x['NOTE'] = qry_assoc_row('SELECT ID, Note, NoteType FROM epg_notes WHERE ID='.$x['NativeID']);
            break;

        case 12: // film
        case 13: // serial

            $x['FILM'] = epg_film_reader($x['NativeID']);
            $x['PRG'] = qry_assoc_row('SELECT * FROM epg_scnr WHERE ID='.$x['FILM']['ScnrID']);
            break;

        case 14: // linker

            $x['LINK'] = element_reader($x['NativeID']);

            $x['DurForc'] = $x['LINK']['DurForc'];

            $x['PRG']['MatType'] = $x['LINK']['PRG']['MatType'];

            if ($x['EPG']['ChannelID']==$x['LINK']['EPG']['ChannelID'] &&
                strtotime($x['TermEmit'])>strtotime($x['LINK']['TermEmit'])) {
                $x['PRG']['MatType'] = 3;
            }

            break;
    }

    if (in_array($x['NativeType'], [1,12,13])) {	// prg, film, serial

        /* PARENTAL label configuration.
         * Normally we read it from film_description. Otherwise, we have to add PARENTAL input textbox
         * throughout the MDF scripts. (Which will automatically be done if we change config)
         */

		if (in_array($x['NativeType'], [12,13]) && $cfg['lbl_parental_filmbased']) {
            $x['Parental'] = @$x['FILM']['Parental'];
        }

		if (@!$x['Parental']) $x['Parental'] = '';


        // epg TIES (premiere-rerun links)
		if (!$x['EPG']['IsTMPL']) $x['TIE'] = epg_tie($x, 'get');
	}

    $x['SCNRID'] = (!in_array($x['NativeType'], [12,13])) ? $x['NativeID'] : $x['FILM']['ScnrID'];

	return $x;
}




/**
 * Wraper for FRAGMENT reader. Uses GET variables or default values to call fragment_reader() function.
 * The only purpose of this function is to avoid repeating this code in several places where the fragment reader is called.
 * (it's the same logic as for the element reader, which is above)
 *
 * @return array $x Fragment array
 */
function fragment_reader_front() {

	$id 		= 	(isset($_GET['id']))        ? wash('int', $_GET['id']) 			: 0; // for MDF and DTL
    $scnrid	    = 	(isset($_GET['scnr'])) 	    ? wash('int', $_GET['scnr'])		: 0; // only for NEW
	$qu 		= 	(isset($_GET['qu']))        ? wash('int', $_GET['qu']) 			: 0; // only for NEW
	$linetyp 	= 	(isset($_GET['linetyp']))   ? wash('int', $_GET['linetyp']) 	: 0; // only for NEW

	$x = fragment_reader($id, $scnrid, $qu, $linetyp);

	return $x;
}



/**
 * FRAGMENT reader.
 *
 * Fetches data about specified fragment id, or if it is NEW FRAGMENT then simply copies attributes into array
 * and adds few default empty time values.
 *
 * @param int $id Fragment ID. The only attribute which we send, if it is MODIFY or DETAILS page.
 * @param int $scnrid SCNRID. (only for NEW) If it is NEW page, then we don't have element ID, but instead we send this
 *                    and the following two attributes.
 * @param int $qu Queue (only for NEW)
 * @param int $linetyp Line type. (only for NEW)
 * @param string $rtyp Return Type: (normal, import)
 *     - normal: Default
 *     - import: Called only in IFRAME-IMPORT-SCN situation (epg_iframe). Returns simplified (shortened) data.
 * @return array $x Fragment array
 */
function fragment_reader($id, $scnrid=0, $qu=0, $linetyp=0, $rtyp='normal') {

    if ($id) {
        $x = qry_assoc_row('SELECT * FROM epg_scnr_fragments WHERE ID='.$id);
    }
    // epg_scnr_fragments: ID, ScnrID, NativeType, NativeID, Queue, TimeAir, DurForc, IsActive, TermEmit

    $x['ID'] = intval(@$x['ID']);


	if ($rtyp=='import') { // scnr import - returns shorter array: ID, Caption, NativeType, IsActive

		if ($x['NativeID']) {

            switch ($x['NativeType']) {

                case 2: // story
                    $z = qry_assoc_row('SELECT ID, Caption FROM stryz WHERE ID='.$x['NativeID']);
                    break;

                case 3: // mkt
                case 4: // prm
                    $z = qry_assoc_row('SELECT ID, Caption FROM epg_blocks WHERE ID='.$x['NativeID']);
                    break;

                case 5: // clp
                    $z = qry_assoc_row('SELECT ID, Caption FROM epg_clips WHERE ID='.$x['NativeID']);
                    break;

                case 7: // live
                case 8: // note
                case 9: // space
                case 10: // segment
                    $z = qry_assoc_row('SELECT ID, Note AS Caption FROM epg_notes WHERE ID='.$x['NativeID']);
                    break;
            }

		} else {

			$z = ['ID' => 0, 'Caption' => ''];
		}

		$z['NativeType'] 	= $x['NativeType'];
		$z['IsActive'] 		= $x['IsActive'];

		return $z;
	}


	if (!$x['ID']) { // For NEW.. But we don't RETURN right after. We need to fetch SCNR data first.

		$x['ScnrID'] 	    = $scnrid;
		$x['Queue'] 		= $qu;
		$x['NativeType'] 	= $linetyp;

		$x['TimeAirTXT'] 	= t2boxz('', 'time', '');
		$x['DurForcTXT'] 	= t2boxz('', 'time');
	}

	if (!$x['ScnrID']) {
	    return null; // Fragment doesnot exist. Maybe it was deleted between two sql queries. We want to avoid error logs.
    }

	$x['SCNR'] = qry_assoc_row('SELECT * FROM epg_scnr WHERE ID='.$x['ScnrID']);

    $elm = scnr_elm_native($x['SCNR']);

    $x['ELEMENT'] = qry_assoc_row('SELECT ID, EpgID, NativeType, DurForc, IsActive, TermEmit FROM epg_elements '.
        'WHERE NativeType='.$elm['NativeType'].' AND NativeID='.$elm['NativeID']);

	if ($x['ELEMENT']['EpgID']) {
        $x['EPG'] = qry_assoc_row('SELECT DateAir, ChannelID FROM epgz WHERE ID='.$x['ELEMENT']['EpgID']);
    }
    // I need this in: _rcv_epg_single.php, _rcv_epg_multi.php


	if (!$x['ID']) return $x; // this is the point where we return if this is NEW


	if (!strtotime($x['TimeAir'])) $x['TimeAir'] = '';
	//if ($x['TimeAir']=='0000-00-00 00:00:00') $x['TimeAir'] = '';
	//if ($x['TimeAir']=='00:00:00') $x['TimeAir'] = '';

	$x['TimeAirTXT'] = t2boxz($x['TimeAir'], 'time', '');
	$x['DurForcTXT'] = t2boxz($x['DurForc'], 'time');

    switch ($x['NativeType']) {

        case 2: // story
            $x['STRY'] = qry_assoc_row('SELECT ID, Caption, DurForc, Phase, UID FROM stryz WHERE ID='.$x['NativeID']);
            $x['STRY']['DurCalc'] = $x['STRY']['ID'] ? epg_durcalc('story', $x['STRY']['ID']) : null;
            break;

        case 3: // mkt
        case 4: // prm
            $x['BLC'] = qry_assoc_row('SELECT ID, Caption, DurForc FROM epg_blocks WHERE ID='.$x['NativeID']);
            $x['BLC']['DurCalc'] = epg_durcalc('block', $x['BLC']['ID']);
            break;

        case 5: // clp
            $x['CLP'] = qry_assoc_row('SELECT ID, Caption, DurForc, CtgID, Placing FROM epg_clips WHERE ID='.$x['NativeID']);
            break;

        case 7: // live
        case 8: // note
        case 9: // space
        case 10: // segment
            $x['NOTE'] = qry_assoc_row('SELECT ID, Note, NoteType FROM epg_notes WHERE ID='.$x['NativeID']);
            break;
    }

	return $x;
}









/**
 * Reads specified FRAGMENT from a SPICE BLOCK, to be used in EPG/SCNR lists
 *
 * @param int $id epg_cn_blocks:ID
 * @return array $x Spice block fragment data
 */
function epg_spice_reader($id) {

	$x = qry_assoc_row('SELECT ID, NativeID, NativeType, Queue, IsActive FROM epg_cn_blocks WHERE ID='.$id);

	switch ($x['NativeType']) {

		case 3:	$tbl = 'epg_market'; break;
		case 4:	$tbl = 'epg_promo'; break;
		case 5:	$tbl = 'epg_clips'; break;
	}

	$clnz = 'DurForc, Caption'.(($x['NativeType']==3) ? ', IsGratis' : '');

	$z = qry_assoc_row('SELECT '.$clnz.' FROM '.$tbl.' WHERE ID='.$x['NativeID']);

	$x = array_merge($x, $z);

	return $x;
}






/**
 * Reads specified FRAGMENT, i.e. ATOM, from a STORY, to be used in SCNR lists
 *
 * @param int $id stry_atoms:ID
 * @return array $x Story atom data
 */
function epg_story_reader($id) {

	$x = qry_assoc_row('SELECT ID, TypeX, Duration AS DurForc, Queue, TechType FROM stry_atoms WHERE ID='.$id);

	$x['NativeType'] = 20;

	return $x;
}





/**
 * Constructs array with ALL elements of specified epg. Provides CURRENT data for CURRENT-MODIFY comparison in receiver.
 *
 * @param int $epg EPG ID
 * @param string $rtyp Return type (dtl, rcv)
 *
 * @return array $x All elements in epg.
 */
function epg_elements_arr($epg, $rtyp='dtl') {

	$result = qry('SELECT ID FROM epg_elements WHERE EpgID='.$epg.' ORDER BY Queue');
	while (list($id) = mysqli_fetch_row($result)) {
        $x[$id] = element_reader($id, $rtyp);
    }

	if (isset($x)) {
        return $x;
    }
}


/**
 * Constructs array with ALL fragments of specified scnr. Provides CURRENT data for CURRENT-MODIFY comparison in receiver.
 *
 * @param int $scnrid SCNRID
 * @return array $x All fragments in scnr.
 */
function epg_fragments_arr($scnrid) {

	$result = qry('SELECT ID FROM epg_scnr_fragments WHERE ScnrID='.$scnrid.' ORDER BY Queue');
	while (list($id) = mysqli_fetch_row($result)) {
        $x[$id] = fragment_reader($id);
    }

	if (isset($x)) {
        return $x;
    }
}
































/**
 * Deletes EPG ELEMENT(s) or SCNR_FRAGMENT(s)
 *
 * @param string $epgtyp EPG type: (epg, scnr) - Whether this is element or scnr_fragment deleting situation
 * @param int $target_id Target ID (Depending on the $epgtyp and $scope, this can be epg_elements.ID, epgz.ID,
 *        epg_scnr_fragments.ID or epg_scnr.ID/epg_elements.NativeID -- see below)
 * @param string $scope Deletion scope: target (delete target and its descendants) | descend (delete descendants
 *        only - btw. descendants can only be FRAGMENTS)
 * @param string $filter inactives | all ...Whether to delete ALL descendants or only INACTIVES.. (for *descend* scope only)
 * @param bool $tmpl Whether this is a TEMPLATE situation or not
 *
 * @return void
 */
function epg_deleter($epgtyp, $target_id, $scope='target', $filter='inactives', $tmpl=false) {


	if ($epgtyp=='epg') {

		$tbl = 'epg_elements';
        $cln = 'EpgID';

	} else { // scnr

		$tbl = 'epg_scnr_fragments';
        $cln = 'ScnrID';
	}


	if ($scope=='target') { // delete specified ELEMENT / SCNR_FRAGMENT (Target is epg_elements.ID / epg_scnr_fragments.ID)

		$x = rdr_row($tbl, (($epgtyp=='scnr') ? 'ScnrID,' : '').'NativeID, NativeType', $target_id);

        if (!defined('PMS_EPG_DELETER')) {
            pms('epg', 'mdf_single', $x, true);
        }

		qry('DELETE FROM '.$tbl.' WHERE ID='.$target_id, ['x_id' => $target_id]); // delete the target

		/////////// Delete dependencies

        // Notes
        if (in_array($x['NativeType'], [7,8,9,10])) {
            qry('DELETE FROM epg_notes WHERE ID='.$x['NativeID']);
        }

        // Tips
        qry('DELETE FROM epg_tips WHERE SchType='.(($epgtyp=='epg') ? 1 : 2).' AND SchLineID='.$target_id);

        // CVR - for element (prog/film)
        if (in_array($x['NativeType'], [1,12,13])) {
            qry('DELETE FROM epg_coverz WHERE OwnerType=1 AND OwnerID='.$target_id);
        }

        // For STORIES..
        if ($x['NativeType']==2) {

            // Update (cut) epg-stry conn
            receiver_upd_short('stryz', ['ScnrID' => 0, 'ProgID' => 0], $x['NativeID']);

            // Delete TIPS associated with atoms
            $atomz = qry_numer_arr('SELECT ID FROM stry_atoms WHERE StoryID='.$x['NativeID']);
            if ($atomz) {
                foreach ($atomz as $v) {
                    qry('DELETE FROM epg_tips WHERE SchType=3 AND SchLineID='.$v);
                }
            }
        }

        if ($x['NativeType']==3) {
            qry('DELETE FROM epg_market_siblings WHERE MktepgType='.(($epgtyp=='epg') ? 1 : 2).' AND MktepgID='.$target_id,
                ['x_id' => $target_id]);
        }

        if ($tmpl && $x['NativeType']==1) {

            $progid = rdr_cell('epg_scnr', 'ProgID', $x['NativeID']);

            if ($progid) {
                qry('UPDATE prgm_settings SET EPG_TemplateID=null WHERE ID='.$progid);
            }
        }

    } else { // $scope=='descend'

		// EPG:  delete all ELEMENTS in specified EPG (Target is epgz.ID)
		// SCNR: delete all SCNR_FRAGMENTS of specified ScnrID (Target is epg_scnr.ID/epg_elements.NativeID)

		if ($epgtyp=='epg') {

            pms('epg', 'mdf_full', null, true);

        } else { // scnr

            $x['NativeID'] = $target_id;
            $x['NativeType'] = 1;
            $x['PRG']['ProgID'] = rdr_cell('epg_scnr', 'ProgID', $target_id);
            pms('epg', 'mdf_full', $x, true);

            if (!defined('PMS_EPG_DELETER')) {
                define('PMS_EPG_DELETER', true);
            }
        }

        // get the list of elements / scnr_fragments
		$result = qry('SELECT ID FROM '.$tbl.' WHERE '.(($filter=='inactives') ? '!IsActive AND ' : '').$cln.'='.$target_id);

		// delete one by one element / scnr_fragment, by specifically targeting it
        // (thus making sure the descendants / dependencies will be purged too)
		while (list($id) = mysqli_fetch_row($result)) {
            epg_deleter($epgtyp, $id, 'target');
        }
	}


	if ($epgtyp=='epg') {

		if ($scope=='target') {

			/////////// delete descendants

			// prog element
			if ($x['NativeType']==1) {

				qry('DELETE FROM epg_scnr WHERE ID='.$x['NativeID']); // delete the epg_scnr row of this prog element

				epg_deleter('scnr', $x['NativeID'], 'descend', 'all');	// delete the descendants (fragments) of this prog element

				mos_deleter($x['NativeID'], $x['NativeType']);
				crw_deleter($x['NativeID'], $x['NativeType']);

                qry('DELETE FROM stry_followz WHERE ItemType=1 AND ItemID='.$x['NativeID']);

                qry('UPDATE stryz SET ScnrID=0 WHERE ScnrID='.$x['NativeID']); // update epg-stry conn
            }

			// film
			if (in_array($x['NativeType'], [12,13])) {

				$scnrid = rdr_cell('epg_films', 'ScnrID', $x['NativeID']); // first read the scnrid from epg_films

				qry('DELETE FROM epg_films WHERE ID='.$x['NativeID']); // then we can delete the line in epg_films

				qry('DELETE FROM epg_scnr WHERE ID='.$scnrid); // then delete the line in the epg_scnr

				epg_deleter('scnr', $scnrid, 'descend', 'all');	// delete the descendants (fragments) of this prog element
			}

			// prog, film
			if (in_array($x['NativeType'], [1,12,13])) {

                qry('DELETE FROM epg_cn_ties WHERE RerunID='.$target_id);
                qry('DELETE FROM epg_cn_ties WHERE PremiereID='.$target_id);
			}

		} else { // $scope=='descend'

			if ($filter=='all') {
                qry('DELETE FROM epgz WHERE ID='.$target_id, ['x_id' => $target_id]);    // delete the epg itself
            }
		}
	}

}



/**
 * Refresh/rebuild QUEUE column for elements of the specified EPG (it tends to fall out of *consequtive* order when
 * deleting elements, because Queue values of elements which follow are not updated)
 *
 * @param int $epgid EPG ID
 * @return void
 */
function epg_qu_refresh($epgid) {

    $cnt = 0;

    $result = qry('SELECT ID, Queue FROM epg_elements WHERE EpgID='.$epgid.' ORDER BY Queue');

    while ($line = mysqli_fetch_assoc($result)) {

        if ($line['Queue']!=$cnt) {
            qry('UPDATE epg_elements SET Queue='.$cnt.' WHERE ID='.$line['ID']);
        }

        $cnt++;
    }
}



















/**
 * Gets the zero term for the epg
 *
 * @param string $dateair DateAir of the EPG
 * @return string $r Zero term, in DATETIME format
 */
function epg_zeroterm($dateair) {

	global $cfg;

	$r = $dateair.' '.$cfg['zerotime'];

	return $r;
}





/**
 * Calculates TimeAir, i.e. START TERM, for the element which doesn't have TimeAir (i.e. no fixed term) set.
 *
 * (No need to use this fn on an element which already has TimeAir!)
 *
 * @param int $x_epg EPG ID
 * @param int $x_qu Queue of the current element
 * @param string $dateair EPG DateAir (used only for zero-time), in DATE format
 * @param string $termemit TermEmit
 *
 * @return string $r_term Calculated TimeAir
 */
function element_start_calc($x_epg, $x_qu, $dateair='', $termemit='') {

    /* vbdo
    This fn was written before TermEmit column came into place. Having this column perhaps turns this fn obsolete..
    I have added the $termemit attribute to check whether using TermEmit goes well..
    If it does, then this fn can be deleted..
    */

    if ($termemit) {
        return $termemit;
    }


    // Go backwards and find first element which has TIMEAIR

	$sql = 'SELECT ID, TimeAir, Queue FROM epg_elements'.
		   ' WHERE EpgID='.$x_epg.' AND IsActive=1 AND Queue<'.$x_qu.
           ' ORDER BY Queue DESC';
	$result = qry($sql);

	while ($line = mysqli_fetch_assoc($result)) {

		if ($line['TimeAir'] && strtotime($line['TimeAir'])) {

			$start_term = $line['TimeAir'];
			$start_qu 	= $line['Queue'];

			break;
		}
	}


	// If we didnot find any TERMAIR then use ZEROTIME

	if (!isset($start_term)) {

		$start_term = epg_zeroterm($dateair);
		$start_qu 	= 0;
	}


	// Loop elements between, and add all DURs to START-TERM, in order to calculate the TERM-AIR of current alement

	$r_term = $start_term;

	$sql =  'SELECT ID FROM epg_elements'.
	    ' WHERE EpgID='.$x_epg.' AND IsActive=1 AND Queue<'.$x_qu.' AND Queue>='.$start_qu.
        ' ORDER BY Queue ASC';
	$result = qry($sql);

	while (list($elm_id) = mysqli_fetch_row($result)) {

		$dur = epg_durations(element_reader($elm_id), 'hms', 'var'); // Duration

		$r_term = add_dur2term($r_term, $dur);
	}


	return $r_term;
}






/**
 * Calculates summary duration (in TIME format) of all fragments which belong to specified element
 *
 * Collects durations for all elements/fragments and then at the end calls sum_durs() function to sum them.
 *
 * @param string $typ Element type: (prog, block, story)
 * @param int $id NativeID
 * @param array $opt
 * - mosdur (string) MOS duration (used only for *prog* $typ where MatType!=1, i.e. for progs which are not liveair)
 * - block_clips_only (bool) Whether to calculate only duration of *clips* in block (used only for *block* type)
 *
 * @return string Summary duration in TIME format (hh:mm:ss)
 */
function epg_durcalc($typ, $id, $opt=null) {

	$durz = []; // array will hold durations of all fragments

	switch ($typ) {


        case 'prog':

            $where = [];
            $where[] = 'IsActive=1';
            $where[] = 'ScnrID='.$id;

            // (For RECORDED progs, as only they can have MOSdur)
            // If MOSdur is not null, then take it into account but disregard all frags except mkt and prm and holes
            // This is because even progs of *recorded* type may also be prepared in prodesk, and later when the record
            // is ready, the editor inputs MOSdur into prodesk. After that point we don't want to take news dur
            // into prog dur calculation.. But we want to incalculate dur of mkt/prm blocks, as they are added later, i.e.
            // they are not included in MOSdur of a prog..
            // Note: search "REC-PROG"
            if (isset($opt['mosdur'])) {
                $durz[] = $opt['mosdur'];
                $where[] = 'NativeType IN (3,4,9)';
            }

            $result = qry('SELECT NativeID, NativeType, DurForc FROM epg_scnr_fragments '.
                'WHERE '.implode(' AND ', $where).' ORDER BY Queue ASC');

			while ($x = mysqli_fetch_assoc($result)) {

                switch ($x['NativeType']) {

                    case 2: // stry

                        $x['STRY'] = rdr_row('stryz', 'Phase, DurForc', $x['NativeID']);
                        $x['STRY']['DurCalc'] = epg_durcalc('story', $x['NativeID']);
                        break;

                    case 3: // mkt
                    case 4: // prm

                        $x['BLC']['DurForc'] = rdr_cell('epg_blocks', 'DurForc', $x['NativeID']);
                        $x['BLC']['DurCalc'] = epg_durcalc('block', $x['NativeID']);
                        break;

                    case 5: // clp
                        $x['CLP']['DurForc'] = rdr_cell('epg_clips', 'DurForc', $x['NativeID']);
                        break;
                }

                $durz[] = epg_durations($x, 'hms', 'var');
			}

            break;


		case 'block':

			if (!$id) break;
            // HOLDERS for *blocks* can be used in epgz, i.e. user can add e.g. marketing element without chosing a specific
            // block (if he knows that the marketing block should be there, but he doesn't know which one until later).
            // If this is the case, then we wouldn't have $id here, thus we break.

			$result = qry('SELECT NativeID, NativeType FROM epg_cn_blocks WHERE IsActive=1 AND BlockID='.$id.
                ((!empty($opt['block_clips_only'])) ? ' AND NativeType=5' : ''));

			while ($x = mysqli_fetch_assoc($result)) {

				switch ($x['NativeType']) {
					case 3:		$tbl = 'epg_market'; break;
					case 4:		$tbl = 'epg_promo';  break;
					case 5:		$tbl = 'epg_clips';  break;
				}

				$durz[] = rdr_cell($tbl, 'DurForc', $x['NativeID']);
			}

			break;


		case 'story':

			$durz = rdr_cln('stry_atoms', 'Duration', 'StoryID='.$id);
			break;
	}


	if ($durz) {
        return sum_durs($durz);
    } else {
        return null;
    }
}





/**
 * The function decides which DUR to use for EPG (possible durations are epg-forc, forc or calc). It returns not only
 * the *winner* duration, but also the *loser* duration.
 *
 * @param array $x Data array, which is either element or fragment or block component (item) or story component (atom)
 * @param string $r_format Return time format: (hms, ms)
 * @param string $r_typ Return type: (arr, var)
 *
 * @return array|string	$dur If r_typ is 'var', return string ($dur['winner']['dur']), otherwise return multi array:
 * - 'winner':
 *   - 'dur' (dur in format specified by r_format),
 *   - 'typ' (used for css selecting),
 *   - 'dur_hms' (dur in HMS format, used for calculation of epg-term)
 * - 'loser': 'dur', 'typ', 'dur_html'
 */
function epg_durations($x, $r_format='hms', $r_typ='arr') {

    $dur_forc = '';
    $dur_calc = '';
    $dur_epg = '';


    $is_component = false;

    if (in_array($x['NativeType'], [3,4]) && !isset($x['BLC']))     $is_component = true;  // Block component
    if ($x['NativeType']==5 && !isset($x['CLP']))                   $is_component = true;  // Block component
    if ($x['NativeType']==20)                                       $is_component = true;  // Story component, i.e. atom


    if ($is_component) {

        $dur_calc = milli_check(@$x['DurForc'], 'hms');

        // For components, the only dur type is FORC. We put it into $dur_calc only because the CALC colouring
        // seems more appropriate.

    } else {

        $dur_epg = $x['DurForc']; // epg_elements and epg_scnr_fragments have DurForc column.

        switch ($x['NativeType']) {

            case 1:
            case 14:

                $z = ($x['NativeType']==1) ? $x : $x['LINK'];

                $dur_calc = scnr_durcalc(
                    ['ID' => $z['NativeID'], 'MatType' => $z['PRG']['MatType'], 'NativeType' => 1, 'MOS' => $z['MOS']]
                );

                break;

            case 2:
                $dur_forc = @$x['STRY']['DurForc'];
                $dur_calc = @$x['STRY']['DurCalc'];
                break;

            case 3:
            case 4:
                $dur_forc = @$x['BLC']['DurForc'];
                $dur_calc = @$x['BLC']['DurCalc'];
                break;

            case 5:
                $dur_calc = milli_check(@$x['CLP']['DurForc'], 'hms');
                break;

            case 12:
            case 13:
                $frag_dursum = epg_durcalc('prog', $x['FILM']['ScnrID']);
                $dur_calc = sum_durs([@$x['FILM']['Duration'], $frag_dursum]);
                break;
        }
    }


	if ($dur_epg =='00:00:00') $dur_epg = '';
	if ($dur_forc=='00:00:00') $dur_forc = '';
	if ($dur_calc=='00:00:00') $dur_calc = '';


    $durz = [
        'durepg'  => $dur_epg,   // epg-forc
        'durforc' => $dur_forc,  // forc
        'durcalc' => $dur_calc,  // calc
        'epgempt' => '00:00:00', // empty
    ];


    $durz_winner_order = ['durepg', 'durforc', 'durcalc', 'epgempt']; // Default

    if (in_array($x['NativeType'], [1,14]) && $z['PRG']['IsReady']==1) {
        $durz_winner_order = ['durcalc', 'durepg', 'epgempt'];            // SCNR IsReady correction: CALC precedes FORC
    }

    if ($x['NativeType']==2 && $x['STRY']['Phase']==4) {
        $durz_winner_order = ['durepg', 'durcalc', 'durforc', 'epgempt']; // STRY PHZ-finito correction: CALC precedes FORC
    }

    if (in_array($x['NativeType'], [3,4])) {
        $durz_winner_order = ['durforc', 'durcalc', 'durepg', 'epgempt']; // SPICE BLOCKS: CALC precedes FORC
    }

    foreach ($durz_winner_order as $k => $v) { // Set WINNER value
        if ($durz[$v]) {
            $dur['winner']['typ'] = $v;
            $dur['winner']['dur'] = $durz[$v];
            $durz_order_winner_k = $k; // At least 'epgempt' value is never empty. This var will always be set.
            break;
        }
    }


    if ($r_typ=='var') { // If the return type is VAR, this is where we can jump off safely
        return $dur['winner']['dur'];
    }


    $durz_loser_order = array_slice($durz_winner_order, ($durz_order_winner_k+1), -1);
    // Cut winner key and all keys that precede him (as they cannot be losers).
    // Also cut *epgempt* key from the end, as empty loser values are not to be shown as zeros.

    foreach ($durz_loser_order as $v) { // Set LOSER value
        if ($durz[$v]) {
            $dur['loser']['typ'] = $v;
            $dur['loser']['dur'] = $durz[$v];
        }
    }

    if (!isset($dur['loser'])) {
        $dur['loser']['typ'] = '';
        $dur['loser']['dur'] = '';
    }


    // This value is going to be used for calculation of TERM. It is in HMS format.
	$dur['winner']['dur_hms'] = $dur['winner']['dur'];


	if ($r_format=='ms') {

		$dur['winner']['dur'] = hms2ms($dur['winner']['dur']);

		if ($dur['loser']['dur']) {

            $dur['loser']['dur'] = hms2ms($dur['loser']['dur']);
        }
	}


    // HTML: If loser is empty, we put placeholder.
	$dur['loser']['dur_html'] = ($dur['loser']['dur']) ? $dur['loser']['dur'] : '<span></span>';


	return $dur;
}

















/**
 * Epg TIP receiver (*tip* is a note which is to be displayed in specified epg line)
 *
 * @param array $tip Tip data:
 * - schtyp (string): (Parent) List type: (epg, scnr, stry)
 * - schline (int): Element/Fragment/Atom ID
 * - tiptyp (int): Tip type (1-note, 2-cam)
 *
 * @param string $value New tip value
 *
 * @return void
 */
function epg_tip_receiver($tip, $value) {

    switch ($tip['schtyp']) {
        case 'epg':  $schtyp = 1; break;
        case 'scnr': $schtyp = 2; break;
        case 'stry': $schtyp = 3; break;
        default: exit;
    }

    $tbl = 'epg_tips';

    $cur = epg_tip_reader($tip);

    $mdf['Tip']	= ($value===null) ? wash('cpt', @$_POST['Tip']) : $value;

	if (!$mdf['Tip']) {     // no new value

		if (@$cur['ID']) {                                                          // has current value: delete it
			qry('DELETE FROM '.$tbl.' WHERE ID='.$cur['ID']);
		} else {                                                                    // no current value
			return;
		}

	} else {                // has new value

		if (@$cur['ID']) {                                                          // has current value: update it
			receiver_upd($tbl, $mdf, $cur, LOGSKIP);
		} else {                                                                    // no current value: insert
			$mdf['SchLineID'] = $tip['schline'];
            $mdf['SchType'] = $schtyp;
            $mdf['TipType'] = $tip['tiptyp'];
			receiver_ins($tbl, $mdf, LOGSKIP);
		}
	}

}



/**
 * Epg TIP reader (*tip* is a note which is to be displayed in specified epg line)
 *
 * @param array $tip Tip data:
 * - schtyp (string): (Parent) List type: (epg, scnr, stry)
 * - schline (int): Element/Fragment/Atom ID
 * - tiptyp (int): Tip type (1-note, 2-cam)
 *
 * @return array $r TIP
 */
function epg_tip_reader($tip) {

    switch ($tip['schtyp']) {
        case 'epg':  $schtyp = 1; break;
        case 'scnr': $schtyp = 2; break;
        case 'stry': $schtyp = 3; break;
        default: exit;
    }

    $r = qry_assoc_row(
        'SELECT ID, Tip FROM epg_tips '.
        'WHERE SchType='.$schtyp.' AND SchLineID='.$tip['schline'].' AND TipType='.$tip['tiptyp']
    );

    return $r;
}





/**
 * Epg TIP output
 *
 * @param array $tipdata Tip data:
 * - schtyp (string): (Parent) List type: (epg, scnr, stry)
 * - schline (int): Element/Fragment/Atom ID
 * - tiptyp (int): Tip type (1-note, 2-cam)
 * - editable (bool): Whether to make div ajax-editable
 *
 * @return void
 */
function epg_tip_output($tipdata) {

    $tip = epg_tip_reader($tipdata);

    $arr_tiptyp = [1 => 'note', 'cam', 'vo'];

    if ($tipdata['editable'] || $tip['Tip']) {

        if ($tipdata['editable']) {

            if (TYP=='epg') {

                $pmstyp = 'mdf_epg_tips';

            } else { // scnr

                $pmstyp = 'mdf_scn_tips_'.$arr_tiptyp[$tipdata['tiptyp']];
            }

            $ajaxdata = [
                'tblid' => 29,
                'itemid' => $tipdata['schline'],
                'cln' => $tipdata['schtyp'].'_'.$tipdata['tiptyp'], // We here use *column* attribute to pass sch-type and tip-type
                'pms' => 'epg.'.$pmstyp,
                'valtyp' => 'cpt',
            ];

            $editdivable =
                ' onclick="editdivable_line(this); return false;"'.
                ' data-ctrlcss="" data-ctrlmax="80"'.
                ' data-ajax="'.implode(',', $ajaxdata).'"';

        } else {

            $editdivable = '';
        }

        if (TYP=='epg' && !MDF_TIPS_NOTE && setz_get('epg_notes_hide')) {

            echo '<span class="glyphicon glyphicon-exclamation-sign lblelnote pull-right hidden-print" '.
                'data-toggle="tooltip" data-placement="left" title="'.$tip['Tip'].'"></span>';

        } else{

            echo
                '<div class="lblnoter lbl_left tip'.$arr_tiptyp[$tipdata['tiptyp']].(($tipdata['editable']) ? ' mdf' : '').'">'.
                    '<div'.$editdivable.'>'.(($tip) ? $tip['Tip'] : '').'</div>'.
                '</div>';
        }
    }
}



/**
 * Handles all operations with epg ties (premiere-rerun links)
 *
 * @param array $x Element array/object
 * @param string $action Action type: (add, get, del, list, upd)
 * @return mixed Depends on the ACTION type. Only LIST and GET actions expect return.
 */
function epg_tie($x, $action) {

	switch ($action) {

        // ADD is triggered only from EPG_DETAILS (epg_dtl_html() function)
        // LIST can come only from MDF_SINGLE. That's where we display radio input list so the user can
        // change the tie which was normally made automatically by ADD trigger.
		case 'add':
		case 'list':

			if ($x['NativeType']==1) { // PROG

                // find elements of same linetype, and same program, and not reruns and broadcasted BEFORE this one.

				$sql = 'SELECT epg_elements.ID '.
                    'FROM epg_elements INNER JOIN epg_scnr ON epg_elements.NativeID = epg_scnr.ID '
					.'WHERE epg_elements.NativeType='.$x['NativeType'].
                    ' AND epg_scnr.ProgID='.$x['PRG']['ProgID'].
                    ' AND epg_scnr.MatType!=3'.
                    ' AND epg_elements.TermEmit<\''.$x['TermEmit'].'\' ';

			} else {	// FILM, SERIAL (12,13)

                // find elements of same linetype, and same FilmID, and not reruns and broadcasted BEFORE this one.

				$sql = 'SELECT epg_elements.ID '.
                    'FROM epg_elements INNER JOIN epg_films ON epg_elements.NativeID = epg_films.ID'.
                    ' INNER JOIN epg_scnr ON epg_films.ScnrID = epg_scnr.ID '
					.'WHERE epg_elements.NativeType='.$x['NativeType'].
                    ' AND epg_films.FilmID='.$x['FILM']['FilmID'].
                    ' AND epg_scnr.MatType!=3'.
                    ' AND epg_elements.TermEmit<\''.$x['TermEmit'].'\' ';
			}

            // only within epgz: of the same channel, not templates, belonging to the same date or previous 6 dates.

            $sql .= 'AND epg_elements.EpgID IN '.
                '(SELECT ID FROM epgz '.
                'WHERE ChannelID='.$x['EPG']['ChannelID'].
                ' AND IsTMPL=0'.
                ' AND DateAir<=\''.$x['EPG']['DateAir'].'\''.
                ' AND DateAir >= DATE_SUB(\''.$x['EPG']['DateAir'].'\', INTERVAL 6 DAY) '.
                'ORDER BY DateAir DESC) '; // it is not actually important how we order epgz, but anyway..

			$sql .= 'ORDER BY epg_elements.TermEmit DESC'; // we order elements by term of broadcast

            // LIST needs all elements, so the user could chose one of them, but ADD needs just the last one
			if ($action=='add') {
				$sql .= ' LIMIT 1';
            }


			if ($action=='add') {

                $r = qry_numer_var($sql);

				qry('INSERT INTO epg_cn_ties (PremiereID, RerunID) VALUES ('.intval($r).','.$x['ID'].')');

				/* We use intval(), in order to insert 0 instead of NULL if there are no matches. This is important
				 * because ADD initialization looks for integer values as a precondition, i.e. if we save a tie
				 * with zero value (for premiere), that will TURN OFF further ADD initialization, which is what we want.
				 * ADD init code is in epg_dtl_html(), i.e. it would be triggered each time by simple epg viewing,
				 * and we only want to trigger it once. (If it finds nothing, then we save zero tie, to point out that
				 * further attempts are not necessary.)
				 */

                return null;

			} else { // list

				$r = [];
				$result = qry($sql);
				while ($z = mysqli_fetch_row($result)) {
                    $r[$z[0]] = rdr_cell('epg_elements', 'TermEmit', $z[0]);
                }
				return $r;
			}


		case 'get':

            // if this is RERUN, look for premiere. Otherwise, look for rerun
			if ($x['PRG']['MatType']==3) {
                $x['TIE']['ID'] = rdr_cell('epg_cn_ties', 'PremiereID', 'RerunID='.$x['ID']);
            } else {
                $x['TIE']['ID'] = rdr_cell('epg_cn_ties', 'RerunID', 'PremiereID='.$x['ID']);
            }

            if ($x['TIE']['ID']) { // TIE_ID can be 0, so we have to check before really filling it with data.
				$x['TIE'] = qry_assoc_row('SELECT ID, EpgID, TermEmit FROM epg_elements WHERE ID='.$x['TIE']['ID']);
				$x['TIE']['Type'] = ($x['PRG']['MatType']==3) ? 'r' : 'p'; // rerun | premiere
			}

			return $x['TIE'];


		case 'del':
			qry('DELETE FROM epg_cn_ties WHERE RerunID='.$x['ID']);
            return null;


        // UPD can come only from MDF_SINGLE receiver, because only in MDF_SINGLE we display LIST (radio input)
		case 'upd':
			qry('UPDATE epg_cn_ties SET PremiereID='.intval($_POST['Premiere']).' WHERE RerunID='.$x['ID']);
            return null;

        // We don't have to use "break;" at the end of case, because each case ends with RETURN.

	}

    return null; // not really necessary because we cannot get here, but anyway.. just to make phpstorm happy..
}











/**
 * Explodes program caption, for the programs which don't have selected ID
 *
 * @param string $prgcpt Caption
 * @return array $r Title(0) and Theme(1)
 */
function prgcpt_explode($prgcpt) {


    $div_pos = strpos($prgcpt, '|');
    if (!$div_pos) $div_pos = strpos($prgcpt, '~'); // if there isn't any '|', then also try '~'


    if ($div_pos) {

        // if there IS a divider, we use first part as the program name, second part as the program theme

        $r[0] = substr($prgcpt, 0, $div_pos);   // first part
        $r[1] = substr($prgcpt, $div_pos+1);    // second part

    } else {

        // if there IS NO divider, we use only program name, and we leave program theme empty.

        $r[0] = $prgcpt;
        $r[1] = '';
    }

    return $r;
}




/**
 * Get program caption
 *
 * @param int $progid Program ID (epg_scnr.ProgID)
 * @param string $progcpt Program caption, as saved in DB (epg_scnr.Caption) - it can contain subcaption
 * @param string $rtyp Return type (var, arr); If we need to return not only caption, but subcaption too, use *arr*.
 * @return string|array $cpt Either program caption only, or array with caption and subcaption. Depends on $rtyp.
 */
function prgcpt_get($progid, $progcpt, $rtyp='var') {

/* When it is some kind of a special program, which doesn't exist in programs table, then the user will not
 * select the program (from prog cbo), but instead just write the PROGRAM NAME in the TITLE input field.
 * This will be used as the program name. If the user also wants to add the regular TITLE/THEME,
 * then he will put that together with the program name, but separate it with the '|' or '~'.
 */

    if ($progid) {

        // If we have Program ID, that means the program was selected in the cbo.
        // in that case, fetch program caption.

        $cpt = prg_caption($progid);
        $subcpt = '';

    } else {

        // If we do not have Program ID, that means this is not one of the programs we have in db. The caption for the
        // program was saved in epg_scnr.Caption, together with subcaption. We use prgcpt_explode() to get the caption.

        list($cpt, $subcpt) = prgcpt_explode($progcpt);
    }

    if ($rtyp=='var') {

        return $cpt;

    } else {

        return [$cpt, $subcpt];
    }
}





/**
 * Get scnr (element of prog/film/serial type) caption
 *
 * @param int $element_id Element ID (epg_elements.ID)
 * @param string $rtyp Return type (arr, cpt_only)
 *
 * @return array $cpt Array with caption and other data that could be used for caption text and link.
 */
function scnr_cpt_get($element_id, $rtyp='cpt_only') {

    if (!$element_id) {
        return null;
    }


    $x['ELEMENT'] = rdr_row('epg_elements', 'ID, NativeID, NativeType, TermEmit', $element_id);

    $r = [
        'ID' => $x['ELEMENT']['ID'],
        'NativeType' => $x['ELEMENT']['NativeType'],
        'TermEmit' => $x['ELEMENT']['TermEmit']
    ];

    if (!empty($r['TermEmit'])) {
        $t = strtotime($r['TermEmit']);
        $r['TermEmit'] = date('Y-m-d H:i', $t);
        $r['TermEmit_hhmm'] = date('H:i', $t);
    }


    if ($x['ELEMENT']['NativeType']==1) { // prog

        $x['SCNR'] = rdr_row('epg_scnr', 'ProgID, Caption', $x['ELEMENT']['NativeID']);

        $r['Caption'] = prgcpt_get($x['SCNR']['ProgID'], $x['SCNR']['Caption']);

    } else { // film

        $x['EPGFILM'] = rdr_row('epg_films', 'ID, FilmID, FilmParentID', $x['ELEMENT']['NativeID']);

        $r['Caption'] = rdr_cell('film_description','Title',
                (($x['ELEMENT']['NativeType']==12) ? $x['EPGFILM']['FilmID'] : $x['EPGFILM']['FilmParentID']));
    }

    return (($rtyp=='arr') ? $r : $r['Caption']);
}




/**
 * Get queue value to be used for new fragment in scnr
 *
 * @param int $scnrid SCNR ID
 * @param string $typ Type (max, ordinal)
 * @param string $term Term (used only for type *ordinal*)
 *
 * @return int $qu Queue value to be used for new fragment in scnr
 */
function scnr_get_qu($scnrid, $typ='max', $term=null) {

    if ($typ=='max') {

        $qu_max = rdr_cell('epg_scnr_fragments', 'MAX(Queue)', 'ScnrID='.$scnrid);
        $qu = (int)$qu_max + 1;

    } else { // ordinal

        $qu = rdr_cell('epg_scnr_fragments', 'Queue', 'ScnrID='.$scnrid.' AND TermEmit>\''.$term.'\'', 'TermEmit ASC');

        // If cannot find any row with larger TermEmit returns null, which is int(0) and would therfore jump to
        // first position in scnr
        if ($qu===null) {
            $qu = scnr_get_qu($scnrid);
        } else {
            $qu = (int)$qu;
        }
    }

    return $qu;
}




/**
 * Get program id for a scnr
 *
 * @param int $scnrid SCNR ID
 *
 * @return int $progid Prgm ID
 */
function scnr_get_progid($scnrid) {

    $progid = rdr_cell('epg_scnr', 'ProgID', $scnrid);

    return (int)$progid;
}




/**
 * Read SCNR data and fetch additional data, necessary for duremit/termit procedure
 *
 * @param int $scnrid epg_scnr.ID
 * @return array $x SCNR data, including ElementID and NativeType
 */
function scnr_reader($scnrid) {

    $x = qry_assoc_row('SELECT * FROM epg_scnr WHERE ID='.$scnrid);

    $elm = scnr_elm_native($x, ['r_elmid' => true]);

    $x['ElementID'] = $elm['ElementID'];
    $x['NativeType'] = $elm['NativeType'];

    return $x;
}



/**
 * From some SCNR data get some ELEMENT data - Native Type/ID for epg_elements.
 *
 * @param array $scnr SCNR array (epg_scnr:ID,IsFilm)
 * @param array $opt
 * - r_film (bool) - Whether to return FILM array (which we anyway get in the process of fetching Native Type/ID)
 * - r_elmid (bool) - Whether to return ElementID
 *
 * @return array $r Element data (epg_elements:NativeType,NativeID; also epg_elements:ID if *r_elmid*)
 */
function scnr_elm_native($scnr, $opt=null) {

    if (!isset($opt['r_film']))  $opt['r_film'] = false;
    if (!isset($opt['r_elmid'])) $opt['r_elmid'] = false;


    if (!$scnr['IsFilm']) { // PROG

        $r['NativeType'] = 1;
        $r['NativeID'] = $scnr['ID'];

    } else { // FILM

        $epgfilm = rdr_row('epg_films', 'ID, FilmID, FilmParentID', 'ScnrID='.$scnr['ID']);

        $r['NativeType'] = ($epgfilm['FilmParentID']) ? 13 : 12;
        $r['NativeID'] = $epgfilm['ID'];
    }

    if ($opt['r_film'] && $scnr['IsFilm']) {
        $r['EPGFILM'] = $epgfilm;
    }

    if ($opt['r_elmid']) {
        $r['ElementID'] = rdr_cell('epg_elements', 'ID', 'NativeType='.$r['NativeType'].' AND NativeID='.$r['NativeID']);
    }

    return $r;
}





/**
 * Get SCNR element ID from SCNR ID
 *
 * @param int $scnrid SCNR ID (epg_elements.NativeID, i.e. epg_scnr.ID, i.e. epg_scnr_fragments.ScnrID)
 * @param int $nattyp Native type
 *
 * @return int $elmid SCNR element ID (epg_elements.ID)
 */
function scnr_id_to_elmid($scnrid, $nattyp=1) {

    $elmid = rdr_cell('epg_elements', 'ID', 'NativeType='.$nattyp.' AND NativeID='.$scnrid);

    return $elmid;
}



/**
 * Get SCNR ID from SCNR element ID (ONLY for PROG!! Procedure to get film/serial ScnrID is different.)
 *
 * @param int $elmid SCNR element ID (epg_elements.ID)
 *
 * @return int $scnrid SCNR ID (epg_elements.NativeID, i.e. epg_scnr.ID)
 */
function scnrid_prog($elmid) {

    $scnrid = rdr_cell('epg_elements', 'NativeID', $elmid);

    return $scnrid;
}



/**
 * Get SCNR ID from element NativeID and NativeType (For both progs and films/serial)
 *
 * @param int $natid Element NativeID
 * @param int $nattyp Element NativeType
 *
 * @return int $scnrid SCNR ID (epg_scnr.ID)
 */
function scnrid_universal($natid, $nattyp) {

    $scnrid = ($nattyp==1) ? $natid : rdr_cell('epg_films', 'ScnrID', $natid);

    return $scnrid;
}



/**
 * Get Channel ID from SCNR ID
 *
 * @param int $scnrid SCNR ID (epg_scnr.ID)
 *
 * @return int $chnlid Channel ID
 */
function chnlid_from_scnrid($scnrid) {

    $chnlid = 0;

    if ($scnrid) {
        $elmid = scnr_id_to_elmid(intval($scnrid));
        if ($elmid) {
            $epgid = rdr_cell('epg_elements', 'EpgID', $elmid);
            if ($epgid) {
                $chnlid = rdr_cell('epgz', 'ChannelID', $epgid);
            }
        }
    }

    if (!$chnlid) {
        $chnlid = CHNL;
    }

    return $chnlid;
}



/**
 * Get EPG ID from date
 *
 * @param string $datex Date (Y-m-d)
 * @param int $chnl Channel ID
 *
 * @return int $epgid EPG ID (epgs.ID)
 */
function epgid_from_date($datex, $chnl) {

    $t = strtotime($datex);

    $ymd = date('Y-m-d', $t);

    $epgid = rdr_cell('epgz', 'ID', 'IsTMPL=0 AND DateAir=\''.$ymd.'\' AND ChannelID='.$chnl);

    $epgid = intval($epgid);

    return $epgid;
}





/**
 * Strings caption for LINK
 *
 * @param array $x Element
 * @return string $r
 */
function epg_link_cpt($x) {

    if ($x['EpgID']==$x['LINK']['EpgID']) {
        $link_term = date('H:i:s', strtotime($x['LINK']['TermEmit']));
    } else {
        $link_term = $x['LINK']['TermEmit'];
    }

    if ($x['EPG']['ChannelID']!=$x['LINK']['EPG']['ChannelID']) {
        $link_chnl = '<span class="label label-primary channel">'.
            channelz(['id' => $x['LINK']['EPG']['ChannelID']], true).'</span> ';
    } else {
        $link_chnl = '';
    }

    $r = $link_chnl.'<span class="glyphicon glyphicon-link"></span> '.
        '<span class="cpt">'.$x['LINK']['PRG']['ProgCPT'].'</span>'.
        (($x['LINK']['PRG']['Caption']) ? ' - '.$x['LINK']['PRG']['Caption'] : '').
        ' ('.$link_term.')';

    return $r;
}








/**
 * Gets epg subsection title, to be used in navigation bar
 *
 * @param array $x Element/Fragment array
 * @return string $title Subsection title
 */
function epg_get_subscn_title($x) {

    $tmpl = (defined('TMPL') ? TMPL : @$x['EPG']['IsTMPL']);

    if (!$tmpl) {

        if (!@$x['EPG']['DateAir']) { // NEW EPG
            $x['EPG']['DateAir'] = EPG_NEW;
        }

        $real_dateair = get_real_dateair();

        if ($x['EPG']['DateAir']==$real_dateair) {
            $title = 'real';
        } else {
            if ($x['EPG']['DateAir'] < $real_dateair) {
                $title = 'archive';
            } else {
                $title = 'plan';
            }
        }

    } else {
        $title = 'tmpl';
    }

    return $title;
}




/**
 * Output "squizrow", i.e. bar with buttons for adding/inserting new element
 *
 * @param string $typ Type: (epg_details, epgmdf_clone, epgmdf_bottom)
 * @param array $opt Options array: col_sum_noghost(int), parent_id
 * @return void
 */
function epg_squizrow_output($typ, $opt=null) {

    global $linetypz;

    $epg_line_types = txarr('arrays', 'epg_line_types');


    if ($typ!='epgmdf_bottom') {
        echo '<tr id="squizrow" style="display:none;"><td class="ghost"></td>
            <td class="squiztyp text-center" colspan="'.$opt['col_sum_noghost'].'">';
    }


    foreach ($linetypz as $v) {

        $attr_arr['class'] = 'btn btn-xs text-uppercase '.epg_linetyp($v, 'var');

        if ($typ=='epg_details') {

            if ($v==6) {
                $attr_arr['href'] = '/desk/stry_modify.php?typ=mdf_task&scnid='.SCNRID;
            } else {
                $attr_arr['href'] = 'epg_modify_single.php?'.TYP.'='.$opt['parent_id'].'&linetyp='.$v;
            }

        } else {

            $attr_arr['href'] = '#';
            $attr_arr['onclick'] = 'element_clone('.(($typ=='epgmdf_clone') ? 'this' : '0').', '.$v.'); return false;';
        }

        pms_btn(
            (PMS_FULL || pms('epg', 'mdf_line', ['NativeType' => $v])),
            $epg_line_types[$v],
            $attr_arr
        );
    }


    if ($typ!='epgmdf_bottom') {
        echo '</td><td class="ghost"></td></tr>';
    }

}






/**
 * Epg STRY connection updater (when in scnr fragment the story is being chosen or one story is replaced by another)
 *
 * @param array $mdf MDF: NativeID
 * @param array $cur CUR: NativeID, ScnrID
 *
 * @return void
 */
function epg_stry_conn($mdf, $cur) {

    global $tx;


    if (!isset($cur['NativeID']) || // Either the fragment previously didn't have any story chosen
        $cur['NativeID']!=$mdf['NativeID']) { // Or the fragment previously had some story chosen and it is now changing


        // Error-catcher: this should never happen
        // User shouldn't have an option to pick a story which is already being used within some scnr, but only FREE stories
        // If the story already has another SCNR conn, just show error notice and log a srpriz

        $scnrid = rdr_cell('stryz', 'ScnrID', $mdf['NativeID']);

        if ($scnrid && $scnrid!=$cur['ScnrID']) {

            $scnr = rdr_row('epg_scnr', 'ProgID, Caption', $scnrid);
            $scnr['ElementID'] = scnr_id_to_elmid($scnrid);

            omg_put('danger',
                $tx[SCTN]['MSG']['err_epg_stry'].' <a href="/epg/epg.php?typ=scnr&id='.$scnr['ElementID'].'">'.
                prgcpt_get($scnr['ProgID'], $scnr['Caption']).'</a>'
            );

            log2file('srpriz', ['type' => '_epg_stry_conn', 'stry' => $mdf['NativeID'], 'epg' => $scnrid]);
            // Note: This doesnot actually have to mean an error. It can also happen if the script lags because of
            // the server, and the unpatient user clicks submit btn again..

            hop($_SERVER['HTTP_REFERER']);
        }


        if ($mdf['NativeID']) {
            receiver_upd('stryz',
                ['ScnrID' => $cur['ScnrID'], 'ProgID' => scnr_get_progid($cur['ScnrID'])],
                ['ID' => $mdf['NativeID']]);
        }

        // If there WAS previously selected story and it has changed: clear its ScnrID column
        if (!empty($cur['NativeID'])) {
            receiver_upd(
                'stryz',
                ['ScnrID' => 0, 'ProgID' => 0],
                ['ID' => $cur['NativeID'], 'ScnrID' => $cur['ScnrID']]
            );
        }

    }
}







/**
 * Make an EPG copy (used for AUTO epg copy)
 *
 * @param string $date_target Date for the epg to be created
 * @param int $chnl Channel ID
 *
 * @return void
 */
function epg_copy($date_target, $chnl) {


    // Copy epg of the previous same day of the week
    $date_src = date('Y-m-d', strtotime($date_target.' - 7 day'));

    $sql = 'SELECT ID FROM prodesk.epgz WHERE !IsTMPL AND ChannelID='.$chnl.' AND DateAir=\''.$date_src.'\'';

    $epgid_src = qry_numer_var($sql);

    if (!$epgid_src) {
        return;
    }


    $epg_insert_arr = [
        'IsTMPL' 	=> 0,
        'DateAir'	=> $date_target,
        'IsReady'	=> 1,
        'TermMod'	=> TIMENOW,
        'TermAdd'	=> TIMENOW,
        'UID'		=> UZID,
        'ChannelID'	=> $chnl
    ];

    // Insert row in *epgz* table
    $epgid_target = receiver_ins('epgz', $epg_insert_arr);


    $tbl[1] = 'epg_elements';
    $tbl[2] = 'epg_scnr';
    $tbl[3] = 'epg_notes';
    $tbl[4] = 'epg_films';


    $sql = 'SELECT * FROM '.$tbl[1].' WHERE EpgID='.$epgid_src.' ORDER BY Queue';

    $result = qry($sql);

    while ($x = mysqli_fetch_assoc($result)) {


        if ($x['NativeType']==1) {	// PROG

            $x['PRG'] = qry_assoc_row('SELECT ProgID, Caption, MatType, IsFilm FROM '.$tbl[2].' WHERE ID='.$x['NativeID']);

            $x['NativeID'] = receiver_ins($tbl[2], $x['PRG']);
        }


        if (in_array($x['NativeType'], [3,4])) {	// BLOCKS

            $x['NativeID'] = 0; // We don't want to copy the exact block, we will just leave a placeholder
        }


        if (in_array($x['NativeType'], [7,8,9,10])) {	// NOTE

            $x['NOTE'] = qry_assoc_row('SELECT Note, NoteType FROM '.$tbl[3].' WHERE ID='.$x['NativeID']);

            $x['NativeID'] = receiver_ins($tbl[3], $x['NOTE']);
        }


        if (in_array($x['NativeType'], [12,13])) {	 // FILM

            $prgid = rdr_cell('epg_films', 'ScnrID', $x['NativeID']);

            $x['PRG'] = qry_assoc_row('SELECT ProgID, Caption, MatType, IsFilm FROM '.$tbl[2].' WHERE ID='.$prgid);

            if ($x['NativeType']==13) { // serial

                $x['FILM']['FilmParentID'] = rdr_cell('epg_films', 'FilmParentID', $x['NativeID']);

                $x['FILM']['EpisodeCount'] = rdr_cell('film', 'EpisodeCount', $x['FILM']['FilmParentID']);

                $z = film_next_episode($x, $date_target);

                unset($x['FILM']['EpisodeCount']);
                // We only needed this for film_next_episode(), but now we must delete it because it would mess up sql,
                // because there is no such column in epg_films table..

                $x['FILM']['FilmID'] = $z['FilmID'];

                film_termepg_upd($x['NativeType'], $x['FILM']);

            } else { // movie

                //$x['FILM']['FilmID'] = rdr_cell('epg_films', 'FilmID', $x['NativeID']);

                $x['FILM']['FilmID'] = 0; // We don't want to copy the exact film, we will just leave a placeholder
            }

            $x['FILM']['ScnrID'] = receiver_ins($tbl[2], $x['PRG']);

            $x['NativeID'] = receiver_ins($tbl[4], $x['FILM']);
        }


        $clnz = ['NativeType', 'NativeID', 'Queue', 'TimeAir', 'DurForc', 'IsActive', 'OnHold', 'WebLIVE', 'WebVOD', 'TermEmit'];
        $filter = array_flip($clnz);
        $mdf = array_intersect_key($x, $filter);

        $mdf['EpgID'] = $epgid_target;


        if ($mdf['OnHold'] || (!empty($holder) && !$mdf['TimeAir'])) {
            $holder = true;
        } else {
            $holder = false;
            $mdf['TimeAir'] = $mdf['TermEmit'];
        }


        receiver_ins($tbl[1], $mdf);
    }
}










/**
 * Make a SCNR copy (used for template auto-import)
 *
 * @param int $source_id Source SCNR Element-ID
 * @param int $target_id Target SCNR Element-ID
 *
 * @return void
 */
function scnr_copy($source_id, $target_id) {

    $source_scnr_id = scnrid_prog($source_id);
    $target_scnr_id = scnrid_prog($target_id);

    $tbl[1] = 'epg_scnr_fragments';
    $tbl[2] = 'epg_notes';

    $sql = 'SELECT * FROM '.$tbl[1].' WHERE ScnrID='.$source_scnr_id.' ORDER BY ID';

    $result = qry($sql);

    while ($x = mysqli_fetch_assoc($result)) {


        if ($x['NativeType']==2) {	// STRY

            $x['NativeID'] = stry_copy($x['NativeID'], $target_scnr_id);
        }


        if (in_array($x['NativeType'], [7,8,9,10])) {	// NOTE

            $z['NOTE'] = qry_assoc_row('SELECT Note, NoteType FROM '.$tbl[2].' WHERE ID='.$x['NativeID']);

            $x['NativeID'] = receiver_ins($tbl[2], $z['NOTE']);
        }


        $x['ScnrID'] = $target_scnr_id;

        unset($x['ID']);

        receiver_ins($tbl[1], $x);
    }
}



/**
 * Check whether fragment term is faulty
 *
 * @param array $x Element (parent)
 * @param string $mdfterm Term
 *
 * @return bool $faul Faulty or not
 */
function fragment_term_faul($x, $mdfterm) {

    global $tx;

    $faul = false;

    $t_mdf = strtotime($mdfterm);

    $hms_mdf = date('H:i:s', $t_mdf);

    if ($x['PRG']['MatType']==1) {

        if (empty($x['TimeAir'])) {
            $x['TimeAir'] = $x['TermEmit'];
        }

        if (empty($x['_TimeFinito'])) {
            $x['_TimeFinito'] = add_dur2term($x['TimeAir'], $x['_Dur']['winner']['dur_hms']);
        }

        if ($t_mdf<strtotime($x['TimeAir']) || $t_mdf>strtotime($x['_TimeFinito'])) {
            $faul = true;
        }

    } else {

        if (dur2secs($hms_mdf) > dur2secs($x['_Dur']['winner']['dur_hms'])) {
            $faul = true;
        }
    }

    if ($faul) {
        omg_put('danger', $hms_mdf.' - '.$tx[SCTN]['MSG']['term_faulty']);
    }

    return $faul;
}


