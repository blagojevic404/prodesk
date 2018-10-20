<?php


// mos




/**
 * "MOS" reader
 *
 * @param int $id Native ID
 * @param int $typid Native Type ID
 * @param array|string $clnz Columns to fetch
 * @return array $x MOS data
 */
function mos_reader($id, $typid, $clnz='') {

	if ($clnz) {
		$clnz_sql = (is_array($clnz)) ? implode(', ', $clnz) : $clnz;
	} else {
		$clnz_sql = 'ID, IsReady, Duration, TCin, TCout, Label, Path'; // fetch ALL info
	}

	$x = qry_assoc_row('SELECT '.$clnz_sql.' FROM cn_mos WHERE NativeType='.$typid.' AND NativeID='.$id);


    // For columns of TIME type, we also return *TXT, i.e. textual HMS value

	if (isset($x['TCin']))		$x['TCinTXT'] 		= t2boxz($x['TCin'], 'time');
	if (isset($x['TCout']))		$x['TCoutTXT'] 	    = t2boxz($x['TCout'], 'time');
	if (isset($x['Duration']))	$x['DurationTXT']   = t2boxz($x['Duration'], 'time');
	
	return $x;
}



/**
 * "MOS" deleter
 *
 * @param int $id Native ID
 * @param int $typid Native Type ID
 * @return void
 */
function mos_deleter($id, $typid) {

	 qry('DELETE FROM cn_mos WHERE NativeType='.$typid.' AND NativeID='.$id);
}




/**
 * "MOS" output
 *
 * @param string $pg_typ (dtl, mdf, dtl_atom, mdf_atom)
 * @param array $x Array with values
 * @param bool $collapse Whether to collapse accordion (this is passed to form_accordion_output())
 * @return void
 */
function mos_output($pg_typ, $x, $collapse=true) {

    global $tx;
	$tx['MOS']	= txarr('labels','mos');


	$arr_status = lng2arr($tx['LBL']['opp_ready']);

	switch ($pg_typ) {


        case 'dtl':

            form_accordion_output('head', $tx['MOS']['record'], 'mos', ['type'=>'dtlform', 'collapse'=>$collapse]);

            detail_output([ 'lbl' => $tx['MOS']['prep'], 'txt' => $arr_status[intval(@$x['IsReady'])] ]);

            if (@$x['Duration'] && $x['Duration']!='00:00:00') {
                detail_output([ 'lbl' => $tx['MOS']['duration'], 'txt' => $x['Duration'] ]);
            }

            if (@$x['Label']) {
                detail_output([ 'lbl' => $tx['MOS']['label'], 'txt' => $x['Label'] ]);
            }

            if (@$x['Path']) {
                detail_output([ 'lbl' => $tx['MOS']['path'], 'txt' => $x['Path'] ]);
            }

            if (@$x['TCin'] || $x['TCout']) {
                detail_output(
                    [   'lbl' => $tx['MOS']['tc-in-out'],
                        'txt' => $x['TCinTXT']['hhmmss'].'&nbsp;/&nbsp;'.$x['TCoutTXT']['hhmmss']   ]
                );
            }

            form_accordion_output('foot');

            break;


		case 'mdf':

            // We wrap the mos box into fieldset so we could disable it via JS (if MatType is LIVE >>mosbox_switch()<<)
            echo '<fieldset id="mos_field">';

            form_accordion_output('head', $tx['MOS']['record'], 'mos', ['collapse'=>$collapse]);

            echo '<div class="alert alert-danger text-center" role="alert" id="mos_alert">'.
                sprintf($tx[SCTN]['MSG']['mos_disabled'],
                    $tx[SCTN]['LBL']['material_type'],
                    txarr('arrays', 'epg_material_types', 1, 'uppercase'),
                    mb_strtoupper($tx['MOS']['record'])
                ).'</div>';

            ctrl('form', 'hms', $tx['MOS']['duration'], 'mosdur', @$x['DurationTXT']);

            ctrl('form', 'textbox', $tx['MOS']['label'], 'mos_label', @$x['Label']);

            ctrl('form', 'textbox', $tx['MOS']['path'], 'mos_path', @$x['Path']);

            $html =
                '<span><span style="margin-right: 8px">'.$tx['MOS']['tc-in'].'</span>'.
                form_hms_output('normal', 'tc-in', @$x['TCinTXT']).
                '</span>'.
                '<span class="pull-right"><span style="margin-right: 8px">'.$tx['MOS']['tc-out'].'</span>'.
                form_hms_output('normal', 'tc-out', @$x['TCoutTXT']).
                '</span>';
            ctrl('form', 'block', $tx['MOS']['tc-in-out'], 'tc-in-hh', $html, 'form-inline');

            ctrl('form', 'radio', $tx['MOS']['prep'], 'mos_prep', @$x['IsReady'], $arr_status);

            form_accordion_output('foot');

            echo '</fieldset>';

            break;


        case 'dtl_atom':

            /*
            detail_output(
                [   'lbl' => '',
                    'txt' => $arr_status[intval(@$x['MOS']['IsReady'])],
                    'css' => ((intval(@$x['MOS']['IsReady'])) ? 'is_ready text-uppercase' : 'text-uppercase'),
                    'layout' => 'vertical'  ]
            );*/

            echo '<div class="ready_wrap">'.
                isready_output($x['MOS']['IsReady'], PMS_ATOM_READY, '/desk/_ajax/_aj_atom.php', 'stry_id='.$x['StoryID'].'&id='.$x['ID']).
                '</div>';

            detail_output([ 'lbl' => $tx['MOS']['label'], 'txt' => $x['MOS']['Label'], 'layout' => 'vertical' ]);

            detail_output([ 'lbl' => $tx['MOS']['path'], 'txt' => $x['MOS']['Path'], 'layout' => 'vertical' ]);

            break;


		case 'mdf_atom':

            echo '<div class="field">';

            // IsReady checkbox
            $name = 'mos_prep[2][]';
            echo '<div class="field checkbox text-uppercase">'.
                '<label class="is_ready"><input type="checkbox" name="'.$name.'" id="'.$name.'" '.((@$x['MOS']['IsReady']) ? 'checked':'').
                ' value="1" tabindex=-1 onClick="setHider(this.parentNode.parentNode, \'shd\', ((this.checked)?1:0));">'.
                $arr_status[1].'</label>'.
                '<input type="hidden" name="shd_'.$name.'" id="shd_'.$name.'" value="'.((@$x['MOS']['IsReady'])?1:0).'">'.
                '</div>';

            // DUR mmss
            echo '<div class="form-group">'.
                '<label for="mosdurMM[2][]">'.$tx['MOS']['duration'].'</label>'.
                '<div class="form-inline">'.
                form_hms_output('desk-atom', 'mosdur%s[2][]', @$x['MOS']['DurationTXT']).
                '</div></div>';

            // LABEL txtbox
            ctrl('form', 'textbox', $tx['MOS']['label'], 'mos_label[2][]', @$x['MOS']['Label'], null, ['vertical' => true]);

            // PATH txtbox
            ctrl('form', 'textbox', $tx['MOS']['path'], 'mos_path[2][]', @$x['MOS']['Path'], null, ['vertical' => true]);

            echo '</div>';

            break;
	}
}







/**
 * "MOS" receiver
 *
 * @param int $id Native ID
 * @param int $typid Native Type ID
 * @param array $cur Array with current values
 * @param array $log Log data array
 * @param array $mdf Array with new/modify values (If omitted, POST array will be used)
 *
 * @return void
 */
function mos_receiver($id, $typid, $cur, $log, $mdf=null) {

	$tbl = 'cn_mos';

    $log = ['tbl_name' => $log['tbl_name'], 'x_id' => $log['x_id'], 'act_id' => 61, 'act' => 'mos'];


    if (!$mdf) { // Use POST
		
		$mdf['IsReady']	= wash('int', @$_POST['mos_prep']);
		$mdf['Label']	= wash('cpt', @$_POST['mos_label']);
		$mdf['Path']	= wash('cpt', @$_POST['mos_path']);

        $mdf['Duration'] = rcv_datetime('hms_nozeroz',
            ['hh' => @$_POST['mosdur-hh'], 'mm' => @$_POST['mosdur-mm'], 'ss' => @$_POST['mosdur-ss']]);

        $mdf['TCin'] = rcv_datetime('hms_nozeroz',
            ['hh' => @$_POST['tc-in-hh'], 'mm' => @$_POST['tc-in-mm'], 'ss' => @$_POST['tc-in-ss']]);

        $mdf['TCout'] = rcv_datetime('hms_nozeroz',
            ['hh' => @$_POST['tc-out-hh'], 'mm' => @$_POST['tc-out-mm'], 'ss' => @$_POST['tc-out-ss']]);
    }

	if (!@$cur['ID']) { // new = insert
	
		receiver_ins($tbl, array_merge($mdf, ['NativeID' => $id, 'NativeType' => $typid]), $log);

	} else { // modify = update

		receiver_upd($tbl, $mdf, $cur, $log);
	}
}


