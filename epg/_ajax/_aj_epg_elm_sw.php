<?php
/**
 * (DE)ACTIVATING, i.e. switching active state of an element/fragment on or off.
 * Fired by button on the right side of each element/fragment.
 */


require '../../../__ssn/ssn_boot.php';




define('TYP', ($_POST['typ']=='epg') ? 'epg' : 'scnr');


$sw = intval($_POST['switch']);

qry('UPDATE '.((TYP=='epg') ? 'epg_elements' : 'epg_scnr_fragments').
    ' SET IsActive = IF(IsActive=1,0,1) WHERE ID='.$sw, ['x_id' => $sw]);



// If we are (de)activating a fragment, then the duration of its parent element will change,
// thus we should run termemit update for the parent (and also linkers!) epg (i.e. other elements of that epg)

if (TYP=='scnr') {

    // For TMPL I intentionally omit this attribute because they should not run termemit.
    $elmid = intval(@$_POST['elmid']);

    if ($elmid) {

        $x = rdr_row('epg_elements', 'NativeType, NativeID', $elmid);

        $x['ScnrID'] = scnrid_universal($x['NativeID'], $x['NativeType']);

        $x['SCNR'] = qry_assoc_row('SELECT * FROM epg_scnr WHERE ID='.$x['ScnrID']);

        $x_emit = [
            'ID' => $x['SCNR']['ID'],
            'MatType' => $x['SCNR']['MatType'],
            'DurEmit' => $x['SCNR']['DurEmit'],
            'NativeType' => $x['NativeType'],
            'ElementID' => $elmid
        ];

        scnr_duremit($x_emit);
        // scnr_duremit will also run/loop sch_termemit, that's why we can omit it here.
    }
}

echo '1';


