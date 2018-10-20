<?php

// hrm







/**
 * User account reader
 *
 * @param int $id User ID
 *
 * @return array $x
 */
function uzr_reader($id) {

    $tbl = 'hrm_users';

    if ($id) {
        $x = qry_assoc_row('SELECT ID, Name1st, Name2nd, ADuser, GroupID, IsActive, IsHidden FROM '.$tbl.
            ' WHERE ID='.$id);
    }

    $x['ID']  = intval(@$x['ID']);
    $x['TYP'] = 'uzr';
    $x['TBL'] = $tbl;
    $x['TBLID'] = tablez('id', $x['TBL']);

    if (!$x['ID']) {
        return $x;
    }

    $x['DATA'] = rdr_row('hrm_users_data', '*', $x['ID']);

    return $x;
}




/**
 * ORG-GROUP reader
 *
 * @param int $id Org-group ID
 *
 * @return array $x
 */
function org_reader($id) {

    $tbl = 'hrm_groups';

    if ($id) {
        $x = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$id);
    }

    $x['ID']  = intval(@$x['ID']);
    $x['TYP'] = 'org';
    $x['TBL'] = $tbl;
    $x['TBLID'] = tablez('id', $x['TBL']);

    if (!$x['ID']) {
        return $x;
    }

    return $x;
}







/**
 * Get org tree starting from the specified GroupID as the root
 *
 * @param int $gid_root Root GroupID
 * @return array $r Org tree
 */
function org_tree_get($gid_root=0) {

    $r = [];

    $a = rdr_cln('hrm_groups', 'Title', 'ParentID='.$gid_root, 'Queue');

    foreach ($a as $k => $v) {

        $r[$k]['Title'] = $v;

        $r[$k]['Subs'] = org_tree_get($k);

        if (!$r[$k]['Subs']) {
            unset($r[$k]['Subs']);
        }
    }

    return $r;
}





/**
 * Print org tree
 *
 * @param string $typ Type: (list, list_uzr, ctrl)
 * @param array $org Org tree
 * @param array $opt Options data
 * - depth (int) - Header depth
 * - ctrl (array) - (for CTRL type)
 *   - name (string) - Control name
 *   - disable_id - ID of the org-group you want to disable
 *   - select_id - ID of the org-group you want to select
 *
 * @return void
 */
function org_tree_output($typ, $org, $opt=null) {

    if (empty($opt['depth'])) {
        $opt['depth'] = 3;
    } elseif ($opt['depth']>6) {
        $opt['depth'] = 6; // H6 is the last
    }


    echo '<ul'.((empty($opt['recurs'])) ? ' id="org_'.$typ.'"' : '').'>';
    // id is used for css targeting, but we want to set it only on the *root* ul

    foreach ($org as $k => $v) {

        if ($typ!='ctrl') { // list, list_uzr

            $title = ($typ=='list') ? '<a href="org_details.php?id='.$k.'">'.$v['Title'].'</a>' : $v['Title'];

            echo '<li><h'.$opt['depth'].'>'.$title.'</h'.$opt['depth'].'>';

            if ($typ=='list_uzr') {
                uzr_list($k, ['typ' => 'uzr_tree']);
            }

        } else { // ctrl

            $dis = (@$opt['ctrl']['disable_id']==$k) ? ' disabled' : '';

            $sel = (@$opt['ctrl']['select_id']==$k) ? ' checked' : '';

            echo '<li><h'.$opt['depth'].' class="radio'.$sel.'">'.
                '<label><input type="radio" name="'.$opt['ctrl']['name'].'" value="'.$k.'"'.$dis.$sel.'>'.$v['Title'].
                '</label>'.
                '</h'.$opt['depth'].'>';
        }

        if (!empty($v['Subs'])) {

            $opt_recurs['recurs'] = true;
            $opt_recurs['depth'] = $opt['depth']+1;

            if ($typ=='ctrl') {
                $opt_recurs['ctrl'] = $opt['ctrl'];
            }

            org_tree_output($typ, $v['Subs'], $opt_recurs); // recursive call
        }

        echo '</li>';
    }

    echo '</ul>';
}







/**
 * Get branch-up for the specified GroupID
 *
 * @param int $gid Start GroupID
 * @param bool $include_starter Whether to include the start group
 *
 * @return array $r Branch-up
 */
function branch_up_get($gid, $include_starter=false) {

    $r = [];

    if (!$gid) {
        return $r;
    }

    if ($include_starter) {
        $r[$gid] = rdr_cell('hrm_groups', 'Title', $gid);
    }

    while ($gid = rdr_cell('hrm_groups', 'ParentID', 'ID='.$gid.' AND ParentID<>1')) {
        $r[$gid] = rdr_cell('hrm_groups', 'Title', $gid);
    }

    if ($r) {
        $r = array_reverse($r, true);
    }

    return $r;
}





/**
 * Print branch up/down for the selected org-group
 *
 * @param array $branch_up Branch-up
 * @param array $grp Selected org-group
 * @param string $typ Type: (up/down, up)
 *
 * @return void
 */
function branch_output($branch_up, $grp, $typ='up/down') {

    $depth = 0;
    $h = 3;

    // Branch UP
    foreach ($branch_up as $k => $v) {
        echo '<ul'.((!$depth) ? ' class="branch"' : '').'><li>'.
            '<h'.($depth+$h).'><a href="org_details.php?id='.$k.'">'.$v.'</a></h'.($depth+$h).'>';
        $depth++;
    }

    if ($typ=='up/down') {

        // Selected org-group
        echo '<ul><li><h'.($depth+$h).'>'.$grp['Title'].'</h'.($depth+$h).'>';
        $depth++;

        // Branch DOWN
        $branch_down = rdr_cln('hrm_groups', 'Title', 'ParentID='.$grp['ID'], 'Queue');
        if ($branch_down) {
            echo '<ul>';
            foreach ($branch_down as $k => $v) {
                echo '<li><h'.($depth+$h).'><a href="org_details.php?id='.$k.'">'.$v.'</a></h'.($depth+$h).'></li>';
            }
            echo '</ul>';
        }
    }

    for ($i=0; $i<$depth; $i++) {
        echo '</li></ul>';
    }
}










/**
 * Fetch all users for specified org-group
 *
 * @param int $id Group ID
 *
 * @return array $uzr_arr
 */
function uzr_array($id) {

    global $cfg;

    $sql = 'SELECT ID, CONCAT(Name1st,\'&nbsp;\',Name2nd) AS name_full FROM hrm_users '.
        'WHERE IsActive AND (IsHidden=0 OR IsHidden IS NULL) AND '.
        'GroupID='.$id.' '.
        'ORDER BY Name1st ASC';

    $uzr_arr = qry_numer_arr($sql);

    foreach ($uzr_arr as $k => $v) {
        $uzr_arr[$k] = ['name_full' => $v, 'Title' => rdr_cell('hrm_users_data', 'Title', $k)];
    }

    if (@$cfg['cyr2lat'] && in_array(LNG, [2,4])) {
        foreach ($uzr_arr as $k => $v) {
            $uzr_arr[$k] = [
                'name_full' => text_convert($v['name_full'], 'cyr', 'lat'),
                'Title' => text_convert($v['Title'], 'cyr', 'lat')
            ];
        }
    }

    return $uzr_arr;
}






/**
 * Print list-table of all users for specified org-group
 *
 * @param int $gid Group ID
 * @param array $opt Options data
 * - typ (string) - Type: (org_details, uzr_tree)
 * - chief_id (int) - Group chief ID
 *
 * @return void
 */
function uzr_list($gid, $opt) {

    $uzr_arr = uzr_array($gid);

    if (!$uzr_arr) {
        return null;
    }


    if (empty($opt['typ'])) {
        $opt['typ'] = 'org_details';
    }

    if (empty($opt['chief_id'])) {
        $opt['chief_id'] = rdr_cell('hrm_groups', 'ChiefID', $gid);
    }

    $chief_id = $opt['chief_id'];


    $css = ($opt['typ']=='uzr_tree') ? ' table-condensed table-nonfluid uzr_tree' : '';

    echo '<table class="table table-hover listingz'.$css.'" id="grp_workers">';

    if ($opt['chief_id']) {

        echo '<tr class="chief">'.
            '<td><a href="uzr_details.php?id='.$chief_id.'">'.$uzr_arr[$chief_id]['name_full'].'</a><td>'.
            '<td><span class="glyphicon glyphicon-star"></span>'.$uzr_arr[$chief_id]['Title'].'<td>'.
            '</tr>';

        unset($uzr_arr[$chief_id]);
    }

    // Whether to print shortcut buttons for setting group CHIEF next to each worker's name.
    $do_btn_chief = (!$chief_id && $opt['typ']=='org_details' && PMS) ? true : false;

    foreach ($uzr_arr as $k =>$v) {

        if ($do_btn_chief) {

            $href = $_SERVER["REQUEST_URI"].(strpos($_SERVER["REQUEST_URI"], '?') ? '&' : '?').'chief='.$k;
            $btn = '<a href="'.$href.'" class="pull-right opcty2"><span class="glyphicon glyphicon-star"></span></a>';

        } else {

            $btn = '';
        }

        echo '<tr>'.
            '<td><a href="uzr_details.php?id='.$k.'">'.$v['name_full'].'</a>'.$btn.'<td>'.
            '<td>'.$v['Title'].'<td>'.
            '</tr>';
    }
    echo '</table>';
}










/**
 * Create unique username for the specified firstname-lastname pair
 *
 * @param string $name1 Firstname
 * @param string $name2 Secondname
 *
 * @return string $name_ad Unique username
 */
function create_username($name1, $name2) {

    $name_ad = $name1.'.'.$name2;

    // Conversion

    if (LNG==1) { // yu-cyr

        $name_ad = text_convert($name_ad, 'cyr', 'lateng'); // convert yu-cyr letters to eng

    } elseif (LNG==2) { // yu-lat

        $name_ad = text_convert($name_ad, 'lat', 'lateng'); // convert yu-lat letters to eng
    }

    $name_ad = strtolower($name_ad);
    $name_ad = str_replace(' ', '-', $name_ad); // convert spaces to hyphens
    $name_ad = preg_replace('/\-{2,}/', '-', $name_ad); // convert multiple hyphens to single hyphen
    // e.g. "Petrovic - Markovic" would first go to "petrovic---markovic" and then to "petrovic-markovic" which is ok


    // Unique

    $sufix = 2;
    $name_start = $name_ad;
    while ($user_exists = rdr_id('hrm_users', 'ADuser=\''.$name_ad.'\'')) {
        $name_ad = $name_start.$sufix;
        $sufix++;
    }


    return $name_ad;
}





/**
 * User account login/logout history
 *
 * @param int $uid User ID
 * @param int $limit Limit
 * @return void
 */
function uzr_usage($uid, $limit) {

    $sql = 'SELECT ActionID, Time FROM log_in_out WHERE UserID='.$uid.' ORDER BY ID DESC LIMIT '.$limit;

    $r = qry_assoc_arr($sql);

    echo '<div class="col-lg-6 col-md-7 col-sm-9 col-xs-12">';

    foreach ($r as $v) {

        echo '<div'.(($v['ActionID']==1) ? '' : ' class="pull-right"').'>'.
            '<span class="glyphicon glyphicon-log-'.(($v['ActionID']==1) ? 'in' : 'out').'"></span>'.$v['Time'].'</div>';
    }

    echo '</div>';
}
