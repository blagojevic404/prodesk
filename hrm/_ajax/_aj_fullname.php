<?php
/**
 * Check whether specified fullname already exists.
 */

require '../../../__ssn/ssn_boot.php';



$name1 = wash('cpt', @$_POST['name1']);
$name2 = wash('cpt', @$_POST['name2']);

if (!$name1 || !$name2) {
    exit('0');
}


$fullname_exists = rdr_id('hrm_users', 'Name1st=\''.$name1.'\' AND Name2nd=\''.$name2.'\'');


echo intval($fullname_exists);

