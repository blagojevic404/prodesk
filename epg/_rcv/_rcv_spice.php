<?php

pms('epg/'.$x['EPG_SCT'], 'mdf', $x, true);



$tbl[1] = $x['TBL'];


// All cases have caption
$mdf['Caption']	= wash('cpt', @$_POST['Caption']);


switch ($x['TYP']) {
	
	case 'item':

		$mdf['DurForc'] = rcv_datetime('hms_nozeroz',
            ['hh' => '', 'mm' => @$_POST['DurForcMM'], 'ss' => @$_POST['DurForcSS'], 'ff' => @$_POST['DurForcFF']]);

		if ($x['EPG_SCT']=='mkt') {

            $mdf['IsGratis'] = wash('bln', @$_POST['IsGratis']);
            $mdf['IsBumper'] = wash('bln', @$_POST['IsBumper']);
            $mdf['AgencyID'] = wash('int', @$_POST['AgencyID']);

            if ($cfg[SCTN]['use_mktitem_video_id']) {

                $mdf['VideoID'] = wash('int', @$_POST['VideoID']);

                if (!$mdf['VideoID']) {

                    $mdf['VideoID'] = null;

                } else {

                    if ($cfg[SCTN]['unique_mktitem_video_id']) {

                        // By default, UNIQUE video_id is off.
                        // If you decide to turn it on, then also add unique index for the column in DB:epg_market
                        // ALTER TABLE epg_market ADD UNIQUE INDEX `videoid` (`VideoID` ASC);

                        $videoid_existing = rdr_id('epg_market', 'ID<>'.$x['ID'].' AND VideoID='.$mdf['VideoID']);

                        if ($videoid_existing) {
                            $mdf['VideoID'] = null;
                            omg_put('danger');
                        }
                    }
                }
            }
        }
		
		if ($x['EPG_SCT']=='clp') {
            $mdf['Placing'] = wash('int', @$_POST['Placing']);
        }

        if ($x['EPG_SCT']!='clp') {
            $mdf['DateStart'] = wash('ymd', @$_POST['DateStart']);
            $mdf['DateExpire'] = wash('ymd', @$_POST['DateExpire']);
        }

        if (in_array($x['EPG_SCT'], ['prm', 'clp'])) {
            $mdf['CtgID'] = wash('int', @$_POST['CtgID']);
        }
				
		break;
		
	case 'block':

        if ($x['EPG_SCT']=='prm') {
            $mdf['CtgID'] = wash('int', @$_POST['CtgID']);
        }

        $mdf['DurForc'] = rcv_datetime('hms_nozeroz',
            ['hh' => '', 'mm' => @$_POST['DurForcMM'], 'ss' => @$_POST['DurForcSS']]);

        $mdf['BlockType'] = $x['EPG_SCT_ID'];

		break;
		
	case 'agent':
	
		break;
}


if (!@$x['ID']) { // new = insert

	$mdf['UID']	= UZID;
	$mdf['TermAdd']	= TIMENOW;
    $mdf['ChannelID'] = $x['ChannelID'];

	$x['ID'] = receiver_ins($tbl[1], $mdf);
	
	define('PAGE_TYP', 'NEW'); // Will need this to decide whether to do EMIT update (we do only for MDF)
	
} else { // modify = update

    receiver_upd($tbl[1], $mdf, $x);

	define('PAGE_TYP', 'MDF');
}



if ($x['TYP']=='block') {
	
	$mdf['DurEmit'] = block_receiver($x['ID']);
    // block_receiver() returns block duration, so we could use it later for EMIT update

    $log = ['tbl_name' => $x['TBL'], 'x_id' => $x['ID']];

	mos_receiver($x['ID'], $x['EPG_SCT_ID'], $x['MOS'], $log);
	
	crw_receiver($x['ID'], $x['EPG_SCT_ID'], @$x['CRW'], $log);
}






// EMIT update

if (PAGE_TYP=='MDF') {


    if ($x['TYP']=='block' ||
        ($x['EPG_SCT']=='clp' && $x['TYP']=='item') ) {

        // If FORC block duration differs from previous, or if FORC is not set but CALC block duration differs from previous
		if (
		    ($mdf['DurForc']!=$x['DurForc']) ||
            ($x['EPG_SCT']!='clp' && !$mdf['DurForc'] && $mdf['DurEmit']!=$x['DurEmit'])
        ) {

            spcblock_termemit($x);
		}
	}


    // Items have only DurForc, so we check if it has changed, and if so, we have to loop each block which contains
    // the item, and recalculate its duration
	if (($x['TYP']=='item' && $x['EPG_SCT']!='clp') && $mdf['DurForc']!=$x['DurForc']) {

		$blockz_arr = spcitem_usage($x, 'IDs');
		
		if ($blockz_arr) {
			
			foreach ($blockz_arr as $blockz_v) {
				
				$block_x = rdr_row('epg_blocks', 'ID, BlockType, DurForc, DurEmit', $blockz_v);

                // Recalculate DurEmit for the block, because item's duration might have changed
                $block_mdf['DurEmit'] = epg_durcalc('block', $block_x['ID']);

				// If DurEmit differs, update the DurEmit for the block
				if ($block_mdf['DurEmit']!=$block_x['DurEmit']) {

                    qry('UPDATE epg_blocks SET DurEmit=\''.$block_mdf['DurEmit'].'\' WHERE ID='.$block_x['ID']);

                    // If block has its DurForc defined, then DurEmit (calculated dur) is irrelevant, so we would skip
                    // updating EPGs and SCNRs which contain the block.
					if (!$block_x['DurForc']) {

                        spcblock_termemit($block_x);
					}
				}
			}
		}
	}

}





if (empty($_GET['rfrtyp'])) {

    $redirect_url = $_SERVER['SCRIPT_NAME'].'?sct='.$x['EPG_SCT'].'&typ='.$x['TYP'].'&id='.$x['ID'];

} else { // NEW/MDF BLOCK, opened from epg spicer

    $rfr_epg = intval($_GET['rfrepg']);

    if ($_GET['rfrtyp']=='epg') {

        $rfr_tbl = 'epg_elements';

        sch_termemit($rfr_epg, 'epg');

    } else { // scnr

        $rfr_tbl = 'epg_scnr_fragments';

        scnr_termemit(intval($_GET['rfrelm']));
    }

    qry('UPDATE '.$rfr_tbl.' SET NativeID='.$x['ID'].' WHERE ID='.intval($_GET['rfrid'])); // Associate with element/fragment

    $redirect_url = '/epg/epg.php?typ=epg&id='.$rfr_epg.'&view='.(($x['EPG_SCT']=='mkt') ? 8 : 9);

    $redirect_url .= (isset($_GET['rfrid'])) ? '#tr'.intval($_GET['rfrid']) : '';
}



hop($pathz['www_root'].$redirect_url);


