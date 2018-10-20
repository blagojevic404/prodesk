<?php

// receivers







/**
 * Takes an array with column-value pairs and strings it into sql, ready to be used for WHERE part of the SELECT (or DELETE) query
 *
 * @param array $arr_pairs Array with column-value pairs (Keys are column names.)
 * @return string $where Stringed WHERE sql
 */
function receiver_sql4select($arr_pairs) {

    foreach ($arr_pairs as $k => $v) {
        $sql_pairs[$k] = ($v!==NULL) ? "$k='$v'" : "$k IS NULL";
    }

    $where = implode(' AND ', $sql_pairs);

    return $where;
}



/**
 * Takes an array with column-value pairs and strings it into sql, ready to be used for WHERE part of the UPDATE query
 *
 * @param array $arr_pairs Array with column-value pairs (Keys are column names.)
 * @return string $where Stringed WHERE sql
 */
function receiver_sql4update($arr_pairs) {

    foreach ($arr_pairs as $k => $v) {
        $sql_pairs[$k] = ($v!==NULL) ? "$k='$v'" : "$k=NULL";
    }

    $where = implode(', ', $sql_pairs);

    return $where;
}



/**
 * Takes an array with column-value pairs and strings it into two sqls (for columns list, and for values list),
 * ready to be used in the INSERT query
 *
 * @param array $arr_pairs Array with column-value pairs (Keys are column names.)
 * @return array $r SQL for COLUMNS ('clnz') and for VALUES ('valz') in INSERT query
 */
function receiver_sql4insert($arr_pairs) {
	
	$r['clnz'] = array_keys($arr_pairs);
	$r['valz'] = array_values($arr_pairs);
	
	foreach ($r['valz'] as $k => $v) {                          // put single quotes around each value (except NULL)
        $r['valz'][$k] = ($v!==NULL) ? "'$v'" : "NULL";
    }

	$r['clnz'] = implode(', ', $r['clnz']);
	$r['valz'] = implode(', ', $r['valz']);

	return $r;
}









/**
 * Simple INSERT wrap, with no checking whether data already exists. Used for NEW operations.
 *
 * @param string $tbl Table name
 * @param array $arr_pairs Array with column-value pairs (Keys are column names.) - INSERT data
 * @param array $log Log data array
 * @param bool $arhv Whether to target ARHV server
 *
 * @return int $id Insert ID
 */
function receiver_ins($tbl, $arr_pairs, $log=null, $arhv=false) {

	$str_insert = receiver_sql4insert($arr_pairs); // turn insert-data array into strings (columns + values)
	
	$sql = 'INSERT INTO '.$tbl.' ('.$str_insert['clnz'].') VALUES ('.$str_insert['valz'].')';
		
	$id = qry($sql, $log, $arhv);

    return $id;
}



































/**
 * Updater with optimisation check (doesn't update if there is nothing to update, i.e. none of the values has changed)
 *
 * @param string $tbl Table name
 * @param array $mdf_arr Array with new (MDF) values. Keys are column names.
 * @param array $cur_arr Array with old (CUR) values. Keys are column names. Must have 'ID' key.
 * @param array $log Log data array
 *
 * @return bool Whether anything has changed or not
 */
function receiver_upd($tbl, $mdf_arr, $cur_arr, $log=null) {

	$mdf_arr = receiver_upd_optimizer($mdf_arr, $cur_arr); // Clean up values which are the same in CUR and MDF, i.e. not changed
	if (!$mdf_arr) return false;// If there are no any changed values left, then update itself is not neccesery.

    receiver_upd_short($tbl, $mdf_arr, $cur_arr['ID'], $log);

	return true;
}




/**
 * Simple UPDATE wrap: builds UPDATE sql using UPDATE key-value pairs et al, then performs the UPDATE query
 *
 * @param string $tbl Table name
 * @param array $mdf_arr Array with column-value pairs for UPDATE
 * @param int $id Row ID
 * @param array $log Log data array
 * @return void
 */
function receiver_upd_short($tbl, $mdf_arr, $id, $log=null) {

	$sql = 'UPDATE '.$tbl.' SET '.receiver_sql4update($mdf_arr).' WHERE ID='.$id;

    if (!isset($log['x_id'])) {
        $log['x_id'] = $id;
    }

	qry($sql, $log);
}




/**
 * Compares new (MDF) values with old (CUR) values, and then deletes those new values which are SAME AS old ones.
 * (If they are the same, then no need to add them to UPDATE sql.)
 *
 * @param array $mdf_arr Array with new (MDF) values
 * @param array $cur_arr Array with old (CUR) values
 * @return array $mdf_arr FILTERED array with new (MDF) values
 */
function receiver_upd_optimizer($mdf_arr, $cur_arr) {

    // If MODIFY value is same as CURRENT value, then modifying is not necessary, so we delete that element from the modify array.
    foreach ($mdf_arr as $k => $v) {
        if (isset($cur_arr[$k]) && $cur_arr[$k]==$v) {
            unset($mdf_arr[$k]);
        }
    }

    return $mdf_arr;
}



















/**
 * MODIFY operation handler for one-to-one case (i.e. there can only be one record with filtered data or none)
 * Determines whether to modify data by UPDATE or INSERT and then performs it.
 *
 * @param string $tbl Table name
 * @param array $where_arr Array with WHERE values. Keys are column names.
 * @param array $mdf_arr Array with new (MDF) values. Keys are column names.
 * @param array $log Log data array
 * @param array $cur_arr Array with old (CUR) values. Keys are column names. Must have 'ID' key.
 *
 * @return void
 */
function receiver_mdf($tbl, $where_arr, $mdf_arr, $log=null, $cur_arr=null) {

    // Check whether data record already exists, to define whether this should be UPDATE or INSERT operation
    $line = qry_numer_row('SELECT ID FROM '.$tbl.' WHERE '.receiver_sql4select($where_arr));

	if ($line) { // UPDATE
        if ($cur_arr) {
            receiver_upd($tbl, $mdf_arr, $cur_arr, $log);
        } else {
            receiver_upd_short($tbl, $mdf_arr, $line[0], $log);
        }
	} else {	// INSERT
        $id = receiver_ins($tbl, array_merge($mdf_arr, $where_arr), $log);
	}
}





/**
 * MODIFY operation handler for one-to-many case (i.e. there can be multiple records with filtered data, e.g. film-genre)
 *
 * @param string $tbl Table name
 * @param array $where_arr Array with WHERE column-value pairs.
 * @param string $cln Target column
 * @param array $cln_mdf Array with MDF values
 * @param array $cln_cur Array with CURRENT values
 * @return void
 */
function receiver_mdf_array($tbl, $where_arr, $cln, $cln_mdf, $cln_cur) {

    // Check if MDF and CUR data arrays are the same.. If they are, then no need to do anything, so return.

    $cln_mdf = array_values($cln_mdf);
    $cln_cur = array_values($cln_cur);
    sort($cln_mdf);
    sort($cln_cur);

    if ($cln_mdf==$cln_cur) {
        return;
    }

    // If we get to here, that means the values have changed, so we proceed.

    // We don't do UPDATE, we do DELETE-INSERT
    // So, if there are any current values, first purge them from db and then we will write new ones.

    if ($cln_cur) {
        qry('DELETE FROM '.$tbl.' WHERE '.receiver_sql4select($where_arr));
    }


    $mdf_arr = $where_arr;     // First, add CONSTANT columns to MDF array (columns from WHERE clause)

    foreach ($cln_mdf as $v) {

        $mdf_arr[$cln] = $v; // Iterate through data array and add the target column (value changes on each iteration) to MDF array

        receiver_ins($tbl, $mdf_arr, LOGSKIP);
    }

}




















/** vbdo
 * receiver_post() - POST controls receiver
 *
 * @param string $typ Type: chk=checkbox, optionbox (or combo)
 * @param string $name Name of the form element and session variable
 * @param int $def Default value for session variable
 * @return
 */
function receiver_post($typ, $name, $def=0) {

    global $pathz;


    switch ($typ) {

        case 'opt':

            $cond = ( isset($_POST[$name]) && $_POST[$name]!=@$_SESSION[$name] );
            break;

        case 'chk':
            $cond = ( (@$_POST[$name] && !@$_SESSION[$name]) || ($_POST && @!$_POST[$name] && @$_SESSION[$name]) );
            break;
    }


    if ($cond) {

        $_SESSION[$name] = intval(@$_POST[$name]);

        if ($name=='channel') $_SESSION['prg']=''; // prm/articles: when we change channel, reset the programs cbo

        hop($pathz['www_root'].$_SERVER['REQUEST_URI']);

    } else {

        if (!isset($_SESSION[$name])) $_SESSION[$name] = $def;
    }

}



/* * vbdo
 * receiver_cn() - Receiver for many-to-many case, (with built-in fetching MDF values from $_POST)
 *
 * @param string $tbl Table name
 * @param array $where_arr Array with column values that are constant, i.e. must exist and should not change
 * @param array $cur_arr Array with old (CUR) values (keys are column names)
 * @return void
 * /
function receiver_cn($tbl, $where_arr, $cur_arr) {

    // fetching MDF values from $_POST (CUR keys must have same names as $_POST keys for this to work)
    foreach ($cur_arr as $k => $v) {
        $mdf_arr[$k] = wash('int', @$_POST[$k]);
    }

    $empt = receiver_mdf_delete($tbl, $mdf_arr, receiver_sql4select($where_arr));
    if ($empt) {
        return;
    }

    $mdf_arr = receiver_upd_optimizer($mdf_arr, $cur_arr);
    if (!$mdf_arr) {
        return;
    }

    receiver_mdf($tbl, $where_arr, $mdf_arr);

    // 20130524: for now, used only for photo agencies and photographers
}




/* *
 * receiver_mdf_delete() - deleting the row if all MDF values are null
 * 
 * Used only in receiver_cn
 *
 * @param string $tbl Table name
 * @param array $mdf_arr Array with new (MDF) values
 * @param array $where WHERE sql
 * @return bool Deleted or NotDeleted
 * /
function receiver_mdf_delete($tbl, $mdf_arr, $where) {

    $empt = true;

    foreach ($mdf_arr as $v) {
        if ($v) $empt = false;   // the array with new (MDF) values is NOT empty
    }

    if ($empt) { // if it is empty then we can delete the existing CN rows
        qry('DELETE FROM '.$tbl.' WHERE '.$where);
    }

    return $empt;
}
*/




