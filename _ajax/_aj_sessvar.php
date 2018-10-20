<?php
/**
 * Write to SESSION variable
 */

require '../../__ssn/ssn_boot.php';



$name = (isset($_GET['name'])) ? wash('cpt', $_GET['name']) : ''; if (!$name) exit;


if (isset($_GET['val'])) {

    $val = $_GET['val'];

} else {

    $val = 1 - (empty($_SESSION[$name]) ? 0 : 1);
}


$_SESSION[$name] = $val;

