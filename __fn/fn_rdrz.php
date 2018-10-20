<?php

// readers






/**
 * Fetch from db: ID for the row specified by other data
 *
 * @param string $tbl Table
 * @param string $where WHERE sql
 * @param string $order ORDER sql
 * @return int $r ID
 */
function rdr_id($tbl, $where, $order='') {

    if ($order) {
        $order = 'ORDER BY '.$order;
    }

    $r = qry_numer_row("SELECT ID FROM $tbl WHERE $where $order");
    return intval($r[0]);
}






/**
 * Fetch from db: a cell
 *
 * @param string $tbl Table name
 * @param string $cln Column
 * @param string $where WHERE clause. If it is integer then 'ID=' will be prepended.
 * @param string $order Optional ORDER clause
 * @return string $r
 */
function rdr_cell($tbl, $cln, $where, $order=null) {

    if (is_numeric($where)) {       // If it is integer then 'ID=' will be prepended
        $where = 'ID='.$where;
    }

    if ($order) {
        $order = 'ORDER BY '.$order;
    }

    $sql = "SELECT $cln FROM $tbl WHERE $where $order";

    $r = qry_numer_var($sql);

    return $r;
}




/**
 * Fetch from db: row
 *
 * @param string $tbl Table name
 * @param string $clnz Columnz
 * @param string $where WHERE clause. If it is integer then 'ID=' will be prepended.
 * @param string $order Optional ORDER BY clause
 * @return array $r an Associative array (column names are keys).
 */
function rdr_row($tbl, $clnz, $where, $order=null) {

    if (is_numeric($where)) {       // If it is integer then 'ID=' will be prepended
        $where = 'ID='.$where;
    }

    if ($order) {
        $order = 'ORDER BY '.$order;
    }

    $sql = "SELECT $clnz FROM $tbl WHERE $where $order";

    $r = qry_assoc_row($sql);

    return $r;
}






/**
 * Fetch from db: column
 *
 * @param string $tbl Table name
 * @param string $cln Column name
 * @param string $where Optional WHERE clause. If it is integer then 'ID=' will be prepended.
 * @param string $order Optional ORDER BY clause
 * @param string $key_cln Column which will be used for KEYS for returning array ($x(KEY)=value).
 *	                      Default is ID, but '-1' will set it as NULL thus making the return array have incremental keys.
 * @param string $limit Optional LIMIT clause
 * @return array $ctg_arr Numeric array
 */
function rdr_cln($tbl, $cln, $where='', $order='', $key_cln='ID', $limit='') {

    // '-1' disables using any column for KEY part of the returning KEY=VALUE pairs, instead uses simple incremental keys
	if ($key_cln==-1) {
        $key_cln = '';
    }

    // If *key_cln* and *cln* are the same, then we delete one of them
    if ($cln==$key_cln) {
        $cln = '';
    }

    // If both *key_cln* and *cln* are set (and they are different), then we have to put comma between them
    $cln = $key_cln.(($key_cln && $cln) ? ', ' : '').$cln;


    if (is_numeric($where)) {       // If it is integer then 'ID=' will be prepended
        $where = 'ID='.$where;
    }

	if ($where) {
        $where = 'WHERE '.$where;
    }


	if ((!$order) && ($key_cln)) {
        $order = $key_cln.' ASC';
    }

	if ($order) {
        $order = 'ORDER BY '.$order;
    }

	if ($limit) {
        $limit = 'LIMIT '.$limit;
    }


	$result = qry("SELECT $cln FROM $tbl $where $order $limit");
	while ($line = mysqli_fetch_row($result)) {

		switch (count($line)) {

			case 3:
				$ctg_arr[$line[0]][0] = $line[1];
				$ctg_arr[$line[0]][1] = $line[2];
				break;

			case 2:
				$ctg_arr[$line[0]] = $line[1];
				break;

			default:
				$ctg_arr[] = $line[0];
		}
	}


	if (isset($ctg_arr)) {
		return $ctg_arr;
	} else {
		return [];
	}
}








/**
 * Builds HTML menu (combo) from specified array
 *
 * @param array $arr Input array
 * @param int|array $slcted Selected key(s), if any
 * @param string $zero_txt Text for zero value, if any
 * @param bool $indexing_by_values Whether menu INDEXING should be based on VALUES of input array
 *                                 (instead on KEYS, which is default).. (For example, if it is HH mnu)
 * @return string $r HTML for menu (without <SELECT> part)
 */
function arr2mnu($arr, $slcted=0, $zero_txt='', $indexing_by_values=false) {

    if (!is_array($slcted)) {
        $slcted = [$slcted];
    }

    $r = [];

    if ($zero_txt) {
        $r[] = '<option value="">'.$zero_txt.'</option>';
    }

    foreach($arr as $k => $v) {
        $z = ($indexing_by_values) ? $v : $k;
        $r[] = '<option value="'.$z.'"'.((in_array($z, $slcted)) ? ' selected' : '').'>'.$v.'</option>';
    }

    return implode('', $r);
}








/**
 * Get the name for the specified UID
 *
 * @param int $uid UID
 * @param array $frmt Format data
 * - n1_typ - FirstName type (normal, init, none)
 * - n2_typ - SecondName type (normal, init, none)
 * - dot - Character which will be used for DOT after an initial (only for *init* name type)
 * - space - Character which will used for SPACE between names
 * @param bool $scrambler Whether we got here from scrambler
 *
 * @return string Name
 */
function uid2name($uid, $frmt=null, $scrambler=false) {

    global $cfg;

    if (!$uid) {
        return false;
    }


    if (!isset($frmt['n1_typ']))        $frmt['n1_typ'] = 'normal';
    if (!isset($frmt['n2_typ']))        $frmt['n2_typ'] = 'normal';
    if (!isset($frmt['dot']))           $frmt['dot'] = '.';
    if (!isset($frmt['space']))         $frmt['space'] = '&nbsp;';


    switch ($frmt['n1_typ']) {
        case 'init': 	$n1 = 'LEFT(Name1st, 1)'; if ($frmt['dot']) $n1 .= ",'{$frmt['dot']}'"; break;
        case 'none':    $n1 = ''; break;
        case 'normal':
        default: 	    $n1 = 'Name1st'; break;
    }

    switch ($frmt['n2_typ']) {
        case 'init': 	$n2 = 'LEFT(Name2nd, 1)'; if ($frmt['dot']) $n2 .= ",'{$frmt['dot']}'"; break;
        case 'none':    $n2 = ''; break;
        case 'normal':
        default: 	    $n2 = 'Name2nd'; break;
    }


    if ($n1 && $n2) {
        $space = ($frmt['space']) ? ",'{$frmt['space']}'," : ',';
    } else {
        $space = '';
    }

    $sql = 'SELECT CONCAT('.$n1.$space.$n2.') AS name_full FROM hrm_users WHERE ID='.$uid;
    $name = qry_numer_var($sql);


    if (UZID==UZID_ALFA && LNG!=1 && @$cfg['user_scramble'] && $uid!=UZID_ALFA && !$scrambler) {
        $name = user_scramble($uid, $frmt); // vbdo
    }

    if (@$cfg['cyr2lat'] && in_array(LNG, [2,4])) {
        $name = text_convert($name, 'cyr', 'lat');
    }

    return $name;
}


/**
 * Scramble user firstname and lastname (I use it when creating user manual videos or images)
 *
 * @param int $uid UID
 * @param array $frmt Format data (same as in uid2name())
 *
 * @return string Name
 */
function user_scramble($uid, $frmt) {

    $uid_next1 = qry_numer_var('SELECT ID FROM hrm_users WHERE ID>'.($uid-100).' LIMIT 1');
    $uid_next2 = qry_numer_var('SELECT ID FROM hrm_users WHERE ID>'.($uid-40).' LIMIT 1');

    $arr_name_next1 = explode('&nbsp;', uid2name($uid_next1, $frmt, true));
    $arr_name_next2 = explode('&nbsp;', uid2name($uid_next2, $frmt, true));

    if (isset($arr_name_next2[1])) {

        $double_surname = strpos($arr_name_next2[1], '-');

        if (!$double_surname) $double_surname = strpos($arr_name_next2[1], ' ');

        if ($double_surname) $arr_name_next2[1] = substr($arr_name_next2[1], 0, $double_surname);
    }

    $name = $arr_name_next1[0].(($frmt['n1_typ']!='none' && $frmt['n2_typ']!='none') ? '&nbsp;'.$arr_name_next2[1] : '');

    return $name;
}









/**
 * String inner html for USERS combo
 *
 * @param array $groups Groups from which we will read users
 * @param int $uid_sel UID which should be selected, if any
 * @param array $opt Options:
 * - zero-txt (string): Text for the line with value 0 which will be added at the beginning of the combo if this option is set.
 *                      Leave empty if you do not need zero line at all. Use shortcut "*" for text label "all".
 * - divider (bool): Whether to divide user groups by <optgroup> tags
 * - arr_static (array): Static lines to add at the beggining of the combo
 *
 * @return string Stringed inner html for user combo
 */
function users2mnu($groups, $uid_sel=0, $opt) {
	
	global $tx;

	
	if (isset($opt['zero-txt']) && $opt['zero-txt']=='*') {
        $opt['zero-txt'] = $tx['LBL']['all'];
    }

    $html = (empty($opt['zero-txt'])) ? '' : '<option value="">'.$opt['zero-txt'].'</option>';


    if (isset($opt['arr_static'])) {
        foreach ($opt['arr_static'] as $v) {
            $html .= '<option value="'.$v[0].'"'.(($v[0]==$uid_sel) ? ' selected' : '').'>'.$v[1].'</option>';
        }
    }


    if (count($groups)==1) {
        $opt['divider'] = false;
    }


	if (empty($opt['divider'])) {

        $html .= users2mnu_group($groups, $uid_sel);

    } else {

        foreach ($groups as $v) {

            $html .= '<optgroup label="&nbsp;'.qry_numer_var('SELECT Title FROM hrm_groups WHERE ID='.$v).'">'.
                users2mnu_group([$v], $uid_sel).'</optgroup>';
        }
    }

	return $html;
}

/**
 * String html for a group of users in USERS combo. Helper function for users2mnu().
 *
 * @param array $groups Groups from which we will read users
 * @param int $uid_sel UID which should be selected, if any
 * @return string Stringed inner html for user combo
 */
function users2mnu_group($groups, $uid_sel) {

    $html = [];

    $sql = 'SELECT ID, CONCAT(Name1st,\'&nbsp;\',Name2nd) AS name_full FROM hrm_users '.
        'WHERE IsActive AND (IsHidden=0 OR IsHidden IS NULL) AND '.
        'GroupID IN ('.implode(',', $groups).') '.
        'ORDER BY Name1st ASC, Name2nd ASC';

    $a = qry_numer_arr($sql);

    foreach ($a as $k => $v) {
        $html[] = '<option value="'.$k.'"'.(($k==$uid_sel) ? ' selected' : '').'>'.$v.'</option>';
    }

    return implode('', $html);
}





