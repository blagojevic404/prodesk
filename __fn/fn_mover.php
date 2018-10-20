<?php
/**
 * MOVER functions.
 * Mover is used for story cut/copy, and for scnr import, i.e. import of stories from one scnr to another.
 * Also used (only epg calendar function) in epg archive page and epg_new page (copy from existing epg).
 *
 * SCNR IMPORT:
 * Calendar is in the first frame (i.e. first we choose epg).
 * Click on a date (i.e. epg) opens the second frame which lists all of the scenarios for that epg.
 * Click on a scnr then opens the third frame, with checklist of all importable fragments (i.e. stories) of that scnr.
 *
 * STORY CUT/COPY uses only first two frames (epg calendar + scnr list).
 *
 * EPG ARCHIVE/NEW uses only first frame (epg calendar).
 */





/**
 * Print epg calendar list. (Revolver for epg calendar function.)
 *
 * @param string $casetyp (epg_archive, epg_new).
 * - epg_archive: list all epgs in *archive* (i.e. older than today).
 * - epg_new: list all epgs, both past and future, because we want to make a copy of an epg.
 * @param string $layout Layout: (horizontal, vertical).
 * @param int $from_chnl ChannelID. Only for *epg_new* casetype, i.e. *copy epg* from another channel.
 *
 * @return void
 */
function epg_calendar_list($casetyp, $layout='horizontal', $from_chnl=0) {

    if (!$from_chnl) {
        $from_chnl = CHNL;
    }

    // Get oldest epg-date from db. We will use it as DATE START in archive calendar.
    $date_start = rdr_cell('epgz', 'DateAir', 'ChannelID='.$from_chnl.' AND IsTMPL=0', 'DateAir ASC');
    if (!$date_start) return; // If there aren't ANY dates, then no use in continuing..

    if ($casetyp=='epg_archive') { // We will show links on epg-dates until *yesterday*

        $date_end = date('Y-m-d', strtotime('-1 day'));

    } else { // We will show links on ALL epg-dates (both past and future) until newest epg-date from db

        $date_end = rdr_cell('epgz', 'DateAir', 'ChannelID='.$from_chnl.' AND IsTMPL=0', 'DateAir DESC');
    }

    $ts_start    = strtotime($date_start);
    $ts_end      = strtotime($date_end);

    $month_start = date('Y-m-01', $ts_start);
    $month_end   = date('Y-m-01', $ts_end);


    $month_cur = $month_end; // We are showing a DESC month list. (Note: For ASC month list, look into git commits history.)

    while ($month_cur >= $month_start){

        $opt = [
            'month' => $month_cur,
            'layout' => $layout,
            'chnlid' => $from_chnl,
            'ts_end' => $ts_end,
        ];

        mover_calendar($casetyp, $opt);

        $month_cur = date('Y-m-d', strtotime('-1 month', strtotime($month_cur))); // Decrement the month
    }
}




/**
 * Print mover calendar - i.e. table with epgz days, in a calendar.
 *
 * @param string $casetyp Casetype
 *  - framer: calendar is in the first frame, click on any epg-date link, opens the specified epg in the second frame.
 *            (used for story cut/copy and scnr import)
 *  - epg_archive, epg_new: simple epg calendar list (no moving); called from epg_calendar_list().
 * @param array $opt
 *  - month (string): Month, i.e. *first* day of the month, in yyyy-mm-dd format
 *  - layout (string): Layout: (horizontal, vertical). Def: horizontal.
 *  - chnlid (int): ChannelID. (For using epgs from another channel.)
 *  - ts_end (string): End date timestamp. Used only in *epg_archive* case, in order to put *links* only on *past* dates.
 *  - scnr_epgid (int): EpgID of the SCNR we are coming from. Used in *framer* casetype, simply in order to
 *                      visually emphasize the epg from which the scnr originates.
 *  - prev_next (bool): Whether to show prev/next controls
 *  - href_query (string): Used in *framer* case, to pass data to ajax (_aj_mover.php).
 *                         Has these attrz: act(cut,copy,import), objid(scnrid for import, stryid for stry cut/copy).
 *
 * @return void
 */
function mover_calendar($casetyp, $opt) {

    global $tx;

    if (empty($opt['layout'])) $opt['layout'] = 'horizontal';

    if (empty($opt['chnlid'])) $opt['chnlid'] = CHNL;

    if (!isset($opt['prev_next'])) {
        $opt['prev_next'] = ($casetyp=='framer') ? true : false;
    }

    if ($casetyp=='epg_archive' && empty($opt['ts_end'])) {
        $date_end = rdr_cell('epgz', 'DateAir', 'ChannelID='.$opt['chnlid'].' AND IsTMPL=0', 'DateAir DESC');
        $opt['ts_end'] = strtotime($date_end);
    }


    $weekdays = ($opt['layout']=='vertical') ? $tx['DAYS']['wdays_short'] : $tx['DAYS']['wdays'];

    $month_cur = $date_cur = $opt['month'];
    // First day of the month. We will use $month_cur to check whether the month is finished.

    $ts_cur = strtotime($date_cur);

    $t_yy   = date('Y', $ts_cur);
    $t_mm   = date('n', $ts_cur);
    $t_wday = date('N', $ts_cur); //1-7


    echo '<table id="epgcalendar" class="table table-default table-responsive '.
        (($opt['layout']=='vertical') ? 'vert' : 'hor').'"><tr>';


    // HEADER

    if ($opt['layout']=='vertical') {

        echo '<td class="head" colspan="7">';

        if ($opt['prev_next']) {
            mover_prevnext($month_cur, $opt);
        }

        echo '<h3>'.$tx['DAYS']['months'][$t_mm].' <small>'.$t_yy.'</small></h3>';

        echo '</td>';

        // For VERTICAL layout, month cell is in a ROW above, otherwise it is in a COLUMN on the left
        if ($opt['layout']=='vertical') echo '</tr><tr>';

    } else { // horizontal

        if ($opt['prev_next']) {
            echo '<td colspan="8">';
            mover_prevnext($month_cur, $opt);
            echo '</td></tr><tr>';
        }

        echo '<td class="col-xs-3"><h3>'.$tx['DAYS']['months'][$t_mm].'</h3><p>'.$t_yy.'</p></td>';
    }


    // Weekdays cell
    echo '<td class="col-xs-3">';
    foreach ($weekdays as $k => $v) {
        echo '<div'.(($k==7) ? ' style="color:red"' : '').'>'.$v.'</div>';
    }
    echo '</td>';

    // Week-dates cell
    echo '<td class="col-xs-1 date" data-type="epg">';

    // Add empty date divs (if any) at the beggining of the month
    for ($i=1; $i<$t_wday; $i++) {
        echo '<div>&nbsp;</div>';
    }

    $date_cols = 1;

    // If looping date hasn't looped out into next month, which would mean we have to wrap up and go to next month..
    while (date('Y-m-01', $ts_cur) == $month_cur) {

        $t_dd   = date('j', $ts_cur); // day of month (1-31)
        $t_wday = date('N', $ts_cur); // ISO day of week (1-7)

        // Get the EPG ID, we need it for the link
        $epgid = epgid_from_date($date_cur, $opt['chnlid']);

        // If we have EPG for that day, we will put link, if not we won't
        $href = '';
        if ($epgid) {

            switch ($casetyp) {

                case 'framer':
                    $href = '#';
                    break;

                case 'epg_archive':
                    $href = ($ts_cur > $opt['ts_end']) ? '' : 'epg.php?typ=epg&id='.$epgid;
                    break;

                case 'epg_new': // EPG NEW (COPY FROM EXISTING EPG)
                    $href = 'epg_modify_multi.php?typ=epg&id='.$epgid.'&d='.EPG_NEW;
                    break;
            }
        }

        if ($href) {

            $attr = '';

            // Marking selected epg-date with a special format. (Used only in *framer* casetype.)
            $css = (!empty($opt['scnr_epgid']) && $epgid==$opt['scnr_epgid']) ? 'class="sel" ' : '';

            if ($casetyp=='framer') {

                $ajax['type'] = 'GET';
                $ajax['url'] = '/_ajax/_aj_mover.php';
                $ajax['fn'] = 'mover_success';
                $ajax['data'] = 'typ=epg&epgid='.$epgid.$opt['href_query'];

                $attr = ajax_onclick($ajax);
            }

            echo '<a '.$attr.$css.'href="'.$href.'">'.$t_dd.'</a>';

        } else {

            echo '<div>'.$t_dd.'</div>';
        }

        // Closing week-dates cell and starting another.
        // No need to do it, if this is last day in a month (date('t'))
        if ($t_wday==7 && ($t_dd != date('t', $ts_cur))) {
            echo '</td><td class="col-xs-1 date" data-type="epg">';
            $date_cols++;
        }

        // Increment the date
        $date_cur = date('Y-m-d', strtotime('+1 day', $ts_cur));
        $ts_cur = strtotime($date_cur);
    }

    // Add empty date divs (if any) at the end of the month
    for ($i=7; $i>$t_wday; $i--) {
        echo '<div>&nbsp;</div>';
    }

    echo '</td>'; // Closing week-dates cell

    // Add extra 1 or 2 empty column(s) at the end because number of weeks differs between months, and we always want
    // to have the SAME number (6) of week columns in the table.
    // (BS grid sum is: 3 + 3 + x*1 where 4<=x<=6, i.e. there may be 4-6 week columns)
    if ($date_cols==4) echo '<td class="col-xs-1 date"></td><td class="col-xs-1 date"></td>';
    if ($date_cols==5) echo '<td class="col-xs-1 date"></td>';

    echo '</tr></table>';
}




/**
 * Print mover epg - i.e. simplified (scnr-only) epg.
 *
 * @param int $epgid EPG ID
 * @param string $casetyp Casetype
 *  - cutcopy: story cut/copy - links point to stry_mover.php script
 *  - import: scnr import - links point to another frame (ajax)
 *  - linker: epg link - links point to js
 * @param string $href_query Data to pass along to another step.
 *                           For stry cutcopy: id(stryid), act(cut,copy); for scnr import: objid(scnrid)
 * @return void
 */
function mover_epg($epgid, $casetyp, $href_query=null) {

    echo '<div id="moverepg"><div class="wrap">';

    // Select elements of PROG type (NativeType=1) which belong to specified epg.
    $result = qry('SELECT ID, NativeID, Queue, TimeAir, TermEmit FROM epg_elements WHERE EpgID='.$epgid.
        ' AND NativeType=1 ORDER BY Queue');

    while ($x = mysqli_fetch_assoc($result)) {

        $x['DateAir'] = rdr_cell('epgz', 'DateAir', $epgid);

        $x['PRG'] = qry_assoc_row('SELECT ProgID, Caption FROM epg_scnr WHERE ID='.$x['NativeID']);

        $x['PRG']['ProgCPT'] = prgcpt_get($x['PRG']['ProgID'], $x['PRG']['Caption']);

        // If there is no fixed term, we have to calculate it
        if (!strtotime($x['TimeAir'])) {
            $x['TimeAir'] = element_start_calc($epgid, $x['Queue'], $x['DateAir'], $x['TermEmit']);
        }

        $t = term2timehm($x['TimeAir']);

        echo '<div data-type="scnr">';

        echo '<span class="term">'.$t.'</span>';

        switch ($casetyp) {

            case 'cutcopy':

                $href = 'stry_mover.php?scnrid='.$x['NativeID'].$href_query;
                $attr = 'onclick="this.style.pointer-events=none);"'; // prevent double_click

                break;

            case 'import':

                $href = '#';

                $ajax['type'] = 'GET';
                $ajax['url'] = '/_ajax/_aj_mover.php';
                $ajax['fn'] = 'mover_success';
                $ajax['data'] = 'typ=scnr&act=import&elmid='.$x['ID'].$href_query;

                $attr = ajax_onclick($ajax);

                break;

            case 'linker':

                $href = '#';

                $r = "['".$x['ID']."','14','".$t."','".$x['PRG']['ProgCPT']."']"; // '14' is for linker linetype
                $attr = 'onclick="window.parent.window.parent.ifrm_result('.$r.');return false;"';

                break;
        }

        echo '<a '.$attr.' href="'.$href.'">'.$x['PRG']['ProgCPT'].'</a>';

        echo '</div>';
    }

    echo '</div></div>';
}




/**
 * Print mover scnr - i.e. checklist of all importable fragments (i.e. stories) of that scnr
 *
 * @param int $source_elmid ElementID for SOURCE scnr
 * @param int $target_elmid ElementID for TARGET scnr
 * @param bool $editor_pms Whether user is the editor. If he is then we import stories to scnr and open mdf_multi script,
 *                         otherwise we import as *pending* stories (epg_mover script).
 * @return void
 */
function mover_scnr($source_elmid, $target_elmid, $editor_pms) {

    global $tx;


    $stryz = [];

    $scnr_id = scnrid_prog($source_elmid);

    $result = qry('SELECT ID FROM epg_scnr_fragments WHERE ScnrID='.$scnr_id.' AND NativeType=2 ORDER BY Queue'); // only stry

    while (list($xid) = mysqli_fetch_row($result)) {

        $x = fragment_reader($xid, 0, 0, 0, 'import'); // IMPORT return type will give us shorter version of fragment array

        $stryz[] = '<li class="list-group-item checkbox '.(($x['IsActive']) ? '' : 'sleepline').'">'.
            '<label><input type="checkbox" name="fragz['.$xid.']" id="fragz['.$xid.']" value="1">'.$x['Caption'].'</label>'.
            '</li>';
    }


    echo '<div id="moverscnr">';

    if ($stryz) {

        if ($editor_pms) {
            $href = 'epg_modify_multi.php?typ=scnr&id='.$target_elmid.'&import_id='.$source_elmid;
        } else {
            $href = 'epg_mover.php?act=import&scnrid='.scnrid_prog($target_elmid);
        }

        echo '<form action="'.$href.'" method="post" enctype="multipart/form-data" id="form1" name="form1">';

        echo '<ul class="list-group importer" id="importer">';

        echo implode('', $stryz);

        echo '</ul>';

        form_btnsubmit_output('Submit_EPG_FRM', ['type' => 'btn_only', 'css' => 'btn-sm']);

        echo '</form>';

    } else {

        echo  '<h3>'.$tx['LBL']['noth'].'</h3>';
    }

    echo '</div>';
}



/**
 * Print prev/next controls
 *
 * @param string $month_cur Current month (i.e. *first* day of the month, in yyyy-mm-dd format)
 * @param array $opt Data passed from mover_calendar(). Used attributes: chnlid, href_query.
 * @return void
 */
function mover_prevnext($month_cur, $opt) {

    $date_start = rdr_cell('epgz', 'DateAir', 'ChannelID='.$opt['chnlid'].' AND IsTMPL=0', 'DateAir ASC');
    $date_end   = rdr_cell('epgz', 'DateAir', 'ChannelID='.$opt['chnlid'].' AND IsTMPL=0', 'DateAir DESC');

    $ts_start = strtotime($date_start);
    $ts_end   = strtotime($date_end);

    $month_prev_ts = strtotime('-1 month', strtotime($month_cur));
    $month_prev = date('Y-m-d', $month_prev_ts);

    $month_next_ts = strtotime('+1 month', strtotime($month_cur));
    $month_next = date('Y-m-d', $month_next_ts);

    echo '<div class="pull-right prevnext" data-type="calendar">';

    $dis = ($month_prev_ts >= $ts_start) ? '' : ' disabled';

    $ajax['type'] = 'GET';
    $ajax['url'] = '/_ajax/_aj_mover.php';
    $ajax['fn'] = 'mover_success';
    $ajax['data'] = 'typ=calendar&month='.$month_prev.'&chnlid='.$opt['chnlid'].$opt['href_query'];

    echo '<a type="button" class="btn btn-default btn-xs js_starter'.$dis.'" href="#" '.ajax_onclick($ajax).'>'.
        '<span class="glyphicon glyphicon-chevron-left"></span></a>';

    $dis = ($month_next_ts <= $ts_end) ? '' : ' disabled';

    $ajax['data'] = 'typ=calendar&month='.$month_next.'&chnlid='.$opt['chnlid'].$opt['href_query'];

    echo '<a type="button" class="btn btn-default btn-xs js_starter righty'.$dis.'" href="#" '.ajax_onclick($ajax).'>'.
        '<span class="glyphicon glyphicon-chevron-right"></span></a>';

    echo '</div>';
}


