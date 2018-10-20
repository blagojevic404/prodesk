<?php
/** Library of functions for output (simple print out) of epgZ. Types: plan, archive, tmpl. */






/**
 * Prints table with epgz days, for PLAN
 *
 * Used only in EPG PLANS page.
 *
 * @return void
 */
function epgz_plan_html() {

    global $tx, $cfg;

    // Number of days to show in the list.
    $days_limit = $cfg[SCTN]['epg_plan_days_limit'];


    $dates = [];
    for ($i=0; $i<$days_limit; $i++) { // show days from TODAY to TODAY+$days_limit
        $dates[] = date('Y-m-d', strtotime('+'.$i.' day'));
    }

    $where = [];
    $where[] = 'ChannelID='.CHNL;
    $where[] = 'IsTMPL=0';

    $arr_status = lng2arr($tx['LBL']['opp_ready']);


    echo '<ul id="ulgrid">';

    foreach ($dates as $v) {

        $tstamp = strtotime($v);
        $t_mm   = date('n', $tstamp);
        $t_dd   = date('j', $tstamp);
        $t_wday = date('N', $tstamp);

        $line = qry_assoc_row('SELECT * FROM epgz WHERE DateAir=\''.$v.'\' AND '.implode(' AND ', $where));

        $href = (@$line['ID']) ? 'epg.php?typ=epg&id='.$line['ID'] : '';
        // We will use this variable several times afterwards, whenever we want to check if epg for this date
        // exists in the database


        echo '<li'.((!$line['IsReady'] && $line['ID']) ? ' class="epg_ready_not"' : '').'>';

        if ($href) echo '<a href="'.$href.'" class="title">';

        echo
            '<div class="title">'.
                '<span class="dd">'.$t_dd.'</span>'.
                '<div class="rowspan">'.
                    '<div class="month">'.$tx['DAYS']['months'][$t_mm].'</div>'.
                    '<div class="weekday">'.$tx['DAYS']['wdays_short'][$t_wday].'</div>'.
                '</div>'.
            '</div>';

        if ($href) echo '</a>';

        echo '<div class="controls">';

        if ($href) { // epg exists

            pms_btn(    // BTN: MODIFY EPG
                PMS, '<span class="glyphicon glyphicon-cog"></span>',
                [   'href' => 'epg_modify_multi.php?typ=epg&id='.$line['ID'],
                    'title' => $tx['LBL']['modify'],
                    'class' => 'btn btn-default btn-xs'    ]
            );

            pms_btn(    // BTN: IsReady
                PMS, '<span class="glyphicon glyphicon-'.(($line['IsReady']) ? 'ban-circle' : 'ok').'"></span>',
                [   'href' => '?sw_ready='.$line['ID'],
                    'title' => mb_strtoupper($arr_status[1-intval($line['IsReady'])]),
                    'class' => 'btn btn-default btn-xs'    ]
            );

            pms_btn(    // BTN: INFO
                PMS, '<span class="glyphicon glyphicon-info-sign"></span>',
                [   'href' => 'javascript:return false;',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'title' => $tx['LBL']['author'].': <b>'.uid2name($line['UID']).'</b><br>'.
                        $tx['LBL']['update'].': <b>'.date('Y/m/d H:i', strtotime($line['TermMod'])).'</b>',
                    'class' => 'btn btn-default btn-xs'    ]
            );

        } else { // no epg

            pms_btn(    // BTN: START EMPTY EPG
                PMS, '<span class="glyphicon glyphicon-file"></span>',
                [   'href' => 'epg_modify_multi.php?typ=epg&chnl='.CHNL.'&d='.$v,
                    'title' => $tx[SCTN]['LBL']['start_empty_epg'],
                    'class' => 'btn btn-default btn-xs'    ]
            );

            pms_btn(    // BTN: COPY ANOTHER EPG
                PMS, '<span class="glyphicon glyphicon-duplicate"></span>',
                [   'href' => 'epg_new.php?fromtyp=exst&d='.$v,
                    'title' => $tx[SCTN]['LBL']['copy_another_epg'],
                    'class' => 'btn btn-default btn-xs'    ]
            );

            pms_btn(    // BTN: IMPORT EPG TEMPLATE
                PMS, '<span class="glyphicon glyphicon-import"></span>',
                [   'href' => 'epg_new.php?fromtyp=tmpl&d='.$v,
                    'title' => $tx[SCTN]['LBL']['import_epg_tmpl'],
                    'class' => 'btn btn-default btn-xs'    ]
            );
        }

        echo '</div>';

        echo '</li>';
    }

    echo '</ul>';
}












/**
 * Prints listing of templates.
 *
 * Used on TEMPLATES page (listing), and NEW (i.e. COPY FROM TMPL) page.
 *
 * @param string $typ Type: (epg, scn). Whether it is EPG or SCN templates.
 * @param string $action Action type: (list, new). Whether function is invoked for ARCHIVE or NEW (i.e. COPY FROM TMPL) page.
 *
 * @return void
 */
function epgz_tmpl_html($typ, $action='list') {

    global $tx;

    echo '<table class="table table-hover">';

	if ($typ=='epg') {

		$where = [];
		$where[] = 'ChannelID='.CHNL;
		$where[] = 'IsTMPL=1';

        // EPG templates are stored in epgz table, just as ordinary epgz, except they don't have DateAir and their IsTMPL is 1.
		$sql = 'SELECT * FROM epgz WHERE '.implode(' AND ', $where).' ORDER BY TermMod DESC';

        $result = qry($sql);

        if ($result) {
            list($num_rows) = mysqli_fetch_row(mysqli_query($GLOBALS["db"], 'SELECT FOUND_ROWS()'));
        } else {
            $num_rows = 0;
        }

        if ($num_rows) {

            echo
                '<thead><tr>'.
                '<th>'.$tx['LBL']['id'].'</th>'.
                '<th>'.$tx['LBL']['title'].'</th>'.
                '<th>'.$tx['LBL']['author'].'</th>'.
                '<th>'.$tx['LBL']['update'].'</th>'.
                '</tr></thead>';

        } else {

            echo $tx['LBL']['noth'];
        }

		while ($line = mysqli_fetch_assoc($result)) {

			if ($action=='list') {
				$href = 'epg.php?typ=epg&view=1&id='.$line['ID']; // display EPG
			} else { // new
				$href = 'epg_modify_multi.php?typ=epg&id='.$line['ID'].'&d='.EPG_NEW; //  NEW EPG via COPY TMPL
			}

            echo
            '<tr>'.
            '<td>'.$line['ID'].'</td>'.
            '<td style="font-weight:bold"><a href="'.$href.'">'.rdr_cell('epg_templates', 'Caption', $line['ID']).'</a></td>'.
            '<td>'.uid2name($line['UID'], ['n1_typ'=>'init']).'</td>'.
            '<td>'.date("Y-m-d H:i", strtotime($line['TermMod'])).'</td>'.
            '</tr>';
		}


	} else { // SCNR


        if ($action=='list') {

            $tmpl_prg = @$_SESSION['tmpl_prg'];

        } else {

            $scnr_id = scnrid_prog(SCNELMID);

            $tmpl_prg = rdr_cell('epg_scnr', 'ProgID', $scnr_id);
        }

        $clnz = ['epg_elements.ID', 'epg_elements.DurForc', 'epg_elements.IsActive', 'epg_scnr.ProgID', 'epg_scnr.Caption'];

        $sql = 'SELECT SQL_CALC_FOUND_ROWS '.implode(', ', $clnz).' FROM epg_elements'.
            ' INNER JOIN epg_scnr ON epg_scnr.ID = epg_elements.NativeID'.
            ' WHERE epg_elements.EpgID IS NULL AND AttrA='.CHNL.(($tmpl_prg) ? ' AND epg_scnr.ProgID='.$tmpl_prg : '').
            ' ORDER BY epg_elements.ID DESC';

        $result = qry($sql);

        if ($result) {
            list($num_rows) = mysqli_fetch_row(mysqli_query($GLOBALS["db"], 'SELECT FOUND_ROWS()'));
        } else {
            $num_rows = 0;
        }

        if ($num_rows) {

            echo
                '<thead><tr>'.
                '<th>'.$tx['LBL']['id'].'</th>'.
                '<th>'.$tx['LBL']['title'].'</th>'.
                '<th>'.$tx['LBL']['program'].'</th>'.
                '<th><i>'.$tx['LBL']['default'].'</i></th>'.
                '</tr></thead>';

        } else {

            echo $tx['LBL']['noth'];
        }

        while ($line = mysqli_fetch_assoc($result)) {
	
			if ($action=='list') {
				$href = 'epg.php?typ=scnr&view=1&id='.$line['ID']; // Display SCN
			} else { // new
				$href = 'epg_modify_multi.php?typ=scnr&id='.SCNELMID.'&tmpl='.$line['ID']; //  NEW SCN via COPY TMPL
			}

            $prog_tmpl_id = rdr_cell('prgm_settings', 'EPG_TemplateID', $line['ProgID']);

            echo
            '<tr>'.
            '<td>'.$line['ID'].'</td>'.
            '<td style="font-weight:bold"><a href="'.$href.'">'.$line['Caption'].'</a></td>'.
            '<td>'.prg_caption($line['ProgID']).'</td>'.
            '<td>'.(($line['ID']==$prog_tmpl_id) ? '<span class="glyphicon glyphicon-thumbs-up"></span>' : '').'</td>'.
            '</tr>';
		}

    }

    echo '</table>';
}




/**
 * Gets ID of the REAL epg, i.e. TODAY's epg.
 *
 * @return int Epg ID
 */
function get_real_epgid() {

    $dater = get_real_dateair();

    $id = epgid_from_date($dater, CHNL);

    return intval($id);
}




/**
 * Gets DATEAIR for the REAL epg, i.e. TODAY's epg.
 *
 * @return string DateAir
 */
function get_real_dateair() {

    global $cfg; // for 'zerotime'

    // if CURRENT hms is less than CFG_ZEROTIME then running date is -1. (EPG day doesn't start at 00:00, but at CFG_ZEROTIME.)
    $dater = (date('H:i:s') < $cfg['zerotime']) ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d');

    return $dater;
}


