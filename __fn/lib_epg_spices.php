<?php

// epg spices - marketing, promo, clips






/**
 * SPICE reader
 *
 * @param int $id ID
 * @param string $typ Type: (item, block, agent)
 * @param string $sct Section: (mkt, prm, clp)
 * @return void|array $x
 */
function spice_reader($id=0, $typ='', $sct='') {


    if (!$id  && isset($_GET['id'])) 	$id 	= wash('int', $_GET['id']);
    if (!$typ && isset($_GET['typ'])) 	$typ 	= wash('cpt', $_GET['typ']);
    if (!$sct && isset($_GET['sct'])) 	$sct 	= wash('cpt', $_GET['sct']);


    if (!in_array($sct, ['mkt', 'prm', 'clp'])) {
        return null;
    }

    if ($sct=='clp') $typ = 'item'; // clip can have *only* items, there are no blocks or agencies

    if (!in_array($typ, ['item', 'block', 'agent'])) {
        return null;
    }


    switch ($typ) {

        case 'item':

            switch ($sct) {
                case 'mkt':		$tbl = 'epg_market'; 	        break;
                case 'prm':		$tbl = 'epg_promo';		        break;
                case 'clp':		$tbl = 'epg_clips';		        break;
            }
            break;

        case 'block':			$tbl = 'epg_blocks'; 	        break;
        case 'agent':			$tbl = 'epg_market_agencies';	break;
    }



    if ($id) {
        $x = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$id);
    }


    $x['ID'] 		= intval(@$x['ID']) ;
    $x['TYP'] 		= $typ;
    $x['EPG_SCT']   = $sct;
    $x['TBL'] 		= $tbl;

    switch ($x['EPG_SCT']) {
        case 'mkt':		$x['EPG_SCT_ID'] = 3; break;
        case 'prm':		$x['EPG_SCT_ID'] = 4; break;
        case 'clp':		$x['EPG_SCT_ID'] = 5; break;
    }



    if (!$x['ID']) { // for NEW.. set default empty time values and return

        if ($typ=='item') {

            $x['DurForcTXT'] = t2boxz('', 'time');

            if ($x['EPG_SCT']!='clp') {
                $x['DateStartTXT'] = t2boxz('', 'date');
                $x['DateExpireTXT'] = t2boxz('', 'date');
            }
        }

        if ($typ=='block') {
            $x['DurForcTXT'] = $x['DurCalcTXT'] = $x['MOS']['tc-in'] = $x['MOS']['tc-out'] = t2boxz('', 'time');
        }

        $x['ChannelID'] = (!empty($_GET['chnl'])) ? intval($_GET['chnl']) : CHNL;

        return $x;
    }



    switch ($typ) {

        case 'item':

            if (@$x['AgencyID']) {
                $x['AgencyTXT'] = rdr_cell('epg_market_agencies', 'Caption', $x['AgencyID']);
            }

            if ($x['EPG_SCT']=='prm' && @$x['CtgID']) {
                $x['CtgTXT'] = txarr('arrays', 'epg_'.$x['EPG_SCT'].'_ctgz', $x['CtgID']);
            }

            if (@$x['Placing']) {
                $x['PlacingTXT'] = txarr('arrays', 'epg_clp_place', $x['Placing']);
            }

            $x['DurForcTXT'] = t2boxz(@$x['DurForc'], 'time');

            if ($x['EPG_SCT']!='clp') {

                $x['DateStartTXT'] = t2boxz(@$x['DateStart'], 'date');
                $x['DateExpireTXT'] = t2boxz(@$x['DateExpire'], 'date');
            }

            break;

        case 'block':

            $x['DurCalc'] = epg_durcalc('block', $x['ID']);

            $x['DurCalcCSS'] = dur_handler($x['DurForc'], $x['DurCalc'], 'css');

            $x['DurForcTXT'] = t2boxz(@$x['DurForc'], 'time');
            $x['DurCalcTXT'] = t2boxz(@$x['DurCalc'], 'time');

            if ($x['EPG_SCT']=='prm') {
                $x['CtgTXT'] = txarr('arrays', 'epg_'.$x['EPG_SCT'].'_ctgz', $x['CtgID']);
            }

            $x['MOS'] = mos_reader($x['ID'], $x['EPG_SCT_ID']);

            $x['CRW'] = crw_reader($x['ID'], $x['EPG_SCT_ID']);

            break;

        case 'agent':
            break;
    }

    return $x;
}






/**
 * Prints table with block members (i.e fragments or rows)
 *
 * @param int $id Block ID
 * @param string $pg_type Page type: (dtl, mdf)
 * @param string $epg_line_type EPG line type: (mkt, prm). Used only for MDF page type, to put default clips into
 *                              a new empty block.
 * @param int $item_type Item type (epg_prm_ctgz)
 *
 * @return void
 */
function block_content($id, $pg_type='dtl', $epg_line_type='', $item_type=0) {

    $a = []; // array which will hold block fragments

    if ($id) { // mdf

        $a = qry_assoc_arr('SELECT NativeID, NativeType, IsActive FROM epg_cn_blocks WHERE BlockID='.$id.' ORDER BY Queue');

    } else { // new

        if ($pg_type=='dtl') { // Only NEW (i.e. MDF) page can have missing ID.
            return;
        }

        if ($epg_line_type=='prm' && $item_type!=1) {

            // Skip for PRMs of other than type 1 (promo)

        } else {

            foreach([1,2] as $v) { // put *default* clips into a new empty block, if any default clips are set.

                $sql = 'SELECT ID AS NativeID, 5 AS NativeType, 1 AS IsActive FROM epg_clips '.
                    'WHERE CtgID='.(($epg_line_type=='mkt') ? 1 : 2).' AND Placing IN ('.$v.', 3) '.
                    'ORDER BY ID ASC LIMIT 2';
                // epg_clp_place: 1-opens, 2-closes, 3-both
                // epg_clp_ctgz: 1-mkt, 2-prm, 3-prog

                $lines = qry_assoc_arr($sql);
                if ($lines) {
                    $a = array_merge($a, $lines);
                }
            }
        }
    }


    // Even if there are not any rows, we should still go ahead with this, i.e. speaking of MDF at least, because
    // otherwise there wouldn't be any DND table, and the user wouldn't be able to add new items to empty table

    if ($pg_type=='dtl') { // DTL

        echo '<div class="row"><div id="spiceblock" class="dtl col-lg-9 col-md-10 col-md-offset-1 col-xs-12">';

    } else { // MDF

        echo '<div id="spiceblock">';
        echo '<table id="dndtable" class="row col-lg-9 col-md-10 col-md-offset-1 col-xs-12">';
    }


    $cnt=0;

    foreach ($a as $v) {

        $cnt++;

        switch ($v['NativeType']) {

            case 3: $tbl = 'epg_market';    $epg_line_typ = 'mkt';	break;
            case 4:	$tbl = 'epg_promo';		$epg_line_typ = 'prm';	break;
            case 5:	$tbl = 'epg_clips';		$epg_line_typ = 'clp';	break;
        }

        $x = qry_assoc_row('SELECT Caption, DurForc FROM '.$tbl.' WHERE ID='.$v['NativeID']);
        if (!$x) continue;

        if (!$x['DurForc']) {
            $x['DurForc'] = '00:00:00';
        }

        if ($pg_type=='dtl') { // DTL

            $cpt = $x['Caption'].' <small><a href="spice_details.php?sct='.$epg_line_typ.'&typ=item&id='.$v['NativeID'].'">/'.
                sprintf('%04s', $v['NativeID']).'/</a></small>';

            echo '<div class="'.$epg_line_typ.'">';

            block_content_item($pg_type, $cnt, $v['NativeID'], $cpt, $x['DurForc'], $v['IsActive']);

            echo '</div>';

        } else { // MDF

            echo '<tr name="wraptr" id="'.$v['NativeID'].'" class="'.$epg_line_typ.
                '" lang="'.$v['NativeType'].'" '.'active="'.$v['IsActive'].'"><td>';
            // note: we use LANG attribute to pass NativeType..

            block_content_item($pg_type, $cnt, $v['NativeID'], $x['Caption'], $x['DurForc'], $v['IsActive']);

            echo '</td></tr>';
        }
    }


    if ($pg_type=='dtl') { // DTL

        echo '</div></div>';

    } else { // MDF

        echo '</table>';
        echo '</div>';
    }

}



/**
 * Prints an item in spice block
 *
 * @param string $pg_typ Page type: (dtl, mdf)
 * @param int $cnt Ordinal number
 * @param int $id Item native ID
 * @param string $cpt Caption
 * @param string $dur Duration (DurForc)
 * @param bool $actve Active/inactive state
 * @return void
 */
function block_content_item($pg_typ, $cnt, $id, $cpt, $dur, $actve) {

    global $cfg;

    $dur_txt = date('i:s', strtotime($dur));

    if ($cfg['dur_use_milli']) {
        $ms = milli_check($dur, 'ms');
        $ff = milli2ff($ms);
        $dur_txt .= '<span class="hms_ff">.'.$ff.'</span>';
    }

    if ($pg_typ=='dtl') {

        echo
            '<div id="wrapdiv" name="wrapdiv" class="row '.(($actve) ? '' : ' switchedoff').'">'.
                '<div id="numero" class="col-xs-1 text-center">'.$cnt.'</div>'.
                '<div id="dur" name="dur" class="col-xs-2 text-center">'.$dur_txt.'</div>'.
                '<div id="cpt" class="col-xs-9">'.$cpt.'</div>'.
            '</div>';
    }

    if ($pg_typ=='mdf') {

        echo
            '<div id="wrapdiv" name="wrapdiv" class="'.(($actve) ? '' : 'switchedoff').'">'.
                '<div id="numero" class="col-xs-1 text-center">'.$cnt.'</div>'.
                '<div id="dur" name="dur" class="col-xs-2 text-center">'.$dur_txt.'</div>'.
                '<div id="cpt" class="col-xs-9">'.$cpt.
                    '<a id="sleep" class="text-muted opcty3" href="#" onClick="SPICE_item_switch(this); return false;">'.
                    '<span class="glyphicon glyphicon-'.(($actve) ? 'ok' : 'ban').'-circle"></span></a>'.
                    '<a id="del" class="text-muted opcty3" href="#" onclick="SPICE_item_del('.$id.', this); return false;">'.
                    '<span class="glyphicon glyphicon-remove-circle"></span></a>'.
                '</div>'.
            '</div>';
    }
}





/**
 * Saves BLOCK connections, i.e. content
 *
 * @param int $id BlockID
 * @return string $r DurEmit - Summary duration of block items
 */
function block_receiver($id) {

    $dur_atomz = [];

    qry('DELETE FROM epg_cn_blocks WHERE BlockID='.$id);


    if (@$_POST['qu']) {

        $q_arr      = explode(' ', trim($_POST['qu']));
        $q_typ_arr  = explode(' ', trim($_POST['qu_typ']));
        $q_act_arr  = explode(' ', trim($_POST['qu_active']));

        foreach ($q_arr as $k => $v) {

            $sql = sprintf('INSERT INTO epg_cn_blocks (BlockID, NativeType, NativeID, Queue, IsActive) VALUES (%u,%u,%u,%u,%u)',
                $id, $q_typ_arr[$k], $v, $k, $q_act_arr[$k]);
            qry($sql, LOGSKIP);

            if ($q_act_arr[$k]) {

                switch ($q_typ_arr[$k]) {
                    case 3:	$tbl = 'epg_market'; break;
                    case 4:	$tbl = 'epg_promo';	 break;
                    case 5:	$tbl = 'epg_clips';	 break;
                }

                list($dur_atomz[]) = qry_numer_row('SELECT DurForc FROM '.$tbl.' WHERE ID='.$v);
            }
        }
    }

    // calculate and update DurEmit
    $r = sum_durs($dur_atomz);
    qry('UPDATE epg_blocks SET DurEmit=\''.$r.'\' WHERE ID='.$id);


    $log = ['tbl_name' => 'epg_blocks', 'x_id' => $id, 'act_id' => 2, 'act' => 'block'];
    qry_log(null, $log);

    return $r;
}



/**
 * Get Wrapclips sign from specified Wrapclips code
 *
 * @param int $wrapclip_code Wrapclips code (0 - both, 1 - only front, 2 - only back, 3 - none)
 *
 * @return string $r Wrapclips sign
 */
function mkt_wrapclips_sign($wrapclip_code) {

    $r = (in_array($wrapclip_code, [2,3])) ? '<b>{</b>' : '{';
    $r .= (in_array($wrapclip_code, [1,3])) ? '<b>}</b>' : '}';

    return '<span class="wrapclips">'.$r.'</span>';
}



/**
 * Add agency caption to mkt item caption
 *
 * @param string $cpt Mkt item caption
 * @param int $agencyid Agency ID
 *
 * @return string $cpt Mkt item caption with agency caption
 */
function mkt_cpt_agency($cpt, $agencyid) {

    if ($agencyid) {

        $agencytxt = rdr_cell('epg_market_agencies', 'Caption', $agencyid);

        $cpt = mb_strtoupper($agencytxt).': '.$cpt;
    }

    return $cpt;
}




/**
 * Get caption for spice block which is within scnr.
 * We add scnr caption and term in order to indicate that the block is *within* scnr.
 *
 * Used in epg table for mkt/prm blocks, and also in mktplan table.
 *
 * @param array $element Element data (must have: ID, IsActive, TermEmit, NativeType)
 * @param string $rtyp Return type (html, txt, msg)
 *
 * @return string $cpt Block caption with scnr data
 */
function epgblock_cpt_scnr($element, $rtyp='html') {

    $cpt = scnr_cpt_get($element['ID']);


    $cpt = txt_cutter($cpt, 18);

    if (in_array($element['NativeType'], [12, 13])) {
        $cpt = txarr('arrays', 'epg_line_types', $element['NativeType'], 'uppercase').' '.$cpt;
    }


    $cpt = term2timehm($element['TermEmit']).' '.$cpt;


    if ($rtyp=='html') {

        $cpt =
            '<span class="progcpt lbl_left'.((!$element['IsActive']) ? ' inactv' : '').'">'.
                '<a href="epg.php?typ=scnr&id='.$element['ID'].'">'.
                    $cpt.
                '</a>'.
            '</span>';
    }


    return $cpt;
}



/**
 * Check whether spice block is used anywhere or not (so we can then safely delete it)
 *
 * @param int $id Block ID
 * @param int $nattyp Block NativeType
 * @param bool $do_delete Whether to delete if block is not used anywhere
 *
 * @return bool $is_used Whether it is used or not
 */
function block_is_used($id, $nattyp, $do_delete=false) {

    $is_used = rdr_cell('epg_elements', 'ID', 'NativeType='.$nattyp.' AND NativeID='.$id); // check elements

    if (!$is_used) {
        $is_used = rdr_cell('epg_scnr_fragments', 'ID', 'NativeType='.$nattyp.' AND NativeID='.$id); // check fragments
    }

    if (!$is_used && $do_delete) {

        require_once 'fn_del.php';

        deleter('epg_mkt', $id, ['zsct' => 'mkt', 'ztyp' => 'block', 'skip_redirect' => true]);
    }

    return $is_used;
}

