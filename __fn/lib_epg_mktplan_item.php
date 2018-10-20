<?php





/**
 * Get MKTplan items list
 *
 * @param int $id MKT Item ID
 * @param string $date_start Start date
 *
 * @return array $r MKTplan list
 */
function mktplan_item_arr($id, $date_start=null) {

    $r = [];

    $where = [];
    $where[] = 'ItemID='.$id;

    if ($date_start) {
        $where[] = 'DateEPG>=\''.$date_start.'\'';
    }

    $result = qry('SELECT * FROM epg_market_plan WHERE '.implode(' AND ', $where).' ORDER BY DateEPG ASC, BlockTermEPG ASC');
    while ($line = mysqli_fetch_assoc($result)) {
        $r[$line['ID']] = $line;
    }

    return $r;
}




/**
 * MKTplan: Print listing for item
 *
 * @param int $id MKT Item ID
 * @param int $chnlid MKT Item channel ID
 * @param string $typ (list, replace)
 * @return void
 */
function mktplan_item_list($id, $chnlid, $typ='list') {

    global $tx, $cfg;

    $mkt_positions = explode(',', $cfg[SCTN]['mktplan_positions']);


    $planz = mktplan_item_arr($id, mktplan_item_list_start($typ));

    if (empty($planz)) {
        return;
    }


    echo '<table class="table table-hover table-condensed listingz"'.(($typ=='replace') ? ' id="replace_tbl"' : '').'>';

    echo
        '<tr>'.
        '<th class="col-xs-1">'.
            (($typ=='replace') ? '<input type="checkbox" value="1" onClick="chk_all(\'replace_tbl\', this)">' : '').
        '</th>'.
        '<th class="col-xs-2">'.$tx['LBL']['epg'].'</th>'.
        '<th class="col-xs-7">'.$tx[SCTN]['LBL']['block'].'</th>'.
        '<th class="col-xs-1">&nbsp;#</th>'.
            (($typ=='list') ? '<th class="col-xs-1"></th>' : '').
        '</tr>';


    $ord = 0;

    foreach ($planz as $k => $item) {

        $block = mktplan_block_data($item, false);

        $block['cpt'] = [];
        $block['cpt']['title'] = $block['Title'];

        if (!$item['BlockProgID']) {

            $blc_data = mktplan_items($block, 'blcdata_row');

            if ($blc_data['BLC_Label']) {
                $block['cpt']['blc_label'] = '<span class="note blc">'.$blc_data['BLC_Label'].'</span>';
            }
        }

        if ($cfg[SCTN]['mktplan_use_notes']) {

            $mktplan_note_typ = 3; // We can use MKT NativeID as it is not otherwise used, i.e. MKT doesn't use notes.
            $item['Note'] = note_reader($item['ID'], $mktplan_note_typ);

            if (!empty($item['Note'])) {
                $block['cpt']['note'] = '<span class="note">'.$item['Note'].'</span>';
            }
        }

        echo '<tr>'.
            '<td>'.
                (($typ=='list') ? ++$ord : '<input name="mpi['.$item['ID'].']" type="checkbox" value="1">').
            '</td>'.
            '<td><a href="epg.php?typ=epg&view=8&dtr_date='.$item['DateEPG'].'&dtr_chnl='.$chnlid.'#mpi'.$item['ID'].'">'.
                $item['DateEPG'].'</a></td>'.
            '<td>'.implode(' ', $block['cpt']).'</td>'.
            '<td class="td_listing_pos">'.sprintf('%2s', $mkt_positions[$item['Position']]).'</td>';

        if ($typ=='list') {

            echo '<td class="td_listing_ctrl">';

            // MKTPLANITEM MDF
            mktplan_item_modalmdf_btn($item);

            // MKTPLANITEM DEL
            mktplan_item_modaldel_btn($item, ['modaltext' => $item['DateEPG'].' &mdash; '.$block['Title']]);

            echo '</td>';
        }

        echo '</tr>';
    }


    echo '</table>';
}





/**
 * MKTplan: Print MODIFY-MULTI table
 *
 * @param array $x MKT Item
 * @return void
 */
function mktplan_item_mdf($x) {

    global $tx;


    echo '<table class="table table-hover table-condensed mktplan_mdfmulti" id="mdf_tbl">';
    echo
        '<tr>'.
        '<th style="width:40px"></th>'. // ord
        '<th style="width:20px"></th>'. // chk
        '<th>'.$tx['LBL']['epg'].'</th>'.
        '<th style="width:90px">'.$tx[SCTN]['LBL']['block'].'</th>'.
        '<th style="width:110px"></th>'.
        '<th style="width:250px"></th>'.
        '<th style="width:160px"></th>'.
        '<th style="width:190px">'.$tx[SCTN]['LBL']['in_block_pos'].'</th>'.
        '<th style="width:60px"></th>'.
        '</tr>';


    $planz = mktplan_item_arr($x['ID'], mktplan_item_list_start('mdf'));

    if (!empty($planz)) { // MDF

        foreach ($planz as $v) {

            mktplan_item_rowmdf('loop', $v, $x['ChannelID']);
        }

        $t_last = strtotime($v['DateEPG']) + 86400; // + 1 day
        $t_expire = strtotime($x['DateExpire']);
        $t_start = strtotime($x['DateStart']);

        if ($t_start < $t_last) $t_start = $t_last;

        if ($t_start < time()) $t_start = time();

        if ($t_start < $t_expire) { // By changing DateStart/DateExpire user can add new empty rows to mdf table

            $date_start = date('Y-m-d', $t_start);

            mktplan_item_rowmdf_loop($date_start, $x['DateExpire'], $x['ChannelID']);
        }

    } else { // NEW: table with rows for each date in the range

        if ($x['DateStart'] && $x['DateExpire']) {

            mktplan_item_rowmdf_loop($x['DateStart'], $x['DateExpire'], $x['ChannelID']);
        }
    }

    // ADD button
    echo '<tr><td colspan="9">'.
        '<button type="button" id="btn_new" class="btn btn-default col-xs-12 opcty2" onclick="mdfplan_add(this);">'.
        '<span class="glyphicon glyphicon-plus-sign"></span></button>'.
        '</td></tr>';

    mktplan_item_rowmdf('master', null, $x['ChannelID']);

    echo '</table>';


    echo '<table id="clone[0]" style="display:none">';

    mktplan_item_rowmdf('cloner', null, $x['ChannelID']);

    echo '</table>';
}



/**
 * MKTplan: Print one row, i.e. item, in MODIFY-MULTI table
 *
 * @param string $typ (loop, master, cloner)
 * @param array $item MKTplan Item
 * @param int $chnl Channel ID (used for *select-prg* ctrl, to fetch progs for specific channel)
 *
 * @return void
 */
function mktplan_item_rowmdf($typ, $item=null, $chnl) {

    global $tx, $cfg;

    $mkt_positions = explode(',', $cfg[SCTN]['mktplan_positions']);


    if (!isset($item['Position'])) {
        $item['Position'] = array_search(0, $mkt_positions); // zero position
    }

    if ($typ=='loop') {
        static $ord;
        $ord++;
    }

    if (!empty($item['BlockTermEPG'])) {
        $item['BlockTermEPG'] = hms2hm($item['BlockTermEPG']);
    }


    echo '<tr'.(($typ=='master') ? ' id="master_controls"' : '').'>';


    // ID
    if ($typ!='master') {
        form_ctrl_hidden('ID[]', @$item['ID']);
    }


    // ORDINAL label

    echo '<td class="ordinal">';
    if ($typ=='loop') {
        echo $ord;
    }
    echo '</td>';


    // CHKBOX for master controls
    echo '<td>'.
            '<input type="checkbox" value="1"'.(($typ=='master') ? ' onClick="chk_all(\'mdf_tbl\', this)"' : '').'>'.
        '</td>';


    // DateEPG (txt)
    echo '<td class="dateepg">';
    if ($typ!='master') {
        $req = ($typ=='loop') ? 'required' : null;
        ctrl('form', 'textbox', $tx['LBL']['epg'], 'DateEPG[]', @$item['DateEPG'], $req,
            ['nowrap' => true, 'txt_nolabel' => true]);
    }
    echo '</td>';


    // BlockTermEPG (txt)
    echo '<td class="termepg">';
    echo '<input type="text" maxlength="5" class="form-control" data-ctrltyp="termepg"'.
            (($typ=='loop') ? ' required' : null).
            (($typ!='master') ? ' name="BlockTermEPG[]"' :
                ' onchange="mdfplan_master(this)" onkeypress="return mdfplan_keyhandler(this)"').
            ' placeholder="'.$tx['LBL']['term'].'" value="'.@$item['BlockTermEPG'].'">';
    echo '</td>';


    // BLOCK-POS
    echo '<td>';
    echo '<select class="form-control" data-ctrltyp="blockpos" '.
        (($typ!='master') ? 'name="BlockPos[]"' : 'onchange="mdfplan_master(this)"').'>'.
        arr2mnu(txarr('arrays', 'epg_mktblc_positions'), @$item['BlockPos']).'</select>';
    echo '</td>';


    // BLOCK-PROGID/FILM/SERIAL
    echo '<td>';
    ctrl_prg('BlockProgID[]', @$item['BlockProgID'],
        [
            'chnl' => $chnl,
            'output' => true,
            'typ' => (($typ!='master') ? 'mktplan' : 'mktplan_master'),
            'attr' => 'data-ctrltyp="blockprogid"'
        ]);
    echo '</td>';


    // BLC_Label_new (txt)
    echo '<td class="blc_label">';
    if ($typ!='master') {
        ctrl('form', 'textbox', $tx['LBL']['note'].' ('.$tx[SCTN]['LBL']['block'].')', 'BLC_Label_new[]',
            null, 'maxlength="45"'.(($typ=='cloner') ? '' : ' style="visibility: hidden"'), ['nowrap' => true, 'txt_nolabel' => true]);
    }
    echo '</td>';


    // Position in block (radio group)

    echo '<td>';
    if ($typ!='master') {
        echo '<input type="hidden" data-ctrltyp="position" name="Position[]" value="'.$item['Position'].'">'; // Shadow submitter
    }
    echo '<div class="btn-group btn-group-justified" data-toggle="buttons">';

    foreach ($mkt_positions as $k => $v) {

        $actv = ($typ!='master' && $item['Position']==intval($k));

        echo
            '<label '.
            (($typ!='master') ? 'onclick="btngroup_shadow(this);"' : 'data-ctrltyp="position" onchange="mdfplan_master(this)"').
            ' class="btn btn-default'.(($actv) ? ' active' : '').'">'.
            '<input type="radio" value="'.$k.'"'.(($actv) ? ' checked' : '').'>'.$v.
            '</label>';
    }

    echo '</div>';
    echo '</td>';


    // BTN CTRLZ
    echo '<td class="td_listing_ctrl">';
    if ($typ!='master') {
        echo '<a href="#" class="text-muted opcty2" onclick="mdfplan_del(this); return false;">'.
            '<span class="glyphicon glyphicon-remove"></span></a>';
        echo '<a href="#" class="text-muted opcty2" onclick="mdfplan_double(this); return false;">'.
            '<span class="glyphicon glyphicon-duplicate"></span></a>';
    }
    echo '</td>';


    echo '</tr>';
}



/**
 * MKTplan: Print empty rows in MODIFY-MULTI table
 *
 * @param string $date_start Start date
 * @param string $date_finit Finish date
 * @param int $chnl Channel ID
 * @return void
 */
function mktplan_item_rowmdf_loop($date_start, $date_finit, $chnl) {

    $i = $date_start;

    while ($i) {

        mktplan_item_rowmdf('loop', ['DateEPG' => $i], $chnl);

        if ($i==$date_finit) {
            break;
        } else {
            $i = date('Y-m-d', strtotime($i.' +1 day'));
        }
    }
}



/**
 * MKTplan: Output modal for mktitemplan mdf
 *
 * @param int $chnl Channel ID (used for *select-prg* ctrl, to fetch progs for specific channel)
 *
 * @return void
 */
function mktplan_item_modalmdf($chnl) {

    global $tx, $cfg;

    $mkt_positions = explode(',', $cfg[SCTN]['mktplan_positions']);


    $html_modal =

        form_ctrl_hidden('MKTPLAN_MDFID', 0, false).

        ctrl('modal', 'textbox', $tx['LBL']['epg'], 'DateEPG', null, 'required', ['txt_nolabel' => true]).

        '<label class="control-label">'.$tx[SCTN]['LBL']['block'].'</label>'.
        '<div class="well block_ctrl">'.

            ctrl('modal', 'textbox', $tx['LBL']['term'], 'BlockTermEPG', null, 'required maxlength="5"', ['txt_nolabel' => true]).

            ctrl('modal', 'radio', null, 'BlockPos', null, txarr('arrays', 'epg_mktblc_positions')).

            ctrl('modal', 'select-prg', null, 'BlockProgID', null, $chnl, ['prg-mktplan' => true]).

            ctrl('modal', 'textbox', $tx['LBL']['note'].' ('.$tx[SCTN]['LBL']['block'].')',
                'BLC_Label_new', null, 'maxlength="45"', ['txt_nolabel' => true]).

        '</div>';


    // Wrap the controls which we want to show (via js) only in MDF-ALL case

    $html_modal .=

        '<div id="div_switcher_mdfall">'.

            ctrl('modal', 'select', $tx[SCTN]['LBL']['wrapclips'], 'BLC_Wrapclips',
                arr2mnu(txarr('arrays', 'epg_mktplan_wrapclips'))).

            ctrl('modal', 'textbox', $tx['LBL']['note'], 'BLC_Label', null, null, ['txt_nolabel' => true]).

        '</div>';


    // Wrap the controls which we want to hide (via js) in MDF-ALL case

    $html_modal .= '<div id="div_switcher_item">';

    $html_modal .= ctrl('modal', 'radio', $tx[SCTN]['LBL']['in_block_pos'], 'Position', null, $mkt_positions);

    if ($cfg[SCTN]['mktplan_use_notes']) {
        $html_modal .= ctrl('modal', 'textbox', $tx['LBL']['note'], 'Note', null, null, ['txt_nolabel' => true]);
    }

    $html_modal .= '</div>';


    modal_output('modal', 'poster',
        [
            'onshow_js' => 'mktplan_modal_poster_onshow',
            'name_prefix' => 'mdfplan',
            'pms' => PMS_MKT_MDF,
            'modal_size' => 'modal-sm',
            'cpt_header' => $tx['LBL']['modify'],
            'txt_header' => '<a id="new_window" class="pull-right opcty3 satrt">'.
                '<span class="glyphicon glyphicon-new-window"></span></a>',
            'txt_body' => $html_modal
        ]
    );
}



/**
 * MKTplan: Print modal BUTTON for mktitemplan mdf
 *
 * @param array $item MKTplan Item
 * @param string $css Additional css class
 *
 * @return void
 */
function mktplan_item_modalmdf_btn($item, $css=null) {

    global $tx;

    $dsc_term = ($item['BlockTermEPG']) ? hms2hm($item['BlockTermEPG']) : null;

    $data_varz = [
        'vary_id' => $item['ID'],
        'vary_dateepg' => $item['DateEPG'],
        'vary_blocktermepg' => $dsc_term,
        'vary_blockpos' => $item['BlockPos'],
        'vary_blockprogid' => $item['BlockProgID'],
        'vary_position' => $item['Position'],
        'vary_note' => @$item['Note']
    ];

    modal_output('button', 'poster',
        [
            'onshow_js' => true,
            'pms' => PMS_MKT_MDF,
            'name_prefix' => 'mdfplan',
            'button_css' => 'text-info satrt opcty3 '.$css,
            'button_css_not_btn' => true,
            'button_txt' => '<span class="glyphicon glyphicon-cog"></span>',
            'button_title' => $tx['LBL']['modify'],
            'data_varz' => $data_varz // will be picked-up by JS:mktplan_modal_poster_onshow()
        ]);
}



/**
 * MKTplan: Print modal BUTTON for mktitemplan del
 *
 * @param array $item MKTplan Item
 * @param array $opt Optional data
 *  - modaltext
 *  - plancode (used only when called from epg, for afterwards scroll-down to tr)
 * @param string $css Additional css class
 *
 * @return void
 */
function mktplan_item_modaldel_btn($item, $opt=null, $css=null) {

    global $tx;


    $submiter_href = 'delete.php?typ=mkt_plan&id='.$item['ID'];

    if (defined('EPGID')) { // called from epg list

        $submiter_href .= ((EPGID) ? '&epgid='.EPGID : '&dtr_date='.$_GET['dtr_date'].'&dtr_chnl='.$_GET['dtr_chnl']).
            '&plancode='.($opt['plancode']);

    } else { // called from mktitem list

        $submiter_href .= '&mktid='.$item['ItemID'];
    }


    modal_output('button', 'deleter',
        [
            'onshow_js' => true,
            'pms' => PMS_MKT_MDF,
            'button_css' => 'text-danger satrt opcty3 '.$css,
            'button_css_not_btn' => true,
            'button_title' => $tx['LBL']['delete'],
            'data_varz' => [ // will be picked-up by JS:modal_del_onshow()
                'vary_modaltext' => $tx['MSG']['del_quest'].': '.$opt['modaltext'],
                'vary_submiter_href' => $submiter_href
            ]
        ]);
}




/**
 * MKTplan: Item caption, for the epg table
 *
 * @param int $item_id Spice item id
 * @param string $item_cpt Spice item caption
 * @param int $nattyp Spice item native type (3-mkt, 5-clp)
 *
 * @return string $r Spice item caption html
 */
function mktplan_item_cpt_html($item_id, $item_cpt, $nattyp=3) {

    $href = 'spice_details.php?sct='.(($nattyp==3) ? 'mkt' : 'clp').'&typ=item&id='.$item_id;

    $r = '<a href="'.$href.'"><small>'.sprintf('%04s', $item_id).'</small></a>'.
        '<a href="'.$href.'" class="cpt">'.$item_cpt.'</a>';

    // JS: mktplan_search() relies on this html code. It uses such querySelector: 'td.cpt a.cpt'..

    return $r;
}



/**
 * Get start date for mktplan item list
 *
 * @param string $typ (list, replace, mdf)
 *
 * @return string $date_start Start date
 */
function mktplan_item_list_start($typ) {

    switch ($typ) {

        case 'list': // first day of previous month
            $date_start = (empty($_GET['show_all'])) ? date('Y-m-01', strtotime('-1 month')) : null; break;

        case 'replace': // today
            $date_start = DATENOW; break;

        case 'mdf': // one month ago
            $date_start = date('Y-m-d', strtotime('-1 month')); break;

        default:
            $date_start = null;
    }

    return $date_start;
}


