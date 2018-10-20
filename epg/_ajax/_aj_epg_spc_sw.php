<?php
/**
 * EPG SPICER-VIEW AJAX
 * 1. (DE)ACTIVATING, i.e. switching active state of a spice block fragment on or off.
 *    Fired by button on the right side of each block fragment.
 * 2. DRAGGABLE ITEMS
 */


require '../../../__ssn/ssn_boot.php';




if (isset($_POST['switch'])) { // ITEM-SWITCH


    $id = intval($_POST['switch']);

    $cn = rdr_row('epg_cn_blocks', 'ID, BlockID', $id);
    if (!$cn['ID']) exit;


    $log = ['tbl_name' => 'epg_blocks', 'x_id' => $cn['BlockID']];

    qry('UPDATE epg_cn_blocks SET IsActive = IF(IsActive=1,0,1) WHERE ID='.$cn['ID'], $log); // Update ITEM



    $block = rdr_row('epg_blocks', 'ID, BlockType, DurEmit', $cn['BlockID']); // Get OLD block data

    $block_duremit = epg_durcalc('block', $block['ID']); // Get NEW block DurEmit

    if ($block['DurEmit']!=$block_duremit) { // If block DurEMit has changed

        qry('UPDATE epg_blocks SET DurEmit = \''.$block_duremit.'\' WHERE ID='.$block['ID']); // Update BLOCK data


        // Simplified termemit to update only parent epg/scnr. To avoid lagging. (It replaced spcblock_termemit())
        // Note: Later I decided to omit this altogether, as reloading the epg spicer script does the same..
        // (and we do reload the script on ajax success)
        // However if for some reason I decide to use it again, then some similar *termemit* procedure should be applied
        // also for ITEM-DRAG ajax below
        /*
        if ($_POST['rfrtyp']=='epg') {

            sch_termemit(intval($_POST['rfrid']));

        } else { // scnr

            scnr_termemit(intval($_POST['rfrid']));
        }
        */
        //spcblock_termemit($block); // Update epgz/scnrz
    }


} elseif (isset($_POST['itemid'])) { // ITEM-DRAG


    $id = intval($_POST['itemid']);

    $source = rdr_row('epg_cn_blocks', 'ID, BlockID, Queue', $id);
    if (!$source['ID']) exit;

    $target_typ = ($_POST['target_typ']=='bloc') ? 'block' : 'item';
    $target_id = intval($_POST['target_id']);
    if (!$target_id) exit;


    if ($target_typ=='block') {

        $target = ['ID' => null, 'BlockID' => $target_id];

        if ($target['BlockID']==$source['BlockID'] && $source['Queue']==0) exit; // No changes

    } else { // $target_typ=='item'

        $target = rdr_row('epg_cn_blocks', 'ID, BlockID, Queue', $target_id);
        if (!$target['ID']) exit;
    }

    if ($target['BlockID']!=$source['BlockID']) { // Block has changed

        $log = ['tbl_name' => 'epg_blocks', 'x_id' => $target['BlockID']]; // We log under TARGET block

        qry('UPDATE epg_cn_blocks SET BlockID='.$target['BlockID'].' WHERE ID='.$source['ID'], $log); // Move item

        block_queuer($target['BlockID'], $source['ID'], $target['ID']); // New block

        block_queuer($source['BlockID']); // Old block

    } else { // Same block, only order has changed

        block_queuer($target['BlockID'], $source['ID'], $target['ID']);
    }
}


echo '1';





/**
 * Update QUEUE for block items
 *
 * @param int $target_blockid Target BlockID
 * @param int $source_id Source (dragged) item epg_cn_blocks:ID
 * @param int $target_id Target (dropped-on) item epg_cn_blocks:ID. Source item is to be queued *right after* it.
 * @return void
 */
function block_queuer($target_blockid, $source_id=null, $target_id=null) {

    $itemz = [];

    if ($target_id===null && $source_id!==null) {

        // When TARGET item is null, that means source item was dropped on the *block* row,
        // i.e. it should be queued at the beggining of the block.

        $itemz[] = $source_id;
    }

    $result = qry('SELECT ID FROM epg_cn_blocks WHERE BlockID='.$target_blockid.' ORDER BY Queue ASC');

    while ($x = mysqli_fetch_assoc($result)) {

        if ($x['ID']==$source_id) {
            continue;
        }

        $itemz[] = $x['ID'];

        if ($x['ID']==$target_id) {
            $itemz[] = $source_id;
        }
    }

    foreach ($itemz as $q => $item_id) {
        qry('UPDATE epg_cn_blocks SET Queue='.$q.' WHERE ID='.$item_id);
    }
}

