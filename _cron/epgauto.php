<?php

/** (init from cron_1day.sh)
 *
 * EPG auto copy
 *
 * - cfg: ['epg']['epg_auto_chnlz'] - List of channels which are to be handled by auto-copy procedure
 * - cfg: ['epg']['epg_auto_daycnt_1'] - Date of the auto-copy epg should be how many days from today (for channel type 1)
 * - cfg: ['epg']['epg_auto_daycnt_2'] - (same, for channel type 2)
 *
 * The script will calculate the date for the epg auto-copy, then check whether epg for that day exists. If it doesn't,
 * it will create it by copying the first previous epg for the same day of the week..
 */


define('ROBOT',1);
define('SCTN', 'epg');

require '../../__ssn/ssn_boot.php';



log2file('robot-ok'); // for troubleshooting




if (empty($cfg[SCTN]['epg_auto_chnlz'])) {
    exit;
}



$chnlz = explode(',', $cfg[SCTN]['epg_auto_chnlz']);

foreach ($chnlz as $chnl) {

    $chnl_typ = rdr_cell('channels', 'TypeX', $chnl);

    $date_target = date('Y-m-d', strtotime('+ '.$cfg[SCTN]['epg_auto_daycnt_'.$chnl_typ].' day'));

    $sql = 'SELECT ID FROM prodesk.epgz WHERE !IsTMPL AND ChannelID='.$chnl.' AND DateAir=\''.$date_target.'\'';

    $epgid_target = qry_numer_var($sql);

    if ($epgid_target) { // If the target EPG already exists, skip the copy procedure for it
        continue;
    }

    epg_copy($date_target, $chnl);
}



