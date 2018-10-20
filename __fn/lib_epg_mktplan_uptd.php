<?php

// Mkt uptodate functions



/**
 * MKT: From mktplan code, get mktepg (i.e. sibling) data
 *
 * @param string $mktplan_code Mktplan code
 * @return array $r Mktepg (i.e. sibling) data
 */
function mktuptd_sibling_get($mktplan_code) {

    $r = rdr_row('epg_market_siblings', 'MktepgID, MktepgType', 'MktplanCode='.$mktplan_code);

    if (!$r) { // no sibling
        return $r;
    }

    $tbl = ($r['MktepgType']==1) ? 'epg_elements' : 'epg_scnr_fragments';

    $r['BlockID'] = rdr_cell($tbl, 'NativeID', $r['MktepgID']);

    return $r;
}



/**
 * MKT: Reset mkt spice block content, i.e. clear items + copy items from mktplan
 *
 * @param array $z mktplan and mktepg (i.e. sibling) data
 * @return void
 */
function mktuptd_mktepg_reset($z) {

    $spc_block_id       = $z['sibling']['BlockID']; // Mkt spice block ID
    $items              = $z['plan']['items']; // Items to be copied to mkt spice block
    $mktplan_code       = $z['plan']['code']; // Mktplan code
    $mktplan_block_data = $z['plan']['item']; // Mktplan block data


    if (!$spc_block_id) {
        return; // No spc block
    }

    // Delete all items from the mkt spice block
    qry('DELETE FROM epg_cn_blocks WHERE BlockID='.$spc_block_id);


    if ($items) {

        // Copy items from mktplan to the mkt spice block
        $blcdata = mktplan_items($mktplan_block_data, 'blcdata_row');
        mktsync_spcblock_items($spc_block_id, $items, $blcdata);

    } else {

        // Delete the sibling association
        qry('DELETE FROM epg_market_siblings WHERE MktplanCode='.$mktplan_code);

        // Delete the spc block
        qry('DELETE FROM epg_blocks WHERE ID='.$spc_block_id);
        mos_deleter($spc_block_id, 3);
        crw_deleter($spc_block_id, 3);

        // Delete spc block from schedule element/fragment
        $tbl = ($z['sibling']['MktepgType']==1) ? 'epg_elements' : 'epg_scnr_fragments';
        qry('UPDATE '.$tbl.' SET NativeID=0 WHERE ID='.$z['sibling']['MktepgID']);
    }
}



/**
 * MKT: Wrapper for mktuptd_mktepg_reset()
 *
 * @param int $code Mktplan blockcode
 * @param bool $skip_timecheck Whether to skip time check. Used for manual uptodate (init via equaling button).
 * @return void
 */
function mktuptd_mktepg_reset_wrapper($code, $skip_timecheck=false) {

    global $tx;


    $z['sibling'] = mktuptd_sibling_get($code);

    if (!$z['sibling']['BlockID']) {
        return;
    }


    $z['plan']['code']  = $code;
    $z['plan']['item']  = mktplan_blockcode_explode($z['plan']['code']);
    $z['plan']['items'] = mktplan_items($z['plan']['item'], 'itemid');


    if (!$skip_timecheck) {

        $term = $z['plan']['item']['DateEPG'].' '.$z['plan']['item']['BlockTermEPG'];

        if (strtotime($term) < time()) {

            omg_put('warning', $tx[SCTN]['MSG']['mktuptd_termcheck']);
            return;
        }
    }


    mktuptd_mktepg_reset($z);

    spcblock_termemit(['ID' => $z['sibling']['BlockID'], 'BlockType' => 3]);
}



/**
 * MKT: Uptodate
 *
 * @param array $item_src Mktplan item (source, i.e. CUR) data
 * @param array $item_dst Mktplan item (destination, i.e. MDF) data
 *
 * @return void
 */
function mktuptd($item_src, $item_dst) {

    $item_id = $item_src['ID'];

    // When adding NEW mktplanitem, only ID will be set, DateEPG won't; and we want to skip updating src
    if (!isset($item_src['DateEPG'])) {
        $item_src = null;
    }


    // If nothing changed, return
    if (
        $item_src &&
        $item_dst['ItemID']      ==$item_src['ItemID'] &&
        $item_dst['DateEPG']     ==$item_src['DateEPG'] &&
        $item_dst['Position']    ==$item_src['Position'] &&
        $item_dst['BlockTermEPG']==$item_src['BlockTermEPG'] &&
        $item_dst['BlockPos']    ==$item_src['BlockPos'] &&
        $item_dst['BlockProgID'] ==$item_src['BlockProgID']
    ) {
        return;
    }


    mktuptd_blc_data($item_id, $item_src, $item_dst);


    // Reset source

    if ($item_src) {

        $code_src  = mktplan_blockcode_create($item_src);

        mktuptd_mktepg_reset_wrapper($code_src);
    }


    // Reset destination

    $code_dsc  = mktplan_blockcode_create($item_dst);

    // If the item didn't move to another block, then dsc==src and we can avoid double reset of the same block
    if ($item_src && $code_src==$code_dsc) {
        return;
    }

    mktuptd_mktepg_reset_wrapper($code_dsc);
}



/**
 * MKT: Uptodate from mktepg spc block to mktplan (init not auto, but only via lower equaling button)
 *
 * @param int $code Mktplan blockcode
 * @return void
 */
function mktuptd_reverse($code) {

    $z['plan']['code']  = $code;
    $z['plan']['item']  = mktplan_blockcode_explode($z['plan']['code']);
    $z['plan']['items'] = mktplan_items($z['plan']['item'], 'itemid');
    $z['sibling']       = mktuptd_sibling_get($z['plan']['code']);

    $z['sibling']['items'] = rdr_cln('epg_cn_blocks', 'NativeID',
        'BlockID='.$z['sibling']['BlockID'].' AND NativeType=3 AND IsActive=1', 'Queue');

    foreach ($z['sibling']['items'] as $k => $v) { // Skip items which exist on both sides

        $key = array_search($v, $z['plan']['items']);

        if ($key) {
            unset($z['sibling']['items'][$k]);
            unset($z['plan']['items'][$key]);
        }
    }

    foreach ($z['plan']['items'] as $k => $v) { // Delete mktplan items

        qry('DELETE FROM epg_market_plan WHERE ID='.$k);
    }

    foreach ($z['sibling']['items'] as $k => $v) { // Add mktplan items

        $mdf['ItemID'] = $v;
        $mdf['ChannelID'] = $z['plan']['item']['ChannelID'];
        $mdf['DateEPG'] = $z['plan']['item']['DateEPG'];
        $mdf['Position'] = 2; // 2 = zero
        $mdf['BlockTermEPG'] = $z['plan']['item']['BlockTermEPG'];
        $mdf['BlockPos'] = $z['plan']['item']['BlockPos'];
        $mdf['BlockProgID'] = $z['plan']['item']['BlockProgID'];

        receiver_ins('epg_market_plan', $mdf);
    }
}



/**
 * MKT: Update BLC data (BLC_Wrapclips, BLC_Label) if necessary - move data to first item in block
 *
 * @param int $item_id Mktplan item ID
 * @param array $item_src Mktplan item (source, i.e. CUR) data
 * @param array $item_dst Mktplan item (destination, i.e. MDF) data
 *
 * @return void
 */
function mktuptd_blc_data($item_id, $item_src, $item_dst) {

    if ($item_src) {
        $src_block_items = mktplan_items($item_src, 'blcdata_table');
    }

    if ($item_dst) {

        $dst_block_items = mktplan_items($item_dst, 'blcdata_table');

    } else { // deleter

        $dst_block_items[$item_id]['BLC_Wrapclips'] = $item_src['BLC_Wrapclips'];
        $dst_block_items[$item_id]['BLC_Label'] = $item_src['BLC_Label'];
    }

    $item_was_first = ($item_src && ($dst_block_items[$item_id]['BLC_Wrapclips'] || $dst_block_items[$item_id]['BLC_Label']));
    // If it has BLC data, that means it was first item in block

    $item_to_first = ($item_dst && $item_id==key($dst_block_items));

    /*
    echo '<pre>'; echo 'SRC:'; print_r($item_src); echo 'DST:'; print_r($item_dst);
    print_r($src_block_items); print_r($dst_block_items);
    var_dump($item_was_first); var_dump($item_to_first);
    exit;
    */

    if ($item_was_first && $item_to_first && @$src_block_items==$dst_block_items) {
        // e.g. when changing POSITION column of an item
        return;
    }

    // SRC: Was first: move BLC data to new first item
    if ($item_was_first) {

        if ($src_block_items) { // If there are any other elements left at all

            mktuptd_blc_data_mover(key($src_block_items), $item_id, $dst_block_items[$item_id]);
            // Copy BLC data to the *new* first item in src block and clear BLC data from the migrating item
            // (i.e. the previous *first* item)
        }
    }

    // Was first and now moves to another block: must remove BLC data from it
    if ($item_was_first && @$src_block_items!=$dst_block_items) {

        // Clear BLC data of the previous block
        qry('UPDATE epg_market_plan SET '.receiver_sql4update(['BLC_Wrapclips' => null, 'BLC_Label' => null]).
            ' WHERE ID='.$item_id);
    }

    // DST: To first: move BLC data from previous first item
    if ($item_to_first && count($dst_block_items)>1) {

        $item_second = array_slice($dst_block_items, 1, 1, true); // *previous* first item is now second item
        $item_second_id = key($item_second);
        $item_second = $item_second[$item_second_id];

        if ($item_second['BLC_Wrapclips'] || $item_second['BLC_Label']) {

            mktuptd_blc_data_mover($item_id, $item_second_id, $item_second);
            // Copy BLC data to the *new* first item in dst block (i.e. the migrating item) and clear BLC data from
            // the previous *first* item
        }
    }
}


/**
 * MKT: Move BLC data (BLC_Wrapclips, BLC_Label) from the previous carrier to the new carrier (first item in the block)
 *
 * @param int $carrier_new Mktplan item ID of the new carrier of BLC data
 * @param int $carrier_prev Mktplan item ID of the previous carrier of BLC data
 * @param array $blc_data BLC data (taken from the previous carrier)
 *
 * @return void
 */
function mktuptd_blc_data_mover($carrier_new, $carrier_prev, $blc_data) {

    // Copy BLC data to the *new* carrier
    qry('UPDATE epg_market_plan SET '.receiver_sql4update($blc_data).' WHERE ID='.$carrier_new);

    // Clear BLC data from the previous carrier
    qry('UPDATE epg_market_plan SET '.receiver_sql4update(['BLC_Wrapclips' => null, 'BLC_Label' => null]).
        ' WHERE ID='.$carrier_prev);
}

