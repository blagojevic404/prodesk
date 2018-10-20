<?php

/** (init from cron_1day.sh)
 *
 * Clean-up jobs - db tables; epg-xml files
 *
 */


define('ROBOT',1);
define('SCTN', 'admin');
define('DBARHV', true); // Used when calling receiver_ins() on ARHV server


require '../../__ssn/ssn_boot.php';

require '../../__fn/fn_del.php';

require '../../__fn/lib_epg.php'; // for jnt_stry_unused_deassociate()


$cfg['dsk'] = cfg_global('varz', 'dsk');



log2file('robot-ok'); // for troubleshooting




echo '<pre>';

jnt_stry_unused_deassociate();

jnt_epgxml($cfg[SCTN]['jnt_epgxml']); // How many days to keep in epgxml directory

jnt_stry_versions($cfg[SCTN]['jnt_stry_versions']); // How many days to keep for stry_versions table

jnt_stry_trash($cfg[SCTN]['jnt_stry_trash']); // After how many days to delete stories from RECYCLE BIN

jnt_log_sql($cfg[SCTN]['jnt_log_sql']); // How many days to keep for log_qry/log_sql tables

jnt_log_mdf($cfg[SCTN]['jnt_log_mdf']); // How many days to keep for log_mdf table

jnt_log_inout($cfg[SCTN]['jnt_log_inout']); // How many days to keep for log_in_out table

//db_archiver($cfg[SCTN]['db_arhiver']); vb2do

echo '</pre>';


echo 't = '.t_exec();




/**
 * TOOLS: DB Archiver
 *
 * @param int $d How many days to keep
 *
 * @return void
 */
function db_archiver($d) {

    $GLOBALS['db_arhv'] = connect_db(DB_SERVER, DB_USER, DB_PASS, 'test'); // vb2do
    if (!$GLOBALS['db_arhv']) exit;


    if ($d<90) { // safety check
        log2file('srpriz', ['type' => 'db_archiver_min']);
        exit;
    }

    $date_end = date('Y-m-d', strtotime('- '.$d.' day'));

    //$epgid_arr = rdr_cln('epgz', 'ID', 'DateAir<=\''.$date_end.'\' AND IsTMPL!=1', 'DateAir ASC');

    $epgid_arr = [283]; //vb2do

    foreach ($epgid_arr as $epgid) {

        db_archiver_rowcopy('epgz', $epgid);
        db_archiver_epgcopy($epgid);
    }


    mysqli_close($GLOBALS["db"]);

    echo "URA db archiver ($d)".PHP_EOL;
}


/**
 * DB Archiver: copy row from PROD to ARCH
 *
 * @param string $tbl Table
 * @param int $id ID
 * @param string $clnz Columns to copy
 * @param bool $rtyp Whether to return a data array
 *
 * @return void|array
 */
function db_archiver_rowcopy($tbl, $id, $clnz=null, $rtyp=false) {

    if (!$clnz) {
        $clnz = '*';
    }

    $x = qry_assoc_row('SELECT '.$clnz.' FROM '.$tbl.' WHERE ID='.$id);

    receiver_ins($tbl, $x, LOGSKIP, DBARHV);

    if ($rtyp) {
        return $x;
    }
}


/**
 * DB Archiver: copy an EPG
 *
 * @param int $epgid Epg ID
 *
 * @return void
 */
function db_archiver_epgcopy($epgid) {

    $tbl[1] = 'epg_elements';
    $tbl[2] = 'epg_scnr';
    $tbl[3] = 'epg_notes';
    $tbl[4] = 'epg_films';

    $result = qry('SELECT * FROM '.$tbl[1].' WHERE EpgID='.$epgid.' ORDER BY Queue');

    while ($x = mysqli_fetch_assoc($result)) {

        switch ($x['NativeType']) {

            case 1: // prog
                db_archiver_rowcopy($tbl[2], $x['NativeID']);
                db_archiver_scnrcopy($x['NativeID']);
                break;

            case 3: // mkt
            case 4: // prm
                $x['NativeID'] = 0; // We don't want to copy the exact block, we will just leave a placeholder
                break;

            case 7: // live
            case 8: // note
            case 9: // space
            case 10: // segment
                db_archiver_rowcopy($tbl[3], $x['NativeID']);
                break;

            case 12: // film
            case 13: // serial
                db_archiver_rowcopy($tbl[2], rdr_cell($tbl[4], 'ScnrID', $x['NativeID']));
                db_archiver_rowcopy($tbl[4], $x['NativeID'], 'ID, ScnrID');
                break;
        }

        if ($x['OnHold'] || (!empty($holder) && !$x['TimeAir'])) {
            $holder = true;
        } else {
            $holder = false;
            $x['TimeAir'] = $x['TermEmit'];
        }

        receiver_ins($tbl[1], $x, LOGSKIP, DBARHV);
    }


    //epg_deleter('epg', $epgid, 'descend', 'all'); vb2do
}


/**
 * DB Archiver: copy a SCNR
 *
 * @param int $scnrid Scnr ID
 *
 * @return void
 */
function db_archiver_scnrcopy($scnrid) {

    $tbl[1] = 'epg_scnr_fragments';
    $tbl[2] = 'epg_notes';

    $result = qry('SELECT * FROM '.$tbl[1].' WHERE ScnrID='.$scnrid.' ORDER BY ID');

    while ($x = mysqli_fetch_assoc($result)) {

        switch ($x['NativeType']) {

            case 2: // stry
                db_archiver_strycopy($x['NativeID']);
                break;

            case 7: // live
            case 8: // note
            case 9: // space
            case 10: // segment
                db_archiver_rowcopy($tbl[2], $x['NativeID']);
                break;
        }

        receiver_ins($tbl[1], $x, LOGSKIP, DBARHV);
    }
}


/**
 * DB Archiver: copy a story
 *
 * @param int $stryid Story ID
 *
 * @return void
 */
function db_archiver_strycopy($stryid) {

    $tbl[1] = 'stryz';
    $tbl[2] = 'stry_atoms_text';
    $tbl[3] = 'stry_atoms_speaker';
    $tbl[5] = 'stry_atoms';

    $stry = db_archiver_rowcopy($tbl[1], $stryid, null, true);


    $result = qry('SELECT * FROM '.$tbl[5].' WHERE StoryID='.$stry['ID'].' ORDER BY Queue');

    while ($atom = mysqli_fetch_assoc($result)) {

        db_archiver_rowcopy($tbl[5], $atom['ID']);

        db_archiver_rowcopy($tbl[2], $atom['ID']);

        if ($atom['TypeX']==1) {
            db_archiver_rowcopy($tbl[3], $atom['ID']);
        }
    }


    //stry_deleter($stryid); vb2do
}







/**
 * Delete epgxml files when they are no longer needed
 *
 * @param int $d How many days to keep in epgxml directory
 * @return void
 */
function jnt_epgxml($d) {

    $epgxml_path = txt_rdr('cfg', 'varz', 'epg', 'epgxml_path');

    foreach (glob($epgxml_path.'*.xml') as $filename) {

        $dater = substr($filename, -14, 10);

        if (strtotime($dater) < strtotime('- '.$d.' day')) {

            unlink($filename);
        }
    }

    echo "URA epgxml ($d)".PHP_EOL;
}




/**
 * Janitor: Deassociate stories from yesterdays scnrz which are inactive, i.e. they were not used
 */
function jnt_stry_unused_deassociate() {

    $stryz = [];
    define('PMS_EPG_DELETER', true);


    $epgz = qry_numer_arr('SELECT ID FROM epgz WHERE DateAir=DATE_SUB(CURDATE(), INTERVAL 1 DAY)');

    foreach ($epgz as $epgid) {

        $scnrz = qry_numer_arr('SELECT NativeID FROM epg_elements WHERE EpgID='.$epgid.' AND NativeType=1');

        foreach ($scnrz as $scnrid) {

            $fragz = qry_numer_arr('SELECT ID, NativeID FROM epg_scnr_fragments '.
                'WHERE ScnrID='.$scnrid.' AND NativeType=2 AND IsActive=0');

            if ($fragz) {

                foreach ($fragz as $fragid => $stryid) {

                    epg_deleter('scnr', $fragid);

                    $stryz[] = $stryid;
                }
            }
        }
    }

    echo 'URA story deassociate if unused ('.implode(', ', $stryz).')'.PHP_EOL;
}



/**
 * Janitor for *stry_versions* table
 *
 * @param int $d How many days to keep
 * @return void
 */
function jnt_stry_versions($d) {

    $id = rdr_cell('stry_versions', 'ID', 'DATE(TermMod) < DATE_SUB(CURDATE(), INTERVAL '.$d.' DAY)', 'ID DESC');

    if ($id) {
        qry('DELETE FROM stry_versions WHERE ID<='.$id);
    }

    echo "URA story versions ($d)".PHP_EOL;
}



/**
 * Janitor for stories in RECYCLE BIN
 *
 * @param int $d How many days to keep
 * @return void
 */
function jnt_stry_trash($d) {

    $result = qry('SELECT ItemID FROM stry_trash WHERE ItemType=2 AND DATE(DelTerm) < DATE_SUB(CURDATE(), INTERVAL '.$d.' DAY)');

    while ($line = mysqli_fetch_row($result))  {

        stry_deleter($line[0], true);

        echo $line[0].PHP_EOL;
    }

    echo "URA story trash ($d)".PHP_EOL;
}



/**
 * Janitor for *log_qry* and *log_sql* tables
 *
 * @param int $d How many days to keep
 * @return void
 */
function jnt_log_sql($d) {

    $id = rdr_cell('log_qry', 'ID', 'DATE(TermAdd) < DATE_SUB(CURDATE(), INTERVAL '.$d.' DAY)', 'ID DESC');

    if ($id) {
        qry('DELETE FROM log_qry WHERE ID<='.$id);
        qry('DELETE FROM log_sql WHERE ID<='.$id);
    }

    echo "URA qry/sql log ($d)".PHP_EOL;
}



/**
 * Janitor for *log_mdf* table
 *
 * @param int $d How many days to keep
 * @return void
 */
function jnt_log_mdf($d) {

    $id = rdr_cell('log_mdf', 'ID', 'DATE(TermAccess) < DATE_SUB(CURDATE(), INTERVAL '.$d.' DAY)', 'ID DESC');

    if ($id) {
        qry('DELETE FROM log_mdf WHERE ID<='.$id);
    }

    echo "URA mdf log ($d)".PHP_EOL;
}



/**
 * Janitor for *log_in_out* table
 *
 * @param int $d How many days to keep
 * @return void
 */
function jnt_log_inout($d) {

    $id = rdr_cell('log_in_out', 'ID', 'DATE(Time) < DATE_SUB(CURDATE(), INTERVAL '.$d.' DAY)', 'ID DESC');

    if ($id) {
        qry('DELETE FROM log_in_out WHERE ID<='.$id);
    }

    echo "URA in/out log ($d)".PHP_EOL;
}

