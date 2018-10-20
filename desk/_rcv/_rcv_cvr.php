<?php

// We intentionally skip usual pms() PMS checking here, on account of being too complicated to bother with it..


if (!$id) {

    $opt = [
        'new_ownertyp' => wash('int', @$_POST['OwnerType']),
        'new_ownerid' => wash('int', @$_POST['OwnerID'])
    ];

} else {

    $opt = null;
}



$x = cover_reader($id, $opt);


if (!empty($_GET['id']) && !@$x['ID']) { // Other user has deleted the cvr while this one was modifying it..
    omg_put('danger', $tx['MSG']['item_fail'].' ('.$tx['MSG']['deleted'].')');
    return;
}



$tbl[1] = 'epg_coverz';

$mdf[1]['TypeX']     = wash('int', @$_POST['TypeX']);
$mdf[1]['Texter']    = wash('txt', @$_POST['Texter']);
$mdf[1]['PageLabel'] = substr(wash('cpt', @$_POST['PageLabel']), 0, 7);

$mdf[1]['IsReady'] = wash('int', @$_POST['IsReady']);

$mdf[1]['ProoferUID'] = (@$_POST['Proofer']) ? UZID : 0;

$mdf[1]['TCin'] = rcv_datetime('hms_nozeroz',
    ['hh' => @$_POST['TCinHH'], 'mm' => @$_POST['TCinMM'], 'ss' => @$_POST['TCinSS']]);

$mdf[1]['TCout'] = rcv_datetime('hms_nozeroz',
    ['hh' => @$_POST['TCoutHH'], 'mm' => @$_POST['TCoutMM'], 'ss' => @$_POST['TCoutSS']]);


if (!@$x['ID']) { // new = insert

    $mdf[1]['OwnerType'] = wash('int', @$_POST['OwnerType']);
    $mdf[1]['OwnerID']	 = wash('int', @$_POST['OwnerID']);

    $mdf[1]['UID']	     = UZID;
    $mdf[1]['TermAdd']	 = TIMENOW;

    $mdf[1]['ChannelID'] = $x['ChannelID'];

    $x['ID'] = receiver_ins($tbl[1], $mdf[1]);

} else { // modify = update

	receiver_upd($tbl[1], $mdf[1], $x);
}





if (!IFRM) {

    // CVR modify page can be accessed from several pages, and user should be returned to the calling page after submit.

    if (!isset($_GET['rfrscr'])) { // referer script is cvr_details

        $url = $_SERVER['SCRIPT_NAME'].'?id='.$x['ID'];

    } else {

        if ($_GET['rfrscr']=='stry_details') {

            $url = '/desk/stry_details.php?id='.$_GET['rfrid'];

        } elseif ($_GET['rfrscr']=='epg') {

            $url = '/epg/epg.php?view=2&typ='.$_GET['rfrtyp'].'&id='.$_GET['rfrid'];
        }
    }

    hop($pathz['www_root'].$url);

} else {

    echo '<script type="text/javascript">window.parent.location.reload()</script>';
    exit;
}

