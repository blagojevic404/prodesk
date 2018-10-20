<?php

if (!defined('STRYNEW_FROM_TASK')) {

    pms('dsk/stry', 'mdf', $x, true);

    $q_arr = array_flip(explode(' ', trim($_POST['qu'])));
}

$tbl[1] = 'stry_atoms';
$tbl[2] = 'stry_atoms_text';
$tbl[3] = 'stry_atoms_speaker';
$tbl[4] = 'epg_coverz';
//$tbl[7] = 'stry_atoms_dsc';   // ATOMBACK (discontinued)



$post = [];

if (defined('STRYNEW_FROM_TASK')) { // TASK: create atoms for the selected storytype

    $stry_typ = wash('int', @$_POST['StoryType']);

    $post = stry_defaults($stry_typ);

    foreach ($post as $k => $v) {

        $post[$k]['Queue'] = $k;

        $post[$k]['ID'] = 0;
        $post[$k]['Texter'] = '';
        $post[$k]['DEL'] = false;

        if ($v['TypeX']==1) { // CAM

            $post[$k]['SpeakerX'] = 1;
            $post[$k]['Duration'] = '00:00:00';
        }

        if ($v['TypeX']==3) { // LIVE

            $post[$k]['TechType'] = 0;
        }
    }

} else {

    foreach ($_POST['id'] as $k_typ => $v_typ) {        // ['id'][native_type_id][index] => id

        foreach ($v_typ as $k_id => $v_id) {

            $cnt = @$_POST['cnt'][$k_typ][$k_id];

            $post[$cnt]['ID'] = intval($v_id);
            $post[$cnt]['TypeX'] = intval($k_typ);
            $post[$cnt]['Queue'] = intval($q_arr[$cnt]);
            $post[$cnt]['DEL'] = wash('int', @$_POST['del'][$k_typ][$k_id]);
            $post[$cnt]['Texter'] = wash('txt', @$_POST['dsc'][$k_typ][$k_id]);


            if ($k_typ==1) {
                $post[$cnt]['SpeakerX'] = wash('int', @$_POST['SpeakerX'][$k_typ][$k_id]);
            }

            /* ATOMBACK (discontinued)
            if (in_array($k_typ, [1,3])) {
                $post[$cnt]['DSC']['IsActive']		= wash('int', @$_POST['shd_back_has'][$k_typ][$k_id]);
                $post[$cnt]['DSC']['Dsc'] 			= ($post[$cnt]['DSC']['IsActive']) ?
                                                        wash('txt', @$_POST['back_dsc'][$k_typ][$k_id]) : '';
            }
            */

            if ($k_typ==2 && $cfg[SCTN]['use_mos_for_rec_atom']) {
                $post[$cnt]['MOS']['IsReady'] = wash('int', @$_POST['shd_mos_prep'][$k_typ][$k_id]);
                $post[$cnt]['MOS']['Label'] = wash('cpt', @$_POST['mos_label'][$k_typ][$k_id]);
                $post[$cnt]['MOS']['Path'] = wash('cpt', @$_POST['mos_path'][$k_typ][$k_id]);

                $arr_hms = ['hh' => '',
                    'mm' => @$_POST['mosdurMM'][$k_typ][$k_id],
                    'ss' => @$_POST['mosdurSS'][$k_typ][$k_id]];

                $post[$cnt]['MOS']['Duration'] = rcv_datetime('hms_nozeroz', $arr_hms);
            }

            if ($k_typ==3) {
                $post[$cnt]['TechType'] = wash('int', @$_POST['TechType'][$k_typ][$k_id]);
            }

            switch ($k_typ) {

                case 1:
                    $speed = get_readspeed('stry', 't_per_char',
                        ['stry_id' => $x['ID'], 'speaker_x' => $post[$cnt]['SpeakerX']]);
                    $post[$cnt]['Duration'] = atom_txtdur($post[$cnt]['Texter'], $speed);
                    break;

                case 2:
                case 3:

                    if ($k_typ==2 && $cfg[SCTN]['use_mos_for_rec_atom']) {
                        $post[$cnt]['Duration'] = $post[$cnt]['MOS']['Duration'];
                        break;
                    }

                    $arr_hms = ['hh' => '',
                        'mm' => @$_POST['DurForcMM'][$k_typ][$k_id],
                        'ss' => @$_POST['DurForcSS'][$k_typ][$k_id]];
                    $post[$cnt]['Duration'] = rcv_datetime('hms_nozeroz', $arr_hms);
                    break;
            }

        }
    }
}

ksort($post);




/*
echo '<pre>';

//print_r($x['ATOMZ']);
print_r($post);
exit;
*/


foreach ($post as $atom_mdf) {


    if ($atom_mdf['ID']) {

        if (isset($x['ATOMZ'][$atom_mdf['ID']])) {

            $atom_cur = $x['ATOMZ'][$atom_mdf['ID']];

        } else {

            // This is to prevent an error which happens when the MDF form is submitted twice and one of the atoms was
            // deleted. (it used to be simple as this: $atom_cur = ($atom_mdf['ID']) ? $x['ATOMZ'][$atom_mdf['ID']] : '';)
            // Although JS:prevent_double_submit() minimize these cases, however sometimes the submit lags for server or
            // network reasons, and it happens that the nervous user clicks again on the submit button, thus effectively
            // repeating the submit once the requests get to the server etc..

            continue;
        }

    } else {

        $atom_cur = '';
    }

	
	if ($atom_mdf['DEL']) {
		
		if ($atom_mdf['ID']) {

            qry('DELETE FROM '.$tbl[1].' WHERE ID='.$atom_cur['ID']);
            qry('DELETE FROM '.$tbl[2].' WHERE ID='.$atom_cur['ID']);
            qry('DELETE FROM '.$tbl[3].' WHERE ID='.$atom_cur['ID']);
            qry('DELETE FROM '.$tbl[4].' WHERE OwnerType=3 AND OwnerID='.$atom_cur['ID']);
            //qry('DELETE FROM '.$tbl[7].' WHERE ID='.$atom_cur['ID']);    // ATOMBACK (discontinued)

            if ($cfg[SCTN]['use_mos_for_rec_atom']) {
                mos_deleter($atom_cur['ID'], $atom_cur['EPG_SCT_ID']);
            }
		}

		continue;
	}


	if (!@$atom_mdf['ID']) { // new = insert

		// atom
		$filter = array_flip(['Queue', 'TypeX', 'Duration', 'TechType']);
		$mdf = array_intersect_key($atom_mdf, $filter);
		
		$mdf['StoryID'] = $x['ID'];
		
		$atom_mdf['ID'] = receiver_ins($tbl[1], $mdf);

        // We have EMPTY $atom_cur (because this is NEW atom), but functions like crw_receiver(), which we use later,
        // rely on $atom_cur with 'TBL', 'EPG_SCT_ID' and 'ID' attributes. We provide it here.
		$atom_cur = ['TBL' => $tbl[1], 'EPG_SCT_ID' => 20, 'ID' => $atom_mdf['ID']];

        // texter
        receiver_ins($tbl[2], ['ID' => $atom_mdf['ID'], 'Texter' => $atom_mdf['Texter']], LOGSKIP);

        // speaker
        if ($atom_mdf['TypeX']==1) {
            receiver_ins($tbl[3], ['ID' => $atom_mdf['ID'], 'SpeakerX' => $atom_mdf['SpeakerX']], LOGSKIP);
        }

        /* ATOMBACK (discontinued)
        // dsc
		if (in_array($atom_mdf['TypeX'], [1,3]) && $atom_mdf['DSC']['IsActive']) {
			receiver_ins($tbl[7], array_merge($atom_mdf['DSC'], ['ID' => $atom_mdf['ID']]));
			// We add ID because we don't want receiver for DSC to auto increment. We have just got the atom ID from
            // receiver for ATOM.
		}
        */

	} else { // modify = update

		// atom
		$filter = array_flip(['ID', 'Queue', 'Duration', 'TechType']);
		$mdf = array_intersect_key($atom_mdf, $filter);
		$cur = array_intersect_key($atom_cur, $filter);
		receiver_upd($tbl[1], $mdf, $cur);

        // texter
        $mdf = ['Texter' => $atom_mdf['Texter']];
        $cur = ['Texter' => $atom_cur['Texter'], 'ID' => $atom_cur['ID']];
        receiver_upd($tbl[2], $mdf, $cur, LOGSKIP);

        // speaker
        if ($atom_mdf['TypeX']==1) {

            $mdf = ['SpeakerX' => $atom_mdf['SpeakerX']];
            $cur = ['SpeakerX' => $atom_cur['SpeakerX'], 'ID' => $atom_cur['ID']];
            receiver_upd($tbl[3], $mdf, $cur, LOGSKIP);
        }

        /* ATOMBACK (discontinued)
        // dsc
		if (in_array($atom_mdf['TypeX'], [1,3])) {
			
			$back_exists = (isset($atom_cur['DSC']['ID']) && $atom_cur['DSC']['ID']);

            // We do not delete dsc text when it is not used.
			
			if ( ($atom_mdf['DSC']['IsActive']) || ($back_exists) ) { // New value is positive OR some value already exists
			
				if ($back_exists) {
					receiver_upd($tbl[7], $atom_mdf['DSC'], $atom_cur['DSC']);
				} else {
					receiver_ins($tbl[7], array_merge($atom_mdf['DSC'], ['ID' => $atom_mdf['ID']]));
                    // We add ID because we don't want receiver for DSC to auto increment. Instead, we want it to use atom ID.
				}
			}
		}
        */

	}


	// mos
	if ($atom_mdf['TypeX']==2 && $cfg[SCTN]['use_mos_for_rec_atom']) {
        $log = ['tbl_name' => $atom_cur['TBL'], 'x_id' => $atom_cur['ID']];
		mos_receiver($atom_cur['ID'], $atom_cur['EPG_SCT_ID'], @$atom_cur['MOS'], $log, $atom_mdf['MOS']);
	}
}



if (!defined('STRYNEW_FROM_TASK')) {


    $log = ['tbl_name' => 'stryz', 'x_id' => $x['ID'], 'act_id' => 2, 'act' => 'atom'];
    qry_log(null, $log);

    stry_termemit($x['ID'], $x);


    // Phase lifting

    $i = 0;

    if (!$x['Phase'] && $x['UID']!=UZID) { // First MDF of 0-story (i.e. task)
        $i++;
    }

    if (isset($_POST['Submit_STRY_ATOM+PHZ']) && $x['Phase']<4) { // Submit with phase increment

        if ($x['UID']==UZID && $x['Phase']==1 && $x['ScnrID'] && pms_scnr($x['ScnrID'], 1)) {
            $i += 2; // Lift above editor phase, when author is the editor
        } else {
            $i += 1;
        }
    }

    if ($i) {
        stry_phazer($x, ($x['Phase']+$i));
    }

}



stry_versions_put($x['ID'], @$x['ATOMZ']);



if (empty($_GET['scnid'])) {

    $href = $pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?id='.$x['ID'];

} else {

    $href = $pathz['www_root'].'/epg/epg.php?typ=scnr&id='.scnr_id_to_elmid(intval($_GET['scnid']));
}

hop($href);

