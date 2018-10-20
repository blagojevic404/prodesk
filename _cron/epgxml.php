<?php

/** (init from cron_1min.sh)
 *
 * EPG ProDesk -> ProWeb
 *
 * Copy EPG data from ProDesk server to ProWeb server
 *
 * - cfg: ['epg']['epg_cron_chnlz'] - List of channels which are to be handled by epgxml procedure
 * - cfg: ['epg']['epgxml_path'] - Where to save epgxml files
 * - db: cfg_arrz:chnlX.Epg_days_cnt - How many epg dates to show on web
 *
 * The script will for each channel in the list get the list of epgid's, starting from YESTERDAY and up to
 * (YESTERDAY + Epg_days_cnt).
 * For each of those epg's, epgxml file will be created.
 * The file will not overwrite its previous version if no changes were made in the meantime.
 *
 * Crontab runs this script every *1 minut*. By this mean we create/update epgxml files.
 * File replication for EPGXML directory from ProDesk server to ProWeb server is also set on every *1 minut*.
 * By this mean we get epgxml files on ProWeb side.
 *
 * On ProWeb side, crontab runs epgxml_db.php script every *1 minut*.
 * This script transfers data from xml to db (to tables _epgs and _epg). The script shouldnot contain any functions
 * which are specific to ProDesk or ProWeb.
 *
 * There are janitor scripts on both sides, which are run by crontab every *1 day*. ProDesk janitor deletes epg-xml files
 * when they are no longer needed. (File replication deletes those files on ProWeb side.) ProWeb janitor deletes db data
 * when it is no longer needed.
 *
 * In each file there is a line which turns on 'robot-ok' logging. This can be used to check whether cron is properly
 * running the scripts. This logging should always be turned on for scripts which are to be run once a day.
 */


define('ROBOT',1);
define('SCTN', 'epg');

require '../../__ssn/ssn_boot.php';

require '../../__fn/fn_xml.php';



//header('content-type: application/xml; utf-8');



//log2file('robot-ok'); // for troubleshooting



$chnlz = explode(',', $cfg[SCTN]['epg_cron_chnlz']);

foreach ($chnlz as $chnl) {

    $epg_days_cnt = cfg_local('arrz', 'chnl_'.$chnl, 'Epg_days_cnt');

    $dates = [];

    $dates[] = date('Y-m-d', strtotime('-1 day'));

    for ($i=0; $i<$epg_days_cnt; $i++) { // show days from TODAY to TODAY+$rows_total
        $dates[] = date('Y-m-d', strtotime('+'.$i.' day'));
    }

    foreach ($dates as $k => $dater) {

        echo $chnl.'_'.$dater.'<br>';

        $epgid = qry_numer_var('SELECT ID FROM epgz WHERE DateAir=\''.$dater.'\' AND IsTMPL=0 AND ChannelID='.$chnl);

        if ($epgid) {

            epg_web_html($epgid, 'xml');
        }
    }
}

