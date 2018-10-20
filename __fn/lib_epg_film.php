<?php

// epg spices - marketing, promo, clips





/**
 * Fetches data array for EPG film object
 *
 * @param int $id epg_films:ID
 * @return array
 *  [ID] => 2
 *  [FilmID] => 2
 *  [FilmParentID] => (is set only if this is episode)
 *  [ScnrID] => 106
 *  [DurType] => approx
 *  [Duration] => 02:04:56
 *  [Title] => Naslov naslov
 *  [DscTitle] => podnaslov podnaslov
 */
function epg_film_reader($id) {
	
	global $cfg;
	
	
	if ($id) {
        $x = qry_assoc_row('SELECT * FROM epg_films WHERE ID='.$id);
    }

	if (!@$x['ID']) return;

    // If *FilmID* is not set, then it is just a placeholder in EPG (probably at planning phase), actual film hasn't been selected
	if (!$x['FilmID']) return $x;


	if (!$x['FilmParentID']) { // NOT SERIAL
		
		$film = qry_assoc_row('SELECT ID AS FilmID, DurApprox, DurReal FROM film WHERE ID='.$x['FilmID']);
		
		$r = array_merge(
            $x,
            $film,
            film_dur($film['DurApprox'], $film['DurReal'], '', 'arr'),
            rdr_row(
                'film_description',
                'Title, DscTitle'.(($cfg['lbl_parental_filmbased']) ? ', Parental' : ''),
                $film['FilmID']
            )
        );
										
		//$r['Caption'] = $r['Title'];
		
	} else { //SERIAL
		
		$episode = rdr_row('film_episodes', 'Title AS EpiTitle, Ordinal, DurApprox, DurReal', $x['FilmID']);
				
		$r = array_merge(
            $x,
            $episode,
            film_dur($episode['DurApprox'], $episode['DurReal'], '', 'arr'),
            rdr_row('film', 'EpisodeCount, TypeID', $x['FilmParentID']),
            rdr_row(
                'film_description',
                'Title, DscTitle, Seasons_arr'.(($cfg['lbl_parental_filmbased']) ? ', Parental' : ''),
                $x['FilmParentID']
            )
        );
										
		//$r['Caption'] = $r['Title'].' ('.$r['Ordinal'].')'.(($r['DscTitle']) ? ' - '.$r['DscTitle'] : '');
	}


	return $r;
}






/**
 * Fetches data array for film item or contract or agency
 *
 * @param int $id ID
 * @param string $typ Object type:	('item', 'contract', 'agent')
 * @return array $x
 */
function film_reader($id=0, $typ='') {

    global $cfg;

	switch ($typ) {

		case 'item':	    $tbl = 'film';			    break;
		case 'contract':	$tbl = 'film_contracts'; 	break;
		case 'agent':		$tbl = 'film_agencies';		break;
	}

	if ($id) {
        $x = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$id);
    }

	$x['ID']  = intval(@$x['ID']);
	$x['TYP'] = $typ;
	$x['TBL'] = $tbl;


	if (!$x['ID']) { // set default values and return
		
		if ($typ=='item') {
			
			$x['LicenceStartTXT'] = t2boxz('', 'date');
			$x['LicenceExpireTXT'] = t2boxz('', 'date');
			$x['DurApproxTXT'] = $x['DurRealTXT'] = t2boxz('', 'time');
			$x['Genres'] = [];
            $x['IsDelivered'] = 1;
            $x['ProdType'] = 2;

			$x['TypeID'] 	= (isset($_GET['filmtyp'])) ? intval($_GET['filmtyp']) : 1;
			$x['SectionID'] = (isset($_GET['filmsct'])) ? intval($_GET['filmsct']) : 1;
			$x['EPG_SCT_ID'] = ($x['TypeID']==1) ? 12 : 13;
			
			$x['Channels'] 	= [];
		}
		
		if ($typ=='contract') {
			$x['DateContractTXT'] = t2boxz('', 'date');
            $x['LicenceStartTXT'] = t2boxz('', 'date');
            $x['LicenceExpireTXT'] = t2boxz('', 'date');
        }
		
		return $x;
	}



	switch ($typ) {
		
		case 'item':
			
			$x['EPG_SCT_ID'] = ($x['TypeID']==1) ? 12 : 13;
			 
			$x['LicenceStartTXT'] 	= t2boxz(@$x['LicenceStart'], 'date');
			$x['LicenceExpireTXT'] 	= t2boxz(@$x['LicenceExpire'], 'date');
			$x['DurApproxTXT'] 		= t2boxz(@$x['DurApprox'], 'time');
			$x['DurRealTXT'] 		= t2boxz(@$x['DurReal'], 'time');


			$x['Contract']['ID'] = rdr_cell('film_cn_contracts', 'ContractID', 'FilmID='.$x['ID']);

			if ($x['Contract']['ID']) {
                $x['Contract'] = film_reader($x['Contract']['ID'], 'contract');
            }
			
			
			$x['DSC'] = rdr_row('film_description', '*', $x['ID']);

			if (@!$x['DSC']['Parental']) {
                $x['DSC']['Parental'] = '';
            }


			$x['Genres'] = rdr_cln('film_cn_genre', 'GenreID', 'FilmID='.$x['ID']);
            if (!$x['Genres']) {
                $x['Genres'] = [];
            }

			$x['Title'] = $x['DSC']['Title'];

			$x['Note'] = note_reader($x['ID'], $x['EPG_SCT_ID']);

			$x['Channels'] = rdr_cln('film_cn_channel', 'ChannelID', 'FilmID='.$x['ID'], 'ID', -1);
            if (!$x['Channels']) {
                $x['Channels'] = [];
            }

            if ($cfg[SCTN]['bcast_cnt_separate']) {

                foreach($x['Channels'] as $v) {
                    $x['BCmax_arr'][$v] = rdr_cell('film_cn_bcasts', 'BCmax', 'FilmID='.$x['ID'].' AND ChannelID='.$v);
                    $x['BCcur_arr'][$v] = rdr_cell('film_cn_bcasts', 'BCcur', 'FilmID='.$x['ID'].' AND ChannelID='.$v);
                }

                // These will be ignored where array is more appropriate
                $x['BCmax'] = $x['BCmax_arr'][CHNL];
                $x['BCcur'] = $x['BCcur_arr'][CHNL];

            } else {

                $x['BCmax'] = rdr_cell('film_cn_bcasts', 'BCmax', 'FilmID='.$x['ID']); // .' AND ChannelID IS NULL'
                $x['BCcur'] = rdr_cell('film_cn_bcasts', 'BCcur', 'FilmID='.$x['ID']); // .' AND ChannelID IS NULL'
                // In film_cn_bcasts table, ChannelID will be null if separate bcast count is off.
                // Thus it is not necessary to add this filter (ChannelID IS NULL).
                // It could be useful only in case we are migrating from one type of bcast count to other, and we want to test
                // so we have mixed migrated and unmigrated rows together, etc..
            }

			break;
			
		case 'contract':
		
			$x['DateContractTXT'] = t2boxz(@$x['DateContract'], 'date');
            $x['LicenceStartTXT'] 	= t2boxz(@$x['LicenceStart'], 'date');
            $x['LicenceExpireTXT'] 	= t2boxz(@$x['LicenceExpire'], 'date');

			if (@$x['AgencyID']) {
				$x['AgencyTXT'] = rdr_cell('film_agencies', 'Caption', $x['AgencyID']);
            }
			break;
			
		case 'agent':
			break;
	}

	return $x;
}

	



	

/**
 * Fetches specified episode of the film serial
 *
 * @param int $i Episode ordinal
 * @param array $x Film array
 * @return array $y	Episode array
 */
function episode_reader($i, $x) {


	if (in_array($i, $x['Episodes'])) { // whether the episode exists
		
		$y = rdr_row('film_episodes', '*', 'ParentID='.$x['ID'].' AND Ordinal='.$i);
		$y['DurApproxTXT'] 	= t2boxz($y['DurApprox'], 'time');
		$y['DurRealTXT'] 	= t2boxz($y['DurReal'], 'time');

	} else {
		
		$y['ID'] = 0;
		$y['Title'] = '';
		$y['DurApproxTXT'] = $y['DurRealTXT'] = t2boxz('', 'time');
	}

	return $y;
}












/**
 * Changes FILM EPISODE array/object to next episode
 *
 * @param array $x Element array/object
 * @param string $epg_new EPG date, used only in case EPG_NEW constant is missing (happens only when called from epgauto procedure)
 *
 * @return array $film $x['FILM'] - changed to next episode
 */
function film_next_episode($x, $epg_new=null) {
	
	global $cfg;

    if (defined('EPG_NEW')) {
        $epg_new = EPG_NEW;
    }


	$film = $x['FILM'];
	
	if (!$film['FilmParentID']) { // If it is just a placeholder, i.e. with no selected film item, then return
        return $film;
    }


    if (@$film['PREMIERES']) {

        // We search for TODAY'S premieres (FIRST broadcasts) of the same serial

		if (isset($film['PREMIERES'][$film['FilmParentID']])) {
            $episode_last['ID'] = $film['PREMIERES'][$film['FilmParentID']];
        }
	}
	
	if (!@$episode_last['ID']) {

        // We search for PAST premieres in the DB. We don't wan't to search FUTURE premieres.

		$sql = 'SELECT epg_films.FilmID FROM epg_elements INNER JOIN epg_films ON epg_elements.NativeID = epg_films.ID '
                .'INNER JOIN epg_scnr ON epg_films.ScnrID = epg_scnr.ID '
                .'WHERE epg_elements.NativeType='.$x['NativeType'].' AND epg_films.FilmParentID='.$film['FilmParentID']
                .' AND epg_scnr.MatType!=3 AND epg_elements.TermEmit<\''.$epg_new.' '.$cfg['zerotime'].'\' '
                .'ORDER BY epg_elements.TermEmit DESC LIMIT 1';
	
		$line = qry_numer_row($sql);
		$episode_last['ID'] = $line[0];
	}

	if (!$episode_last['ID']) {
        return null;
    }

	
	$episode_last['Ordinal'] = rdr_cell('film_episodes', 'Ordinal', $episode_last['ID']);
	
	$episode_next['Ordinal'] = ($x['PRG']['MatType']==3) ? $episode_last['Ordinal'] : (int)$episode_last['Ordinal'] + 1;
    // For reruns we leave the same ordinal, otherwise we increment

    // If last episode was FINAL, then we are starting from the beginning
	if ($episode_next['Ordinal'] > $film['EpisodeCount']) {
        $episode_next['Ordinal'] = 1;
    }


	$r = rdr_row(
        'film_episodes',
        'ID AS FilmID, Title AS EpiTitle, Ordinal, DurApprox, DurReal',
        'ParentID='.$film['FilmParentID'].' AND Ordinal='.$episode_next['Ordinal']
    );

	$r = array_merge(
        $film,
        $r,
        film_dur($r['DurApprox'], $r['DurReal'], '', 'arr')
    );
	
	$r['PREMIERES'][$r['FilmParentID']] = $r['FilmID'];
	
	return $r;
}









/**
 * Get the "winner" duration for film
 *
 * @param string $durapprox Duration APPROXIMATE (HMS)
 * @param string $durreal Duration REAL (HMS)
 * @param string $durdesc Duration DESCRIPTIVE (STRING) - used in (mini)serials
 * @param string $rtyp Return type: (arr, html)
 * @return string|array $r Duration in array or as html
 */
function film_dur($durapprox, $durreal, $durdesc='', $rtyp='html') {
	
	if (!$durdesc) { // APPROX or REAL duration
		
		$durtyp = (!$durreal || $durreal=='00:00:00') ? 'approx' : 'real';
		$dur = ($durtyp=='real') ? $durreal : $durapprox;
		
	} else { // DESC duration, used in (mini)serials, not treated as TIME

		$durtyp = 'desc';
		$dur = $durdesc;
	}
	
	if ($rtyp=='html'){
		
		$r = ($dur) ? '<span class="'.(($durtyp=='real') ? 'durok' : '').'">'.$dur.'</span>' : '&nbsp;';
		
	} else {
		
		$r = [
            'DurType' 	=> $durtyp,
            'Duration' 	=> $dur
        ];
	}

	return $r;
}








/**
 * Adds row to film_episodes table for each episode of the specified serial film. Used in receiver for film serial.
 *
 * @param int $id FilmID
 * @param int $cnt EpisodeCount
 * @return void
 */
function film_serial_build($id, $cnt) {

    $mdf['ParentID'] = $id;

    $episode_1 = rdr_cell('film_episodes', 'ID', 'ParentID='.$mdf['ParentID'].' AND Ordinal=1');
    // This function is called whenever EpisodeCount changes. That means either on NEW serial, (in which case we will
    // add each episode from 1 to EpisodeCount), or on MDF serial, when EpisodeCount is changed specifically,
    // i.e. more episodes are bought (in which case we want to add only rows for NEW episodes)

    for ($i=1; $i<=$cnt; $i++) {

        $mdf['Ordinal'] = $i;

        if ($episode_1) { // MDF situation

            $episode_x = rdr_cell('film_episodes', 'ID', 'ParentID='.$mdf['ParentID'].' AND Ordinal='.$mdf['Ordinal']);

            if ($episode_x) { // Episode already exists, skip it..
                continue;
            }
        }

        receiver_ins('film_episodes', $mdf);
    }
}



/**
 * Update film:TermEPG, i.e. the term of last case of adding the film to epg.
 *
 * (It is used in film list, for *recent* cbo control)
 *
 * @param int $nattype Native type (12 or 13)
 * @param array $film_new New film data
 * @param array $film_cur Old film data
 *
 * @return void
 */
function film_termepg_upd($nattype, $film_new, $film_cur=null) {

    $filmid_cur = ($nattype==12) ? @$film_cur['FilmID'] : @$film_cur['FilmParentID'];
    $filmid_new = ($nattype==12) ? @$film_new['FilmID'] : @$film_new['FilmParentID'];

    if ($filmid_new && (!$filmid_cur || $filmid_new!=$filmid_cur)) {

        receiver_upd('film', ['TermEPG' => TIMENOW], ['ID' => $filmid_new]);
    }
}






/**
 * Get film caption html
 *
 * @param array $x Film data
 * @param array $opt
 *  - typ (int): NativeType - (12, 13)
 *  - epi (bool): show episode title (used only for serials)
 *  - ord (bool): show episode ordinal (used only for serials)
 *  - dsc (bool): show dsc (subtitle for epg)
 *
 * @return string $cpt Film caption
 */
function film_caption($x, $opt) {

    if ($opt['typ']==13) {

        $opt['epi'] = true;
        $opt['ord'] = true;
    }

    if (!isset($opt['epi']))    $opt['epi'] = false;
    if (!isset($opt['ord']))    $opt['ord'] = false;
    if (!isset($opt['dsc']))    $opt['dsc'] = false;


    if ($opt['typ']==13 && !empty($x['Seasons_arr'])) {

        $seasons = explode(',', $x['Seasons_arr']);

        if (count($seasons)>1) {

            if ((int)$seasons[0]!=1) {
                array_unshift($seasons, '1');
            }

            $seasons[count($seasons)] = $x['EpisodeCount']+1;

            foreach ($seasons as $k => $v) {

                if ($x['Ordinal']<(int)$v) {
                    break;
                }
            }

            $x['Ordinal'] = ($k!=1) ? $x['Ordinal'] - ($seasons[$k-1]-1) : $x['Ordinal'];

            $x['EpisodeCount'] = (int)$v-$seasons[$k-1];

            $season = $k;
        }
    }


    $cpt = '<span class="cpt">'.
                @$x['Title'].
                ((isset($season)) ? ' '.$season: '').
                (($opt['epi'] && @$x['EpiTitle']) ? ': '.$x['EpiTitle'] : '').
            '</span>';

    if ($opt['ord'] && @$x['Ordinal']) {
        $cpt .= ' ('.$x['Ordinal'].(($x['EpisodeCount']) ? '/'.$x['EpisodeCount'] : '').')';
    }

    if ($opt['dsc'] && @$x['DscTitle']) {
        $cpt .= ' - '.$x['DscTitle'];
    }

    return $cpt;
}

