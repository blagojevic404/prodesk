<?php
/**
 * Epg studio: ATOM Speaker change
 */

require '../../../__ssn/ssn_boot.php';


$atomid = (isset($_POST['atomid'])) ? wash('int', $_POST['atomid']) : 0;

$speakerx = (isset($_POST['speakerx'])) ? wash('int', $_POST['speakerx']) : 0;

if (!$atomid || !$speakerx) exit;



$story_id = rdr_cell('stry_atoms', 'StoryID', $atomid); if (!$story_id) exit;
$scnrid = rdr_cell('stryz', 'ScnrID', $story_id); if (!$scnrid) exit;

pms('epg', 'mdf_speaker_x', ['NativeID' => $scnrid], true);



$log = ['tbl_name' => 'stryz', 'x_id' => $story_id];

qry('UPDATE stry_atoms_speaker SET SpeakerX='.$speakerx.' WHERE ID='.$atomid, $log);



$new_dur = atom_termemit($atomid, ['SpeakerX' => $speakerx]);



echo $speakerx.','.$atomid.','.$new_dur;


