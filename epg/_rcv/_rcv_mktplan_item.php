<?php

pms('epg/mktplan', 'mdf', null, true);


$tbl = 'epg_market_plan';



// This RCV can be called from several scripts:
// - mktplan_modify_single.php (new mktplanitem in the block; initiated from epg.php),
// - mktplan_modify_multi.php (mdf_multi all mktplanitems of the same mktitem; initiated from spice_details.php)
// - spice_details.php (modal: new/mdf),
// - epg.php (2 modals: mktplanitem mdf, mktplanitem mdf-all in the block)



if ($pathz['filename']=='mktplan_modify_multi') { // MDF_MULTI


    $planz = mktplan_item_arr($x['ID'], mktplan_item_list_start('mdf'));

    $post = [];

    if (isset($_POST['ID'])) {

        foreach ($_POST['ID'] as $k => $v) {

            $post[$k]['ID']          = wash('int', $v);
            $post[$k]['DateEPG']     = wash('ymd', $_POST['DateEPG'][$k]);
            $post[$k]['Position']    = wash('int', $_POST['Position'][$k]);

            $post[$k]['BlockPos']    = wash('int', $_POST['BlockPos'][$k]);
            $post[$k]['BlockProgID'] = wash('int', $_POST['BlockProgID'][$k]);
            $post[$k]['BlockTermEPG'] = rcv_datetime('hms_nozeroz', rcv_hms($_POST['BlockTermEPG'][$k], 'hhmm'));

            if (!$post[$k]['DateEPG']) {
                unset($post[$k]);
            }
        }
    }

    /*
    echo '<pre>';
    print_r($planz);
    //print_r($_POST);
    print_r($post);
    exit;
    */

    foreach ($post as $k => $mdf) {

        $mdf['ChannelID'] = $x['ChannelID'];
        $mdf['ItemID'] = $x['ID'];

        if (!$mdf['ID']) { // new = insert

            $cur = null;

            $mdf['Queue'] = qu_max($tbl, mktplan_items_where($mdf, 'queue'));

            $cur['ID'] = receiver_ins($tbl, $mdf);

        } else { // modify = update

            $cur = $planz[$mdf['ID']];

            unset($planz[$mdf['ID']]);

            receiver_upd($tbl, $mdf, $cur);
        }

        mktuptd($cur, $mdf);

        if (empty($cur['DateEPG'])) { // new
            mktplan_rcv_blc_label($mdf, wash('cpt', $_POST['BLC_Label_new'][$k]));
        }
    }

    if ($planz) { // Remained only those which were deleted from the form

        foreach ($planz as $k => $v) {
            qry('DELETE FROM '.$tbl.' WHERE ID='.$k);
        }
    }


} elseif (isset($_POST['MKTPLAN_MDFID']) && strlen($_POST['MKTPLAN_MDFID'])==17) { // epg.php: MDF-ALL items in a block
    // (mktplancode is 17 chars long.. beware: js:mktplan_modal_poster_onshow() also counts on it being 17 chars long)


    $mktplan_code = wash('int', $_POST['MKTPLAN_MDFID']);
    $mktplan_data  = mktplan_blockcode_explode($mktplan_code);
    $mktplan_itemz = mktplan_items($mktplan_data, 'rcv_mdfall');

    $mdf['ChannelID']   = $mktplan_data['ChannelID'];
    $mdf['DateEPG']     = wash('ymd', $_POST['DateEPG']);
    $mdf['BlockPos']    = wash('int', $_POST['BlockPos']);
    $mdf['BlockProgID'] = wash('int', $_POST['BlockProgID']);
    $mdf['BlockTermEPG'] = rcv_datetime('hms_nozeroz', rcv_hms($_POST['BlockTermEPG'], 'hhmm'));

    if (!$mktplan_itemz) { // error-catcher: this can happen only if the mktplan block stopped existing in the meantime
        hop($_SERVER['HTTP_REFERER']); // Reload the requestING page
    }


    // Check whether this is the case of merging one block onto another

    $mktplan_itemz_dst_raw = mktplan_items($mdf, 'blcdata_table');

    $mktplan_itemz_dst = array_keys($mktplan_itemz_dst_raw);

    $merging = ($mktplan_itemz_dst && $mktplan_itemz_dst!=$mktplan_itemz) ? true : false;


    foreach ($mktplan_itemz as $v) {

        $cur = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$v);

        $x = spice_reader($cur['ItemID'], 'item', 'mkt');

        $mdf['ItemID'] = $x['ID'];

        if (!$merging && empty($not_first)) {

            $not_first = true;
            $mdf['BLC_Label'] = wash('cpt', $_POST['BLC_Label']);
            $mdf['BLC_Wrapclips'] = wash('int', $_POST['BLC_Wrapclips']);

        } else {

            $mdf['BLC_Label'] = $mdf['BLC_Wrapclips'] = null; // Reset values for all items except first one
        }

        receiver_upd($tbl, $mdf, $cur);
    }


    if ($merging) { // Must take care of the BLC data

        $mktplan_itemz_merge = mktplan_items($mdf, 'blcdata_table');

        $first_prev = key($mktplan_itemz_dst_raw);
        $first_new = key($mktplan_itemz_merge);

        if ($first_prev!=$first_new) {

            $blcdata = $mktplan_itemz_dst_raw[$first_prev];

            if ($blcdata['BLC_Wrapclips'] || $blcdata['BLC_Label']) {

                mktuptd_blc_data_mover($first_new, $first_prev, $blcdata);
            }
        }
    }


    // If mktplan already has an associated mktepg, i.e. sibling, then update mktplan-code in sibling table

    $sibling_id = rdr_id('epg_market_siblings', 'MktplanCode='.$mktplan_code);

    if ($sibling_id) {

        $mktplan_code_new = mktplan_blockcode_create($mdf);

        if ($merging) {

            $sibling = rdr_row('epg_market_siblings', 'MktepgID, MktepgType', $sibling_id);

            qry('DELETE FROM epg_market_siblings WHERE ID='.$sibling_id);

            epg_deleter((($sibling['MktepgType']==1) ? 'epg' : 'scnr'), $sibling['MktepgID'], 'target');

            // Perhaps call to sch_termemit() would be appropriate, but I won't bother because it's not worth the effort..

        } else {

            if ($mdf['DateEPG']!=$cur['DateEPG']) { // the epg has changed

                qry('DELETE FROM epg_market_siblings WHERE ID='.$sibling_id);

            } elseif ($mktplan_code!=$mktplan_code_new) {

                qry('UPDATE epg_market_siblings SET MktplanCode='.$mktplan_code_new.' WHERE ID='.$sibling_id);
            }
        }

        mktuptd_mktepg_reset_wrapper($mktplan_code_new);
    }


} else { // MDF_SINGLE/modal


    switch ($pathz['filename']) {

        case 'spice_details': // can be NEW or MDF

            $mktplanitem_id = wash('int', $_POST['MKTPLAN_MDFID']);

            if ($mktplanitem_id) {
                $cur = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$mktplanitem_id);
            }

            // $x is already previously set

            break;

        case 'epg': // only MDF

            $mktplanitem_id = wash('int', $_POST['MKTPLAN_MDFID']);

            $cur = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$mktplanitem_id);

            $x = spice_reader($cur['ItemID'], 'item', 'mkt');

            break;

        case 'mktplan_modify_single': // NEW-IN-THE-BLOCK or MDF-ITEM

            if ($_POST['case']=='mktplan_block') { // NEW-IN-THE-BLOCK

                $cur = null;

            } else { // MDF-ITEM

                $mktplanitem_id = wash('int', $_POST['MKTPLAN_MDFID']);

                $cur = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$mktplanitem_id);
            }

            $mktitem_id = wash('int', $_POST['NativeID']);

            $x = spice_reader($mktitem_id, 'item', 'mkt');

            break;
    }

    $mdf['ChannelID'] = $x['ChannelID'];
    $mdf['ItemID'] = $x['ID'];

    $mdf['DateEPG']     = wash('ymd', $_POST['DateEPG']);
    $mdf['Position']    = wash('int', $_POST['Position']);

    $mdf['BlockPos']    = wash('int', $_POST['BlockPos']);
    $mdf['BlockProgID'] = wash('int', $_POST['BlockProgID']);
    $mdf['BlockTermEPG'] = rcv_datetime('hms_nozeroz', rcv_hms($_POST['BlockTermEPG'], 'hhmm'));

    if (empty($cur['ID'])) { // new = insert

        $mdf['Queue'] = qu_max($tbl, mktplan_items_where($mdf, 'queue'));

        $cur['ID'] = receiver_ins($tbl, $mdf);

    } else { // modify = update

        receiver_upd($tbl, $mdf, $cur);
    }

    mktuptd($cur, $mdf);

    if ($pathz['filename']=='spice_details' && empty($cur['DateEPG'])) { // new (only MODAL, not MDF_SINGLE)
        mktplan_rcv_blc_label($mdf, wash('cpt', $_POST['BLC_Label_new']));
    }

    if ($cfg[SCTN]['mktplan_use_notes']) {

        $mktplan_note_typ = 3; // We can use MKT NativeID as it is not otherwise used, i.e. MKT doesn't use notes.

        note_receiver($cur['ID'], $mktplan_note_typ, note_reader($cur['ID'], $mktplan_note_typ));
    }

}


// Redirecting

if ($pathz['filename']=='spice_details' || $pathz['filename']=='mktplan_modify_multi') {

    $redirect_url = '/epg/spice_details.php?sct='.$x['EPG_SCT'].'&typ='.$x['TYP'].'&id='.$x['ID'];

} else { // mktplan_modify_single.php, epg.php

    $redirect_url = '/epg/epg.php?typ=epg&view=8';

    $epgid = epgid_from_date($mdf['DateEPG'], $x['ChannelID']);

    if ($epgid) {
        $redirect_url .= '&id='.$epgid;
    } else {
        $redirect_url .= '&dtr_date='.$mdf['DateEPG'].'&dtr_chnl='.$x['ChannelID'];
    }

    $redirect_url .= '#tr'.mktplan_blockcode_create($mdf);
}

hop($pathz['www_root'].$redirect_url);
