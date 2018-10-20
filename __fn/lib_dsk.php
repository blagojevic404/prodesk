<?php

// desk






/**
 * Story reader
 *
 * @param int $id ID
 * @param string $pgtyp Page type: (details, mdf_dsc, mdf_atom, mdf_task, pms). We don't want to fetch more data than we have to.
 * @return array $x
 */
function stry_reader($id, $pgtyp='') {

    global $cfg;

    $tbl = 'stryz';

    if ($id) {
        $x = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$id);
    }

    $x['ID']  = intval(@$x['ID']);
    $x['TBL'] = $tbl;
    $x['TYP'] = 'stry';
    $x['EPG_SCT_ID'] = 2;
    $x['PG_TYP'] = $pgtyp;

    if ($pgtyp=='pms') {
        return $x;
    }


    if (!$x['ID']) { // set default values and return

        $x['DurForcTXT'] = $x['DurCalcTXT'] = $x['MOS']['tc-in'] = $x['MOS']['tc-out'] = t2boxz('', 'time');

        $x['ChannelID'] = (!empty($_GET['scnid'])) ? chnlid_from_scnrid(intval($_GET['scnid'])) : CHNL;

        return $x;
    }


    if ($pgtyp=='mdf_task') {
        return $x;
    }


    $x['DurCalc'] = epg_durcalc('story', $x['ID']);     // calculate summary duration of atoms

    $x['DurForcTXT'] = t2boxz(@$x['DurForc'], 'time');

    $x['DurCalcTXT'] = t2boxz(@$x['DurCalc'], 'time');

    $x['DurCalcCSS'] = dur_handler($x['DurForc'], $x['DurCalc'], 'css');


    if ($x['PG_TYP']!='mdf_dsc') {
        $x['ATOMZ'] = atomz_reader($x['ID']);
    }

    if ($x['PG_TYP']!='mdf_atom') {

        $x['CRW'] = crw_reader($x['ID'], $x['EPG_SCT_ID']);

        if ($cfg['dsk']['dsk_use_notes']) {
            $x['Note'] = note_reader($x['ID'], $x['EPG_SCT_ID']);
        }
    }

    if ($x['PG_TYP']=='details') {
        $x['FlwID'] = flw_checker($x['ID'], $x['EPG_SCT_ID']);
    }

    return $x;
}

















/**
 * Fetch default data for atom types of the specified story type
 *
 * @param int $strytyp_id Story type ID (from CFG-ARR: dsk_story_type_formula)
 * @return array $r Array with default data for atoms types of the specified story type
 */
function stry_defaults($strytyp_id) {

    if ($strytyp_id) {

        $formula = cfg_local('arrz', 'dsk_story_type_formula', $strytyp_id);
        // Formula - what atoms to use as default for specific story type
        // '-' is used as a sign to connect atom types

    } else {

        $formula = '1-2-3'; // We need this for *clone* because clone needs atoms of all types
    }

	$atomz = explode('-', trim($formula));
	
	foreach ($atomz as $k => $v) {

        $r[$k]['TypeX'] = $v;

        /* ATOMBACK (discontinued)
        // '+' sign is used to mark that the atom should have DSC turned on
		$r[$k]['DSC'] = ['IsActive' => ((bool)(strstr($v, '+')) ? 1 : 0), 'Dsc' => ''];

		$r[$k]['TypeX'] = trim($v, '+'); // purge '+' sign
        */

        if ($v==1 && $k==1) { // This is to pre-set *second* atom of *reading* type to speaker #2.
            $r[$k]['SpeakerX'] = 2;
        }
	}
	
	return $r;
}







/**
 * Story atom(s) output
 *
 * @param string $typ Case type: (dtl, mdf, mdf_clone)
 * @param array $atomz Array with atoms data (2-dim array)
 * @return void
 */
function atom_output($typ, $atomz) {

    global $tx, $bs_css, $cfg;

    if (!$atomz) {
        return;
    }

	$arr_atom_typz = txarr('arrays', 'atom_jargons.'.ATOM_JARGON);

    $arr_atom_live_techtypz = txarr('arrays', 'dsk_atom_live_techtyp_short');

	$cnt = 0;


    // Wrap div
    $h_wrap =
        '<div class="row">'.
        '<div name="atom" class="panel '.(($typ!='dtl') ? 'atom_mdf col-xs-12' : 'atom_dtl typ%2$d '.$bs_css['panel_w']).'"'.
        (($typ=='mdf_clone') ? ' style="display: none;" id="clone[%1$d]"' : '').'>'.
        '<div class="row">'.PHP_EOL;

    if ($typ!='dtl') {

        // Shadows
        $h_shd =
            '<input type="hidden" name="id[%1$d][]"  id="id[%1$d][]" value="'.
            (($typ!='mdf_clone') ? '%3$d' : '').'">'.
            '<input type="hidden" name="cnt[%1$d][]" id="cnt[%1$d][]" value="%2$d">'.
            '<input type="hidden" name="del[%1$d][]" id="cnt[%1$d][]" value="">'.PHP_EOL;
    }


    // Head field

    $h_head =
        '<div class="col-sm-9 atom_header">'.
            '<span class="label label-default">'.(($typ!='mdf_clone') ? '%2$d' : '').'</span>'.
            '<span class="title">%4$s</span>'.
        '</div>';

    $h_head .= '<div class="col-sm-3 atom_header">';

    if ($typ=='dtl') {
        $h_head .= '<span class="label dur pull-right" data-toggle="tooltip" data-placement="left" title="%7$s">'.
            '<span class="opcty3 glyphicon glyphicon-time"></span>%6$s</span>';
    } else {
        $h_head .= '<a id="sleep" class="text-muted opcty3 pull-right" href="#" '.
            'onClick="atom_switch(this); return false;"><span class="glyphicon glyphicon-minus"></span></a>';
    }

    $h_head .= '</div>';


    // Texter field

    $h_txtr = '<div class="col-sm-9 atom_texter">';

    if ($typ=='dtl') {

        $h_txtr .= '<div class="atom_txt">%5$s</div>';  // TEXT DIV

    } else {

        $h_txtr .= '<textarea class="form-control no_vert_scroll" name="dsc[%1$d][]" id="dsc[%1$d][]" rows="7"'.
            expandTxtarea(6).'>%5$s</textarea>';
    }

    $h_txtr .= '</div>';


    // Glue
    $h = $h_wrap.(($typ!='dtl') ? $h_shd : '').$h_head.$h_txtr;

    /* ATOMBACK (discontinued)
    // Dsc field: Checkbox + Checkbox Shadow + Textarea
    $h_field_dsc =
        '<div class="field checkbox"><label><input type="checkbox" name="back_has[%1$d][]" id="back_has[%1$d][]" '.
            'value="1" %2$s tabindex=-1 onClick="atom_dsc_switch(this);">'.$tx[SCTN]['LBL']['bgcover'].'</label>'.
        '<input type="hidden" name="shd_back_has[%1$d][]" id="shd_back_has[%1$d][]" value="%4$d">'.
        '<textarea name="back_dsc[%1$d][]" id="back_dsc" class="form-control no_vert_scroll" style=" %3$s" rows="8" '.
            'maxlength="200"'.expandTxtarea(6).'>%5$s</textarea>'.
        '</div>';
        // We have to use hidden input field as "shadow" for back_has checkbox, otherwise it would *miss* unchecked
        // instances and also array members..
        // That's because this checkbox doesn't have normal name, but an array name
    */


    foreach ($atomz as $v) {

        $cnt++;

        if ($typ=='mdf') {  // This is TR for dnd-table. We skip this for (mdf)clones, as they are not in dnd-table..
            echo PHP_EOL.PHP_EOL.'<tr id="'.$cnt.'"><td class="'.$bs_css['panel_w'].'">';
        }

        if ($typ=='dtl') {
            echo '<div name="wraper">';         // Put WRAPER div around everything, for ifrm JS
        }

        if ($typ=='dtl') {

            if (@$v['Texter']) {
                $v['Texter'] = nl2br($v['Texter']);
            }

            if ($v['TypeX']==1) {
                $speed = get_readspeed('stry', 'char_per_s', ['stry_id' => $v['StoryID'], 'speaker_x' => $v['SpeakerX']]);
                $tooltip_txt = 's='.atom_txtlen($v['Texter']).', v='.$speed;
            } else {
                $tooltip_txt = '';
            }

            if ($v['TypeX']==2 && DUR_EDITABLE) {
                $dur_html =
                    '<span class="hmsedit" onclick="hms_editable(this, \'dur\', 1)" data-hms="'.$v['DurationTXT']['mmss'].'" '.
                    'data-id="'.$v['ID'].'">'.$v['DurationTXT']['mmss'].'</span>';
            } else {
                $dur_html = $v['DurationTXT']['mmss'];
            }
        }


        $cpt = $arr_atom_typz[$v['TypeX']];

        if ($typ=='dtl') {

            if ($v['TypeX']==3) {
                $cpt .= '/'.$arr_atom_live_techtypz[intval($v['TechType'])];
            }

            printf($h, $v['TypeX'], $cnt, @$v['ID'], $cpt, @$v['Texter'], $dur_html, $tooltip_txt);

        } else {

            printf($h, $v['TypeX'], $cnt, @$v['ID'], $cpt, @$v['Texter']);
        }



        /* DSC FIELD start */

        $dsc = [];

        switch ($v['TypeX']) {

            case 1: // read

                $txt = mb_strtoupper($tx['LBL']['speaker']);

                if ($typ=='dtl') {

                    $dsc[] = speaker_box([$v['SpeakerX']], 'speaker');

                } else { // mdf

                    $dsc[] = '<select name="SpeakerX[1][]" id="SpeakerX[1][]" class="form-control">';

                    for ($i=1; $i<=$cfg['speakerz_cnt_max']; $i++) {
                        $dsc[] = '<option value="'.$i.'"'.(($i==@$v['SpeakerX']) ? ' selected' : '').'>'.$txt.' '.$i.'</option>';
                    }

                    $dsc[] = '</select>';
                }

                break;

            case 2: // tape
            case 3: // live

                if ($v['TypeX']==2 && $cfg[SCTN]['use_mos_for_rec_atom']) { // tape only
                    mos_output((($typ!='dtl') ? 'mdf_atom' : 'dtl_atom'), $v);
                    break;
                }

                if ($typ!='dtl') {

                    $dsc[] = '<div class="field form-inline">'.
                        form_hms_output('desk-atom', 'DurForc%s['.$v['TypeX'].'][]', @$v['DurationTXT']).
                        '<span style="padding-left:10px">'.$tx['LBL']['duration'].'</span>'.
                        '</div>';
                }

                if ($v['TypeX']==3 && $typ!='dtl') {
                    $dsc[] = ctrl('form', 'select-txt', null, 'TechType[3][]', @$v['TechType'],
                        ['ctg_name'=>'dsk_atom_live_techtyp'], ['nowrap' => true, 'rtyp' => 'return']);
                }

                break;
        }

        $dsc = implode($dsc);

        if ($dsc) {

            if ($typ=='dtl') {

                $dsc = '<div class="field">'.$dsc.'</div>';

            } else {

                $speed = get_readspeed('user', 't_per_char', ['uid' => UZID]);

                $dsc .=
                    '<div class="ctrlz">'.
                        '<a href="#" class="text-muted" title="'.$tx['LBL']['duration'].'" '.
                            'onclick="js_atom_txtdur(this,\''.$speed.'\'); return false;">'.
                            '<span class="glyphicon glyphicon-time"></span>'.
                        '</a>'.
                        '<a href="#" class="text-muted" title="'.$tx['LBL']['alphconv'].'" '.
                            'onclick="alphconv(\'lat2cyr\',\'atom\',this); return false;">'.
                            '<span class="glyphicon glyphicon-retweet"></span>'.
                        '</a>'.
                    '</div>';
            }

            echo '<div class="col-sm-3 atom_dsc">'.$dsc.'</div>';
        }



        /* ATOMBACK (discontinued)
        if (in_array($v['TypeX'], [1,3])) {

            if ($typ!='dtl') {

                printf($h_field_dsc,
                    $v['TypeX'],
                    ((@$v['DSC']['IsActive']) ? 'checked' : ''),
                    ((@$v['DSC']['IsActive']) ? '' : 'display:none'),
                    ((@$v['DSC']['IsActive']) ? '1' : '0'),
                    @$v['DSC']['Dsc']);

            } else { // dtl

                detail_output(
                    [   'lbl' => $tx[SCTN]['LBL']['bgcover'],
                        'txt' => (($v['DSC']['IsActive']) ? nl2br($v['DSC']['Dsc']) : $tx['LBL']['noth']),
                        'layout' => 'vertical'  ]
                );
            }
        }
        */



        /* DSC FIELD finito */





        echo '</div></div></div>'; // close wraper


        if ($typ=='dtl') {
            if (CHNLTYP==1) {
                coverz_accordion_output($v['ID'], 3, 'cvr'.$cnt);
            } else {
                echo '<div style="height:20px;" class="hidden-print"></div>';
            }
        }


        if ($typ=='dtl') {
            echo '</div>'; // Closing ATOM div
        }

        if ($typ=='mdf') {
            echo '</td></tr>'; // Closing for TR in dnd-table
        }

    }

}










/**
 * Updates story phase
 *
 * @param array $x Story array
 * @param int $phz_new Phase
 * @return void
 */
function stry_phazer($x, $phz_new) {

    $log = [
        'x_id' => $x['ID'],
        'act_id' => (100 + $phz_new)      // TXT-ARR [log_actions] keys 100-110 are reserved for phases
    ];

    qry('UPDATE stryz SET Phase='.$phz_new.' WHERE ID='.$x['ID'], $log); // Update stry phase


    if (!$x['Phase']) { // Whoever modifies 0-Story (task), he becomes the owner, and we delete the assignee from CRW

        qry('UPDATE stryz SET UID='.UZID.', TermAdd="'.TIMENOW.'" WHERE ID='.$x['ID']);

        crw_deleter($x['ID'], 2, 5);
    }
}



