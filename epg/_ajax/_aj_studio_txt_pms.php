<?php
/**
 * Epg studio: Check PMS for atom text change
 */

require '../../../__ssn/ssn_boot.php';


$atomid = (isset($_POST['atomid'])) ? wash('int', $_POST['atomid']) : 0;
if (!$atomid) exit('-404');


$story_id = rdr_cell('stry_atoms', 'StoryID', $atomid);
$x = rdr_row('stryz', 'ID, UID, Phase, ScnrID, ChannelID', $story_id);
if (!$x['ID']) exit('-404');


$pms = pms('dsk/stry', 'mdf', $x);
if (!$pms) {
    exit('-1');
};

echo $atomid;
