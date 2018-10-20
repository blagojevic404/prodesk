<?php
/**
 * DRAGGABLE ITEMS for mktepg - changing item order
 */

require '../../../__ssn/ssn_boot.php';

$typ = (isset($_POST['typ'])) ? wash('arr_assoc', $_POST['typ'], ['mktepg']) : null;
if (!$typ) exit;


if ($typ=='mktepg') {


    if (isset($_POST['source_id']) && isset($_POST['target_id'])) {

        $id = intval($_POST['source_id']);
        $source = rdr_row('epg_market_plan', '*', $id);
        if (!$source['ID']) exit;

        $target_id = intval($_POST['target_id']);

        $sql = 'SELECT ID FROM epg_market_plan WHERE '.mktplan_items_where($source, 'queue').' ORDER BY '.$mktplanitem_order;
        $result = qry($sql);


        $arr_items = [];

        while ($line = mysqli_fetch_assoc($result)) {

            if (empty($not_first)) {

                if ($target_id==0) { // Target is 0, i.e. we are dropping source item to FIRST position

                    $arr_items[] = $source['ID'];

                    $blockdata_cur = rdr_row('epg_market_plan', 'BLC_Wrapclips, BLC_Label', $line['ID']);

                    // Copy BLC data from previous first item
                    mktuptd_blc_data_mover($source['ID'], $line['ID'], $blockdata_cur);

                } else { // Try to detect if this is the case of FIRST item being moved down

                    $blockdata_cur = rdr_row('epg_market_plan', 'Queue, BLC_Wrapclips, BLC_Label', $source['ID']);

                    if ($blockdata_cur['Queue']==0) {

                        unset($blockdata_cur['Queue']);

                        // Copy BLC data to new first item (previous second item)
                        mktuptd_blc_data_mover($line['ID'], $source['ID'], $blockdata_cur);
                    }
                }
            }

            $arr_items[] = $line['ID'];

            if ($target_id==$line['ID']) { // We omit *source* item by WHERE filter, and we add it here
                $arr_items[] = $source['ID'];
            }

            $not_first = true;
        }

        foreach ($arr_items as $k => $v) {
            qry('UPDATE epg_market_plan SET Queue='.$k.' WHERE ID='.$v);
        }


        $mktplan_code = mktplan_blockcode_create($source);

        mktuptd_mktepg_reset_wrapper($mktplan_code);
    }
}





echo '1';


