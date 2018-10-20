<?php

// permissions







/**
 * Check permissions
 *
 * @param string $sct Section
 * @param string $act Action the user wants to perform
 * @param array $x Data array
 * @param bool $redirect Whether to redirect on failure or to return false. (Use *true* for MDF pages)
 *
 * @return bool|array $ok Whether permissions are positive or not. Except dsk/stry:phase returns an array with pms for each phase.
 */

function pms($sct, $act='', $x=null, $redirect=false) {

    $ok = false;
    // At the bottom we run pms_master() if *false*, but we don't if ZERO.
    // I.e. if you want to skip pms_master() function (which always gives pms to UZID_ALFA), set $ok to ZERO!


    switch ($sct) {


    /* ADMIN */

        case 'admin':

            switch ($act) {

                case 'lng': // LNG files update

                    // Should be allowed only on DEV side, because of FTP/GIT config..
                    $ok = (SERVER_TYPE=='dev' && UZID==UZID_ALFA) ? true : 0;

                    break;

                case 'maestro':

                    if (UZID==UZID_ALFA) { $ok = true; break; }

                    break;

                case 'spec':

                    $itech_spec = pms_roler('user', 'Itech_spec');

                    if ($itech_spec) { $ok = true; break; }

                    break;

                default:

                    $itech = ( pms_roler('descendant', 'Itech') || pms_roler('user', 'Itech_spec') );

                    if ($itech) { $ok = true; break; }

                    break;
            }

            break;


    /* HRM */

        case 'hrm/uzr':

            switch ($act) {

                case 'new':
                case 'mdf':
                    $kadrovik = pms_roler('group', 'Kadrovik');
                    if ($kadrovik) { $ok = true; break; }
                    break;

                case 'admin':
                case 'history':
                    $itech = pms_roler('descendant', 'Itech');
                    if ($itech) { $ok = true; break; }
                    if (!$ok && $act=='history' && UZID==$x['ID']) {
                        $ok = true; break;
                    }
                    break;
            }

            break;


        case 'hrm/org':

            $kadrovik = pms_roler('group', 'Kadrovik');
            if ($kadrovik) { $ok = true; break; }

            switch ($act) {

                case 'new':
                case 'mdf':
                    break;

                case 'del':
                    $not_empty = rdr_cell('hrm_users', 'ID',
                        'IsActive AND (IsHidden=0 OR IsHidden IS NULL) AND GroupID='.$x['ID']);
                    if ($not_empty) {
                        $ok = 0; break;
                    } else {
                        $not_childless = rdr_cell('hrm_groups', 'ID', 'ParentID='.$x['ID']);
                        if ($not_childless) {
                            $ok = 0; break;
                        }
                    }
                    break;
            }

            break;


    /* DSK */

        case 'dsk/prgm':

            $planer = ( pms_roler('group', 'Planer') || pms_roler('user', 'Planer') );

            switch ($act) {

                case 'new':
                    if ($planer) { $ok = true; break; }
                    break;

                case 'mdf':
                    if ($planer) { $ok = true; break; }
                    break;

                case 'mdf_crw':

                    // Chief of the associated org-group for the parent team
                    $uid_own = ($x['TEAM']['GroupID']) ? rdr_cell('hrm_groups', 'ChiefID', $x['TEAM']['GroupID']) : 0;
                    if (UZID==$uid_own) { $ok = true; break; }

                    // Main producer
                    $producer = pms_roler('user', 'Producer_main');
                    if ($producer) { $ok = true; break; }

                    break;

                case 'mdf_web':
                    $webmaster = pms_roler('user', 'Webmaster');
                    if ($webmaster) { $ok = true; break; }
                    break;

                case 'del':

                    if ($planer) { $ok = true; }

                    $not_empty = rdr_cell('epg_scnr', 'ID', 'ProgID='.$x['ID']);
                    if ($not_empty) { $ok = 0; break; }

                    break;
            }

            break;


        case 'dsk/tmz':

            $planer = ( pms_roler('group', 'Planer') || pms_roler('user', 'Planer') );
            if ($planer) { $ok = true; break; }

            switch ($act) {

                case 'new':
                    break;

                case 'mdf':
                    $uid_own = ($x['GroupID']) ? rdr_cell('hrm_groups', 'ChiefID', $x['GroupID']) : 0;
                    if (UZID==$uid_own) { $ok = true; break; } // Chief of the associated org-group
                    break;

                case 'del':
                    $not_empty = rdr_cell('prgm', 'ID', 'TeamID='.$x['ID']);
                    if ($not_empty) { $ok = 0; break; }
                    break;
            }

            break;


        case 'dsk/stry':

            if (in_array($act, ['mdf', 'phase', 'del'])) {

                $phz = $x['Phase'];

                $author = ($x['UID']==UZID);

                $proofer = pms_roler('group', 'Proofreader');

                if ($x['ScnrID']) {

                    $editor = pms_scnr($x['ScnrID'], 1);


                    // ALIEN TEAM fellows shoud have editor pms

                    if (!$editor) {

                        $prgm_id = rdr_cell('epg_scnr', 'ProgID', $x['ScnrID']);

                        if ($prgm_id) {

                            $prgm_team_id = rdr_cell('prgm', 'TeamID', $prgm_id);

                            $prgm_team_grp_id = rdr_cell('prgm_teams', 'GroupID', $prgm_team_id);

                            $author_grp_id = ($author) ? UZGRP : rdr_cell('hrm_users', 'GroupID', $x['UID']);

                            // Whether author is from alien team (doesnot belong to the same team to which prgm belongs)
                            if (in_array($prgm_team_grp_id, group_ancestors($author_grp_id))) {

                                $author_is_alien = false;

                            } else {

                                // We want to exclude users which don't belong to any team at all (e.g. ITC - external bureaus)

                                $prgm_team_chnl_id = rdr_cell('prgm_teams', 'ChannelID', $prgm_team_id);

                                if (rdr_cell('prgm_teams', 'ID',
                                    'ChannelID='.$prgm_team_chnl_id.' AND GroupID='.$author_grp_id)) {
                                    $author_is_alien = true;
                                } else {
                                    $author_is_alien = false;
                                }
                            }

                            if ($author_is_alien) {

                                if ($author) { // Give editor pms to the author himself

                                    $editor = true;

                                } else { // Check whether author and UZID are fellow team members

                                    $uzid_is_fellow = false;

                                     if ($author_grp_id==UZGRP) { // They are from the same group

                                         $uzid_is_fellow = true;

                                     } else { // Check whether UZID is in the author's ancestor team

                                         if (in_array(UZGRP, group_ancestors($author_grp_id))) {

                                             // Check whether UZGRP is any team at all
                                             $fellow_team_id = rdr_cell('prgm_teams', 'ID', 'GroupID='.UZGRP);

                                             if ($fellow_team_id) {

                                                 $uzid_is_fellow = true;
                                             }
                                         }
                                     }

                                     if ($uzid_is_fellow) {

                                         // We could complicate it some more by adding check whether editor is defined
                                         // at the fellow team level, and then if it IS - check whether UZID is the editor,
                                         // and if it IS NOT - only then give editor pms to all fellow team members.

                                         $editor = true;
                                     }
                                }
                            }
                        }
                    }

                } else { // Story is not connected into any SCNR, give loose pms

                    if ($author) {

                        $editor = true;

                    } else {

                        $editor = ($act=='mdf') ? true : false;
                    }
                }
            }


            // *phase* pms returns an array, that's why we have to put this here in front
            if (in_array($act, ['mdf', 'phase']) && $author && pms_team_loose($x['ScnrID'])) {
                $editor = true;
            }

            switch ($act) {


                case 'mdf':

                    switch ($phz) {

                        case 0:
                            if ($author || $editor) {
                                $ok = true;
                            } else {
                                $asigned_uid = crw_reader($x['ID'], 2, 5, 'normal_single');
                                if ($asigned_uid==UZID) { $ok = true; }
                            }
                            break;

                        case 1:
                            if ($author) { $ok = true; }
                            break;

                        case 2:
                            if ($editor) { $ok = true; }
                            break;

                        case 3:
                            if ($proofer) { $ok = true; }
                            break;

                        case 4:

                            if ($editor) { $ok = true; break; }

                            $speaker = (!empty($x['ScnrID']) && pms_scnr($x['ScnrID'], 2));
                            if ($speaker) { $ok = true; break; }

                            if ($author) {

                                if (!empty($x['ScnrID'])) { // For REC PROGS give author pms
                                    $prg_mattyp = rdr_cell('epg_scnr', 'MatType', $x['ScnrID']);
                                    if ($prg_mattyp==2) { $ok = true; break; }
                                }
                            }

                            break;
                    }

                    break;


                case 'phase':

                    $master = pms_roler('group', 'Realztor');

                    $admin = pms_master();

                    $speaker = (!empty($x['ScnrID']) && pms_scnr($x['ScnrID'], 2));

                    // pms for phases (who can set which phase)
                    // phases: 1 - author, 2 - editor, 3 - proofer, 4 - master

                    $pms[1] = ( $editor );

                    $pms[2] = ( $editor || ($phz==1 && $author) || ($phz==3 && $proofer) || ($phz==4 && $master) );

                    $pms[3] = ( $editor || ($phz==4 && $proofer) || ($phz==4 && $speaker) );
                    // Proofer wants to be able to return to previously corrected story

                    $pms[4] = ( $editor || ($phz==3 && $proofer));

                    foreach ($pms as $k => $v) {

                        if ($admin) {
                            $pms[$k] = true;
                        }

                        if ($phz==$k) {
                            $pms[$k] = false;   // For *current* phase pms is always false, because we are already there..
                        }
                    }

                    return $pms;


                case 'new':
                    $journo = pms_roler('descendant', 'Journo');
                    $promoter = pms_roler('group', 'Promoter'); // (TMP) Because they want to send some text to proofers..
                    if ($journo||$promoter) { $ok = true; break; }
                    break;

                case 'del':

                    if ($author) { // Only author can delete story

                        if ($editor) { // If author is also editor then he can do it in any phase
                            $ok = true; break;
                        } elseif (in_array($x['Phase'], [0,1])) { // Author which is not editor can only in phases 0 and 1
                            $ok = true; break;
                        }
                    }

                    // Editor can delete task (i.e. story in phase 0) which another editor wrote (i.e. though he is not author)
                    if ($editor && $x['Phase']==0) {
                        $ok = true; break;
                    }

                    $ok = 0;
                    break;

                case 'purge_restore':
                    $author = ($x['UID']==UZID);
                    if ($author) { $ok = true; break; }
                    break;

                case 'mdf_recs_dur':
                    $author = ($x['UID']==UZID);
                    if ($author) { $ok = true; break; }
                    break;

                case 'atom_isready':
                    $author = (rdr_cell('stryz', 'UID', $x['story_id']) == UZID);
                    if ($author) { $ok = true; break; }
                    break;
            }

            if (!$ok && $act=='mdf' && $author && pms_chnl_loose($x['ChannelID'])) {
                $ok = true; break;
            }

            break;


        case 'dsk/cvr':

            if (in_array($act, ['new', 'del', 'kargen'])) { // CARGEN by default has pms for these actions
                $cg = pms_roler('group', 'Egd');
                if ($cg) { $ok = true; break; }
            }

            if (in_array($act, ['new', 'mdf', 'del'])) {
                $producer = pms_roler('user', 'Producer_main'); // Producer main
                if ($producer) { $ok = true; break; }
            }

            switch ($act) {

                case 'proofer':
                    $proofer = pms_roler('group', 'Proofreader');
                    if ($proofer) { $ok = true; break; }
                    break;

                case 'new':

                    $journo = pms_roler('descendant', 'Journo');
                    $promoter = pms_roler('group', 'Promoter'); // Promoters write *inserters*
                    $realztor = pms_roler('group', 'Realztor');

                    if ($journo || $promoter || $realztor) { $ok = true; break; }

                    /*
                    // Owner TYPE: 1-scnr (prog/film), 2-fragment, 3-atom, 4-epg

                    if ($x['OwnerType']==4 || $x['OwnerType']==1) {

                        $journo = pms_roler('descendant', 'Journo');
                        if ($journo) { $ok = true; break; }

                        $promoter = pms_roler('group', 'Promoter'); // Promoters write *inserters*
                        if ($promoter) { $ok = true; break; }

                    } else { // 2-fragment (i.e. story), 3-atom

                        $ok = pms_stry_cvr($x);
                    }
                    */

                    break;


                case 'mdf':
                case 'del':

                    if ($x['IsReady']) {    // If IsReady is *true*, then ONLY CARGEN

                        $cg = pms_roler('group', 'Egd'); // We put it here by intent, so they have pms only when IsReady
                        if ($cg) { $ok = true; break; }

                        break;

                    } else { // NOT $x['IsReady']

                        $author = ($x['UID']==UZID);
                        if ($author) { $ok = true; break; }

                        if (@$x['TypeX']==9) { // Inserters, handled by promo department
                            $promoter = pms_roler('group', 'Promoter'); // Promoters write *inserters*
                            if ($promoter) { $ok = true; break; }
                        }

                        $realztor = pms_roler('group', 'Realztor');
                        if ($realztor) { $ok = true; break; }

                        if ($act=='mdf') { // MDF only
                            $proofer = pms_roler('group', 'Proofreader');
                            if ($proofer) { $ok = true; break; }
                        }

                        $journo = pms_roler('descendant', 'Journo');
                        if ($journo) { $ok = true; break; }

                        /*
                        if ($x['OwnerType']==4) { // 4-epg

                            // *author* pms is enough

                        } elseif ($x['OwnerType']==1) { // 1-scnr (i.e.prog/film)

                            $scnid = scnrid_prog($x['OwnerID']);

                            $ok = pms_scnr($scnid, 1);

                        } else { // 2-fragment (i.e. story), 3-atom

                            $ok = pms_stry_cvr($x);
                        }
                        */
                    }

                    break;
            }

            break;














    /* EPG */

        // epg/scn
        case 'epg':

            if (in_array($act,
                ['mdf_term', 'mdf_epg_plan', 'mdf_recs_dur', 'del_epg', 'tmpl_epg', 'mdf_web',
                    'mdf_epg_tips', 'mdf_scn_tips_note'])) {

                $planer = ( pms_roler('group', 'Planer') || pms_roler('user', 'Planer') );
            }

            if (in_array($act,
                ['mdf_term', 'mdf_recs_dur', 'mdf_epg_tips', 'mdf_web',
                'mdf_scn_tips_note', 'mdf_scn_tips_cam', 'mdf_scn_tips_vo'])) {

                $realztor = pms_roler('group', 'Realztor');
            }

            switch ($act) {


                // mdf_full for parent + mdf_line
                case 'mdf_scn_fragment':

                    $ok = pmsepg_full(1, $x['ScnrID']); // check full pms for parent element

                    if (!$ok && pmsepg_line($x['NativeType'])) { $ok = true; break; } // check line pms for fragment

                    break;


                // mdf_full + mdf_line
                case 'mdf_single':

                    if (TYP=='epg' && @$_GET['ref']!='scn') { // ref=scn when trying to open mdf-dsc from a scnr

                        $ok = pmsepg_full();

                    } else {

                        // This is the same as *mdf_scn_dsc*

                        $ok = pmsepg_full($x['NativeType'], @$x['NativeID'], @$x['ScnrID']);

                        if (!$ok && $x['NativeType']==1 && pms_prgm_editor($x['PRG']['ProgID'])) { $ok = true; break; }
                    }

                    if (!$ok && pmsepg_line($x['NativeType'])) { $ok = true; break; }

                    break;


                case 'mdf_full':

                    $ok = (TYP=='epg') ? pmsepg_full() : pmsepg_full($x['NativeType'], $x['NativeID']);
                    break;


                case 'mdf_term':
                    if ($realztor||$planer) { $ok = true; break; }
                    break;


                case 'mdf_epg_tips':
                    $promoter 	= pms_roler('group', 'Promoter');
                    if ($realztor||$planer||$promoter) { $ok = true; break; }
                    break;


                case 'mdf_epg_plan':
                    if ($planer) { $ok = true; break; }
                    break;


                case 'mdf_recs_dur':
                    if ($realztor) { $ok = true; break; }
                    if (TYP=='epg' && $planer) { $ok = true; break; }
                    break;


                case 'mdf_scn_tips_note':
                case 'mdf_scn_tips_vo':

                    if ($realztor) { $ok = true; break; }

                    if ($act=='mdf_scn_tips_note' && $planer) { $ok = true; break; }

                    if (isset($x['NativeID'])) { // Will be set unless coming from ajax (aj_editdivable)..

                        if (pms_scnr($x['NativeID'], 1)) { $ok = true; break; } // Editor

                    } else { // We had to leave pms-free ajax access, because it would be to complicated..

                        $ok = true; break;
                    }

                    break;

                case 'mdf_scn_tips_cam':
                    if ($realztor) { $ok = true; break; }
                    break;


                case 'del_epg':
                    $itech = pms_roler('descendant', 'Itech');
                    if ($itech) { $ok = true; break; }
                    if ($planer && $x['EPG']['DateAir']!=DATENOW) { $ok = true; break; }
                    break;


                case 'tmpl_epg':
                    if ($planer) { $ok = true; break; }
                    break;


                // Check whether user has pms for specific linetype (specified by $x['NativeType'])
                // (this is checked for each line in epg/scn, to determine whether to show MDF & DEL buttons)
                // (also, checked *once* to determine whether to show BCASTS controls)
                case 'mdf_line':

                    $ok = pmsepg_line($x['NativeType']);
                    break;


                // Modify SCN dsc (where we can set CRW, i.e. editor for the scn. That editor then gets mdf_full pms, etc..)
                case 'mdf_scn_dsc':

                    $ok = pmsepg_full($x['NativeType'], $x['NativeID']);

                    if (!$ok && $x['NativeType']==1 && pms_prgm_editor($x['PRG']['ProgID'])) { $ok = true; break; }

                    break;


                case 'tmpl_scnr':
                    if (pms_prgm_editor($x['PRG']['ProgID'])) { $ok = true; break; }
                    break;


                case 'mdf_speaker_rs': // EPG STUDIO
                    if (UZID==$x['UID']) { $ok = true; break; }
                    break;


                case 'mdf_speaker_x':      // EPG STUDIO
                case 'mdf_editdivable':    // EPG STUDIO

                    $ok = pms_scnr($x['NativeID'], 1) || pms_scnr($x['NativeID'], 2); // Editor or speaker

                    if (!$ok && $act=='mdf_editdivable') { // Proofer
                        $proofer = pms_roler('group', 'Proofreader');
                        if ($proofer) { $ok = true; break; }
                    }

                    break;


                case 'mdf_web':

                    if ($realztor||$planer) { $ok = true; break; }

                    $webmaster = pms_roler('user', 'Webmaster');
                    if ($webmaster) { $ok = true; break; }

                    break;
            }

            break;



        // spices mktplan
        case 'epg/mktplan':

            switch ($act) {

                case 'mdf':
                    $marketing 	= pms_roler('user', 'Marketing');
                    if ($marketing) { $ok = true; break; }
                    break;

                case 'sync':
                    $planer = pms_roler('user', 'Planer_mktsync');
                    if ($planer) { $ok = true; break; }
                    break;
            }

            break;

        // spices
        case 'epg/mkt':
        case 'epg/prm':
        case 'epg/clp':

            if (!isset($x['EPG_SCT'])) {
                $x['EPG_SCT'] = EPG_SCT;
            }

            $realztor 	= pms_roler('group', 'Realztor');
            $planer		= ( pms_roler('group', 'Planer') || pms_roler('user', 'Planer') );
            $marketing 	= pms_roler('group', 'Marketing');
            $promoter 	= pms_roler('group', 'Promoter');

            switch ($x['EPG_SCT']) {

                case 'mkt':
                    if ($marketing) { $ok = true; break; }
                    break;

                case 'prm':
                    if ($promoter) { $ok = true; break; }
                    break;

                case 'clp':
                    if ($realztor||$planer) { $ok = true; break; }
                    break;
            }

            switch ($act) {

                case 'mdf':
                    if ($realztor||$planer) { $ok = true; break; }
                    break;

                case 'del':

                    if ($x['EPG_SCT']=='mkt' && $x['TYP']=='block') {
                        if ($planer) { $ok = true; } // planer deletes mktepg blocks
                    }

                    if ($x['TYP']=='item') {

                        // Check whether the item is used in blocks
                        $not_empty = rdr_cell('epg_cn_blocks', 'ID', 'NativeType='.$x['EPG_SCT_ID'].' AND NativeID='.$x['ID']);

                        // Check whether the item is used in mktplan
                        if (!$not_empty) {
                            $not_empty = rdr_cell('epg_market_plan', 'ID', 'ItemID='.$x['ID']);
                        }
                    }

                    if ($x['TYP']=='block' || ($x['TYP']=='item' && $x['EPG_SCT']=='clp')) {
                        // For mkt/prm block or clip item (because clip item can be part of epg/scn):
                        // Check whether it is used in epg or scn (first try elements table, then try fragments)
                        $not_empty = block_is_used($x['ID'], $x['EPG_SCT_ID']);
                    }

                    if ($x['TYP']=='agent') {
                        // Check whether the agency is used in marketing items (only MKT has agencies)
                        $not_empty = rdr_cell('epg_market', 'ID', 'AgencyID='.$x['ID']);
                    }

                    if ($not_empty) {
                        $ok = 0; break;
                    }

                    break;
            }

            break;



        // film
        case 'epg/film':

            $filmer	= ( pms_roler('group', 'Film') || pms_roler('user', 'Film') );
            $planer = ( pms_roler('group', 'Planer') || pms_roler('user', 'Planer') );

            switch ($act) {

                case 'mdf':
                case 'bcast_mdf':
                    if ($filmer) { $ok = true; break; }
                    break;

                case 'read':
                    if ($filmer||$planer) { $ok = true; break; }
                    break;

                case 'del':

                    if ($x['TYP']=='item') { // Check whether the film is used in epg

                        if ($x['TypeID']==1) { // movie
                            $not_empty = rdr_cell('epg_films', 'ID', 'FilmParentID IS NULL AND FilmID='.$x['ID']);
                        } else { // serial
                            $not_empty = rdr_cell('epg_films', 'ID', 'FilmParentID='.$x['ID']);
                        }
                    }

                    if ($x['TYP']=='contract') {
                        // Check whether the contract is used in films
                        $not_empty = rdr_cell('film_cn_contracts', 'ID', 'ContractID='.$x['ID']);
                    }

                    if ($x['TYP']=='agent') {
                        // Check whether the agency is used in contracts
                        $not_empty = rdr_cell('film_contracts', 'ID', 'AgencyID='.$x['ID']);
                    }

                    if ($not_empty) {
                        $ok = 0; break;
                    }

                    if ($filmer) { $ok = true; break; }

                    break;
            }

            break;
    }





    /******************************************************************************************/

    if ($ok===false && pms_master()) {

        // We run pms_master() if *false*, but we don't if ZERO.
        // I.e. if you want to skip pms_master() function (which always gives pms to UZID_ALFA), set $ok to ZERO!

        $ok = true;
    }


    if ($redirect && !$ok) {

        switch ($sct) {
            case 'admin':       $redirect_url = ''; break;
            case 'hrm/uzr':     $redirect_url = 'list_uzr.php'; break;
            case 'hrm/org':     $redirect_url = 'org.php'; break;
            case 'dsk/prgm':    $redirect_url = 'list_prgm.php'; break;
            case 'dsk/tmz':     $redirect_url = 'tmz.php'; break;
            case 'dsk/stry':    $redirect_url = 'list_stry.php'; break;
            case 'dsk/cvr':     $redirect_url = 'list_cvr.php'; break;
            case 'epg':         $redirect_url = 'epgs.php'; break;
            case 'epg/mktplan': $redirect_url = 'list_mkt.php'; break;
            case 'epg/mkt':
            case 'epg/prm':
            case 'epg/clp':     $redirect_url = 'list_'.$x['EPG_SCT'].'.php?typ='.$x['TYP']; break;
            case 'epg/film':    $redirect_url = 'list_film.php?typ='.$x['TYP']; break;
        }

        redirector('access', $redirect_url);
        return null;
    } else {
        return (bool) $ok;
    }

}








/**
 * Ensure that PMS_CHNL_LOOSE constant is set, and fetch it.
 *
 * @param int $chnlid Channel ID
 * @return bool
 */
function pms_chnl_loose($chnlid) {

    if ($chnlid) {
        $chnlid = CHNL;
    }

    if (!defined('PMS_CHNL_LOOSE')) {
        define('PMS_CHNL_LOOSE', intval(cfg_local('arrz', 'chnl_'.$chnlid, 'PMS_loose')));
    }

    return PMS_CHNL_LOOSE;
}


/**
 * Check whether story belongs to program which belongs to team which has PMS_LOOSE set on.
 *
 * @param int $scnrid Scnr ID
 * @return bool $r
 */
function pms_team_loose($scnrid) {

    $r = false;

    if ($scnrid) {
        $prgm_id = rdr_cell('epg_scnr', 'ProgID', $scnrid);
        if ($prgm_id) {
            $prgm_team_id = rdr_cell('prgm', 'TeamID', $prgm_id);
            if ($prgm_team_id) {
                $r = rdr_cell('prgm_teams', 'PMS_loose', $prgm_team_id);
            }
        }
    }

    return $r;
}







/**
 * Checks for FULL permissions for epg or specified scn
 *
 * @param string $nattyp NativeType
 * @param int $natid NativeID, i.e. SCNRID
 * @param int $scnid SCNRID
 * @return bool $ok
 */
function pmsepg_full($nattyp=null, $natid=null, $scnid=null) {

    $ok = false;

    $planer = ( pms_roler('group', 'Planer') || pms_roler('user', 'Planer') );

    $realztor = pms_roler('group', 'Realztor');

    if ($realztor||$planer) {
        $ok = true;
    }

    if (!$ok) {

        if ($nattyp==1) { // prog

            $ok = pms_scnr($natid, 1); // editor

        } elseif ($nattyp==2) { // story

            if (!$scnid) {
                $scnid = rdr_cell('epg_scnr_fragments', 'ScnrID', 'NativeType=2 AND NativeID='.$natid);
            }

            $ok = pms_scnr($scnid, 1); // editor

        } elseif ($scnid) {

            $ok = pms_scnr($scnid, 1); // editor
        }
    }

    return $ok;
}


/**
 * Checks whether user should get permissions for specified epg linetype
 *
 * @param string $nattyp NativeType
 * @return bool $ok
 */
function pmsepg_line($nattyp) {

    $ok = false;

    // This is to avoid unnecessarily running the following function repeatedly.
    // pmsepg_linetypz() will return an array of EPG LINETYPES for which THIS USER has permissions
    // We then go through implode/explode procedure because constants cannot hold array values.
    if (!defined('PMS_EPG_LINETYPZ')) {
        define('PMS_EPG_LINETYPZ', implode(',',pmsepg_linetypz()));
    }
    if (in_array($nattyp, explode(',', PMS_EPG_LINETYPZ))) {
        $ok = true;
    }

    return $ok;
}




/**
 * Returns an array of EPG LINETYPES for which this user has permissions
 *
 * @return array EPG LINETYPES list
 */
function pmsepg_linetypz() {

    $marketing 	= pms_roler('group', 'Marketing');
    $promoter 	= pms_roler('group', 'Promoter');
    $filmer		= ( pms_roler('group', 'Film') || pms_roler('user', 'Film') );

    $r = [];

    if ($marketing) 			$r = array_merge($r, [3,8]);
    if ($promoter) 				$r = array_merge($r, [4,8]);
    if ($filmer) 				$r = array_merge($r, [12,13,8]);

    return $r;
}






/**
 * Checks whether specified USER ID or GROUP ID is included in specified role
 *
 * @param string $scope Scope: (user, group, descendant)
 * @param string $role Role
 * @param int $xid User ID or group ID
 * @return bool
 */
function pms_roler($scope, $role, $xid=0){
	
	$sts = cfg_local('arrz', $scope.'_permissions');
	$arr_role = explode(',', $sts[$role]);

    if (!$xid) {
        $xid = ($scope=='user') ? UZID : UZGRP;
    }

    switch ($scope) {

		case 'user':
			if (in_array($xid, $arr_role)) return true;
			break;

		case 'group':
			if (in_array($xid, $arr_role)) return true;
			break;

		case 'descendant': // Check whether any of UZGRP's ancestors are listed for pms ($arr_role)
			$ancestors = group_ancestors($xid);
            foreach ($ancestors as $v) {
                if (in_array($v, $arr_role)) return true;
            }
			break;
	}

	if (pms_master()) {
        return true;
    } else {
        return false;
    }
}








/**
 * Check SCNR permissions for specifed role
 *
 * @param int $scnid SCNRID
 * @param int $crw_typ CrewType ID (see: TXT/ARR[epg_crew_types], CFG/ARR[epg_crew_lists])
 * @param int $uid UID
 *
 * @return bool $ok
 */
function pms_scnr($scnid, $crw_typ, $uid=UZID) {

    $crw = crw_reader($scnid, 1, $crw_typ);

    $ok = ($crw && in_array($uid, $crw)) ? true : false;

    if (pms_master()) {
        $ok = true;
    }

    return $ok;
}



/* * DISCONTINUED i.e. SIMPLIFIED 20170407
(motive: when editor makes a copy then the author can't add CVR because he is not author any more..)

 * Check PMS for story CVR
 *
 * @param array $x CVR
 *
 * @return bool $ok
 * /
function pms_stry_cvr($x) {

    $stry_id = ($x['OwnerType']==2) ? $x['OwnerID'] : rdr_cell('stry_atoms', 'StoryID', $x['OwnerID']);

    $stry = rdr_row('stryz', 'UID, Phase, ScnrID', $stry_id);

    if ($stry['UID']==UZID) { // Story AUTHOR
        return true;
    }

    if ($stry['ScnrID']) { // Story's SCNR EDITOR

        $editor = pms_scnr($stry['ScnrID'], 1);

        if ($editor && $stry['Phase']>=2) {
            return true;
        }
    }

    $realztor = pms_roler('group', 'Realztor');
    if ($realztor) {
        return true;
    }

    return false;
}*/


/**
 * Check whether the *current* user (UZID) should have editor permissions for the specified program
 *
 * @param int $prgm_id Program ID
 * @return bool
 */
function pms_prgm_editor($prgm_id) {


    // For cases where program is not selected (i.e. special one-time programs), give editor pms to everybody

    if (!$prgm_id) {
        return true;
    }


    // Try program editor

    $prgm_editors = crw_reader($prgm_id, 45, 1); // 45 is TBLID for DB:prgm

    if ($prgm_editors && in_array(UZID, $prgm_editors)) {
        return true;
    }


    // Try team editor (for the team that this program belongs to)

    $team_id = rdr_cell('prgm', 'TeamID', $prgm_id);    // Get the team ID for the specified prog

    $team_editors = crw_reader($team_id, 49, 1); // 49 is TBLID for DB:prgm_teams

    if ($team_editors && in_array(UZID, $team_editors)) {
        return true;
    }


    $team_chnl = rdr_cell('prgm_teams', 'ChannelID', $team_id);

    // If there are no any defined editors, either on program level or parent team level,
    // then we give editor permissions to any member of the associated org-group (or its descendants) for the parent team
    if ((!$team_editors && !$prgm_editors) || pms_chnl_loose($team_chnl)) {

        $team_grp_id = rdr_cell('prgm_teams', 'GroupID', $team_id);	// Get the org-group ID for the specified team

        if (UZGRP==$team_grp_id) {
            return true;
        }

        $ancestors = group_ancestors(UZGRP); // Check whether this group is a descendant of the org-group

        if (in_array($team_grp_id, $ancestors)) {
            return true;
        }
    }


    return false;
}





/**
 * Impose security restrictions on *reading* the stories (and scnrz which reveal them too)
 *
 * @param array $x
 * @param string $typ Type (stry, scnr)
 *
 * @return void
 */
function stry_security($x, $typ) {

    global $cfg;


    if ($typ=='stry') {

        if ($x['UID']==UZID) { return; } // exempt story author from restrictions on his story

        if ($x['Phase']==0) { // exempt task asignee
            $asigned_uid = crw_reader($x['ID'], 2, 5, 'normal_single');
            if ($asigned_uid==UZID) { return; }
        }
    }

    if ($cfg['stry_sec_level']>0 && $_SESSION['UserSecLevel']!=1) {

        if ($_SESSION['UserSecLevel']==0) {
            redirector('access');
        }

        if ($typ=='stry' && $_SESSION['UserSecLevel']==2) {
            redirector('access');
        }
    }


    // SecurityStrict


    if ($typ=='scnr') {

        if ($x['NativeType']==1 && $x['PRG']['ProgID']) {

            // only restrict viewtypes which reveal stories
            if ($x['PRG']['SETZ']['SecurityStrict'] && in_array(VIEW_TYP, [1,3,5,7])) {

                $ok = false;

                if (!$ok) {
                    if (strtotime($x['TermEmit']) < time()) {
                        $ok = true;
                    }
                }

                if (!$ok) {
                    $ok = pms_prgm_editor($x['PRG']['ProgID']); // Editor
                }

                if (!$ok) {
                    $ok = pms_scnr($x['SCNRID'], 1) || pms_scnr($x['SCNRID'], 2) || pms_scnr($x['SCNRID'], 3);
                }

                if (!$ok) {
                    redirector('access');
                }
            }
        }
    }

    if ($typ=='stry') {

        if ($x['ScnrID']) {

            $progid = rdr_cell('epg_scnr', 'ProgID', $x['ScnrID']);

            if ($progid) {

                $security_strict = rdr_cell('prgm_settings', 'SecurityStrict', $progid);

                if ($security_strict) {

                    $ok = false;

                    if (!$ok) {
                        $termemit = rdr_cell('epg_elements', 'TermEmit', 'NativeType=1 AND NativeID='.$x['ScnrID']);
                        if (strtotime($termemit) < time()) {
                            $ok = true;
                        }
                    }

                    if (!$ok) {
                        $ok = pms_scnr($x['ScnrID'], 1) || pms_scnr($x['ScnrID'], 2); // Editor or speaker
                    }

                    if (!$ok && $x['Phase']==3) {
                        $ok = pms_roler('group', 'Proofreader'); // Proofreader in his phase
                    }

                    if (!$ok) {
                        redirector('access');
                    }
                }
            }
        }
    }
}



/**
 * Master pms function, has priority over normal pms function
 *
 * @return bool ALLOW or DENY permission
 */
function pms_master() {

    if (UZID==UZID_ALFA) return true;

    return false;
}
