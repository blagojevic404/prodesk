<?php




/**
 * Delete an item from db, and optionally delete/update dependant items from other tables
 *
 * @param string $typ Type
 * @param int $id ID of the item to be deleted
 * @param array $opt Options array
 * - skip_redirect (bool) - Skip redirecting
 *
 * @return void
 */
function deleter($typ, $id, $opt=null) {

    global $cfg;

    $pms = false;         // On the top of each TYPE case there should be pms check with redirect attribute set to *true*.
    $redirect_url = '';   // On the bottom of each TYPE case *redirect url* should be set.
    $redirect_act = 'delete'; // Optionally, you can set $redirect_act to 'restore' or 'purge' to display different omg message.

    $already_deleted = false; // Two people could delete same item almost simultaneously and thus produce error. Prevent that.

    $tblz_delete = [];
    $tblz_update = [];


    switch ($typ) {



        /* HRM */


        case 'org':

            $redirect_url = 'org.php';

            $x = org_reader($id);

            if (empty($x['ID'])) {
                $already_deleted = true;
                break;
            }

            $pms = pms('hrm/org', 'del', $x, true);

            $tblz_delete[] = ['tbl' => $x['TBL'], 'where' => 'ID='.$id, 'log' => ['x_id' => $id]];

            break;




        /* DSK */


        case 'prgm':

            $redirect_url = 'list_prgm.php';

            $x = prgm_reader($id);

            if (empty($x['ID'])) {
                $already_deleted = true;
                break;
            }

            $pms = pms('dsk/prgm', 'del', $x, true);

            $tblz_delete[] = ['tbl' => $x['TBL'], 'where' => 'ID='.$id, 'log' => ['x_id' => $id]];

            break;


        case 'tmz':

            $redirect_url = 'tmz.php';

            $x = team_reader($id);

            if (empty($x['ID'])) {
                $already_deleted = true;
                break;
            }

            $pms = pms('dsk/tmz', 'del', $x, true);

            $tblz_delete[] = ['tbl' => $x['TBL'], 'where' => 'ID='.$id, 'log' => ['x_id' => $id]];

            break;


        case 'stry':

            $x = stry_reader($id, 'pms');

            $redirect_act = $opt['act'];

            $redirect_url = ($opt['act']=='purge') ? 'list_trash.php' : 'list_'.$x['TYP'].'.php';

            if (empty($x['ID'])) {
                $already_deleted = true;
                break;
            }

            if ($opt['act']=='delete') { // action: delete (move to trash and remove from scnrz)

                $pms = pms('dsk/stry', 'del', $x, true);

                if ($x['IsDeleted']) {  // Error-catcher: this should never happen
                    $pms = false;
                    break;
                }


                $log = ['x_id' => $id, 'act_id' => 4];

                // Switch ON the IsDeleted column
                $tblz_update[] = ['tbl' => $x['TBL'], 'cln' => 'IsDeleted=1', 'where' => 'ID='.$id, 'log' => $log];

                $tblz_update[] = ['tbl' => $x['TBL'], 'cln' => 'ScnrID=NULL', 'where' => 'ID='.$id, 'log' => $log];


                // Insert into recycle table
                $sql = 'INSERT INTO stry_trash (ItemID, ItemType, DelUID, DelTerm, ChannelID) '.
                    'VALUES ('.$id.', '.$x['EPG_SCT_ID'].', '.UZID.', \''.TIMENOW.'\', '.$x['ChannelID'].')';
                qry($sql, LOGSKIP);


                // Get IDs of SCNRs which contain this story
                $scnrz_arr = stry_usage(['ID' => $id, 'EPG_SCT_ID' => $x['EPG_SCT_ID']], 'IDs');


                // Delete this story from SCNRs
                qry('DELETE FROM epg_scnr_fragments WHERE NativeType='.$x['EPG_SCT_ID'].' AND NativeID='.$id);


                // Update EMIT for each SCNR
                if ($scnrz_arr) {
                    foreach ($scnrz_arr as $v) {
                        sch_termemit($v, 'scnr');
                    }
                }


            } elseif ($opt['act']=='purge') { // action: purge


                $pms = pms('dsk/stry', 'purge_restore', $x, true);

                $tblz_delete = stry_deleter($id);


            } else { // action: restore


                $pms = pms('dsk/stry', 'purge_restore', $x, true);


                // Switch OFF the IsDeleted column
                $log = ['x_id' => $id, 'act_id' => 5];
                $tblz_update[] = ['tbl' => $x['TBL'], 'cln' => 'IsDeleted=NULL', 'where' => 'ID='.$id, 'log' => $log];


                // Delete from recycle table
                $tblz_delete[] = [
                    'tbl' => 'stry_trash',
                    'where' => 'ItemID='.$id.' AND ItemType='.$x['EPG_SCT_ID'],
                    'log' => ['x_id' => $id]
                ];
            }

            break;


        case 'cvr':

            $x = cover_reader($id);

            if (empty($x['ID'])) {
                $already_deleted = true;
            }

            if (!$already_deleted) {

                $pms = pms('dsk/cvr', 'del', $x, true);

                $tblz_delete[] = ['tbl' => $x['TBL'], 'where' => 'ID='.$id, 'log' => ['x_id' => $id]];
            }

            // CVR deleter can be accessed from several pages, and user should be returned to the calling page after submit.

            $rfrscr = cvr_referer('arr');

            if (!$rfrscr) { // referer script is cvr_details

                $redirect_url = 'list_cvr.php';

            } else {

                if ($rfrscr['rfrscr']=='stry_details') {

                    $redirect_url = '/desk/stry_details.php?id='.$rfrscr['rfrid'];

                } elseif ($rfrscr['rfrscr']=='epg') {

                    $redirect_url = '/epg/epg.php?view=2&typ='.$rfrscr['rfrtyp'].'&id='.$rfrscr['rfrid'];
                }
            }

            break;






        /* EPG */


        case 'mkt_plan': // EPG: mktplan

            if (!empty($_GET['mktid'])) { // we got here from spice item page

                $redirect_url = 'spice_details.php?sct=mkt&typ=item&id='.$_GET['mktid'];

            } else { // we got here from epg list

                $redirect_url = '/epg/epg.php?typ=epg&view=8';

                if ($_GET['epgid']) {
                    $redirect_url .= '&id='.$_GET['epgid'];
                } else {
                    $redirect_url .= '&dtr_date='.$_GET['dtr_date'].'&dtr_chnl='.$_GET['dtr_chnl'];
                }

                $redirect_url .= '#tr'.$_GET['plancode'];
            }

            $item = rdr_row('epg_market_plan', '*', $id); // We will need it later for mktuptd()

            if (empty($item['ID'])) {
                $already_deleted = true;
                break;
            }

            $pms = pms('epg/mkt', 'mdf', ['EPG_SCT' => 'mkt', 'TYP' => 'item'], true);

            $tblz_delete[] = ['tbl' => 'epg_market_plan', 'where' => 'ID='.$id, 'log' => ['x_id' => $id]];

            if ($cfg[SCTN]['mktplan_use_notes']) {
                $mktplan_note_typ = 3; // We can use MKT NativeID as it is not otherwise used, i.e. MKT doesn't use notes.
                note_deleter($id, $mktplan_note_typ);
            }

            // Mkt block uptodate function - mktuptd() has to be called *after* the DELETE action itself,
            // therefore we have to put it below, at the bottom of this function

            break;


        case 'epg_mkt': // EPG: marketing
        case 'epg_prm': // EPG: promo
        case 'epg_clp': // EPG: clips

            $x = spice_reader($id, $opt['ztyp'], $opt['zsct']);

            $redirect_url = 'list_'.$opt['zsct'].'.php?typ='.$x['TYP'];

            if (empty($x['ID'])) {
                $already_deleted = true;
                break;
            }

            $pms = pms('epg/'.$opt['zsct'], 'del', $x, true);

            $tblz_delete[] = ['tbl' => $x['TBL'], 'where' => 'ID='.$id, 'log' => ['x_id' => $id]];

            if ($x['TYP']=='block') {

                $tblz_delete[] = ['tbl' => 'epg_cn_blocks', 'where' => 'BlockID='.$x['ID']];

                mos_deleter($x['ID'], $x['EPG_SCT_ID']);
                crw_deleter($x['ID'], $x['EPG_SCT_ID']);
            }

            break;


        case 'epg_film':

            $redirect_url = 'list_film.php?typ='.$opt['ztyp'].'&cluster[1]='.$opt['filmtyp'].'&cluster[2]='.$opt['filmsct'];

            $x = film_reader($id, $opt['ztyp']);

            if (empty($x['ID'])) {
                $already_deleted = true;
                break;
            }

            $pms = pms('epg/film', 'del', $x, true);

            $tblz_delete[] = ['tbl'=>$x['TBL'], 'where' => 'ID='.$id, 'log' => ['x_id' => $id]];

            if ($x['TYP']=='item') {

                $tblz_delete[] = ['tbl' => 'film_description',    'where' => 'ID='.$x['ID']];
                $tblz_delete[] = ['tbl' => 'film_cn_contracts',   'where' => 'FilmID='.$x['ID']];
                $tblz_delete[] = ['tbl' => 'film_cn_genre',       'where' => 'FilmID='.$x['ID']];
                $tblz_delete[] = ['tbl' => 'film_cn_channel',     'where' => 'FilmID='.$x['ID']];
                $tblz_delete[] = ['tbl' => 'film_episodes',       'where' => 'ParentID='.$x['ID']];

                note_deleter($x['ID'], $x['EPG_SCT_ID']);
            }

            break;

    }


    if (!$already_deleted) {

        if (!$pms) {
            redirector('access'); // Insufficient permissions
        }

        foreach ($tblz_delete as $v) {
            qry('DELETE FROM '.$v['tbl'].' WHERE '.$v['where'], @$v['log']);
        }

        foreach ($tblz_update as $v) {
            qry('UPDATE '.$v['tbl'].' SET '.$v['cln'].' WHERE '.$v['where'], @$v['log']);
        }

        // This has to be done *after* the DELETE action itself, therefore we have to put it here
        if ($typ=='mkt_plan') {
            mktuptd($item, null);
        }
    }


    if (empty($opt['skip_redirect'])) {
        redirector($redirect_act, $redirect_url);
    }
}




/**
 * Story deleter
 *
 * @param int $id ID of the story to be deleted
 * @param bool $do_delete Whether to delete the items instead of returning them to the caller (the deleter() function)
 *
 * @return array $tblz_delete Items to be deleted (for deleter() function)
 */
function stry_deleter($id, $do_delete=false) {

    global $cfg;

    $stry['ID'] = $id;
    $stry['EPG_SCT_ID'] = 2;
    $atom['EPG_SCT_ID'] = 20;

    $tbl[1] = 'stryz';
    $tbl[2] = 'stry_trash';
    $tbl[3] = 'stry_followz';
    $tbl[4] = 'epg_coverz';
    $tbl[5] = 'stry_atoms';
    $tbl[6] = 'stry_atoms_text';


    // Delete the story
    $tblz_delete[] = ['tbl' => $tbl[1], 'where' => 'ID='.$stry['ID'], 'log' => ['x_id' => $stry['ID']]];

    // Delete from recycle table
    $tblz_delete[] = [
        'tbl' => $tbl[2],
        'where' => 'ItemID='.$stry['ID'].' AND ItemType='.$stry['EPG_SCT_ID'],
        'log' => ['x_id' => $stry['ID']]
    ];

    // Delete from followz table
    $tblz_delete[] = ['tbl' => $tbl[3], 'where' => 'ItemID='.$stry['ID'].' AND ItemType='.$stry['EPG_SCT_ID']];

    if ($cfg['dsk']['dsk_use_notes']) {
        note_deleter($stry['ID'], $stry['EPG_SCT_ID']);
    }

    crw_deleter($stry['ID'], $stry['EPG_SCT_ID']);

    // Delete COVERZ associated with story
    $tblz_delete[] = ['tbl' => $tbl[4], 'where' => 'OwnerType=2 AND OwnerID='.$stry['ID']];


    $atomz = qry_numer_arr('SELECT ID FROM '.$tbl[5].' WHERE StoryID='.$stry['ID']);

    if ($atomz) {

        foreach ($atomz as $v) {

            $tblz_delete[] = ['tbl' => $tbl[5], 'where' => 'ID='.$v];
            $tblz_delete[] = ['tbl' => $tbl[6], 'where' => 'ID='.$v];
            // $tblz_delete[] = ['tbl' => 'stry_atoms_dsc', 'where' => 'ID='.$v]; // ATOMBACK (discontinued)

            crw_deleter($v, $atom['EPG_SCT_ID']);

            if ($cfg['dsk']['use_mos_for_rec_atom']) {
                mos_deleter($v, $atom['EPG_SCT_ID']);
            }

            // Delete COVERZ associated with atom
            $tblz_delete[] = ['tbl' => $tbl[4], 'where' => 'OwnerType=3 AND OwnerID='.$v];

            // Delete TIPS associated with atom
            qry('DELETE FROM epg_tips WHERE SchType=3 AND SchLineID='.$v);
        }
    }


    if ($do_delete) {

        mysqli_query($GLOBALS['db'], 'START TRANSACTION');

        foreach ($tblz_delete as $v) {
            qry('DELETE FROM '.$v['tbl'].' WHERE '.$v['where']);
        }

        mysqli_query($GLOBALS['db'], 'COMMIT');
    }

    return $tblz_delete;
}

