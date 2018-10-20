<?php
/** Library of functions for output (simple print out) of EPG */






/**
 * Returns CSS classname and TYP (i.e. SECTION NAME) for specified linetype (NativeType column)
 *
 * @param int $typint NativeType
 * @param string $rtyp Return type: (arr, var). If VAR, returns only CSS variable.
 * @return array Numeric array with CSS and TYP information
 */
function epg_linetyp($typint, $rtyp='arr') {

    switch ($typint) {

        case 1:		$css = 				$typ = 'prg';			break;
        case 2:		$css = 'prg';		$typ = 'stry';			break;
        case 3:		$css = 				$typ = 'mkt';			break;
        case 4:		$css = 				$typ = 'prm'; 			break;
        case 5:		$css = 				$typ = 'clp'; 			break;
        case 6:		$css = 'prg';		$typ = 'task';			break;
        case 7:		$css = 				$typ = 'liveair'; 	    break;
        case 8:		$css = 				$typ = 'note'; 			break;
        case 9:		$css = 				$typ = 'hole'; 			break;
        case 10:	$css = 				$typ = 'segmento';		break;
        case 12:	$css = 				$typ = 'film';			break;
        case 13:	$css = 'film';	    $typ = 'episode';		break;
        case 14:	$css = 'prg';	    $typ = 'linker';		break;
        case 20:	$css = 'prg atom';	$typ = 'atom';			break;
    }

    if ($rtyp=='var') return $css;

    return [$css, $typ];
}





































/**
 * Prints MODIFY-MULTI table for EPG or SCNR
 *
 * @param int $id EPG ID or SCNR ID
 * @param string $typ Type: (epg, scnr)
 * @param int $scn_import IMPORT SCNR ID. Applicable only for scnr.
 *
 * @return void
 */
function epg_mdf_html($id, $typ='epg', $scn_import=0) {


    $src_id = ($scn_import) ? $scn_import : $id;

    $tbl = ($typ=='epg') ? 'epg_elements' : 'epg_scnr_fragments';
    $cln = ($typ=='epg') ? 'EpgID' : 'ScnrID';

    $sql = 'SELECT ID FROM '.$tbl.' WHERE '.$cln.'='.$src_id.
        //(($scn_import) ? '' : ' AND IsActive=1'). // Don't skip sleeplines when importing scn
        // Note: We later decided to show sleeplines *always*, that's why we turned off this line..
        ' ORDER BY Queue';

    $result = qry($sql);


    if ($scn_import) {

        // On SCN-IMPORT page there is a checkbox for each fragment, so you can decide which fragment to discard (uncheck)
        $arr_actv_fragz = (!empty($_POST['fragz'])) ? array_keys($_POST['fragz']) : [];

        $qu_prev = cnt_sql('epg_scnr_fragments', 'ScnrID='.$id);

    } else {

        $qu_prev = 0;
    }


    while (list($xid) = mysqli_fetch_row($result)) {

        if ($typ=='epg') {

            $x = element_reader($xid, 'mdf');

            if (EPG_NEW && $x['NativeType']==13) { // linetype 13 is *serial-film*

                // As a favor to the user, if this is a NEW EPG COPIED FROM TMPL OR FROM EXISTING EPG situation then we
                // automatically increment episodes on serial-films

                $x['FILM']['PREMIERES'] = (isset($arr_premieres)) ? $arr_premieres : [];

                $x['FILM'] = film_next_episode($x);

                $arr_premieres = $x['FILM']['PREMIERES'];
            }

        } else { // scn

            if ($scn_import && !in_array($xid, $arr_actv_fragz)) {
                continue; // Disregard unchecked fragments
            }

            $x = fragment_reader($xid);
        }

        $qu_prev = item_mdf_output($x, (($scn_import) ? 'import' : 'normal'), $qu_prev);
        // I had to separate this into two functions because I need to call only the other one (item_mdf_output) when
        // I want to print the code for the clones (c. epg_modify_multi.php)
    }

}





/**
 * Outputs one single element/fragment for MODIFY-MULTI table (for EPG or SCNR)
 *
 * @param array $x Element/Fragment array
 * @param string $item_typ Item type: (normal, clone, import)
 * @param int $qu_prev Previous element's queue
 *
 * @return int $qu This element's queue
 */
function item_mdf_output($x, $item_typ='normal', $qu_prev=0) {

    global $tx, $cfg;


    $epg_line_types = txarr('arrays', 'epg_line_types');

    $ifrm_members = [2,3,4,5,12,13,14];
    // These elements will trigger ifrm control.

    if (!$cfg[SCTN]['epg_prg_use_cbo']) { // Whether to use cbo (or iframe) for choosing prog
        $ifrm_members[] = 1; // Add prog linetyp (1) to array of elements which trigger iframe
    }

    $is_sleepline = ($item_typ=='normal' && !$x['IsActive']) ? true : false;

    if (defined('TMPL_NEW_FROM_EPG') && TMPL_NEW_FROM_EPG) {
        // Delete block content (both mkt and prm) for TMPL_NEW_FROM_EPG situation,
        // because we don't want to copy mkt and prm, but we want their placeholders
        $x['BLC'] = [];
        $x['NativeID'] = '';
    }

    list($css['cpt']) = epg_linetyp($x['NativeType']);

    $qu = ++$qu_prev;


    $no_elm_ids = false; // Whether to discard element IDs

    if ($item_typ!='normal') { // CLONE rows or IMPORT rows

        $no_elm_ids = true;

    } elseif (EPG_NEW) {// If it's *NEW* EPG, discard the element IDs as they surely belong to copied-from EPG's elements

        $no_elm_ids = true;

    } elseif (SCN_NEW_FROM_TMPL) { // NEW SCN FROM TMPL situation

        $no_elm_ids = true;

    } elseif (TYP=='epg' && TMPL && @!$x['EPG']['IsTMPL']) { // NEW EPG-TMPL from EPG

        $no_elm_ids = true;
    }


    // Movable TRs in DND table must have integers for IDs.. Clones will not be movable.

    if ($item_typ=='clone') {

        echo '<tr id="clone['.$x['NativeType'].']" style="display: none;">';

    } else {

        echo '<tr id="'.$qu.'"'.(($is_sleepline) ? ' class="sleepline"' : '').'>';
    }

    echo '<td style="position: relative;">';

    
    if ($item_typ=='normal' && $x['NativeType']!=8) {

        $termemit = (TYP=='epg') ? date('H:i:s', strtotime($x['TermEmit'])) : $x['TermEmit'];

        echo '<span class="termemit">'.$termemit.'</span>';
    }


    // Inside each DND TR, first we have hidden input fields..

    $ctrl = '<input type="hidden" name="%2$s[%1$d][]" id="%2$s[%1$d][]" value="%3$s">';

    printf($ctrl, $x['NativeType'], 'id', ((!$no_elm_ids) ? @$x['ID'] : ''));
    printf($ctrl, $x['NativeType'], 'cnt', $qu);
    printf($ctrl, $x['NativeType'], 'del', '');


    // And then in each DND TR we have table with real data..

    echo '<table id="tblwrap">'.
        '<tr>';


    // LEFT hanging controls

    echo
        '<td class="ghost">'.
            '<div class="floater-outside"><div class="floater-inside left">'.
                '<a href="#" class="text-muted squiz" onclick="squizer_mdfmulti(this); return false;">'.
                    '<span class="glyphicon glyphicon-circle-arrow-right"></span>'.
                '</a>'.
            '</div></div>'.
        '</td>';


    // CAPTION

    echo '<td class="cpt text-uppercase '.$css['cpt'].'">'.$epg_line_types[$x['NativeType']].'</td>';


    // TERM

    if (in_array($x['NativeType'], [2,8,10])) { // Types which don't have term/dur (note, segmenter, story)

        echo '<td class="term '.$css['cpt'].'"></td>';

    } else {

        $dis = (TMPL && TYP=='scnr') ? ' disabled' : '';      // TERM is omitted for scenario templates

        if (TYP=='epg' && $item_typ!='clone' && $x['OnHold']) {
            $x['TimeAirTXT'] = ['hh' => '*', 'mm' => '*', 'ss' => '*'];
        }

        $ctrl = '<input type="text"'.$dis.' name="term-%2$s['.$x['NativeType'].'][]" id="term-%2$s" value="%1$s" '.
            'size="1" maxlength="2" onkeydown="return hmsbox(this, \'term\');">';

        echo '<td class="term '.TERM_TYP.'">';

        printf($ctrl.'<span>:</span>', @$x['TimeAirTXT']['hh'], 'hh');
        printf($ctrl.'<span>:</span>', @$x['TimeAirTXT']['mm'], 'mm');
        printf($ctrl, @$x['TimeAirTXT']['ss'], 'ss');

        echo '</td>';
    }


    // DURATION

    if (in_array($x['NativeType'], [2,8,10])) { // Types which don't have term/dur (note, segmenter, story)

        echo '<td class="dur '.$css['cpt'].'"></td>';

    } else {

        $dis = ($x['NativeType']==14) ? ' disabled' : '';       // DUR is omitted for LINKERS

        $ctrl = '<input type="text"'.$dis.' name="dur-%2$s['.$x['NativeType'].'][]" id="dur-%2$s" value="%1$s" '.
            'size="1" maxlength="2" onfocus="hmsdur_focus(this,1);" onblur="hmsdur_focus(this,0);" '.
            'onkeydown="return hmsbox(this, \'dur\');">';

        echo '<td class="dur durepg">';

        printf($ctrl.'<span>:</span>', @$x['DurForcTXT']['hh'], 'hh');
        printf($ctrl.'<span>:</span>', @$x['DurForcTXT']['mm'], 'mm');
        printf($ctrl, @$x['DurForcTXT']['ss'], 'ss');

        echo '</td>';
    }



    // CONTROLS CELL start

    echo '<td class="ctrl '.$css['cpt'].'">'.
        '<div class="row">';


    // IFRM LABEL

    if (in_array($x['NativeType'], $ifrm_members)) {

        $ifrm = ifrm_setting($x);

        $ifrm['disabled'] = $is_sleepline;

        echo '<div class="col-sm-'.$ifrm['div_col_span'].
            ((isset($ifrm['div_col_css']) ? ' '.$ifrm['div_col_css'] : '')).
            '">';

        ifrm_output_lbl('multi', $ifrm);

        echo '</div>';
    }


    switch ($x['NativeType']) {

        case 1:     // prg
        case 12:    // film
        case 13:    // film-serial


            // PROG only: caption textbox (films don't need it)
            if ($x['NativeType']==1) {

                echo '<div class="col-sm-4 pad_l0 pad_r0">';

                // Caption textbox
                printf('<input type="text" class="form-control input-sm" name="dsc[%1$d][]" id="dsc[%1$d][]" '.
                    'value="%2$s" maxlength="250" placeholder="'.$tx[SCTN]['LBL']['theme'].'">',
                    $x['NativeType'], @$x['PRG']['Caption']
                );

                // Cbo for choosing prog, if we don't use iframe for the same purpose, which is default
                if ($cfg[SCTN]['epg_prg_use_cbo']) {
                    ctrl_prg('ProgID[1][]', @$x['PRG']['ProgID'], ['output' => true, 'chnl' => $x['EPG']['ChannelID']]);
                }

                echo '</div>';
            }


            $epg_material = txarr('arrays', 'epg_material_types');

            if (!$cfg[SCTN]['mattype_use_cbo']) {

                foreach ($epg_material as $k => $v) {
                    $epg_material[$k] = mb_substr($v,0,2);
                }
            }

            if (!isset($x['PRG']['MatType'])) { // Default value for MatType
                $x['PRG']['MatType'] = ($x['NativeType']==1) ? $cfg[SCTN]['epg_mattyp_def'] : 2;
            }

            // MatType buttons

            echo '<div class="col-sm-2 pad_l5 mattyp">';

            if ($cfg[SCTN]['mattype_use_cbo']) { // Whether to use cbo (or radio buttons) for mattype

                if ($x['NativeType']!=1) unset($epg_material[1]); // Films(12,13) cannot be liveair

                echo '<select name="MatType['.$x['NativeType'].'][]">'.
                    arr2mnu($epg_material, @$x['PRG']['MatType']).
                    '</select>';

            } else { // Use radio buttons. THIS IS DEFAULT.

                // Shadow submitter
                echo '<input type="hidden" name="MatType['.$x['NativeType'].'][]" id="MatType['.$x['NativeType'].'][]" '.
                    'value="'.@$x['PRG']['MatType'].'">';

                echo '<div class="btn-group btn-group-sm btn-group-justified" data-toggle="buttons">';

                foreach ($epg_material as $k => $v) {

                    $tmp_name = 'tmp_'.$item_typ.'['.sprintf('%02s%03s', $x['NativeType'], $qu).']';
                    // %02s and %03s - right-justification with zero-padding

                    echo
                        '<label onclick="btngroup_shadow(this);" '.
                        (($x['NativeType']!=1 && $k==1) ? 'disabled="disabled" ' : ''). // Films(12,13) cannot be liveair
                        'class="btn btn-default'.((@$x['PRG']['MatType']==$k) ? ' active' : '').'">'.
                        '<input type="radio" name="'.$tmp_name.'" value="'.$k.'"'.
                        ((@$x['PRG']['MatType']==$k) ? ' checked' : '').'>'.$epg_material[$k].
                        '</label>';
                }

                echo '</div>';

            }

            // Parental limit can either be set on film details (default), or for each film separately
            // (in which case we would have to add a text control for it here)
            if (!$cfg['lbl_parental_filmbased']) {

                printf('<input type="text" class="parental" name="Parental[%1$d][]" id="Parental[%1$d][]" '.
                    'value="%2$s" size="1" maxlength="2">',
                    $x['NativeType'], @$x['Parental']);
            }

            // Record-for-rerun can either be set on prog details (default), or for each prog separately
            // (in which case we would have to add a checkbox control for it here)
            if (!$cfg['lbl_rec4rerun_prgbased']) {

                printf('<input type="checkbox" name="Record_z[%1$d][]" id="Record_z[%1$d][]" class="record" %2$s '.
                    'value="1" tabindex=-1 onclick="this.nextSibling.value=Number(this.checked);">'.
                    '<input name="Record[%1$d][]" id="Record[%1$d][]" type="hidden" value="%3$d">',
                    $x['NativeType'], ((@$x['Record']) ? 'checked' : ''), intval(@$x['Record']));

                /* We have to use this fix with hidden field shadow on every checkbox, because checkboxes
                 * have no value at all when not checked, and that would make a mess because receiver wants
                 * each and every line to have a value.
                 * We can't use just hidden field copy because receiver would then count every checkbox twice..
                 * that's how receiver works..
                 */
            }

            echo '</div>';

            break;


        case 4: // promo

            $mnu_html = arr2mnu(txarr('arrays', 'epg_prm_ctgz'), @$x['AttrA']);

            echo
                '<div class="col-sm-4 pad_l5">'.
                    '<select name="AttrA['.$x['NativeType'].'][]" class="form-control input-sm">'.$mnu_html.'</select>'.
                '</div>';
            break;


        case 7:  // liveair
        case 8:  // note
        case 9:  // hole
        case 10: // segmenter

            if ($x['NativeType']==8) { // note cbo

                echo
                    '<div class="col-sm-2 pad_r5">'.
                        '<select name="NoteType['.$x['NativeType'].'][]" class="form-control input-sm">'.
                            arr2mnu(txarr('arrays', 'epg_note_types'), @$x['NOTE']['NoteType']).
                        '</select>'.
                    '</div>'.
                    '<div class="col-sm-10 pad_l0">';

            } else {

                echo '<div class="col-sm-12">';
            }

            // textbox
            printf('<input type="text" class="form-control input-sm" '.
                'name="dsc[%1$d][]" id="dsc[%1$d][]" value="%2$s" maxlength="90"></div>',
                $x['NativeType'], @$x['NOTE']['Note']);

            echo '</div>';

            break;
    }


    echo '</div></td>';
    // CONTROLS CELL finish


    // RIGHT hanging controls

    echo
        '<td class="ghost">'.
            '<div class="floater-outside"><div class="floater-inside right">'.
                '<a class="text-muted" href="#" onClick="element_switch(this); return false;">'.
                    '<span class="glyphicon glyphicon-ok-circle"></span></a>'.
                '<a class="text-muted'.(($is_sleepline) ? ' disabled' : '').'" '.
                    'href="#" onClick="element_double(this,'.$x['NativeType'].'); return false;">'.
                    '<span class="glyphicon glyphicon-duplicate"></span></a>'.
            '</div></div>'.
        '</td>';

    echo '</tr>';
    // ROW finish



    echo '</table></td></tr>'.PHP_EOL.PHP_EOL;

    return $qu;
}



























/**
 * Prints EPG or SCNR
 *
 * This function can be initiated from:
 * - epg.php script, for either EPG or SCN list type (NORMAL field);
 * - itself, for SHEET (drop-down field with content of specific element/fragment) - (SHEET field type)
 *           and to fetch items for BROADCAST list from within PROGS (BCAST field type, SCNR list type)
 *
 * @param int $id ID of the master object, for which we want list, and which can be of four specified below list types.
 *
 * @param string $listtyp List type: (epg, scnr, spice, story).
 *                        SPICE (which means mkt or prm) and STORY are types used only for SHEET field.
 *
 * @param string $field Field type: (normal, normal_studio, sheet, sheet_studio, bcasts).
 *                      SHEET is drop-down field, BCAST is broadcast list.
 *
 * @param array $opt (used only for SCNR lists and SHEETS - objects which have a PARENT element. Not for EPG.)
 * - zero_term (string) Zero term is the TimeAir (i.e. start term) of the master object
 * - parent_dur (string) Duration of parent element
 * - epgfilmid (int) - epg_films:ID. Used only for films.
 * - block_parent_typ (string) - Block parent type (epg, scnr)
 *
 * @return int $col_sum	Column sum, without GHOST columns (important only when called from epg.php)
 */
function epg_dtl_html($id, $listtyp='epg', $field='normal', $opt=null) {

    global $tx, $cfg;
    global $speakerz;


    $id = intval($id);


    $zero_term = (isset($opt['zero_term'])) ? $opt['zero_term'] : '';

    $list_dur_sum = $segment_dur_sum = '00:00:00';	// starting values for DURATION variables

    // At the end of each iteraton we save the TimeAir (i.e. start term) and duration of the iterated line, to be used
    // later in TimeAir calculations in case the following line doesn't have fixed TimeAir.
    $prev_term = ''; // this will also be used for a boolean check, thus we leave it this way, we cannot set it to "00:00:00"
    $prev_dur = '00:00:00';

    // We shall use this to decide whether to color the error-dur. We don't want to color the subsequent *fixed* terms.
    $prev_term_typ = '';

    // $term_rel is not reset on loop, thus it will hold value for previous line.
    $term_rel = '00:00:00';

    // Whether previous line was shadow or sleep line. We use it when deciding whether to set the *empty* css on term cln.
    $prev_skip = true;


    // Columns switching

    $show_cln_term_fix      = true;
    $show_cln_term_rel      = false;
    $show_cln_err           = false;
    $show_cln_numero        = false;
    $show_cln_dur_invalid   = false;
    $show_cln_tframe        = (VIEW_TYP==8); // MKT TIMEFRAMES

    if ($field=='normal') {

        if ($listtyp=='epg') {

            if (VIEW_TYP==0) {
                $show_cln_err = true;
            }

        } else { //$listtyp=='scnr'

            if (VIEW_TYP==0) { // SCNR LIST
                $show_cln_err = true;
                $show_cln_term_rel = true;
            }
        }
    }

    if ($listtyp=='story' && VIEW_TYP==3) {
        $show_cln_numero = true;
    }

    if (VIEW_TYP==8) {
        $show_cln_numero = true;
        static $cnt_numero_block = 0;
    }

    if (in_array($field, ['normal', 'sheet'])) {

        if ($listtyp=='epg') {

            if (VIEW_TYP==0) {
                $show_cln_dur_invalid = true;
            }


        } elseif ($cfg[SCTN]['scnr_show_cln_dur_invalid']) {

            $show_cln_dur_invalid = true;
        }
    }


    $col_sum = 2; // Caption & Dur

    if ($show_cln_term_fix)     $col_sum++;
    if ($show_cln_term_rel)     $col_sum++;
    if ($show_cln_err)          $col_sum++;
    if ($show_cln_dur_invalid)  $col_sum++;
    if ($show_cln_tframe)       $col_sum++;
    if ($show_cln_numero)       $col_sum++;

    //$col_sum_with_ghosts = $col_sum + 2; // Column count with ghost columns, i.e. columns for hanging controls


    $cnt_numero = 0; // for NUMERO (numbering) cell

    $is_segment_start = true;
    // Marker for the FIRST LINE IN THE SEGMENT. Used for SEGMENTER data calculations.


    // For SCNRs except those of LIVEAIR(1) type, terms (TimeAir column) represent RELATIVE values, instead absolute,
    // i.e. they are relative to the scnr start.. Otherwise, terms are always absolute.
    $terms_relative = (($listtyp=='scnr') && (rdr_cell('epg_scnr', 'MatType', $id)!=1)) ? true : false;


    $atom_typz = txarr('arrays', 'atom_jargons.'.ATOM_JARGON);

    $atom_live_techtypz = txarr('arrays', 'dsk_atom_live_techtyp_short');

    $epg_mattyp_signs = txarr('arrays', 'epg_mattyp_signs');

    if (CHNLTYP==2) {
        $epg_mattyp_signs[1] = $cfg[SCTN]['epg_radio_live_sign'];
    }

    if (TYP=='scnr' && VIEW_TYP==0) {
        $setz['scnr_list_cam'] = setz_get('scnr_list_cam');
    }

    if (VIEW_TYP==0) {
        $setz['epg_hide_inactives'] = setz_get('epg_hide_inactives');
    }

    if (TYP=='epg') {
        $setz['epg_list_filter_teams'] = setz_get('epg_list_filter_teams');
    }

    if (TYP=='epg' && VIEW_TYP==0) {
        $setz['epg_show_skop_cvr'] = setz_get('epg_show_skop_cvr');
    }

    $btn_squiz =
        '<div class="floater-outside"><div class="floater-inside left">'.
            '<a href="#" class="text-muted squiz" onclick="squizer_epglist(this, %d); return false;">'.
                '<span class="glyphicon glyphicon-circle-arrow-right"></span>'.
            '</a>'.
        '</div></div>';



    // TABLE HEADERS (they depend on field type)

    switch ($field) {

        case 'normal':

            if (VIEW_TYP==0) {

                $txt = explode('*', txarr('blocks', 'help_epgterm'));

                $term_helper = help_output('button', [
                    'name'    => 'epgterm',
                    'title'   => $txt[0],
                    'css'     => 'help th',
                    'content' => $txt[1],
                    'output'  => false
                ]);

                $txt = explode('*', txarr('blocks', 'help_epgdur__'.$listtyp));

                $dur_helper = help_output('button', [
                    'name'    => 'epgdur',
                    'title'   => $txt[0],
                    'css'     => 'help th',
                    'content' => $txt[1],
                    'output'  => false
                ]);

            } else {

                $term_helper = '';
                $dur_helper = '';
            }


            if (!SCH_BLANK) {

                echo '<tr class="tr_header">';

                if (VIEW_TYP!=5) {
                    echo '<th class="ghost"></th>';
                }

                if ($show_cln_term_rel || $show_cln_term_fix) {
                    echo '<th style="position:relative;"'.(($show_cln_term_rel && $show_cln_term_fix) ? ' colspan="2"' : '').'>'.
                        $tx['LBL']['term'].$term_helper.
                        '</th>';
                }

                if ($show_cln_err) {
                    echo '<th style="padding:5px;">'.$tx[SCTN]['LBL']['error'].'</th>';
                }

                echo '<th '.(($show_cln_dur_invalid) ? 'colspan="2" ' : '').'style="position:relative;">'.
                    $tx['LBL']['duration'].(($show_cln_dur_invalid) ? $dur_helper : '').'</th>';

                echo '<th style="text-align:left;">'.$tx['LBL']['title'];

                if (in_array(VIEW_TYP, [0,1])) { // Switch buttons for tips and last-phrase labels

                    echo '<div class="swtch">';

                    $href = '?typ='.TYP.'&id='.intval($_GET['id']).'&view='.VIEW_TYP;

                    if (SHOW_TIPS_NOTE && CTRL_TIPS_NOTE) {

                        echo '<a title="'.$tx[SCTN]['LBL']['show_note_ctrlz'].'" '.
                            'href="'.$href.'&mdftips='.(1-MDF_TIPS_NOTE).'"'.(MDF_TIPS_NOTE ? ' class="on"' : '').'>'.
                            '<span class="glyphicon glyphicon-exclamation-sign"></span></a>';
                    }

                    if (SHOW_TIPS_CAM && CTRL_TIPS_CAM) {

                        echo '<a title="'.$tx[SCTN]['LBL']['show_cam_ctrlz'].'" '.
                            'href="'.$href.'&mdfcam='.(1-MDF_TIPS_CAM).'"'.(MDF_TIPS_CAM ? ' class="on"' : '').'>'.
                            '<span class="glyphicon glyphicon-facetime-video"></span></a>';
                    }

                    if (SHOW_TIPS_VO && CTRL_TIPS_VO) {

                        echo '<a title="'.$tx[SCTN]['LBL']['show_cam_vo'].'" '.
                            'href="'.$href.'&mdfvo='.(1-MDF_TIPS_VO).'"'.(MDF_TIPS_VO ? ' class="on"' : '').'>'.
                            '<span class="glyphicon glyphicon-film"></span></a>';
                    }

                    if (SHOW_JS_CTRLZ) { // Buttons only for scnr, and pms-free

                        echo '<a title="'.$tx[SCTN]['LBL']['show_elm_text'].'" href="#" '.
                            ((empty($_SESSION['SCNR_ATOM_COLLAPSE'])) ? '' : 'class="on" ').
                            'onclick="atomtxt_switch_all(this); swtch(this); '.
                            'ajaxer(\'GET\', \'/_ajax/_aj_sessvar.php\', \'name=SCNR_ATOM_COLLAPSE\', null, null); '.
                            'return false;"><span class="glyphicon glyphicon-resize-'.
                            ((empty($_SESSION['SCNR_ATOM_COLLAPSE'])) ? 'small' : 'full').'"></span></a>';

                        echo '<a title="'.$tx[SCTN]['LBL']['show_cvr'].'" href="#" '.
                            ((empty($_SESSION['SCNR_CVR_COLLAPSE'])) ? '' : 'class="on" ').
                            'onclick="display_switch_all(\'cvr_clps\',\'table-row\'); swtch(this); '.
                            'ajaxer(\'GET\', \'/_ajax/_aj_sessvar.php\', \'name=SCNR_CVR_COLLAPSE\', null, null); '.
                            'return false;">'.
                            '<span class="glyphicon glyphicon-tag"></span></a>';

                        echo '<a title="'.$tx[SCTN]['LBL']['show_text_ending'].'" href="#" '.
                            ((empty($_SESSION['SCNR_TEXTEND'])) ? '' : 'class="on" ').
                            'onclick="display_switch_all(\'schlbl texter\',\'inline\'); swtch(this); '.
                            'ajaxer(\'GET\', \'/_ajax/_aj_sessvar.php\', \'name=SCNR_TEXTEND\', null, null); '.
                            'return false;">'.
                            '<span class="glyphicon glyphicon-scissors"></span></a>';
                    }

                    echo '</div>';
                }

                echo '</th>';

                echo '<th class="ghost"></th>'.
                    '</tr>';
            }

            break;

        case 'normal_studio':
            break;

        case 'bcasts':

            // BCASTs have different header because they imitate LIST pages. This header should be displayed only once,
            // when the function is initiated for EPG list type. Should not be displayed when it is initiated
            // for SCNR list type, later in the loop.

            if ($listtyp=='epg') {

                echo '<tr class="tr_header">';
                echo (($show_cln_numero) ? '<th></th>' : '');
                echo ((PMS_BC) ? '<th>'.$tx[SCTN]['LBL']['verification'].'</th>' : '');
                echo '<th>'.$tx['LBL']['term'].'</th>';
                echo '<th>'.$tx['LBL']['duration'].'</th>';
                echo (($show_cln_tframe) ? '<th class="tframe"></th>' : '');
                echo '<th style="text-align:left;">'.$tx['LBL']['title'].'</th>';
                echo '</tr>';
            }

            break;

        case 'sheet':

            // SHEETs don't have header at all, because they are simply displayed below their parent (slightly indented)

            break;
    }




    // MOS FRAGMENT

    if (in_array(VIEW_TYP, [0,1]) && $terms_relative && $field!='bcasts') {

        $tx['MOS']	= txarr('labels','mos');

        $mos = (empty($opt['epgfilmid'])) ? mos_reader($id, 1, 'Duration') : epg_film_reader($opt['epgfilmid']);

        $list_dur_sum = sum_durs([$list_dur_sum, $mos['Duration']]);
        // We are adding this dur to the ENTIRE LIST sum, which will be displayed at FINITO-LINE
        // $list_dur_sum is actually zero at this moment, but we leave it this way for the sake of code clarity

        echo '<tr class="mosfragment hidden-print">';

        if ($field=='normal') {
            echo '<td class="ghost"></td>';
        }

        if ($show_cln_numero) {
            echo '<td id="numero">&nbsp;</td>';
        }

        if ($show_cln_term_fix) {
            echo '<td>&nbsp;</td>';
        }
        if ($show_cln_term_rel) {
            echo '<td class="hidden-print">&nbsp;</td>';
        }
        if ($show_cln_err) {
            echo '<td class="hidden-print dur">&nbsp;</td>';
        }

        echo '<td '.(($show_cln_dur_invalid) ? 'colspan="2" ' : '').'class="dur durreal durcalc"><span class="spacer">'.
            $mos['Duration'].'</span></td>'.
            '<td class="cpt">['.$tx['MOS']['record'].']</td>';

        if ($field=='normal') {
            echo '<td class="ghost"></td>';
        }

        echo '</tr>';
    }



    switch ($listtyp) {

        // We need TABLE to fetch lines (children) from, and COLUMN for filtering by ID (i.e. parent)

        case 'epg':
            $tbl = 'epg_elements';
            $cln = 'EpgID';
            $zero_term = epg_zeroterm(rdr_cell('epgz', 'DateAir', $id));
            break;

        case 'scnr':
            $tbl = 'epg_scnr_fragments';
            $cln = 'ScnrID';
            break;

        case 'spice':
            $tbl = 'epg_cn_blocks';
            $cln = 'BlockID';
            break;

        case 'story':
            $tbl = 'stry_atoms';
            $cln = 'StoryID';
            break;
    }


    $css_printhid = (in_array(VIEW_TYP, [0,1]) && TYP!='epg') ? ' hidden-print' : '';


    $sql = sprintf('SELECT ID FROM %s WHERE %s=%d ORDER BY Queue', $tbl, $cln, $id);
    $result = qry($sql);

    // THE BIG LOOP start
    while (@list($xid) = mysqli_fetch_row($result)) {

        switch ($listtyp) {
            case 'epg':			$x = element_reader($xid);   break;
            case 'scnr':	    $x = fragment_reader($xid);  break;
            case 'spice':		$x = epg_spice_reader($xid); break;
            case 'story':		$x = epg_story_reader($xid); break;
        }


        if (empty($x['ID'])) {
            // It happens (seldom) that an element gets deleted by one user and at the same time the other user
            // gets it in this loop just a moment before, which pruduces an error. Prevent that.
            continue;
        }

        if (VIEW_TYP==3 && $listtyp=='scnr' && !$x['IsActive']) { // Do not display inactive rows in studio view
            continue;
        }


        if ($field!='bcasts' && LOG) {
            if ($listtyp=='epg') {
                logzer_output('tbl-epg-get', ['obj_id' => $x['ID'], 'obj_typ' => 'element']);
            }
            if ($listtyp=='scnr') {
                logzer_output('tbl-epg-get', ['obj_id' => $x['ID'], 'obj_typ' => 'fragment']);
            }
        }



        $is_shadowline = (in_array($x['NativeType'], [8,10])) ? true : false;
        // SHADOWLINE means line which doesn't have term nor duration. These are: note, segmenter.

        $is_sleepline = (in_array($listtyp, ['epg', 'scnr', 'spice']) && !$x['IsActive']) ? true : false;
        // SLEEPLINE means DEACTIVATED, i.e. inactive line


        $css = []; // We have to reset css array each time we loop

        list($css['cpt'][], $sct) = epg_linetyp($x['NativeType']);


        // CAPTION CELL DATA

        switch ($x['NativeType']) {

            /*
             * $x['_Caption']
             * Every line must have a caption, and it is set here
             *
             * $x['_CNT_Frag']
             * For linetypes which can have SHEETS (1,2,3,4,12,13), we check whether there are any children lines
             * (fragments). We save COUNT value, because we will use it for drop-down button.
             *
             * $x['_CPT_href']
             * If caption text should be LINK, then it is specified here.
             *
             */

            case 1: // prg

                $x['_Caption'] = '<span class="cpt">'.$x['PRG']['ProgCPT'].'</span>'.
                    (($x['PRG']['Caption']) ? ' - '.$x['PRG']['Caption'] : '');

                $x['_CNT_Frag'] = cnt_sql('epg_scnr_fragments', 'ScnrID='.$x['NativeID'].' AND NativeType NOT IN (8,10)');
                // We don't take into account Notes(8) and Segmenters(10)

                $x['_CPT_href'] = 'epg.php?typ=scnr&id='.$x['ID'];

                break;


            case 2: // stry

                $x['_Caption'] = '<span class="cpt">'.$x['STRY']['Caption'].'</span>';

                $x['_CNT_Frag'] = cnt_sql('stry_atoms', 'StoryID='.$x['NativeID']);

                $x['_CPT_href'] = '/desk/stry_details.php?id='.$x['NativeID'];

                if (!$x['_CNT_Frag']) {
                    $x['ATOMZ_SPKRZ'] = '';
                    $x['ATOMZ_LBL'] = '';
                    break;
                }

                if (in_array(VIEW_TYP, [0,3])) {

                    $x['ATOMZ'] = rdr_cln('stry_atoms', 'TypeX, TechType', 'StoryID='.$x['NativeID'], 'Queue');

                    foreach ($x['ATOMZ'] as $atom_id => $atom_data) {

                        $atom_typ = $atom_data[0];
                        $atom_techtyp = $atom_data[1];

                        if ($atom_typ==1) {

                            $x['ATOMZ_SPKRZ'][] = rdr_cell('stry_atoms_speaker', 'SpeakerX', $atom_id);

                            $atomlbl = $atom_typz[$atom_typ];

                            $vo = atom_is_vo($atom_id);

                            if ($vo) {

                                switch (ATOM_JARGON) {

                                    case 3: // octopus
                                        $atomlbl = $tx['LBL']['jargon'.ATOM_JARGON.'_cam_vo'];
                                        break;

                                    case 4: // tvsa
                                        $atomlbl = $cfg[SCTN]['epg_vo_sign'].$tx['LBL']['jargon'.ATOM_JARGON.'_cam_simple'];
                                        break;

                                    default:
                                        $atomlbl = $cfg[SCTN]['epg_vo_sign'].$atomlbl;
                                }

                            } else {

                                if (in_array(ATOM_JARGON, [3,4])) {

                                    $atomlbl = $tx['LBL']['jargon'.ATOM_JARGON.'_cam_simple'];
                                }
                            }

                            if (in_array(ATOM_JARGON, [3,4])) { // Avoid same consequtive labels

                                if (isset($x['ATOMZ_LBL']) && $x['ATOMZ_LBL'][count($x['ATOMZ_LBL'])-1] == $atomlbl) {
                                    $atomlbl = '';
                                }
                            }

                            if (TYP=='scnr' && VIEW_TYP==0 && $setz['scnr_list_cam']) {

                                $tip = epg_tip_reader(['schtyp' => 'stry', 'schline' => $atom_id, 'tiptyp' => 2]);

                                if ($tip) {
                                    $atomlbl .= '-'.$tip['Tip'];
                                }
                            }

                        } elseif ($atom_typ==2) {

                            $atomlbl = $atom_typz[$atom_typ];

                        } elseif ($atom_typ==3) {

                            $atomlbl = $atom_live_techtypz[intval($atom_techtyp)];
                        }

                        if ($atomlbl) {

                            $x['ATOMZ_LBL'][] = $atomlbl;
                        }
                    }

                    $x['ATOMZ_LBL'] = (isset($x['ATOMZ_LBL'])) ? implode('/', $x['ATOMZ_LBL']) : '';

                    if (ATOM_JARGON==4) { // TVSA: *single* cam in a story is called differently

                        if ($x['ATOMZ_LBL']==$tx['LBL']['jargon4_cam_simple']) {
                            $x['ATOMZ_LBL'] = $tx['LBL']['jargon4_cam_single'];
                        }

                        if ($x['ATOMZ_LBL']==$cfg[SCTN]['epg_vo_sign'].$tx['LBL']['jargon4_cam_simple']) {
                            $x['ATOMZ_LBL'] = $cfg[SCTN]['epg_vo_sign'].$tx['LBL']['jargon4_cam_single'];
                        }
                    }
                }

                if (VIEW_TYP==0) {

                    if (!empty($x['ATOMZ_SPKRZ'])) {

                        if (count($x['ATOMZ_SPKRZ'])>1) {

                            $spkrz_uniq = array_unique($x['ATOMZ_SPKRZ']);

                            if (count($spkrz_uniq)==1) {

                                $x['ATOMZ_SPKRZ'] = $spkrz_uniq;
                            }
                        }

                        foreach ($x['ATOMZ_SPKRZ'] as $k => $spkr_x) {

                            if (!empty($speakerz[$spkr_x]['uid'])) {

                                $x['ATOMZ_SPKRZ'][$k] = $speakerz[$spkr_x]['name_short'];
                            }
                        }

                        $x['ATOMZ_SPKRZ'] = implode(', ', $x['ATOMZ_SPKRZ']);

                    } else {

                        $x['ATOMZ_SPKRZ'] = '';
                    }
                }

                break;


            case 3:	// spice
            case 4:

                $x['_CPT_href'] =
                    'spice_details.php?sct='.$sct.'&typ='.(($listtyp!='spice') ? 'block' : 'item').'&id='.$x['NativeID'];

                if ($listtyp!='spice') { // block

                    $x['_Caption'] = @$x['BLC']['Caption'];

                    $x['_CNT_Frag'] = cnt_sql('epg_cn_blocks', 'BlockID='.$x['NativeID']);

                } else { // item

                    $x['_Caption'] = $x['Caption'];
                }

                break;


            case 5: // clp

                $x['_Caption'] = ($listtyp!='spice') ? $x['CLP']['Caption'] : $tx[SCTN]['LBL']['clp'].': '.$x['Caption'];

                if (isset($x['CLP']['CtgID']) && $x['CLP']['CtgID']==3 && $x['CLP']['Placing']) {
                    $x['_Caption'] .= ' ('.txarr('arrays', 'epg_clp_place', $x['CLP']['Placing']).')';
                }

                $x['_CPT_href'] = 'spice_details.php?sct='.$sct.'&id='.$x['NativeID'];
                break;


            case 7: // liveair

                $x['_Caption'] = '<span class="lbl_left">'.$x['NOTE']['Note'].'</span>';
                break;


            case 8: // note

                $x['_Caption'] = '<span class="note">'.$x['NOTE']['Note'].'</span>';
                break;


            case 9: // hole

                $x['_Caption'] = $x['NOTE']['Note'];
                break;


            case 10: // segmenter

                $x['_Caption'] = $x['NOTE']['Note'];
                break;


            case 12: // film
            case 13: // film-serial

                $x['_Caption'] = film_caption($x['FILM'], ['typ' => $x['NativeType']]);

                $x['_CNT_Frag'] = cnt_sql('epg_scnr_fragments', 'ScnrID='.$x['SCNRID'].' AND NativeType NOT IN (8,10)');

                $x['_CPT_href'] = 'epg.php?typ=scnr&id='.$x['ID'];

                break;


            case 14: // linker

                $x['_Caption'] = epg_link_cpt($x);
                $x['_CPT_href'] = 'epg.php?typ=scnr&id='.$x['NativeID'];
                break;


            case 20: // atom

                $x['_Caption'] = mb_strtoupper($atom_typz[$x['TypeX']]);

                if ($x['TypeX']==1) {

                    $vo = atom_is_vo($x['ID']);

                    if ($vo) {
                        $x['_Caption'] = $cfg[SCTN]['epg_vo_sign'].$x['_Caption'];
                    }

                } elseif ($x['TypeX']==3) {

                    $x['_Caption'] .= '/'.$atom_live_techtypz[intval($x['TechType'])];
                }

                $clicker = (VIEW_TYP==1) ? ' onclick="atomtxt_switch('.$x['ID'].'); return false;"' : '';

                $x['_Caption'] = '<button class="btn btn-xs typer lbl_left"'.$clicker.'>'.$x['_Caption'].'</button>';

                break;
        }



        // TERM & DUR calc and update

        if (!$is_shadowline) { // shadowlines (notes, segmenters) are excluded as they don't have term and duration

            // Duration

            $dur_frmt = 'ms';

            if (($listtyp=='epg' && $field!='sheet' && !in_array(VIEW_TYP, [8,9]))) {
                $dur_frmt = 'hms';
            }

            $x['_Dur'] = epg_durations($x, $dur_frmt);
            // epg_durations will decide which DUR to use (epg-forc, forc or calc).
            // It will return array with winner and loser values.

            if (!$is_sleepline) {

                // Here we increment the duration for both the segment and the entire list, in the same way, but
                // segment duration will be reset at the segment end, and the other one will keep counting until the
                // list end. These variables are not again incremented anywhere past this line.


                // Increment the duration sum for the SEGMENT (which will be displayed in SEGMENTER-LINE)
                $segment_dur_sum = sum_durs([$segment_dur_sum, $x['_Dur']['winner']['dur_hms']]);


                if (@$mos['Duration']) {

                    // (For RECORDED progs, as only they can have MOSdur)
                    // If MOSdur is not null, then take it into account but disregard all frags except mkt and prm and holes
                    // This is because even progs of *recorded* type may also be prepared in prodesk, and later when the record
                    // is ready, the editor inputs MOSdur into prodesk. After that point we don't want to take news dur
                    // into prog dur calculation.. But we want to incalculate dur of mkt/prm blocks, as they are added later, i.e.
                    // they are not included in MOSdur of a prog..
                    // Note: search "REC-PROG"

                    if (in_array($x['NativeType'], [3,4,9])) {

                        $dur_skip = false;

                    } else {

                        $dur_skip = true;

                        $css['dur'][] = 'epgempt';
                    }

                } else {

                    $dur_skip = false;
                }

                if (!$dur_skip) {

                    // Increment the duration sum for the ENTIRE LIST (which will be displayed in FINITO-LINE)
                    $list_dur_sum = sum_durs([$list_dur_sum, $x['_Dur']['winner']['dur_hms']]);
                }
            }


            /* FIXED TERM column
             * We must have fixed term value for every line. It is either already set, or we calculate it.
             * TimeAir column can hold FIXED TERM value, or it can hold RELATIVE term (in that case $terms_relative would
             * be TRUE), or it can be empty. In two latter cases, we have to calculate it.
             * Because TimeAir can have different sorts of values, we save REAL FIXED TERM to $x['_REAL_TimeAir'].
             */

            if (@$x['TimeAir']) {

                $css['term'][] = 'fixed';


                // Whether terms (TimeAir column) represent RELATIVE values, instead absolute. If that is the case,
                // we here calculate REAL (i.e. ABSOLUTE TERM) TimeAir (by adding relative term to zero term)
                if ($terms_relative) {
                    $x['TimeAir'] = add_dur2term($zero_term, date('H:i:s', strtotime($x['TimeAir'])));
                }

                $x['_REAL_TimeAir'] = $x['TimeAir'];


                // TERMS ERROR CHECK

                if ($listtyp=='epg' && $x['EPG']['IsTMPL']) { // EPG TMPL

                    // In EPG templates, fixed terms are missing Ymd part, thus the error calculation is impossible
                    $x['_Error'] = '';

                } else { // NOT EPG TMPL

                    // If TimeAir differs from the term we get by adding duration of the previous line to
                    // the TimeAir of the previous line, then mark this TimeAir as ERROR.
                    $x['_Error'] = terms_diff($x['TimeAir'], add_dur2term($prev_term, $prev_dur));
                }


            } else { // !$x['TimeAir'] --- term not set

                if (!$prev_term) { // This means this is the first line in the list
                    $prev_term = $zero_term;
                }

                // Calculate TimeAir by adding duration of the previous line to the TimeAir of the previous line
                $x['_REAL_TimeAir'] = add_dur2term($prev_term, $prev_dur);

                // Don't use css mark if this is the first line in the list or first line after shadow or sleep line
                if ($x['_REAL_TimeAir']==$prev_term && !$prev_skip) {
                    $css['term'][] = 'epgempt';
                }
            }

            if ($listtyp=='epg' && $field=='normal') {

                if ($x['OnHold']) {
                    $css['term'][] = 'termhold';
                }

                // Not only element which has OnHold set, but all following elements up until the first one with fixed TermAir

                if ($x['OnHold'] || (!empty($holder) && !$x['TimeAir'])) {
                    $holder = true;
                    $css['term'][] = 'epgempt';
                } else {
                    $holder = false;
                }
            }


            // RELATIVE TERM calculation

            if ($terms_relative) { // Whether terms (TimeAir column) represent RELATIVE values, instead absolute

                $term_rel = terms_diff($zero_term, $x['_REAL_TimeAir'], 'hms');
                // Zero term is the TimeAir (i.e. start term) of the master object
                // We calculate RELATIVE term by substracting it from the START term

            } else {

                if (@$x['TimeAir']) { // FIX-TERM

                    //$term_rel = terms_diff($x['TimeAir'], add_dur2term($prev_term, $prev_dur), 'hms');

                    // When FIXterm *is* manually set, we must update the RELterm too..
                    $term_rel = terms_diff($x['TimeAir'], $zero_term, 'hms');

                } else { // NOT FIX-TERM

                    $term_rel = sum_durs([$term_rel, $prev_dur]);
                    // We calculate RELATIVE term by adding duration of the previous line to the relative term of
                    // the previous line ($term_rel is not reset on loop, thus it will hold value for previous line)
                }

            }

            if (!$term_rel) $term_rel = '00:00:00';



            /* Term EMIT update. TermEmit column (in epg_elements and epg_scnr_fragments tables) holds CURRENT state of
             * calculated TimeAir terms which are used in up-to-date check scripts, and for epg-ties, and in WEB schedule,
             * etc. For EPGs it holds FIXED values, for SCN it holds RELATIVE values.
             * We add this code block here in order to ensure that TermEmit values will always be up-to-date.
             */

            if ($listtyp=='epg') {
                if ($x['TermEmit']!=$x['_REAL_TimeAir']) {
                    $x['TermEmit'] = $x['_REAL_TimeAir'];
                    qry('UPDATE '.$tbl.' SET TermEmit=\''.$x['TermEmit'].'\' WHERE ID='.$x['ID']);
                }
            }
            if ($listtyp=='scnr') {
                if ($x['TermEmit']!=$term_rel) {
                    $x['TermEmit'] = $term_rel;
                    qry('UPDATE '.$tbl.' SET TermEmit=\''.$x['TermEmit'].'\' WHERE ID='.$x['ID']);
                }
            }



            // TIES handling. We add it here because it depends on TERMS.
            // (this has to go AFTER TermEmit update, because it uses $x['TermEmit'])

            if (!TMPL && $listtyp=='epg') {
                if (in_array($x['NativeType'], [1,12,13]) && $x['PRG']['MatType']==3 && $x['TIE']['ID']===null) {
                    if ($x['NativeType']==1 || (in_array($x['NativeType'], [12,13]) && $x['FILM']['FilmID'])) {

                        /* Only if NOT template, if EPG (i.e. NOT SCN), if PRG or FILM or SERIAL, if NOT RERUN,
                         * if it doesn't already have a tie and tie was not set to 0 (must be NULL, not zero),
                         * and if it is a FILM then only if the FILM is actually selected (i.e. it is not a HOLDER).
					     * ($x['FILM']['FilmID'] check is to avoid film/serial HOLDERS)
						 */

                        // first we ADD, then we READ
                        epg_tie($x, 'add');
                        $x['TIE'] = epg_tie($x, 'get');

                    }
                }
            }

        }


        // SEGMENT handling
        if ($is_segment_start) {
            $segment_start_term = @$x['_REAL_TimeAir']; // Save starting term of the SEGMENT (we will print it in segmenter)
            $is_segment_start = false;		        // Turn off the marker
        }



        // Decide whether to OUTPUT THIS LINE or not

        $output = true;

        // In TRASH view, output ONLY INACTIVE lines
        if (VIEW_TYP==5 && $x['IsActive']) {
            $output = false;
        }

        // In LIST view, do not output INACTIVE lines if user settings say so
        if (VIEW_TYP==0 && !$x['IsActive'] && $setz['epg_hide_inactives']) {
            $output = false;
        }

        // In TREE view, do not output sleplines
        if (VIEW_TYP==1 && $is_sleepline) {
            $output = false;
        }


        if ($field=='bcasts') { // BCAST FIELD

            switch (VIEW_TYP) {
                case 8:     $bcast_filter = [3];       break;
                case 9:     $bcast_filter = [4];       break;
                case 10:    $bcast_filter = [12,13];   break;
            }

            if (!in_array($x['NativeType'], $bcast_filter)) { // Output only lines of SPECIFIED line-type
                $output = false;
            }

            if ($is_sleepline) { // Discard inactive lines (blocks/films)
                //$output = false;
            }

            // SPICER
            // If this line is program/film (and if the program is not empty, and if we are filtering mkt or prm),
            // we also have to call epg_dtl_html() on scnr-level (SCNR list, BCAST field) in order to
            // search for mkt/prm WITHIN, which we will then display in the bcast list together with
            // MKT/PRM which are epg elements.

            if (in_array($x['NativeType'], [1,12,13]) && $x['_CNT_Frag'] && in_array(VIEW_TYP, [8,9])) {
                // was: "&& !$is_sleepline" - to skip inactive progs; but now we want to also show blocks from inactive progs

                epg_dtl_html((($x['NativeType']==1) ? $x['NativeID'] : $x['SCNRID']), 'scnr', 'bcasts',
                    ['zero_term' => $x['_REAL_TimeAir'], 'parent_dur' => $x['_Dur']['winner']['dur']]);
            }
        }





        /* **************************************************************
         *                      LINE OUTPUT: start
         * **************************************************************
         */

        if ($output) {


            $css['cpt'] 	= (isset($css['cpt'])) 	? implode(' ', $css['cpt']) 	: '';
            $css['term'] 	= (isset($css['term'])) ? implode(' ', $css['term']) 	: '';
            $css['dur'] 	= (isset($css['dur'])) 	? implode(' ', $css['dur']) 	: '';


            // Hanging mdf/del commands (they open MDF SINGLE or DELETE script for specified line)

            if ($field!='bcasts' && PMS_FULL || pms('epg', 'mdf_line', $x)) {

                if ($field=='normal') {

                    $ajax = [
                        'url' => '_ajax/_aj_epg_elm_sw.php',
                        'data' => 'typ='.$listtyp.'&switch='.$x['ID'].
                            ((!TMPL && $listtyp=='scnr') ? '&elmid='.$x['ELEMENT']['ID'] : ''),
                        'fn' => 'reloader',
                        'btn_disable' => true
                    ];

                    $btn = '<a class="text-%s" href="%s" title="%s" %s><span class="glyphicon glyphicon-%s"></span></a>';

                    if (VIEW_TYP!=5) {

                        $btn_mdfdel =
                            sprintf($btn, 'muted', '#', $tx['LBL']['switch'], ajax_onclick($ajax),
                                (($x['IsActive']) ? 'ok' : 'ban').'-circle').
                            sprintf($btn, 'muted', 'epg_modify_single.php?'.$listtyp.'='.$id.'&id='.$x['ID'].'&ref=epg',
                                $tx['LBL']['modify'], null, 'cog');

                    } else { // 5-TRASH

                        $btn_mdfdel =
                            sprintf($btn, 'success', '#', $tx['LBL']['switch'], ajax_onclick($ajax), 'ok-circle').
                            sprintf($btn, 'danger', 'epg.php?typ='.$listtyp.'&delscope=target&del='.$x['ID'],
                                $tx['LBL']['delete'], null, 'remove-circle');
                    }

                    $btn_mdfdel = '<div class="floater-outside"><div class="floater-inside right">'.$btn_mdfdel.'</div></div>';
                }

            } else {

                $btn_mdfdel = '';
            }


            // TR opening tag - LINE START

            // $tr_css is used here - for main TR tag, but also later for DROP TR..
            // Otherwise, the linetype filter (bs_multiselect) wouldnot work properly in TREE view
            $tr_css = [];

            // css for linetype filter
            if ($field!='sheet') {

                if (VIEW_TYP==9) { // promo

                    $linetyp = $x['AttrA'];

                } else { // (0,1) list, tree

                    if (TYP=='epg' && $setz['epg_list_filter_teams']) {

                        // Teams filter. Mark non-program lines (i.e. mkt, prm, films, etc) with 0.
                        $linetyp = (!empty($x['PRG']['ProgID'])) ? rdr_cell('prgm', 'TeamID', $x['PRG']['ProgID']) : 0;

                    } else { // scnr

                        $linetyp = $x['NativeType'];
                    }
                }

                $tr_css[] = 'linetyp'.$linetyp;
            }

            // css for sleeplines
            if ($is_sleepline && VIEW_TYP!=5) {
                $tr_css[] = 'sleepline';
            }

            // css for spicer opening/closing clips in blocks (used in js switcher)
            if (in_array(VIEW_TYP, [8,9]) && $listtyp=='spice') {
                if ($x['NativeType']==5 && in_array($x['NativeID'], SPICER_CLIPZ)) {
                    $tr_css[] = 'spicer_clipz';
                }
            }

            $tr_css = (!empty($tr_css)) ? implode(' ', $tr_css) : '';

            if ($listtyp==TYP && $x['IsActive']) {
                // Attributes for *uptodate* (ajax) functionality.. (conditions must be the same as in _aj_epg.php)
                // ID will be used in JS; LANG attribute is used to pass TermEmit to JS
                $tr_ajax = ' id="tr'.$x['ID'].'" lang="'.@strtotime($x['TermEmit']).'" ';
            } else {
                $tr_ajax = '';
            }

            echo PHP_EOL.PHP_EOL.'<tr'.$tr_ajax.(($tr_css) ? ' class="'.$tr_css.'"': '').'>';


            // NUMERO cell

            if ($show_cln_numero && !$is_shadowline) {

                $css_numero = $css['cpt'];

                if (VIEW_TYP==8 && $field=='bcasts') {

                    $cnt_numero = $cnt_numero_block;
                    $cnt_numero_block++;

                    $css_numero .= ' mktblock';
                }

                $skip_numero = ($is_sleepline) ? true : false;

                if (VIEW_TYP==8 && $x['NativeType']==5) { // Clips
                    $skip_numero = true;
                }

                echo '<td width="1" id="numero" class="'.$css_numero.'">'.
                    ((!$skip_numero) ? ++$cnt_numero : '').'</td>';
            }


            // LEFT hanging controls cell

            if ($field=='normal' && VIEW_TYP!=5) {
                echo '<td class="ghost">'.sprintf($btn_squiz, $x['Queue']).'</td>';
            }


            // BCAST VERIFICATION controls cell

            if ($field=='bcasts' && PMS_BC) {

                echo '<td class="bc_ctrl text-center">';

                /* We have several conditions under which we don't output controls, but we show a label instead,
                 * to indicate the reason why controls are being skipped:
                 * - empty, i.e. non-selected items (no ID) - place-holders
                 * - episodes except the FIRST one (for films, i.e. film-serials)
                 * - reruns (for films)
                 */

                $is_skiper = false;

                // Skip PLACE-HOLDERS
                if (!$is_skiper && !$x['NativeID'] ||
                    ($x['NativeType']==12 && !$x['FILM']['FilmID']) ||
                    ($x['NativeType']==13 && !$x['FILM']['FilmParentID']) )
                {
                    echo '<span class="lbl_skiper">ID=0</span>';
                    $is_skiper = true;
                }

                // Skip SERIAL if it is NOT EPISODE #1
                if (!$is_skiper && $x['NativeType']==13 && @$x['FILM']['Ordinal']!=1) {
                    echo '<span class="lbl_skiper">&#8800;1.</span>';
                    $is_skiper = true;
                }

                // Skip RERUN
                if (!$is_skiper && isset($x['PRG']['MatType']) && $x['PRG']['MatType']==3) {
                    echo '<span class="lblprog3">'.$epg_mattyp_signs[3].'</span>';
                    $is_skiper = true;
                }


                if (!$is_skiper) {

                    $verif['schtyp'] = (($listtyp=='epg') ? 1 : 2);     // 1 - epg, 2 - scn
                    $verif['id'] = $x['ID'];                            // Item ID
                    $verif['term'] = strtotime($x['_REAL_TimeAir']);    // Item TimeAir

                    if (intval(@$_GET['verif_all'])) {

                        $verif['phase'] = intval(@$_GET['verif_phase']);  // 1 - ok (has run), 2 - fail (has not run)

                        // RECEIVER for VERIFY_ALL case.
                        // If VERIFY_ALL button was clicked, we call receiver function on each row
                        epg_bcast_receiver($verif);

                    } else {

                        $verif['phase'] = rdr_cell('epg_bcasts', 'Phase',
                            'SchType='.$verif['schtyp'].' AND SchLineID='.$verif['id']);
                    }

                    epg_bcast_ctrl_output($verif);
                }


                echo '</td>';
            }



            // TERM & DUR CELLS

            if (!$is_shadowline) { // shadowlines don't have term or duration


                // FIXED TERM cell

                $term_real_ts = strtotime($x['_REAL_TimeAir']);

                $term_html = $term_real = date('H:i:s', $term_real_ts);

                if ($listtyp=='epg' && $field=='normal' && $holder==true) {
                    $term_html = '* * *';
                }

                if ($show_cln_term_fix) {

                    echo '<td class="term '.$css['term'].$css_printhid.'">';

                    if ($field=='normal' && TERM_EDITABLE && !$is_sleepline) {

                        // For recorded scnrz use term_rel
                        $term_hmsedit = ($listtyp=='scnr' && $x['SCNR']['MatType']!=1) ? $term_rel : $term_html;

                        echo '<span class="hmsedit" onclick="hms_editable(this, \'term\')" data-hms="'.$term_hmsedit.'" '.
                            'data-termtyp="'.((@$x['OnHold']) ? 'hold' : (($x['TimeAir']) ? 'fixed' : 'zero')).'" '.
                            'data-epgtyp="'.TYP.'" data-id="'.$x['ID'].'" data-query="'.$_SERVER["QUERY_STRING"].'">'.
                            $term_html.'</span>';

                    } else {

                        echo $term_html;
                    }

                    echo '</td>';
                }


                // RELATIVE TERM cell

                if ($show_cln_term_rel) {
                    echo '<td class="term rel'.$css_printhid.'">'.$term_rel.'</td>';
                }


                // DURATION ERROR cell (outputs DIFFERENCE between expected and factual duration)

                if ($show_cln_err) {
                    echo '<td class="hidden-print dur '.((@$x['_Error'] && $prev_term_typ!='fixed') ? 'tddurerr' : 'epgempt').'">'.
                        '<span class="spacer">'.@$x['_Error'].'</span>'.
                        '</td>';
                }


                // DURATION WINNER cell

                echo '<td class="dur durreal '.$x['_Dur']['winner']['typ'].' '.$css['dur'].'">'.
                    '<span class="spacer">';

                if ($field=='normal'&& PMS_FULL && VIEW_TYP==0 && $x['NativeType']!=14
                    && $x['_Dur']['loser']['typ']   // loser must be set
                    && $x['_Dur']['winner']['typ']!='durcalc'  // cannot erase durcalc
                ) {

                    $href = 'epg.php?'.$_SERVER["QUERY_STRING"].'&durdel='.$x['ID'].'&durtyp='.$x['_Dur']['winner']['typ'];

                    echo '<a class="duredit" href="'.$href.'">'.$x['_Dur']['winner']['dur'].'</a>';

                } else {

                    echo $x['_Dur']['winner']['dur'];
                }

                echo '</span></td>';


                // DURATION LOSER cell

                if ($show_cln_dur_invalid) {
                    echo '<td class="hidden-print dur '.$x['_Dur']['loser']['typ'].'">'.
                        '<span class="spacer">'.$x['_Dur']['loser']['dur_html'].'</span></td>';
                }


                // MKT TIMEFRAMES CELL

                if ($show_cln_tframe) {

                    if ($listtyp=='spice' && $x['NativeType']!=5) { // block items, excluding clips

                        $tcalc = mkt_timecalc($term_real, $x['_Dur']['winner']['dur'], $x['IsGratis'], 'plan');

                        echo '<td class="tframe '.$tcalc['css'].'">'.hms2ms($tcalc['out']).'</td>';

                    } else {

                        echo '<td class="tframe'.(($listtyp!='spice') ? ' mkt' : '').'"></td>';
                    }
                }


                // CAPTION CELL start

                echo '<td class="cpt '.$css['cpt'].'">';


            } else { // SHADOWLINE


                switch ($x['NativeType']) {

                    case 8: // note

                        echo '<td class="cpt '.$css['cpt'].'" colspan="'.$col_sum.'">';

                        break;

                    case 10: // segmenter

                        if ($segment_start_term) {
                            $segment_start_term = date('H:i:s', strtotime($segment_start_term));
                            $segment_start_term = sum_durs([$segment_start_term, $segment_dur_sum]);
                        } else {
                            $segment_start_term = '-';
                        }

                        if ($show_cln_numero) {
                            echo '<td class="segmento" id="numero">&nbsp;</td>';
                        }

                        if ($show_cln_term_fix) {
                            echo '<td class="segmento'.$css_printhid.' text-center">'.$segment_start_term.'</td>';
                        }

                        if ($show_cln_term_rel) {
                            echo '<td class="segmento'.$css_printhid.'">&nbsp;</td>';
                        }

                        if ($show_cln_err) {
                            echo '<td class="segmento hidden-print">&nbsp;</td>';
                        }

                        echo '<td class="segmento durreal text-center">'.hms2ms($segment_dur_sum).'</td>';

                        if ($show_cln_dur_invalid) {
                            echo '<td class="segmento hidden-print">&nbsp;</td>';
                        }

                        echo '<td class="cpt segmento">';

                        // If this segment is finished, then obviously what follows next is another segment start,
                        // and we also want to reset the SEGMENT duration sum variable, to start new counting..
                        $is_segment_start = true;
                        $segment_dur_sum = '00:00:00';
                }

            }



            // SHEET drop-down button
            if ($field=='normal' && VIEW_TYP==1 && @$x['_CNT_Frag']) {

                echo
                    '<button class="btn btn-xs pull-right drop hidden-print" '.
                    'onclick="sheet_switch('.$x['ID'].'); return false;">'.
                    '<span class="cnt">'.$x['_CNT_Frag'].'</span>'.
                    '<span class="caret'.((VIEW_TOGGLE_ALLCOLLAPSE) ? ' caret-reversed' : '').'" id="drp'.$x['ID'].
                    '" lang="'.((VIEW_TOGGLE_ALLCOLLAPSE) ? 'down' : 'up').'"></span>'.
                    '</button>';
            }


            // SPICER: ITEM ID LABEL
            if (in_array(VIEW_TYP, [8,9]) && $listtyp=='spice') {
                $attrz = (PMS_SPICER) ? drg_attrz('spicer', $x['ID']) : '';
                echo '<span class="lbl_spcitem drg_target" '.$attrz.'>'.sprintf('%04s', $x['NativeID']).'</span>';
            }


            // LINETYPE LABEL
            if (in_array(VIEW_TYP, [0,1,3,5,9,10]) && $listtyp!='spice') {
                epg_linetyp_lbl($x, $listtyp);
            }

            // STORY: PHASE
            if ($x['NativeType']==2) {

                echo phase_sign([
                    'phase' => $x['STRY']['Phase'],
                    'lift' => (PMS_MDF_EDITDIVABLE),
                    'story_id' => $x['STRY']['ID']
                ]);
            }

            // NOTE LABEL (indicates note TYPE by text and color)
            if ($field=='normal' && $x['NativeType']==8) {
                echo '<span class="lblnote'.$x['NOTE']['NoteType'].' glyphicon glyphicon-exclamation-sign"></span>';
            }

            // PRG/PRG-LINK IsReady
            if (($x['NativeType']==1 && $x['PRG']['IsReady']) || ($x['NativeType']==14 && $x['LINK']['PRG']['IsReady'])) {
                echo '<span class="glyphicon glyphicon-ok ready"></span>';
            }


            ///////////////////////////// CAPTION TEXT ////////////////////////////////

            echo (isset($x['_CPT_href'])) ? '<a class="cpt lbl_left" href="'.$x['_CPT_href'].'">'.$x['_Caption'].'</a>' :
                $x['_Caption'];

            ///////////////////////////////////////////////////////////////////////////


            // BCASTS: for spicer WITHIN SCNR, we add SCNR caption and term to indicate that it is *within* program
            if ($field=='bcasts' && $listtyp=='scnr' && in_array($x['NativeType'], [3,4])) {

                echo epgblock_cpt_scnr([
                    'ID' => $x['ELEMENT']['ID'],
                    'NativeType' => $x['ELEMENT']['NativeType'],
                    'IsActive' => $x['ELEMENT']['IsActive'],
                    'TermEmit' => $zero_term
                ]);
            }


            // STORY: redactor name
            if (in_array(VIEW_TYP, [0,1,3]) && $x['NativeType']==2) {

                // If journo name is set in story CREW block, then show that name as the AUTHOR name
                $author_uid = crw_reader($x['NativeID'], $x['NativeType'], 5, 'normal_single');

                if (!$author_uid) {
                    $author_uid = $x['STRY']['UID'];
                }

                echo '<span class="stry_author lbl_left">'.uid2name($author_uid, ['n1_typ'=>'init']).'</span>';
            }


            // STORY: speakerz
            if (VIEW_TYP==0 && $x['NativeType']==2 && $x['ATOMZ_SPKRZ'] && count($speakerz)!=1) {

                echo
                    '<span class="schlbl speaker stry lbl_right">'.
                    '<span class="glyphicon glyphicon-user"></span>'.$x['ATOMZ_SPKRZ'].
                    '</span>';
            }


            if ($x['NativeType']==20) { // ATOM

                // ATOM CAM-TIPS

                if ($x['TypeX']==1 && SHOW_TIPS_CAM) {

                    epg_tip_output(['schtyp' => 'stry', 'schline' => $x['ID'], 'tiptyp' => 2, 'editable' => MDF_TIPS_CAM]);
                }

                // ATOM SPEAKER

                if (in_array(VIEW_TYP, [1,3]) && $x['TypeX']==1) {  // (view: tree, studio; atom type: reading)

                    $x['SpeakerX'] = rdr_cell('stry_atoms_speaker', 'SpeakerX', $x['ID']);

                    if (VIEW_TYP==1) { // (view: tree)

                        if (empty($speakerz[$x['SpeakerX']]['uid'])) {
                            $spkr = $x['SpeakerX'];
                        } else {
                            $spkr = mb_strtoupper($speakerz[$x['SpeakerX']]['name_short']);
                        }

                        echo
                            '<span class="schlbl speaker">'.
                            '<span class="glyphicon glyphicon-user"></span>'.$spkr.
                            '</span>';
                    }
                }

                // ATOM VO-TIPS (The order is intentional - after speaker.)

                if ($x['TypeX']==1 && SHOW_TIPS_VO) {

                    epg_tip_output(['schtyp' => 'stry', 'schline' => $x['ID'], 'tiptyp' => 3, 'editable' => MDF_TIPS_VO]);
                }

                // ATOM LAST PHRASE

                if (VIEW_TYP==1) {  // (view: tree)

                    $x['Texter'] = rdr_cell('stry_atoms_text', 'Texter', $x['ID']);

                    $x['Texter'] = atom_last_phrase($cfg[SCTN]['atom_last_phrase_width'], $x['Texter']);

                    if ($x['Texter']) {

                        echo '<span class="schlbl texter">'.$x['Texter'].'</span>';
                    }
                }
            }


            // Labels specific for PROGRAM and PROGRAM-like elements (FILM and FILM-SERIAL)
            if (in_array($x['NativeType'], [1,12,13,14])) {

                // LIVE MATERIAL-TYPE LABEL
                if ($x['PRG']['MatType']==1) {
                    echo ' <span class="lbl lblprog1">'.$epg_mattyp_signs[1].'</span>';
                }

                // RERUN MATERIAL-TYPE LABEL
                if ($x['PRG']['MatType']==3) {
                    echo '<span class="lbl lblprog3">'.$epg_mattyp_signs[3].'</span>';
                }

                // PARENTAL LABEL
                if (@$x['Parental']) {
                    echo '<span class="lbl lblparental">'.$x['Parental'].'</span>';
                }

                // TIE LABEL
                if (@$x['TIE']['ID']) {
                    echo '<a class="lbl_right lbltie_'.$x['TIE']['Type'].' hidden-print" href="epg.php?typ=epg&id='.
                        $x['TIE']['EpgID'].'#tr'.$x['TIE']['ID'].'" title="'.$x['TIE']['TermEmit'].'">'.
                        '<span class="glyphicon glyphicon-link"></span></a>';
                }

                // REC4RERUN LABEL
                if (@$x['Record']) {
                    echo '<span class="lbl_right lblrecord hidden-print">'.$epg_mattyp_signs[3].'</span>';
                }

                // SHOW DUR ADDENDS (Split dur into RECORD dur and BLOCKS dur)
                if (in_array($x['NativeType'], [12,13]) || ($x['NativeType']==1 && $x['PRG']['MatType']!=1)) {

                    if ($x['NativeType']==1) { // PRG

                        $dur_record = $x['MOS']['Duration'];
                        $dur_blocks = $x['PRG']['DurCalc'];

                        if ($dur_record) {
                            $dur_blocks = substract_durs([$dur_blocks, $dur_record]);
                        }

                    } else { // FILM/SERIAL

                        if ($x['FILM']['FilmID']) {

                            $dur_record = $x['FILM']['Duration'];
                            $dur_blocks = $x['PRG']['DurEmit'];

                        } else {

                            $dur_record = $dur_blocks = '';
                        }
                    }

                    if ($dur_record && $dur_blocks) {

                        echo '<small class="lbl_right">('.$dur_record.' + '.$dur_blocks.')</small>';
                    }
                }

            }


            // PROG TEAM

            if ($x['NativeType']==1 && $x['AttrA']) {

                $team_id = rdr_cell('prgm', 'TeamID', $x['PRG']['ProgID']);    // Get program's team ID

                if ($team_id<99) { // pseudo-team

                    echo '<span class="lbl text-uppercase"> '.rdr_cell('prgm_teams', 'Caption', $x['AttrA']).'</span>';
                }
            }


            // TIPS

            if ($listtyp!='spice' && SHOW_TIPS_NOTE && in_array($x['NativeType'], [2,20,1,12,13,14,3,4,5,7,9])) {

                if (TYP=='epg') {
                    $schtyp = 'epg';
                } else {
                    $schtyp = ($x['NativeType']==20) ? 'stry' : 'scnr';
                }

                epg_tip_output(['schtyp' => $schtyp, 'schline' => $x['ID'], 'tiptyp' => 1, 'editable' => MDF_TIPS_NOTE]);
            }


            // SKOP COVERZ

            if (TYP=='epg' && VIEW_TYP==0 && in_array($x['NativeType'], [1,12,13,14]) && $setz['epg_show_skop_cvr']) {

                $coverz = coverz_output($x['ID'], 1, ['txt_only' => true, 'output' => false, 'cvr_typ' => 10]);

                if ($coverz) {
                    foreach ($coverz as $v) {
                        echo '<span class="glyphicon glyphicon-exclamation-sign lblelnote pull-right hidden-print" '.
                            'data-toggle="tooltip" data-placement="left" title="'.$v['TypeXTXT'].': '.$v['Texter'].'"></span>';
                    }
                }

            }


            // SPICER CTRLZ

            if (in_array(VIEW_TYP, [8,9]) && PMS_SPICER) {

                $css_spc = 'ctrlbtn pull-right hidden-print ';

                if ($listtyp=='spice') { // item

                    $href_rfr = '&rfrtyp='.$opt['block_parent_typ'].'&rfrid='.$opt['block_parent_id'];

                    $ajax = [
                        'url' => '_ajax/_aj_epg_spc_sw.php',
                        'data' => 'switch='.$x['ID'].$href_rfr,
                        'fn' => 'reloader',
                        'btn_disable' => true
                    ];

                    echo '<a class="'.$css_spc.'switcher text-muted" href="#" title="'.$tx['LBL']['switch'].'"'.
                        ajax_onclick($ajax).'>'.
                        '<span class="glyphicon glyphicon-'.(($x['IsActive']) ? 'ok' : 'ban').'-circle"></span></a>';

                } else { // block ($listtyp==[epg, scnr])

                    $href_rfr =
                        '&rfrtyp='.$listtyp.
                        '&rfrid='.$x['ID'].
                        '&rfrctg='.$x['AttrA']. // actually not used for MKT
                        '&rfrelm='.(($listtyp=='scnr') ? $x['ELEMENT']['ID'] : $x['ID']).
                        '&rfrepg='.(($listtyp=='scnr') ? $x['ELEMENT']['EpgID'] : $x['EpgID']);

                    $href = 'spice_modify.php?sct='.((VIEW_TYP==8) ? 'mkt' : 'prm').'&typ=block'.$href_rfr;

                    if ($x['NativeID']) { // MDF - Block is selected

                        $href .= '&id='.$x['NativeID'];

                        echo '<a class="'.$css_spc.'mdfblock lbl_right" href="'.$href.'">'.
                            '<span class="glyphicon glyphicon-cog"></span></a>';

                        if ($x['_CNT_Frag']) { // SHOW DUR ADDENDS (Split dur into ITEMS dur and CLIPS dur)

                            $dur_clips = epg_durcalc('block', $x['NativeID'], ['block_clips_only' => true]);

                            if ($dur_clips && $dur_clips!=$x['BLC']['DurCalc']) {

                                $dur_items = substract_durs([$x['BLC']['DurCalc'], $dur_clips]);

                                echo '<small class="lbl_right">('.hms2ms($dur_items).' + '.hms2ms($dur_clips).')</small>';
                            }
                        }

                    } else { // NEW - Empty

                        $t_rfr = ($listtyp=='epg') ? $x['TermEmit'] : $x['_REAL_TimeAir'];

                        $href .= '&t='.strtotime($t_rfr); // We'll use it for caption

                        echo '<a class="'.$css_spc.'newblock text-success" href="'.$href.'">'.
                            '<span class="glyphicon glyphicon-plus-sign"></span></a>';
                    }
                }
            }


            echo '</td>';
            // CAPTION CELL finish




            // RIGHT hanging controls cell

            if ($field=='normal') {
                echo '<td class="ghost">'.$btn_mdfdel.'</td>';
            }

            // TR closing tag - LINE FINISH
            echo '</tr>';




            /* **************************** SHEET start *****************************/


            if ($field=='normal'
                && VIEW_TYP==1 // TREE view
                && in_array($x['NativeType'], [1,2,3,4,12,13]) // linetypes which can have sheet: prog, story, mkt, prm, film/serial
                && $x['_CNT_Frag']) {

                switch ($x['NativeType']) {

                    case 1: 	$sht_typ = 'scnr';	break;
                    case 2: 	$sht_typ = 'story'; 	break;

                    case 3:
                    case 4: 	$sht_typ = 'spice';     break;

                    case 12:
                    case 13:	$sht_typ = 'scnr'; 	break;
                }

                $sht_id = !in_array($x['NativeType'], [12,13]) ? $x['NativeID'] : $x['SCNRID'];

                echo
                    PHP_EOL.PHP_EOL.
                    '<tr id="sht'.$x['ID'].'" class="drop '.$tr_css.'">'. // $tr_css here at DROP TR is the same as for main TR tag
                    '<td class="ghost"></td>'.
                    '<td colspan="'.$col_sum.'" class="tblsheet">'.PHP_EOL.
                    '<table class="schtable tblsheet '.$css['cpt'].'">';

                epg_dtl_html($sht_id, $sht_typ, 'sheet',
                    [
                        'zero_term' => $x['_REAL_TimeAir'],
                        'parent_dur' => $x['_Dur']['winner']['dur'],
                        'epgfilmid' => @$x['FILM']['ID']
                    ]
                );

                echo
                    '</table>'.PHP_EOL.
                    '</td>'.
                    '<td class="ghost"></td>'.
                    '</tr>'.PHP_EOL;
            }


            if ($field=='normal_studio'
                && $x['NativeType']==2 && $x['_CNT_Frag']) {

                echo
                    '<tr class="drop '.(($is_sleepline) ? 'sleepline ' : '').'" id="sht'.$x['ID'].'">'.
                    '<td colspan="'.$col_sum.'" class="tblsheet studio">'.PHP_EOL.
                    '<table class="schtable tblsheet '.$css['cpt'].'">';

                epg_dtl_html($x['NativeID'], 'story', 'sheet_studio', ['zero_term' => $x['_REAL_TimeAir']]);

                echo
                    '</table>'.PHP_EOL.
                    '</td>'.
                    '</tr>'.PHP_EOL;
            }


            // SPICER
            if ($field=='bcasts'
                && $listtyp!='spice'
                && in_array(VIEW_TYP, [8,9])
                && $x['_CNT_Frag']) {

                echo
                    PHP_EOL.PHP_EOL.
                    '<tr id="sht'.$x['ID'].'" class="drop '.$tr_css.'">'. // $tr_css here at DROP TR is the same as for main TR tag
                    '<td colspan="'.$col_sum.'" class="tblsheet">'.PHP_EOL.
                    '<table class="schtable tblsheet '.$css['cpt'].'">';

                epg_dtl_html($x['NativeID'], 'spice', 'sheet',
                    [
                        'zero_term' => $x['_REAL_TimeAir'],
                        'block_parent_typ' => $listtyp,
                        'block_parent_id' => (($listtyp=='scnr') ? $x['ELEMENT']['ID'] : $x['EpgID'])
                    ]
                );

                echo
                    '</table>'.PHP_EOL.
                    '</td>'.
                    '</tr>'.PHP_EOL;
            }


            if ($field=='sheet_studio') {

                if ($x['TypeX']==1) { // (atom type: reading)

                    $css_wrap = 'spkr'.$speakerz[$x['SpeakerX']]['i'];

                    $atom_data = speaker_box($speakerz, 'speaker_x', ['sel_x' => $x['SpeakerX'], 'atomid' => $x['ID']]);

                } else {

                    $css_wrap = 'prg';

                    $atom_data = '';
                }

                if (PMS_MDF_EDITDIVABLE) {

                    $ajax = [
                        'url' => '_ajax/_aj_studio_txt_pms.php',
                        'data' => 'atomid='.$x['ID'],
                        'fn' => 'studio_txt_pms_ajax_success',
                        'trigger' => 'ondblclick'
                    ];

                    $editdivable = ajax_onclick($ajax);

                } else {

                    $editdivable = '';
                }

                if ($x['TypeX']==1 || VIEW_TOGGLE_ALLCOLLAPSE) {

                    $x['Texter'] = rdr_cell('stry_atoms_text', 'Texter', $x['ID']);
                    $x['Texter'] = nl2br($x['Texter']);

                    echo
                        '<tr class="drop '.(($is_sleepline) ? 'sleepline ' : '').'" id="sht'.$x['ID'].'">'.
                            '<td colspan="'.$col_sum.'" class="tblsheet studio">'.PHP_EOL.
                                '<div class="row atom_wraper '.$css_wrap.'">'.
                                    '<div class="col-xs-3 studio">'.
                                        $atom_data.
                                        '<div class="new_dur" lang="'.$x['_Dur']['winner']['dur'].'"></div>'.
                                    '</div>'.
                                    '<div class="col-xs-9 atom_texter">
                                            <div class="atom_txt"'.$editdivable.'>'.$x['Texter'].'</div>
                                    </div>'.
                                '</div>'.
                            '</td>'.
                        '</tr>'.PHP_EOL;
                }
            }


            if ($field=='sheet'
                && VIEW_TYP==1 && $x['NativeType']==20) {

                $x['Texter'] = rdr_cell('stry_atoms_text', 'Texter', $x['ID']);
                $x['Texter'] = nl2br($x['Texter']);

                echo
                    '<tr class="atomtxt_drop" id="atomtxt'.$x['ID'].'">'.
                        '<td colspan="'.($col_sum-1).'" class="tblsheet"></td>'.PHP_EOL.
                        '<td class="tblsheet">'.
                            '<div class="atom_texter"><div class="atom_txt">'.$x['Texter'].'</div></div>'.
                        '</td>'.PHP_EOL.
                    '</tr>'.PHP_EOL;
            }


            /* SCNR TREE: CVRz collapse */

            if (TYP=='scnr' && VIEW_TYP==1) {

                if ($field=='sheet' && $x['NativeType']==20) { // atom

                    $x['_CNT_CVR'] = cvr_cnt($x['ID'], 3);

                    if ($x['_CNT_CVR']['sum']) {
                        coverz_output($x['ID'], 3, ['txt_only' => true, 'cln_offset' => ($col_sum-1)]);
                    }
                }

                if ($field=='normal' && $x['NativeType']==2) { // story

                    $x['_CNT_CVR'] = cvr_cnt($x['NativeID'], 2);

                    if ($x['_CNT_CVR']['story']) {
                        coverz_output($x['NativeID'], 2,
                            ['txt_only' => true, 'cln_offset' => ($col_sum), 'cnt' => $x['_CNT_CVR']]);
                    }
                }
            }


            /* **************************** SHEET finish *****************************/

            // vbdo: SUMMARY:durz (temporary solution)
            if ($field=='bcasts' && !$is_shadowline && !$is_sleepline) {
                @$_SESSION['durz'][] = @$x['_Dur']['winner']['dur_hms'];
            }

        }

        /* **************************************************************
         *                      LINE OUTPUT: finish
         * **************************************************************
         */


        // Save term and duration of finished line, in order to use it for term calculation in following line
        if (!$is_shadowline && !$is_sleepline) { // shadowlines and sleeplines don't have terms and durs

            $prev_term 	= @$x['_REAL_TimeAir'];
            $prev_dur 	= @$x['_Dur']['winner']['dur_hms'];

            $prev_term_typ = (@$x['TimeAir']) ? 'fixed' : 'rel';

            $prev_skip = false;

        } else {

            if ($is_sleepline) {

                // $prev_term 	= @$x['_REAL_TimeAir'];
                // $prev_dur = '00:00:00';

                // For inactive i.e. sleeplines, we skip redefining $prev_term and $prev_dur, thus letting the values
                // from previous loop iteration to fall through..
                // As to $term_rel, we have to calculate and set it to value from previous loop iteration,
                // because it changes during the loop iteration..

                $term_rel = terms_diff($prev_term, $zero_term, 'hms');

            } else { // $is_shadowline

            }

            $prev_skip = true;
        }

    }
    // THE BIG LOOP finish




    // Check whether we should proceed to list footer output

    // For TRASH view, end.
    if (VIEW_TYP==5) return null;

    // For EPG, check whether there was any lines. If not, end.
    if ($listtyp=='epg' && !isset($x['NativeType'])) return null;

    // If this is case of BCAST search for MKT/PRM within progs (SCNR list, BCAST field), return.
    if ($listtyp=='scnr' && $field=='bcasts') return null;




    // FOOTER


    // FINITO-LINE

    // Only for scnr, and only if list isn't empty (check whether there was any lines)
    // Also: later I turned it off for sheets (in epg)
    if ($listtyp=='scnr' && isset($x['NativeType']) && $field!='sheet') {

        // Calculate sum

        $x['_Error'] = '';

        if (!$terms_relative) { // fixed terms

            $term_finito = date('H:i:s', strtotime(add_dur2term(@$prev_term, @$prev_dur)));
            // Calculate FINITO TERM by adding last line duration to last line term

            if ($opt['parent_dur']!=$list_dur_sum) {
                $x['_Error'] = terms_diff($opt['parent_dur'], $list_dur_sum);
            }

        } else { // relative terms

            $term_finito = date('H:i:s', strtotime(add_dur2term(@$zero_term, @$list_dur_sum)));
            // Calculate FINITO TERM by adding list duration to zero term, i.e. list start term
        }

        // Display the line

        echo '<tr class="hidden-print finito">';

        if ($field=='normal') {
            echo '<td class="ghost">'.sprintf($btn_squiz, (@$x['Queue']+1)).'</td>';
        }

        if ($show_cln_numero) {
            echo '<td class="finito" id="numero">&nbsp;</td>';
        }

        if ($show_cln_term_rel || $show_cln_term_fix) {
            echo '<td class="term" style="text-align:center"'.
                (($show_cln_term_rel && $show_cln_term_fix) ? ' colspan="2"' : '').'>'.$term_finito.'</td>';
        }

        if ($show_cln_err) {
            echo '<td class="dur '.((@$x['_Error']) ? 'tddurerr' : '').'"><span class="spacer">'.@$x['_Error'].'</span></td>';
        }

        echo '<td class="dur durcalc" '.(($show_cln_dur_invalid) ? 'colspan="2"' : '').
            ' style="font-weight:bold;">'.hms2ms($list_dur_sum, false).'</td>';

        echo '<td class="cpt"><span class="lbl_linetyp">&sum;</td>';

        if ($field=='normal') {
            echo '<td class="ghost"></td>';
        }

        echo '</tr>';
    }


    // Line with BOTTOM END SQUIZER

    if (
        ($listtyp=='epg' && $field=='normal') ||
        ($listtyp=='scnr' && $field!='normal_studio' && !isset($x['NativeType']))
    ) {

        $term_finito = ($listtyp=='epg') ? date('H:i:s', strtotime(add_dur2term(@$prev_term, @$prev_dur))) : '&nbsp;';

        echo '<tr>'.
            '<td class="ghost">'.sprintf($btn_squiz, (@$x['Queue']+1)).'</td>'.
            '<td colspan="'.$col_sum.'" class="term_finito">'.$term_finito.'</td>'.
            '<td class="ghost"></td>'.
            '</tr>';
    }


    // BCASTS: Row with VERIFY_ALL buttons

    if ($listtyp=='epg' && $field=='bcasts' && PMS_BC) {

        echo '<tr><td class="bc_ctrl text-center">';

        epg_bcast_ctrl_output(null);

        echo '</td></tr>';
    }


    // vbdo: SUMMARY:durz (temporary solution)
    if ($listtyp=='epg' && $field=='bcasts') {
        echo '<tr class="summary"><td></td>'.((PMS_BC) ? '<td></td>' : '').'<td>'.@sum_durs(@$_SESSION['durz']).'</td></tr>';
        $_SESSION['durz'] = [];
    }




    return $col_sum;
    // Return value is important only when the function is called from epg.php. It gives back the COLUMN COUNT
    // to be used for column spanning in SQUIZROW clone.
}





















/**
 * Prints epg in WEB view
 *
 * @param int $id EPG ID
 * @param string $rtyp Return type: (html, xml)
 *
 * @return void
 */
function epg_web_html($id, $rtyp='html') {

    global $cfg;

    $pms = pms('epg', 'mdf_web');


    if ($rtyp=='xml') {

        $xml = new DOMDocument('1.0', 'utf-8');

        $epg = epg_reader($id);
        $filter = array_flip(['ID', 'DateAir', 'ChannelID', 'IsReady']);
        $epg = array_intersect_key($epg, $filter);

        $xml_epg = xml_element_add($xml, 'EPG', ['attrz' => $epg]);
    }


    $rerun_sign = txarr('arrays', 'epg_mattyp_signs', 3);


    $sql = 'SELECT ID FROM epg_elements WHERE EpgID='.$id.
        ' AND TermEmit AND IsActive AND NativeType IN (1,12,13,14)'. // We need: progs(1), films(12,13), links(14)
        ' ORDER BY TermEmit ASC, Queue ASC';
    $result = qry($sql);

    while (@list($xid) = mysqli_fetch_row($result)) {

        $x = element_reader($xid);

        if (!$x['ID']) { // To avoid errors on slow connection when an epg element gets deleted in the middle of the query loop
            continue;
        }

        // Not only element which has OnHold set, but all following elements up until the first one with fixed TermAir

        if ($x['OnHold'] || (!empty($holder) && !$x['TimeAir'])) {
            $holder = true;
        } else {
            $holder = false;
        }

        if ($holder==true) {
            $term_seconds = $term_minutes = '* * *';
        } else {
            $t = strtotime($x['TermEmit']);
            $term_seconds = date('H:i:s', $t);
            $term_minutes = date('H:i', $t);
        }


        if ($x['NativeType']==1 && $x['PRG']['ProgID']) {

            // For PROGRAMS: If a program has ProgID, then we check whether this program should be hidden on the web
            if ($x['PRG']['SETZ']['WebHide']) continue;
        }


        if ($rtyp=='html') {


            list($css['cpt']) = epg_linetyp($x['NativeType']);

            switch ($x['NativeType']) {

                case 1: // prg

                    $x['_Caption'] = '<span class="cpt">'.$x['PRG']['ProgCPT'].'</span>';

                    if ($x['PRG']['Caption']) {

                        $x['_Caption'] .= ' - '.$x['PRG']['Caption'];

                    } elseif (!empty($x['PRG']['SETZ']['DscTitle'])) {

                        $x['_Caption'] .= ' - '.$x['PRG']['SETZ']['DscTitle'];
                    }

                    break;

                case 5: // clip
                    $x['_Caption'] = '<span class="cpt">'.$x['CLP']['Caption'].'</span>';
                    break;

                case 12: // film
                case 13: // film-serial
                    $x['_Caption'] = film_caption($x['FILM'], ['typ' => $x['NativeType'], 'dsc' => true]);
                    break;

                case 14: // link
                    $x['_Caption'] = epg_link_cpt($x);
                    break;
            }

            echo '<tr>';


            $ajax = [
                'url' => '_ajax/_aj_epg_web_sw.php',
                'data' => 'typ=live&switch='.$x['ID'],
                'fn' => 'reloader'
            ];

            echo '<td class="web weblive'.(($x['WebLIVE']) ? 1 : 0).'" '.(($pms) ? ajax_onclick($ajax) : '').'>&nbsp;'.
                (($x['WebLIVE']) ? '<span class="glyphicon glyphicon-play-circle visible-print-inline"></span>' : '').
                '</td>';


            echo '<td class="term seconds">'.$term_seconds.'</td>';
            echo '<td class="term minutes">'.$term_minutes.'</td>';

            echo '<td class="cpt '.$css['cpt'].'">';


            $ajax = [
                'url' => '_ajax/_aj_epg_web_sw.php',
                'data' => 'typ=vod&switch='.$x['ID'],
                'fn' => 'reloader'
            ];

            echo '<a href="#" '.(($pms) ? ajax_onclick($ajax) : 'onclick="return false;"').'>'.
                '<span class="glyphicon glyphicon-download webvod'.(($x['WebVOD']) ? '' : ' off').'"></span></a>';


            if (in_array($x['NativeType'], [1,12,13,14])) {

                $x['_CPT_href'] = 'epg.php?view=0&typ=scnr&id='.(($x['NativeType']!=14) ? $x['ID'] : $x['NativeID']);

                echo '<a class="cpt" href="'.$x['_CPT_href'].'">'.$x['_Caption'].'</a>';

                // Print material-type label only for material-type 3, i.e. RERUN
                if ($x['PRG']['MatType']==3) {
                    echo '<span class="lbl lblprog'.$x['PRG']['MatType'].'">'.$rerun_sign.'</span>';
                }

                if (@$x['Parental']) {
                    echo '<span class="lbl lblparental">'.$x['Parental'].'</span>';
                }

            } else { // clip(5)

                echo $x['_Caption'];
            }

            echo '</td>';

            echo '</tr>';


        } else { // xml


            $z = [];

            $z['TermCpt'] = $term_minutes;
            $z['TermEmit'] = $x['TermEmit'];
            $z['ItemType'] = $x['NativeType'];

            switch ($x['NativeType']) {

                case 1: // prg
                    $z['Caption'] = $x['PRG']['ProgCPT'];
                    $z['Theme'] = (($x['PRG']['Caption']) ? $x['PRG']['Caption'] : @$x['PRG']['SETZ']['DscTitle']);
                    $z['ItemID'] = $x['PRG']['ProgID'];
                    break;

                case 5: // clip
                    $z['Caption'] = $x['CLP']['Caption'];
                    break;

                case 12: // film
                case 13: // film-serial
                    $z['Caption'] = @$x['FILM']['Title'].
                        ((@$x['FILM']['EpiTitle']) ? ': '.$x['FILM']['EpiTitle'] : '');
                    $z['Theme'] = @$x['FILM']['DscTitle'];
                    break;

                case 14: // link
                    $z['Caption'] = $x['LINK']['PRG']['ProgCPT'];
                    $z['Theme'] = $x['LINK']['PRG']['Caption'];
                    $z['ItemID'] = $x['LINK']['PRG']['ProgID'];
                    break;
            }

            $z['WebLIVE'] = @intval($x['WebLIVE']);
            $z['WebVOD'] = @intval($x['WebVOD']);

            if (empty($z['Theme'])) {
                unset($z['Theme']);
            }

            if ($x['NativeType']==13 && @$x['FILM']['Ordinal']) {
                $z['Episode'] = $x['FILM']['Ordinal'].(($x['FILM']['EpisodeCount']) ? '/'.$x['FILM']['EpisodeCount'] : '');
            }

            if (in_array($x['NativeType'], [1,12,13,14])) {

                if ($x['PRG']['MatType']==3) {
                    $z['R'] = 1;
                }

                if (!empty($x['Parental'])) {
                    $z['Parental'] = $x['Parental'];
                }
            }

            $xml_item = xml_element_add($xml, 'epgItem', ['tagz' => $z]);

            $xml_epg->appendChild($xml_item);
        }

    }


    if ($rtyp=='xml') {

        $xml->appendChild($xml_epg);

        $xml->formatOutput = true;


        $fileold = $cfg[SCTN]['epgxml_path'].sprintf('%u-%s.xml', $epg['ChannelID'], $epg['DateAir']);

        $filenew = $fileold.'1';

        $xml->save($filenew);

        file_old_new($fileold, $filenew);

        //print $xml->saveXML();  // uncomment when troubleshooting
    }
}



















/**
 * Prints zero-level cvrz
 *
 * @param int $owner_id Owner ID
 * @param int $owner_typ Owner TYPE: 1-scnr, 4-epg
 *
 * @return void
 */
function epg_cvr_html_zerolevel($owner_id, $owner_typ) {

    coverz_output($owner_id, $owner_typ);
}





/**
 * Prints epg in CVR view
 *
 * @param int $id ID of the master object, for which we want list, and which can be of three specified below list types.
 * @param string $listtyp List type: (epg, scnr, story).
 *
 * @return void
 */
function epg_cvr_html($id, $listtyp) {

    switch ($listtyp) {

        case 'epg':
            $typ = 1;
            $tbl = 'epg_elements';
            $cln = 'EpgID';
            $linefilter = [1,12,13,14,5];
            $rerun_sign = txarr('arrays', 'epg_mattyp_signs', 3);
            break;

        case 'scnr':
            $typ = 2;
            $tbl = 'epg_scnr_fragments';
            $cln = 'ScnrID';
            $linefilter = [2];
            break;

        case 'story':
            $typ = 3;
            $tbl = 'stry_atoms';
            $cln = 'StoryID';
            $atom_typz = txarr('arrays', 'atom_jargons.'.ATOM_JARGON);
            break;
    }


    $sql = 'SELECT ID FROM '.$tbl.' WHERE '.$cln.'='.$id;

    if (in_array($listtyp, ['epg', 'scnr'])) {
        $sql .= ' AND IsActive AND NativeType IN ('.implode(',', $linefilter).')';
    }

    $sql .= ' ORDER BY Queue';

    $result = qry($sql);

    while (@list($xid) = mysqli_fetch_row($result)) {

        switch ($listtyp) {
            case 'epg':			$x = element_reader($xid);   break;
            case 'scnr':	    $x = fragment_reader($xid);  break;
            case 'story':		$x = epg_story_reader($xid); break;
        }

        if (!$x) {
            continue; // Element/fragment doesnot exist. Maybe it was deleted between two sql queries.
        }

        switch ($x['NativeType']) {


            case 2: // stry

                $x['_CNT_CVR'] = cvr_cnt($x['NativeID'], 2);

                if (!$x['_CNT_CVR']['sum']) {
                    //continue 2; // *switch* is also considered a loop, so we have to indicate *2* levels of loop skip
                    // Uncomment if we decide that we do not want to show stories with zero cvrz..
                }

                $x['_Caption'] = phase_sign(['phase' => $x['STRY']['Phase']]).
                    '<span class="cpt">'.$x['STRY']['Caption'].'</span>';

                $x['_CPT_href'] = '/desk/stry_details.php?id='.$x['NativeID'];

                break;


            case 20: // atom

                $x['_Caption'] = '<span class="label numero">'.($x['Queue']+1).'</span>'.
                    '<span class="cpt">'.($atom_typz[$x['TypeX']]).'</span>';

                $x['_CNT_CVR'] = cvr_cnt($x['ID'], 3);

                if (!$x['_CNT_CVR']['sum']) {
                    //continue 2; // *switch* is also considered a loop, so we have to indicate *2* levels of loop skip
                }

                break;


            case 1: // prg
                $x['_Caption'] = '<span class="cpt">'.$x['PRG']['ProgCPT'].'</span>'.
                    (($x['PRG']['Caption']) ? ' - '.$x['PRG']['Caption'] : '');
                break;

            case 14: // link
                $x['_Caption'] = epg_link_cpt($x);
                break;

            case 5: // clip
                $x['_Caption'] = '<span class="cpt">'.$x['CLP']['Caption'].'</span>';
                break;

            case 12: // film
            case 13: // film-serial
                $x['_Caption'] = film_caption($x['FILM'], ['typ' => $x['NativeType']]);
                break;
        }


        if (in_array($x['NativeType'], [1,12,13,14])) {
            $x['_CPT_href'] = 'epg.php?view=2&typ=scnr&id='.(($x['NativeType']!=14) ? $x['ID'] : $x['NativeID']);
        }


        // HEADERS

        echo '<div class="epg_cvr header typ'.$typ.' col-xs-12">';

        if ($x['NativeType']==2) {
            echo '<span class="label label-default pull-right cnt">'.$x['_CNT_CVR']['lbl'].'</span>';
        }

        if (in_array($x['NativeType'], [1,12,13,14,5,2])) {
            echo '<span class="term">'.date('H:i:s', strtotime($x['TermEmit'])).'</span>';
        }

        // CAPTION TEXT
        if (isset($x['_CPT_href'])) { // whether caption text will be LINK or not
            echo '<a class="dark" href="'.$x['_CPT_href'].'">'.$x['_Caption'].'</a>';
        } else {
            echo $x['_Caption'];
        }

        if (in_array($x['NativeType'], [1,12,13,14]) && $x['PRG']['MatType']==3) {

            // Print material-type label only for material-type 3, i.e. RERUN
            echo '<span class="lbl lblprog'.$x['PRG']['MatType'].'">'.$rerun_sign.'</span>';
        }

        echo '</div>';


        // COVERS

        if (in_array($x['NativeType'], [1,12,13])) {

            // Coverz that are associated directly with the element
            coverz_output($x['ID'], 1);

            // Rerun the function on element level
            epg_cvr_html($x['NativeID'], 'scnr');

        } elseif ($x['NativeType']==2) {

            // Coverz that are associated directly with the story
            if ($x['_CNT_CVR']['story']) {
                coverz_output($x['STRY']['ID'], 2);
            }

            // Rerun the function on story level
            if ($x['_CNT_CVR']['atomz']) {
                epg_cvr_html($x['STRY']['ID'], 'story');
            }

        } elseif ($x['NativeType']==20 && $x['_CNT_CVR']['sum']) {

            // Coverz that are associated with the atom
            coverz_output($x['ID'], 3);
        }
    }
}
















/**
 * Prints scnr in PROMPTER view
 *
 * @param int $id ID of the master object, for which we want list, and which can be of specified below list types.
 * @param string $listtyp List type: (scnr, story).
 *
 * @return void|array $r Data to be used in print version
 */
function epg_prompter_html($id, $listtyp) {

    global $speakerz, $show_labels;


    if ($listtyp=='scnr') {

        $sql = 'SELECT ID, NativeID FROM epg_scnr_fragments WHERE ScnrID='.$id.
            ' AND IsActive AND NativeType=2 ORDER BY Queue';

        $show_labels = setz_get('scnr_prompter_element_labels');

    } else { // story

        $sql = 'SELECT ID, TypeX FROM stry_atoms WHERE StoryID='.$id.' ORDER BY Queue';

        $atom_typz = txarr('arrays', 'atom_jargons.'.ATOM_JARGON);
    }

    $result = qry($sql);

    $r = [];

    while ($line = mysqli_fetch_assoc($result)) {

        $html = [];

        switch ($listtyp) {

            case 'scnr':

                $x = rdr_row('stryz', 'ID, Caption', $line['NativeID']);

                $cnt = cnt_sql('stry_atoms', 'TypeX=1 AND StoryID='.$x['ID']);

                if ($cnt) {

                    $html['caption'] = mb_strtoupper($x['Caption']);

                    $html['atoms'] = epg_prompter_html($x['ID'], 'story');
                }

                break;

            case 'story':

                if ($line['TypeX']==1) { // KAM

                    if (count($speakerz)>1) {

                        $spkr_x = rdr_cell('stry_atoms_speaker', 'SpeakerX', $line['ID']);

                        $spkr = (empty($speakerz[$spkr_x]['uid'])) ? $spkr_x : $speakerz[$spkr_x]['name_short'];

                        $tip = epg_tip_reader(['schtyp' => 'stry', 'schline' => $line['ID'], 'tiptyp' => 2]);

                        $spkr_html = '{'.$spkr.(($tip) ? ':'.$tip['Tip'] : '').'} ';

                    } else {

                        $spkr_html = '';
                    }

                    $html = $spkr_html.rdr_cell('stry_atoms_text', 'Texter', $line['ID']);

                } elseif ($line['TypeX']==2) { // SKOP

                    if ($show_labels) {

                        $html =  '//'.$atom_typz[$line['TypeX']].'//';
                    }

                } elseif ($line['TypeX']==3) { // EKST

                    $html = '//'.$atom_typz[$line['TypeX']].'//'.PHP_EOL.
                        rdr_cell('stry_atoms_text', 'Texter', $line['ID']);
                }

                break;
        }

        if ($html) {

            $r[] = $html;
        }
    }


    if ($listtyp=='scnr') {


        // textbox

        echo '<div class="epg_prompter col-xs-12 hidden-print">'.
            '<textarea class="form-control no_vert_scroll" rows="25" '.
            'onclick="expandTxtarea(this,13); this.select(); document.execCommand(\'copy\');">';

        // Chrome quirk: doesn't properly divide stories into pages, if the height of the displayed page is not enough
        // for the page to be scrollable. Therefore, we put 25 rows in this textarea control, only to assure necessary
        // page height..

        foreach ($r as $cnt => $stry) {

            echo PHP_EOL.strtoupper($stry['caption']).PHP_EOL;

            foreach ($stry['atoms'] as $atom) {
                echo $atom.PHP_EOL;
            }
        }

        echo '</textarea></div>';


        // Print version

        echo '<div class="visible-print-block">';

        foreach ($r as $cnt => $stry) {

            echo '<div class="prompter_print">';

            echo '<h1>('.($cnt+1).') '.$stry['caption'].'</h1>';

            foreach ($stry['atoms'] as $atom) {

                // Put BOLD tags around speaker name (in print version)
                if ($atom[0]=='{') {
                    $ende = strpos($atom, '}')+1;
                    $atom = '<b>'.substr($atom, 0, $ende).'</b>'.substr($atom, $ende);
                }

                echo '<p>'.nl2br($atom).'</p>';
            }

            echo '</div>';
        }

        echo '</div>';


    } else {

        return $r;
    }
}













/**
 * Prints epg/scnr in RECORDS view
 *
 * @param int $id Either EPG ID or SCNR ID or Story ID, depending on $listtyp.
 * @param string $listtyp List type: (epg, scnr, story).
 * @param bool $is_active Whether line (story) is active (necessary only for *story* type).
 *
 * @return string $r Summary duration
 */
function epg_recs_html($id, $listtyp, $is_active=true) {

    $durz = [];

    if (!isset($ord)) {
        static $ord = 1;
    }
    if (!isset($cnt_bad)) {
        static $cnt_bad = 0;
    }


    if ($listtyp=='epg') {

        $rerun_sign = ' (<b>'.txarr('arrays', 'epg_mattyp_signs', 3).'</b>)';

        $epg_line_types = txarr('arrays', 'epg_line_types');
    }


    static $show_vo = 0;
    if ($listtyp=='scnr') $show_vo = setz_get('scnr_recs_vo');


    if ($listtyp=='epg') {
        $sql = 'SELECT ID FROM epg_elements WHERE EpgID='.$id.' AND NativeType IN (1, 12, 13) ORDER BY Queue';
    } elseif ($listtyp=='scnr') {
        $sql = 'SELECT ID FROM epg_scnr_fragments WHERE ScnrID='.$id.' AND NativeType=2 ORDER BY Queue';
    } else { // story
        $sql = 'SELECT SQL_CALC_FOUND_ROWS ID FROM stry_atoms WHERE '.((!$show_vo) ? 'TypeX=2 AND ' : '').'StoryID='.$id.
            ' ORDER BY Queue';
    }

    $result = qry($sql);


    if ($listtyp=='story') {
        if ($result) {
            list($num_rows) = mysqli_fetch_row(mysqli_query($GLOBALS["db"], 'SELECT FOUND_ROWS()'));
            // $num_rows is 1 when mysql error happens, therefore I added $result checking..
        } else {
            $num_rows = 0;
        }
    }


    if ($listtyp=='scnr' || $listtyp=='epg') {
        echo '<table class="table table-hover recs"><tbody>';
    }

    while (@list($xid) = mysqli_fetch_row($result)) {

        $css = '';

        switch ($listtyp) {


            case 'scnr':

                $x = rdr_row('epg_scnr_fragments', 'NativeID AS StoryID, IsActive', $xid);

                $atom_cnt = cnt_sql('stry_atoms', ((!$show_vo) ? 'TypeX=2 AND ' : '').'StoryID='.$x['StoryID']);

                if ($atom_cnt) {

                    $dur = epg_recs_html($x['StoryID'], 'story', $x['IsActive']);

                    if ($x['IsActive']) {
                        $durz[] = $dur;
                    }
                }

                break;


            case 'story':

                $x = rdr_row('stry_atoms', 'Duration, Queue, TypeX', $xid);

                $stry_cpt = rdr_cell('stryz', 'Caption', $id);

                if (!$show_vo && $num_rows>1) {
                    $stry_cpt .= ' ['.$x['Queue'].']';
                }

                if ($x['TypeX']!=2) { // Only SCOP proceeds
                    break;
                }

                if (!$x['Duration']) {
                    $x['Duration'] = '00:00:00';
                }

                if ($x['Duration']=='00:00:00' && $is_active) {
                    $cnt_bad++;
                }

                $durz[] = $x['Duration'];

                $x['Duration'] = hms2ms($x['Duration']);

                if (DUR_EDITABLE) {
                    $dur_html =
                        '<span class="hmsedit" onclick="hms_editable(this, \'dur\')" data-hms="'.$x['Duration'].'" '.
                        'data-id="'.$xid.'">'.$x['Duration'].'</span>';
                } else {
                    $dur_html = $x['Duration'];
                }

                $css = ($x['Duration']=='00:00') ? 'danger empty' : 'success';

                break;


            case 'epg':

                $x = element_reader($xid);

                if ($x['NativeType']==1) { // (prog)

                    if ($x['PRG']['MatType']==1) {
                        continue 2;
                    }

                    $x['Duration'] = $x['MOS']['Duration'];

                    $cpt = $x['PRG']['ProgCPT'];

                    if ($x['PRG']['Caption']) {
                        $cpt .= '<small> '.$x['PRG']['Caption'].'</small>';
                    }

                } else { // 12, 13 (film)

                    if (!$x['FILM']['FilmID']) {
                        continue 2;
                    }

                    $x['Duration'] = $x['FILM']['Duration'];

                    $cpt = film_caption($x['FILM'], ['typ' => $x['NativeType']]);

                    $css = ($x['FILM']['DurType']=='approx') ? 'danger' : 'success';
                }

                if (!$x['Duration']) {
                    $x['Duration'] = '00:00:00';
                }

                if ($x['IsActive']) {
                    $durz[] = $x['Duration'];
                }

                if (DUR_EDITABLE) {
                    $dur_html =
                        '<span class="hmsedit" onclick="hms_editable(this, \'dur_wide\')" data-hms="'.$x['Duration'].'" '.
                        'data-id="'.$xid.'">'.$x['Duration'].'</span>';
                } else {
                    $dur_html = $x['Duration'];
                }

                if ($x['Duration']=='00:00:00') {
                    $css = 'danger empty';
                } elseif (!$css) {
                    $css = 'success';
                }

                $is_active = $x['IsActive'];

                break;
        }


        if ($listtyp=='story' || $listtyp=='epg') {

            if ($listtyp=='story' && $x['TypeX']!=2) {

                $tip = epg_tip_reader(['schtyp' => 'stry', 'schline' => $xid, 'tiptyp' => 3]);

                if (!$tip) {
                    continue;
                }

            } else {
                $tip = '';
            }

            echo '<tr'.(($is_active) ? '' : ' class="inactive"').'>';

            echo '<td class="ordinal">'.(($tip) ? '' : $ord).'</td>';

            echo '<td class="dur bg-'.(($tip) ? 'warning' : $css).'">'.(($tip) ? '' : $dur_html).'</td>';

            if ($listtyp=='story') {

                echo '<td class="cpt story">'.
                    '<a href="/desk/stry_details.php?id='.$id.'">'.$stry_cpt.'</a>'.(($tip) ? ' - '.$tip['Tip'] : '').
                    '</td>';

            } else { // epg

                echo '<td class="linetyp text-uppercase text-center">'.$epg_line_types[$x['NativeType']].'</td>';

                echo '<td class="term">'.date('H:i:s', strtotime($x['TermEmit'])).'</td>';

                echo '<td class="cpt"><a href="epg.php?typ=scnr&id='.$xid.'">'.$cpt.'</a>'.
                    (($x['PRG']['MatType']==3) ? $rerun_sign : ''). // Rerun
                    '</td>';
            }

            echo '</tr>';

            if (!$tip) {
                $ord++;
            }
        }
    }


    $r = sum_durs($durz);

    if ($listtyp=='scnr' || $listtyp=='epg') {

        echo '<tr>';
        echo '<td class="ordinal">&sum;</td>';

        if ($listtyp=='scnr') {

            echo '<td class="summa bg-'.(($cnt_bad) ? 'danger' : 'success').'">'.hms2ms($r).'</td>';
            echo '<td class="finito">'.(($cnt_bad) ? '(-'.$cnt_bad.')' : '').'</td>';

        } else { // epg

            echo '<td class="summa">'.$r.'</td>';
            echo '<td colspan="3"></td>';
        }

        echo '</tr>';
        echo '</tbody></table>';
    }

    return $r; // this is actually necessary only for *story* type
}







/**
 * Prints epg in EXPORTER view
 *
 * @param string $listtyp Type: (epg, spice)
 * @param int $id EPG ID
 * @param array $sch_data Morpheus schedule data
 * @param array $parent Parent data (not used in *epg* list-type)
 * - term (string) Parent Term
 * - id (int) Parent ID
 *
 * @return void
 */
function epg_exp_html($listtyp, $id, $sch_data, $parent=null) {


    if ($listtyp=='epg') {

        form_tag_output('open', '?typ=epg&view=11&downer=1&id='.EPGID, false);

        echo '<div class="epgexp_ctrlz hidden-print" style="position: relative;">';

        echo
            '<div class="col-sm-2"><input type="text" name="SCH_Channel" class="form-control" '.
            'value="'.$sch_data['SCH']['Channel'].'" placeholder="Channel Name" required></div>';

        echo
            '<div class="col-sm-2"><input type="text" name="SCH_Name" class="form-control" '.
            'value="'.$sch_data['SCH']['Name'].'" placeholder="Schedule Name" required></div>';

        echo
            '<div class="col-sm-4"><input type="text" name="SCH_ExternalId" class="form-control" '.
            'value="'.$sch_data['SCH']['ExternalId'].'" placeholder="ExternalId" required></div>';

        echo
            '<div class="col-sm-2"><input type="text" name="SCH_SvrRecs" class="form-control" '.
            'value="'.$sch_data['SCH']['SvrRecs'].'" placeholder="SvrRecs" required></div>';

        echo
            '<div class="col-sm-2"><input type="text" name="SCH_SvrLive" class="form-control" '.
            'value="'.$sch_data['SCH']['SvrLive'].'" placeholder="SvrLive" required></div>';

        echo '<input type="checkbox" value="1" style="position: absolute; left:-20px; top: 48px;" '.
            'onClick="chk_all(\'epg_table\', this)">'; // master checkbox

        echo '</div>';

        echo '<table id="epg_table" class="schtable epgexp klikle"><tbody>';

        $epg_mattyp_signs = txarr('arrays', 'epg_mattyp_signs');
    }


    $result = qry(epg_exp_sql($listtyp, $id));

    while (@list($xid) = mysqli_fetch_row($result)) {

        $x = epg_exp_element($listtyp, $xid);
        if (!$x) continue;

        list($css, $sct) = epg_linetyp($x['NativeType']);

        switch ($x['NativeType']) {

            case 1: // prg

                //$x['MOS_Duration'] = $x['MOS']['Duration'];

                $x['_Caption'] = '<span class="cpt">'.$x['PRG']['ProgCPT'].'</span>'.
                    (($x['PRG']['Caption']) ? ' - '.$x['PRG']['Caption'] : '');
                $x['_CPT_href'] = 'epg.php?typ=scnr&id='.$x['ID'];
                break;

            case 12: // film
            case 13: // film-serial

                //$x['MOS_Duration'] = $x['FILM']['Duration'];

                $x['_Caption'] = film_caption($x['FILM'], ['typ' => $x['NativeType']]);
                $x['_CPT_href'] = 'epg.php?typ=scnr&id='.$x['ID'];
                break;

            case 14: // linker

                $x['_Caption'] = epg_link_cpt($x);
                $x['_CPT_href'] = 'epg.php?typ=scnr&id='.$x['NativeID'];
                break;

            case 3:	// spice
            case 4:

                $x['_Caption'] = ($listtyp!='spice') ? @$x['BLC']['Caption'] : $x['Caption'];
                $x['_CPT_href'] =
                    'spice_details.php?sct='.$sct.'&typ='.(($listtyp!='spice') ? 'block' : 'item').'&id='.$x['NativeID'];
                break;

            case 5: // clp

                $x['_Caption'] = ($listtyp!='spice') ? $x['CLP']['Caption'] : $x['Caption'];
                $x['_CPT_href'] = 'spice_details.php?sct='.$sct.'&id='.$x['NativeID'];
                break;
        }

        //if (!$x['MOS_Duration']) {$x['MOS_Duration'] = '00:00:00';}

        if ($listtyp=='epg' && $x['NativeType']==4 && $x['_CNT_Frag']) {

            epg_exp_html('spice', $x['NativeID'], $sch_data, ['term' => $x['TermEmit'], 'id' => $x['ID']]);

        } else {

            echo '<tr>';

            if ($listtyp=='epg') {

                echo '<td class="term'.(($x['TimeAir']) ? ' fixed' : '').'">'.
                    '<input name="chk'.$x['ID'].'" class="chk_event" type="checkbox" value="1" checked>'.
                    (($x['OnHold']) ? '* * *' : date('H:i:s', strtotime($x['TermEmit']))).'</td>';

            } else {

                echo '<td class="term">'.
                    '<input name="chk'.$parent['id'].'_'.$x['ID'].'" class="chk_event" type="checkbox" value="1" checked>'.
                    date('H:i:s', strtotime($parent['term'])).
                    '</td>';

                $parent['term'] = add_dur2term($parent['term'], $x['_Dur']['winner']['dur']);
            }

            echo '<td class="dur '.$x['_Dur']['winner']['typ'].'">'.$x['_Dur']['winner']['dur'].'</td>';
            //echo '<td class="dur">'.$x['MOS_Duration'].'</td>';

            echo '<td class="cpt '.$css.'">';

            epg_linetyp_lbl($x, 'epg');

            echo '<a class="cpt lbl_left" href="'.$x['_CPT_href'].'">'.$x['_Caption'].'</a>';

            if (in_array($x['NativeType'], [1,12,13,14]) && $x['PRG']['MatType']!=2) {
                echo '<span class="lbl lblprog'.$x['PRG']['MatType'].'">'.$epg_mattyp_signs[$x['PRG']['MatType']].'</span>';
            }

            if ($listtyp=='epg' && in_array($x['NativeType'], [1,12,13])) {
                echo '<a class="lbl_right scnrexp opcty3 satrt hidden-print" href="'.
                    '?'.$_SERVER["QUERY_STRING"].'&downer=1&output_type=file&scnrexp='.$x['NativeID'].'">'.
                    '<span class="glyphicon glyphicon-save"></span></a>';
            }

            echo '</td>';

            echo '<td id="tr'.$x['ID'].'" name="klk" class="klk bg-info">'.morpheus_id($x, $sch_data, 'id').'</td>';

            echo '</tr>';
        }
    }


    if ($listtyp=='epg') {

        echo '</tbody></table>';

        form_tag_output('close');
    }
}


/**
 * Get SQL for epg_exp functions (epg_exp_html, morpheus_epgexp)
 *
 * @param string $listtyp Type: (epg, scnr, spice)
 * @param int $id
 *
 * @return string $sql Sql
 */
function epg_exp_sql($listtyp, $id) {

    if ($listtyp=='epg') {

        $filter = 'NativeType IN (1,12,13,14,3,4,5)';

        $sql = 'SELECT ID FROM epg_elements WHERE EpgID='.$id.' AND '.$filter.' AND IsActive ORDER BY Queue';

    } elseif ($listtyp=='scnr') {

        $filter = 'NativeType IN (3,4,5)';

        $sql = 'SELECT ID FROM epg_scnr_fragments WHERE ScnrID='.$id.' AND '.$filter.' AND IsActive ORDER BY Queue';

    } else { // spice

        $sql = 'SELECT ID FROM epg_cn_blocks WHERE BlockID='.$id.' AND IsActive ORDER BY Queue';
    }

    return $sql;
}


/**
 * Get element data array for epg_exp functions (epg_exp_html, morpheus_epgexp)
 *
 * @param string $listtyp Type: (epg, scnr, spice)
 * @param int $xid ID
 *
 * @return array|null $x Element data array
 */
function epg_exp_element($listtyp, $xid) {

    switch ($listtyp) {
        case 'epg':			$x = element_reader($xid);   break;
        case 'scnr':	    $x = fragment_reader($xid);  break;
        case 'spice':		$x = epg_spice_reader($xid); break;
    }

    $x['_Dur'] = epg_durations($x, 'hms');
    // epg_durations will decide which DUR to use (epg-forc, forc or calc).
    // It will return array with winner and loser values.

    $x['Duration'] = $x['_Dur']['winner']['dur'];

    switch ($x['NativeType']) {

        case 12: // film
        case 13: // film-serial

            if (!$x['FILM']['FilmID']) {
                return null;
            }
            break;

        case 3:	// spice
        case 4:

            if ($listtyp!='spice') {

                $x['_CNT_Frag'] = cnt_sql('epg_cn_blocks', 'BlockID='.$x['NativeID']);

            } else { // item

                $x['EPG']['ChannelID'] = CHNL;

                $x['AttrA'] = rdr_cell('epg_promo', 'CtgID', $x['NativeID']);
            }

            break;

        case 5: // clp

            if ($listtyp=='spice') { // item

                $x['EPG']['ChannelID'] = CHNL;

                $x['CLP']['Caption'] = $x['Caption'];
            }

            break;
    }

    return $x;
}







/**
 * Print EPG LIST with ONLY LIVE PROGS or ONLY PROGS AND FILMS
 *
 * This option was added as a favor to desk people (editors & journos), so they could easily get to their programs
 *
 * @param int $id EPG ID
 * @param string $typ (live_only, progs_films)
 *
 * @return void
 */
function epg_list_short($id, $typ) {

    $epg_mattyp_signs = txarr('arrays', 'epg_mattyp_signs');

    $filter = ($typ=='live_only') ? 'NativeType=1' : 'NativeType IN (1,12,13,14)';

    $sql = 'SELECT ID FROM epg_elements WHERE EpgID='.$id.' AND '.$filter.' AND IsActive ORDER BY Queue';

    $result = qry($sql);

    while (@list($xid) = mysqli_fetch_row($result)) {

        $x = element_reader($xid);

        if ($typ=='live_only' && $x['PRG']['MatType']!=1) { // LIVE only
            continue;
        }

        switch ($x['NativeType']) {

            case 1: // prg

                $x['_Caption'] = '<span class="cpt">'.$x['PRG']['ProgCPT'].'</span>'.
                    (($x['PRG']['Caption']) ? ' - '.$x['PRG']['Caption'] : '');
                $x['_CPT_href'] = 'epg.php?typ=scnr&id='.$x['ID'];
                $css = 'prg';
                break;

            case 12: // film
            case 13: // film-serial

                $x['_Caption'] = film_caption($x['FILM'], ['typ' => $x['NativeType']]);
                $x['_CPT_href'] = 'epg.php?typ=scnr&id='.$x['ID'];
                $css = 'film';
                break;

            case 14: // linker

                $x['_Caption'] = epg_link_cpt($x);
                $x['_CPT_href'] = 'epg.php?typ=scnr&id='.$x['NativeID'];
                $css = 'prg';
                break;
        }

        $x['_Dur'] = epg_durations($x, 'hms');
        // epg_durations will decide which DUR to use (epg-forc, forc or calc).
        // It will return array with winner and loser values.


        echo PHP_EOL.PHP_EOL.'<tr>';

        echo '<td class="term'.(($x['TimeAir']) ? ' fixed' : '').'">'.
            (($x['OnHold']) ? '* * *' : date('H:i:s', strtotime($x['TermEmit']))).'</td>';

        echo '<td class="dur '.$x['_Dur']['winner']['typ'].'">'.$x['_Dur']['winner']['dur'].'</td>';

        echo '<td class="cpt '.$css.'">';

        if ($x['NativeType']==1 && $x['PRG']['IsReady']) {
            echo '<span class="glyphicon glyphicon-ok ready"></span>';
        }

        echo '<a class="cpt lbl_left" href="'.$x['_CPT_href'].'">'.$x['_Caption'].'</a>';

        if ($x['PRG']['MatType']!=2) {
            echo '<span class="lbl lblprog'.$x['PRG']['MatType'].'">'.$epg_mattyp_signs[$x['PRG']['MatType']].'</span>';
        }

        if ($x['NativeType']==1 && $x['CRW']) {

            $editors = [];

            $crw = [];
            foreach ($x['CRW'] as $v) {
                $crw[$v['CrewType']][] = $v['CrewUID'];
            }

            if (isset($crw[1])) { // CrewType: Editor
                foreach ($crw[1] as $v) {
                    $editors[] = uid2name($v);
                }
            }

            echo (($editors) ? '<span class="stry_author lbl_right">'.implode(', ', $editors).'</span>' : '');
        }


        echo '</td>';

        echo '</tr>';
    }
}








/**
 * Print linetype label
 *
 * @param array $x Element/Fragment
 * @param string $listtyp List type: (epg, scnr, story).
 * @return void
 */
function epg_linetyp_lbl($x, $listtyp) {

    $epg_line_types = txarr('arrays', 'epg_line_types');

    if ($x['NativeType']==2 && in_array(VIEW_TYP, [0,3])) { // Story

        $lbl_linetyp = $x['ATOMZ_LBL'];

    } elseif ($x['NativeType']==4) { // Promo: type

        $lbl_linetyp = txarr('arrays', 'epg_prm_ctgz', $x['AttrA']);

        // We can't simply use str_replace(), because it replaces *all* occurences and we want just *one*
        if (mb_strlen($lbl_linetyp)>15) {
            $blanker = strpos($lbl_linetyp, ' ');
            if ($blanker) {
                $lbl_linetyp = substr($lbl_linetyp, 0, $blanker).'<br>'.substr($lbl_linetyp, $blanker+1);
            }
        }

    } elseif ($x['NativeType']==9) { // Spacer

        $lbl_linetyp = '<span class="glyphicon glyphicon-resize-horizontal"></span>';

    } elseif ($x['NativeType']==10) { // Segment

        $lbl_linetyp = '<span class="glyphicon glyphicon-arrow-up"></span>';

    } elseif (in_array($x['NativeType'], [1,14]) && in_array(VIEW_TYP, [0,11])) {

        $lbl_linetyp = $epg_line_types[$x['NativeType']];

    } elseif ($listtyp!='spice' && in_array($x['NativeType'], [3,5,7,12,13])) {

        $lbl_linetyp = $epg_line_types[$x['NativeType']];

    } else {

        $lbl_linetyp = '';
    }

    if ($lbl_linetyp) {

        if (in_array(VIEW_TYP, [8,9]) && $x['NativeID'] && PMS_SPICER) { // SPICER: Only if *block* is selected
            $attrz = drg_attrz('spicer', $x['NativeID'], 'bloc', true);
        } else {
            $attrz = '';
        }

        echo '<span class="lbl_linetyp text-uppercase" '.$attrz.'>'.$lbl_linetyp.'</span>';
    }
}



/**
 * Check whether atom is labeled as voiceover
 *
 * @param int $atomid Atom ID
 * @return bool $vo
 */
function atom_is_vo($atomid) {

    $tip = epg_tip_reader(['schtyp' => 'stry', 'schline' => $atomid, 'tiptyp' => 3]);

    $vo = ($tip) ? true : false;

    return $vo;
}




/**
 * Output YESTERDAY & TOMMOROW links (used in EPG header)
 *
 * @param array $datecpt
 * @param string $href Href
 * @param string $href_key Href key, used in forming Href
 * @return void
 */
function ytdtmr_html($datecpt, $href, $href_key='epgid') {

    echo '<div class="pull-right hidden-print">';

    echo '<div class="ytdtmr" style="text-align:right;">';
    if ($datecpt['YTD'][$href_key]) {
        echo '<a href="'.$href.$datecpt['YTD'][$href_key].'">'.$datecpt['YTD']['HTML'].'</a>';
    } else {
        echo $datecpt['YTD']['HTML'];
    }
    echo '</div>';

    echo '<div class="ytdtmr">';
    if ($datecpt['TMR'][$href_key]) {
        echo '<a href="'.$href.$datecpt['TMR'][$href_key].'">'.$datecpt['TMR']['HTML'].'</a>';
    } else {
        echo $datecpt['TMR']['HTML'];
    }
    echo '</div>';

    echo '</div>';
}


