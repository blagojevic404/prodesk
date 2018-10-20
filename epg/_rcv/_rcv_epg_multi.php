<?php


if (TYP=='epg') {

    pms('epg', 'mdf_full', null, true); // for 'epg' TYP we don't need data array for pms

} else {

    // for scnr we cannot determine pms for NEW situation, so we have to skip pms check altogether
}



if (TYP=='epg') {

    define('TMPL', (isset($_POST['Caption'])) ? 1 : 0); // Only TMPL can have caption
	
	$id = (isset($_GET['id'])) ? wash('int', $_GET['id']) : 0;

	$x['EPG'] = epg_reader($id);	


	if (!@$x['EPG']['ID']) { // NEW! (insert to epgz table)

        $epg_insert_arr = [
            'IsTMPL' 	=> (!TMPL) ? 0 : 1,
            'DateAir'	=> (!TMPL) ? EPG_NEW : NULL,
            'IsReady'	=> wash('int', @$_POST['IsReady']),
            'TermMod'	=> TIMENOW,
            'TermAdd'	=> TIMENOW,
            'UID'		=> UZID,
            'ChannelID'	=> $x['EPG']['ChannelID']
        ];

        $epg_exists = rdr_cell('epgz', 'ID',
            'DateAir=\''.$epg_insert_arr['DateAir'].'\' AND ChannelID='.$epg_insert_arr['ChannelID']);

        if ($epg_exists) {
            omg_put('danger', $tx['LBL']['epg'].' '.$tx['MSG']['already_exists']);
            hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?typ=epg&id='.$epg_exists);
        }

        // Insert row in *epgz* table
		$id = receiver_ins('epgz', $epg_insert_arr);
		
		if (TMPL) {
            // For templates, also insert row in *epg_templates* table
            receiver_ins('epg_templates', ['ID' => $id, 'Caption' => wash('txt', @$_POST['Caption'])], LOGSKIP);
        }
		
		$x['EPG'] = epg_reader($id);	
		
	} else { // MDF

		if (!TMPL) { // mdf EPG: updatable columns are *IsReady* and *TermMod*
			
			$mdf = ['IsReady' => wash('int', @$_POST['IsReady']),
			        'TermMod' => TIMENOW];
			$cur = ['ID' => $x['EPG']['ID'],
					'IsReady' => $x['EPG']['IsReady']];
			receiver_upd('epgz', $mdf, $cur);
			
		} else { // mdf TMPL: updatable column is *Caption*

			$mdf = ['Caption' => wash('txt', @$_POST['Caption'])];
			$cur = ['ID' => $x['EPG']['ID'],
                    'Caption' => $x['EPG']['Caption']];
			receiver_upd('epg_templates', $mdf, $cur);
		}
	}

	
	$x['ELEMENTS'] = epg_elements_arr($x['EPG']['ID'], 'rcv');
	
	$tbl[1] = 'epg_elements';
	$tbl[2] = 'epg_scnr';
	$tbl[3] = 'epg_notes';
	$tbl[4] = 'epg_films';


} else { // scnr


	$id = (isset($_GET['id'])) ? wash('int', $_GET['id']) : 0;

    // If there is no ID, it is the NEW SCNR TMPL situation
    // Normal SCNRs already have ID, because we can add fragments only to an existing element.
    define('TMPL_NEW', (!$id) ? 1 : 0);

	if (TMPL_NEW) {

        // Insert row in *epg_scnr* table

        $prg_mdf['ProgID'] 	= wash('int', @$_POST['Tmpl_ProgID']);
        $prg_mdf['MatType'] = 1; // We have to set MatType because it determines what type of the SCNR page will be displayed
        $prg_mdf['Caption'] = wash('txt', @$_POST['Tmpl_Caption']);
		$prg_id = receiver_ins('epg_scnr', $prg_mdf, LOGSKIP);

        // Insert row in *epg_elements* table

        $elm_mdf['NativeID'] = $prg_id;
        $elm_mdf['NativeType'] = 1;

        $team_id = rdr_cell('prgm', 'TeamID', $prg_mdf['ProgID']);    // Get the team ID for the specified prog
        $team_chnl = rdr_cell('prgm_teams', 'ChannelID', $team_id);
        $elm_mdf['AttrA'] = $team_chnl; // TMPL SCNR: ChannelID

		$id = receiver_ins('epg_elements', $elm_mdf);
	}

	$x['ELEMENT'] = element_reader($id);


    // We need to know not only whether this is TMPL, but whether this is MDF or NEW situation with that TMPL
	define('TMPL_MDF', (!TMPL_NEW && !$x['ELEMENT']['EPG']['ID']) ? 1 : 0);
	define('TMPL', (TMPL_NEW || TMPL_MDF) ? 1 : 0);


    if (TMPL_MDF) {

        // Update row in *epg_scnr* table
		$mdf = [];
		$mdf['Caption'] = wash('txt', @$_POST['Tmpl_Caption']);
		$filter = array_flip(['Caption']);
		$cur = array_intersect_key($x['ELEMENT']['PRG'], $filter);
        $cur['ID'] = $x['ELEMENT']['SCNRID'];
		receiver_upd('epg_scnr', $mdf, $cur);
    }
	
		
	$x['FRAGMENTS'] = epg_fragments_arr(
        ($x['ELEMENT']['NativeType']==1 ? $x['ELEMENT']['NativeID'] : $x['ELEMENT']['FILM']['ScnrID'])
    );


    if (!TMPL) {

        // DSK: FLW
        // Get the list of users which have a FLW for the PARENT SCNR, so we can later call this inside the loop and
        // write FLW for the looped fragment for each of those users

        $result = qry('SELECT UID, MarkType FROM stry_followz WHERE ItemID='.$x['ELEMENT']['SCNRID'].' AND ItemType=1');
        while ($line = mysqli_fetch_assoc($result)) {
            $x['ELEMENT']['FLW'][] = $line;
        }
    }

    define('SCN_IMPORT_FROM_SCN', (isset($_GET['import_id'])) ? 1 : 0);


    $tbl[1] = 'epg_scnr_fragments';
	$tbl[3] = 'epg_notes';
}






/* Convert $_POST global to $post array which will further be used for data manipulation */


if (empty($_POST['qu'])) { // If BLANK list was submitted, end right away

    hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?typ='.TYP.
        '&id='.((TYP=='epg') ? $x['EPG']['ID'] : $x['ELEMENT']['ID']));

} else {

    $q_arr = array_flip(explode(' ', trim($_POST['qu'])));
}



$post = [];

foreach ($_POST['id'] as $k_typ => $v_typ) {        // ['id'][native_type_id][index] => id

	foreach ($v_typ as $k_id => $v_id) {
		
		$cnt = @$_POST['cnt'][$k_typ][$k_id];
		
		$post[$cnt]['ID'] 			= intval($v_id);
		$post[$cnt]['NativeType'] 	= intval($k_typ);
		$post[$cnt]['Queue'] 		= intval($q_arr[$cnt]);
		$post[$cnt]['DEL'] 			= wash('int', @$_POST['del'][$k_typ][$k_id]);

		if (in_array($k_typ, [1,3,4,5,7,9,12,13])) {	// DUR

            $arr_hms = ['hh' => @$_POST['dur-hh'][$k_typ][$k_id],
                        'mm' => @$_POST['dur-mm'][$k_typ][$k_id],
                        'ss' => @$_POST['dur-ss'][$k_typ][$k_id]];

            $post[$cnt]['DurForc'] = rcv_datetime('hms', $arr_hms);
        }

        if (in_array($k_typ, [1,3,4,5,7,9,12,13,14])) {	// TERM

            if (!(TMPL && TYP=='scnr')) { 	// TERM is not necessary for scenario templates

				$arr_hms = ['hh' => @$_POST['term-hh'][$k_typ][$k_id],
                            'mm' => @$_POST['term-mm'][$k_typ][$k_id],
                            'ss' => @$_POST['term-ss'][$k_typ][$k_id]];

                if (TYP=='epg') {
                    if (in_array('*', $arr_hms)) {
                        $arr_hms = ['hh' => '', 'mm' => '', 'ss' => ''];
                        $post[$cnt]['OnHold'] = 1;
                    } else {
                        $post[$cnt]['OnHold'] = 0;
                    }
                }

				$post[$cnt]['TimeAir'] = rcv_datetime(
                    'ymdhms', $arr_hms, ((TYP=='epg') ? $x['EPG']['DateAir'] : $x['ELEMENT']['EPG']['DateAir'])
                );

                if (TYP=='scnr' && $post[$cnt]['TimeAir']) {

                    $el = $x['ELEMENT'];
                    $el['_Dur'] = epg_durations($el, 'hms');

                    $faul = fragment_term_faul($el, $post[$cnt]['TimeAir']);

                    if ($faul) {
                        unset($post[$cnt]['TimeAir']);
                    }

                    unset($el);
                }
            }
		}
			
		if (in_array($k_typ, [2,3,4,5,12,13,14])) { 	// IFRM

			$post[$cnt]['NativeID']	= wash('int', @$_POST['NativeID'][$k_typ][$k_id]);

            if ($k_typ==2 && !$post[$cnt]['NativeID']) {	// Empty story
                $post[$cnt]['DEL'] = 1;
            }
        }
			
		if ($k_typ==1) { 	// PROG

			$post[$cnt]['PRG']['ProgID'] = wash('int', @$_POST['ProgID'][$k_typ][$k_id]);
            $post[$cnt]['PRG']['Caption'] = wash('txt', @$_POST['dsc'][$k_typ][$k_id]);

            if (!$post[$cnt]['PRG']['ProgID'] && !$post[$cnt]['PRG']['Caption']) {	// Empty prog..
                // We should never get here. We also have js check on submit, because we want to avoid accidental deleting.
                $post[$cnt]['DEL'] = 1;
            }
		}

		if (in_array($k_typ, [1,12,13])) {
			$post[$cnt]['PRG']['MatType'] = wash('int', @$_POST['MatType'][$k_typ][$k_id]);
			
			if (!$cfg['lbl_parental_filmbased']) $post[$cnt]['Parental'] = wash('int', @$_POST['Parental'][$k_typ][$k_id]);
			if (!$cfg['lbl_rec4rerun_prgbased']) $post[$cnt]['Record'] = wash('bln', @$_POST['Record'][$k_typ][$k_id]);
		}

		if (in_array($k_typ, [7,8,9,10])) {     // NOTE TXT
			$post[$cnt]['NOTE']['Note']	= wash('txt', @$_POST['dsc'][$k_typ][$k_id]);
		}

        if (in_array($k_typ, [8])) { 	// NOTE TYPE
            $post[$cnt]['NOTE']['NoteType']	= wash('int', @$_POST['NoteType'][$k_typ][$k_id]);
        }

        if (in_array($k_typ, [4])) { 	// PROMO: TYPE
            $post[$cnt]['AttrA'] = wash('int', @$_POST['AttrA'][$k_typ][$k_id]);
        }


        // COPYING stories

        // In the *import-from-other-scenario* situation, we have to check for each story whether it is already
        // connected with the other scn, and if so then make a copy of the story.

        // Also, when creating a row copy by *duplicate* btn in the MDF list.. In that case, scnrid for the story
        // would be already set to *this* scnr ($x['ELEMENT']['SCNRID'])

        if ($post[$cnt]['NativeType']==2 && !$post[$cnt]['ID']) { // Concerns only stories which weren't already in this scn

            $stry_scnr = rdr_cell('stryz', 'ScnrID', $post[$cnt]['NativeID']); // Get scnrid for this story

            if ($stry_scnr) { // Concerns only stories which *have* scnrid

                if (SCN_IMPORT_FROM_SCN || $stry_scnr==$x['ELEMENT']['SCNRID']) {

                    $post[$cnt]['NativeID'] = stry_copy($post[$cnt]['NativeID'], $x['ELEMENT']['SCNRID']);
                }
            }
        }

    }
}

ksort($post);



/*
echo '<pre>';
print_r($x);
print_r($_POST);
print_r($post);
exit;
*/


// We are going to compare POST array with current ELEMENTS/FRAGMENTS array, i.e. MDF value will be from POST,
// and CUR value will be from elements/fragments array

foreach ($post as $atom_mdf) {
	

	if ($atom_mdf['ID']) { // MDF.. If there is ID, the row already exists in db

        if (isset($x[((TYP=='epg') ? 'ELEMENTS' : 'FRAGMENTS')][$atom_mdf['ID']])) {

            $atom_cur = (TYP=='epg') ? $x['ELEMENTS'][$atom_mdf['ID']] : $x['FRAGMENTS'][$atom_mdf['ID']];

        } else {

            // This is to prevent an error which happens when the MDF form is submitted twice and one of the
            // elements/fragments was deleted.
            // Although JS:prevent_double_submit() minimize these cases, however sometimes the submit lags for server or
            // network reasons, and it happens that the nervous user clicks again on the submit button, thus effectively
            // repeating the submit once the requests get to the server etc..

            continue;
        }

	} else { // NEW
		
		$atom_cur = '';
	}


    if ($atom_mdf['DEL']) {
		
		if ($atom_mdf['ID']) {
            epg_deleter(TYP, $atom_mdf['ID'], 'target', 'inactives');
        }

		continue;
	}


    // STRY: connection with epg-scn
    if ($atom_mdf['NativeType']==2) {
        epg_stry_conn(
            ['NativeID' => $atom_mdf['NativeID']],
            ['NativeID' => @$atom_cur['NativeID'], 'ScnrID' => $x['ELEMENT']['SCNRID']]
        );
    }


    if (!@$atom_mdf['ID']) { // new = insert


		if ($atom_mdf['NativeType']==1) {	// PROG
			$atom_mdf['NativeID'] = receiver_ins($tbl[2], $atom_mdf['PRG'], LOGSKIP);
		}

		if (in_array($atom_mdf['NativeType'], [7,8,9,10])) {	// NOTE
			$atom_mdf['NativeID'] = receiver_ins($tbl[3], $atom_mdf['NOTE'], LOGSKIP);
			$atom_mdf['TermEmit'] = $cfg[SCTN]['tmpl_dummy_starttime'];
		}

		if (in_array($atom_mdf['NativeType'], [12,13])){	 // FILM
			
			$atom_mdf['PRG']['IsFilm'] = 1;

            // We insert a row into epg_scnr which will hold fragments for FILM, i.e. turn film into a pseudo-prog.
			$atom_mdf['FILM']['ScnrID'] = receiver_ins($tbl[2], $atom_mdf['PRG'], LOGSKIP);

            // POST:NativeID holds film.ID value. We have to save this to epg_films.FilmID instead to epg_elements.NativeID
            // which would be normal behaviour
            $atom_mdf['FILM']['FilmID'] = $atom_mdf['NativeID'];
			
			if ($atom_mdf['NativeType']==13) {
                $atom_mdf['FILM']['FilmParentID'] = rdr_cell('film_episodes', 'ParentID', $atom_mdf['FILM']['FilmID']);
            }

            // and for epg_elements.NativeID we will use epg_films.ID which is created right here
			$atom_mdf['NativeID'] = receiver_ins($tbl[4], $atom_mdf['FILM'], LOGSKIP);

            film_termepg_upd($atom_mdf['NativeType'], $atom_mdf['FILM']);
        }

		if (in_array($atom_mdf['NativeType'], [1,12,13,14])) {

			$atom_mdf['WebLIVE'] = ($atom_mdf['NativeType']==1) ?
                rdr_cell('prgm_settings', 'WebLIVE', $atom_mdf['PRG']['ProgID']) : $cfg[SCTN]['film_weblive_def'];

			$atom_mdf['WebVOD']	= ($atom_mdf['NativeType']==1) ?
                rdr_cell('prgm_settings', 'WebVOD', $atom_mdf['PRG']['ProgID']) : $cfg[SCTN]['film_webvod_def'];
		}

		// element | fragment
		$filter = array_flip(['NativeType', 'NativeID', 'Queue', 'TimeAir', 'DurForc', 'WebLIVE', 'WebVOD', 'TermEmit',
            'OnHold', 'AttrA']);
		$mdf = array_intersect_key($atom_mdf, $filter);
		
		if (TYP=='epg') {
				
			$mdf['EpgID'] = $x['EPG']['ID'];
			
		} else {

			$mdf['ScnrID'] = $x['ELEMENT']['SCNRID'];
		}
		
		$mdf['IsActive'] = 1;

		receiver_ins($tbl[1], $mdf);


        // DSK: FLW
        // Write FLW for each user in $x['ELEMENT']['FLW'], which we listed before the loop

        if (!TMPL && TYP!='epg' && !empty($x['ELEMENT']['FLW']) && $mdf['NativeType']==2) {

            foreach($x['ELEMENT']['FLW'] as $flw) {

                flw_put($mdf['NativeID'], $mdf['NativeType'], $flw['MarkType'], $flw['UID']);
            }
        }


    } else { // modify = update


		if ($atom_mdf['NativeType']==1) {	// PROG
			$atom_mdf['PRG']['ID'] = $atom_cur['PRG']['ID'];
			receiver_upd($tbl[2], $atom_mdf['PRG'], $atom_cur['PRG'], ['tbl_name' => $tbl[1], 'x_id' => $atom_mdf['ID']]);
		}
	
		if (in_array($atom_mdf['NativeType'], [7,8,9,10])) {	// NOTE
			$atom_mdf['NOTE']['ID'] = $atom_cur['NOTE']['ID'];
			$changed = receiver_upd($tbl[3], $atom_mdf['NOTE'], $atom_cur['NOTE'], LOGSKIP);
			if ($changed) {
                $atom_mdf['TermEmit'] = date('Y-m-d H:i:s', strtotime($atom_cur['TermEmit'])+1);
            }
		}

		if (in_array($atom_mdf['NativeType'], [12,13])) {    // FILM

            // POST:NativeID holds film.ID value. We have to save this to epg_films.FilmID instead to epg_elements.NativeID
            // which would be normal behaviour
            $atom_mdf['FILM']['FilmID'] = $atom_mdf['NativeID'];

            // and for epg_elements.NativeID we will use cur['NativeID'] which is actually epg_films.ID
            $atom_mdf['NativeID'] = $atom_cur['NativeID'];

			if ($atom_mdf['NativeType']==13) {
                $atom_mdf['FILM']['FilmParentID'] = rdr_cell('film_episodes', 'ParentID', $atom_mdf['FILM']['FilmID']);
            }

			receiver_upd($tbl[4], $atom_mdf['FILM'], $atom_cur['FILM'], LOGSKIP);

			$atom_mdf['PRG']['ID'] = $atom_cur['PRG']['ID'];

            // we save MatType into epg_scnr, that's why we need this update too
			receiver_upd($tbl[2], $atom_mdf['PRG'], $atom_cur['PRG'], ['tbl_name' => $tbl[1], 'x_id' => $atom_mdf['ID']]);

            film_termepg_upd($atom_mdf['NativeType'], $atom_mdf['FILM'], $atom_cur['FILM']);
		}

		
		// element | fragment
		switch ($atom_mdf['NativeType']) {
			
			case 1:

			    $filter = array_flip(['ID', 'Queue', 'TimeAir', 'DurForc', 'Parental', 'Record']);

                if (!$atom_cur['PRG']['ProgID'] && $atom_mdf['PRG']['ProgID']) {

                    $atom_mdf['WebLIVE'] = rdr_cell('prgm_settings', 'WebLIVE', $atom_mdf['PRG']['ProgID']);

                    $atom_mdf['WebVOD']	= rdr_cell('prgm_settings', 'WebVOD', $atom_mdf['PRG']['ProgID']);

                    $filter = array_merge($filter, array_flip(['WebLIVE', 'WebVOD']));
                }

                break;

			case 7:
			case 9: $filter = array_flip(['ID', 'Queue', 'TimeAir', 'DurForc', 'TermEmit']); break;
			
			case 12:
			case 13: $filter = array_flip(['ID', 'Queue', 'TimeAir', 'DurForc', 'NativeID', 'Parental', 'Record']);	break; 	// IFRM +

            case 3:
            case 4:
            case 5: $filter = array_flip(['ID', 'Queue', 'TimeAir', 'DurForc', 'NativeID', 'AttrA']); break;  // IFRM spice

            case 2: $filter = array_flip(['ID', 'Queue', 'NativeID']); break;                        // IFRM stry

            case 14: $filter = array_flip(['ID', 'Queue', 'TimeAir', 'NativeID']); break; 	         // IFRM (NO DUR)

			case 8:
			case 10: $filter = array_flip(['ID', 'Queue', 'TermEmit']);	break;	// NO DUR&TERM
		}

        if (TYP=='epg') {
            $filter['OnHold'] = '';
        }
		
		$mdf = array_intersect_key($atom_mdf, $filter);
		$cur = array_intersect_key($atom_cur, $filter);
		receiver_upd($tbl[1], $mdf, $cur);

        if (TYP=='epg' && !$x['EPG']['IsTMPL'] && in_array($atom_mdf['NativeType'], [1,12,13])){	// PROG, FILM
			if ($atom_mdf['PRG']['MatType']!=3 && $atom_cur['PRG']['MatType']==3) {
                epg_tie($atom_cur, 'del'); // remove tie
            }
		}


        // EMIT update: For every PROG which has changed DurForc, run scnr_termemit

        if ($atom_mdf['NativeType']==1 && $atom_mdf['DurForc']!=$atom_cur['DurForc']) {
            scnr_termemit($atom_cur['ID']);
        }

    }

}







// EMIT update

if (TYP=='scnr' && $x['ELEMENT']['EpgID']) { // TMPL doesn't have $x['ELEMENT']['EpgID']

	$x_emit = [
		'ID' 			=> $x['ELEMENT']['PRG']['ID'],
		'MatType' 		=> $x['ELEMENT']['PRG']['MatType'],
		'DurEmit' 		=> $x['ELEMENT']['PRG']['DurEmit'],
		'NativeType' 	=> $x['ELEMENT']['NativeType'],
        'ElementID'     => $x['ELEMENT']['ID']
	];
						
	scnr_duremit($x_emit);
}






hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?typ='.TYP.
    '&id='.((TYP=='epg') ? $x['EPG']['ID'] : $x['ELEMENT']['ID']));

