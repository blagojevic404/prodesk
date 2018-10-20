<?php



/**
 * MKTsync: Receiver
 *
 * @param string $mktplan_code MKTPLAN block code
 * @param string $mktepg_pick MKTEPG pick
 * @param int $epgid EPG ID
 *
 * @return void
 */
function mktsync_rcv($mktplan_code, $mktepg_pick, $epgid) {

    global $tx;

    if (!$mktepg_pick) { // User picked *not defined* value in the cbo

        $mktepg_typ = 'none';

    } else {

        $mktepg = mktepg_blockcode_explode($mktepg_pick);

        if ($mktepg['TYP']=='new') { // User picked SYNC_ADDBLOCK value in the cbo

            if (empty($mktepg['ID'])) return; // Didnot find any epg element to suggest for SYNC_ADDBLOCK

            $mktepg_typ = 'new';

        } else { // User picked one of the mktepgz values in the cbo

            $mktepg_typ = 'associate';
            $mktepg_code = $mktepg_pick;
        }
    }


    // Check for existing associations

    $mktepg_existing = rdr_row('epg_market_siblings', 'ID, MktepgID, MktepgType', 'MktplanCode='.$mktplan_code);
    $mktepg_existing_code = ($mktepg_existing) ? mktepg_blockcode_create($mktepg_existing) : null;

    //if ($mktepg_typ!='new' && $mktepg_existing_code!=$mktepg_pick) {
    if (($mktepg_typ=='associate' && $mktepg_existing_code!=$mktepg_code) ||
        ($mktepg_typ=='none' && !empty($mktepg_existing_code))) {

        // Check whether this mktplan is already associated to some other mktepg. If so, delete the old association.

        if ($mktepg_existing_code) {
            qry('DELETE FROM epg_market_siblings WHERE ID='.$mktepg_existing['ID']);
        }

        if ($mktepg_typ=='associate') {

            // Check whether the selected mktepg is already associated to some other mktplan. If so, omg!

            $mktplan_existing = rdr_cell('epg_market_siblings', 'MktplanCode',
                'MktepgID='.$mktepg['ID'].' AND MktepgType='.$mktepg['TYPint']);

            if ($mktplan_existing) {

                $mktepg_x = ($mktepg['TYP']=='epg') ? element_reader($mktepg['ID']) : fragment_reader($mktepg['ID']);

                $block = mktplan_block_data(mktplan_blockcode_explode($mktplan_existing));

                if ($mktepg['TYP']=='epg') {
                    $termemit = $mktepg_x['TermEmit'];
                } else {
                    $termemit = add_dur2term($mktepg_x['ELEMENT']['TermEmit'], $mktepg_x['TermEmit']);
                }

                omg_put('error', '<b>'.date('H:i:s', strtotime($termemit)).' '.
                    mktepg_block_cpt($mktepg_x, $mktepg['TYP'], 'msg').'</b> '.
                    $tx[SCTN]['MSG']['err_mktplan_assoc'].
                    ' <b>'.$block['cpt'].' ('.hms2hm($block['BlockTermEPG']).')</b>');

                return;
            }
        }
    }


    if ($mktepg_typ=='none') {    // If the user has selected 'not defined' value in the cbo, we skip further sync
        return;
    }


    if ($mktepg_typ=='new') { // SYNC_ADDBLOCK

        mktepg_new('scnr', $mktplan_code, $epgid, ['mktepg' => $mktepg]);

        // WAS: $mktepg = mktsync_addblock_element($epgid, $mktplan);
        // Instead of calling mktsync_addblock_element() here again (in SYNC-RCV), we pass the element data from SYNC-MDF
        // in the cbo value (which we otherwise use to send the $mktepg_pick) and thus we can now skip it here..
        // Not the cleanest solution, but we can thus avoid calling twice the same function in two different scripts
        // which could lead to unexpected (different) results if something would change in epg between these two calls..

    } else { // ASSOCIATE

        if ($mktepg_existing_code!=$mktepg_pick) { // Unless already associated to the same block

            mktepg_new($mktepg['TYP'], $mktplan_code, $epgid, ['mktepg' => $mktepg, 'casetyp' => 'associate']);
        }
    }
}



/**
 * MKTsync: Add new mktepg block and element/fragment
 *
 * @param string $typ Type (epg, scnr)
 * @param string $mktplan_code MKTPLAN block code
 * @param int $epgid EPG ID
 * @param array $opt
 *  - ref_elemid (int): Element ID of the element before which we insert mktepg (only for epg type)
 *  - mktepg (array): Mktepg blockcode data (only for scnr type)
 *  - casetyp (string): (associate)
 *
 * @return void
 */
function mktepg_new($typ, $mktplan_code, $epgid, $opt) {


    $tbl = ($typ=='epg') ? 'epg_elements' : 'epg_scnr_fragments';


    if (@$opt['casetyp']=='associate') { // Element/fragment already exists


        // Check whether picked element/fragment exists
        $mktepg = rdr_row($tbl, 'ID, NativeID', $opt['mktepg']['ID']);

        if (!$mktepg['ID']) {
            return; // error-catcher
        }


        $mktepg_code = $mktepg['ID'].'.'.$typ;

        // Add sibling association (associate mktplan block to this existing element/fragment)
        mktsync_sibling_associate($mktplan_code, $mktepg_code);


        if ($mktepg['NativeID']) { // Spice block already exists. (We presume its contents are correct.)
            return;
        }

        // If mktepg element doesnot have an mkt block selected, create an mkt block and copy items from mktplan
    }


    $mktplan = mktplan_blockcode_explode($mktplan_code);


    if (@$opt['casetyp']=='associate') { // Element/fragment already exists

        // We need TIMEAIR for the caption of new block

        if ($opt['mktepg']['TYP']=='epg') {

            $mktplan['Timeair'] = rdr_cell('epg_elements', 'TermEmit', $opt['mktepg']['ID']);

        } else { // scnr

            $sch_row = rdr_row('epg_scnr_fragments', 'TermEmit, ScnrID', $opt['mktepg']['ID']);
            $scnrid = $sch_row['ScnrID'];
            $scnr = scnr_reader($scnrid);
            $mktplan['Timeair'] = add_dur2term(rdr_cell('epg_elements', 'TermEmit', $scnr['ElementID']), $sch_row['TermEmit']);
        }

    } else {

        if ($typ=='epg') {

            $mktplan['Timeair'] = $mktplan['DateEPG'].' '.$mktplan['BlockTermEPG'];

        } else {

            $element = rdr_row('epg_elements', 'NativeID, NativeType, TermEmit', $opt['mktepg']['ID']);

            $scnrid = scnrid_universal($element['NativeID'], $element['NativeType']);

            // Get TIMEAIR: Add TermSCNR (Term within SCNR) to TermEmit (SCNR start)
            $mktplan['Timeair'] = add_dur2term($element['TermEmit'], $opt['mktepg']['term_scnr']);

            // MatType decides whether the term will be absolute (if liveair) or relative (if recorded).
            $mattyp = rdr_cell('epg_scnr', 'MatType', $scnrid);
            $timeair = ($mattyp==1) ? $mktplan['Timeair'] : $mktplan['DateEPG'].' '.$opt['mktepg']['term_scnr'];
        }
    }


    // Add new mkt spice block
    $spc_block_id = mktsync_spcblock_create($epgid, $mktplan['Timeair']); // Timeair is for the caption

    // Copy items from mktplan to new mkt spice block
    list($items, $blcdata) = mktplan_items($mktplan, 'sync_block_copy');
    mktsync_spcblock_items($spc_block_id, $items, $blcdata);


    if (@$opt['casetyp']=='associate') { // Element/fragment already exists

        // Connect new mkt spice block to element/fragment
        qry('UPDATE '.$tbl.' SET NativeID='.$spc_block_id.' WHERE ID='.$opt['mktepg']['ID']);

        $mktepg_id = $opt['mktepg']['ID'];

    } else {

        // Add new element/fragment containing new mkt spice block

        $mdf = [
            'NativeID' => $spc_block_id,
            'NativeType' => 3,
            'IsActive' => 1
        ];

        if ($typ=='epg') {

            $mdf['Queue'] = rdr_cell('epg_elements', 'Queue', wash('int', $opt['ref_elemid']));
            $mdf['EpgID'] = $epgid;

        } else { // scnr

            $mdf['Queue'] = scnr_get_qu($scnrid, 'ordinal', $opt['mktepg']['term_scnr']);
            $mdf['ScnrID'] = $scnrid;
            $mdf['TimeAir'] = $timeair;
        }

        qry('UPDATE '.$tbl.' SET Queue=Queue+1 WHERE Queue>='.$mdf['Queue'].' AND '.
            (($typ=='epg') ? 'EpgID='.$mdf['EpgID'] : 'ScnrID='.$mdf['ScnrID']));


        $mktepg_id = receiver_ins($tbl, $mdf);
        $mktepg_code = $mktepg_id.'.'.$typ;

        // Add sibling association (associate mktplan block to this new element/fragment)
        mktsync_sibling_associate($mktplan_code, $mktepg_code);
    }


    if ($typ=='scnr') {
        sch_termemit($scnrid, 'scnr');
    }


    //Add MktPlan LABEL to epg_tips on the MktEpg side
    if ($blcdata['BLC_Label']) {
        epg_tip_receiver(['schtyp' => $typ, 'schline' => $mktepg_id, 'tiptyp' => 1], $blcdata['BLC_Label']);
    }
}



/**
 * MKTsync: Try to suggest the right epg element (i.e. parent) to use in SYNC_ADDBLOCK operation
 *
 * @param int $epgid EPG ID
 * @param array $block MKtplan Block data
 * @param string $typ Type (scnr, epg)
 *
 * @return array $element Data for the epg element which is picked to be used in SYNC_ADDBLOCK operation (ID, term_scnr)
 */
function mktsync_addblock_element($epgid, $block, $typ='scnr') {

    global $cfg;

    $block_term = timehms2timeint($block['BlockTermEPG']);
    $block_progid = $block['BlockProgID'];


    // Get ID list of epg elements which apply for the sync

    switch ($block_progid) {
        case 65535: $nattyp = 12; $block_progid = 0; break;
        case 65534: $nattyp = 13; $block_progid = 0; break;
        default: $nattyp = 1;;
    }

    // Filter only elements which match specified NativeType
    $result = qry('SELECT ID, NativeID, TermEmit, Queue FROM epg_elements WHERE EpgID='.$epgid.' AND NativeType='.$nattyp.
        ' ORDER BY TermEmit');

    while ($line = mysqli_fetch_assoc($result)) {

        // Filter only elements which match specified ProgID. (Can apply only to programs..)
        if ($block_progid && $block_progid!=rdr_cell('epg_scnr', 'ProgID', $line['NativeID'])) {
            continue;
        }

        $elements[term2timeint($line['TermEmit'])] = $line['ID'];
    }

    if (empty($elements)) {
        return null;
    }


    // Pick the element where we shall add the block

    if ($typ=='scnr') {

        krsort($elements);

        foreach ($elements as $k => $v) {

            if ($k <= $block_term) { // Element start term must be lower than the cut term, i.e. mktplan block term

                $term_finito = mktsync_element_next($v, $epgid, 'var');

                if ($term_finito < $block_term) { // Also, it must fall within the element, i.e. before its end
                    continue;
                }

                $element['ID'] = $v;

                // Calculate TERMSCNR - it is *relative* term (to the start of the program) and mktplan block-term is an
                // *absolute term*.. Therefore we substract the program *start* term from the mktplan block-term
                // (i.e. program *cut* term), in order to get the desired TERMSCNR
                $element['term_scnr'] = timeint_diff($block_term, $k, $block['DateEPG']);

                break;
            }
        }


    } else { // epg


        $b['term_before'] = timeint_calc($block_term, $cfg[SCTN]['mkt_slct_float_t_before'], '-');
        $b['term_after'] = timeint_calc($block_term, $cfg[SCTN]['mkt_slct_float_t_after'], '+');

        foreach ($elements as $k => $v) {

            if ($block['BlockPos']==2) { // Behind: switch to NEXT element

                $elem_next = mktsync_element_next($v, $epgid, 'arr');

                $k = $elem_next['TermINT'];
                $v = $elem_next['ID'];
            }

            if ($k < $b['term_before'] || $k > $b['term_after']) {
                continue;
            }

            // Pick the one with the CLOSEST term

            $diff = abs($block_term-$k);

            if (!isset($diff_champion) || $diff_champion > $diff) {

                $diff_champion = $diff;

                $element = $v;
            }
        }
    }


    return (!empty($element)) ? $element : null;
}



/**
 * MKTsync: Get data for the next epg element
 *
 * @param int $elemid Element ID
 * @param int $epgid EPG ID
 * @param string $rtyp Return type (var, arr)
 *
 * @return string|array $elem_next Either only TermINT for the next element, or array with TermINT + ID
 */
function mktsync_element_next($elemid, $epgid, $rtyp='var') {

    global $cfg;


    $elem_data = rdr_row('epg_elements', 'DurForc, TermEmit, Queue', $elemid);

    $where = 'EpgID='.$epgid.' AND Queue>'.$elem_data['Queue'];
    $order = 'TermEmit';

    if (!$elem_data['DurForc'] || $elem_data['DurForc']=='00:00:00') {

        $elem_next = rdr_row('epg_elements', 'ID, TermEmit', $where, $order);

    } else {

        $elem_next['TermEmit'] = add_dur2term($elem_data['TermEmit'], $elem_data['DurForc']);

        $elem_next['ID'] = rdr_cell('epg_elements', 'ID', $where, $order);
    }


    if ($cfg['company_id']==1) { // RTRS

        if ($elem_next['ID']) {

            $elem_natdata = rdr_row('epg_elements', 'NativeID, NativeType', $elem_next['ID']);

            if ($elem_natdata['NativeType']==1){

                $progid = rdr_cell('epg_scnr', 'ProgID', $elem_natdata['NativeID']);

                if ($progid==564 || $progid==591) { // News within morning show

                    $next = mktsync_element_next($elem_next['ID'], $epgid, $rtyp);
                    return $next;
                }
            }
        }
    }


    $elem_next['TermINT'] = term2timeint($elem_next['TermEmit']);

    return ($rtyp=='var') ? $elem_next['TermINT'] : $elem_next;
}



/**
 * MKTsync: Get the synchronization list for specified epg
 *
 * @param array $epg EPG data (we use ID, DateAir, ChannelID)
 *
 * @return array $mktplanz Synchronization list, containing epg, scnr, and omg (failure) subarrays
 */
function mktsync_diagnostic($epg) {

    $mktplanz_raw = mktplan_epg_arr($epg, 'short');

    usort($mktplanz_raw, 'epg_term_sort'); // We need the 00:00-05:59 to be sorted AFTER the 06:00-23:59

    $mktplanz = [];

    $mktplan['DateEPG'] = $epg['DateAir'];
    $mktplan['ChannelID'] = $epg['ChannelID'];

    foreach ($mktplanz_raw as $v) {

        $v = explode('.', $v);

        $mktplan['BlockTermEPG'] = $v[0].':00';
        $mktplan['BlockPos'] = $v[1];
        $mktplan['BlockProgID'] = $v[2];

        $mktplan_code = mktplan_blockcode_create($mktplan);

        $typ = ($mktplan['BlockPos']) ? 'epg' : 'scnr';

        if ($typ=='scnr') {

            $element_data = mktsync_addblock_element($epg['ID'], $mktplan);

            if ($element_data) {

                $slcted = $element_data['ID'].'.new.'.$element_data['term_scnr'];

                $mktplanz[$typ][$mktplan_code] = $slcted;

            } else {
                $mktplanz['omg'][] = $v[0];
            }

        } else { // epg

            $element_id = mktsync_addblock_element($epg['ID'], $mktplan, 'epg');

            if ($element_id) {

                $mktplanz[$typ][$mktplan_code] = $element_id;

            } else {
                $mktplanz['omg'][] = $v[0];
            }
        }
    }

    return $mktplanz;
}



/**
 * MKTsync: Create mkt spice block
 *
 * @param int $epgid EPG ID
 * @param string $timeair TimeAir used for the CAPTION
 *
 * @return int $block_id Block ID
 */
function mktsync_spcblock_create($epgid, $timeair) {

    $chnl = rdr_cell('epgz', 'ChannelID', $epgid);

    $mdf['BlockType'] = 3;
    $mdf['Caption'] = date('Ymd/H:i', strtotime($timeair)); // Create caption from the timeair
    $mdf['UID']	= UZID;
    $mdf['TermAdd']	= TIMENOW;
    $mdf['ChannelID'] = $chnl;
    $mdf['CtgID'] = 0;

    $block_id = receiver_ins('epg_blocks', $mdf);

    return $block_id;
}




/**
 * MKTsync: Copy items from mktplan to mkt spice block
 *
 * @param int $spc_block_id Mkt spice BLOCK ID
 * @param array $items Mktplan items
 * @param array $blcdata BLC data (BLC_Wrapclips, BLC_Label)
 *
 * @return void
 */
function mktsync_spcblock_items($spc_block_id, $items, $blcdata) {

    if (!$spc_block_id) {
        return; // No spc block
    }

    if (empty($items)) {
        return; // Nothing to copy
    }


    $items_rich = [];

    foreach ($items as $v) {
        $items_rich[] = ['ItemID' => $v, 'ItemType' => 3];
    }

    $items_rich = mkt_block_wrapclips($items_rich, $blcdata['BLC_Wrapclips']); // Add WRAPCLIPS (opening and closing clips) to items


    foreach ($items_rich as $k => $v) {

        $sql = sprintf('INSERT INTO epg_cn_blocks (BlockID, NativeType, NativeID, Queue, IsActive) VALUES (%u,%u,%u,%u,%u)',
            $spc_block_id, $v['ItemType'], $v['ItemID'], $k, 1);
        qry($sql, LOGSKIP);
    }


    // Calculate and update DurEmit
    $block_dur = mkt_block_dur($items_rich);
    qry('UPDATE epg_blocks SET DurEmit=\''.$block_dur.'\' WHERE ID='.$spc_block_id);
}




/**
 * MKTsync: Make sibling association (associate mktplan block to EPG element or SCNR fragment)
 *
 * @param string $mktplan_code MKTPLAN block code
 * @param string $mktepg_code MKTEPG block code (ScheduleID + dot + ScheduleType)
 * @return void
 */
function mktsync_sibling_associate($mktplan_code, $mktepg_code) {

    $mktepg = mktepg_blockcode_explode($mktepg_code);

    $mdf['MktplanCode'] = $mktplan_code;
    $mdf['MktepgID'] = $mktepg['ID'];
    $mdf['MktepgType'] = $mktepg['TYPint'];

    receiver_mdf('epg_market_siblings', ['MktplanCode' => $mdf['MktplanCode']], $mdf);
}

