<?php
/**
 * Epg studio: ATOM Text change
 */

require '../../../__ssn/ssn_boot.php';


$atomid = (isset($_POST['atomid'])) ? wash('int', $_POST['atomid']) : 0;

$texter = (isset($_POST['texter'])) ? wash('txt', $_POST['texter']) : '';

if (!$atomid) exit('-404');


$story_id = rdr_cell('stry_atoms', 'StoryID', $atomid);
$x = rdr_row('stryz', 'ID, UID, Phase, ScnrID, ChannelID', $story_id);
if (!$x['ID']) exit('-404');


$pms = pms('dsk/stry', 'mdf', $x);
if (!$pms) {
    exit('-1');
};



$cur_atomz = atomz_reader($story_id);

qry('UPDATE stry_atoms_text SET Texter=\''.$texter.'\' WHERE ID='.$atomid, ['tbl_name' => 'stryz', 'x_id' => $story_id]);

stry_versions_put($story_id, $cur_atomz);



$atom_typ = rdr_cell('stry_atoms', 'TypeX', $atomid);
if ($atom_typ!=1) {
    exit('-2');
}


$new_dur = atom_termemit($atomid, ['Texter' => $texter]);

echo $atomid.','.$new_dur;


