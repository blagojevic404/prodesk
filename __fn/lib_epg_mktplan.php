<?php


$mktplanitem_order = 'Position ASC, Queue ASC, ID ASC';
// ORDER has to be the same in the mktplan_epg_arr() and mktplan_items() functions, and in _aj_mktplan.php




/**
 * MKTplan(EPG): Mktplan (pseudo)block data
 *
 * @param array $item MKTPLANITEM (first item in block)
 * @param bool $first_item Whether item is the first item in block (which means we can read BLC data)
 *
 * @return array $block Block data
 */
function mktplan_block_data($item, $first_item=true) {

    $block = array_intersect_key($item,
        array_flip(['BlockTermEPG', 'BlockProgID', 'BlockPos', 'BLC_Wrapclips', 'DateEPG', 'ChannelID']));

    $block['Title'] = mktplan_block_title($item);


    if ($first_item) { // Add BLC data to cpt

        $block['cpt'] = [];
        $block['cpt']['title'] = $block['Title'];

        if ($item['BLC_Label']) {
            $block['cpt']['note'] = '<span class="note">'.$item['BLC_Label'].'</span>';
        }

        if ($item['BLC_Wrapclips']) {
            $block['cpt']['wrapclips'] = mkt_wrapclips_sign($item['BLC_Wrapclips']);
        }

        $block['cpt'] = implode(' ', $block['cpt']);
    }

    return $block;
}



/**
 * MKTplan(EPG): Get block title
 *
 * @param array $item MKTPLANITEM
 * @return string $r Block title
 */
function mktplan_block_title($item) {

    $r = [];

    $r[] = hms2hm($item['BlockTermEPG']);

    $r[] = '['.txarr('arrays', 'epg_mktblc_positions', $item['BlockPos'], 'lowercase').']';

    $r[] = prg_caption($item['BlockProgID'], ['typ' => 'mktplan']);

    $r = implode(' ', $r);

    return $r;
}



/**
 * MKT: Create mktplan block code. This block doesnot exist, thus we have to identify it by its CHANNEL-ID, DATE,
 * TERM, BLOCK-POS & PROGID
 *
 * @param array $data Mktplanitem or Block (from mktplan_block_data() function) data
 *
 * @return string $r Block code
 */
function mktplan_blockcode_create($data) {

    // First digit is ChannelID, next ten digits are TERM, then one for the BlockPos and five for the BlockProgID
    $r =  $data['ChannelID'].
        date('ymdHi', strtotime($data['DateEPG'].' '.$data['BlockTermEPG'])).
        intval($data['BlockPos']).
        sprintf('%05s',$data['BlockProgID']);

    return $r;
}



/**
 * MKT: Get mktplan block data from block code.
 *
 * @param string $mktplan_code Block code.
 *
 * @return array $r Block data
 */
function mktplan_blockcode_explode($mktplan_code) {

    // First digit is ChannelID, next ten digits are TERM, then one for the BlockPos and five for the BlockProgID

    $s = str_split($mktplan_code);

    $r['ChannelID'] = intval($s[0]);

    $r['DateEPG'] = '20'.$s[1].$s[2].'-'.$s[3].$s[4].'-'.$s[5].$s[6];

    $r['BlockTermEPG'] = $s[7].$s[8].':'.$s[9].$s[10].':00';

    $r['BlockPos'] = intval($s[11]);

    $r['BlockProgID'] = intval(substr($mktplan_code, -5));

    return $r;
}



/**
 * MKT: Create MKTEPG block code (ScheduleID + dot + ScheduleType)
 *
 * @param array $block Block data (MktepgID, MktepgType)
 * @return string $r Block code
 */
function mktepg_blockcode_create($block) {

    $r = ($block) ? $block['MktepgID'].'.'.(($block['MktepgType']==1) ? 'epg' : 'scnr') : '';

    return $r;
}

/**
 * MKT: Get mktepg block data (Id, Type) which can be read from block code.
 *
 * @param string $blockcode Block code (ScheduleID + dot + ScheduleType)
 *
 * @return array $r Block data (ID, TYP, TYPint, term_scnr (only for *new*))
 */
function mktepg_blockcode_explode($blockcode) {

    $tmp = explode('.', $blockcode);

    $mktepg['ID'] = intval($tmp[0]);

    $mktepg['TYP'] = $tmp[1]; // (epg, scnr, new)

    $mktepg['TYPint'] = ($mktepg['TYP']=='epg') ? 1 : 2; // 'scnr' is 2, and 'new' is also 2 because 'new' is always 'scnr'

    if ($mktepg['TYP']=='new') {
        $mktepg['term_scnr'] = $tmp[2];
    }

    return $mktepg;
}



/**
 * MKT: Get block dur
 *
 * @param array $items MKT Block items
 * @param int|bool $wrapclip_code Wrapclips code
 *                  (0 - both, 1 - only front, 2 - only back, 3 - none; FALSE - skip adding wrapclips)
 * @param string $r_format Return time format: (hms, ms)
 *
 * @return string $blockdur Block dur
 */
function mkt_block_dur($items, $wrapclip_code=false, $r_format='ms') {

    if ($wrapclip_code!==false) {
        $items = mkt_block_wrapclips($items, $wrapclip_code);
    }

    $itemdurz = [];

    foreach ($items as $v) {

        $tbl = (isset($v['ItemType']) && $v['ItemType']==5) ? 'epg_clips' : 'epg_market';

        if ($v['ItemID']) {
            $itemdurz[] = rdr_cell($tbl, 'DurForc', $v['ItemID']);
        }
    }

    if ($itemdurz) {

        $blockdur = sum_durs($itemdurz);

        if ($r_format=='ms') {
            $blockdur = hms2ms($blockdur);
        }

    } else {

        $blockdur = null;
    }

    return $blockdur;
}



/**
 * MKT: Add WRAPCLIPS (opening and closing clips) to block items array
 *
 * @param array $items MKT Block items
 * @param int $wrapclip_code Wrapclips code (0 - both, 1 - only front, 2 - only back, 3 - none)
 *
 * @return array $items MKT Block items
 */
function mkt_block_wrapclips($items, $wrapclip_code) {

    if (in_array($wrapclip_code, [2,3])) {
        $clip_opening = null;
    } else {
        $clip_opening = rdr_cell('epg_clips', 'ID', 'CtgID=1 AND Placing IN (1,3)'); // mkt opening clip
    }

    if (in_array($wrapclip_code, [1,3])) {
        $clip_closing = null;
    } else {
        $clip_closing = rdr_cell('epg_clips', 'ID', 'CtgID=1 AND Placing IN (2,3)'); // mkt closing clip
    }

    if ($clip_opening) {
        array_unshift($items, ['ItemID' => $clip_opening, 'ItemType' => 5]);
    }

    if ($clip_closing) {
        $items[] = ['ItemID' => $clip_closing, 'ItemType' => 5];
    }

    return $items;
}



/**
 * MKT: Get timeframe data for the specified term
 *
 * @param string $term Term, in DATETIME format (YYYY-mm-dd hh:mm:ss)
 *
 * @return string $key Start term in ddhhmm format, to be used as a key in durz array
 */
function mkt_timeframe($term) {

    global $cfg;

    $mm_cutter = $cfg[SCTN]['mkt_timeframe_cutter_mm'];

    $t_term = strtotime($term);

    $frame['start'] = date('Y-m-d H:'.$mm_cutter.':00', $t_term);
    $t_start = strtotime($frame['start']);

    if ($t_start > $t_term) {
        $frame['start'] = date('Y-m-d H:i:s', strtotime($frame['start'].' -1 hour'));
        $t_start = strtotime($frame['start']);
    }

    $frame['finit'] = date('Y-m-d H:i:s', strtotime($frame['start'].' +1 hour'));

    $key = date('dHi', $t_start);

    return $key;
}



/**
 * MKT: Get timeframe data for the specified term
 *
 * @param string $term Term, in DATETIME format (YYYY-mm-dd hh:mm:ss)
 * @param string $dur Mktitem duration
 * @param bool $gratis Whether item is gratis i.e. free of charge
 * @param string $typ Type (epg, plan, hourly)
 *
 * @return array $r Timeframe calculated data
 * - css (string) - Css for the tframe cell
 * - out (string) - Text (current tframe duration in hhmm) for the tframe cell
 */
function mkt_timecalc($term, $dur, $gratis=false, $typ='plan') {

    static $tframe_durz = []; // Timeframes which will hold duration for each of them
    // We use *static* statement so the function would remember the value of a function variable from function call to function call.


    if ($typ=='hourly') {
        return $tframe_durz;
    }

    global $cfg;

    $tframe_limit = $cfg[SCTN]['mkt_timeframe_limit_ss'];


    $dur = ($dur) ? rcv_datetime('hms_nozeroz', rcv_hms($dur)) : null;

    $key = mkt_timeframe($term);
    // If you want to follow what time-frame an item belongs to, print $key

    $tcalc['css'] = '';

    if ($dur) {

        if ($gratis) {
            $dur = '00:00:00';
        }

        if (isset($tframe_durz[$typ][$key])) {
            $tframe_dur = $tframe_durz[$typ][$key] + dur2secs($dur);
        } else {
            $tframe_dur = dur2secs($dur);
        }

    } else {
        $tframe_dur = 0;
    }

    if ($tframe_dur > $tframe_limit) { // TFRAME ERROR
        $tcalc['out'] = $tframe_dur - $tframe_limit;
        $tcalc['css'] = 'err_dngr';
    } else {
        $tcalc['out'] = $tframe_dur;
    }

    $tcalc['out'] = secs2dur($tcalc['out']);

    $tframe_durz[$typ][$key] = $tframe_dur;


    return $tcalc;
}



/**
 * Check whether minimum distance between two mkt blocks within scnr is kept
 *
 * @param array $mktepg MKTEPG - block data
 *
 * @return string $css Css for the term cell
 */
function mkt_time_distance($mktepg) {

    global $cfg;

    static $prev;

    $css = null;


    if (!empty($prev)) {

        if ($prev['ElementID']==$mktepg['ELEMENT']['ID'] &&
            strtotime($prev['TermNextOK']) > strtotime($mktepg['TermEmit'])) {

            $css = ' err_dngr';
        }
    }

    $prev['ElementID'] = $mktepg['ELEMENT']['ID'];
    $prev['TermEnd'] = add_dur2term($mktepg['TermEmit'], $mktepg['_Dur']['winner']['dur_hms']);
    $prev['TermNextOK'] = add_dur2term($prev['TermEnd'], '00:'.$cfg[SCTN]['mkt_min_distance_in_scnr'].':00');

    //Regulation: Within SCNRz mkt blocks must have minimum distance of X minutes

    return $css;
}



/**
 * MKT: Get mktplan items (by default only IDs)
 *
 * @param array $mktplan_block_data MKT-PLAN Block data which we use to identify the block and get the items
 *                                  (must have: DateEPG, BlockProgID, BlockPos, BlockTermEPG, ChannelID)
 * @param string $typ Type
 *
 * @return array $r Mktplan items
 */
function mktplan_items($mktplan_block_data, $typ=null) {

    global $mktplanitem_order;

    // $opt['limit'] - true, false
    // $opt['rtyp']  - table, row, separate
    // $opt['arr']   - num-id, id-assoc, id-item

    if ($typ=='rcv_blc_label') {

        $opt['clnz'] = 'ID, ItemID, BLC_Wrapclips, BLC_Label';
        $opt['limit'] = true;
        $opt['rtyp'] = 'row';

    } elseif ($typ=='rcv_mdfall') {

        $opt['clnz'] = 'ID';
        $opt['limit'] = false;
        $opt['rtyp'] = 'table';
        $opt['arr'] = 'num-id';

    } elseif ($typ=='blcdata_row') {

        $opt['clnz'] = 'BLC_Wrapclips, BLC_Label';
        $opt['limit'] = true;
        $opt['rtyp'] = 'row';

    } elseif ($typ=='blcdata_table') {

        $opt['clnz'] = 'ID, BLC_Wrapclips, BLC_Label';
        $opt['limit'] = false;
        $opt['rtyp'] = 'table';
        $opt['arr'] = 'id-assoc';

    } elseif ($typ=='itemid') {

        $opt['clnz'] = 'ID, ItemID';
        $opt['limit'] = false;
        $opt['rtyp'] = 'table';
        $opt['arr'] = 'id-item';

    } elseif ($typ=='sync_block_copy') {

        $opt['clnz'] = 'ID, ItemID, BLC_Wrapclips, BLC_Label';
        $opt['limit'] = false;
        $opt['rtyp'] = 'separate';
        $opt['arr'] = 'id-item';
    }


    $sql = 'SELECT '.$opt['clnz'].' FROM epg_market_plan WHERE '.mktplan_items_where($mktplan_block_data).
        ' ORDER BY '.$mktplanitem_order.(($opt['limit']) ? ' LIMIT 1' : '');
    $result = qry($sql);


    if ($opt['rtyp']=='row') {
        $line = mysqli_fetch_assoc($result);
        return $line;
    }

    $items = [];
    while ($line = mysqli_fetch_assoc($result)) {

        if ($opt['arr']=='id-item') {
            $items[$line['ID']] = $line['ItemID'];
        }

        if ($opt['arr']=='id-assoc') {
            $id = $line['ID']; unset($line['ID']);
            $items[$id] = $line;
        }

        if ($opt['arr']=='num-id') {
            $items[] = $line['ID'];
        }

        if ($opt['rtyp']=='separate') {

            if (empty($not_first)) {
                $blcdata = $line; $not_first = true;
            }
        }
    }

    if ($opt['rtyp']=='separate') {

        return [array_values($items), $blcdata];
        // This may log an "PHP Notice" error on $blcdata being undefined if the item term falls on clock changing
        // (daylight saving change).. Ignore..

    } else { // $opt['rtyp']=='table' (as rtyp 'row' jumped off right after query)

        return $items;
    }
}


/**
 * MKT: Get *WHERE* part of the SQL query for mktplan block
 *
 * @param array $item Mktplan item data (DateEPG, BlockProgID, BlockPos, BlockTermEPG)
 * @param string $case Case (queue - used in mkplan items QUEUE change)
 *
 * @return string $r SQL Where
 */
function mktplan_items_where($item, $case=null) {

    $where = [];

    $where[] = 'ChannelID='.$item['ChannelID'];
    $where[] = 'DateEPG=\''.$item['DateEPG'].'\'';
    $where[] = 'BlockProgID='.$item['BlockProgID'];
    $where[] = 'BlockPos='.$item['BlockPos'];
    $where[] = 'BlockTermEPG'.(($item['BlockTermEPG']) ? '=\''.$item['BlockTermEPG'].'\'' : ' IS NULL');


    if ($case=='queue') {

        if (!empty($item['ID'])) {
            $where[] = 'ID<>'.$item['ID'];
        }

        $where[] = 'Position='.$item['Position'];
    }


    $where = implode(' AND ', $where);

    return $where;
}



/**
 * MKT: epg term sorting function (used as a user-defined function called from uksort())
 *
 * We need the 00:00-05:59 to be sorted AFTER the 06:00-23:59
 */
function epg_term_sort($x, $y) {

    $x = timehms2timeint($x, 'hm');
    $y = timehms2timeint($y, 'hm');

    if ($x > $y) {
        return true;
    } elseif ($x < $y) {
        return false;
    } else {
        return 0;
    }
}



/**
 * Get queue value to be used for new item (max of the existing values + 1)
 *
 * @param string $tbl Table
 * @param string $where SQL WHERE
 *
 * @return int $qu Queue value to be used for new item
 */
function qu_max($tbl, $where=null) {

    if ($where) {

        $qu_max = rdr_cell($tbl, 'MAX(Queue)', $where);

    } else {

        $qu_max = qry_numer_var('SELECT MAX(Queue) FROM '.$tbl);
    }

    $qu = ($qu_max!==null) ? (int)$qu_max + 1 : null;

    return $qu;
}



/**
 * MKTplan: RCV for BLC_Label when adding it via NEW mktplanitem (abort&omg if BLC_Label for the block is already set)
 *
 * @param array $mdf MKTPLANITEM
 * @param string $post Posted BLC_Label value
 * @return void
 */
function mktplan_rcv_blc_label($mdf, $post) {

    global $tx;

    $z['BLC_Label'] = $post;

    if (!$z['BLC_Label']) {
        return;
    }

    $cur = mktplan_items($mdf, 'rcv_blc_label'); // FIRST mpi in block

    if (!empty($cur['BLC_Label']) && $cur['BLC_Label']!=$z['BLC_Label']) { // abort (label already exists)

        omg_put('info', $tx[SCTN]['MSG']['err_block_label_exists'].': <b>'.$cur['BLC_Label'].'</b>');

    } else { // save

        qry('UPDATE epg_market_plan SET '.receiver_sql4update($z).' WHERE ID='.$cur['ID']);
    }
}
