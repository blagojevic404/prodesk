<?php



if (TYP=='epg') {
	
	$x = element_reader_front('rcv');

	$tbl[1] = 'epg_elements';
	$tbl[2] = 'epg_scnr';
	$tbl[3] = 'epg_notes';
	$tbl[4] = 'epg_films';

    pms('epg', 'mdf_single', $x, true);

} else { // scnr

	$x = fragment_reader_front();

	$tbl[1] = 'epg_scnr_fragments';
	$tbl[3] = 'epg_notes';
	
	if (!$x['ELEMENT']['EpgID']) { // TMPL doesn't have $x['ELEMENT']['EpgID']
        $x['EPG']['DateAir'] = $cfg[SCTN]['tmpl_dummy_starttime'];
    }

    pms('epg', 'mdf_scn_fragment', $x, true);
}






$mdf[1]['NativeType'] = wash('int', @$_POST['NativeType']);

	
if (in_array($mdf[1]['NativeType'], [1,3,4,5,7,9,12,13])) {     // DUR

    $arr_hms = ['hh' => @$_POST['dur-hh'], 'mm' => @$_POST['dur-mm'], 'ss' => @$_POST['dur-ss']];

    $mdf[1]['DurForc'] = rcv_datetime('hms_nozeroz', $arr_hms);
}

if (in_array($mdf[1]['NativeType'], [1,3,4,5,7,9,12,13,14])) {     // TERM

    $arr_hms = ['hh' => @$_POST['term-hh'], 'mm' => @$_POST['term-mm'], 'ss' => @$_POST['term-ss']];

    if (TYP=='epg') {
        if (in_array('*', $arr_hms)) {
            $arr_hms = ['hh' => '', 'mm' => '', 'ss' => ''];
            $mdf[1]['OnHold'] = 1;
        } else {
            $mdf[1]['OnHold'] = 0;
        }
    }

    $mdf[1]['TimeAir'] = rcv_datetime('ymdhms', $arr_hms, $x['EPG']['DateAir']);

    if (TYP=='scnr' && $mdf[1]['TimeAir']) {

        $el = element_reader($x['ELEMENT']['ID']);
        $el['_Dur'] = epg_durations($el, 'hms');

        $faul = fragment_term_faul($el, $mdf[1]['TimeAir']);

        if ($faul) {
            unset($mdf[1]['TimeAir']);
        }

        unset($el);
    }
}

if (in_array($mdf[1]['NativeType'], [2,3,4,5,12,13,14])) {	// IFRM
	
	$mdf[1]['NativeID']	= wash('int', @$_POST['NativeID']);

    if ($mdf[1]['NativeType']==2 && !$mdf[1]['NativeID']) {	// empty story

        omg_put('danger', $tx[SCTN]['MSG']['err_empty_element']);
        hop($_SERVER['HTTP_REFERER']); // Reload the requesting page
    }
}

if ($mdf[1]['NativeType']==1) {	 // PROG
	
	$mdf[2]['ProgID']	= wash('int', @$_POST['ProgID']);
    $mdf[2]['Caption']	= wash('cpt', @$_POST['Caption']);
    $mdf[2]['IsReady']	= wash('int', @$_POST['IsReady']);

    if (!$mdf[2]['ProgID'] && !$mdf[2]['Caption']) {	// empty prog

        omg_put('danger', $tx[SCTN]['MSG']['err_empty_prog']);
        hop($_SERVER['HTTP_REFERER']); // Reload the requesting page
    }
}

if (in_array($mdf[1]['NativeType'], [1,12,13]) && @$_GET['ref']=='epg') {
	
	$mdf[2]['MatType'] = wash('int', @$_POST['MatType']);
	
	if (!$cfg['lbl_parental_filmbased']) {
        $mdf[1]['Parental']	= wash('int', @$_POST['Parental']);
    }

	if (!$cfg['lbl_rec4rerun_prgbased']) {
        $mdf[1]['Record'] = wash('int', @$_POST['Record']);
    }
}

if (in_array($mdf[1]['NativeType'], [1,4])) {

    $mdf[1]['AttrA'] = wash('int', @$_POST['AttrA']);
}

if (in_array($mdf[1]['NativeType'], [1])) {

    $mdf[1]['AttrB'] = wash('int', @$_POST['AttrB']);
}





if (in_array($mdf[1]['NativeType'], [1,12,13,14])) {

	if (isset($_POST['WebLIVE']) && $_POST['WebLIVE']<2) {
		$mdf[1]['WebLIVE'] = wash('int', $_POST['WebLIVE']);
	} else {

        switch ($x['NativeType']) {

            case 1:  $mdf[1]['WebLIVE'] = rdr_cell('prgm_settings', 'WebLIVE', $mdf[2]['ProgID']); break;

            case 14: $mdf[1]['WebLIVE'] = rdr_cell('epg_elements', 'WebLIVE', $mdf[1]['NativeID']); break;

            case 12:
            case 13:  $mdf[1]['WebLIVE'] = $cfg[SCTN]['film_weblive_def']; break;
        }
    }

	if (isset($_POST['WebVOD']) && $_POST['WebVOD']<2) {
		$mdf[1]['WebVOD'] = wash('int', $_POST['WebVOD']);
	} else {

        switch ($x['NativeType']) {

            case 1:  $mdf[1]['WebVOD'] = rdr_cell('prgm_settings', 'WebVOD', $mdf[2]['ProgID']); break;

            case 14: $mdf[1]['WebVOD'] = rdr_cell('epg_elements', 'WebVOD', $mdf[1]['NativeID']); break;

            case 12:
            case 13:  $mdf[1]['WebVOD'] = $cfg[SCTN]['film_webvod_def']; break;
        }
    }
}





if (in_array($mdf[1]['NativeType'], [7,8,9,10])) {	    // NOTE TXT
	$mdf[3]['Note']	= wash('cpt', @$_POST['Note']);
}

if ($mdf[1]['NativeType']==8) {	    // NOTE TYP
	$mdf[3]['NoteType']	= wash('int', @$_POST['NoteType']);
}

// STRY: connection with epg-scn
if ($mdf[1]['NativeType']==2) {
    epg_stry_conn($mdf[1], $x);
}


/*
echo '<pre>';
print_r($x);
//print_r($_POST);
print_r($mdf);
exit;
*/




if (!@$x['ID']) { // new = insert


	$mdf[1]['Queue'] = $x['Queue'];

	
	// UPDATE QUEUE of OTHER elements/fragments
	if (TYP=='epg') {
		$mdf[1]['EpgID'] = $x['EpgID'];
		$sql = 'UPDATE epg_elements SET Queue=Queue+1 WHERE Queue>='.$mdf[1]['Queue'].' AND EpgID='.$mdf[1]['EpgID'];
	} else {
		$mdf[1]['ScnrID'] = $x['ScnrID'];
		$sql = 'UPDATE epg_scnr_fragments SET Queue=Queue+1 WHERE Queue>='.$mdf[1]['Queue'].' AND ScnrID='.$mdf[1]['ScnrID'];
	}
	qry($sql);


	if (in_array($mdf[1]['NativeType'], [1])) {     // PROG
		$mdf[1]['NativeID'] = $x['NativeID'] = receiver_ins($tbl[2], $mdf[2], LOGSKIP);
    }

	if (in_array($mdf[1]['NativeType'], [7,8,9,10])) {     // NOTE
		
		$mdf[1]['NativeID'] = receiver_ins($tbl[3], $mdf[3], LOGSKIP);
		
		if (TYP=='epg') {
            $mdf[1]['TermEmit']	= $cfg[SCTN]['tmpl_dummy_starttime'];
        }
	}

	if (in_array($mdf[1]['NativeType'], [12,13])) {     // FILM

		$mdf[2]['IsFilm'] = 1;

        // We insert a row into epg_scnr which will hold fragments for FILM, i.e. turn film into a pseudo-prog.
		$mdf[4]['ScnrID'] = receiver_ins($tbl[2], $mdf[2], LOGSKIP);

        // POST:NativeID holds film.ID value. We have to save this to epg_films.FilmID instead to epg_elements.NativeID
        // which would be normal behaviour
		$mdf[4]['FilmID'] = $mdf[1]['NativeID'];

		if ($mdf[1]['NativeType']==13) {
            $mdf[4]['FilmParentID'] = rdr_cell('film_episodes', 'ParentID', $mdf[4]['FilmID']);
        }

        // and for epg_elements.NativeID we will use epg_films.ID which is created right here
		$mdf[1]['NativeID'] = receiver_ins($tbl[4], $mdf[4], LOGSKIP);

        film_termepg_upd($mdf[1]['NativeType'], $mdf[4]);
    }


	$mdf[1]['IsActive'] = 1;

	$x['ID'] = receiver_ins($tbl[1], $mdf[1]);


    // DSK: FLW
    // Get the list of users which have a FLW for the PARENT SCNR, and write FLW for this fragment for each of those users

    if (TYP!='epg' && $mdf[1]['NativeType']==2) {

        $result = qry('SELECT UID, MarkType FROM stry_followz WHERE ItemID='.$x['SCNR']['ID'].' AND ItemType=1');
        while ($line = mysqli_fetch_assoc($result)) {

            flw_put($mdf[1]['NativeID'], $mdf[1]['NativeType'], $line['MarkType'], $line['UID']);
        }
    }


} else { // modify = update


	if (in_array($mdf[1]['NativeType'], [1])) {    // PROG
		receiver_upd($tbl[2], $mdf[2], $x['PRG'], ['tbl_name' => $tbl[1], 'x_id' => $x['ID']]);
    }

    if (in_array($mdf[1]['NativeType'], [7,8,9,10])) {	    // NOTE
		
		$changed = receiver_upd($tbl[3], $mdf[3], $x['NOTE'], LOGSKIP);
		
		if (TYP=='epg' && $changed) {
            $mdf[1]['TermEmit']	= date('Y-m-d H:i:s', strtotime($x['TermEmit'])+1);
        }
	}
	
	if (in_array($mdf[1]['NativeType'], [12,13])) {    // FILM

        // POST:NativeID holds film.ID value. We have to save this to epg_films.FilmID instead to epg_elements.NativeID
        // which would be normal behaviour
		$mdf[4]['FilmID'] = $mdf[1]['NativeID'];

        // and for epg_elements.NativeID we will use x['NativeID'] which is actually epg_films.ID
		$mdf[1]['NativeID'] = $x['NativeID'];

		if ($mdf[1]['NativeType']==13) {
            $mdf[4]['FilmParentID'] = rdr_cell('film_episodes', 'ParentID', $mdf[4]['FilmID']);
        }

		receiver_upd($tbl[4], $mdf[4], $x['FILM'], LOGSKIP);

        // We save MatType into epg_scnr, that's why we need this update too.
        // Note: MatType controls do not appear when the mdf page is opened through scnr page (where editors have pms),
        // but only when it is opened through epg page (where planers have pms). Thus, $mdf[2] may not be set.
        if (isset($mdf[2])) {
            receiver_upd($tbl[2], $mdf[2], $x['PRG'], ['tbl_name' => $tbl[1], 'x_id' => $x['ID']]);
        }

        film_termepg_upd($mdf[1]['NativeType'], $mdf[4], $x['FILM']);
	}


	receiver_upd($tbl[1], $mdf[1], $x);
	

	if (in_array($mdf[1]['NativeType'], [1,12,13]) && !$x['EPG']['IsTMPL'] && isset($mdf[2]['MatType'])) {	// PROG, FILM
		if ($mdf[2]['MatType']!=3 && $x['PRG']['MatType']==3) {
            epg_tie($x, 'del'); // remove tie
        }
		if ($mdf[2]['MatType']==3 && $x['PRG']['MatType']==3) {
            epg_tie($x, 'upd'); // update tie
        }
	}
}



if (TYP=='epg' && $mdf[1]['NativeType']==1) { // PROG

    $log = ['tbl_name' => 'epg_elements', 'x_id' => $x['ID']];

	crw_receiver($x['NativeID'], $x['NativeType'], @$x['CRW'], $log);
	
	if (isset($mdf[2]['MatType']) && $mdf[2]['MatType']!=1) {    // not liveair
		mos_receiver($x['NativeID'], $x['NativeType'], @$x['MOS'], $log);
    }
}




// EMIT update


if (TYP=='epg') {

    if ($x['NativeType']==1) {

        // The second atribute is $add_parent (Whether to add parent EpgID to loop array)
        // If we got here from EPG, then $_GET['ref'] will be 'epg', and we will return to the same page (EPG) after rcv,
        // thus adding that EpgID is not necessary, because all of its elements will be up-to-dated on its display anyway..
        scnr_termemit($x['ID'], ((@$_GET['ref']=='scn') ? true : false));


        // DurEmit update. This is currently not really necessary, because DurEmit is used only to check whether termemit
        // procedure is necessary, and with progs it never is, because epg display itself updates termemit for each prog.

        if (!empty($x['PRG']['ID'])) { // New progs will not have any attributes

            $x_emit = [
                'ID' => $x['PRG']['ID'],
                'MatType' => $x['PRG']['MatType'],
                'DurEmit' => $x['PRG']['DurEmit'],
                'NativeType' => $x['NativeType'],
            ];

            // The second attribute is to skip calling scnr_termemit() within function, because we have already called it once
            scnr_duremit($x_emit, true);
        }

    }

    // $_GET['ref'] is used only for PROG, because MDF page for prog can be opened either from epg details/list page
    // (via right hanging MDF button), either from scnr details/list page (via MDF-DSC button in the toolbar). So, by this
    // attribute we pass info about what was the REFERER script - whether it is epg(ref=epg) or scnr (ref=scn). And we need
    // that, in order to know where to redirect after receiver is done.
    // Also, if we are going to redirect to scn page, we should run sch_termemit for parent epg, but if we are going to
    // redirect to epg page, then we don't have to do sch_termemit for parent epg, because just displaying epg will do the same..


    $url_redirect = $pathz['www_root'].$_SERVER['SCRIPT_NAME'];

    $url_redirect .= (@$_GET['ref']=='scn') ? '?typ=scnr&id='.$x['ID'] : '?typ=epg&id='.$x['EpgID'].'#tr'.$x['ID'];

    $url_redirect .= (isset($_GET['view'])) ? '&view='.intval($_GET['view']) : '';

    hop($url_redirect);

} else { // scnr

    if ($x['ELEMENT']['EpgID']) { // TMPL doesn't have $x['ELEMENT']['EpgID']

        $x_emit = [
            'ID' 			=> $x['SCNR']['ID'],
            'MatType' 		=> $x['SCNR']['MatType'],
            'DurEmit' 		=> $x['SCNR']['DurEmit'],
            'NativeType' 	=> $x['ELEMENT']['NativeType'],
            'ElementID'     => $x['ELEMENT']['ID']
        ];

        scnr_duremit($x_emit);
        // scnr_duremit will also run/loop sch_termemit, that's why we can omit it here.
    }

    hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?typ='.TYP.'&id='.$x['ELEMENT']['ID']);
}