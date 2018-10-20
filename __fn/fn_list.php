<?php

// LIST






/**
 * Reads list settings. First it tries *custom* (i.e. user-defined) setz (from DB), then it tries *global* setz (from list script).
 *
 * Note: Uses constants to filter USER and LIST
 *
 * @return array $lsetz Memorized list settings
 */
function list_setz_get() {

    global $lsetz;


    $sql = 'SELECT SettingName, SettingValue FROM settingz_lst WHERE TableID='.TBLID.' AND UID='.UZID.
        ' AND ChannelID='.$lsetz['chnl'];

    $lsetz_get = [];

    // Read setz from the *custom* level
    $result = qry($sql);
    while ($line = mysqli_fetch_assoc($result)) {
        $lsetz_get[$line['SettingName']] = $line['SettingValue'];
    }

    // Read setz from the *global* level
    if (!empty($lsetz['global'])) {
        foreach ($lsetz['global'] as $k => $v) {
            if (!isset($lsetz_get[$k])) {
                $lsetz_get[$k] = $v;
            }
        }
    }

    return $lsetz_get;
}


/**
 * Saves *custom* list settings to DB. Only setz which differ from the *global* setz.
 *
 * @return void
 */
function list_setz_put() {

    global $lsetz;

    $lsetz_global = (empty($lsetz['global'])) ? null : $lsetz['global'];

    $chnl = $lsetz['chnl']; // Channel ID. If the list is dependless of channel, then 0.

    $lsetz_new = $lsetz['put']; // NEW list settings


    $sql = 'SELECT SettingName, SettingValue FROM settingz_lst WHERE TableID='.TBLID.' AND UID='.UZID.' AND ChannelID='.$chnl;

    // Read setz from the *custom* level
    $result = qry($sql);
    while ($line = mysqli_fetch_assoc($result)) {
        $lsetz_custom[$line['SettingName']] = $line['SettingValue'];
    }


    // If we have the same setz defined on the *global* level and with the same value, there is no need to save that
    // to *custom* level
    // Also, if we don't have the same setz on the global level, but the value is zero, no need to save it because
    // zero is default if not otherwise specified on the global level
    foreach ($lsetz_new as $k => $v) {
        if ((isset($lsetz_global[$k]) && $lsetz_global[$k]==$v) || (!isset($lsetz_global[$k]) && $v==0)) {
            unset($lsetz_new[$k]);
        }
    }


    // Save remaining setz to custom setz table
    if (isset($lsetz_new)) {

        foreach ($lsetz_new as $k => $v) {

            if (isset($lsetz_custom[$k])) { // setz already exists at the *custom* level

                if ($lsetz_custom[$k]==$v) { // IGNORE setz which are already saved to *custom* level with same value

                    //if (!$lsetz_new[$k]) unset($lsetz_new[$k]);

                    // FIX: Just skip. Do not *remove* it because the DELETE loop below would remove the saved setz too.

                    continue;

                } else { // Otherwise UPDATE it

                    qry('UPDATE settingz_lst SET SettingValue=\''.$v.'\' '.
                        'WHERE UID='.UZID.' AND TableID='.TBLID.' AND ChannelID='.$chnl.' AND SettingName=\''.$k.'\'');
                }

            } else { // setz doesnot exist at the *custom* level, so INSERT it

                if (!$v && @!$lsetz_global[$k]) continue;
                // No need to insert zero/false/null values (provided that global is also zero/false/null)

                qry('INSERT INTO settingz_lst (TableID, UID, ChannelID, SettingName, SettingValue)'.
                    ' VALUES ('.TBLID.', '.UZID.', '.$chnl.', \''.$k.'\', \''.$v.'\')', LOGSKIP);
            }
        }


        // DELETE setz from the *custom* level, but which are not defined in NEW setz
        if (isset($lsetz_custom)) {

            foreach ($lsetz_custom as $k => $v) {

                if (!isset($lsetz_new[$k])) {
                    qry('DELETE FROM settingz_lst WHERE '.
                        'TableID='.TBLID.' AND UID='.UZID.' AND ChannelID='.$chnl.' AND SettingName=\''.$k.'\'');
                }
            }
        }
    }
}










/**
 * Prints buttons, both simple and clustered.
 *
 * @param array $btnz Simple buttons
 *  'typ' - Type. NEW button must be of type 'new'. Other than that, it doesn't matter.
 *  'sub' - Corresponds to TYP constant. If it is equal to TYP than turns on ACTIVE state of the button.
 *  'pms' - Permissions. If false, turns on DISABLED state of the button.
 *  'hrf' - Href
 *  'cpt' - Caption
 *
 * @param array $clusterz Clustered buttons
 *  'arr' - Cluster categories array, from db or txt.
 *  'cln' - Column which will be filtered in WHERE sql (i.e. which corresponds to *cluster category*)
 *  'sel' - Selected Cluster ID (in cluster categories array). Will turn on ACTIVE state of the button.
 *  'pms' - Permissions. If false, turns on DISABLED state of the button.
 *
 * @return void
 */
function list_buttons_html(&$btnz, &$clusterz=null) {


	if (isset($btnz) && is_array($btnz)) {
		
        $h_group = [];
        $h_sole = [];

		foreach($btnz['sub'] as $k => $v) {

            // default values for settings which can be omitted: permissions
            if (!isset($btnz['pms'][$k])) $btnz['pms'][$k] = 1;
            if (!isset($btnz['typ'][$k])) $btnz['typ'][$k] = 'grp';

            $cpt = $btnz['cpt'][$k];

            $is_actv = (defined('TYP') && $v==TYP) ? true : false;

            $is_disabled = ($btnz['pms'][$k]) ? false : true;

            if ($btnz['typ'][$k]=='grp') { // type GRP is grouped

                $h_group[] = '<a type="button" class="btn btn-default text-uppercase'.
                    (($is_actv) ? ' active' : '').
                    (($is_disabled) ? ' disabled' : '').
                    '" href="'.$btnz['hrf'][$k].'">'.$cpt.'</a>'.PHP_EOL;

            } elseif ($btnz['typ'][$k]=='new') { // type NEW

                $h_sole[] = '<a type="button" class="btn btn-success btn-sm text-uppercase'.
                    (($is_disabled) ? ' disabled' : '').
                    '" href="'.$btnz['hrf'][$k].'">'.
                    '<span class="glyphicon glyphicon-plus-sign new"></span>'.$cpt.'</a>'.PHP_EOL;

            } elseif ($btnz['typ'][$k]=='sole') { // type SOLE

                $h_sole[] = '<a type="button" class="btn btn-primary btn-sm text-uppercase'.
                    (($is_disabled) ? ' disabled' : '').
                    '" href="'.$btnz['hrf'][$k].'">'.
                    $cpt.'</a>'.PHP_EOL;
            }
		}
	}


	if (isset($clusterz) && is_array($clusterz)) {
		
		foreach($clusterz as $cluster_index => $cluster) {
			
			$h_single = [];
			
			foreach($cluster['arr'] as $k => $v) {
				
				// default values for settings which can be omitted: permissions
				if (!isset($cluster['pms'][$k])) $cluster['pms'][$k] = 1;

                $cpt = txt_cutter($cluster['arr'][$k], 11, ['typ' => 'letter', 'trail' => '.']);

                $is_actv = ($k==$cluster['sel']) ? true : false;

                $is_disabled = ($cluster['pms'][$k]) ? false : true;


                // HREF construct

                $href ='?typ=item'.((IFRM) ? '&ifrm=1' : '');

                // We have to iterate clusters again in order to construct href
                foreach ($clusterz as $tmp_cluster_k => $tmp_cluster_v) {

                    if (CLUSTER_ALLOW_ZERO) {
                        $n = ($k==$cluster['sel']) ? 0 : $k;
                        $href .= '&cluster['.$tmp_cluster_k.']='.(($tmp_cluster_k==$cluster_index) ? $n : $tmp_cluster_v['sel']);
                    } else {
                        $href .= '&cluster['.$tmp_cluster_k.']='.(($tmp_cluster_k==$cluster_index) ? $k : $tmp_cluster_v['sel']);
                    }
                }


                $h_single[] = '<a type="button" class="btn btn-default text-uppercase'.
                    (($is_actv ) ? ' active' : '').
                    (($is_disabled) ? ' disabled' : '').
                    '" href="'.$href.'">'.$cpt.'</a>';
			}
			
			$h_clusterz[$cluster_index] = $h_single;
		}
		
	}


	// OUTPUT

    if (!IFRM) {

        if (!empty($h_group)) {
            echo '<div class="btn-group btn-group-sm">'.PHP_EOL.implode('', $h_group).'</div>'.PHP_EOL;
        }

        if (!empty($h_sole)) {
            echo '<div class="pull-right">'.PHP_EOL.implode('', $h_sole).'</div>'.PHP_EOL;
        }
    }


    if (isset($h_clusterz) && is_array($h_clusterz)) {

        foreach ($h_clusterz as $v) {
            echo '<div class="btn-group btn-group-sm">'.PHP_EOL.implode('', $v).'</div>'.PHP_EOL;
        }
    }

}




/**
 * Receives GET variables for DISPLAY
 *
 * @param array $dspl DISPLAY controls
 * @param array $lsetz List settings
 *  int     'chnl'   Selected channel
 *  array   'memory' Names of the controls which should be treated in list settings functions (other controls will be ignored).
 *                   Contains 'displ' and 'cndz' arrays. There mustnot be any values of same name within both arrays.
 *  array   'get'    Currently saved
 *  array   'put'    To be saved
 *
 * @return void
 */
function list_display_rcv(&$dspl, &$lsetz) {

    foreach ($dspl as $k => $v) {

        if (isset($_GET[$k])) {

            $dspl[$k]['val'] = intval(@$_GET[$k]);

        } else {  // If GET value is not set, we try db-saved values

            $dspl[$k]['val'] = (isset($lsetz['get'][$k])) ? $lsetz['get'][$k] : 0;
        }
    }

    foreach ($lsetz['memory']['displ'] as $v) { // prepare values to be saved

        $lsetz['put'][$v] = $dspl[$v]['val'];
    }
}



/**
 * Receives GET variables for CONDITIONS
 *
 * @param array $cndz CONDITION controls
 * @param array $lsetz List settings
 * @return void
 */
function list_conditions_rcv(&$cndz, &$lsetz) {


    foreach($cndz['typ'] as $k => $v) {

        $name = $cndz['nme'][$k];

        switch ($v) {

            case 'str-tme': // time textbox

                $t_arr	= explode('-', @$_GET[$name]);

                $t1	= (@$t_arr[0]) ? wash('ymd', $t_arr[0], 'Y-m-d H:i:s') : '';
                $t2	= (@$t_arr[1]) ? wash('ymd', $t_arr[1], 'Y-m-d H:i:s') : '';

                $cndz['val'][$k] = [];
                if ($t1) $cndz['val'][$k][] = $t1;
                if ($t2) $cndz['val'][$k][] = $t2;

                if (!implode('',$cndz['val'][$k])) {
                    $cndz['val'][$k] = ''; // null
                } else {
                    $dtr = 0; // TIME boxes override DATER
                }

                break;

            case 'str-dtr':

                $cndz['val'][$k] = '';

                if (isset($_GET[$name]) && !isset($dtr)) { // $dtr is set if TME value exists (TIME boxes override DATER)

                    $dtr = intval($_GET[$name]);

                    if ($dtr) {

                        $cndz['val'][$k][0] = wash('ymd', $dtr, 'Y-m-d H:i:s');
                        // We add it to $cndz['val'][$k][0], in order to imitate the control of *str-tme* type,
                        // i.e. the date range box. Thus we will be able to treat them in the same way when building WHERE sql.

                        define('DTR', $cndz['val'][$k][0]); // will be used for JS calendar
                    }
                }
                break;

            case 'str-ggl': // textbox needs string (default is '')

                $cndz['val'][$k] = (@$_GET[$name]) ? explode(' ',wash('ggl', @$_GET[$name])) : '';
                break;

            case 'str-abv': // Alphabet bar

                $cndz['val'][$k] = (!empty($_GET[$name])) ? wash('arr_assoc', $_GET[$name], $cndz['opt'][$k]) : '';
                break;


            default: // int (cbo) and bln (chk) need integer (default is 0)

                if (isset($_GET[$name])) {

                    $cndz['val'][$k] = intval(@$_GET[$name]);

                } else {  // If GET value is not set, we try db-saved values

                    $cndz['val'][$k] = (isset($lsetz['get'][$name]) && !isset($_GET['cndz_clear'])) ? $lsetz['get'][$name] : 0;
                }

                if (!empty($lsetz['memory']['cndz']) && in_array($name, $lsetz['memory']['cndz'])) { // prepare values to be saved

                    $lsetz['put'][$name] = $cndz['val'][$k];

                    if ($v=='bln') {
                        $cndz['mmry'][$k] = true;
                        // We use 'mmry' attribute to mark the specific checkbox is used for setz memorizing, which means
                        // we have to add shadow submitter for it and also add JS tunel in its *onChange* event.
                    }
                }

                break;
        }
    }
}




/**
 * Builds array with conditions for WHERE sql
 *
 * @param array $cndz CONDITION controls
 * @return array $query['where']
 */
function list_conditions_sql(&$cndz) {

    global $tx;

    $query['where'] = [];


    if (isset($cndz['spec']) && is_array($cndz['spec'])) {

        // We copy the index values from 'spec', so that we could overwrite the specific 'spec' if necessary.
        // For example, when 'spec' says to display only IsActive=1, and CHK_ARCHIVE CNDZ says to display only IsActive=0
        foreach($cndz['spec'] as $k => $v){
            $query['where'][$k] = $v;
        }
    }

    /* Loop CNDZ to build WHERE part of the SQL query */

    foreach($cndz['typ'] as $k => $v) {

        switch ($v) {

            case 'int-db':
            case 'int-txt':
            case 'int-swz':
            case 'int-usr':
            case 'int-id':

                if ($cndz['nme'][$k]=='FULFIL') {   // FULFIL (in film-item)

                    $cln2 = str_replace('=', '', strstr($cndz['cln'][$k], '='));
                    if ($cndz['val'][$k]==1) {
                        $query['where'][] = '('.$cndz['cln'][$k].' AND '.$cln2.')';
                    }
                    // FINITO  		(count=max AND max), if max is zero than it is never FINITO

                    if ($cndz['val'][$k]==2) {
                        $query['where'][] = '('.str_replace('=', '!=', $cndz['cln'][$k]).' OR !'.$cln2.')';
                    }
                    // NOT-FINITO 	(count!=max OR !max), if max is zero than it is NOT-FINITO

                } elseif ($cndz['nme'][$k]=='EPGCONN') {

                    if ($cndz['val'][$k]==1) {
                        $query['where'][] = $cndz['cln'][$k];
                    } elseif ($cndz['val'][$k]==2) {
                        $query['where'][] = '('.$cndz['cln'][$k].'=0 OR '.$cndz['cln'][$k].' IS NULL)';
                    }

                } else {

                    if ($cndz['val'][$k]) {
                        $query['where'][] = $cndz['cln'][$k].'='.$cndz['val'][$k];
                    }
                }

                break;

            case 'int-dur':

                if ($cndz['val'][$k]) {

                    $query['where'][] = list_cndz_durz('sql',
                        ['opt' => $cndz['opt'][$k], 'val' => $cndz['val'][$k], 'cln' => $cndz['cln'][$k]]);
                }

                break;

            case 'str-ggl':

                if ($cndz['val'][$k]) {

                    if ($cndz['opt'][$k]) { // Table: if it is set, that means it differs from the default TBL

                        foreach ($cndz['cln'][$k] as $zk => $zv) {

                            $cndz['cln'][$k][$zk] =
                                ((is_array($cndz['opt'][$k])) ? $cndz['opt'][$k][$zk] : $cndz['opt'][$k]).'.'.$zv;
                        }
                    }

                    if ($cndz['nme'][$k]=='GGL_REGEXP') {// GGL_REGEXP: no index, slow; for non-ascii characters: case-sensitive

                        $clnz = implode(', ', $cndz['cln'][$k]);

                        $sql = [];

                        foreach ($cndz['val'][$k] as $word) {

                            if (mb_strlen($word) > 2) {

                                $sql[] = "CONCAT_WS(' ', $clnz) REGEXP '$word'";
                                // Add *BINARY* after *REGEXP* if you want to make it case-sensitive
                            }
                        }

                        if ($sql) {
                            $query['where'][] = implode(' AND ', $sql);
                        } else {
                            omg_put('info', $tx['MSG']['ggl_2letter_words']);
                        }

                    } else { // GGL_FULLTEXT: indexes, fast; down side: whole words only

                        /*
                         * You can't define fulltext indexes (or any kind of index) across MULTIPLE tables in MySQL.
                         * Each index definition references exactly one table. All columns in a given fulltext index
                         * must be from the same table.
                         *
                         * The columns named as arguments to the MATCH() function must be part of a single fulltext index.
                         * You can't use a single call to MATCH() to search all columns that are part of all fulltext
                         * indexes in your database.
                         *
                         * Fulltext indexes only index columns defined with CHAR, VARCHAR, and TEXT datatypes.
                         *
                         * You can define a fulltext index in each table.
                         *
                         * Note: We could also ORDER results by search score, but in our case that seems unnecessary.
                         *
                         * (http://stackoverflow.com/questions/1241602/mysql-match-across-multiple-tables)
                         *
                         */

                        if (is_array($cndz['opt'][$k])) {

                            // Table(s) are set in *opt*..
                            // If it is null, that means we use default TBL.
                            // If it is var, that means it is not default table but it is the same for all columns.
                            // If it is array, then we are using *multiple* tables and therefore we have to use
                            // MATCH..AGAINST sintax for *each* table and stick it together with *OR*..

                            $sql = [];

                            foreach ($cndz['opt'][$k] as $zk => $zv) {

                                $sql[] = 'MATCH('.$cndz['cln'][$k][$zk].') '.
                                    'AGAINST(\''.implode(' ', $cndz['val'][$k]).'\')';
                            }

                            $query['where'][] = '('.implode(' OR ', $sql).')';

                        } else {

                            $query['where'][] = 'MATCH('.implode(', ', $cndz['cln'][$k]).') '.
                                'AGAINST(\''.implode(' ', $cndz['val'][$k]).'\')';
                        }
                    }
                }

                break;

            case 'str-tme':
            case 'str-dtr':

                if ($cndz['val'][$k]) {

                    $tmp = (count($cndz['val'][$k])>1) ? 1 : 0;
                    $query['where'][] = "(".LST_DATECLN." >= '".$cndz['val'][$k][0]."' AND ".
                        LST_DATECLN." <= DATE_ADD('".$cndz['val'][$k][$tmp]."', INTERVAL 1 DAY))";
                    // We add 1 DAY because default hms is 00:00:00, which would exclude the last day in the range.
                }

                break;

            case 'str-abv':

                if ($cndz['val'][$k]) {

                    $query['where'][] = 'LEFT('.$cndz['cln'][$k].', 1)=\''.$cndz['val'][$k].'\'';
                }

                break;

            case 'bln':

                switch ($cndz['nme'][$k]) {

                    case 'CHK_TODAY':
                        // CHK_TODAY is currently not used anywhere, but it is tested and ready for use.
                        // Also, don't forget to memorize it: $lsetz['memory']['cndz'][] = 'CHK_TODAY';

                        if ($cndz['val'][$k]) {

                            $today = wash('ymd', date('Ymd'), 'Y-m-d H:i:s');

                            $query['where'][] = "(".LST_DATECLN." >= '".$today."' AND ".
                                LST_DATECLN." <= DATE_ADD('".$today."', INTERVAL 1 DAY))";
                            // We add 1 DAY because default hms is 00:00:00, which would exclude the last day in the range.
                        }

                        break;

                    case 'CHK_TODAY_EPG':

                        if ($cndz['val'][$k]) {

                            $scnrz = list_epg_scnrz();

                            $query['where'][] = (empty($scnrz)) ? 'FALSE' : 'ScnrID IN ('.$scnrz.')';
                        }

                        break;

                    case 'CHK_ARCHIVE':

                        if ($cndz['val'][$k]) {
                            $query['where']['active'] = 'IsActive=0';
                        }
                        break;

                    case 'CHK_TASKS':

                        if ($cndz['val'][$k]) {
                            $query['where'][] = 'Phase=0';
                        }
                        break;

                    case 'CHK_CVR_UNPROOFED':

                        if ($cndz['val'][$k]) {
                            $query['where'][] = '(ProoferUID IS NULL OR ProoferUID=0)';
                        }
                        break;

                    case 'CHK_FLM_RECENT':

                        if ($cndz['val'][$k]) {
                            $query['where'][] = '(TermEPG >= DATE_SUB(\''.date('Y-m-d').' 00:00:00\', INTERVAL 10 DAY))';
                        }
                        break;

                    case 'CHK_EXPIRED':

                        $cln = TBL.'.'.$cndz['cln'][$k];

                        if ($cndz['val'][$k]) { // EXPIRED

                            $query['where'][] = '( ('.$cln.' IS NOT NULL AND '.$cln.' != \'0000-00-00\') '.
                                'AND ('.$cln.' < \''.date('Y-m-d').'\') )';

                        } else { // NOT EXPIRED

                            $query['where'][] = '( ('.$cln.' IS NULL OR '.$cln.' = \'0000-00-00\') '.
                                'OR ('.$cln.' >= \''.date('Y-m-d').'\') )';
                        }

                        break;
                }

                break;
        }
    }

    return $query['where'];
}




/**
 * Builds HTML for CONDITION controls
 *
 * @param array $cndz CONDITION controls
 * @return void
 */
function list_conditions_html(&$cndz) {

	foreach($cndz['typ'] as $k => $v) {

	    if (isset($cndz['act'][$k]) && $cndz['act'][$k]===false) {
	        continue;
        }

        $cpt = mb_strtoupper($cndz['cpt'][$k]);
		
		switch ($v) {
			
			case 'int-db':
				$h = arr2mnu(
                        rdr_cln($cndz['opt'][$k][0], $cndz['opt'][$k][1], @$cndz['opt'][$k][2]),
                        $cndz['val'][$k],
                        $cpt
                    );
				break;
				
			case 'int-txt':
				$h = arr2mnu(
                    $cndz['opt'][$k],
                    $cndz['val'][$k],
                    $cpt
                );
				break;
				
			case 'int-swz':
                $h = ctrl_prg($cndz['nme'][$k], $cndz['val'][$k], ['submit' => true]);
                break;
				
			case 'int-usr':

                if (SCTN=='dsk') {

                    if (setz_get('dsk_cndz_author')) { // stry1 && stry2

                        $tmp = cfg_local('arrz', 'lst_author_groups', 'stry1').','.
                            cfg_local('arrz', 'lst_author_groups', 'stry2');

                    } else {

                        $sts_k = 'stry'.rdr_cell('channels', 'TypeX', CHNL); // (stry1, stry2)
                        $tmp = cfg_local('arrz', 'lst_author_groups', $sts_k);
                    }

                } elseif (SCTN=='epg') {

                    $tmp = cfg_local('arrz', 'lst_author_groups', EPG_SCT); // (clp, mkt, prm, film)
                }

                $author_groups = explode(',', $tmp);
                $h = users2mnu($author_groups, $cndz['val'][$k], ['zero-txt' => $cpt]);

				break;

            case 'int-id':
                $h = '<input type="number" class="form-control" onkeypress="submit13(event);" '.
                    'name="'.$cndz['nme'][$k].'" id="'.$cndz['nme'][$k].'" placeholder="'.$cpt.'" value="">';
                break;

            case 'int-dur':
                $h = arr2mnu(list_cndz_durz('html', $cndz['opt'][$k]), $cndz['val'][$k], $cpt);
                break;

            case 'str-tme':
            case 'str-dtr':

                if ($cndz['val'][$k]) {

                    foreach($cndz['val'][$k] as $n => $m) {
                        $cndz['val'][$k][$n] = wash('ymd', $m, 'Y/m/d');
                    }

                    $value = implode(' - ', $cndz['val'][$k]);

                } else {

                    $value = '';
                }

                if ($v=='str-dtr') {
                    return;
                }

                $h = '<input type="text" class="form-control" onkeypress="submit13(event);" '.
                    'name="'.$cndz['nme'][$k].'" placeholder="'.$cpt.'" value="'.$value.'">';
				break;

            case 'str-ggl':

                $value = ($cndz['val'][$k]) ? implode(' ', $cndz['val'][$k]) : '';

                $h = '<div class="form-group has-feedback">
                    <div class="col-sm-12">
                      <input type="text" id="lst_search" class="form-control" maxlength="60" onkeypress="submit13(event);" '.
                    'name="'.$cndz['nme'][$k].'" placeholder="'.$cpt.'" value="'.$value.'">
                      <span class="glyphicon glyphicon-search form-control-feedback"></span>
                    </div>
                    </div>';

                break;

            case 'bln':
                $h = '<div class="checkbox chkpretty">
                    <label class="form-control">
                    <input type="checkbox" name="'.$cndz['nme'][$k].'" id="'.$cndz['nme'][$k].'" value="1" '.
                    'onChange="'.((@$cndz['mmry'][$k]) ? 'tunel(\''.$cndz['nme'][$k].'\', +this.checked)' : 'submit()').'" '.
                    (($cndz['val'][$k]) ? 'checked' : '').'>'.$cpt.'</label>
                    </div>';
                break;

            default:
				return;
		}

        if (in_array($v, ['int-db', 'int-txt', 'int-usr', 'int-dur'])) {     // cbo
            $h = '<select onChange="submit()" class="form-control" name="'.$cndz['nme'][$k].'">'.$h.'</select>';
        }

		echo '<td'.
            ((!empty($cndz['siz'][$k])) ? ' width="'.$cndz['siz'][$k].'%"' : ''). // We use *percentage* value
            (($cndz['val'][$k]) ? ' class="has-success"' : '').'>'.$h.'</td>';
	}
}








/**
 * String URL attributes for pagination links
 *
 * @return array URL
 */
function list_conditions_url() {

    global $cndz;

    $url = [];

    foreach($cndz['typ'] as $k => $v) {

        $attr_value = $cndz['val'][$k];

        if (is_array($attr_value)) {

            foreach($attr_value as $n => $m) {
                $attr_value[$n] = urlencode($m);
            }

            $attr_value = implode('+', $attr_value);

        } else {

            $attr_value = urlencode($attr_value);
        }

        $url[] = $cndz['nme'][$k].'='.$attr_value;
    }

    return $url;
}





/**
 * Builds HTML for FILTER REPORT, (which is based on CONDITION controls)
 *
 * @param array $cndz CONDITION controls
 * @return void
 */
function list_filter_html(&$cndz) {

	global $tx;


	$html_cndz = [];
	
	foreach($cndz['typ'] as $k => $v) {
	
		$h = '';
		
		if ($cndz['val'][$k]) {
		
			switch ($v) {
	
				case 'int-db':
				case 'int-swz':
                    $h = rdr_cell($cndz['opt'][$k][0], $cndz['opt'][$k][1], $cndz['val'][$k]);
                break;
	
				case 'int-txt':
					$h = $cndz['opt'][$k][$cndz['val'][$k]]; break;

                case 'int-usr':
                    $h = uid2name($cndz['val'][$k]);
                    break;

                case 'int-dur':
                    $h = list_cndz_durz('msg', ['opt' => $cndz['opt'][$k], 'val' => $cndz['val'][$k]]);
                    break;

                case 'str-tme':
                case 'str-dtr':
                    $h = implode(' - ',$cndz['val'][$k]);
					break;

                case 'str-ggl':
                    $h = implode(' ',$cndz['val'][$k]);
                    break;

                case 'bln':
                    $h = $cndz['cpt'][$k];
                    break;

                case 'str-abv':
                    $h = $cndz['val'][$k];
                    break;
			}
		}

		if ($h) {
            $html_cndz[] = '<b>'.$h.'</b>'.(($v!='bln') ? ' ('.$cndz['cpt'][$k].')' : '');
        }
	}

	if ($html_cndz) {
		
		echo
        '<div class="alert alert-info" role="alert">'.
            $tx['LST']['filter'].': '.implode('; ', $html_cndz).
            '<a class="alert-link pull-right" href="?'.list_home_link('cndz_clear').'">'.
                '<span class="glyphicon glyphicon-remove"></span></a>'.
        '</div>';
	}
	
}







/**
 * Prints HTML for RESULT REPORT
 *
 * @param int $start LIMIT-START from the sql
 * @param int $rpp Results-per-pages, i.e. LIMIT-LENGTH from the sql
 * @return void
 */
function list_result_cnt($start, $rpp) {

    global $tx;


    echo '<span class="results">'.$tx['LST']['results'].':&nbsp;<strong>';

    if (RESULT_CNT) {
        echo ($start+1).'-';
    }

    echo (($start+$rpp) < RESULT_CNT) ? ($start+$rpp) : RESULT_CNT;

    echo '</strong>'.' '.$tx['LST']['of_total'].' <strong>'.RESULT_CNT.'</strong> (<strong>'.
        substr((getmicrotime() - SCRIPT_START), 0, 5).'</strong> '.$tx['LST']['sec'].')';

    echo '</span>';
}






/**
 * Prints HTML for PAGES - pagination button group
 *
 * @param int $start LIMIT-START from the sql
 * @param int $rpp Results-per-pages, i.e. LIMIT-LENGTH from the sql
 * @return void
 */
function list_pgz_html($start, $rpp) { 

	if (!RESULT_CNT) return;


	$page_top = ceil(RESULT_CNT/$rpp)-1;
	$page_cur = ceil($start/$rpp);
	$page_left = $page_cur - 10;
	$page_right = $page_cur + 9;
	
	if ($page_left<0) {
        $page_left=0;
    }
	if ($page_right>$page_top) {
        $page_right=$page_top;
    }


    $home_href = '&'.list_home_link();

    echo '<nav><ul class="pagination">';

    if ($page_cur==0) {
        echo '<li class="disabled"><span>&laquo;</span></li>';
    } else {
        echo '<li><a href="?pgr='.(($page_cur-1)*$rpp).$home_href.'">&laquo;</a></li>';
    }

    for ($i=$page_left; $i<=$page_right; $i++) {

        echo '<li'.(($page_cur==$i) ? ' class="active"' : '').'>'.
            '<a href="?pgr='.($i*$rpp).$home_href.'">'.($i+1).'</a></li>';
	}

    if ($page_cur==$page_top) {
        echo '<li class="disabled"><span>&raquo;</span></li>';
    } else {
        echo '<li><a href="?pgr='.(($page_cur+1)*$rpp).$home_href.'">&raquo;</a></li>';
    }

    echo '</ul></nav>';
}







/**
 * Constructs *attributes* part of the home link.
 *
 * Used for CLEAR ALL FILTERS button and for buttons in PAGES - pagination button group.
 *
 * @param string $typ Type: pages, cndz_clear
 * @return string $href
 */
function list_home_link($typ='pages') {

    global $clusterz;

    $href = [];


    if ($typ=='cndz_clear') {

        $href[] = 'cndz_clear=1';

    } elseif ($typ=='pages') {

        $href = array_merge($href, list_conditions_url());
    }



    if (IFRM) {
        $href[] = 'ifrm=1';
    }

    if (in_array(TBLID, [30,31,32,33,36,37,38])) { // spices (except clips) and films need *typ* attribute in links
        $href[] = 'typ='.TYP;
    }

    if (is_array($clusterz)) {
        foreach ($clusterz as $k => $v) {
            $href[] = 'cluster['.$k.']='.$v['sel'];
        }
    }

    $href = implode('&', $href);
    return $href;
}











/**
 * Builds HTML for LIST TABLE HEADER
 *
 * @param array $clnz COLUMNS
 * @param array $dspl DISPLAY controls
 * @return string $html_hdr HTML for table header
 */
function list_th_html(&$clnz, &$dspl) {

    $arr_hdr = [];

    foreach($clnz['act'] as $k => $v) {

        if (!$v) continue; // skip inactive columns

        $cpt = $clnz['cpt'][$k];

        $is_sort_cln = ($dspl['sort_cln']['val']==$k) ? true : false;

        if ($is_sort_cln) {

            $sortdir_sign = ($dspl['sort_typ']['val']) ? 'up' : 'down';
            $sortdir_sign = '<span class="glyphicon glyphicon-arrow-'.$sortdir_sign.'"></span>';
            $cpt .= $sortdir_sign;
        }

        // CPT will be link - if there is a caption to click on and there is a table column to sort by (and no-sort is OFF)
        if ($clnz['cpt'][$k] && $clnz['cln'][$k] && @!$clnz['no-sort'][$k]) {

            if ($is_sort_cln) {

                // change sort type (i.e. direction)
                $href = 'tunel(\'sort_typ\', \''.(($dspl['sort_typ']['val']) ? 0 : 1).'\');';

            } else {

                // change sort column
                $href = 'tunel(\'sort_cln\', \''.$k.'\');';
            }

            $cpt = '<a href="#" onclick="'.$href.'">'.$cpt.'</a>';
        }


        $helper = '';

        /* vbdo discontinued
        if (TBLID==41 && STRY_LST_SCNR && !$is_sort_cln) { // 41=stryz

            $k_scnr = array_search('scnr', $clnz['typ']);

            if ($k_scnr==$k) {

                $helper = help_output('button',
                    ['name' => 'scnr', 'title' => $tx['LBL']['note'], 'pull' => '', 'css' => '',
                        'content' => txarr('blocks', 'help_lst_stry'), 'output' => false]);
            }
        }*/


        $arr_hdr[] = '<th'.
            ((!empty($clnz['siz'][$k])) ? ' width="'.$clnz['siz'][$k].'"' : '').
            (($is_sort_cln) ? ' class="sortcln"' : '').'>'.
            $cpt.$helper.
            '</th>';
    }

    $html_hdr = '<thead><tr>'.implode(PHP_EOL, $arr_hdr).PHP_EOL.'</tr></thead>'.PHP_EOL;

    return $html_hdr;
}




/**
 * Handle data for DURZ cndz control (int-dur)
 *
 * @param string $typ Case type (html, sql, msg)
 * @param array $opt 'opt' data
 *
 * @return array|string $r DURZ data
 */
function list_cndz_durz($typ, $opt) {

    switch ($typ) {


        case 'html':

            $j = 1;
            $r[$j] = '< '.$opt[0];
            $j++;

            for ($i=$opt[0]; $i<$opt[1]; $i+=$opt[2]) {
                $r[$j] = $i.'-'.($i+$opt[2]-1);
                $j++;
            }

            $r[$j] = $opt[1].' >';

            break;


        case 'sql':

            if ($opt['val']==1) { // first

                $t[1] = $opt['opt'][0];

            } elseif ($opt['val']*$opt['opt'][2]==$opt['opt'][1]) { // last

                $t[0] = $opt['opt'][0] + (($opt['val']-2) * $opt['opt'][2]);

            } else {

                $t[0] = $opt['opt'][0] + (($opt['val']-2) * $opt['opt'][2]);
                $t[1] = $t[0] + ($opt['opt'][2]-1);
            }

            foreach ($t as $k => $v) {
                $t[$k] = ($opt['opt'][3]=='ss') ? secs2dur($v) : mins2dur($v);
            }

            if (!empty($t[0])) {
                $r[0] = $opt['cln'].'>=\''.$t[0].'\'';
            }

            if (!empty($t[1])) {
                $r[1] = $opt['cln'].'<\''.$t[1].'\'';
            }

            $r[] = $opt['cln'].' IS NOT NULL';
            $r[] = $opt['cln'].'<>\'00:00:00\'';

            $r = implode(' AND ', $r);

            break;


        case 'msg':

            if ($opt['val']==1) { // first

                $r = '< '.$opt['opt'][0];

            } elseif ($opt['val']*$opt['opt'][2]==$opt['opt'][1]) { // last

                $r = $opt['opt'][1].' >';

            } else {

                $r = $opt['opt'][0] + (($opt['val']-2) * $opt['opt'][2]);
                $r = $r.'-'.($r + ($opt['opt'][2]-1));
            }

            break;
    }

    return $r;
}




/**
 * Data for JS calendar bar
 *
 * Doesnot need any attributes, but needs several constants defined: LST_DATECLN, TBL, TBLID
 * @return array $calendar
 *  'date_lead' - Lead date
 *  'date_last' - Last date
 *  'date_sel'  - Selected date
 *  'date_submited' - Submited date
 */
function calendar_data() {

    $today = date('Ymd');

    if (defined('LST_DATECLN')) { // list

        $calendar['typ'] = '';

        $sql = 'SELECT min('.LST_DATECLN.'), max('.LST_DATECLN.') FROM '.TBL. // .' WHERE '.LST_DATECLN.">'2008'".
            ((TBLID==32) ? ' WHERE BlockType='.EPG_SCT_ID : ''); // 32=epg_blocks
        $line = qry_numer_row($sql);

    } else { // epg calendar (e.g. mktplan)

        $calendar['typ'] = 'submit_to_self';

        if (VIEW_TYP==8) { // MKT
            $line[0] = date('Ymd', strtotime(date('Y-m-d').' -1 year'));
            $line[1] = date('Ymd', strtotime(date('Y-m-d').' +3 month')); // Mkt can be PLANNED several months in advance
        } else {
            $line = qry_numer_row('SELECT min(DateAir), max(DateAir) FROM epgz WHERE ChannelID='.CHNL);
        }

    }

    $calendar['date_lead'] = wash('ymd', $line[0], 'Ymd');
    if (!$calendar['date_lead']) {
        $calendar['date_lead'] = $today;
    }

    $calendar['yr_minus_4'] = date('Y', strtotime('4 years ago')).'0101';
    if ($calendar['date_lead'] < $calendar['yr_minus_4']) {
        $calendar['date_lead'] = $calendar['yr_minus_4'];
    }

    $calendar['date_last'] = wash('ymd', $line[1], 'Ymd');
    if (!$calendar['date_last'] || $calendar['date_last'] < $today) {
        $calendar['date_last'] = $today;
    }

    if (defined('DTR')) {
        $calendar['date_sel'] = wash('ymd', DTR, 'Ymd');
        $calendar['date_submited'] = DTR;
    } else {
        $calendar['date_sel'] = $today;
        $calendar['date_submited'] = null;
    }

    return $calendar;
}





/**
 * Get list of SCNRz for specified EPG
 *
 * @param int $epgid EPG ID
 * @return null|string SCNRz list
 */
function list_epg_scnrz($epgid=null) {

    if (!$epgid) {
        $epgid = get_real_epgid(); // TODAY's epg
    }

    if ($epgid) {
        $scnrz = rdr_cln('epg_elements', 'NativeID', 'EpgID='.$epgid.' AND NativeType=1');
        if ($scnrz) {
            return implode(',', $scnrz);
        }
    }

    return null;
}



/**
 * Return term in hh:mm format if it is TODAY, otherwise return its date.
 */
function list_time($xtime) {

    $xtime = strtotime($xtime);
    $x = (date('Y-m-d')==date('Y-m-d',$xtime)) ? date('H:i',$xtime) : date('Y/m/d',$xtime);

    return $x;
}
