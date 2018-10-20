<?php
/**
 * Write to SETTINGS table
 */

require '../../__ssn/ssn_boot.php';

exit;
// CURRENTLY not used anywhere..

$name = (isset($_GET['name'])) ? wash('cpt', $_GET['name']) : ''; if (!$name) exit;

$vlu = (isset($_GET['vlu'])) ? wash('int', $_GET['vlu']) : 0;


setz_put($name, $vlu);

