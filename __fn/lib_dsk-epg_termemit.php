<?php

// epg/dsk termemit






/**
 * Short-circuit version of epg_dtl_html(). Updates TermEmit column in epg_elements (or epg_scnr_fragments) table,
 * for every element in specified epg (or for every fragment in specified scnr).
 *
 * @param int $id EPG ID or SCNR ID
 * @param string $typ Type: (epg, scnr)
 * @return void
 */
function sch_termemit($id, $typ='epg') {

    $term_rel = $prev_dur = '00:00:00';			// starting values for all kinds of DURATION variables


    if ($typ=='epg') {  // EPG

        $tbl = 'epg_elements';
        $cln = 'EpgID';
        $zero_term = epg_zeroterm(rdr_cell('epgz', 'DateAir', $id));
        $prev_term = $zero_term;

    } else {	// SCNR

        $tbl = 'epg_scnr_fragments';
        $cln = 'ScnrID';
        $scnr = scnr_reader($id);
        $zero_term = rdr_cell('epg_elements', 'TermEmit', $scnr['ElementID']);
        $prev_term = '00:00:00';
    }

    // Loop elements of EPG (or fragments of SCNR). We only need IDs.
    $sql = sprintf('SELECT ID FROM %s WHERE %s=%d AND IsActive AND NativeType NOT IN (8,10) ORDER BY Queue', $tbl, $cln, $id);
    $result = qry($sql);
    while (@list($xid) = mysqli_fetch_row($result)) {

        $x = ($typ=='epg') ? element_reader($xid) : fragment_reader($xid);

        $x['_Dur'] = epg_durations($x, ($typ=='epg') ? 'hms' : 'ms');
        // epg_durations will decide which DUR to use (epg-forc, forc or calc).


        if (@$x['TimeAir']) {   // Fixed TimeAir (FORC)

            $x['_REAL_TimeAir'] = $x['TimeAir'];

        } else  { // Calculated TimeAir (CALC)

            $x['_REAL_TimeAir'] = add_dur2term($prev_term, $prev_dur);
        }


        if ($typ=='epg') {

            if ($x['TermEmit']!=$x['_REAL_TimeAir']) { // If TermEmit not correct: update it.

                $x['TermEmit'] = $x['_REAL_TimeAir'];
                qry('UPDATE '.$tbl.' SET TermEmit=\''.$x['TermEmit'].'\' WHERE ID='.$x['ID']);
            }

        } else { // scnr

            // For SCNRs except those of LIVEAIR(1) type, terms (TimeAir column) represent RELATIVE values, instead absolute,
            // i.e. they are relative to the scnr start.. Otherwise, terms are always absolute.

            if ($scnr['MatType']==1) {

                if (@$x['TimeAir']) { // FIX-TERM

                    // When FIXterm *is* manually set, we must update the RELterm too..
                    $term_rel = terms_diff($x['TimeAir'], $zero_term, 'hms');

                } else { // NOT FIX-TERM

                    $term_rel = sum_durs([$term_rel, $prev_dur]);
                    // We calculate RELATIVE term by adding duration of the previous line to the relative term of
                    // the previous line ($term_rel is not reset on loop, thus it will hold value for previous line)
                }

            } else {

                $term_rel = $x['_REAL_TimeAir'];
            }

            if (!$term_rel) {
                $term_rel = '00:00:00';
            }

            if ($x['TermEmit']!=$term_rel) { // If TermEmit not correct: update it.

                $x['TermEmit'] = $term_rel;
                qry('UPDATE '.$tbl.' SET TermEmit=\''.$x['TermEmit'].'\' WHERE ID='.$x['ID']);
            }
        }

        $prev_term 	= @$x['_REAL_TimeAir'];
        $prev_dur 	= @$x['_Dur']['winner']['dur_hms'];
    }

    if ($typ=='scnr') {
        scnr_duremit($scnr);
    }

}




/**
 * Checks whether specified SCNR is LINKED TO in any EPGs and then runs epg-termemit for each of those epgs
 *
 * @param int $elmid  Element ID
 * @param bool $add_parent  Whether to add parent EpgID to loop array
 * @return void
 */
function scnr_termemit($elmid, $add_parent=true) {

    $epgs = rdr_cln('epg_elements', 'EpgID', 'NativeType=14 AND NativeID='.$elmid); // Get linkers' EPGs

    if ($add_parent) {
        $epgs[] = rdr_cell('epg_elements', 'EpgID', $elmid);
    }

    if ($epgs) {
        $epgs = array_unique($epgs);
        foreach ($epgs as $v) {
            sch_termemit($v, 'epg');
        }
    }
}





/**
 * Updates TERMEMIT for all EPGs and SCNRs which contain specified spice block
 *
 * @param array $x Block (must have ID, BlockType)
 * @return void
 */
function spcblock_termemit($x) {

    list($epgz_arr, $scnrz_arr) = spcblock_usage($x, 'IDs', false);
    // First return array key will hold IDs of EPGs which contain this block.
    // Other key will hold IDs of SCNRs which contain this block.

    // Update EMIT for each block in SCNRs. Do not change the order: First SCNRS, then EPGS!
    if ($scnrz_arr) {
        foreach ($scnrz_arr as $v) {
            sch_termemit($v, 'scnr');
        }
    }

    // Update EMIT for each block in EPGS
    if ($epgz_arr) {
        foreach ($epgz_arr as $v) {
            sch_termemit($v, 'epg');
        }
    }
}






/**
 * Updates TERMEMIT for all EPGs which contain specified film item
 *
 * @param array $x Block
 * @return void
 */
function film_termemit($x) {

    // Get IDs of EPGs which contain this film.
    $epgz_arr = film_usage($x, 'IDs');

    // Update EMIT for each EPG
    if ($epgz_arr) {
        foreach ($epgz_arr as $v) {
            sch_termemit($v, 'epg');
        }
    }
}





/**
 * Updates TERMEMIT for all SCNRs which contain specified story (mdf_dsc)
 *
 * @param array $x Data array. Must have these keys: ID, EPG_SCT_ID.
 * @return void
 */
function dsk_termemit($x) {

    // Get IDs of SCNRs which contain this story.
    $scnrz_arr = stry_usage($x, 'IDs');

    // Update EMIT for each SCNR
    if ($scnrz_arr) {
        foreach ($scnrz_arr as $v) {
            sch_termemit($v, 'scnr');
        }
    }
}





/**
 * STRY EMIT updater. Wraper for dsk_termemit().
 *
 * @param int $story_id Story ID
 * @param array $x Stry data array
 *
 * @return void
 */
function stry_termemit($story_id, $x=null) {

    if (!$x) {
        $x = rdr_row('stryz', 'ID, DurForc, DurEmit', $story_id);
    }

    // Recalculate DurEmit for the story, because atoms duration might have changed
    $mdf['DurEmit'] = epg_durcalc('story', $x['ID']);

    if ($mdf['DurEmit']!=$x['DurEmit']) { // Only if the story DurEmit has changed

        // Update the DurEmit for the story
        qry('UPDATE stryz SET DurEmit=\''.$mdf['DurEmit'].'\' WHERE ID='.$x['ID']);

        // If story has its DurForc set, then DurEmit (calculated dur) is irrelevant, so we would skip
        // updating SCNRs which contain the story.
        if (!$x['DurForc']) {
            $x['EPG_SCT_ID'] = 2; // dsk_termemit() needs this key..
            dsk_termemit($x);
        }
    }
}





/**
 * Update atom duration and then run EMIT update for the parent story.
 *
 * @param int $atomid Atom ID
 * @param array $opt Options array:
 * - Texter (string) - New Texter (for TXT modify)
 * - SpeakerX (int) - New SpeakerX (for SPK modify)
 *
 * @return string $new_dur
 */
function atom_termemit($atomid, $opt) {

    // First, we have to compare old and new duration, to see if we need update at all

    $atom = rdr_row('stry_atoms', 'Duration, StoryID', $atomid);

    $atom['SpeakerX'] = (isset($opt['SpeakerX'])) ? $opt['SpeakerX'] : rdr_cell('stry_atoms_speaker', 'SpeakerX', $atomid);

    $atom['Texter'] = (isset($opt['Texter'])) ? $opt['Texter'] : rdr_cell('stry_atoms_text', 'Texter', $atomid);

    $atom['Speed'] = get_readspeed('stry', 't_per_char', ['stry_id' => $atom['StoryID'], 'speaker_x' => $atom['SpeakerX']]);

    $atom['Duration_NEW'] = atom_txtdur($atom['Texter'], $atom['Speed']);


    // If the duration has changed, we update it and then run termemit update

    if ($atom['Duration']!=$atom['Duration_NEW']) {

        qry('UPDATE stry_atoms SET Duration=\''.$atom['Duration_NEW'].'\' WHERE ID='.$atomid);

        stry_termemit($atom['StoryID']);

        // If the duration has changed, we return it to JS and display it under the speaker name..
        $new_dur = hms2ms($atom['Duration_NEW']);

    } else {

        $new_dur = '';
    }

    return $new_dur;
}





/**
 * Run EMIT update for all stories which are affected by specific SpeakerX (i.e. Speaker#No) for specified scnr
 *
 * @param int $scnrid SCNRID
 * @param int $speakerx Speaker#No
 *
 * @return void
 */
function speakerX_termemit($scnrid, $speakerx) {

    // RS change affects only those stories within this epg-scn which contain at least one atom (of reading type)
    // and have the same SpeakerX.. We run *termemit* update only on those stories.

    $stories_upd = [];  // Storiez for *termemit* loop, i.e. for update

    $stories = rdr_cln('epg_scnr_fragments', 'NativeID', 'NativeType=2 AND ScnrID='.$scnrid); // Get all stories in this scnr

    foreach ($stories as $story_id) {

        $atoms = rdr_cln('stry_atoms', 'ID', 'TypeX=1 AND StoryID='.$story_id); // For each story get only atoms of *reading* type

        foreach ($atoms as $atom_id) {

            $atom['SpeakerX'] = rdr_cell('stry_atoms_speaker', 'SpeakerX', $atom_id);

            if ($atom['SpeakerX'] && $atom['SpeakerX']==$speakerx) { // Finnaly, filter only atoms with specific SpeakerX

                $atom['Duration'] = rdr_cell('stry_atoms', 'Duration', $atom_id);

                $atom['Texter'] = rdr_cell('stry_atoms_text', 'Texter', $atom_id);

                $atom['Speed'] = get_readspeed('stry', 't_per_char', ['stry_id' => $story_id, 'speaker_x' => $atom['SpeakerX']]);

                $dur_new = atom_txtdur($atom['Texter'], $atom['Speed']);

                // If atom duration has changed, we update it, and then add the stry to update list
                if ($atom['Duration']!=$dur_new) {

                    $sql = 'UPDATE stry_atoms SET Duration=\''.$dur_new.'\' WHERE ID='.$atom_id;
                    qry($sql);

                    $stories_upd[] = $story_id;
                }
            }
        }
    }

    $stories_upd = array_unique($stories_upd);

    foreach ($stories_upd as $v) {
        stry_termemit($v);
    }
}





/**
 * Run EMIT update for all stories which are affected by specific SpeakerUID
 *
 * Called when user updates his readspeed settings (either changes velocity or deletes or sets default).
 * We find all active SCNRs where he has been set as speaker, and then for each of them find which Speaker#No is set to
 * this user and then run termemit for that Speaker#No (i.e. SpeakerX).
 *
 * @param int $uid Speaker UID
 * @return void
 */
function speakerUID_termemit($uid) {

    // Get all SCNRs where this user is set as speaker
    $scnrz = qry_numer_arr('SELECT NativeID FROM cn_crw WHERE CrewType=2 AND NativeType=1 AND CrewUID='.$uid);

    foreach($scnrz as $scnrid) {

        $timeair = rdr_cell('epg_elements', 'TimeAir', 'NativeType=1 AND NativeID='.$scnrid);

        if (strtotime($timeair) > strtotime('now')) { // Skip SCNRs which have already passsed
            // !!!!!!!!! Note that we have to disable this condition check when troubleshooting

            // We have to get SpeakerX value for this SpeakerUID. We can do that only if we fetch
            // all speakers (*ordered by ID*) for the specific SCNR, because X value is determined by order.

            $sql = 'SELECT CrewUID AS uid FROM cn_crw WHERE CrewType=2 AND NativeType=1'.
                ' AND NativeID='.$scnrid.' ORDER BY ID';

            $spkrz = array_flip(qry_numer_arr($sql));

            $speakerx = $spkrz[UZID] + 1;

            speakerX_termemit($scnrid, $speakerx);
        }
    }
}














/**
 * Calculate duration of a prg
 *
 * @param array $x SCNR array/object (must have ID, MatType, NativeType)
 * @return string $durcalc Program duration (in HMS)
 */
function scnr_durcalc($x) {

    // SCNRs which are not LIVEAIR(1) type, have MOS duration, so we have to take that into account too.
    if ($x['MatType']!=1 && !isset($x['MOS']['Duration'])) {
        $x['MOS'] = mos_reader($x['ID'], $x['NativeType'], 'Duration');
    }

    $opt = ((isset($x['MOS']['Duration'])) ? ['mosdur' => $x['MOS']['Duration']] : null);

    $durcalc = epg_durcalc('prog', $x['ID'], $opt);

    return $durcalc;
}


/**
 * Updates DurEmit column in epg_scnr table, for specific scnr.
 * (epg_scnr.DurEmit is actually DurCalc value for that scnr)
 *
 * Note: DurEmit exists for one single purpose: to decide whether scnr_termemit() is necessary.
 *
 * @param array $x SCNR array/object (must have ID, MatType, DurEmit, NativeType, ElementID)
 * @param bool $skip_termemit Whether to skip calling scnr_termemit()
 * @return void
 */
function scnr_duremit($x, $skip_termemit=false) {

    $mdf['DurEmit'] = scnr_durcalc($x);

    // Only if DurEmit differs, do update. Otherwise it is unnecessary.

    if ($x['DurEmit']!=$mdf['DurEmit']) {

        qry('UPDATE epg_scnr SET DurEmit=\''.$mdf['DurEmit'].'\' WHERE ID='.$x['ID']); // update epg_scnr.DurEmit

        if (!$skip_termemit) {
            scnr_termemit($x['ElementID']); // update EPGs which contain this scnr or its link
        }
    }
}



