<?php
/**
 * Story phase uptodater. The script is called via ajax, from EPG studio-view page.
 */

require '../../../__ssn/ssn_boot.php';


if (empty($_GET['idz'])) exit;



$idz = explode(',', $_GET['idz']);

$phz = [];

foreach ($idz as $v) {

    $phz[] = rdr_cell('stryz', 'Phase', intval($v));
}

echo implode($phz, ',');

