<?php

// COMMON functions

// This file can be loaded before the SCTN is determined, (i.e. after ssn_sets.php), and thus these functions
// can be used within ssn_sets.php.



require 'fn_rcv.php';
require 'fn_txt.php';
require 'fn_pms.php';
require 'fn_rdrz.php';
require 'fn_tim.php';
require 'fn_out.php';
require 'fn_setz.php';

require 'fn_note.php';
require 'fn_mos.php';
require 'fn_crw.php';
require 'fn_ifrm.php';




/**
 * Get Table ID from name or name from ID
 *
 * @param string $get_typ Type (id, name)
 * @param string $x Table name or ID
 *
 * @return int|string $r
 */
function tablez($get_typ, $x) {

    global $tablez;

    $r = ($get_typ=='id') ? array_search($x, $tablez) : $tablez[$x];

    return $r;
}





/**
 * Counts rows that specified SQL SELECT query would found
 *
 * @param string $tbl Table name
 * @param string $where WHERE clause
 * @param string $groupby GROUP BY clause
 *
 * @return int $cnt Count of found rows
 */
function cnt_sql($tbl, $where, $groupby=null) {

	mysqli_query($GLOBALS["db"], 'SELECT ID FROM '.$tbl.' WHERE '.$where.(($groupby) ? ' GROUP BY '.$groupby : ''));
	list($cnt) = mysqli_fetch_row(mysqli_query($GLOBALS["db"], 'SELECT FOUND_ROWS()'));
	
	return intval($cnt);
}





/**
 * Array slice function that works with associative arrays (keys)
 *
 * @param array $arr Input array
 * @param array $keys Keys to slice
 * @return array
 */
function array_slice_assoc($arr, $keys) {
    return array_intersect_key($arr, array_flip($keys));
}








/**
 * Strings onclick attribute for ajax button
 *
 * @param array $opt
 *  - type (string): (POST/GET). Use GET for SELECT, use POST for MDF!
 *  - trigger (string): js action which will trigger ajax (default: onclick)
 *  - url (string): ajax url (php script)
 *  - data (string): ajax data (will be sent to php script)
 *  - fn (string): js function to be called on ajax success
 *  - fn_arg (string): argument to pass along with the js function on ajax success
 *  - btn_disable (bool): whether to add call for js function ajax_btn_disable()
 *
 * @return string $r
 */
function ajax_onclick($opt) {

    if (!isset($opt['type']))           $opt['type'] = 'POST';
    if (!isset($opt['trigger']))        $opt['trigger'] = 'onclick';
    if (!isset($opt['fn_arg']))         $opt['fn_arg'] = 'this';
    if (!isset($opt['btn_disable']))    $opt['btn_disable'] = false;

    $r = ' '.$opt['trigger'].'="'.
        'ajaxer(\''.$opt['type'].'\', \''.$opt['url'].'\', \''.$opt['data'].'\', \''.$opt['fn'].'\', '.$opt['fn_arg'].');'.
        (($opt['btn_disable']) ? 'ajax_btn_disable(this);' : '').
        'return false;" ';

    return $r;
}







/**
 * Fetches a channel or channel list
 *
 * @param array $where Filter:
 *  - type (int|array) - Channel type: 1-tv, 2-radio, 3-pseudo (returns list of channels)
 *  - id (int) - Channel ID (returns a specified channel)
 *
 * @param bool $shortcpt Whether to return short caption or normal caption
 *
 * @return array|int
 */
function channelz($where=null, $shortcpt=false) {

    global $cfg;


    $cpt_cln = ($shortcpt) ? 'Caption_short' : 'Caption';

    if (isset($where['id'])) {

        $r = qry_numer_var('SELECT '.$cpt_cln.' FROM channels WHERE ID='.$where['id']);

    } elseif (isset($where['typ'])) {

        $where = (is_array($where['typ'])) ? 'TypeX IN ('.implode(', ', $where['typ']).')' : 'TypeX='.$where['typ'];
        $r = qry_numer_arr('SELECT ID, '.$cpt_cln.' FROM channels WHERE '.$where.' ORDER BY Queue');

    } else {

        $r = qry_numer_arr('SELECT ID, '.$cpt_cln.' FROM channels ORDER BY ID');
    }


    if (@$cfg['cyr2lat'] && in_array(LNG, [2,4])) {
        $r = text_convert($r, 'cyr', 'lat');
    }

    return $r;
}





/**
 * Html for channel cbo in navigation bar
 */
function channelz_cbo() {

    $chnl_arr = channelz(['typ' => [1,2]]);

    $tmp = [];

    foreach ($chnl_arr as $k => $v) {
        $tmp[$k] = '<option value="'.$k.'"'.( ($k==CHNL) ? ' selected' : '' ).'>'.$v.'</option>';
    }

    $r = implode($tmp);

    return $r;
}






/**
 * Html for expandTxtarea JS event setting
 */
function expandTxtarea($rows) {

    $r = ' onKeyUp="expandTxtarea(this,'.$rows.')"'.
        ' onClick="expandTxtarea(this,'.$rows.')"'.
        ' oninput="expandTxtarea(this,'.$rows.')"';

        // oninput is new html5 event, it will fire e.g. when user choses *Paste* from the contextual menu
        // (other two events skip this)

    return $r;
}




/**
 * Get list (array) of active languages
 */
function languages_act() {

    $lngz = txarr('arrays','languages');

    if (SERVER_TYPE!='dev' && UZID!=UZID_ALFA) {

        $lngz_act = cfg_global('arrz','languages_act');

        foreach ($lngz_act as $k => $v) {
            if (!$v) unset($lngz[$k]);
        }
    }

    return $lngz;
}






/**
 * Check if Chromium browser version is "good" (which means printing will be fine)
 *
 * @return int $v Either VERSION - for good (i.e. not old) Chromium, or "0" - for old one, or "-1" - for not-Chromium
 */
function is_chromium_good() {

    $a = strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome/');

    if ($a) { // IS Chrome

        $v = intval(substr($_SERVER['HTTP_USER_AGENT'], $a+7, 2));

        if ($v>0 && ($v<50 || $v>62)) { // For versions below 50, printing is large

            $v = 'PRINT_1';

        } else {

            $v = 'PRINT_2';
        }

    } else { // NOT Chrome

        $v = 'PRINT_1';
    }

    return $v;
}





/**
 * Attributes for turning an element to *draggable*
 *
 * @param string $case_typ Case type (spicer, mktepg)
 * @param int $id Target ID
 * @param string $target_typ Target type (item, bloc).
 * Note: *bloc* is used in EPG SPICER, and it doesn't need starter attibutes.
 * @param bool $not_draggable We don't want the item to be draggable, it should only be able to *receive* drops.
 *
 * @return string $r
 */
function drg_attrz($case_typ, $id, $target_typ='item', $not_draggable=false) {

    $r = (!$not_draggable) ? 'draggable="true" ondragstart="drg_start(event)" ' : ''; // Starter attributes

    $r .= 'ondragover="drg_over(event)" ondragleave="drg_leave(event)" ondrop="drg_drop(event)" ';

    $r .= 'id="'.$target_typ.$id.'" CaseType="'.$case_typ.'"';

    return $r;
}





/**
 * Get an array from the LNG *opposite* item (item which holds an array)
 *
 * @param string $txt LNG *opposite* item (item which holds an array)
 * @param int $r_key Return key (if you want to return not the array but only a specific array value)
 * @param array $opt
 *  - delimiter (string): Delimiter char for explode() function
 *  - start_key (int): Start key, to be used instead of the 0 key
 *  - reverse (bool): Sort in reverse order
 *
 * @return array|string $r
 */
function lng2arr($txt, $r_key=null, $opt=null) {

    if (empty($opt['delimiter'])) {
        $opt['delimiter'] = '/';
    }

    $r = explode($opt['delimiter'], $txt);

    if (!empty($opt['start_key'])) {
        $r[$opt['start_key']+1] = $r[1];
        $r[$opt['start_key']] = $r[0];
        unset($r[0]);
    }

    if (!empty($opt['reverse'])) {
        krsort($r);    }

    if ($r_key===null) {
        return $r;
    } else {
        return $r[$r_key];
    }
}



/**
 * Output either header for displaying XML, or for downloading it
 *
 * @param string $output Output type
 * @param string $filename Filename for download
 * @return void
 */
function downer_header($output, $filename) {

    $output = ($output=='text') ? 'text' : 'file';

    if ($output=='file') {

        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

    } else { // text

        header('content-type: application/xml; utf-8');
    }
}

