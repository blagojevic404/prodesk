<?php
require '../../../__ssn/ssn_boot.php';


define('TYP', ($_GET['typ']=='epg') ? 'epg' : 'scnr');

$id = (isset($_GET['id'])) ? wash('int', $_GET['id']) : 0;

if (!$id) exit;


$r = [];

$where = [];



if (TYP=='epg') {
	
	$x['EPG'] = qry_assoc_row('SELECT ID, DateAir FROM epgz WHERE ID='.$id);
	$id = @$x['EPG']['ID'];	if (!$id) exit;
	$tbl = 'epg_elements';
	$cln = 'EpgID';

    $where[] = 'TermEmit>\''.TIMENOW.'\'';
    // We want to exclude past terms and even current term.
    // This also exludes the term-less lines, such as notes..

} else { // scnr
	
	$x['SCNR'] = qry_assoc_row('SELECT ID FROM epg_scnr WHERE ID='.$id);
	$id = @$x['SCNR']['ID'];	if (!$id) exit;
	$tbl = 'epg_scnr_fragments';
	$cln = 'ScnrID';
}




$where[] = 'IsActive';
$where[] = $cln.'='.$id;

// (conditions must be the same as in epg_dtl_html(), where *lang* and *id* attributes for TRs are set)
$sql = 'SELECT ID, TIME_FORMAT(TermEmit, \'%H%i%s\') AS hms FROM '.$tbl.
    ' WHERE '.implode(' AND ', $where).
    ' ORDER BY Queue ASC';//  LIMIT 5

$result = qry($sql);
while ($line = mysqli_fetch_assoc($result)) {
    $r[] = $line['ID'].':'.$line['hms'];
}






/////////////////////// OUTPUT

if (@!$r) exit;

if (@$_GET['outputarray']) {
	
	echo '<pre>';
	print_r($r);
	
} else {
	
	echo implode(',',$r);
	
	//echo json_encode($r);
}
