<?php
/**
 * LOG MDF - logs current MDF situations in order to prevent simultaneous MDF of the same item
 */

require '../../__ssn/ssn_boot.php';



$x['id'] = (isset($_GET['id'])) ? wash('int', $_GET['id']) : 0;

$x['del'] = (isset($_GET['del'])) ? wash('int', $_GET['del']) : 0;

if (!$x['id'] && !$x['del']) exit;




if ($x['id']) {

    if (isset($_GET['checker'])) {

        // Show warning if someone is currently working on the story (modifying)
        require $pathz['rel_rootpath'].'../__fn/fn_mdflog.php';
        echo mdflog_conflict(250, $x['id'], 'msg');
        exit;

    } else {

        qry('UPDATE log_mdf SET TermAccess=\''.TIMENOW.'\' WHERE ID='.$x['id']);
    }

} elseif ($x['del']) {

    qry('DELETE FROM log_mdf WHERE ID='.$x['del']);
}
