<?php







/**
 * MKTplan(EPG): EPG Block data
 *
 * @param array $x Element/Fragment data
 * @param string $sch_typ Epg schedule type (epg or scnr)
 *
 * @return array $block Block data
 */
function mktepg_block_data($x, $sch_typ) {

    if ($x['NativeID']) {
        $x['BLC']  = qry_assoc_row('SELECT ID, Caption, DurForc FROM epg_blocks WHERE ID='.$x['NativeID']);
        $x['BLC']['DurCalc'] = epg_durcalc('block', $x['BLC']['ID']);
    }

    $x['_Dur'] = epg_durations($x, 'ms');

    $x['_Caption'] = mktepg_block_cpt($x, $sch_typ);

    return $x;
}



/**
 * MKTplan(EPG): Get full mktepg-block caption. If applicable includes scnr reference, and spice block caption.
 *
 * @param array $x Epg-block data (Element or fragment data)
 * @param string $sch_typ Epg schedule type (epg or scnr)
 * @param string $rtyp Return type (html, txt, msg).. (txt is for cbo, msg is for omg)
 *
 * @return string $cpt Full epg-block caption
 */
function mktepg_block_cpt($x, $sch_typ, $rtyp='html') {

    $cpt = '';

    if ($sch_typ=='scnr') {

        $cpt = epgblock_cpt_scnr($x['ELEMENT'], $rtyp); // SCNR reference

    } else { // epg

        if ($rtyp=='html' && isset($x['cbo'][$x['ID'].'.epg'])) {

            $cpt = '<span class="progcpt lbl_left">'.
                '<a href="epg.php?typ=epg&id='.EPGID.'#tr'.$x['ID'].'">'.$x['cbo'][$x['ID'].'.epg'].'</a>'.
                '</span>';
        }
    }

    // Block position
    if ($sch_typ=='scnr') {
        $cpt = '['.txarr('arrays', 'epg_mktblc_positions', 0, 'lowercase').'] '.$cpt;
    }

    // Spice block caption
    if ($x['NativeID']) { // If spice block is associated with epg-block
        if ($rtyp=='txt') {
            $cpt = $x['BLC']['Caption'].' '.$cpt;
        } elseif ($rtyp=='html') {
            $cpt .= '<a class="cpt lbl_left" href="spice_details.php?sct=mkt&typ=block&id='.$x['NativeID'].'">'.
                '<span class="glyphicon glyphicon-new-window"></span></a>'; // glyph is instead of $x['BLC']['Caption']
        }
    }

    // TermEmit
    if ($rtyp=='txt') {
        $cpt = date('H:i:s', strtotime($x['TermEmit'])).' '.$cpt;
    }

    return $cpt;
}



/**
 * MKTplan(EPG): Get epg array, i.e. array with data for all mkt blocks in epg
 *
 * @param int $epgid EpgID
 * @param array $used_mktepg_blocks List of mktepg blocks which are already associated with mktplan blocks and therefore
 *                                  shouldn't be listed in association cbo or displayed as table rows
 * @param string $rtyp Return type ('idz' - Short list, just IDs)
 *
 * @return array $r Mkt-epg array
 * 'cbo' - used for sibling-suggest cbo (only in MKT_SYNC and in LINKING SIBLINGS (MKTPLAN-MKTEPG) modal)
 * 'list' - list of unassociated (i.e. not used) mktepg blocks, to be used in table
 * 'all' - used in mktepg_block_output()
 * 'slct' - will be passed to mktplan_epg_slct(), used only in MKT_SYNC
 * 'prevnext' - will be passed to mktplan_epg_slct(), used only in MKT_SYNC
 */
function mktepg_arr($epgid, $used_mktepg_blocks, $rtyp=null) {

    $r = [];

    define('EPG_FIELD', true);
    define('EPG_FIELD_CTRLZ', ((PMS_MKT_SYNC && MKTPLAN_TWINVIEW_TYP!=1) ? true : false));


    if ($rtyp=='idz') {

        $result_elem = qry('SELECT ID, NativeID, NativeType FROM epg_elements '.
            'WHERE EpgID='.$epgid.' AND NativeType IN (3,1,12,13) ORDER BY Queue');

        while ($elem = mysqli_fetch_assoc($result_elem)) {

            if ($elem['NativeType']==3) { // elements

                $r['epg'][] = $elem['ID'];

            } else { // fragments

                $scnrid = scnrid_universal($elem['NativeID'], $elem['NativeType']);

                $result_frag = qry('SELECT ID FROM epg_scnr_fragments WHERE ScnrID='.$scnrid.' AND NativeType=3 ORDER BY Queue');

                while ($frag = mysqli_fetch_assoc($result_frag)) {

                    $r['scnr'][] = $frag['ID'];
                }
            }
        }

        return $r;
    }


    $cnt = 1;

    $result_elem = qry('SELECT ID, NativeID, NativeType, TermEmit, IsActive, DurForc FROM epg_elements '.
        'WHERE EpgID='.$epgid.' AND NativeType IN (3,1,12,13'.((EPG_FIELD) ? ',4,5,9,14' : '').') '.
        'ORDER BY Queue');

    while ($elem = mysqli_fetch_assoc($result_elem)) {


        if (EPG_FIELD) {

            $epg_field[] = ['ID' => $elem['ID'], 'NativeType' => $elem['NativeType'], 'Term' => term2timehm($elem['TermEmit'])];

            if (in_array($elem['NativeType'], [4,5,9,14])) {
                continue;
            }
        }


        if ($elem['NativeType']==3) { // elements


            $elem['_CNT'] = $cnt++;

            $r['all']['epg'][$elem['ID']] = $elem;

            if (!MKT_SYNC && !empty($used_mktepg_blocks) && in_array($elem['ID'].'.1', $used_mktepg_blocks)) {
                continue; // If this mktepg block is already associated (= used)
            }


            $t_termemit = strtotime($elem['TermEmit']);

            $r['list'][date('H:i', $t_termemit).'.epg'] = $elem;

            $elem['BLC']['Caption']  = rdr_cell('epg_blocks', 'Caption', $elem['NativeID']);

            $r['cbo'][$elem['ID'].'.epg'] = mktepg_block_cpt($elem, 'epg', 'txt');

            if (MKT_SYNC && (empty($used_mktepg_blocks) || !in_array($elem['ID'].'.1', $used_mktepg_blocks))) {

                $r['slct']['epg'][term2timeint($t_termemit)] = $elem['ID'];
                // We have to use hhmmss instead of just hhmm, because it can happen (rarely though) that two epg mkt blocks
                // start in the same minute, e.g. one after another and the first one is 30 secs long..
                // NOTE: We must have the same code below, for the fragments..
            }


        } elseif (in_array($elem['NativeType'], [1, 12, 13])) { // fragments


            if ($elem['NativeType']==1) { // prog

                $elem['SCNRID'] = $elem['NativeID'];
                $elem['ProgID'] = rdr_cell('epg_scnr', 'ProgID', $elem['SCNRID']);

            } else { // film

                $elem['FILM'] = epg_film_reader($elem['NativeID']);
                $elem['SCNRID'] = $elem['FILM']['ScnrID'];
                $elem['ProgID'] = ($elem['NativeType']==12) ? 65535 : 65534;
            }

            if (EPG_FIELD) {
                end($epg_field);
                $epg_field[key($epg_field)]['ProgID'] = $elem['ProgID'];
                $epg_field[key($epg_field)]['ProgCPT'] = epgblock_cpt_scnr($elem, 'txt');
            }


            $result_frag = qry('SELECT ID, NativeID, NativeType, TermEmit, DurForc, ScnrID FROM epg_scnr_fragments '.
                'WHERE ScnrID='.$elem['SCNRID'].' AND NativeType=3 ORDER BY Queue');

            while ($frag = mysqli_fetch_assoc($result_frag)) {

                $frag['_CNT'] = $cnt++;

                $frag['TermEmit'] = add_dur2term($elem['TermEmit'], $frag['TermEmit']);
                $t_termemit = strtotime($frag['TermEmit']);

                $frag['ELEMENT'] = $elem;

                $r['all']['scnr'][$frag['ID']] = $frag;

                if (!MKT_SYNC && !empty($used_mktepg_blocks) && in_array($frag['ID'].'.2', $used_mktepg_blocks)) {
                    continue;
                }

                $r['list'][date('H:i', $t_termemit).'.scnr'] = $frag;

                $frag['BLC']['Caption']  = rdr_cell('epg_blocks', 'Caption', $frag['NativeID']);

                $r['cbo'][$frag['ID'].'.scnr'] = mktepg_block_cpt($frag, 'scnr', 'txt');

                if (MKT_SYNC && (empty($used_mktepg_blocks) || !in_array($elem['ID'].'.1', $used_mktepg_blocks))) {

                    $r['slct']['scnr'][$elem['ProgID']][term2timeint($t_termemit)] = $frag['ID'];
                    // NOTE: We have the same code above, for the elements..
                }
            }
        }
    }


    if (EPG_FIELD && !empty($epg_field)) {

        $positions = txarr('arrays', 'epg_mktblc_positions', null, 'lowercase');

        $epg_line_types = txarr('arrays', 'epg_line_types', null, 'uppercase');


        foreach ($epg_field as $k => $v) {


            if (in_array($v['NativeType'], [3,4,5,9,14])) { // For these types ProgCPT is still not set
                $v['ProgCPT'] = $v['Term'].' '.$epg_line_types[$v['NativeType']];
            }

            if (EPG_FIELD_CTRLZ) { // EPG_FIELD controls

                $btn_del = '<a class="epgsync_del" href="#" onclick="mktplan_epgsync_sw(this); return false;">'.
                    '<span class="glyphicon glyphicon-remove"></span></a>';

                $btn_ins = '<a class="epgsync_ins" href="#" onclick="mktplan_epgsync_sw(this); return false;">'.
                    '<span class="glyphicon glyphicon-arrow-right"></span></a>';

                $mkt_sync_epg[] = '<p class="nattyp'.$v['NativeType'].'" id="el'.$v['ID'].'">'.$btn_ins.$v['ProgCPT'].
                    (($v['NativeType']==3) ? $btn_del : '').'</p>';

            } else {

                $mkt_sync_epg[] = '<p class="nattyp'.$v['NativeType'].'"><span>'.$v['ProgCPT'].'</span></p>';
            }


            if (MKTPLAN_TWINVIEW_TYP!=1 && $v['NativeType']==3) {

                $prev = null;
                $next = null;

                if (isset($epg_field[$k-1]) && !in_array($epg_field[$k-1]['NativeType'], [3,4,5,9,14])) {
                    $prev = $epg_field[$k-1];
                }

                if (isset($epg_field[$k+1]) && !in_array($epg_field[$k+1]['NativeType'], [3,4,5,9,14])) {
                    $next = $epg_field[$k+1];
                }

                $mktepg = $v['ID'].'.epg';

                if ($prev) {
                    $r['prevnext'][$mktepg]['prev'] = $prev['ProgID'];
                    @$r['cbo'][$mktepg] .= ' ['.$positions[2].'] '.$prev['ProgCPT'];
                }

                if ($next) {
                    $r['prevnext'][$mktepg]['next'] = $next['ProgID'];
                    @$r['cbo'][$mktepg] .= ' ['.$positions[1].'] '.$next['ProgCPT'];
                }
            }
        }

        echo '<a id="mkt_sync_epg_btn" href="#" '.
            'onClick="display_swtch(document.getElementById(\'mkt_sync_epg_field\'), \'block\'); return false;">'.
            '<span class="glyphicon glyphicon-list"></span></a>';

        if (EPG_FIELD_CTRLZ) { // EPG_FIELD controls

            echo '<a id="mkt_sync_epg_submit" role="button" onClick="mktplan_epgsync_submit(this); return false;">'.
                '<span class="glyphicon glyphicon-save"></span></a>';

            form_ctrl_hidden('mkt_sync_delz', '');
            form_ctrl_hidden('mkt_sync_insz', '');
        }

        echo '<div id="mkt_sync_epg_field"'.((EPG_FIELD_CTRLZ) ? '' : ' class="ctrl_less"').'>'.
            implode('', $mkt_sync_epg).'</div>';
    }


    return $r;
}



/**
 * MKTplan(EPG): EPG Block output
 *
 * @param array $x For *regular* row: Mktepg-block (Element/Fragment) data; For *sibling* row: Mktplan-block data
 * @param array $opt Options
 * - ROW_TYP (string) - (sibling, regular) - Whether we are printing regular mktepg block row or a sibling within mktplan row
 * - SCH_TYP (string) - (epg, scnr) Only for *sibling*: value from $sibling['MktepgType']
 * - SiblingID (int) - Only for *sibling*: $sibling['MktepgID']
 * @param array $mktepgz Mkt-epg array
 *
 * @return array|void $x For *sibling* row: Mktepg-block (Element/Fragment) data;
 */
function mktepg_block_output($x, $opt, $mktepgz) {

    global $tx, $cfg;


    if (MKTPLAN_TWINVIEW_TYP!=0) return; // error-catcher.. Only SIBLINGS twinview uses/displays sibling data..


    if ($opt['ROW_TYP']=='sibling') {

        $r['BLOCK'] = $x['BLOCK']['Sibling'];

        foreach ($x['ITEMS'] as $v) {
            $r['SHORT']['PLAN'][] = $v['ItemID'];
        }

        $btn = pms_btn( // UNLINKING SIBLINGS (anchor)
            PMS_MKT_SYNC,
            '<span class="glyphicon glyphicon-link"></span><span class="glyphicon glyphicon-remove text-danger"></span>',
            [   'href' => 'epg.php?typ=epg&view=8&id='.EPGID.'&mktplan_unlink='.$x['BLOCK']['PlanCode'],
                'title' => $tx[SCTN]['LBL']['unlink'],
                'class' => 'text-info satrt opcty3 pull-right unlinker'    ],
            false
        );

        $mktepg = $mktepgz['all'][$opt['SCH_TYP']][$opt['SiblingID']];

        $mktepg['cbo'] = @$mktepgz['cbo'];

    } else { // non-associated mktepg

        $btn = pms_btn( // DELETE MKTEPG (anchor)
            PMS_MKT_SYNC,
            '<span class="glyphicon glyphicon-remove text-danger"></span>',
            [   'href' => 'epg.php?typ=epg&view=8&id='.EPGID.'&del_mktepg='.$x['ID'].'&del_schtyp='.$opt['SCH_TYP'].
                    (($opt['SCH_TYP']=='scnr') ? '&scnrid='.$x['ScnrID'] : ''),
                'title' => $tx['LBL']['delete'],
                'class' => 'text-info satrt opcty3 pull-right'    ],
            false
        );

        $mktepg = $x;
    }

    $mktepg = mktepg_block_data($mktepg, $opt['SCH_TYP']); // Add some block data, such as _Dur, _Caption, _CNT


    if (PMS_MKT_SYNC && $opt['ROW_TYP']=='sibling' && $mktepg['NativeID']) {  // EQUALING SIBLINGS (anchors)

        $href = 'epg.php?typ=epg&view=8&id='.EPGID.'&code='.$x['BLOCK']['PlanCode'].'&mktplan_equal=';

        $inter = '<a href="'.$href.'right"><span class="glyphicon glyphicon-arrow-right"></span></a><br>'.
            '<a href="'.$href.'left"><span class="glyphicon glyphicon-arrow-left"></span></a>';

    } else {

        $inter = '';
    }


    if ($opt['ROW_TYP']=='sibling') {

        $mktepg['TermHMS'] = date('H:i:s', strtotime($mktepg['TermEmit']));

        $t1 = timehms2timeint($mktepg['TermHMS']);
        $t2 = timehms2timeint($x['BLOCK']['BlockTermEPG']);

        $diff_hms = timeint_diff($t1, $t2, $x['BLOCK']['DateEPG']);
        $diff_s = dur2secs($diff_hms);

        if ($diff_s>600) { // 600 seconds, i.e. 10 minutes
            $css = ' err_warn';
        }

        // Regulation: Within SCNRz mkt blocks must have minimum distance of X minutes
        if ($x['BLOCK']['BlockPos']==0) {
            $css = mkt_time_distance($mktepg);
        }
    }


    // MKTEPG BLOCK HEADER row

    $r['HEADER_HTML'] =
        '<td class="equaling">'.$inter.'</td>'.
        '<td class="epg numero">'.$mktepg['_CNT'].'</td>'.
        '<td class="term'.((!empty($css)) ? $css : '').'">'.date('H:i:s', strtotime($mktepg['TermEmit'])).'</td>'.
        '<td class="dur">'.$mktepg['_Dur']['winner']['dur'].'</td>'.
        (($cfg[SCTN]['mktplan_sibling_tframes']) ? '<td class="tframe"></td>' : '').
        '<td class="epg cpt">'.$mktepg['_Caption'].$btn.'</td>';

    if ($opt['ROW_TYP']=='regular') {
        echo '<tr class="block"><td class="unequal" colspan="5"></td>'.$r['HEADER_HTML'].'</tr>';
    }


    // MKTEPG BLOCK ITEMS rows

    if ($mktepg['NativeID']) { // There IS an associated block

        $item_cnt = 1;

        $term = $mktepg['TermEmit'];

        $result = qry('SELECT ID FROM epg_cn_blocks WHERE BlockID='.$mktepg['NativeID'].' AND IsActive=1 ORDER BY Queue');

        while ($line = mysqli_fetch_assoc($result)) {

            $y = epg_spice_reader($line['ID']);

            if ($y['NativeType']==5) { // clips

                if ($y['DurForc']) {
                    $term = add_dur2term($term, $y['DurForc']);
                }

                continue;
            }

            if ($cfg[SCTN]['mktplan_sibling_tframes']) {
                $tcalc = mkt_timecalc($term, $y['DurForc'], $y['IsGratis'], 'list');
            }

            $err_css = ($opt['ROW_TYP']=='sibling' && !in_array($y['NativeID'], $r['SHORT']['PLAN'])) ? 'err_info' : '';

            $html =
                '<td class="equaling"></td>'.
                '<td class="epg numero '.$err_css.'"><div>'.$item_cnt++.'</div></td>'.
                '<td class="term">'.date('H:i:s', strtotime($term)).'</td>'.
                '<td class="dur">'.hms2ms($y['DurForc']).'</td>'.
                (($cfg[SCTN]['mktplan_sibling_tframes']) ?
                    '<td class="tframe '.$tcalc['css'].'">'.hms2ms($tcalc['out']).'</td>' : '').
                '<td class="epg cpt'.(($y['NativeType']==5) ? ' clp' : '').'">'.
                    mktplan_item_cpt_html($y['NativeID'], $y['Caption'], $y['NativeType']).
                '</td>';

            if ($opt['ROW_TYP']=='regular') {
                $html = '<tr class="item"><td class="unequal" colspan="5"></td>'.$html.'</tr>';
            }

            if ($y['DurForc']) {
                $term = add_dur2term($term, $y['DurForc']);
            }

            if ($opt['ROW_TYP']=='regular') {

                echo $html;

            } else { // sibling

                $r['ITEMS']['HTML'][] = $html;
                $r['ITEMS']['LIST'][] = $y;
            }
        }
    }


    if ($opt['ROW_TYP']=='sibling' && !empty($r)) {

        if (isset($r['ITEMS'])) {
            foreach ($r['ITEMS']['LIST'] as $v) {
                $r['SHORT']['EPG'][] = $v['NativeID'];
            }
        }

        return $r;
    }
}




/**
 * MKTplan(EPG): Print listing for date
 *
 * @param array $epg EPG data (we use ID, DateAir, ChannelID)
 * @return void
 */
function mktplan_epg_list($epg) {

    global $cfg, $tx;

    $show_mktepg = ($epg['ID'] && EPG_MKTVIEW==0) ? true : false; // If EPG exists


    $mktplanz = mktplan_epg_arr($epg);
    if (empty($mktplanz)) {
        echo '<div style="padding:30px 0">'.$tx['LBL']['noth'].'</div>';
        return;
    }

    if ($show_mktepg) {

        $used_mktepg_blocks = [];
        foreach ($mktplanz as $v) {
            if (!empty($v['BLOCK']['Sibling']['MktepgID'])) {
                $used_mktepg_blocks[] = $v['BLOCK']['Sibling']['MktepgID'].'.'.$v['BLOCK']['Sibling']['MktepgType'];
            }
        }

        $mktepgz = mktepg_arr($epg['ID'], $used_mktepg_blocks);

        $blocks = (!empty($mktepgz['list'])) ? array_merge($mktplanz, $mktepgz['list']) : $mktplanz;


        if (MKTPLAN_TWINVIEW_TYP==1) { // PROBE

            $mktplanz_diagnostic = mktsync_diagnostic($epg);

            $epg_line_types = txarr('arrays', 'epg_line_types', null, 'uppercase');

            $epg_mktblc_positions = txarr('arrays', 'epg_mktblc_positions', null, 'lowercase');
        }

    } else {

        $mktepgz = [];

        $blocks = $mktplanz;
    }

    uksort($blocks, 'epg_term_sort'); // We need the 00:00-05:59 to be sorted AFTER the 06:00-23:59


    $t_start_term = strtotime(epg_zeroterm($epg['DateAir']));

    $clip_opening_dur = rdr_cell('epg_clips', 'DurForc', 'CtgID=1 AND Placing IN (1,3)'); // mkt opening clip

    $mkt_positions = explode(',', $cfg[SCTN]['mktplan_positions']);


    // MKTPLAN CTRL BAR

    echo '<div class="row"><div class="btnbar mktplan col-sm-12">';

    // Search ctrl
    echo
        '<div class="form-group has-feedback search pull-left">'.
            '<input id="mktplan_search" type="text" class="form-control" onkeypress="mktplan_search_submit13(event);" '.
                'maxlength="60" placeholder="'.mb_strtoupper($tx['LBL']['search']).'" value="">'.
            '<span class="glyphicon glyphicon-search form-control-feedback"></span>'.
        '</div>';

    // Search reset btn
    echo '<a id="mktplan_search_reset" type="button" href="javascript:mktplan_search(\'reset\')" '.
        'class="invisible text-danger"><span class="glyphicon glyphicon-remove-sign"></span></a>';

    // Search results span
    echo '<span id="mktplan_search_result" class="invisible"></span>';

    // Buttons

    echo '<div class="pull-right btn-toolbar">';

    if (EPGID && EPG_MKTVIEW==0) {

        if (MKTPLAN_TWINVIEW_TYP!=0) {

            pms_btn( // BTN: SYNC-AUTO
                PMS_MKT_SYNC, '<span class="glyphicon glyphicon-flash"></span>',
                [   'href' => 'epg.php?typ=epg&view=8&id='.EPGID.'&mkt_sync_auto=1',
                    'class' => 'btn btn-info btn-sm',
                    'onclick' => 'link_disarm(this)'
                ]
            );

            pms_btn( // BTN: DEL_ONLY
                PMS_MKT_SYNC, '<span class="glyphicon glyphicon-remove"></span>',
                [   'href' => 'epg.php?typ=epg&view=8&id='.EPGID.'&mkt_sync_auto=del_only',
                    'class' => 'btn btn-info btn-sm',
                    'onclick' => 'link_disarm(this)'
                ]
            );
        }

        $mktplan_twinview_arr = txarr('arrays', 'epg_mktplan_twinview_types');

        echo '<div class="mktplan_twinviewtyp btn-group btn-group-sm text-uppercase pull-right">';

        foreach ($mktplan_twinview_arr as $k => $v) {

            $n = strpos($v, ' <span'); // tooltip for viewtyp buttons which have bs-glyph as caption
            if ($n) {
                $tooltip = substr($v, 0, $n);
                $v = substr($v, $n+1);
            } else {
                $tooltip = '';
            }

            $btn_attrz = [
                'href' => 'epg.php?typ=epg&view=8&id='.EPGID.'&mktplan_twinview_typ='.$k,
                'class' => 'btn btn-default'.((MKTPLAN_TWINVIEW_TYP==$k) ? ' active' : '')
            ];

            if ($tooltip) {
                $btn_attrz['title'] = $tooltip;
            }

            $pms = ($k==2) ? PMS_MKT_SYNC : true;

            pms_btn($pms, $v, $btn_attrz);
        }

        echo '</div>';
    }

    if (EPG_MKTVIEW==1) {
        pms_btn( // BTN: MKT_HOURLY
            true, '<span class="glyphicon glyphicon-time"></span>',
            [   'href' => '#',
                'onclick' => 'mkt_hourly_sw(this); return false;',
                'class' => 'btn btn-default btn-lg js_starter btn_size_tweak_lg2md'    ]
        );
    }

    echo '</div>';

    echo '</div></div>';



    echo '<div class="row"><table class="table table-condensed mktplan">';

    foreach ($blocks as $block_term => $x) {


        list(, $opt['SCH_TYP']) = explode('.', $block_term);

        $row_typ = ($opt['SCH_TYP']=='epg' || $opt['SCH_TYP']=='scnr') ? 'regular' : 'sibling';


        if (!MKT_SYNC) {

            if (isset($not_first)) {
                echo '<tr class="marginer"><td></td></tr>'; // Margin row
            } else {
                $not_first = true;
            }

        } else {

            if ($row_typ=='sibling') {

                if (isset($not_first)) {
                    echo '<tr class="marginer"><td></td></tr>'; // Margin row
                } else {
                    $not_first = true;
                }
            }
        }


        if ($row_typ=='regular') {


            $opt['ROW_TYP'] = $row_typ;

            if (MKTPLAN_TWINVIEW_TYP==0) {
                mktepg_block_output($x, $opt, $mktepgz);
            }


        } else { // sibling


            $mktplan_itemz = $x['ITEMS'];

            $sibling = ($show_mktepg) ? $x['BLOCK']['Sibling'] : null;

            if ($sibling) {

                $opt = [
                    'ROW_TYP' => $row_typ,
                    'SCH_TYP' => ($sibling['MktepgType']==1) ? 'epg' : 'scnr',
                    'SiblingID' => $sibling['MktepgID']
                ];

                if (MKTPLAN_TWINVIEW_TYP==0) {
                    $sibling = mktepg_block_output($x, $opt, $mktepgz);
                }
            }


            // SIGNALS (Block level)

            $err_css = '';

            if ($show_mktepg && MKTPLAN_TWINVIEW_TYP==0) {

                if (!$sibling) { // Block sibling missing

                    $err_css = 'err_dngr';

                } elseif ($sibling['SHORT']['PLAN']!=@$sibling['SHORT']['EPG']) { // Block sibling differs

                    $err_css = 'err_warn';
                }
            }


            /* BLOCK HEADER row */

            if (!isset($block_cnt)) {
                $block_cnt = 1;
            } else {
                $block_cnt++;
            }

            echo '<tr class="block" id="tr'.$x['BLOCK']['PlanCode'].'">';

            // MKTPLAN BLOCK cells

            echo
                '<td class="numero '.$err_css.'" '.((EPG_MKTVIEW==1) ? drg_attrz('mktepg', 0, 'item', true) : '').'>'.
                    $block_cnt.'</td>'.
                '<td class="term">'.$x['BLOCK']['BlockTermEPG'].'</td>'.
                '<td class="dur">'.$x['BLOCK']['_DUR'].'</td>'.
                ((MKTPLAN_TWINVIEW_TYP==0) ? '<td class="tframe"></td>' : '').
                '<td class="plan cpt">';

            if (EPG_MKTVIEW==1) {

                // Add NEW mktplanitem to this block
                pms_btn(PMS_MKT_MDF, '<span class="glyphicon glyphicon-plus-sign"></span>', [
                    'href' => 'mktplan_modify_single.php?source_id='.$mktplan_itemz[0]['ID'].'&case=block',
                    'class' => 'text-success satrt opcty3 pull-right',
                    'title' => $tx[SCTN]['LBL']['block_newitem']
                ]);

                $dsc_term = ($mktplan_itemz[0]['BlockTermEPG']) ? hms2hm($mktplan_itemz[0]['BlockTermEPG']) : null;

                // MDF ALL mktplanitems in this block
                modal_output('button', 'poster',
                    [
                        'onshow_js' => true,
                        'pms' => PMS_MKT_MDF,
                        'name_prefix' => 'mdfplan',
                        'button_css' => 'text-info satrt opcty3 pull-right',
                        'button_css_not_btn' => true,
                        'button_txt' => '<span class="glyphicon glyphicon-cog"></span>',
                        'button_title' => $tx['LBL']['modify'],
                        'data_varz' => [ // will be picked-up by JS:mktplan_modal_poster_onshow()
                            'vary_id' => $x['BLOCK']['PlanCode'],
                            'vary_dateepg' => $mktplan_itemz[0]['DateEPG'],
                            'vary_blocktermepg' => $dsc_term,
                            'vary_blockpos' => $mktplan_itemz[0]['BlockPos'],
                            'vary_blockprogid' => $mktplan_itemz[0]['BlockProgID'],
                            'vary_block_label' => $mktplan_itemz[0]['BLC_Label'], // used only for MDF-ALL
                            'vary_block_wrapclips' => intval($mktplan_itemz[0]['BLC_Wrapclips']), // used only for MDF-ALL
                            // vary_position, and vary_note are omitted, i.e. not used in mdf-all
                        ]
                    ]
                );

                // Show DUR ADDENDS (Split dur into ITEMS dur and CLIPS dur)

                $wrapclip_items = mkt_block_wrapclips([], $x['BLOCK']['BLC_Wrapclips']);

                $wrapclips_dur = ($wrapclip_items) ? mkt_block_dur($wrapclip_items): '00:00';

                $items_dur = substract_durs(['00:'.$x['BLOCK']['_DUR'], '00:'.$wrapclips_dur]);

                $items_cnt = count($mktplan_itemz).'+'.count($wrapclip_items);

                echo '<small class="lbl_right">'.$items_cnt.' ('.hms2ms($items_dur).' + '.$wrapclips_dur.')'.'</small>';
            }

            echo '<span class="cpt">'.$x['BLOCK']['cpt'].'</span>';
            echo '</td>';

            // MKTEPG BLOCK

            if ($show_mktepg) {

                if (MKTPLAN_TWINVIEW_TYP==0) { // SIBLINGS twinview

                    if (!$sibling) { // MKTEPG BLOCK missing: show ctrl for selecting it

                        $colspan = ($cfg[SCTN]['mktplan_sibling_tframes']) ? 5 : 4;

                        echo '<td class="equaling"></td><td class="epg unequal" colspan="'.$colspan.'">';

                        modal_output('button', 'poster', // LINKING SIBLINGS modal button
                            [
                                'onshow_js' => true,
                                'pms' => PMS_MKT_SYNC,
                                'name_prefix' => 'mkt_sibling',
                                'button_css' => 'text-info satrt opcty3',
                                'button_css_not_btn' => true,
                                'button_txt' => '<span class="glyphicon glyphicon-link"></span>',
                                'button_title' => $tx[SCTN]['LBL']['link'],
                                'data_varz' => [ // will be picked-up by JS:mkt_sibling_modal_poster_onshow()
                                    'vary_id' => $x['BLOCK']['PlanCode']
                                ]
                            ]
                        );

                        echo '</td>';

                    } else { // MKTEPG BLOCK (SIBLING) cells

                        echo $sibling['HEADER_HTML'];
                    }


                } elseif (MKTPLAN_TWINVIEW_TYP==2) { // SYNC


                    if (!empty($x['BLOCK']['Sibling'])) { // Sibling already selected

                        $css = 'simple';
                        $slcted = $x['BLOCK']['Sibling']['MktepgID'].'.'.
                            (($x['BLOCK']['Sibling']['MktepgType']==1) ? 'epg' : 'scnr');

                    } else { // Do suggest sync solution

                        list($slcted, $css) = mktplan_epg_slct($x['BLOCK'], @$mktepgz['slct'], @$mktepgz['prevnext']);
                    }


                    // CBO: SYNC_ADDBLOCK

                    if ($slcted=='new') {

                        // We want to show in advance what epg element will be used in SYNC_ADDBLOCK, thus we will fetch
                        // data (ID, term_scnr) about epg element

                        $element_data = mktsync_addblock_element(EPGID, $x['BLOCK']);

                        if ($element_data) {

                            // We pass along the results of the mktsync_addblock_element(), so we could skip calling the same
                            // function in mktsync_rcv(), i.e. in the sync procedure which follows the button click

                            $slcted = $element_data['ID'].'.new.'.$element_data['term_scnr'];


                            // Write element data beside the sign for SYNC_ADDBLOCK - [+]

                            $css = 'synco_new';

                            if (!empty($element_data['term_scnr'])) {
                                $element_data['term_scnr'] = '(+'.hms2hm($element_data['term_scnr']).')';
                            } else {
                                $element_data['term_scnr'] = '';
                            }

                            $cpt = scnr_cpt_get($element_data['ID'], 'arr');

                            $mktepgz['cbo_tmp'][$slcted] = '[+] '.$cpt['TermEmit_hhmm'].$element_data['term_scnr'].' '.
                                $cpt['Caption'];

                        } else {

                            $slcted = '';
                        }
                    }

                    $arr_cbo = (!empty($mktepgz['cbo'])) ? $mktepgz['cbo'] : [];

                    if (!empty($mktepgz['cbo_tmp'])) {

                        $arr_cbo = array_merge($arr_cbo, $mktepgz['cbo_tmp']);

                        unset($mktepgz['cbo_tmp']); // We are inside the loop, thus we have to reset this
                    }

                    $mktepgz_html = arr2mnu($arr_cbo, $slcted, $tx['LBL']['undefined']);

                    echo '<td class="epg '.$css.'">'.
                        '<select name="MKTEPG_CODE['.$x['BLOCK']['PlanCode'].']" class="form-control">'.
                            $mktepgz_html.'</select>'.
                        '</td>';

                    echo '<td class="epg '.$css.'">'.
                        '<input type="submit" class="btn btn-primary" value="" name="Submit_MKTSYNC" '.
                            'onclick="mkt_sync_submit_single('.$x['BLOCK']['PlanCode'].')">'.
                        '</td>';


                } elseif (MKTPLAN_TWINVIEW_TYP==1) { // PROBE


                    $typ = ($x['BLOCK']['BlockPos']) ? 'epg' : 'scnr';

                    $element = @$mktplanz_diagnostic[$typ][$x['BLOCK']['PlanCode']];

                    echo '<td class="epg '.(($element) ? 'cpt' : 'synco_none').'">';

                    if ($element) {

                        if ($typ=='scnr') {
                            list($element) = explode('.', $element);
                        }

                        $element = rdr_row('epg_elements', 'ID, TermEmit, NativeType', $element);

                        $cpt = [];

                        $cpt[] = '['.(($typ=='epg') ? $epg_mktblc_positions[1] : $epg_mktblc_positions[0]).']';

                        $cpt[] = term2timehm($element['TermEmit']);

                        if ($element['NativeType']!=1) {
                            $cpt[] = $epg_line_types[$element['NativeType']];
                        }

                        if (in_array($element['NativeType'], [1,12,13])) {
                            $cpt[] = scnr_cpt_get($element['ID']);
                        }

                        echo implode(' ', $cpt);
                    }

                    echo '</td>';
                }
            }

            echo '</tr>';


            $term = $epg['DateAir'].' '.$x['BLOCK']['BlockTermEPG'];

            // At the beginning of mktplan block, add dur of the opening wrapclip, unless it is omitted
            if (!in_array($x['BLOCK']['BLC_Wrapclips'], [2,3])) {
                $term = add_dur2term($term, $clip_opening_dur);
            }

            if ($t_start_term > strtotime($term)) {
                $term = date('Y-m-d H:i:s', strtotime($term.' +1 day'));
            }


            /* BLOCK ITEMS rows */

            $sibling_itemz_cnt = (isset($sibling['ITEMS']['LIST'])) ? count($sibling['ITEMS']['LIST']) : 0;
            $mktplan_itemz_cnt = count($mktplan_itemz);

            $itemz_cnt = ($mktplan_itemz_cnt > $sibling_itemz_cnt) ? $mktplan_itemz_cnt : $sibling_itemz_cnt;

            for ($i=0; $i<$itemz_cnt; $i++) {

                echo '<tr class="item"'.((($i < $mktplan_itemz_cnt) ? ' id="mpi'.$mktplan_itemz[$i]['ID'].'"' : '')).'>';

                if ($i < $mktplan_itemz_cnt) {

                    $mktplan_item = $mktplan_itemz[$i];

                    $mktitem = rdr_row('epg_market', 'Caption, DurForc, VideoID, AgencyID, IsBumper, IsGratis',
                        $mktplan_item['ItemID']);

                    if ($cfg[SCTN]['use_mktitem_video_id'] && $mktitem['VideoID']) {
                        $mktitem['Caption'] = $mktitem['Caption'].' '.$mktitem['VideoID'];
                    }

                    $mktitem['Caption'] = mkt_cpt_agency($mktitem['Caption'], $mktitem['AgencyID']);

                    if (MKTPLAN_TWINVIEW_TYP==0) {
                        $tcalc = mkt_timecalc($term, $mktitem['DurForc'], $mktitem['IsGratis'], 'epg');
                    }

                    $err_css = '';

                    if (MKTPLAN_TWINVIEW_TYP==0) {

                        // SIGNALS: Item position not correct

                        switch ($mktplan_item['Position']) {

                            case 0: //1
                                if ($i!=0) $err_css = 'err_warn';
                                break;

                            case 1: //2
                                if ($i!=1) $err_css = 'err_warn';
                                if ($i==0) $err_css = 'err_info'; // 1
                                break;

                            case 3: //-2
                                if ($i!=($mktplan_itemz_cnt-2)) $err_css = 'err_warn';
                                if ($i==0 || $i==($mktplan_itemz_cnt-1)) $err_css = 'err_info'; // 1 OR -1
                                break;

                            case 4: //-1
                                if ($i!=($mktplan_itemz_cnt-1)) $err_css = 'err_warn';
                                break;
                        }

                        // SIGNALS: Item missing in sibling

                        if ($sibling &&
                            (!isset($sibling['SHORT']['EPG']) || !in_array($mktplan_item['ItemID'], $sibling['SHORT']['EPG']))) {
                            $err_css = 'err_dngr';
                        }
                    }

                    // MKTPLAN ITEM cells

                    $term_html = date('H:i:s', strtotime($term));

                    $attrz = (PMS_MKT_MDF && EPG_MKTVIEW==1) ? drg_attrz('mktepg', ($block_cnt).'.'.$mktplan_item['ID']) : '';
                    // We add block numero to DRG ID, to prevent dropping to other (than parent) block

                    echo
                        '<td class="numero '.$err_css.'" '.$attrz.'>'.
                            $mkt_positions[$mktplan_item['Position']].'</td>'.
                        '<td class="term">'.$term_html.'</td>'.
                        '<td class="dur'.(($mktitem['DurForc']) ? '' : ' err_dngr').'">'.hms2ms($mktitem['DurForc']).'</td>'.
                        ((MKTPLAN_TWINVIEW_TYP==0) ? '<td class="tframe '.$tcalc['css'].'">'.hms2ms($tcalc['out']).'</td>' : '');


                    echo '<td class="plan cpt">';

                    echo mktplan_item_cpt_html($mktplan_item['ItemID'], $mktitem['Caption']);

                    if ($mktitem['IsBumper']) {
                        echo '<span>{'.$tx[SCTN]['LBL']['bumper'].'}</span>';
                    }

                    if ($mktitem['IsGratis']) {
                        echo '<span>{'.$tx[SCTN]['LBL']['gratis'].'}</span>';
                    }

                    // NOTE
                    if ($cfg[SCTN]['mktplan_use_notes']) {
                        $mktplan_item['Note'] = note_reader($mktplan_item['ID'], 3); // We can use MKT NativeID (3)
                        if ($mktplan_item['Note']) {
                            if (EPG_MKTVIEW==1) {
                                echo '<span class="note">'.$mktplan_item['Note'].'</span>';
                            } else {
                                echo '<span class="glyphicon glyphicon-exclamation-sign text-danger" '.
                                    'data-toggle="tooltip" data-placement="right" title="'.$mktplan_item['Note'].'"></span>';
                            }
                        }
                    }

                    if (EPG_MKTVIEW==1) {

                        if ($i!=0 && ($mktplan_item['BLC_Label'] || $mktplan_item['BLC_Wrapclips'])) {

                            // Show error if BLC data (BLC_Label, BLC_Wrapclips) is in any mktplanitem except FIRST

                            if ($mktplan_item['BLC_Label']) {
                                echo '<span class="note err_info">'.$mktplan_item['BLC_Label'].'</span>';
                            }

                            if ($mktplan_item['BLC_Wrapclips']) {
                                echo '<span class="note err_info">{}</span>';
                            }

                            log2file('srpriz',
                                ['type' => 'mktplan_blc_data', 'item' => $mktplan_item['ID'], 'block' => $x['BLOCK']['PlanCode']]);
                        }

                        // MKTPLANITEM DEL
                        mktplan_item_modaldel_btn(
                            $mktplan_item,
                            ['modaltext' => $term_html.' &mdash; '.$mktitem['Caption'], 'plancode' => $x['BLOCK']['PlanCode']],
                            'pull-right'
                        );

                        // MKTPLANITEM MDF
                        mktplan_item_modalmdf_btn($mktplan_item, 'pull-right');
                    }

                    echo '<span class="mktplan_search_blockcpt">'.$x['BLOCK']['cpt'].'</span></td>';

                } else { // MKTPLAN ITEM shortage

                    echo '<td class="unequal" colspan="5"></td>';
                }

                if ($show_mktepg) {

                    if ($i<$sibling_itemz_cnt) { // MKTEPG ITEM cells

                        echo $sibling['ITEMS']['HTML'][$i];

                    } else { // MKTEPG ITEM shortage

                        if (MKTPLAN_TWINVIEW_TYP==0) {

                            $colspan = ($cfg[SCTN]['mktplan_sibling_tframes']) ? 5 : 4;

                            echo '<td class="equaling"></td><td class="epg unequal" colspan="'.$colspan.'"></td>';
                        }
                    }
                }

                echo '</tr>';

                if ($mktitem['DurForc']) {
                    $term = add_dur2term($term, $mktitem['DurForc']);
                }
            }
        }


    }

    echo '</table></div>';


    if ($show_mktepg && MKTPLAN_TWINVIEW_TYP==0) {

        // LINKING SIBLINGS (MKTPLAN-MKTEPG) modal

        $mktepgz_html = (!empty($mktepgz['cbo'])) ? arr2mnu($mktepgz['cbo'], null, $tx['LBL']['undefined']) : '';

        modal_output('modal', 'poster',
            [
                'onshow_js' => 'mkt_sibling_modal_poster_onshow',
                'name_prefix' => 'mkt_sibling',
                'pms' => PMS_MKT_SYNC,
                'cpt_header' => $tx[SCTN]['LBL']['link'],
                'txt_body' =>
                    form_ctrl_hidden('mktplan_link_code', 0, false).
                    ctrl('modal', 'select', mb_strtoupper($tx[SCTN]['LBL']['mkt'].' '.$tx[SCTN]['LBL']['block']),
                        'MKTEPG_CODE', $mktepgz_html)
            ]
        );
    }


    if (MKT_SYNC) {

        form_ctrl_hidden('mkt_sync_code', '');

        echo '<div class="row">'.
            '<input type="submit" class="btn btn-primary btn-lg text-uppercase col-xs-12" '.
            'name="Submit_MKTSYNC" value="'.$tx['LBL']['save'].'">'.
            '</div>';
    }


    mktplan_hourly($t_start_term);
}



/**
 * MKTplan(EPG): Hourly table
 *
 * @param int $t_zerotime Zerotime stamp (passed form the parent function only to avoid repeating the calculation)
 *
 * @return void
 */
function mktplan_hourly($t_zerotime) {

    global $cfg;


    $mm_cutter = $cfg[SCTN]['mkt_timeframe_cutter_mm'];

    $mm_zerotime = date('i', $t_zerotime);

    if ($mm_cutter==$mm_zerotime) {

        $t_start = $t_zerotime;

        $term_start = date('Y-m-d H:i:s', $t_start);

    } else {

        $term_zero = date('Y-m-d H:i:s', $t_zerotime);

        $term_start = date('Y-m-d H:'.$mm_cutter.':00', strtotime($term_zero.' -1 hour'));

        $t_start = strtotime($term_start);
    }

    $t_fin = strtotime($term_start.' +1 day');


    $t = $t_start;

    while ($t <= $t_fin) {

        $hourz[] = date('dHi', $t);

        $t += 3600; // +1 hour
    }


    echo '<div class="row"><table class="table table-hover mkt_hourly hidden">';

    $tframe_durz = mkt_timecalc(null, null, null, 'hourly');

    foreach ($hourz as $k => $v) {


        if (isset($tframe_durz['epg'][$v])) {

            $z['s'] = $tframe_durz['epg'][$v];

            $z['mmss'] = substr(secs2dur($tframe_durz['epg'][$v]), 3);

            $z['percent'] = round($tframe_durz['epg'][$v]*100/360).'%';

            $z['faul'] = ($z['s']>$cfg[SCTN]['mkt_timeframe_limit_ss']) ? true : false;

        } else {

            $z = null;
        }


        echo '<tr'.(($z['faul']) ? ' class="faul"' : '').'>';


        echo '<td>'; // timeframe cell

        if ($k==0) {
            echo date('H:i', $t_zerotime);
        } else {
            $a = str_split($v, 2);
            echo $a[1].':'.$a[2];
        }

        echo ' - ';

        if (!isset($hourz[$k+1])) { // last
            echo date('H:i', $t_zerotime);
        } else {
            $a = str_split($hourz[$k+1], 2);
            echo $a[1].':'.$a[2];
        }

        echo '</td>';


        if (!$z) {
            echo '<td colspan="3">';
            continue;
        }


        echo '<td class="hourly_mmss">'.$z['mmss'].'</td>';

        echo '<td><div class="percent">'.$z['percent'].'</div></td>';

        echo '<td><div class="hourly wrap"><div class="hourly percent" style="width:'.$z['s'].'px">&nbsp;</div></div></td>';

        echo '</tr>';
    }

    echo '</table></div>';
}



/**
 * MKTplan(EPG): Get planz array for specific epg
 *
 * @param array $epg EPG data (we use DateAir, ChannelID)
 * @param string $rtyp Return type ('short' - Short list, just raw code for block)
 *
 * @return array $planz Plan array
 */
function mktplan_epg_arr($epg, $rtyp=null) {

    global $cfg;
    global $mktplanitem_order;

    $mkt_positions = explode(',', $cfg[SCTN]['mktplan_positions']);


    $arr_items = [];

    $result = qry('SELECT * FROM epg_market_plan WHERE DateEPG=\''.$epg['DateAir'].'\' AND ChannelID='.$epg['ChannelID'].
        ' ORDER BY '.$mktplanitem_order);

    while ($line = mysqli_fetch_assoc($result)) {
        $arr_items[$line['ID']] = $line; // ITEMS, unsorted
    }


    $arr_blocks = [];

    if ($rtyp=='short') {

        foreach ($arr_items as $v) {
            $code = hms2hm($v['BlockTermEPG']).'.'.$v['BlockPos'].'.'.$v['BlockProgID'];
            if (!in_array($code, $arr_blocks)) {
                $arr_blocks[] = $code;
            }
        }

        return $arr_blocks;
    }

    foreach ($arr_items as $v) {

        $code = hms2hm($v['BlockTermEPG']).'.'.$v['BlockPos'].'.'.$v['BlockProgID'];

        $arr_blocks[$code][] = $v; // BLOCKS
    }
    // Blocks array - all items belonging into same block are grouped into sub-arrays named by that block's term
    // (plus its BlockPos and BlockProgID, in order to differentiate blocks which accidentaly have same term).
    // But the items within blocks are not sorted (they need to be sorted by *Position* column)

    //echo '<pre>'; print_r($arr_blocks); exit;


    foreach ($arr_blocks as $k_block => $v_block) {

        // For each block, first we group items into sub-arrays named by *position* keys (because there might be
        // multiple items with the same position, e.g. more items which have 0 pos)

        $arr_pos = [];
        foreach ($v_block as $v_item) {
            $arr_pos[$v_item['Position']][] = $v_item;
        }

        // Then we loop through CFG positions array, and pick each of the sub-arrays named by *positions*,
        // and loop the items, saving them to new block array, which will be properly sorted by item positions

        $arr_block_new = [];

        foreach ($mkt_positions as $k_pos => $v_pos) {

            if (isset($arr_pos[$k_pos])) {

                foreach ($arr_pos[$k_pos] as $v_item) {

                    $arr_block_new[] = $v_item;
                }
            }
        }

        $arr_blocks[$k_block] = $arr_block_new; // Replace each block by the new block which has items sorted by position
    }
    // Blocks array - all items belonging into same block are grouped into sub-arrays named by that block's term,
    // and the items within blocks are sorted by *Position* column.

    //echo '<pre>'; print_r($arr_blocks); exit;


    /*// Go back to simple items array, but now the items are sorted by term and position.. Ura!
    $itemz = [];
    foreach ($arr_blocks as $v_block) {
        foreach ($v_block as $v_item) {
            $itemz[] = $v_item;
        }
    }*/


    // Add BLOCK data

    foreach ($arr_blocks as $k_block => $v_block) {

        $block = mktplan_block_data(current($v_block)); // Read block data from the first item (it is same in every item)

        $block['PlanCode'] = mktplan_blockcode_create($block);

        $block['_DUR'] = mkt_block_dur($v_block, $block['BLC_Wrapclips']);

        $block['Sibling'] = rdr_row('epg_market_siblings', 'MktepgID, MktepgType', 'MktplanCode='.$block['PlanCode']);

        $arr_blocks[$k_block] = ['ITEMS' => $v_block, 'BLOCK' => $block];
    }


    $planz = $arr_blocks;
    return $planz;
}




/**
 * MKTplan(EPG): Try to suggest the right cbo value for the sync
 *
 * @param array $block MKtplan Block data (BlockPos, BlockProgID, BlockTermEPG)
 * @param array $mktepgz_slct Mkt-epg array - subarray $mktepgz['slct'])
 * @param array $mktepgz_prevnext Mkt-epg array - subarray $mktepgz['prevnext'])
 *
 * @return array
 * - Code for the mktepg mkt fragment that should be selected (code format is: ID.schtyp), or just 'new'.
 * - Css class to be applied
 */
function mktplan_epg_slct($block, $mktepgz_slct, $mktepgz_prevnext) {

    global $cfg;

    static $arr_used = [];

    $b['schtyp'] = ($block['BlockPos']) ? 'epg' : 'scnr'; // BlockPos 0 is for BREAK, which means it is a scnr
    $b['progid'] = $block['BlockProgID'];
    $b['pos'] = $block['BlockPos'];

    $b['term'] = timehms2timeint($block['BlockTermEPG']);
    $b['term_before'] = timeint_calc($b['term'], $cfg[SCTN]['mkt_slct_float_t_before'], '-');
    $b['term_after'] = timeint_calc($b['term'], $cfg[SCTN]['mkt_slct_float_t_after'], '+');

    //if ($b['term']==60000) {echo '<pre>'; print_r($b); print_r($arr_epgz); exit;}


    // Loop the mktepgz in cbo and pick the one with the closest term

    $arr = ($b['schtyp']=='epg') ? @$mktepgz_slct[$b['schtyp']] : @$mktepgz_slct[$b['schtyp']][$b['progid']];

    $slcted = null;

    if ($arr) {

        foreach ($arr as $k => $v) {

            $v .= '.'.$b['schtyp']; // Code for the mktepg mkt elem/frag that should be selected (Code format is: ID.schtyp)

            if (in_array($v, $arr_used)) { // Skip the ones which we have already picked for some of the previous CBOs
                continue;
            }

            // Pick the values which are close enough to the mktplan term
            if ($k >= $b['term_before'] && $k <= $b['term_after']) {
                $picks[] = $v;
            }
        }

        if (!empty($picks)) {

            if (count($picks)==1) { // If there is just one value, select it

                $slcted = $picks[0];

            } else {

                foreach ($picks as $k => $v) { // Pick the first one with matching BlockPos and BlockProgID

                    if (@$mktepgz_prevnext[$v][(($b['pos']==1) ? 'next' : 'prev')]==$b['progid']) {
                        $slcted = $v;
                        break;
                    }
                }

                if (empty($slcted)) { // Otherwise just pick the first one
                    $slcted = $picks[0];
                }
            }
        }
    }


    if ($slcted) {

        $arr_used[] = $slcted;

        $css = 'synco_a';

        // If BlockPos and BlockProgID dont match, then mark the selection with other color
        if ($b['pos']!=0) {
            if (@$mktepgz_prevnext[$slcted][(($b['pos']==1) ? 'next' : 'prev')]!=$b['progid']) {
                $css = 'synco_b';
            }
        }

    } else {

        if ($b['schtyp']=='scnr') {
            $slcted = 'new';
        }

        $css = 'synco_none';
    }


    return [$slcted, $css];
}


