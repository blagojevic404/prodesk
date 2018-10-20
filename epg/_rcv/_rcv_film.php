<?php


pms('epg/film', 'mdf', $x, true);



$tbl[1] = $x['TBL'];
$tbl[2] = 'film_description';
$tbl[3] = 'film_cn_genre';
$tbl[4] = 'film_cn_channel';
$tbl[5] = 'film_cn_bcasts';
$tbl[6] = 'film_cn_contracts';




	

switch ($x['TYP']) {


	case 'item':
	
		$mdf[1]['TypeID']				= wash('int', @$_POST['TypeID']);
		$mdf[1]['SectionID']			= wash('int', @$_POST['SectionID']);
		$mdf[1]['LicenceStart']			= wash('ymd', @$_POST['LicenceStart']);
		$mdf[1]['LicenceExpire']		= wash('ymd', @$_POST['LicenceExpire']);
		$mdf[1]['IsDelivered']			= wash('bln', @$_POST['IsDelivered']);
        $mdf[1]['ProdType']             = wash('int', @$_POST['ProdType']);

		if ($mdf[1]['TypeID']==1) { // movie

			$mdf[1]['DurReal'] = rcv_datetime('hms',
                ['hh' => @$_POST['DurRealHH'], 'mm' => @$_POST['DurRealMM'], 'ss' => @$_POST['DurRealSS']]);

			$mdf[1]['DurApprox'] = rcv_datetime('hms',
                ['hh' => @$_POST['DurApproxHH'], 'mm' => @$_POST['DurApproxMM'], 'ss' => @$_POST['DurApproxSS']]);

		} else { // serial

			$mdf[1]['DurDesc'] 			= wash('cpt', @$_POST['DurDesc']);
			$mdf[1]['EpisodeCount'] 	= wash('int', @$_POST['EpisodeCount']);
		}


		// film_description

		$mdf[2]['Title']				= wash('cpt', @$_POST['Title']);
		$mdf[2]['OriginalTitle']		= wash('cpt', @$_POST['OriginalTitle']);
		$mdf[2]['LanguageID']			= wash('int', @$_POST['LanguageID']);
		$mdf[2]['Country']				= wash('cpt', @$_POST['Country']);

		$mdf[2]['Year']					= wash('int', @$_POST['Year']);
        if (!$mdf[2]['Year']) {
            $mdf[2]['Year'] = '';   // to prevent displaying "0" for empty value
        }

		$mdf[2]['DscTitle']				= wash('cpt', @$_POST['DscTitle']);
		$mdf[2]['DscShort']				= wash('cpt', @$_POST['DscShort']);
		$mdf[2]['DscLong']				= wash('txt', @$_POST['DscLong']);
		$mdf[2]['Director']				= wash('cpt', @$_POST['Director']);
		$mdf[2]['Writer']				= wash('cpt', @$_POST['Writer']);
		$mdf[2]['Actors']				= wash('cpt', @$_POST['Actors']);
		
		if ($cfg['lbl_parental_filmbased']) {
			$mdf[2]['Parental']	= wash('int', @$_POST['Parental']);
            if (!$mdf[2]['Parental']) {
                $mdf[2]['Parental'] = null;
            }
        }

        if ($mdf[1]['TypeID']!=1) { // not movie
            $mdf[2]['Seasons_arr'] = wash('cpt', @$_POST['Seasons_arr']);
        }


		// film_cn_genre

		$mdf[3]	= [];
		if (is_array(@$_POST['Genres'])) {
			foreach ($_POST['Genres'] as $k => $v) {
				if ($mdf[1]['SectionID']!=2) {                      // not documentary
					if ($k<20) $mdf[3][] = wash('int', $k);
				} else {                                            // documentary (has its own list of genres)
					if ($k>=20) $mdf[3][] = wash('int', $k);
				}
			}
        }


		// film_cn_channel

		$mdf[4]	= [];
		if (is_array(@$_POST['Channels'])) {
			foreach ($_POST['Channels'] as $k => $v) {
				$mdf[4][] = wash('int', $k);
			}
        }


        // film_cn_bcasts

        if ($cfg[SCTN]['bcast_cnt_separate']) { // each channel has its own bcmax

            $mdf[5]	= [];
            if (is_array(@$_POST['BCmax'])) {
                foreach ($_POST['BCmax'] as $k => $v) {
                    $mdf[5][$k] = wash('int', $v);
                }
            }

        } else { // only one bcmax is defined and it is divided between multiple channels

            $mdf[5]['BCmax'] = wash('int', @$_POST['BCmax']);
        }


        // film_cn_contracts

        $mdf[6]['ContractID'] = wash('int', @$_POST['ContractID']);

		break;


	case 'contract':

		$mdf[1]['CodeLabel']        = wash('cpt', @$_POST['CodeLabel']);
		$mdf[1]['AgencyID']         = wash('int', @$_POST['AgencyID']);
		$mdf[1]['DateContract']     = wash('ymd', @$_POST['DateContract']);
		$mdf[1]['LicenceType']      = wash('cpt', @$_POST['LicenceType']);
		$mdf[1]['PriceSum']         = wash('int', @$_POST['PriceSum']);
		$mdf[1]['PriceCurrencyID']  = wash('int', @$_POST['PriceCurrencyID']);
        $mdf[1]['LicenceStart']			= wash('ymd', @$_POST['LicenceStart']);
        $mdf[1]['LicenceExpire']		= wash('ymd', @$_POST['LicenceExpire']);
        break;


	case 'agent':

		$mdf[1]['Caption']		        = wash('cpt', @$_POST['Caption']);
		break;
}



		
if (!@$x['ID']) { // new = insert

	$mdf[1]['UID']			= UZID;
	$mdf[1]['TermAdd']		= TIMENOW;
	$x['ID'] = receiver_ins($tbl[1], $mdf[1]);

	define('PAGE_TYP', 'NEW'); // we need this later, to decide whether to run EMIT update

} else { // modify = update

	receiver_upd($tbl[1], $mdf[1], $x);

	define('PAGE_TYP', 'MDF');
}



if ($x['TYP']=='item') {

    // contract
    if (!(PAGE_TYP=='MDF' && $mdf[6]['ContractID']==$x['Contract']['ID'])) { // skip if cur and mdf value are equal
        receiver_mdf($tbl[6], ['FilmID' => $x['ID']], $mdf[6], ['tbl_name' => $tbl[1], 'x_id' => $x['ID']]);
    }

    // description
    unset($x['DSC']['ID']);
    if (!(PAGE_TYP=='MDF' && $mdf[2]==$x['DSC'])) { // skip if cur and mdf value are equal
        receiver_mdf($tbl[2], ['ID' => $x['ID']], $mdf[2], ['tbl_name' => $tbl[1], 'x_id' => $x['ID']]);
    }

    // genres
	receiver_mdf_array($tbl[3], ['FilmID' => $x['ID']], 'GenreID', $mdf[3], $x['Genres']);

    // channels
	receiver_mdf_array($tbl[4], ['FilmID' => $x['ID']], 'ChannelID', $mdf[4], $x['Channels']);


    // bcasts

    if ($cfg[SCTN]['bcast_cnt_separate']) { // each channel has its own bcmax

        $channels = channelz(['typ' => 1]);

        foreach ($channels as $k => $v) {

            if ($mdf[5][$k]==$x['BCmax_arr'][$k]) {
                continue;
            }

            $arr_where = ['FilmID' => $x['ID'], 'ChannelID' => $k];

            qry('DELETE FROM '.$tbl[5].' WHERE '.receiver_sql4select($arr_where));

            if ($mdf[5][$k]) {
                receiver_ins($tbl[5], array_merge($arr_where, ['BCmax' => $mdf[5][$k], 'BCcur' => $x['BCcur_arr'][$k]]), LOGSKIP);

            }
        }

    } else { // only one bcmax is defined and it is divided between multiple channels

        receiver_mdf($tbl[5], ['FilmID' => $x['ID'], 'ChannelID' => NULL], $mdf[5], LOGSKIP);
    }



    // note

    $log = ['tbl_name' => $x['TBL'], 'x_id' => $x['ID']];

    note_receiver($x['ID'], $x['EPG_SCT_ID'], @$x['Note'], $log);





    // episodes
	if ($mdf[1]['TypeID']!=1 && $mdf[1]['EpisodeCount'] && $mdf[1]['EpisodeCount']!=@$x['EpisodeCount']) {   // serial
        film_serial_build($x['ID'], $mdf[1]['EpisodeCount']);
    }
}






// EMIT update

// Only if MDF, and only if FILM and MOVIE (as SERIALS have only descriptive duration)

if (PAGE_TYP=='MDF' && $x['TYP']=='item' && $x['TypeID']==1) {
	
	if (($mdf[1]['DurApprox']!=$x['DurApprox']) || ($mdf[1]['DurReal']!=$x['DurReal'])) { // only if DUR has changed

        film_termemit($x);
	}
}




hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?typ='.$x['TYP'].'&id='.$x['ID']);


