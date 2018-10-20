<?php
/**
 * MOVER: Story cut/copy, scnr stories import.
 */

require '../../__ssn/ssn_boot.php';

require '../../__fn/lib_epg.php';
require '../../__fn/lib_dsk-epg_prgm.php';
require '../../__fn/fn_mover.php';




$typ = (isset($_GET['typ'])) ? wash('arr_assoc', $_GET['typ'], ['calendar', 'epg', 'scnr']) : null;
if (!$typ) exit;


// MOVER data
$act = (isset($_GET['act'])) ? wash('arr_assoc', $_GET['act'], ['cut', 'copy', 'import', 'linker']) : null;
if (!$act) exit;

$objid = (isset($_GET['objid'])) ? wash('int', $_GET['objid']) : 0; // scnrid for import, stryid for stry cut/copy
if ($act!='linker' && !$objid) exit;



if ($typ=='calendar') { // Called via prev/next controls in calendar. Change calendar month..


    $month = wash('ymd', @$_GET['month'], 'Y-m-01');
    if (!$month) exit;

    $chnlid = (isset($_GET['chnlid'])) ? wash('int', $_GET['chnlid']) : CHNL;

    $opt = [
        'month' => $month,
        'chnlid' => $chnlid,
        'href_query' => '&objid='.$objid.'&act='.$act,
    ];

    if ($act=='import') {
        $opt['layout'] = 'vertical';
    }

    mover_calendar('framer', $opt);


} elseif ($typ=='epg') { // Called via click on some epg-date in a calendar.. Show scnr list for the selected epg..


    $epgid = (isset($_GET['epgid'])) ? wash('int', $_GET['epgid']) : null;
    if (!$epgid) exit;

    if ($act=='import') { // In (scnr) *import* case, we show another frame

        $casetyp = $act;

        $href_query = '&objid='.$objid;

    } elseif ($act=='linker') { // In epg *linker* case, we exit via js

        $casetyp = $act;

        $href_query = null;

    } else { // In stry *cut* and *copy* case, we proceed out to stry_mover

        $casetyp = 'cutcopy';

        $href_query = '&id='.$objid.'&act='.$act;
    }

    mover_epg($epgid, $casetyp, $href_query);


} elseif ($typ=='scnr') { // Only *import* case.. Show checklist of all importable fragments for the selected scnr..


    $source_elmid = (isset($_GET['elmid'])) ? wash('int', $_GET['elmid']) : null;
    if (!$source_elmid) exit;

    $editor = pms_scnr(scnrid_prog($objid), 1);

    mover_scnr($source_elmid, $objid, $editor);

}
