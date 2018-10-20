<?php


// crew






/**
 * CREW reader
 *
 * @param int $nat_id NativeID
 * @param int $nat_typ NativeType ID
 * @param int $crw_typ CrewType ID (see: TXT/ARR[epg_crew_types], CFG/ARR[epg_crew_lists])
 * @param string $rtyp Return type: (normal, normal_single, opt-data)
 *
 * @return array|int $a Crew data (2-dimensional array), or Single Crew UID (for normal_single $rtype)
 */
function crw_reader($nat_id, $nat_typ, $crw_typ=null, $rtyp='normal') {

    if ($rtyp!='opt-data') {

        if (!$crw_typ) {

            $sql = 'SELECT CrewType, CrewUID FROM cn_crw '.
                'WHERE NativeType='.$nat_typ.' AND NativeID='.$nat_id.' ORDER BY CrewType, ID';
            $a = qry_assoc_arr($sql);

        } else {

            $sql = 'SELECT CrewUID FROM cn_crw '.
                'WHERE NativeType='.$nat_typ.' AND NativeID='.$nat_id.' AND CrewType='.$crw_typ.' ORDER BY ID';
            $a = qry_numer_arr($sql);
        }

        if ($rtyp=='normal_single') {
            $a = (isset($a[0])) ? $a[0] : null;
        }

    } else { // $rtyp='opt-data'

        if (!$crw_typ) {

            $sql = 'SELECT CrewType, CrewUID, ID, OptData FROM cn_crw '.
                'WHERE NativeType='.$nat_typ.' AND NativeID='.$nat_id.' ORDER BY CrewType, ID';
            $a = qry_assoc_arr($sql);

        } else {

            $sql = 'SELECT CrewUID, ID, OptData FROM cn_crw '.
                'WHERE NativeType='.$nat_typ.' AND NativeID='.$nat_id.' AND CrewType='.$crw_typ.' ORDER BY ID';
            $a = qry_assoc_arr($sql);
        }
    }

	return $a;
}



/**
 * CREW reader for speakers. Get UID and RS for the specified Story ID and Speaker No.
 *
 * @param int $stry_id Story ID
 * @param int $speakerx Speaker No.
 *
 * @return array $r UID and RS for speaker
 */
function crw_spkr_reader($stry_id, $speakerx) {

    $scnrid = rdr_cell('stryz', 'ScnrID', $stry_id);

    if (!$scnrid) {
        return null;
    }

    $sql = 'SELECT CrewUID AS uid, OptData AS rs FROM cn_crw WHERE CrewType=2 AND NativeType=1'.
        ' AND NativeID='.$scnrid.' ORDER BY ID LIMIT '.($speakerx-1).',1';
    // ORDER has to be identical to order in crw_reader(), because it determines the order of speakers in epg-scn

    $r = qry_assoc_row($sql);

    return $r;
}



/**
 * CREW deleter
 *
 * @param int $natid Native ID
 * @param int $nattyp Native Type
 * @param int $crwtyp Crew Type
 * @return void
 */
function crw_deleter($natid, $nattyp, $crwtyp=null) {

	 qry('DELETE FROM cn_crw WHERE NativeType='.$nattyp.' AND NativeID='.$natid.(($crwtyp) ? ' AND CrewType='.$crwtyp : ''));
}





/**
 * CREW output
 *
 * @param string $pg_typ Page type: (mdf, dtl, dtl_print)
 * @param array $x Crew data
 * @param array $crw_typz Crew types (see: TXT/ARR[epg_crew_types], CFG/ARR[epg_crew_lists])
 * @param array $opt Options
 * - collapse (bool) - Whether to collapse accordion (this is passed to form_accordion_output())
 * - name (string) - ID/Name
 * - caption (string) - Caption text
 *
 * @return void
 */
function crw_output($pg_typ, $x, $crw_typz, $opt=null) {

	global $tx;

	$typz_lng = txarr('arrays', 'epg_crew_types');

    if (!isset($opt['collapse']))   $opt['collapse'] = true;
    if (!isset($opt['name']))       $opt['name'] = 'crw';
    if (!isset($opt['caption']))    $opt['caption'] = $tx['LBL']['crew'];


	if ($x) {

        // Reformat the array with CRW values
		foreach ($x as $v) {
            $x_tmp[$v['CrewType']][] = $v['CrewUID'];
        }

		$x = $x_tmp;
	}
	

	if ($pg_typ=='mdf') { // MDF


        form_accordion_output('head', $opt['caption'], $opt['name'], ['collapse'=>$opt['collapse']]);

        foreach ($crw_typz as $v) {

            $users = (@isset($x[$v])) ? $x[$v] : [0];

            $groups = crw_groups($v);

            foreach ($users as $uid) {
                ?>
                <table class="row col-lg-9 col-md-10 col-md-offset-1 col-sm-12 crw" id="<?=$opt['name']?>">
                    <tr>
                        <td class="col-md-1 col-sm-1">
                            <a href="#" class="text-muted" onclick="CRW_duplicate(this, '<?=$opt['name']?>'); return false;">
                                <span class="glyphicon glyphicon-circle-arrow-right"></span></a>
                        </td>
                        <td class="col-md-2 col-sm-3">
                            <input name="crw_typ[]" id="crw_typ[]" type="hidden" value="<?=$v?>">
                            <?=mb_strtoupper($typz_lng[$v])?>
                        </td>
                        <td class="col-md-5 col-sm-7">
                            <select name="crw_uid[]" id="crw_uid[]" class="form-control">
                                <?=users2mnu($groups, @$uid, ['zero-txt' => $tx['LBL']['undefined']])?></select>
                        </td>
                        <td class="col-md-1 col-sm-1 text-right">
                            <a class="text-muted" href="#" onClick="CRW_delete(this, '<?=$opt['name']?>'); return false;">
                                <span class="glyphicon glyphicon-remove"></span></a>
                        </td>
                    </tr>
                </table>
            <?php
            }

        }

        form_accordion_output('foot');


    } else { // DTL, DTL_PRINT


        $r = [];

        foreach ($crw_typz as $v) { // loop default crew types

            if (isset($x[$v])) {

                $users_fullnames = [];

                $users = $x[$v];

                foreach ($users as $uid) {
                    $users_fullnames[] = uid2name(@$uid);
                }

                $r[$v] = $users_fullnames;
            }
        }

        if ($r) {

            if ($pg_typ=='dtl') {

                form_accordion_output('head', $opt['caption'], $opt['name'], ['type'=>'dtlform', 'collapse'=>$opt['collapse']]);

                foreach ($r as $k => $v) {
                    detail_output(['lbl' => $typz_lng[$k], 'txt' => implode(', ', $v)]);
                }

                form_accordion_output('foot');

            } else { // dtl_print

                echo '<table class="crw_print">';

                foreach ($r as $k => $v) {
                    echo '<tr><td>'.$typz_lng[$k].'</td><td>'.implode(', ', $v).'</td></tr>';
                }

                echo '</table>';
            }
        }


	}
}









/*
/**
 * CREW output for ATOM
 *
 * @param string $pg_typ Page type: (dtl, mdf)
 * @param array $opt Options array
 * atom_id - for dtl type, Atom ID
 * sel_uid - for mdf type, Selected UID
 * crw_typ - for mdf type, Crew type (vidi: TXT/arrays.txt[epg_crew_types], CFG/cfgarr[epg_crew_lists])
 * name_sufix - for mdf type, Name sufix for controls
 * @return void
 *
function crw_output_atom($pg_typ, $opt) {

	global $tx;

	switch ($pg_typ) {
		

		case 'dtl':

            if ($opt['atom_id']) {
                $uid = rdr_cell('cn_crw', 'CrewUID', 'NativeType=20 AND NativeID='.$opt['atom_id']);
            } else {
                $uid = 0;
            }

            if ($uid) {
                $fullname = uid2name($uid);
            } else {
                $fullname = '('.$tx['LBL']['default'].')';
            }

            detail_output(['lbl' => $tx['LBL']['speaker'], 'txt' => $fullname, 'layout' => 'vertical']);

            break;
		
		case 'mdf':
		
			$groups = crw_groups($opt['crw_typ']);

            for ($i=1; $i<=$cfg['speakerz_cnt_max']; $i++) {
                $arr_static[] = ['-'.$i, mb_strtoupper($tx['LBL']['speaker']).' '.$i];
            }

			echo '<select name="crw_uid'.$opt['name_sufix'].'" id="crw_uid'.$opt['name_sufix'].'" class="form-control">'.
                users2mnu($groups, $opt['sel_uid'], ['arr_static' => $arr_static]).'</select>';

            // shadow for CRW TYPE
			echo '<input type="hidden" name="crw_typ'.$opt['name_sufix'].'" id="crw_typ'.$opt['name_sufix'].'" '.
                'value="'.$opt['crw_typ'].'">';

			break;
	}
}
*/







/**
 * Fetch group ID(s) (cfg/cfgarr[epg_crew_lists]) for specified crew type
 *
 * @param int $crew_typ Crew Type ID (for crew types, see: _txt/arrays.txt[epg_crew_types])
 * @return array $group_ids	Array with GIDs
 */
function crw_groups($crew_typ) {

    $group_ids = [];

    // For specified crew-type, fetch coma-separated group-names (e.g. "Tone,Radioton")
    $group_labels = explode(',', cfg_global('arrz', 'epg_crew_lists', $crew_typ));

    // For each group-name fetch coma-separated group-id(s) and then combine these group-ids into array
    foreach ($group_labels as $v) {
        $group_ids = array_merge($group_ids, explode(',', cfg_local('arrz', 'group_permissions', $v)));
    }

    return array_unique($group_ids);
}





/**
 * CREW receiver
 *
 * @param int $id Native ID
 * @param int $typid Native Type ID, or TableID (for data types which have no relation to EPG linetypes, e.g. teams, programs)
 * @param array $crw_cur Array with current CRW values
 * @param array $log Log data array
 * @param array $post MDF data (Used only when it is not possible to send MDF data through normal POST)
 *
 * @return void
 */
function crw_receiver($id, $typid, $crw_cur, $log, $post=null) {


	if (empty($post)) {
		
		// We build MDF array which will be in the same format as CUR array, so they can be easily compared
		
		foreach (@$_POST['crw_uid'] as $k => $v) {
			if (!$v) continue; // no zero values for UID
			$mdf[$k] = ['CrewType'=>intval($_POST['crw_typ'][$k]), 'CrewUID'=>intval($v)];
		}
		
	} else {
		
		// cleanup zero values
		foreach ($post as $k => $v) {
			if (!$v['CrewUID']) continue; // no zero values for UID
            $mdf[$k] = $post[$k];
		}
	}



	// First we delete all current rows which are not amongst new rows..

	if ($crw_cur) {

		foreach ($crw_cur as $cur) {
			
			$do_delete = 1;

			if (isset($mdf)) {
				foreach ($mdf as $new) {
					if ($new['CrewUID']==$cur['CrewUID'] && $new['CrewType']==$cur['CrewType']) {
						$do_delete = 0;
						break;
					}
				}
			}
			
			if ($do_delete) {
                qry('DELETE FROM cn_crw WHERE NativeType='.$typid.' AND NativeID='.$id.
                    ' AND CrewType='.$cur['CrewType'].' AND CrewUID='.$cur['CrewUID']);
                $do_log = true;
            }
		}
	}


	// Then we insert new rows which are not amongst current rows..

	if (isset($mdf)) {

		foreach ($mdf as $new) {
			
			$do_insert = 1;

			if ($crw_cur) {
				foreach ($crw_cur as $cur) {
					if ($new['CrewUID']==$cur['CrewUID'] && $new['CrewType']==$cur['CrewType']) {
						$do_insert = 0;
						break;
					}
				}
			}
			
			if ($do_insert) {

                qry('INSERT INTO cn_crw (NativeType, NativeID, CrewType, CrewUID) '.
                    'VALUES ('.$typid.','.$id.','.$new['CrewType'].','.$new['CrewUID'].')', LOGSKIP);

                $do_log = true;

                /* This is no more used, but I leave it because some similar case could appear later
                // Add flw mark for this task to user which is specified for the execution of the task
                if ($typid==6 && $new['CrewType']==5) { // task & journo
                    flw_put($id, $typid, 2, $new['CrewUID']); // [dsk_flw_types]
                }
                */

                // prog type & editor role
                if ($typid==1 && $new['CrewType']==1) {

                    // Add flw mark for this prog to user which is specified as the editor of the prog
                    flw_put($id, $typid, 4, $new['CrewUID']);

                    //flw_prog_circular($id, 4, $new['CrewUID']); // discontinued as unnecessary
                }

                // stry type & journo role.. + stry is in phase 0, i.e. it is a TASK
                if ($typid==2 && $new['CrewType']==5 && defined('STRY_PHASE') && !STRY_PHASE) {

                    // Delete flw marks previously added,
                    // (i.e. when we are changing the asignee, delete the flw mark for the previous fellow)
                    if (!defined('STRYNEW_FROM_TASK')) {
                        qry('DELETE FROM stry_followz WHERE ItemID='.$id.' AND ItemType='.$typid.' AND MarkType=2');
                    }

                    // FLW for the TASK-ASIGNEE.. Add flw mark for this stry-task to task-asignee..
                    flw_put($id, $typid, 2, $new['CrewUID']);
                }

            }
		}
	}


    // TERMEMIT update

    // Speaker change for prog means readspeed change for stories. If the speaker changes, we have to update termemit
    // for atoms/stories/prog/epg..

    if ($typid==1 && $crw_cur!=@$mdf) { // Proceed only if it is PROG type, and there ARE changes to CRW

        // Get CUR and MDF arrays for speakers, so we could determine whether there were any changes
        // to *speakers* specifically.

        $speaker = [];

        if ($crw_cur) {

            foreach ($crw_cur as $v) {
                if ($v['CrewType']==2) {
                    $speaker['cur'][] = $v['CrewUID'];
                }
            }
        }

        if (isset($mdf)) {

            foreach ($mdf as $v) {
                if ($v['CrewType']==2) {
                    $speaker['mdf'][] = $v['CrewUID'];
                }
            }
        }

        if (@$speaker['cur']!=@$speaker['mdf']) { // Proceed only if there ARE changes to SPEAKERS

            $spkrz = speakerx_list($id); // $id is SCNRID

            foreach ($spkrz as $v) {
                speakerX_termemit($id, $v);
            }
        }
    }


    if (!empty($do_log) && empty($log['log_all_skip'])) {
        $log = ['tbl_name' => $log['tbl_name'], 'x_id' => $log['x_id'], 'act_id' => 60, 'act' => 'crew'];
        qry_log(null, $log);
    }
}



