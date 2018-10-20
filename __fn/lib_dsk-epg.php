<?php

// epg & dsk common functions






/**
 * Adding new story via shortcut from epg: add the fragment to scnr
 *
 * @param int $nat_typ Native type
 * @param int $nat_id Native ID
 * @param int $scnid SCNID
 *
 * @return void
 */
function epg_conn_receiver($nat_typ, $nat_id, $scnid) {

    $scnid = intval($scnid);
    if (!$scnid) {
        return;
    }

    $tbl = 'epg_scnr_fragments';

    $mdf['ScnrID'] = $scnid;
    $mdf['NativeID'] = $nat_id;
    $mdf['NativeType'] = $nat_typ;
    $mdf['IsActive'] = 1;


    // It can happen if user clicks twice on the approval button (it can happen e.g. if the server is slowed down)
    $frag_exists = rdr_id($tbl,
        'NativeType='.$mdf['NativeType'].' AND NativeID='.$mdf['NativeID'].' AND ScnrID='.$mdf['ScnrID']);

    if ($frag_exists) {

        log2file('srpriz', ['type' => 'frag_stry_exists', 'scnrid' => $mdf['ScnrID'], 'stryid' => $mdf['NativeID']]);

    } else {

        if (isset($_GET['qu'])) {

            $mdf['Queue'] = intval($_GET['qu']);

            qry('UPDATE '.$tbl.' SET Queue=Queue+1 WHERE Queue>='.$mdf['Queue'].' AND ScnrID='.$mdf['ScnrID']);

        } else {

            $mdf['Queue'] = scnr_get_qu($mdf['ScnrID']);
        }

        receiver_ins($tbl, $mdf);
    }
}





/**
 * String phase sign for story.
 *
 * Used in story lists, and also in epg.
 *
 * @param array $opt Options data
 *  - phase (int) - If you don't need neither phzclick nor phzuptd, then set only this attribute
 *  - story_id (int) - Used both for phzclick and phzuptd
 *  - lift (bool) - Used for phzclick - Whether to wrap phase sign in link for phase increment (depends on pms)
 *
 * @return string $sgn Phase sign html
 */
function phase_sign($opt) {

    global $tx;

    $arr_nwzphzs['txt']	= txarr('arrays', 'dsk_nwz_phases');
    $arr_nwzphzs['clr'] = cfg_global('arrz','dsk_phase_clr');

    $phase = intval($opt['phase']);

    if ($phase) {

        $clr = $arr_nwzphzs['clr'][$phase];
        $ttl = $arr_nwzphzs['txt'][$phase];

    } else {

        $clr = 'f00';
        $ttl = $tx['LBL']['task'];
    }

    // Attributes used both for phzclick and phzuptd.
    $phz_update = (!empty($opt['story_id'])) ?
        ' data-id="'.$opt['story_id'].'" id="phz'.$opt['story_id'].'" data-phz="'.$phase.'"' : '';

    $sgn = '<span class="glyphicon glyphicon-stop phz hidden-print" '.
        'style="color:#'.$clr.'" title="'.$ttl.'"'.$phz_update.'></span>';
    // NOTE: If/When you change this html, then perhaps you should also update js:phzuptd_dot()

    // phzclick control - js:phzclick()
    if (!empty($opt['lift'])) {
        $sgn = '<a class="phz lift" href="#" oncontextmenu="return false" onclick="return false" '.
            'data-id="'.$opt['story_id'].'">'.$sgn.'</a>';
    }

    return $sgn;
}




/**
 * Check whether FLW for the specified item exists.
 *
 * @param int $item_id Item ID
 * @param int $item_typ Item type
 * @param int $uid User ID
 *
 * @return int $flw_id FLW ID, if it exists.
 */
function flw_checker($item_id, $item_typ, $uid=UZID) {

    $flw_id = qry_numer_var('SELECT ID FROM stry_followz WHERE ItemID='.$item_id.' AND ItemType='.$item_typ.' AND UID='.$uid);

    return $flw_id;
}



/**
 * Write FLW to db.
 *
 * @param int $item_id Item ID
 * @param int $item_typ Item type
 * @param int $mark_typ Mark type [dsk_flw_types]
 * @param int $uid User ID
 *
 * @return void
 */
function flw_put($item_id, $item_typ, $mark_typ, $uid=UZID) {

    switch ($mark_typ) {

        case 2:
            if (!setz_get('flw_task', $uid)) return; // task setz
            break;

        case 3:
            if (!setz_get('flw_author', $uid)) return; // author setz
            break;
    }


    if ($mark_typ!=1) { // if automatic flw put, i.e. not user-clicked (via ajax)

        $flw_id = flw_checker($item_id, $item_typ, $uid);

        if ($flw_id) {

            // Mark types 2 or 3 will erase existing FLW, others (1 or 4) will back off
            if ($mark_typ==2 || $mark_typ==3) {
                qry('DELETE FROM stry_followz WHERE ID='.$flw_id);
            } else {
                return;
            }
        }
    }


    $sql = 'INSERT INTO stry_followz (ItemID, ItemType, UID, MarkTerm, MarkType) '.
        'VALUES ('.$item_id.', '.$item_typ.', '.$uid.', \''.TIMENOW.'\', '.$mark_typ.')';
    qry($sql, LOGSKIP);
}






/* discontinued as unnecessary
 *
 * Write FLW for each story in the specified scnr.
 *
 * @param int $prog_id ScnrID
 * @param int $mark_typ Mark type [dsk_flw_types]
 * @param int $uid User ID
 *
 * @return void
function flw_prog_circular($prog_id, $mark_typ, $uid) {

    $result = qry('SELECT NativeID, NativeType FROM epg_scnr_fragments WHERE ScnrID='.$prog_id.' AND NativeType=2');
    while ($line = mysqli_fetch_assoc($result)) {

        flw_put($line['NativeID'], $line['NativeType'], $mark_typ, $uid);
    }
}*/








/**
 * List of speakerX which are used in specified scnr.
 *
 * @param int $scnrid SCNRID
 *
 * @return array $spkrz
 */
function speakerx_list($scnrid) {

    $spkrz = [];

    $stories = rdr_cln('epg_scnr_fragments', 'NativeID', 'NativeType=2 AND ScnrID='.$scnrid); // Get stories

    foreach ($stories as $story_id) {

        $atoms = rdr_cln('stry_atoms', 'ID', 'TypeX=1 AND StoryID='.$story_id); // For each story get atoms (only reading type)

        foreach ($atoms as $atom_id) { // For each atom get speakers

            $spkr = rdr_cell('stry_atoms_speaker', 'SpeakerX', $atom_id);

            if ($spkr && !in_array($spkr, $spkrz)) {
                $spkrz[] = $spkr;
            }
        }
    }

    return $spkrz;
}





/**
 * Get speakers array for specified scnr.
 *
 * @param int $scnrid SCNRID
 *
 * @return array $speakerz
 */
function speakerz($scnrid) {

    global $tx, $cfg;


    // Get list of used speakers. We need it so we could later skip displaying boxes for those which are not used.
    $spkrz = speakerx_list($scnrid);


    // set-CRW speakers

    $speakerz = [];

    $i = 1;
    $crw_speakerz = crw_reader($scnrid, 1, 2, 'opt-data');

    foreach ($crw_speakerz as $v) {

        $speakerz[$i]['uid'] = $v['CrewUID'];
        $speakerz[$i]['name'] = uid2name($v['CrewUID']);
        $speakerz[$i]['name_short'] = uid2name($v['CrewUID'], ['n2_typ'=>'none']);

        $speakerz[$i]['crw_id'] = $v['ID'];
        $speakerz[$i]['rs'] = $v['OptData'];

        if ($v['CrewUID']==UZID && $i!=1) {
            $color_uno = $i;
        }

        $i++;
    }


    // unset-CRW speakers

    $txt = mb_strtoupper($tx['LBL']['speaker']);

    for ($i=1; $i<=$cfg['speakerz_cnt_max']; $i++) {

        if (!isset($speakerz[$i]) && in_array($i, $spkrz)) { // Skip those which are not used
            $speakerz[$i]['name'] = $txt;
        }
    }


    // Colors

    $tmp = cfg_global('arrz','dsk_reader_clr');
    $clrz = setz_get('reader_color');

    for ($i=1; $i<=count($tmp); $i++) {
        $arr_clrz[$i] = $tmp[$clrz[$i-1]];
    }


    // Set color and numero attributes

    foreach ($speakerz as $k => $v) {

        $speakerz[$k]['color'] = $arr_clrz[$k];
        $speakerz[$k]['i'] = $k;
    }


    // Current user always gets the FIRST color

    if (isset($color_uno)) {

        $speakerz[$color_uno]['color'] = $arr_clrz[1];
        $speakerz[1]['color'] = $arr_clrz[$color_uno];
    }


    return $speakerz;
}




/**
 * Strings box for speakers and editors fullname
 *
 * @param array $arr Person array
 * @param string $typ Type:
 * - speaker_rs - with readspeed popover (used in epg studio)
 * - speaker_x - with speakerx popover (used in epg studio)
 * - speaker (used in stry details)
 * - editor (used in epg studio)
 * @param array $opt Options data
 * - scnrid (int) - SCNRID (only for *speaker_rs* type)
 * - sel_x (int) - Selected SpeakerX key (only for *speaker_x* type)
 * - atomid (int) - Atom ID (only for *speaker_x* type)
 *
 * @return string
 */
function speaker_box($arr, $typ, $opt=null) {

    global $tx;


    $html = [];

    switch ($typ) {


        case 'speaker_rs':

            foreach ($arr as $v) {

                $html_rs = [];

                if (!empty($v['uid'])) {    // If a user is selected as speaker

                    $pms = pms('epg', 'mdf_speaker_rs', ['UID' => $v['uid']]);

                    $result = qry('SELECT ROUND(Velocity/100, 1) AS Velocity, ID, Name, IsDefault FROM stry_readspeed '.
                        'WHERE UID='.$v['uid'].' ORDER BY ID DESC');

                    while ($line = mysqli_fetch_assoc($result)) {

                        if (!$v['rs']) { // If specific rs for the speaker is not selected then use the default rs for the speaker

                            $rs_sel = ($line['IsDefault']) ? true : false;

                        } else {    // Specific rs for the speaker is selected

                            $rs_sel = ($v['rs']==$line['ID']) ? true : false;
                        }

                        if (!$rs_sel && $pms) {
                            $href = ' href=\'epg.php?typ=scnr&rs='.((!$line['IsDefault']) ? $line['ID'] : 0).
                                '&crw='.$v['crw_id'].'&scnrid='.$opt['scnrid'].'&speakerx='.$v['i'].'\'';
                            $css = '';
                        } else {
                            $href = '';
                            $css = ' class=\'disabled\'';
                        }

                        $html_rs[] =
                            '<span class=\'rs_line'.(($rs_sel) ? ' sel' : '').'\'>'.
                                $line['Velocity'].'<a'.$href.$css.'>'.$line['Name'].'</a>'
                                .(($line['IsDefault']) ? '<span class=\'glyphicon glyphicon-thumbs-up\'></span>' : '').
                            '</span>';
                    }
                }

                if (!empty($html_rs)) {

                    $html_rs = implode('<br>', $html_rs);

                } else { // If no user is selected as speaker, or user is selected but he doesn't have any rs defined, we use cfg default

                    $html_rs =
                        '<span class=\'rs_line sel\'>'.
                            get_readspeed('cfg', 'char_per_s').'<a class=\'disabled\'>'.$tx['LBL']['default'].'</a>'.
                        '</span>';
                }

                $html[] =
                    '<a data-toggle="popover" class="namebox speaker" href="#" onclick="return false;" '.
                      'title="'.$tx['LBL']['read_speed'].'" data-content="'.$html_rs.'">'.
                        '<span class="numero">'.$v['i'].'</span>'.
                        '<span class="speaker spkr'.$v['i'].'">'.$v['name'].'</span>'.
                    '</a>';
            }

            break;


        case 'speaker_x':

            $pms = PMS_MDF_STUDIO_SPEAKER;

            $html_spk = [];

            foreach ($arr as $k => $v) {

                $js = ' onclick=\'studio_spkr_ajax_front(this, '.$v['i'].', '.$opt['atomid'].'); '.
                    'return false;\'';

                $spk_sel = ($k==$opt['sel_x']) ? true : false;

                $css = 'spk_line';
                if ($spk_sel || !$pms) $css .= ' disabled';
                if ($spk_sel) $css .= ' sel';

                $html_spk[] = '<a href=\'#\' id=\'spk_url'.$v['i'].'\''.$js.' class=\''.$css.'\'>'.
                    $v['i'].' - '.$v['name'].'</a>';
            }

            $html_spk = implode('<br>', $html_spk);

            $html[] =
                '<a data-toggle="popover" class="namebox wired speaker" href="#" onclick="return false;" '.
                'id="spk_atom'.$opt['atomid'].'"title="'.$tx['LBL']['speaker'].'" data-content="'.$html_spk.'" '.
                'data-pms="'.intval($pms).'">'.  // through *data-pms* attribute we pass pms to JS
                '<span class="numero">'.$arr[$opt['sel_x']]['i'].'</span>'.
                '<span class="speaker">'.$arr[$opt['sel_x']]['name'].'</span>'.
                '</a>';

            break;


        case 'speaker':

            $html[] = '<div class="namebox wired story speaker"><span class="numero">'.$arr[0].'</span>'.
                '<span class="speaker">'.mb_strtoupper($tx['LBL']['speaker']).'</span></div>';

            break;


        case 'editor':

            foreach ($arr as $v) {
                $html[] = '<span class="namebox editor">'.
                    '<span class="numero"><span class="glyphicon glyphicon-star"></span></span>'.
                    '<span class="speaker">'.uid2name($v).'</span>'.
                    '</span>';
            }

            break;
    }

    $html = implode('', $html);

    return $html;
}





/**
 * Get ReadSpeed
 *
 * @param string $typ Type: (cfg, user, rs, stry)
 * @param string $rtyp Return type: (raw, char_per_s, t_per_char)
 * @param array $opt Options
 * - uid - for *user* type
 * - rs_id - for *rs* type
 * - stry_id - for *stry* type
 * - speaker_x - for *stry* type
 *
 * @return string $rs ReadSpeed in characters per second (float value)
 */
function get_readspeed($typ, $rtyp='raw', $opt=null) {

    global $cfg;


    switch ($typ) {

        case 'cfg':     // Get DEFAULT rs from CFG.

            $chnl_typ = rdr_cell('channels', 'TypeX', CHNL);
            $rs = $cfg['read_speed_'.$chnl_typ];
            break;

        case 'user':    // Get DEFAULT rs for the specified USER ID

            $rs = qry_numer_var('SELECT Velocity FROM stry_readspeed WHERE IsDefault=1 AND UID='.$opt['uid']);

            if (!$rs) {
                $rs = get_readspeed('cfg');
            }

            break;

        case 'rs':      // Get rs for the specified RS ID

            $rs = qry_numer_var('SELECT Velocity FROM stry_readspeed WHERE ID='.$opt['rs_id']);
            break;

        case 'stry':    // Get rs for the specified STORY ID

            $speaker = crw_spkr_reader($opt['stry_id'], $opt['speaker_x']);

            if (!empty($speaker['rs'])) { // Specific rs for the speaker is selected

                $rs = get_readspeed('rs', 'raw', ['rs_id' => $speaker['rs']]);

            } else { // If specific rs for the speaker is not selected then use default rs for the speaker

                if (!empty($speaker['uid'])) {

                    $rs = get_readspeed('user', 'raw', ['uid' => $speaker['uid']]);

                } else {

                    $rs = get_readspeed('cfg');
                }
            }

            break;
    }


    if ($rtyp=='raw') {
        return $rs;
    }

    $rs = $rs / 100;

    if ($rtyp=='t_per_char') {
        $rs = 1 / $rs;
    }

    return $rs;
}






/**
 * Estimate how much time will it take to read specified text
 *
 * NOTE: This function must be identical in PHP (atom_txtdur) and JS (js_atom_txtdur)
 *
 * @param string $txt Text
 * @param int $speed Speed
 *
 * @return string $dur Duration in hms format
 */
function atom_txtdur($txt, $speed) {

    //$speed = get_readspeed('stry', 't_per_char', ['stry_id' => $stry_id, 'speaker_x' => $speaker_x]);

    if (!$txt) {
        return '00:00:00';
    }

    $txtlen = atom_txtlen($txt);

    $s = round($txtlen * $speed);

    $dur = secs2dur($s);

    return $dur;
}




/**
 * Calculate atom text length
 *
 * NOTE: This function must be identical in PHP (atom_txtlen) and JS (js_atom_txtlen)
 *
 * @param string $txt Text
 * @return int $txtlen Length in characters
 */
function atom_txtlen($txt) {

    $txt = trim($txt, '\n\r\t ');

    $txt = str_replace('\r\n', ' ', $txt);

    $txt = htmlspecialchars_decode($txt, ENT_QUOTES);

    $txtlen = mb_strlen($txt);

    $cnt_digits = preg_match_all("/[0-9]/", $txt);

    if ($cnt_digits) {
        $txtlen += $cnt_digits * 5; // Add 5 characters for each digit
    }

    return $txtlen;
}








/**
 * Get last phrase from specified text
 *
 * @param int $w Width limit (in characters)
 * @param string $txt Atom text
 * @return string $r
 */
function atom_last_phrase($w, $txt) {

    if (!$txt) {
        return null;
    }

    $txt = html_entity_decode(rtrim($txt), ENT_QUOTES); // Must decode, otherwise LEN calculations would be faulty

    $len = mb_strlen($txt);

    if ($len<=$w) { // If text is already within width limit
        return ' '.$txt;
    }


    /* Try NEWLINE */

    $nl = mb_strrpos($txt, "\n");

    if ($nl) {

        $rpos = $len - $nl;

        if ($rpos>$w+10) { // Accept only if newline is within width limit plus 10 characters
            unset($rpos);
        }
    }


    if (!isset($rpos)) {
        $rpos = $len - mb_strrpos($txt, ' ', -$w);
    }


    $r = '..'.ltrim(mb_substr($txt, -$rpos));

    return $r;
}







/**
 * Story ATOMS reader: fetch all atoms in specified story
 *
 * @param int $id Story ID
 * @return array $x	Array of atom objects (data arrays)
 */
function atomz_reader($id) {

    $result = qry('SELECT ID FROM stry_atoms WHERE StoryID='.$id.' ORDER BY Queue');

    while (list($id) = mysqli_fetch_row($result)) {

        $arr[$id] = atom_reader($id);
    }

    return @$arr;
}





/**
 * Story ATOM reader
 *
 * @param int $id Atom ID
 * @return array $x	Atom data array
 */
function atom_reader($id) {

    $tbl = 'stry_atoms';

    $x = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$id);


    $x['ID']  = intval(@$x['ID']) ;
    $x['TBL'] = $tbl;
    $x['EPG_SCT_ID'] = 20;


    if (!$x['ID']) { // set default values and return

        $x['DurationTXT'] = t2boxz('', 'time');

        return $x;
    }


    $x['DurationTXT'] = t2boxz(@$x['Duration'], 'time');

    $x['Texter'] = rdr_cell('stry_atoms_text', 'Texter', $x['ID']);

    $x['SpeakerX'] = rdr_cell('stry_atoms_speaker', 'SpeakerX', $x['ID']);


    /* ATOMBACK (discontinued)
    if (in_array($x['TypeX'], [1,3])) {

        $x['DSC'] = rdr_row('stry_atoms_dsc', '*', 'ID='.$x['ID']);

        if (!$x['DSC']) { // set default values
            $x['DSC'] = ['ID'=>0, 'IsActive'=>0, 'Dsc'=>''];
            // We add ID key to mark whether this data exists at all, so that we could later decide between INSERT and UPDATE
        }
    }
    */

    if ($x['TypeX']==2)	{
        $x['MOS'] = mos_reader($x['ID'], $x['EPG_SCT_ID']);
    }

    if ($x['TypeX']==1)	{
        $x['CRW'] = crw_reader($x['ID'], $x['EPG_SCT_ID']);
    }

    return $x;
}









/**
 * Copy specified story
 *
 * @param int $src_id Original (source) story ID
 * @param int $scnrid Target ScnrID
 *
 * @return int Copy (destination) story ID
 */
function stry_copy($src_id, $scnrid=0) {

    $tbl[1] = 'stryz';
    $tbl[2] = 'stry_atoms_text';
    $tbl[3] = 'stry_atoms_speaker';
    $tbl[4] = 'stry_copies';
    $tbl[5] = 'stry_atoms';


    $dst = rdr_row($tbl[1], 'Caption, DurForc, DurEmit, UID', $src_id);

    if (!$dst) {
        return 0;
    }


    $crw = crw_reader($src_id, 2);

    if (!$crw) {

        $crw = [0 => ['CrewType' => 5, 'CrewUID' => $dst['UID']]];

    } else {

        foreach ($crw as $v) {
            $tmp[$v['CrewType']][] = $v['CrewUID'];
        }

        if (!isset($tmp[5])) {
            $crw[] = ['CrewType' => 5, 'CrewUID' => $dst['UID']];
        }
    }


    $dst['UID'] = UZID;
    $dst['TermAdd'] = TIMENOW;
    $dst['ChannelID'] = chnlid_from_scnrid($scnrid);
    $dst['Phase'] = 1;
    $dst['Caption'] .= ' +';
    $dst['ScnrID'] = $scnrid;
    $dst['ProgID'] = scnr_get_progid($scnrid);

    $dst['ID'] = receiver_ins($tbl[1], $dst);

    receiver_ins($tbl[4], ['OriginalID'=>$src_id, 'CopyID'=>$dst['ID']],
        ['tbl_name' => $tbl[1], 'x_id' => $src_id, 'act_id' => 6]);

    flw_put($dst['ID'], 2, 3);

    // cvrz on story level (2 is for type: fragment, i.e. story)
    cvr_copy($src_id, $dst['ID'], 2);

    crw_receiver($dst['ID'], 2, null, LOGSKIP, $crw);


    $atomz = atomz_reader($src_id);

    if ($atomz) {

        foreach ($atomz as $v) {

            // atom

            $filter = array_flip(['Queue', 'TypeX', 'Duration']);
            $atom = array_intersect_key($v, $filter);
            $atom['StoryID'] = $dst['ID'];

            if ($v['TypeX']==1) {
                $speed = get_readspeed('stry', 't_per_char', ['stry_id' => $atom['StoryID'], 'speaker_x' => $v['SpeakerX']]);
                $atom['Duration'] = atom_txtdur($v['Texter'], $speed);
            }

            $dst_atom_id = receiver_ins($tbl[5], $atom);

            // texter
            receiver_ins($tbl[2], ['ID' => $dst_atom_id, 'Texter' => $v['Texter']], LOGSKIP);

            // speaker
            if ($v['TypeX']==1) {
                receiver_ins($tbl[3], ['ID' => $dst_atom_id, 'SpeakerX' => $v['SpeakerX']], LOGSKIP);
            }

            /* ATOMBACK (discontinued)
            // dsc
            if (in_array($v['TypeX'], [1,3]) && $v['DSC']['IsActive']) {
                receiver_ins('stry_atoms_dsc', array_merge($v['DSC'], ['ID' => $dst_atom_id]));
            }
            */

            // mos
            if ($v['TypeX']==2 && $v['MOS']) {
                $log = ['tbl_name' => $tbl[5], 'x_id' => $dst_atom_id];
                $filter = array_flip(['IsReady', 'Duration', 'TCin', 'TCout', 'Label', 'Path']);
                $v['MOS'] = array_intersect_key($v['MOS'], $filter);
                mos_receiver($dst_atom_id, 20, null, $log, $v['MOS']);
            }

            // cvrz on atom level (3 is for type: atom)
            cvr_copy($v['ID'], $dst_atom_id, 3);
        }
    }

    return $dst['ID'];
}




/**
 * Print story copies tree for specified story.
 *
 * First it finds arhi-original, i.e. top parent story, then prints its the entire tree.
 *
 * @param int $stry_id Story ID
 * @return void
 */
function stry_copies($stry_id) {

    // Find arhi-original

    $arh = $stry_id;

    while ($n = rdr_cell('stry_copies', 'OriginalID', 'CopyID='.$arh)) {
        $arh = $n;
    }

    echo '<ul>';
    echo '<li>'.stry_copies_cpt($arh, $stry_id).'</li>';

    stry_copies_tree($arh, $stry_id);

    echo '</ul>';
}


/**
 * Print story copies tree for specified story (only the children branch).
 *
 * Helper function for stry_copies().
 *
 * @param int $root_id Root story ID
 * @param int $sel_id Selected story ID
 * @return void
 */
function stry_copies_tree($root_id, $sel_id) {

    $children = rdr_cln('stry_copies', 'CopyID', 'OriginalID='.$root_id);

    if (count($children)) {

        echo '<li><ul>';

        foreach ($children as $v) {

            echo '<li>';

            echo stry_copies_cpt($v, $sel_id);

            stry_copies_tree($v, $sel_id);

            echo '</li>';
        }

        echo '</ul></li>';
    }
}


/**
 * Print caption for story copies.
 *
 * Helper function for stry_copies functions
 *
 * @param int $stry_id Story ID
 * @param int $sel_id Selected story ID
 * @return string $cpt Caption
 */

function stry_copies_cpt($stry_id, $sel_id) {

    $stry = rdr_row('stryz', 'Caption, ScnrID, IsDeleted', $stry_id);

    $cpt = $stry['Caption'];

    if ($stry_id!=$sel_id) {
        $cpt = '<a href="stry_details.php?id='.$stry_id.'"'.
            (($stry['IsDeleted']) ? ' style="text-decoration: line-through;"' : '').
            '>'.$cpt.'</a>';
    } else {
        $cpt = '<b>'.$cpt.'</b>';
    }


    if ($stry['ScnrID']) {

        $element_id = scnr_id_to_elmid($stry['ScnrID']);

        $cpt .= ' <i>('.scnr_cpt_get($element_id).')</i>';
    }


    return $cpt;
}







/**
 * Get text for the specified story type
 *
 * @param string $formula Story type formula (from CFG-ARR: dsk_story_type_formula)
 * @return string $r Text for the specified story type
 */
function stry_type_txt($formula) {

    $arr_atom_typz = txarr('arrays', 'atom_jargons.'.ATOM_JARGON);

    $atomz = explode('-', trim($formula));

    $r = [];

    foreach ($atomz as $v) {

        $r[] = $arr_atom_typz[$v];

        /* ATOMBACK (discontinued)
        $cover = (bool)(strstr($v, '+')) ? 1 : 0;

        $v = trim($v, '+'); // purge '+' sign

        $r[] = $arr_atom_typz[$v].(($cover) ? ' ('.$tx[SCTN]['LBL']['bgcover'].')' : '');
        */
    }

    return implode('/', $r);
}



