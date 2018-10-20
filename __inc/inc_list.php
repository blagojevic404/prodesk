<?php
require '../../__fn/fn_list.php';


/**** DATA DEFINED IN LIST SCRIPTS

$query['clnz']      array   - Columns that we will fetch from db

TBL                 string  - Table in db
LST_DATECLN         string  - Column that will be used for time sorting

- optional:

SHOW_CALENDAR       bool    - Whether to display calendar bar. Default is true.
SHOW_ABV            bool    - Whether to display alphabet bar. Default is false.
SHOW_CNDZ           bool    - Whether to display CNDZ bar. Default is true.
SHOW_BTNZ           bool    - Whether to display BTNZ bar. Default is true.
IFRM                bool    - Whether the page is loaded from IFRAME or not. Default is false.
CHNL_CBO            bool    - Whether list content depends on channel or not. Default is false.
BTNZ_CTRLZ_UNI      bool    - Whether BTNZ should be put into CTRLZ bar, instead of normal separate bar.
GGL_FOCUS           bool    - Whether to focus on GGL textbox on page load. Default is false.

$query['join']      array   - Table JOIN sql
$query['join_ggl']  array   - Table JOIN sql used only for GGL procedure. It is separated from other JOINs so we can
                              unset it when GGL is not used, in order to not unnecessarily complicate the query.

$cndz['spec']       array   - Special conditions, i.e. additional conditions for WHERE query


*** CNDZ controls

$cndz['typ'] - Type [bln, int-db, int-txt, int-swz, int-usr, int-id, int-dur, str-tme, str-dtr, str-ggl, str-abv];
$cndz['nme'] - Control name (will be used in *name* attribute of the control, and therefore will produce a GET variable)
$cndz['cpt'] - Caption
$cndz['cln'] - Column name that will be used in WHERE query
$cndz['opt'] - Optional data

- optional:

$clnz['act'] - Boolean to easily switch off (deactivate/activate) a control.
$cndz['siz'] - CNDZ table column size i.e. column width IN PERCENTAGE. (Will set value of TD *width* attribute.)


*** CLNZ

$clnz['typ'] - Code that will be used in the ASSEMBLE LOOP to identify each column
$clnz['cpt'] - Column caption text. Used in TH.
$clnz['css'] - CSS classname which will be added to TD of this column
$clnz['cln'] - Column name for this data in its table in DB
$clnz['opt'] - Optional data

- optional:

$clnz['act'] - Boolean to easily switch off (deactivate/activate) a column.
$clnz['siz'] - Column size i.e. column width IN PIXELS. Will set value of TH *width* attribute for each column.
$clnz['no-sort'] - Boolean to switch off the sorting on the column.


*** BTNZ

$btnz['typ'] - Type. NEW button must be of type 'new'. Other than that, it doesn't matter.
$btnz['sub'] - Corresponds to TYP constant. If it is equal to TYP than turns on ACTIVE state of the button.
$btnz['pms'] - Permissions. If false, turns on DISABLED state of the button.
$btnz['hrf'] - Href
$btnz['cpt'] - Caption

$clusterz['arr'] - Cluster categories array, from db or txt.
$clusterz['cln'] - Column which will be filtered in WHERE sql (i.e. which corresponds to *cluster category*)
$clusterz['sel'] - Selected Cluster ID (in cluster categories array). Will turn on ACTIVE state of the button.
$clusterz['pms'] - Permissions. If false, turns on DISABLED state of the button.


** LIST-SETZ

- optional:

$lsetz['memory']['displ']
$lsetz['memory']['cndz'] - DSPL or CNDZ controls whose current value you want to memorize between page views
                           Notes: There mustnot be any values of same name within both arrays.
                                  You may specify only checkbox and cbo controls!
$lsetz['memory']['global'] - Global (i.e. default) values for controls which are set for memorizing

*/


/** Table ID (DB:tablez) */
define('TBLID', tablez('id', TBL));




// Default values for not-obligatory constants


/** Whether this script is loaded through IFRAME */
if (!defined('IFRM')) define('IFRM', (intval(@$_GET['ifrm'])));


/** Whether to focus on GGL textbox on page load */
if (!defined('GGL_FOCUS')) define('GGL_FOCUS', false);


/** Turns alphabet bar on/off. */
if (!defined('SHOW_ABV')) define('SHOW_ABV', false);

if (SHOW_ABV) {
    $cndz['cpt'][] = $tx[SCTN]['LBL']['letter_cond'];
    $cndz['typ'][] = 'str-abv';
    $cndz['nme'][] = 'ABV';
    $cndz['cln'][] = 'Name2nd';
    $cndz['opt'][] = explode(' ', $tx[SCTN]['LBL']['abv']);
    $abv_key = array_search('str-abv', $cndz['typ']);
}


/** Turns calendar bar on/off. */
if (!defined('SHOW_CALENDAR')) define('SHOW_CALENDAR', true);

if (SHOW_CALENDAR) {
    $cndz['typ'][] = 'str-dtr';
    $cndz['nme'][] = 'dtr';
    $cndz['cpt'][] = $tx['LBL']['date'];
}


/** Turns conditions bar on/off. */
if (!defined('SHOW_CNDZ')) define('SHOW_CNDZ', true);

if (!SHOW_CNDZ) {
    // Index 'typ' is later used for looping the $cndz array, therefore we set it to an empty array to avoid the looping
    $cndz['typ'] = [];
}


/** Turns buttons bar on/off. */
if (!defined('SHOW_BTNZ')) {
    define('SHOW_BTNZ', (IFRM ? false : true));  // Do not show BTNZ in IFRM, unless it is specifically defined so
}


/** Whether BTNZ should be put into CTRLZ bar, instead of normal separate bar */
if (!defined('BTNZ_CTRLZ_UNI')) define('BTNZ_CTRLZ_UNI', false);


/** Turns channel cbo on/off. */
if (!defined('CHNL_CBO')) define('CHNL_CBO', false);

$header_cfg['chnl_cbo'] = CHNL_CBO;


/** Add CHNL filtering */
// Channel will by default be added to filters, i.e. CNDZ, whenever channel cbo is used.
// You can avoid automaticaly adding of CHANNEL filter, by defining CHNL_FILTER with *false* value.
if ((!defined('CHNL_FILTER') && CHNL_CBO) || (defined('CHNL_FILTER') && CHNL_FILTER)) {
    $cndz['spec'][] = TBL.'.ChannelID='.((empty($_GET['listCHNL'])) ? CHNL : intval($_GET['listCHNL']));
}


// Default *act* (is_active) values for COLUMNS
foreach($clnz['typ'] as $k => $v) {
    if (!isset($clnz['act'][$k])) {
        $clnz['act'][$k] = true;
    }
}
ksort($clnz['act']);
// We have to sort the keys because this array will be used for columns looping (in the ASSEMBLE loop),
// and the order would be messed if the certain array key was set and others were not (which is default)..


/* DSPL controls */
$dspl['sort_cln'] = [];
$dspl['sort_typ'] = [];
$dspl['rpp'] = ['options' => [50,100,200,500]];


// Put obligatory ID column in query columns list
array_unshift($query['clnz'], TBL.'.ID');


/* LIST SETTINGS array */

// Current list can be dependless of channel, yet CHNL would hold value of channel selected in some other list,
// and *multiple* (channel-specific) memorized list settings would appear, which would produce confusion.
// So, if list is dependless of channel, we set $lsetz['chnl'] (which will be passed to *list_setz* functions) to 0.
// Note: $header_cfg['chnl_cbo'] definition has to come *before* this line
$lsetz['chnl'] = ($header_cfg['chnl_cbo']) ? CHNL : 0;

// Default controls to memorize in list settings
$lsetz['memory']['displ'][] = 'rpp';
$lsetz['memory']['displ'][] = 'sort_cln';
$lsetz['memory']['displ'][] = 'sort_typ';







/**************  RECEIVING SUBMIT VALUES  ***************/

$lsetz['get'] = list_setz_get();

list_display_rcv($dspl, $lsetz); // Note: sends attributes by reference

list_conditions_rcv($cndz, $lsetz); // Note: sends attributes by reference

$sort_cln_key = @$lsetz['get']['sort_cln'];
if ($sort_cln_key && @$clnz['no-sort'][$sort_cln_key]) { // Sorting column is not ID (0), and it has NO-SORT switched on
    $dspl['sort_cln']['val'] = $lsetz['put']['sort_cln'] = 0; // Change sorting column to ID (0)
}

if (TBLID==41) { // stryz
    if ($cndz['val'][array_search('CHK_TODAY_EPG', $cndz['nme'])]==1) { // If CHK_TODAY_EPG is on
        $cln_scnr_key = array_search('cln-scnr', $clnz['typ']); // Get cln-scnr key
        $clnz['no-sort'][$cln_scnr_key] = 0; // Switch off NO-SORT for cln-scnr
        $clnz['no-sort'][0] = 1; // Switch on NO-SORT for cln-ID
        $dspl['sort_cln']['val'] = $lsetz['put']['sort_cln'] = $cln_scnr_key; // Sort by cln-scnr
    }
}

if (!IFRM) {
    list_setz_put();
}


if (empty($_GET['GGL_FULLTEXT']) && empty($_GET['GGL_REGEXP'])) {
    unset($query['join_ggl']);
}

if (!empty($lsetz['put']['TYPX']) && in_array(TBLID, [32,33])) { // epg_blocks, epg_promo

    $btnz['hrf'][3] .= '&TYPX='.$lsetz['put']['TYPX']; // Add TYPX to button-new href, so we pass it to mdf/new item page
}


if (in_array('int-id', $cndz['typ'])) {

    $key = array_search('int-id', $cndz['typ']);

    $id = $cndz['val'][$key];

    if ($id) { // If *int-id* ctrl is set

        $id = rdr_cell(TBL, 'ID', $id);

        hop(($id) ? $pathz['www_root'].dirname($_SERVER['PHP_SELF']).'/'.$cndz['opt'][$key].$id : $_SERVER['HTTP_REFERER']);
    }

    $header_cfg['js_onload'][]  = 'document.getElementById(\''.$cndz['nme'][$key].'\').value=\'\'';
    // To prevent the value sticking to control when user returns via BACK button etc..
}



/**************  *WHERE* QUERY BUILDING  ***************/

$query['where'] = list_conditions_sql($cndz);


if (isset($clusterz) && is_array($clusterz)) {

/*
  @param array $clusterz Clustered buttons
    'arr' - Cluster categories array, from db or txt.
    'cln' - Column which will be filtered in WHERE sql (i.e. which corresponds to *cluster category*)
    'sel' - Selected Cluster ID (in cluster categories array). Will turn on ACTIVE state of the button.
*/
    foreach($clusterz as $v) {

        if (CLUSTER_ALLOW_ZERO && !$v['sel']) {
            continue;
        }
        $query['where'][] = $v['cln'].'='.$v['sel'];
    }
}


$query['where'] = ($query['where']) ? ' WHERE '.implode(' AND ',$query['where']) : '';



/**************  *ORDER_BY* AND *LIMIT* QUERY BUILDING  ***************/


// If the letter is selected in the alphabet bar, we want to sort by column which holds *lastname*
if (SHOW_ABV && TBLID==51 && $cndz['val'][$abv_key]) { // 51=hrm_users
    $name_key = array_search('Name2nd', $clnz['cln']);
    $dspl['sort_cln']['val'] = $name_key;
    $dspl['sort_typ']['val'] = 1;
}


/* This was forcing sort by SCNR cln when using CHK_TODAY_EPG,
 * I dropped this because when check off CHK_TODAY_EPG, the sort by SCNR cln stays on,
 * and the user would instead expect going back to sort by ID
if (TBLID==41) { // 41=stryz
    $cndz_name_key = array_search('CHK_TODAY_EPG', $cndz['nme']);
    if ($cndz['val'][$cndz_name_key]) {
        $dspl_name_key = array_search('scnr', $clnz['typ']);
        $dspl['sort_cln']['val'] = $dspl_name_key;
        $dspl['sort_typ']['val'] = 1;
    }
}*/


// Use DSPL to build ORDER_BY part of the SQL query

$query['sort_typ'] = ($dspl['sort_typ']['val']) ? 'ASC' : 'DESC';

$query['sort_cln'] = $clnz['cln'][$dspl['sort_cln']['val']]; // get the ColumnName
if (is_array($query['sort_cln'])) {
    $query['sort_cln'] = $query['sort_cln'][0];
}

// If ID is not the sort column, then add ID as the *secondary* sort column (add " ASC|DESC, ID")
if ($query['sort_cln']!='ID' && $query['sort_cln']!=TBL.'.ID') {
    $query['sort_cln'] .= ' '.$query['sort_typ'].', '.TBL.'.ID';
}


// Use DSPL to build LIMIT part of the SQL query

$query['limit_len'] = $dspl['rpp']['options'][$dspl['rpp']['val']];
$query['limit_start'] = (isset($_GET['pgr'])) ? intval($_GET['pgr']) : 0;
$query['limit_start'] = floor($query['limit_start'] / $query['limit_len']) * $query['limit_len'];





/**************  H.R.M. THE QUERY  ***************/

$q = 'SELECT DISTINCT SQL_CALC_FOUND_ROWS '.implode(', ', $query['clnz']).
    ' FROM '.TBL.@$query['join'].@$query['join_ggl'].$query['where'].
    ' ORDER BY '.$query['sort_cln'].' '.$query['sort_typ'].
    ' LIMIT '.$query['limit_start'].','.$query['limit_len'];

//if (UZID==UZID_ALFA) {echo $q; exit;}
$result = qry($q);


if ($result) {
    list($num_rows) = mysqli_fetch_row(mysqli_query($GLOBALS["db"], 'SELECT FOUND_ROWS()'));
    // $num_rows is 1 when mysql error happens, therefore I added $result checking..
} else {
    $num_rows = 0;
}

/** Result count */
define('RESULT_CNT', $num_rows); unset($num_rows);





/**************  LIST TABLE  ***************/

if (RESULT_CNT) {

    $html_tbl = [];


    /* TABLE HEADER */

    $html_hdr = list_th_html($clnz, $dspl);


    /* TABLE BODY */

    while ($line = mysqli_fetch_assoc($result)) {

        $id = $line['ID'];


        if (TBLID==32) { //  32=epg_blocks     epg mkt/prm block

            $line['DurCalc'] = epg_durcalc('block', $id);
        }

        if (TBLID==41) { // 41=stryz

            $line['DurCalc'] = epg_durcalc('story', $id);
        }


        /* THE ASSEMBLE LOOP */

        $html_clnz = '';

        foreach($clnz['act'] as $k => $v) {

            if (!$clnz['act'][$k]) continue;

            $td_css = ($clnz['css'][$k]) ? [$clnz['css'][$k]] : [];	// TD CSS

            $tda = '';	// TD attributes
            $vlu = '';	// TD value, i.e. content

            $cln_typ = $clnz['typ'][$k];


            switch ($cln_typ) {


                case 'id-1':

                    $vlu = sprintf('%04s', $id);
                    break;


                case 'id-film':

                    $vlu = sprintf('%04s', $id);
                    if (!$line['IsDelivered']) $td_css[] = 'film_undelivered';
                    break;


                case 'cpt-simple': // HRM USERS, DSK PROGRAM

                    $cpt = c_output('cpt', $line[$clnz['cln'][$k]]);

                    $href = TYP.'_details.php?id='.$id;

                    // This turns entire caption TD into link. Can be omitted.
                    $tda = 'onClick="location.href=\''.$href.'\'"';

                    $vlu = '<a href="'.$href.'">'.$cpt.'</a>';
                    break;


                case 'cpt-prgm': // PRGM

                    $cpt = c_output('cpt', $line[$clnz['cln'][$k]]);

                    $href = TYP.'_details.php?id='.$id;

                    $vlu = '<a href="'.$href.'">'.$cpt.'</a>';

                    if (!@$_GET['PROD'] && $line['ProdType']!=1) {
                        $css = ($line['ProdType']==2) ? 'usd' : 'cd';
                        $vlu .= '<span class="glyphicon glyphicon-'.$css.' prodtype"></span>';
                    }

                    if ($line['DscTitle']) {
                        $vlu .= ' &nbsp;<small class="text-muted">/'.$line['DscTitle'].'/</small>';
                    }

                    if ($line['Note']) {
                        $vlu .= '<span class="glyphicon glyphicon-info-sign epg text-info" '.
                            'data-toggle="tooltip" data-placement="left" title="'.$line['Note'].'"></span>';
                    }

                    break;


                case 'cpt-spice': // EPG SPICE

                    $cpt = c_output('cpt', $line[$clnz['cln'][$k]]);

                    if (EPG_SCT=='mkt' && TYP=='item') {

                        if ($cfg[SCTN]['use_mktitem_video_id'] && $line['VideoID']) {
                            $cpt = $cpt.' '.$line['VideoID'];
                        }

                        if ($line['AgencyID']) {
                            $cpt = mkt_cpt_agency($cpt, $line['AgencyID']);
                        }
                    }

                    if (!IFRM) {

                        $href = 'spice_details.php?sct='.EPG_SCT.((defined('TYP')) ? '&typ='.TYP : '').'&id='.$id;

                        // This turns entire caption TD into link. Can be omitted.
                        $tda = 'onClick="location.href=\''.$href.'\'"';

                    } else { // IFRM

                        $href = '#';

                        if (TYP=='block') {

                            $t = date('H:i:s', dur_handler($line['DurForc'], $line['DurCalc'], 'time'));

                        } else {

                            $t = $line['DurForc'];

                            if ($cfg['dur_use_milli']) {
                                list($t, $ms) = milli_check($t);
                                $t = $t.'*'.milli2ff($ms);
                            }
                        }

                        $r = "['".$line['ID']."','".EPG_SCT_ID."','".$t."','".$line['Caption']."']";
                        $tda = 'onClick="window.parent.ifrm_result('.$r.');return false;"';
                    }

                    $vlu = '<a href="'.$href.'">'.$cpt.'</a>';
                    break;


                case 'cpt-stry': // DSK STRY

                    $cpt = c_output('cpt', $line[$clnz['cln'][$k]]);

                    // Phase sign
                    $phz = phase_sign(['phase' => $line['Phase']]);
                    $cpt = $phz.$cpt;

                    if (!IFRM) {

                        $vlu = '<a href="'.TYP.'_details.php?id='.$id.'">'.$cpt.'</a>';

                        if (!STRY_LST_SCNR) {

                            if (!empty($line['ScnrID'])) { // IS in epg: show glyphicon

                                $vlu .= '<a href="/epg/epg.php?typ=scnr&id='.scnr_id_to_elmid($line['ScnrID']).'">'.
                                    '<span class="glyphicon glyphicon-list epg"></span></a>';
                            }
                        }

                    } else { // IFRM

                        if (empty($line['ScnrID'])) { // NOT in epg

                            $vlu = '<a href="#">'.$cpt.'</a>';

                            $precedence = ($line['Phase']==4) ? 'calc' : '';

                            $t = date('H:i:s', dur_handler($line['DurForc'], $line['DurCalc'], 'time', $precedence));

                            $r = "['".$line['ID']."','".EPG_SCT_ID."','".$t."','".$line['Caption']."']";
                            $tda = 'onClick="window.parent.ifrm_result('.$r.');return false;"';

                        } else { // IS in epg: don't show link, because one story must not be connected to multiple epgs

                            $vlu = '<span class="title">'.$cpt.'</span>';
                        }
                    }

                    break;


                case 'cpt-cvr': // CVR

                    $y = cover_reader($id, ['get_ownerdata' => true]);

                    $css = [];
                    if (!$line['IsReady'])      $css[] = 'ready_not';
                    if ($line['ProoferUID'])    $css[] = 'cvr_proofed';

                    $phz = '<span class="glyphicon glyphicon-ok ready'.(($css) ? ' '.implode(' ', $css) : '').'"></span>';

                    $href = TYP.'_details.php?id='.$id;

                    $vlu = $phz.'<a href="'.$href.'">'.$y['Caption'].'</a>';

                    if (!empty($y['CaptionSub'])) {
                        $vlu .= ' <small class="text-muted">/' . $y['CaptionSub'] . '/</small>';
                    }

                    $tmp_arr = [1 => 'list', 'stop', 'stop', 'calendar'];

                    $vlu .= '<a href="'.$y['OwnerHREF'].'" title="'.$y['OwnerTypeTXT'].'">'.
                        '<span class="glyphicon glyphicon-'.$tmp_arr[$y['OwnerType']].' epg"></span></a>';

                    break;


                case 'cpt-flw': // DSK followz
                case 'cpt-trash': // DSK trash

                    $css = '';
                    $cpt_sub = '';

                    if ($line['ItemType']==2) { // stry

                        $tbl = 'stryz';

                        $phz = phase_sign(['phase' => rdr_cell('stryz', 'Phase', $line['ItemID'])]);

                        $cpt = c_output('cpt', rdr_cell($tbl, 'Caption', $line['ItemID']));

                        $href = 'stry_details.php?id='.$line['ItemID'];

                        if ($cln_typ=='cpt-flw' && rdr_cell($tbl, 'IsDeleted', $line['ItemID'])) {
                            $css = ' style="text-decoration: line-through;"';
                        }

                    } else {    // 1-prg

                        $element_id = scnr_id_to_elmid($line['ItemID']);

                        if ($element_id) {
                            $scnr = scnr_cpt_get($element_id, 'arr');
                        } else {
                            $scnr = [];
                            log2file('srpriz', ['type' => 'scnr_element_missing', 'lst' => $cln_typ, 'scnr' => $line['ItemID']]);
                        }

                        $cpt = $scnr['Caption'];

                        $cpt_sub = ' <small class="text-muted">/'.$scnr['TermEmit'].'/</small>';

                        $phz = '<span class="glyphicon glyphicon-list" style="margin-right:5px;"></span>';

                        $href = '/epg/epg.php?typ=scnr&id='.$element_id;
                    }

                    $vlu = $phz.'<a href="'.$href.'"'.$css.'>'.$cpt.'</a>'.$cpt_sub;

                    break;


                case 'cpt-serial': // DISCONTINUED: FILM-SERIAL (MKT-BLCTYP)

                    $cpt = c_output('cpt', rdr_cell('film_description', $clnz['cln'][$k], $line['ID']));

                    $href = '#';
                    $r = "['".$line['ID']."','".EPG_SCT_ID."','".$line['DurDesc']."','".$cpt."']";
                    $tda = 'onClick="window.parent.ifrm_result('.$r.');return false;"';

                    $vlu = '<a href="'.$href.'">'.$cpt.'</a>';

                    break;


                case 'cpt-film': // FILM

                    if (TYP=='item') {
                        $cpt = c_output('cpt', rdr_cell('film_description', $clnz['cln'][$k], $line['ID']));
                    } else {
                        $cpt = c_output('cpt', $line[$clnz['cln'][$k]]);
                    }


                    if (!IFRM) {

                        $href = $clnz['opt'][$k].$id;

                    } else { // IFRM

                        if (TYP=='item') {

                            $dur = film_dur($line['DurApprox'], $line['DurReal'], '', 'arr');

                            if ($clusterz[1]['sel']==1) { // movie

                                $href = '#';
                                $r = "['".$line['ID']."','".EPG_SCT_ID."','".$dur['Duration']."','".$cpt."']";
                                $tda = 'onClick="window.parent.ifrm_result('.$r.');return false;"';

                            } else { // serial, mini-serial

                                $href = 'film_episode_list_frm.php?id='.$line['ID'];
                            }

                        } else { // contract (cannot be agencies list, because it is never called through iframe)

                            $href = '#';
                            $r = "['".$line['ID']."','','".$line['CodeLabel']."','".
                                rdr_cell('film_agencies', 'Caption', $line['AgencyID'])."']";
                            $tda = 'onClick="window.parent.ifrm_result('.$r.');return false;"';

                        }

                    }

                    $vlu = '<a href="'.$href.'">'.$cpt.'</a>';


                    if (TYP=='item') {


                        // production type
                        if ($line['ProdType']!=2) {
                            $css = ($line['ProdType']==1) ? 'home' : 'cd';
                            $vlu .= '<span class="glyphicon glyphicon-'.$css.' prodtype"></span>';
                        }


                        // type & section labels

                        if (!$clusterz[2]['sel']) {
                            $vlu = '<span class="film_sct">'.$film_sctz[$line['SectionID']].'</span>'.$vlu;
                        }

                        if (!$clusterz[1]['sel']) {
                            $vlu = '<span class="film_typ">'.$film_typz[$line['TypeID']].'</span>'.$vlu;
                        }


                        // episode count (for serial and mini-serial)
                        if ($clusterz[1]['sel']!=1 && $line['EpisodeCount']) {
                            $vlu .= '<span class="film_ep_cnt"> ['.$line['EpisodeCount'].']</span>';
                        }


                        // language label
                        if ($line['LanguageID']) {
                            $vlu = '<span class="film_lng">'.$film_lngz[$line['LanguageID']].'</span>'.$vlu;
                        }


                        // note subcaption
                        $epg_sct_id = ($line['TypeID']==1) ? 12 : 13;
                        $note = note_reader($line['ID'], $epg_sct_id);
                        if ($note) {
                            $vlu .= ' <span class="film_note">'.$note.'</span>';
                        }


                        // genres subcaption
                        if (setz_get('film_list_show_genre')) {

                            $x['Genres'] = rdr_cln('film_cn_genre', 'GenreID', 'FilmID='.$line['ID']);

                            if ($x['Genres']) {

                                $gnr_arr = [];

                                foreach ($x['Genres'] as $gnr_v) {
                                    $gnr_arr[] = $film_gnrz[$gnr_v];
                                }

                                $vlu .= '<span class="film_gnr">'.implode(', ', $gnr_arr).'</span>';

                                unset($gnr_arr);
                            }
                        }


                    }

                    break;



                case 'ctrl-flw': // DSK followz - restore

                    $vlu = flw_output(true, $line['ItemID'], $line['ItemType']);

                    break;


                case 'ctrl-trash': // DSK TRASH CONTROLS

                    $pms = pms('dsk/stry', 'purge_restore', ['UID' => rdr_cell('stryz', 'UID', $line['ItemID'])]);

                    $btn = '<a class="text-%s'.(($pms) ? '' : ' disabled').'" href="delete.php?typ=stry_%s&id=%u">'.
                        '<span class="glyphicon glyphicon-%s-circle"></span></a>';

                    $vlu = sprintf($btn, 'success', 'restore', $line['ItemID'], 'ok').
                        sprintf($btn, 'danger', 'purge', $line['ItemID'], 'remove');

                    break;


                case 'cln-scnr':

                    if ($line[$clnz['cln'][$k]]) {

                        $element_id = scnr_id_to_elmid($line[$clnz['cln'][$k]]);

                        if ($element_id) {
                            $cpt = scnr_cpt_get($element_id);
                        } else {
                            $cpt = '';
                            log2file('srpriz', ['type' => 'scnr_element_missing', 'lst' => $cln_typ, 'stry' => $line['ID']]);
                        }

                        if (!IFRM) {
                            $vlu = '<a href="/epg/epg.php?typ=scnr&id='.$element_id.'">'.$cpt.'</a>';
                        } else {
                            $vlu = '<span class="title">'.$cpt.'</span>';
                        }
                    }

                    break;


                case 'flw-scnr':

                    if ($line['ItemType']==2) { // stry
                        $scnrid = rdr_cell('stryz', 'ScnrID', $line['ItemID']); // Get scnrid for this story
                        if ($scnrid) {
                            $element_id = scnr_id_to_elmid($scnrid);
                            $vlu = '<a href="/epg/epg.php?typ=scnr&id='.$element_id.'">'.scnr_cpt_get($element_id).'</a>';
                        }
                    }

                    break;


                case 'ctg-txt': // for category which uses array that is fetched from TXT

                    $vlu = @$clnz['opt'][$k][$line[$clnz['cln'][$k]]];
                    break;


                case 'ctg-db':  // for category which uses array that is fetched from DB

                    $vlu = rdr_cell($clnz['opt'][$k][0], $clnz['opt'][$k][1], $line[$clnz['cln'][$k]]);
                    break;


                case 'ctg-db2': // similar to *ctg-db* but with a href

                    if ($line[$clnz['cln'][$k]]) {

                        $cpt = rdr_cell($clnz['opt'][$k][0], $clnz['opt'][$k][1], $line[$clnz['cln'][$k]]);

                        $href = $clnz['opt'][$k][2].$line[$clnz['cln'][$k]];

                        $vlu = '<a href="'.$href.'">'.$cpt.'</a>';
                    }

                    break;



                case 'time-1':

                    $vlu = list_time($line[$clnz['cln'][$k]]);
                    break;


                case 'time-frmt': // time with formatting

                    if ($line[$clnz['cln'][$k]] && $line[$clnz['cln'][$k]]!='0000-00-00') {
                        $vlu = date($clnz['opt'][$k], strtotime($line[$clnz['cln'][$k]]));
                    }

                    break;



                case 'uid-1':

                    if (isset($clnz['opt'][$k]['n1']))      $frmt['n1_typ'] = $clnz['opt'][$k]['n1'];
                    if (isset($clnz['opt'][$k]['n2']))      $frmt['n2_typ'] = $clnz['opt'][$k]['n2'];
                    if (isset($clnz['opt'][$k]['dot']))     $frmt['dot'] = $clnz['opt'][$k]['dot'];

                    $vlu = uid2name($line[$clnz['cln'][$k]], @$frmt);
                    break;


                case 'txt-1': // simply prints out value

                    $vlu = $line[$clnz['cln'][$k]];
                    break;


                case 'prgm-web':

                    $web = rdr_row('prgm_settings', 'WebLIVE, WebVOD, WebHide', $id);

                    $vlu =
                        '<span class="glyphicon glyphicon-play-circle lblweb weblive'.intval($web['WebLIVE']).'"></span>'.
                        '<span class="glyphicon glyphicon-download lblweb webvod'.intval($web['WebVOD']).'"></span>';

                    if ($web['WebHide']) {
                        $vlu .= '<span class="glyphicon glyphicon-ban-circle lblban"></span>';
                    }

                    break;


                case 'crew-1':

                    $nat_typ = (empty($clnz['opt'][$k]['NatTyp'])) ? TBLID : $clnz['opt'][$k]['NatTyp'];

                    $arr = crw_reader($id, $nat_typ);

                    foreach ($arr as $k_crew => $v_crew) {
                        $arr[$k_crew] = uid2name($v_crew['CrewUID'], ['n1_typ'=>'init']);
                    }

                    $vlu = implode(((empty($clnz['opt'][$k]['br'])) ? ', ' : '<br>'), $arr);

                    break;


                case 'uzr-group':

                    $branch = branch_up_get($line[$clnz['cln'][$k]], true);

                    $vlu = implode(' &nbsp;&rsaquo;&nbsp; ', $branch);

                    break;


                case 'sum-rows': // used to sum all contracts/items which belong to a specific film/marketing agency

                    mysqli_query($GLOBALS["db"], 'SELECT SQL_CALC_FOUND_ROWS ID FROM '.$clnz['opt'][$k][0].
                        ' WHERE '.$clnz['opt'][$k][1].'='.$id);
                    list($vlu) = mysqli_fetch_row(mysqli_query($GLOBALS["db"], 'SELECT FOUND_ROWS()'));
                    break;


                case 'dur-epg':

                    $dur = dur_handler($line['DurForc'], $line['DurCalc']);

                    if (!isset($dur['other'])) {
                        $vlu = '<span class="'.$dur['css'].'">'.date('i:s', $dur['time']).'</span>';
                    } else {
                        $vlu = '<span class="'.$dur['css'].'">'.date('i:s', $dur['other']).'</span> '.
                            '<span class="dur_sec">/'.date('i:s', $dur['time']).'/</span>';
                    }

                    break;


                case 'dur-film':

                    $vlu = film_dur($line['DurApprox'], $line['DurReal'], $line['DurDesc']);
                    break;


                case 'date-range':

                    $nme_start = $clnz['cln'][$k][0];
                    $nme_finit = $clnz['cln'][$k][1];

                    if (($line[$nme_start] && $line[$nme_start]!='0000-00-00') ||
                        ($line[$nme_finit] && $line[$nme_finit]!='0000-00-00')) {
                        $vlu = $line[$nme_start].'&nbsp;&#8212;&nbsp;'.$line[$nme_finit];
                    }

                    break;


                case 'film-bcasts':

                    $epg_sct_id = ($line['TypeID']==1) ? 12 : 13;
                    $vlu = epg_bcast_countbox($epg_sct_id, $id, $line['BCmax'], CHNL).
                           epg_bcast_list($epg_sct_id, $id, 'ym_term', CHNL);

                    if (setz_get('film_list_bc_show_all_channels')) {

                        foreach ($bc_channels as $chn_k => $chn_v) {

                            // Display a one-letter channel label, followed by bc list for that channel

                            $bc = epg_bcast_list($epg_sct_id, $id, 'ym_term', $chn_k);

                            if ($bc) {
                                $vlu .= '<span class="bc_channel">'.$chn_v.'</span>'.$bc;
                            }
                        }
                    }

                    break;
            }


            $td_css = ($td_css) ? ' class="'.implode(' ', $td_css).'"' : '';

            if ($tda) $tda = ' '.$tda;
            if (!$vlu) $vlu = '&nbsp;';

            $html_clnz .= '<td'.$td_css.$tda.'>'.$vlu.'</td>'.PHP_EOL;
        }

        $html_tbl[$line['ID']] = '<tr>'.$html_clnz.'</tr>'.PHP_EOL;
    }


    // assemble

    $html =
        '<table class="table table-hover'.((IFRM) ? ' table-condensed' : '').'" id="lst_tbl">'.
            $html_hdr.
            '<tbody>'.implode('',$html_tbl).'</tbody>'.
        '</table>';


} else {
	$html = '';
}
