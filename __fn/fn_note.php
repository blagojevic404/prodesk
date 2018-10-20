<?php


// notes



/**
 * NOTE reader
 *
 * @param int $id Native ID
 * @param int $typ Native Type ID
 * @return string $note	Note
 */
function note_reader($id, $typ) { 

	$sql = 'SELECT Note FROM cn_notes WHERE NativeType='.$typ.' AND NativeID='.$id;

    $note = qry_numer_var($sql);

	return $note;
}



/**
 * NOTE deleter
 *
 * @param int $id Native ID
 * @param int $typ Native Type ID
 * @return void
 */
function note_deleter($id, $typ) {

    qry('DELETE FROM cn_notes WHERE NativeType='.$typ.' AND NativeID='.$id);
}




/**
 * NOTE receiver
 *
 * @param int $id Native ID
 * @param int $typ Native Type ID
 * @param string $note Current value
 * @param array $log Log data array
 *
 * @return void
 */
function note_receiver($id, $typ, $note, $log=null) {

    $tbl = 'cn_notes';
	
	$mdf['Note'] = wash('txt', @$_POST['Note']);
	
	$cur['Note'] = $note;
	
	if ($cur==$mdf) {
        return;
    }

    if (!empty($cur['Note']) && empty($mdf['Note'])) {
        note_deleter($id, $typ);
        return;
    }


    $where_arr  = ['NativeID'=>$id, 'NativeType'=>$typ];

    if ($log) {
        $log = ['tbl_name' => $log['tbl_name'], 'x_id' => $log['x_id'], 'act_id' => 62, 'act' => 'note'];
    } else {
        $log = LOGSKIP;
    }

    receiver_mdf($tbl, $where_arr, $mdf, $log);
}



