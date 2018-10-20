<?php
/**
 * EDITDIVABLE line
 */

require '../../__ssn/ssn_boot.php';


// $post = $_GET; // We use $_GET when troubleshooting.
$post = $_POST;


/*
* Data in CSV format:
* 0 - TBLID (!!! Not the table, but the table *ID* !!!)
* 1 - ITEM-ID
* 2 - CLN
* 3 - PMS (e.g. hrm/uzr/admin)
* 4 - VAL-TYP (int, cpt, txt)
*/
$z = (isset($post['ajax'])) ? explode(',', $post['ajax']) : exit;



$aj['tblid'] = intval($z[0]); if (!$aj['tblid']) exit;

$aj['tbl'] = tablez('name', $aj['tblid']); if (!$aj['tbl']) exit;

$aj['itemid'] = intval($z[1]); if (!$aj['itemid']) exit;

$aj['cln'] = wash('cpt', $z[2]);

if ($z[3]) {
    $aj['pms'] = explode('.', wash('cpt', $z[3]));
    pms($aj['pms'][0], $aj['pms'][1], null, true);
}

$aj['val_typ'] = wash('arr_assoc', $z[4], ['int', 'cpt', 'txt']); if (!$aj['val_typ']) exit;

$aj['val'] = (isset($post['val'])) ? wash($aj['val_typ'], $post['val']) : exit;

//echo '<pre>'; print_r($aj); exit;// for troubleshooting




if ($aj['tbl']=='cfg_varz') {

    require '../../__fn/lib_admin.php';

    admin_cfg_put($aj['cln'], $aj['val']);

    echo '1';

    exit;
}



// Username change: first check whether it already exists!
if ($aj['tbl']=='hrm_users' && $aj['cln']=='ADuser') {
    $user_exists = rdr_id('hrm_users', 'ADuser=\''.$aj['val'].'\'');
    if ($user_exists) {
        exit('-1');
    }
}



if ($aj['tbl']=='epg_tips') {

    require '../../__fn/lib_epg.php';

    $quirk = explode('_', $aj['cln']); // We use *column* attribute to pass sch-type and tip-type

    $tip = [
        'schtyp' => $quirk[0],
        'schline' => $aj['itemid'],
        'tiptyp' => $quirk[1]
    ];

    epg_tip_receiver($tip, $aj['val']);

    echo '1';

    exit;
}





$log = ['tbl_name' => $aj['tbl'], 'x_id' => $aj['itemid']];

qry('UPDATE '.$aj['tbl'].' SET '.$aj['cln'].'=\''.$aj['val'].'\' WHERE ID='.$aj['itemid'], $log);

$cnt = mysqli_affected_rows($GLOBALS["db"]);

if ($cnt) {
    echo '1';
} else {
    echo '0';
}
