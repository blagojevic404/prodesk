<?php

pms('epg/mktplan', 'mdf', null, true);

$tbl = 'epg_market_plan';


$itemid = wash('int', $_POST['NativeID']);

if (!$itemid) { // delete
    require '../../__fn/fn_del.php';
}


if (isset($_POST['mpi'])) {

    foreach ($_POST['mpi'] as $k => $v) {

        if ($itemid) { // replace

            $cur = $mdf = rdr_row('epg_market_plan', '*', $k); // We need it for mktuptd()

            $mdf['ItemID'] = $itemid;

            receiver_upd($tbl, $mdf, $cur);

            mktuptd($cur, $mdf);

        } else { // delete

            deleter('mkt_plan', $k, ['skip_redirect' => true]);
        }
    }
}


hop($pathz['www_root'].'/epg/spice_details.php?sct='.$x['EPG_SCT'].'&typ='.$x['TYP'].'&id='.$x['ID']);
