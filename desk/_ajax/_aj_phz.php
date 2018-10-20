<?php
/**
 * Story phase increment. The script is called via ajax, from EPG studio-view page.
 */

require '../../../__ssn/ssn_boot.php';

$typ = ($_POST['typ']=='up') ? 'up' : 'down';

$id = (isset($_POST['id'])) ? wash('int', $_POST['id']) : 0;
if (!$id) exit('-404');


$x = rdr_row('stryz', 'ID, UID, Phase, ScnrID', $id);
if (!$x['ID']) exit('-404');


$phz_new = ($typ=='up') ? $x['Phase'] + 1 : $x['Phase'] - 1;

if ($phz_new>4 || $phz_new<1) {
    exit('-1');
};


$pms = pms('dsk/stry', 'phase', $x);

if (!$pms[$phz_new]) {
    exit('-2');
};


stry_phazer($x, $phz_new);


echo $phz_new;