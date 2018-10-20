<?php

// dsk program & teams






/**
 * PROGRAM reader
 *
 * @param int $id Program ID
 *
 * @return array $x
 */
function prgm_reader($id) {

    $tbl = 'prgm';

    if ($id) {
        $x = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$id);
    }

    $x['ID']  = intval(@$x['ID']);
    $x['TYP'] = 'prgm';
    $x['TBL'] = $tbl;
    $x['TBLID'] = tablez('id', $x['TBL']);


    if (!$x['ID']) {

        $x['ProdType'] = 1;
        $x['TEAM']['ChannelID'] = (!empty($_GET['chnl'])) ? intval($_GET['chnl']) : CHNL;

        return $x;
    }

    $x['TEAM'] = rdr_row('prgm_teams', '*', $x['TeamID']);

    $x['SETS'] = rdr_row('prgm_settings', '*', $x['ID']);

    $x['CRW'] = crw_reader($x['ID'], $x['TBLID']);

    return $x;
}








/**
 * TEAM reader
 *
 * @param int $id Team ID
 *
 * @return array $x
 */
function team_reader($id) {

    $tbl = 'prgm_teams';

    if ($id) {
        $x = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$id);
    }

    $x['ID']  = intval(@$x['ID']);
    $x['TYP'] = 'tmz';
    $x['TBL'] = $tbl;
    $x['TBLID'] = tablez('id', $x['TBL']);


    if (!$x['ID']) {

        $x['ChannelID'] = (!empty($_GET['chnl'])) ? intval($_GET['chnl']) : CHNL;

        return $x;
    }

    $x['CRW'] = crw_reader($x['ID'], $x['TBLID']);

    return $x;
}









/**
 * Builds programs list (clone) for epg MDF MULTI.
 *
 * This list will be cloned (via JS ifrm_starter()) to iframe window when user clicks on prog ifrm label.
 *
 * @param int $chn_id Channel ID
 * @return void
 */
function epg_prg_list($chn_id) {

    $cln_cnt = 0;
    $bg_switch = false;

    // Get the TEAMS array
    $tmz_arr = rdr_cln('prgm_teams', 'Caption', 'ChannelID='.$chn_id, 'Queue, ID ASC');


    echo '<table><tr>';

    // Loop teams array
    foreach ($tmz_arr as $k => $v) {

        // Get the PROGS array for each team
        $progs_arr = prg_array('from_team', $k);

        if (!$progs_arr) {
            continue;
        }

        // Output cell with all progs for current team
        echo '<td width="10%" class="'.(($bg_switch) ? 'even' : 'odd').'"><div class="title">'.$v.'</div>';
        foreach ($progs_arr as $prg_k =>$prg_v) {
            echo
                '<div id="prgid'.$prg_k.'">'.
                '<a href="#" onClick="window.parent.ifrm_result(['."'".$prg_k."','1','','".$prg_v."'".']);return false;">'.
                $prg_v.'</a>'.
                '</div>';
        }
        echo '</td>';

        $cln_cnt++;
        $bg_switch = !$bg_switch;

        if ($cln_cnt==4) { // tr width is 4 cells
            $cln_cnt = 0;
            $bg_switch = !$bg_switch;
            echo '</tr><tr>';
        }

    }

    echo '</tr></table>';
}






/**
 * Output programs(shows) cbo control
 *
 * @param string $name Name to be used for control *name* and *id* attributes
 * @param int $sel Selected key
 * @param array $opt Options:
 * - chnl (int): Channel ID
 * - cpt (string): Caption for zero value
 * - submit (bool): Whether to use onChange event to submit form
 * - output (bool): Whether to output the result
 * - req (bool): Whether to add *required* attribute
 * - attr (string): Additional attributes
 * - typ (string): Type (for mktplan, mktplan_master we show additional lines)
 *
 * @return string Stringed html for programs cbo control
 */
function ctrl_prg($name='', $sel=0, $opt=null) {

    global $tx;

    if (!isset($opt['typ'])) $opt['typ'] = null;

    $opt['chnl'] = (empty($opt['chnl'])) ? CHNL : intval($opt['chnl']);

    $opt['cpt'] = (empty($opt['cpt'])) ? mb_strtoupper($tx['LBL']['program']) : $opt['cpt'];

    $mktplan = (in_array($opt['typ'], ['mktplan', 'mktplan_master'])) ? true : false;

    $progs_arr = prg_array('from_channel', $opt['chnl'], (($mktplan) ? ['cyr2lat' => true] : null));


    $r = [];

    if (empty($opt['typ'])) {

        $r[] = '<option value="">'.$opt['cpt'].'</option>';

    } elseif ($mktplan) {

        $epg_line_types = txarr('arrays', 'epg_line_types', null, 'uppercase');

        $arr_spec = [
            0 => '*'.mb_strtoupper($opt['cpt']),
            65535 => '*'.$epg_line_types[12],
            65534 => '*'.$epg_line_types[13]
        ];

        $progs_arr = $arr_spec + $progs_arr;
    }

    foreach ($progs_arr as $k => $v) {
        $r[] = '<option value="'.$k.'"'.(($k==$sel) ? ' selected' : '').'>'.$v.'</option>';
    }

    $r = '<select '.(($opt['typ']!='mktplan_master') ? 'name="'.$name.'"' : '').' id="'.$name.'" class="form-control"'.
            (empty($opt['submit']) ? '' : ' onChange="submit()"').
            (empty($opt['req']) ? '' : ' required').
            (empty($opt['attr']) ? '' : ' '.$opt['attr']).
            (($opt['typ']!='mktplan_master') ? '' : ' onchange="mdfplan_master(this)"').
        '>'.
        implode('', $r).'</select>';

    if (empty($opt['output'])) {
        return $r;
    } else {
        echo $r;
        return null;
    }
}



/**
 * Fetch all programs for specified channel or team
 *
 * @param string $typ Type: (from_channel, from_team)
 * @param int $id Channel/Team ID
 * @param array $opt Options data
 * - cyrtolat (bool): CyrToLat conversion
 *
 * @return array $progs_arr Array of all programs for specified Channel/Team
 */
function prg_array($typ, $id, $opt=null) {

    global $cfg;

    $sql =
        'SELECT ID, Caption FROM prgm WHERE IsActive=1 AND '.
        (($typ=='from_channel') ? 'TeamID IN (SELECT ID FROM prgm_teams WHERE ChannelID='.$id.')' : 'TeamID='.$id).
        ' ORDER BY Caption ASC';

    $progs_arr = qry_numer_arr($sql);

    if (@$cfg['cyr2lat'] && in_array(LNG, [2,4])) {
        $opt['cyr2lat'] = true;
    }

    if (!empty($opt['cyr2lat'])) {
        foreach ($progs_arr as $k => $v) {
            $progs_arr[$k] = text_convert($v, 'cyr', 'lat');
        }
    }

    return $progs_arr;
}



/**
 * Fetch program caption
 *
 * @param int $id Program ID
 * @param array $opt Options:
 * - typ (string): Type (mktplan)
 *
 * @return string Program caption
 */
function prg_caption($id, $opt=null) {

    global $cfg, $tx;


    if ($opt['typ']!='mktplan') {

        $r = rdr_cell('prgm', 'Caption', $id);

    } else {

        $film_types = txarr('arrays', 'film_types', null, 'uppercase');

        switch ($id) {

            case 0:
                $r = mb_strtoupper($tx['LBL']['program']);
                break;

            case 65535:
                $r = $film_types[1];
                break;

            case 65534:
                $r = $film_types[3];
                break;

            default:
                $r = rdr_cell('prgm', 'Caption', $id);
        }
    }


    if (@$cfg['cyr2lat'] && in_array(LNG, [2,4])) {
        $r = text_convert($r, 'cyr', 'lat');
    }

    return $r;
}



/**
 * PROGRAM permission check for MDF and RCV pages
 *
 * @param array $x Prgm array/object
 * @param string $typ Page type (mdf, dtl)
 *
 * @return void
 */
function prgm_pms($x, $typ='mdf') {


    define('PMS_MDF', (($x['ID']) ? pms('dsk/prgm', 'mdf', $x) : true));

    define('PMS_MDF_CRW', (($x['ID']) ? pms('dsk/prgm', 'mdf_crw', $x) : true));

    define('PMS_MDF_WEB', (($x['ID']) ? pms('dsk/prgm', 'mdf_web', $x) : true));


    if ($x['ID']) { // MDF

        if (!PMS_MDF_CRW && !PMS_MDF_WEB && !PMS_MDF) {

            if ($typ=='mdf') {

                redirector('access', 'list_prgm.php');
            }

        } else {

            define('PMS', true); // We need this for btn_output('mdf') on DETAILS page
        }

    } else { // NEW

        pms('dsk/prgm', 'new', $x, true);
    }
}

