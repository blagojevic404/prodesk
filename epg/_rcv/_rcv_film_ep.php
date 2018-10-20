<?php


pms('epg/film', 'mdf', $x, true);



$tbl[1] = 'film_episodes';





$master['DurReal'] = rcv_datetime('hms_nozeroz',
    ['hh' => @$_POST['m-DurRealHH'], 'mm' => @$_POST['m-DurRealMM'], 'ss' => @$_POST['m-DurRealSS']]);

$master['DurApprox'] = rcv_datetime('hms_nozeroz',
    ['hh' => @$_POST['m-DurApproxHH'], 'mm' => @$_POST['m-DurApproxMM'], 'ss' => @$_POST['m-DurApproxSS']]);

// If set, master value will overwrite all row values


foreach ($_POST['Ordinal'] as $v) {
	
	$mdf['Ordinal']	= wash('int', $v);
	$mdf['Title']	= wash('cpt', @$_POST['Title'][$v]);

    if ($master['DurApprox']) {
        $mdf['DurApprox'] = $master['DurApprox'];
    } else {
        $mdf['DurApprox'] = rcv_datetime('hms_nozeroz',
            ['hh' => @$_POST['DurApproxHH'][$v], 'mm' => @$_POST['DurApproxMM'][$v], 'ss' => @$_POST['DurApproxSS'][$v]]);
    }

    if ($master['DurReal']) {
        $mdf['DurReal'] = $master['DurReal'];
    } else {
        $mdf['DurReal'] = rcv_datetime('hms_nozeroz',
            ['hh' => @$_POST['DurRealHH'][$v], 	'mm' => @$_POST['DurRealMM'][$v], 	'ss' => @$_POST['DurRealSS'][$v]]);
    }


	if (in_array($v, $x['Episodes'])) {
		$y = rdr_row('film_episodes', '*', 'ParentID='.$x['ID'].' AND Ordinal='.$v);
		$y['EPG_SCT_ID'] = 13;
	} else {
		$y = '';
	}

    // If the episode doesn't already exist, and also none of the new values for the episode is set, then no need to continue
	if (!@$y['ID'] && !$mdf['Title'] && $mdf['DurReal']===null && $mdf['DurApprox']===null) {
        continue;
    }

	
	if (!@$y['ID']) { // new = insert
	
		$mdf['ParentID'] = $x['ID'];
		
		$y['ID'] = receiver_ins($tbl[1], $mdf);
		
	} else { // modify = update
	
		receiver_upd($tbl[1], $mdf, $y);
		

		// EMIT update
		
		if (($mdf['DurApprox']!=$y['DurApprox']) || ($mdf['DurReal']!=$y['DurReal'])) { // only if DUR has changed

            film_termemit($y);
		}
		
		
	}
	
}


hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?typ='.$x['TYP'].'&id='.$x['ID']);
