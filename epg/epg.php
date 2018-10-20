<?php
/**
 * Displays epg or scn.
 */

require '../../__ssn/ssn_boot.php';




/** Schedule type: epg, scnr */
define('TYP', ($_GET['typ']=='epg') ? 'epg' : 'scnr');




$view_arr = txarr('arrays', TYP.'_view_types');



/**
 * View type shows up in view navigation on the top.
 * For EPG type, we want to remember viewtype in setz, and we want to put it into sesion variable so we don't have to
 * read from setz each time.
 */
if (TYP=='epg') {

    define('VIEW_TYP', ((isset($_GET['view'])) ? intval($_GET['view']) : 0));

    /*
    if (isset($_GET['view'])) {

        define('VIEW_TYP', intval($_GET['view']));

        $_SESSION['epg_viewtyp'] = VIEW_TYP;

        setz_put('epg_viewtyp', VIEW_TYP);

    } else { // Nothing changed.. read from session or setz.

        define('VIEW_TYP', (isset($_SESSION['epg_viewtyp']) ? $_SESSION['epg_viewtyp'] : setz_get('epg_viewtyp')));
    }*/

} else { // scnr

    define('VIEW_TYP', ((isset($_GET['view'])) ? intval($_GET['view']) : 0));
}


/** Turns calendar bar on/off. */
define('SHOW_CALENDAR', (VIEW_TYP==8 || setz_get('epg_calendar')));




/**
 * EPG: DATE of the EPG to be created, i.e. NEW EPG.
 * (We are forced to use date, because we still don't have ID of target epg, because it is not created yet.)
 * This variable will be set only when receiving form submission (from epg_modify_multi) in EPG_FROM_TMPL situation,
 * and it is used only in RECEIVER script which follows.
 */
define('EPG_NEW', (isset($_GET['d']) ? wash('ymd', $_GET['d']) : ''));

if (isset($_POST['Submit_SING'])) {
    require '_rcv/_rcv_epg_single.php';
}

if (isset($_POST['Submit_MULT'])) {
    require '_rcv/_rcv_epg_multi.php';
}









/* FIRST RECEIVERS (which do not require EPG/SCNR data or pms, etc..) */



/*
 * EPG_LIST_SHORT &&  EPG_LIST_PROGS (only in EPG LIST view)
 */
setz_switcher('epg_list_short', (TYP=='epg' && VIEW_TYP==0));
setz_switcher('epg_list_progs', (TYP=='epg' && VIEW_TYP==0));



/*
 * In TREE view, another click on the TREE VIEW BUTTON toggles COLLAPSE ALL on and off. Default is off.
 * In CVR view, another click on the CVR VIEW BUTTON toggles COLLAPSE ALL on and off.
 * In STUDIO view, another click on the STUDIO VIEW BUTTON toggles showing contents of ALL atoms instead of only *reading*
 */
if (in_array(VIEW_TYP, [1,2,3])) { // TREE, CVR, STUDIO

    $clps = 'collapse_'.TYP.VIEW_TYP;

    if (isset($_GET['collapse'])) {

        define('VIEW_TOGGLE_ALLCOLLAPSE', intval($_GET['collapse']));

        setz_put($clps, VIEW_TOGGLE_ALLCOLLAPSE);

        $_SESSION[$clps] = VIEW_TOGGLE_ALLCOLLAPSE;

    } else {

        define('VIEW_TOGGLE_ALLCOLLAPSE', (isset($_SESSION[$clps]) ? $_SESSION[$clps] : setz_get($clps)));
    }

    unset($clps);
}



/**
 * We don't display logs ALWAYS. There is a LOG button at the bottom of the page, which turns log display on.
 * We use a CONSTANT here to define logging status and also save logs into a SESSION variable because we need
 * to use variables which will be available inside functions. Logging function will be called multiple times
 * from inside epg_dtl_html() in order to get logs for each element, and then later finally it will also be called
 * from epg.php to get logs for epg generally.
 */
define('LOG', (empty($_GET['log']) ? 0 : 1));
if (LOG) {
    $_SESSION['epglog'] = []; // Initialize logs array.
}



/*
 * MKTPLAN Receiver
 */
if (VIEW_TYP==8 && isset($_POST['MKTPLAN_MDFID'])) {
    require '_rcv/_rcv_mktplan_item.php';
}



/*
 * Changing READSPEED for specific speaker for this scnr.
 * Fired via box with speaker's name, which triggers popover with links..
 */
if (isset($_GET['crw'])) {

    $rs = (isset($_GET['rs'])) ? wash('int', $_GET['rs']) : 0;
    $crw = (isset($_GET['crw'])) ? wash('int', $_GET['crw']) : 0;
    $scnrid = (isset($_GET['scnrid'])) ? wash('int', $_GET['scnrid']) : 0;
    $speakerx = (isset($_GET['speakerx'])) ? wash('int', $_GET['speakerx']) : 0;

    qry('UPDATE cn_crw SET OptData='.$rs.' WHERE ID='.$crw);

    speakerX_termemit($scnrid, $speakerx);

    hop($_SERVER['HTTP_REFERER']); // Reload the requesting page
}



/*
 * DELETING
 * Fired (only in RECYCLE view) either by DELETE button on the right side of each element/fragment,
 * or by DELETE ALL button at the bottom.
 */
if (isset($_GET['del'])) {

    $target_id = intval($_GET['del']);

    $scope = (isset($_GET['delscope'])  && ($_GET['delscope']=='target')) ? 'target' : 'descend';

    $tmpl = (isset($_GET['tmpl'])) ? 1 : 0;

    //$filter = ($tmpl && TYP=='epg') ? 'all' : 'inactives';

    $filter = ((TYP=='epg') && ($tmpl || @$_GET['filter']=='all')) ? 'all' : 'inactives';

    if ($filter=='all')	{
        $x_tmp['EPG']['DateAir'] = rdr_cell('epgz', 'DateAir', $target_id);
        pms('epg', (($tmpl) ? 'tmpl_epg' : 'del_epg'), $x_tmp, true);
        unset($x_tmp);
    }


    epg_deleter(TYP, $target_id, $scope, $filter, $tmpl);


    // Decide where do we go from here

    if (!$tmpl) {

        if ($scope=='target') {

            $location = $_SERVER['HTTP_REFERER'];

        } else { // descend

            if ($filter=='all') {
                $location = $pathz['www_root'].dirname($_SERVER['PHP_SELF']).'/epgs.php';
            } else {
                $location = $pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?typ='.TYP.'&view=0'.
                    '&id='.((TYP=='epg') ? $_GET['del'] : $_GET['id']);
            }
        }

    } else { // TMPL

        if ($scope=='descend') { // epg
            $location = $pathz['www_root'].dirname($_SERVER['PHP_SELF']).'/epgs.php?typ=tmpl&tmpltyp=epg';
        } else { // scnr
            $location = $pathz['www_root'].dirname($_SERVER['PHP_SELF']).'/epgs.php?typ=tmpl&tmpltyp=scn';
        }
    }

    hop($location);
}
















/* GET EPG/SCNR DATA + values which rely on the data */

if (TYP=='epg') {

    // get the EPG array/object

    $id = (isset($_GET['id'])) ? wash('int', $_GET['id']) : 0;
    if ($id) $x['EPG'] = epg_reader($id);


    if (SHOW_CALENDAR) {


        // *dtr* is from the calendar, *dtr_date* is from the redirect
        if (isset($_GET['dtr']) || isset($_GET['dtr_date'])) {

            // I had to add this little tweak with $_GET['dtr_date'] because otherwise dtr name-value pair would be
            // repeatedly added to url when clicking on one 0-epg date after another after another etc.. (i.e. redirect
            // is avoided)
            // Instead, this way we certainly do one redirect after dtr click, thus cleaning the dtr name-value pair
            // from the url..
            $dtr['DateAir'] = wash('ymd', ((isset($_GET['dtr'])) ? $_GET['dtr'] : $_GET['dtr_date']));

            if ($dtr['DateAir']) {

                $dtr['0-EPG'] = (!@$x['EPG']['ID'] && !isset($_GET['dtr'])) ? true : false;

                $dtr['ChannelID'] = (isset($_GET['dtr_chnl'])) ? intval($_GET['dtr_chnl']) : $x['EPG']['ChannelID'];

            } else {

                $dtr = false;
            }

        } else {

            $dtr = false;
        }


        if ($dtr) { // If a calendar date has been clicked

            $clicked_epgid = epgid_from_date($dtr['DateAir'], $dtr['ChannelID']);

            if ($clicked_epgid) { // EPG for the date exists, simply redirect to it

                $location = $pathz['www_root'].dirname($_SERVER['PHP_SELF']).
                    '/epg.php?typ=epg&view='.VIEW_TYP.'&id='.$clicked_epgid;
                hop($location);

            } elseif (!$dtr['0-EPG']) { // EPG doesnot exist, redirect to 0-EPG (unless we are already in 0-EPG)

                $location = $pathz['www_root'].dirname($_SERVER['PHP_SELF']).
                    '/epg.php?typ=epg&view='.VIEW_TYP.'&dtr_date='.$dtr['DateAir'].'&dtr_chnl='.$dtr['ChannelID'];
                hop($location);

            } else { // 0-EPG

                // If EPG for the date doesnot exist, we still have to output mktplan list.
                // Thus we construct this replacment for $x['EPG']

                $x['EPG'] = [
                    'ID' => 0,
                    'DateAir' => $dtr['DateAir'],
                    'ChannelID' => $dtr['ChannelID'],
                    'IsTMPL' => false,
                    'IsReady' => false,
                ];
            }
        }

        define('DTR', $x['EPG']['DateAir']); // For JS calendar, to select the clicked date
    }


    if (!@$x['EPG']['ID'] && !@$dtr['0-EPG']) {
        require '../../__inc/inc_404.php';
    }

    /** Channel type: 1=tv, 2=radio */
    define('CHNLTYP', rdr_cell('channels', 'TypeX', $x['EPG']['ChannelID']));

    define('EPGID', $x['EPG']['ID']);

	define('TMPL', ($x['EPG']['IsTMPL']) ? 1 : 0);


    if (!TMPL) {
        $datecpt = epg_cptdate($x['EPG']['DateAir'], '3day');
    }

    if (!TMPL && VIEW_TYP!=2) { // In CVR view, we have too many controls, so we omit the clock; also, skip for templates.

        // This variable will be used in header script, for configuring JS for "clocks".
        // EPG needs only NOW-CLOCK ('epg_now'), i.e. the simple clock which shows current time.
        $show_epg_clock = ['epg_now'=>1];
    }


    // List of line types for this schedule type (epg). See LNG-ARRAYS: epg_line_types.
    // Used for interface: squiz buttons (order dictates the order of buttons!), filter cbo.
    $linetypz = (CHNLTYP==1) ? [1,12,13,3,4,5,8,9,14] : [1,3,4,5,8,9,14]; // Radio doesn't use film/serial


} else {  // scnr


    // get the ELEMENT array/object

    $x = element_reader_front();
    if (!@$x['SCNRID']) {
        require '../../__inc/inc_404.php';
    }

    stry_security($x, 'scnr');

    /** Channel type: 1=tv, 2=radio */
    define('CHNLTYP', rdr_cell('channels', 'TypeX', (isset($x['EPG']['ChannelID']) ? $x['EPG']['ChannelID'] : CHNL)));
    // SCNR TMPL doesnot have EPG data..

    define('SCNRID', $x['SCNRID']);
    define('EPGID', $x['EPG']['ID']);

	define('TMPL', ($x['EPG']['IsTMPL']) ? 1 : 0);


    if ($x['TimeAir']) {

		$css_tbox['term'][] = 'fixed'; // css class which will be added to timebox for TERM (first cell in first row)

	} else {

	    // If the element (for which we are building scenario) doesn't have start time (fixed TimeAir),
        // we will have to calculate it
		
		if (!TMPL) {
			
			$x['TimeAir'] = element_start_calc(EPGID, $x['Queue'], $x['EPG']['DateAir'], $x['TermEmit']);
			
		} else { // If it's a TMPL then we can't calculate because it is not part of any EPG

            // We have to use some dummy datetime, so the term calculating procedures would work properly in SCN templates
			$x['TimeAir'] = $cfg[SCTN]['tmpl_dummy_starttime'];
		}
	}

	if (!TMPL) {

        $datecpt = epg_cptdate($x['EPG']['DateAir'], 'date_wday');

        $timeair = strtotime($x['TimeAir']);
        $do_countdown = ($timeair > strtotime('-2 hours') && $timeair < strtotime('+18 hours')) ? 1 : 0;

        if (!empty($_GET['sw_countdown'])) { // This way I can force countdown clocks when making photo/video for manuals
            $do_countdown = true;
        }

        /* This variable will be used in header script, for configuring JS for "clocks".
         * SCN needs NOW-CLOCK ('epg_now'). It also needs PRG-CLOCKS ('epg_prg'), which are TWO specific clocks - one to
         * countdown time until broadcast start and the other to countdown time until broadcast finish.
         */
		$show_epg_clock = ['epg_now'=>1, 'epg_prg'=>$do_countdown];
	}


    // Data for TIMEBOXES

	$x['_Dur'] = epg_durations($x, 'hms');

	$x['_TimeFinito'] = add_dur2term($x['TimeAir'], $x['_Dur']['winner']['dur_hms']);

	$x['_JS_Start'] = time2jstime($x['TimeAir']);
	$x['_JS_End'] 	= time2jstime($x['_TimeFinito']);


    // List of line types for this schedule type (scn). See LNG-ARRAYS: epg_line_types.
    // Used for interface: squiz buttons (order dictates the order of buttons!), filter cbo.
    $linetypz = ($x['NativeType']==1) ? [2,6,7,3,4,5,8,9,10] : [7,3,4,5,8,9,10];
	
	$epg_mattyp_signs = txarr('arrays', 'epg_mattyp_signs');

    if (CHNLTYP==2) {
        $epg_mattyp_signs[1] = $cfg[SCTN]['epg_radio_live_sign'];
    }
}


if (CHNLTYP==2) { // RADIO channel: Film and CarGen not used
    unset($view_arr[10]); // film
    unset($view_arr[2]); // cargen
}

// Story atoms jargon
define('ATOM_JARGON', setz_get('atom_jargon_'.CHNLTYP));


// Determine whether this is BLANK schedule
$tbl   = (TYP=='epg') ? 'epg_elements' : 'epg_scnr_fragments';
$where = (TYP=='epg') ? ['EpgID='.EPGID] : ['ScnrID='.SCNRID];
if (VIEW_TYP==5) $where[] = '!IsActive';
define('SCH_BLANK', ((cnt_sql($tbl, implode(' AND ', $where))) ? false : true));



// We put the following code-block right after SCH_BLANK definition because it uses SCH_BLANK const and $tbl and $where

/*
 * Importing default scnr tmpl
 * Fired any time an empty scnr (with defined prog) is opened
 */
if (TYP!='epg' && !TMPL && SCH_BLANK && VIEW_TYP==0 && @$x['PRG']['ProgID']) {

    // If *skip_auto* is on, then we proceed only if button (import dflt tmpl) was clicked

    if (!$x['PRG']['SETZ']['EPG_Skip_Dflt_Tmpl_Auto_Import'] || !empty($_GET['scnr_tmpl_dflt'])) {

        // Import default tmpl to this scnr

        if ($x['PRG']['ProgID']) {

            $prog_tmpl_id = $x['PRG']['SETZ']['EPG_TemplateID'];

            if ($prog_tmpl_id) {

                scnr_copy($prog_tmpl_id, $x['ID']); // Copy template contents to scnr contents

                // We have to check whether anything changed, because if dflt tmpl was empty, then scnr would stay
                // the same and we would enter into redirect loop
                $sch_blank = (cnt_sql($tbl, implode(' AND ', $where))) ? false : true;

                if (!$sch_blank) {
                    hop($_SERVER['REQUEST_URI']); // Reload the requested page
                }

            }
        }
    }
}



if (TYP!='epg' && TMPL) {

    /*
     * Setting THIS template as the default for the prog.
     * Fired via dflt button in scnr tmpl header.
     */
    if (!empty($_GET['tmpl_dflt'])) {

        pms('epg', 'tmpl_scnr', $x, true);

        receiver_mdf('prgm_settings', ['ID' => $x['PRG']['ProgID']], ['EPG_TemplateID' => $x['ID']], LOGSKIP);

        hop($_SERVER['HTTP_REFERER']); // Reload the requesting page
    }

    $x['TMPL']['IsDefault'] = false;

    // If the program is selected, check whether this tmpl is set as the default tmpl for that program
    if ($x['PRG']['ProgID']) {

        $prog_tmpl_id = $x['PRG']['SETZ']['EPG_TemplateID'];

        if ($x['ID']==$prog_tmpl_id) {
            $x['TMPL']['IsDefault'] = true;
        }
    }
}












/* COMMON PMS */

define('PMS_FULL', pms('epg', 'mdf_full', $x));

if (TYP=='scnr') {

    if (!TMPL) {

        if (in_array(VIEW_TYP, [0,1,3,7])) { // list, tree, studio, prompter

            $speakerz = speakerz(SCNRID);
        }

        if (VIEW_TYP==3) { // STUDIO

            define('PMS_MDF_STUDIO_SPEAKER', pms('epg', 'mdf_speaker_x', $x));

            $editors = crw_reader(SCNRID, 1, 1);
        }

        if (in_array(VIEW_TYP, [0,1,3])) { // list, tree, studio

            define('PMS_MDF_EDITDIVABLE', pms('epg', 'mdf_editdivable', $x));
        }

        define('PMS_SCNR_DSC', pms('epg', 'mdf_scn_dsc', $x));

    } else { // TMPL

        define('PMS_TMPL_SCNR', pms('epg', 'tmpl_scnr', $x));
    }

}

if (!defined('PMS_MDF_EDITDIVABLE')) {
    define('PMS_MDF_EDITDIVABLE', false); // We need it for EPG in TREE preview, because it contains STORIES, which have PHZ..
}


/*
 * PMS for epg-tips mdf ctrlz
 */
define('SHOW_TIPS_NOTE', (in_array(VIEW_TYP, [0,1,8]) && !TMPL));
define('SHOW_TIPS_CAM', (VIEW_TYP==1 && TYP=='scnr' && !TMPL));
define('SHOW_TIPS_VO', (VIEW_TYP==1 && TYP=='scnr' && !TMPL));
define('SHOW_JS_CTRLZ', (VIEW_TYP==1 && TYP=='scnr' && !TMPL));

if (SHOW_TIPS_NOTE) {

    if (TYP=='epg') {
        define('CTRL_TIPS_NOTE', pms('epg', 'mdf_epg_tips'));
    } else { // scnr
        define('CTRL_TIPS_NOTE', pms('epg', 'mdf_scn_tips_note', $x));
    }

    define('MDF_TIPS_NOTE', (int)(CTRL_TIPS_NOTE && !empty($_GET['mdftips'])));
}

if (SHOW_TIPS_CAM) {
    define('CTRL_TIPS_CAM', pms('epg', 'mdf_scn_tips_cam'));
    define('MDF_TIPS_CAM', (int)(CTRL_TIPS_CAM && !empty($_GET['mdfcam'])));
}

if (SHOW_TIPS_VO) {
    define('CTRL_TIPS_VO', pms('epg', 'mdf_scn_tips_vo'));
    define('MDF_TIPS_VO', (int)(CTRL_TIPS_VO && !empty($_GET['mdfvo'])));
}












/* SECOND RECEIVERS (which require EPG/SCNR data) */


/*
 * MKTPLAN
 */
if (VIEW_TYP==8) {

    define('MKTPLAN_TWINVIEW_TYP', @intval($_GET['mktplan_twinview_typ']));

    define('MKT_SYNC', (MKTPLAN_TWINVIEW_TYP==2) ? true : false);

    setz_switcher('epg_mktview', true, false); // define EPG_MKTVIEW (1-plan, 2-epg, 0-both)


    if (isset($_POST['Submit_MKTSYNC']) || !empty($_POST['mkt_sync_delz']) || !empty($_POST['mkt_sync_insz'])
        || isset($_POST['mktplan_link_code']) || isset($_GET['mktplan_equal']) || isset($_GET['mktplan_unlink'])
        || isset($_GET['mkt_sync_auto'])) {
        $redirect = true;
    } else {
        $redirect = false;
    }
    define('PMS_MKT_SYNC', pms('epg/mktplan', 'sync', null, $redirect));
    define('PMS_MKT_MDF', pms('epg/mktplan', 'mdf'));


    if (isset($_GET['del_mktepg'])) { // Delete a non-associated mktepg

        $target_id = intval($_GET['del_mktepg']);

        $target_typ = (isset($_GET['del_schtyp']) && ($_GET['del_schtyp']=='epg')) ? 'epg' : 'scnr';

        $block_id = rdr_cell((($target_typ=='epg') ? 'epg_elements' : 'epg_scnr_fragments'), 'NativeID', $target_id);


        // Delete element/fragment
        epg_deleter($target_typ, $target_id, 'target');

        if ($target_typ=='epg') {
            sch_termemit(EPGID, 'epg');
        } else {
            sch_termemit(intval($_GET['scnrid']), 'scnr');
        }


        // Delete spice block if not used anywhere
        block_is_used($block_id, 3, true);


        hop($_SERVER['HTTP_REFERER']); // Reload the requestING page
    }


    if (isset($_GET['mkt_sync_auto'])) { // SYNC-AUTO (anchor)

        $mktepgz = mktepg_arr(EPGID, null, 'idz');


        if (!empty($mktepgz['epg'])) {

            foreach ($mktepgz['epg'] as $elemid) {

                $block_id = rdr_cell('epg_elements', 'NativeID', $elemid);

                epg_deleter('epg', $elemid, 'target'); // Delete all mkt elements (in epg)

                // Delete spice block if not used anywhere
                block_is_used($block_id, 3, true);
            }

            epg_qu_refresh(EPGID);
        }

        if (!empty($mktepgz['scnr'])) {

            foreach ($mktepgz['scnr'] as $fragid) {

                $block_id = rdr_cell('epg_scnr_fragments', 'NativeID', $fragid);

                epg_deleter('scnr', $fragid, 'target'); // Delete all mkt fragments (in scnrz)

                // Delete spice block if not used anywhere
                block_is_used($block_id, 3, true);
            }
        }


        // AUTO add mktepgz
        if ($_GET['mkt_sync_auto']!='del_only') {

            $mktplanz = mktsync_diagnostic($x['EPG']);

            if (!empty($mktplanz['omg'])) {
                omg_put('info', $tx[SCTN]['LBL']['not_found'].': '.implode(', ', $mktplanz['omg']));
            }

            // Add to EPGz
            if (!empty($mktplanz['epg'])) {
                foreach ($mktplanz['epg'] as $mktplan_code => $elemid) {
                    mktepg_new('epg', $mktplan_code, EPGID, ['ref_elemid' => $elemid]);
                }
            }

            // Add to SCNRz
            if (!empty($mktplanz['scnr'])) {
                foreach ($mktplanz['scnr'] as $mktplan_code => $mktepg_pick) {
                    mktsync_rcv($mktplan_code, $mktepg_pick, EPGID);
                }
            }
        }

        sch_termemit(EPGID, 'epg');
        hop($_SERVER['HTTP_REFERER']); // Reload the requestING page
    }

    if (isset($_POST['Submit_MKTSYNC'])) { // Sync RCV

        if ($_POST['mkt_sync_code']) { // sync_single

            $mktplan_code = wash('int', $_POST['mkt_sync_code']);

            $mktepg_pick = $_POST['MKTEPG_CODE'][$mktplan_code];

            mktsync_rcv($mktplan_code, $mktepg_pick, EPGID);

        } else { // sync_all

            foreach ($_POST['MKTEPG_CODE'] as $mktplan_code => $mktepg_pick) {

                mktsync_rcv($mktplan_code, $mktepg_pick, EPGID);
            }
        }

        sch_termemit(EPGID, 'epg');

        hop($_SERVER['REQUEST_URI']); // Reload the requestED page
    }

    if (!empty($_POST['mkt_sync_delz']) || !empty($_POST['mkt_sync_insz'])) { // SYNC-EPG

        if (!empty($_POST['mkt_sync_insz'])) {

            $ins_arr = explode(',', $_POST['mkt_sync_insz']);

            foreach ($ins_arr as $elemid) {

                $mdf = [
                    'Queue' => rdr_cell('epg_elements', 'Queue', wash('int', $elemid)),
                    'EpgID' => EPGID,
                    'NativeID' => 0,
                    'NativeType' => 3,
                    'DurForc' => null,
                    'OnHold' => 0,
                    'TimeAir' => null,
                    'IsActive' => 1
                ];

                qry('UPDATE epg_elements SET Queue=Queue+1 WHERE Queue>='.$mdf['Queue'].' AND EpgID='.$mdf['EpgID']);

                receiver_ins('epg_elements', $mdf);
            }
        }

        if (!empty($_POST['mkt_sync_delz'])) {

            $del_arr = explode(',', $_POST['mkt_sync_delz']);

            foreach ($del_arr as $elemid) {
                epg_deleter('epg', wash('int', $elemid), 'target');
            }
        }

        sch_termemit(EPGID, 'epg');

        hop($_SERVER['HTTP_REFERER']); // Reload the requestING page
    }


    if (isset($_POST['mktplan_link_code'])) { // LINKING SIBLINGS (MKTPLAN-MKTEPG) (modal)

        $mktplan_code = wash('int', $_POST['mktplan_link_code']);

        $mktepg_code = $_POST['MKTEPG_CODE'];
    }

    if (isset($_GET['mktplan_unlink'])) { // UNLINKING SIBLINGS (anchor)

        $mktplan_code = wash('int', $_GET['mktplan_unlink']);

        $mktepg_code = '';
    }

    if (isset($_POST['mktplan_link_code']) || isset($_GET['mktplan_unlink'])) {

        mktsync_rcv($mktplan_code, $mktepg_code, EPGID);

        sch_termemit(EPGID, 'epg');

        hop($_SERVER['HTTP_REFERER'].'#tr'.$mktplan_code); // Reload the requestING page
    }


    if (isset($_GET['mktplan_equal'])) { // EQUALING SIBLINGS (anchor)

        $eql_type = ($_GET['mktplan_equal']=='right') ? 'right' : 'left';

        $mktplan_code = wash('int', $_GET['code']);

        if ($eql_type=='right') {

            mktuptd_mktepg_reset_wrapper($mktplan_code, true);

        } else { // left

            mktuptd_reverse($mktplan_code);
        }

        hop($_SERVER['HTTP_REFERER'].'#tr'.$mktplan_code); // Reload the requestING page
    }
}


/*
 * WEB export
 */
if (VIEW_TYP==4 && !empty($_GET['downer'])) {

    downer_header($_GET['output_type'], 'epg'.rdr_cell('epgz', 'DateAir', EPGID).'.xml');

    require '../../__fn/fn_xml.php';
    require '../../__fn/fn_xml_tvepg.php';

    xmlepg(EPGID);

    exit;
}



/*
 * EXPORTER (MOS)
 */
if (VIEW_TYP==11) {

    require '../../__fn/fn_xml_morpheus.php';

    $sch_data = morpheus_data($x);

    if (!empty($_GET['downer'])) {

        downer_header($_GET['output_type'], $sch_data['NME']['name'].'.sch');

        if (!empty($_GET['scnrexp'])) { // scnr

            $listtyp = 'scnr';
            $id = intval($_GET['scnrexp']);

        } else { // epg

            $listtyp = 'epg';
            $id = EPGID;
        }

        morpheus_epgexp($listtyp, $id, $sch_data);
        exit;
    }
}


/*
 * EPG CVR display cbo
 */
if (!empty($_GET['epg_cvr_cbo']) && TYP=='epg' && VIEW_TYP==2 && !SCH_BLANK) {

    setz_put('epg_cvr_cbo', intval($_GET['epg_cvr_cbo']));

    hop($_SERVER['HTTP_REFERER']);
}



/*
 * Setting SCNR's IsReady state
 * Fired via IsReady button which is in front of program caption in scnr header
 */
if (!empty($_GET['scnr_ready']) && TYP!='epg' && !TMPL && PMS_SCNR_DSC) {

    qry('UPDATE epg_scnr SET IsReady=1 WHERE ID='.$x['PRG']['ID']);

    hop($_SERVER['HTTP_REFERER']);
}



/*
 * Pending stories accept/reject
 */
if (TYP=='scnr' && !empty($_GET['pending_act'])) {

    $editor = pms_scnr(SCNRID, 1);

    if ($_GET['pending_act']=='accept') {

        if ($editor) {
            epg_conn_receiver(2, $_GET['pending_id'], SCNRID);
        }

    } else { // pending_act: reject

        $stry_author = rdr_cell('stryz', 'UID', $_GET['pending_id']);

        if ($editor || $stry_author==UZID) {
            qry('UPDATE stryz SET ScnrID=NULL WHERE ID='.$_GET['pending_id']);
        }
    }

    hop($_SERVER['HTTP_REFERER']);
}



/*
 * Deleting primary duration
 * Fired via shortcut in duration column
 */

if (isset($_GET['durdel']) && isset($_GET['durtyp']) && PMS_FULL) {

    $durdel_id = wash('int', $_GET['durdel']);

    $tbl = (TYP=='epg') ? 'epg_elements' : 'epg_scnr_fragments';

    if ($_GET['durtyp']=='durforc') {

        $nat = rdr_row($tbl, 'NativeType, NativeID', $durdel_id);

        switch ($nat['NativeType']) {

            case 2:     $durtbl = 'stryz';     break;
            case 3:
            case 4:     $durtbl = 'epg_blocks';     break;
        }

        qry('UPDATE '.$durtbl.' SET DurForc=NULL WHERE ID='.$nat['NativeID'], ['x_id' => $nat['NativeID']]);

    } elseif ($_GET['durtyp']=='durepg') {

        qry('UPDATE '.$tbl.' SET DurForc=NULL WHERE ID='.$durdel_id, ['x_id' => $durdel_id]);
    }

    hop($_SERVER['HTTP_REFERER']);
}



/*
 * Whether there should be an editing shortcut on the DUR column for the RECS atoms
 */
define('DUR_EDITABLE', (!TMPL && VIEW_TYP==6 && (PMS_FULL || pms('epg', 'mdf_recs_dur'))));

if (DUR_EDITABLE) { // This code block is receiver for DUR_EDITABLE actions

    if (isset($_GET['mdfid']) && isset($_GET['mdfhms'])) {

        $mdfid = wash('int', $_GET['mdfid']);

        $arr_hms = rcv_hms($_GET['mdfhms']);
        $mdfdur = rcv_datetime('hms_nozeroz', $arr_hms);

        if (TYP=='scnr') {

            // Update duration
            $log = ['tbl_name' => 'stryz', 'act_id' => 61, 'act' => 'recs'];
            qry('UPDATE stry_atoms SET Duration=\''.$mdfdur.'\' WHERE ID='.$mdfid, $log);

            // EMIT update
            $stry_id = rdr_cell('stry_atoms', 'StoryID', $mdfid);
            stry_termemit($stry_id);

        } else { // epg

            $z = rdr_row('epg_elements', 'ID, NativeType, NativeID', $mdfid);

            switch ($z['NativeType']) {

                case 1:

                    $z['MOS'] = mos_reader($z['NativeID'], $z['NativeType']);

                    $log = ['tbl_name' => 'epg_elements', 'x_id' => $z['ID']];

                    mos_receiver($z['NativeID'], $z['NativeType'], $z['MOS'], $log, ['Duration' => $mdfdur]);

                    // We don't add prog DurEmit update here, because it is currently not really necessary

                    break;

                case 12:
                case 13:

                    $filmtbl = ($z['NativeType']==12) ? 'film' : 'film_episodes';

                    $film_id = rdr_cell('epg_films', 'FilmID', $z['NativeID']);

                    qry('UPDATE '.$filmtbl.' SET DurReal=\''.$mdfdur.'\' WHERE ID='.$film_id, ['x_id' => $film_id]);

                break;
            }
        }

        hop($_SERVER['HTTP_REFERER'].'#tr'.$mdfid);
    }
}



/*
 * Whether there should be an editing shortcut on the TERM column
 */
define('TERM_EDITABLE', (!TMPL && in_array(VIEW_TYP, [0,1]) && pms('epg', 'mdf_term')));

if (TERM_EDITABLE) { // This code block is receiver for TERM_EDITABLE actions


    if (isset($_GET['holder']) && TYP=='epg') { // Set OnHold

        $holder_id = wash('int', $_GET['holder']);

        qry('UPDATE epg_elements SET OnHold = NOT IFNULL(OnHold, 0), TimeAir=NULL WHERE ID='.$holder_id, ['x_id' => $holder_id]);

        // Since I have later put JS disable on HOLD button if termtyp==HOLD, this sql could now be simplified.
        // HOLD button is now not a *switch*, it can only turn HOLD to *true*, i.e. switch it *on*..
        // Anyway I will leave it for the reference to this issue - making a boolean switch in sql..

        hop($_SERVER['HTTP_REFERER'].'#tr'.$holder_id);
    }


    if (isset($_GET['mdfid']) && isset($_GET['mdfhms'])) { // Specifically set HMS for new fixed term

        $mdfid = wash('int', $_GET['mdfid']);

        $arr_mdf = explode(':', $_GET['mdfhms']);

        if (count($arr_mdf)!=3) {

            omg_put('danger', $_GET['mdfhms'].' - '.$tx[SCTN]['MSG']['term_faulty']);

            hop($_SERVER['HTTP_REFERER']);

        } else {

            $arr_hms = array_combine(['hh', 'mm', 'ss'], $arr_mdf);

            $mdfterm = rcv_datetime('ymdhms', $arr_hms, $x['EPG']['DateAir']);

            if (TYP=='scnr') {

                $faul = fragment_term_faul($x, $mdfterm);

                if ($faul) {
                    hop($_SERVER['HTTP_REFERER']);
                }
            }

            qry('UPDATE '.((TYP=='epg') ? 'epg_elements' : 'epg_scnr_fragments').
                ' SET TimeAir=\''.$mdfterm.'\' WHERE ID='.$mdfid, ['x_id' => $mdfid]);

            hop($_SERVER['HTTP_REFERER'].'#tr'.$mdfid);
        }
    }


    if (isset($_GET['termdel'])) { // Delete fixed term

        $termdel = wash('int', $_GET['termdel']);

        if (TYP=='epg') {
            $sql = 'UPDATE epg_elements SET TimeAir=NULL, OnHold=NULL WHERE ID='.$termdel;
        } else { // SCNR
            $sql = 'UPDATE epg_scnr_fragments SET TimeAir=NULL WHERE ID='.$termdel;
        }

        qry($sql, ['x_id' => $termdel]);

        hop($_SERVER['HTTP_REFERER'].'#tr'.$termdel);
    }


    if (isset($_GET['termnow'])) {  // Set TERM of the selected element/fragment to NOW.

        // Term is 5 seconds in the past, in order to give some time to the master-editor
        $termnow_tstamp = strtotime('now -'.$cfg[SCTN]['termnow_delay'].' seconds');

        $termnow = date('Y-m-d H:i:s', $termnow_tstamp);


        // We will set valid TIME FRAME LIMITS for this operation,
        // because obviously this function should apply only for current (i.e. today's) epg-date

        // For epg: start of the day. For scnr: start of the prog
        $t_limit['down'] = (TYP=='epg') ? epg_zeroterm($x['EPG']['DateAir']) : $x['TimeAir'];

        // For both: end of the day, i.e. start of the next day
        $t_limit['up'] = epg_zeroterm(date('Y-m-d', strtotime($x['EPG']['DateAir'].' +1 day')));


        if ($termnow_tstamp >= strtotime($t_limit['down']) && $termnow_tstamp < strtotime($t_limit['up'])) {

            $termnow_id = wash('int', $_GET['termnow']); // ID of element/fragment

            qry('UPDATE '.((TYP=='epg') ? 'epg_elements' : 'epg_scnr_fragments').
                ' SET TimeAir=\''.$termnow.'\' WHERE ID='.$termnow_id, ['x_id' => $termnow_id]);

        } else { // Do not change anything, just show error notice

            omg_put('danger', $termnow.' - '.$tx[SCTN]['MSG']['term_faulty'].' ('.$t_limit['down'].' < x < '.$t_limit['up'].')');
        }

        hop($_SERVER['HTTP_REFERER'].((isset($termnow_id)) ? '#tr'.$termnow_id : ''));
    }

}



/*
 * ROLER MODAL rcv
 */
if (TYP=='scnr' && !empty($_GET['roler'])) {

    $post = @$_POST['role'];

    foreach ($x['CRW'] as $k => $v) {

        if ($v['CrewUID']==UZID) {

            if (isset($post[$v['CrewType']])) {

                $uid = UZID;
                unset($post[$v['CrewType']]);

            } else {

                $uid = 0;
            }

            $roles[$k] = ['CrewType' => $v['CrewType'], 'CrewUID' => $uid];

        } else {

            $roles[$k] = $v;
        }
    }

    if (!empty($post)) {
        foreach ($post as $k => $v) {
            $roles[] = ['CrewType' => $k, 'CrewUID' => UZID];
        }
    }

    $log = ['tbl_name' => 'epg_elements', 'x_id' => $x['ID']];

    if (!empty($x['CRW']) || !empty($roles)) {
        crw_receiver($x['NativeID'], $x['NativeType'], $x['CRW'], $log, $roles);
    }

    hop($_SERVER['HTTP_REFERER']);
}



/*
 * PMS & RCV for BC controls and SPICER controls
 */
if (in_array(VIEW_TYP, [8,9,10])) { // BCAST

    switch (VIEW_TYP) {

        case 8:     // mkt
        case 9:     // prm

            $spc_typ = (VIEW_TYP==8) ? 'mkt' : 'prm';

            define('PMS_BC', false);

            define('PMS_SPICER', PMS_FULL || pms('epg/'.$spc_typ, 'mdf', ['EPG_SCT' => $spc_typ]));

            define('SPICER_CLIPZ',
                rdr_cln('epg_clips', 'ID', 'CtgID='.(($spc_typ=='mkt') ? 1 : 2).' AND Placing IN (1,2,3)', null, -1));

            break;

        case 10:    // film
            define('PMS_BC', pms('epg/film', 'bcast_mdf'));
            break;
    }

    // RECEIVER for VERIFY_SINGLE case. (Receiver for VERIFY_ALL case is within epg_dtl_html())

    if (PMS_BC && isset($_GET['verif_phase']) && (!@$_GET['verif_all'])) {

        $verif['schtyp']    = intval(@$_GET['verif_schtyp']);      // 1 - epg, 2 - scn
        $verif['id']        = intval(@$_GET['verif_id']);          // Item ID
        $verif['term']      = intval(@$_GET['verif_term']);        // Item TimeAir
        $verif['phase']     = intval(@$_GET['verif_phase']);       // 1 - ok (has run), 2 - fail (has not run)

        epg_bcast_receiver($verif);
    }
}















/*************************** HEADER ****************************/


/*TITLE FOR SUBSECTION*/
$header_cfg['subscn'] = epg_get_subscn_title($x);

$header_cfg['ajax'] = true;

if (in_array(VIEW_TYP, [0,1,2,4,7,8,9,10])) {
    $footer_cfg['modal'][] = 'printer';
}

$footer_cfg['modal'][] = ['deleter'];


/*CSS*/

$header_cfg['css'][] = 'floater.css';
$header_cfg['css'][] = 'cover.css';
$header_cfg['css'][] = 'epg/epgscn.css';

$header_cfg['css'][] = 'print.css'; // The order of CSS files is important!
if ($_SESSION['BROWSER_TYPE']=='PRINT_1') {
    $header_cfg['css'][] = 'print_old_tweak.css';
}

if (SHOW_JS_CTRLZ) {

    if (!empty($_SESSION['SCNR_ATOM_COLLAPSE'])) {
        $header_cfg['js_onload'][] = 'atomtxt_switch_all(null)';
    }

    if (!empty($_SESSION['SCNR_CVR_COLLAPSE'])) {
        $header_cfg['js_onload'][] = 'display_switch_all(\'cvr_clps\',\'table-row\')';
    }

    if (!empty($_SESSION['SCNR_TEXTEND'])) {
        $header_cfg['js_onload'][] = 'display_switch_all(\'schlbl texter\',\'inline\')';
    }
}

$header_cfg['js'][] = 'epg/epg.js';
$header_cfg['js'][] = 'ajax/editdivable.js';
$footer_cfg['js_lines'][] = '$(\'[data-toggle="tooltip"]\').tooltip();';
$header_cfg['backer_reload'] = true;


if (TERM_EDITABLE || DUR_EDITABLE) {
    $header_cfg['css'][] = 'hmsedit.css';
    $header_cfg['js'][] = 'hms_editable.js';
}

if (VIEW_TYP==0) {
    $footer_cfg['js_lines'][] = help_output('popover', ['name' => 'epgterm', 'placement' => 'right']);
    $footer_cfg['js_lines'][] = help_output('popover', ['name' => 'epgdur', 'placement' => 'right']);
}

if (VIEW_TYP==1) {  // tree view
    $header_cfg['style'][] = 'tr.drop  {display: '.((VIEW_TOGGLE_ALLCOLLAPSE) ? 'table-row' : 'none').'}';
}

if (VIEW_TYP==7 && isset($_GET['print_cont'])) {  // prompter view
    $header_cfg['style'][] = 'div.prompter_print { page-break-after: avoid; }';
}

if (in_array(VIEW_TYP, [8,9,10]) && PMS_BC) { // BCAST
    $header_cfg['css'][] = 'epg/epg_bcast.css';
}

if (VIEW_TYP==9 || (VIEW_TYP==8 && EPG_MKTVIEW==2)) { // SPICER
    $header_cfg['js'][] = 'draggable.js';
    $header_cfg['js_onload'][] = 'spc_clipz_switch_init()';
    $header_cfg['alerter_msgz'] = ['spc_hidden_wrapclips' => $tx[SCTN]['MSG']['spc_hidden_wrapclips']];
}

if (VIEW_TYP==8) {  // mkt view

    $header_cfg['css'][] = 'epg/epg_spice.css';
    $header_cfg['js'][]  = 'epg/mktplan.js';

    if (EPG_MKTVIEW==0) {

        // Modal for LINKING SIBLINGS (MKTPLAN-MKTEPG)
        $footer_cfg['modal'][] = ['poster', ['onshow_js' => 'mkt_sibling_modal_poster_onshow', 'name_prefix' => 'mkt_sibling']];
    }

    if (EPG_MKTVIEW==1) {

        $mkt_positions = explode(',', $cfg[SCTN]['mktplan_positions']);

        // for MKTPLAN QUEUE
        $header_cfg['js'][] = 'draggable.js';
        $header_cfg['ajax'] = true;

        // for MKTPLANITEM DEL
        $footer_cfg['modal'][] = ['deleter', ['onshow_js' => 'modal_del_onshow']];

        // for MKTPLANITEM MDF
        $footer_cfg['modal'][] = ['poster', ['onshow_js' => 'mktplan_modal_poster_onshow', 'name_prefix' => 'mdfplan']];
        $header_cfg['js_lines'][]  = 'var g_mktplan_zero_pos = '.array_search(0, $mkt_positions).';';
        $header_cfg['bs_daterange'] = ['single' => true, 'submit' => false, 'name' => 'DateEPG'];
    }
}

if (VIEW_TYP==2) {  // CVR view

    if (!VIEW_TOGGLE_ALLCOLLAPSE) {
        $header_cfg['style'][] = 'div.cover textarea { display: none; }';
    }

    $header_cfg['bsgrid_typ'] = 'epg-cg';

    $header_cfg['js'][] = 'alphconv.js';
    $header_cfg['js'][] = 'ifrm.js';

    $header_cfg['js_onload'][] = 'textarea_height()';
    // cvrz are displayed in textarea controls because of bug in cg software which doesn't properly paste text from DIVs
    // this js is to adjust height for each cvr textarea

    $header_cfg['js_onload'][] = 'cvr_phzfilter_init()';

    $footer_cfg['modal'][] = ['deleter', ['onshow_js' => 'modal_del_onshow']];
}

if (VIEW_TYP==3) {  // studio view

    $header_cfg['js'][] = 'epg/studio.js';

    foreach ($speakerz as $k => $v) {
        $header_cfg['style'][] = '.spkr'.$k.' { background-color: #'.$v['color'].'; }';
    }

    $footer_cfg['js_lines'][] = '

        $(\'.speaker\').popover({
            "container": "body",
            "trigger": "focus",
            "placement": "bottom",
            "html": "true"
        });
    ';

    $header_cfg['alerter_msgz'] = [
        'pms_fail' =>$tx['MSG']['pms_fail'], // Textarea mdf story
    ];
}

if (TYP=='scnr' && in_array(VIEW_TYP, [0,1,3])) {  // list, tree, studio

    $header_cfg['js'][] = 'epg/phz.js';

    // Story phase incrementing
    $header_cfg['js_onload'][] = 'phzclick_init()';
    $header_cfg['js_lines'][] = '
        var phz_clrarr = [\''.implode('\', \'', cfg_global('arrz','dsk_phase_clr')).'\'];
        var phz_ttlarr = [\''.implode('\', \'', txarr('arrays', 'dsk_nwz_phases')).'\'];
    ';

    // Story phase uptodater
    $header_cfg['js_onload'][] = 'phzuptd_init()';
    $header_cfg['js_lines'][] = 'var phzuptd_ajax_cnt = '.($cfg[SCTN]['phzuptd_ajax_cnt']*1000).';';

    $header_cfg['alerter_msgz'] = [
        'item_fail' =>$tx['MSG']['item_fail'],
        'phz_err_top' =>$tx[SCTN]['MSG']['phz_err_top'], // Story phase incrementing
        'phz_err_pms' =>$tx[SCTN]['MSG']['phz_err_pms'],
    ];
}

if (TYP=='scnr' && in_array(VIEW_TYP, [0,1]) // scnr: list, tree + no ctrlz
    && empty($_GET['mdftips']) && empty($_GET['mdfcam']) && empty($_GET['mdfvo'])) {

    $footer_cfg['js_lines'][] = 'document.addEventListener("keydown", scnr_emph);';
    $header_cfg['js_lines'][] = 'var g_now_row = 0;';
    $header_cfg['js_onload'][] = 'scnr_emph_init()';
}

if (in_array(VIEW_TYP, [3,4,7])) {  // studio, web, prompter
    $footer_cfg['js_lines'][] = help_output('popover');
}

if (VIEW_TYP==11) { // exporter
    $header_cfg['js'][] = 'klikle.js';
    $header_cfg['js_onload'][] = 'klk_page_eventz()';
}

if (in_array(VIEW_TYP, [0,1,2,9])) {

    /* bs_multiselect */
    $header_cfg['css'][] = '/_js/bs_multiselect/bootstrap-multiselect.css';
    $header_cfg['js_inc'][] = '/_js/bs_multiselect/init.php';
    $header_cfg['js_lines'][] = 'var g_line_filter_name = "linefilter_'.TYP.VIEW_TYP.'";';
}


if (in_array(VIEW_TYP, [0,1])) {  // list, tree

    // Do AJAX UP-TO-DATE procedure only if this is regular schedule (i.e. not template), and the table is not empty,
    // and if it is in LIST or TREE view and if it is present or future date

    $header_cfg['ajax_uptodate'] = (!TMPL && !SCH_BLANK);

    if ($header_cfg['ajax_uptodate']) {

        $epg_datereal = get_real_dateair();

        if (strtotime($epg_datereal) > strtotime($x['EPG']['DateAir'])) { // only present date and future dates

            $header_cfg['ajax_uptodate'] = false;
        }
    }

    if (isset($_GET['ajax_uptodate'])) {
        $_SESSION['ajax_uptodate'] = intval($_GET['ajax_uptodate']);
    }

    if ($header_cfg['ajax_uptodate'] && @$_SESSION['ajax_uptodate']) {

        $header_cfg['js_lines'][] = '
        var epguptd_arrorig = [[], []];
        var epguptd_arrcur = [[], []];

        var epg_type = \''.TYP.'\';
        var epguptd_ajax_url = \'_ajax/_aj_epg.php\';
        var epguptd_ajax_data = \'typ='.TYP.'&id='.((TYP=='epg') ? EPGID : SCNRID).'\';

        var epguptd_ajax_cnt = '.($cfg[SCTN]['epguptd_ajax_cnt']*1000).';
        var epguptd_warney_cnt = '.$cfg[SCTN]['epguptd_warney_cnt'].';
        ';

        // UPTODATE procedure is both for epg and scnr, EMPHASIZING procedure is for epg only (and only for present day)

        $header_cfg['js_onload'][] = 'epguptd_init()';

        if (TYP=='epg' && $x['EPG']['DateAir']==$epg_datereal) {
            $header_cfg['js_onload'][] = 'epg_emph(0)';
        }
    }
}

if (SHOW_CALENDAR) {

    require '../../__fn/fn_list.php';
    $header_cfg['css'][] = 'list.css';

    $header_cfg['calendar'] = calendar_data();
}


require '../../__inc/_1header.php';
/***************************************************************/





if (SHOW_CALENDAR) {
    echo '<div class="row">'.
            '<div class="well well-sm clndar">'.
                '<span class="glyphicon glyphicon-calendar"></span>'.
                '<div id="clndar" class="text-center"></div>'.
            '</div>'.
        '</div>';
}








// For print version of EPG/SCNR
if (!TMPL && VIEW_TYP!=7) {

    echo '<div class="row">';

    if (TYP=='scnr') {
        echo '<div class="visible-print-block smallprint pull-right">'.$datecpt['date'].' ('.$datecpt['wday'].')'.'</div>';
    }

    echo '<div class="visible-print-block smallprint timeprint'.((TYP=='epg') ? ' pull-right' : '').'">('.date('H:i').')</div>';

    echo '</div>';
}




/* CRUMBS BAR */

if (TYP=='scnr') {

    crumbs_output('open');

    if (!TMPL ) {

        crumbs_output('item', '<span class="label label-primary channel">'.
            channelz(['id' => $x['EPG']['ChannelID']], true).'</span> ');

        crumbs_output('item', $tx['NAV'][7]);
        crumbs_output('item', $nav_subz[$header_cfg['subscn']]['ttl'], $nav_subz[$header_cfg['subscn']]['url']);

        crumbs_output(
            'item',
            $datecpt['date'].' ('.$datecpt['wday'].')',
            'epg.php?typ=epg&id='.EPGID.'&view='.((VIEW_TYP==2) ? '2' : '0').'#tr'.$x['ID']
        );

        if (in_array(VIEW_TYP, [0,1,2])) {      // NEXT/PREV scnr

            $sql = 'SELECT ID FROM epg_elements WHERE EpgID='.$x['EpgID'].' AND NativeType IN (1,12,13) AND Queue>'.$x['Queue'].
                ' ORDER BY Queue ASC LIMIT 1';

            $r['next'] = qry_numer_var($sql);

            $sql = 'SELECT ID FROM epg_elements WHERE EpgID='.$x['EpgID'].' AND NativeType IN (1,12,13) AND Queue<'.$x['Queue'].
                ' ORDER BY Queue DESC LIMIT 1';

            $r['prev'] = qry_numer_var($sql);

            crumbs_output(
                'item',
                '<span class="glyphicon glyphicon-triangle-right"></span>',
                ($r['next'] ? 'epg.php?typ=scnr&id='.$r['next'].'&view='.VIEW_TYP : ''),
                'pull-right righty pale'.($r['next'] ? '' : ' disabled')
            );
            crumbs_output(
                'item',
                '<span class="glyphicon glyphicon-triangle-left"></span>',
                ($r['prev'] ? 'epg.php?typ=scnr&id='.$r['prev'].'&view='.VIEW_TYP : ''),
                'pull-right righty pale'.($r['prev'] ? '' : ' disabled')
            );
        }

        if (VIEW_TYP==3) {      // FLW button + helper (only in STUDIO view)

            $x['FlwID'] = flw_checker($x['NativeID'], $x['NativeType']);

            crumbs_output('item', flw_output(($x['FlwID']), $x['NativeID'], $x['NativeType']), '', 'pull-right righty');

            help_output('button', ['content' => txarr('blocks', 'help_studio')]);
        }

        if (VIEW_TYP==7) {
            help_output('button', ['content' => txarr('blocks', 'help_prompter')]);
        }

    } else { // TMPL

        crumbs_output('item', $tx['NAV'][7]);
        crumbs_output('item', $nav_subz[$header_cfg['subscn']]['ttl']);
        crumbs_output('item', $tx['LBL']['rundown'], 'epgs.php?typ=tmpl&tmpltyp=scn');
    }

    crumbs_output('close');
}






/* CONTROL BAR, which contains:
 * - CLOCK showing CURRENT time
 * - VIEW buttons
 * - FILTER CBO
 * - CONTROL buttons
 */

if (!TMPL) {


    echo '<div class="btnbar row">';


    // CLOCK showing CURRENT time

    if (isset($show_epg_clock)) {
        echo '<div class="btn-group" id="epg_now"></div>';
    }


    // VIEWTYP buttons

    echo '<div class="btn-group btn-group-sm text-uppercase viewtyp">';

    $dis = (!EPGID) ? ' disabled' : '';

    foreach ($view_arr as $k => $v) {

        /*
        if (in_array($k, [8,9,10]) && $k!=VIEW_TYP) { // For bcast (prm, mkt, film) we show button only when active/selected
            continue;
        }*/

        $href = 'epg.php?typ='.TYP.
            '&id='.((TYP=='epg') ? EPGID : $x['ID']).
            '&view='.$k;

        $n = strpos($v, ' <span'); // tooltip for viewtyp buttons which have bs-glyph as caption
        if ($n) {
            $tooltip = ' title="'.substr($v, 0, $n).'"';
            $v = substr($v, $n+1);
        } else {
            $tooltip = '';
        }

        if ((VIEW_TYP==$k) && (in_array(VIEW_TYP, [1,2,3]))) {
            $href .= '&collapse='.(1-VIEW_TOGGLE_ALLCOLLAPSE);
            $v = '<span class="glyphicon glyphicon-resize-'.(VIEW_TOGGLE_ALLCOLLAPSE ? 'full' : 'small').'"></span>&nbsp;'.$v;
        }

        $act = (VIEW_TYP==$k) ? ' active' : '';

        echo '<a type="button" class="btn btn-default'.$act.$dis.'" href="'.$href.'"'.$tooltip.'>'.
            $v.'</a>';
    }

    echo '</div>';


    // FILTER CBO

    if (!SCH_BLANK && in_array(VIEW_TYP, [0,1,2,9])) {

        if (VIEW_TYP==2) { // CG

            $arr_filter = txarr('arrays', 'epg_cover_types');

        } elseif (VIEW_TYP==9) { // Promo

            $arr_filter = txarr('arrays', 'epg_prm_ctgz');

        } else { // (0,1) list, tree

            if (TYP=='epg' && setz_get('epg_list_filter_teams')) {
                $arr_filter = rdr_cln('prgm_teams', 'Caption', 'ID>99 AND ChannelID='.$x['EPG']['ChannelID'], 'Queue, ID ASC');
            } else {
                $arr_filter = array_intersect_key(txarr('arrays', 'epg_line_types'), array_flip($linetypz));
            }
        }

        echo
        '<select id="LineFilter" name="LineFilter[]" multiple="multiple" '.
            'class="btn btn-sm btn-default" style="display: none">'.
            arr2mnu($arr_filter, @$_POST['LineFilter']).
        '</select>';
    }


    // CVR controls

    if (VIEW_TYP==2) {

        // CVR phase filter

        echo '<div id="cvr_phz_filter" class="btn-group btn-group-sm" data-toggle="buttons">';

        $cvr_phz_arr = ['ready ready_not', 'ready ready_not cvr_proofed', 'ready'];

        foreach ($cvr_phz_arr as $k => $v) {

            echo '<label id="cvr_phz_filter'.$k.'" class="btn btn-default active" '.
                'onclick="display_switch_all(\'phz'.$k.'\',\'block\'); '.
                'sessionStorage.setItem(\'cvr_phz'.$k.'\', (1-sessionStorage.getItem(\'cvr_phz'.$k.'\')));'.
                'return false;">'.
                '<input type="checkbox" autocomplete="off" checked>'.
                '<span class="glyphicon glyphicon-ok '.$v.'"></span></label>';
        }

        echo '</div>';


        // EPG CVR display cbo

        if (TYP=='epg') {

            define('EPG_CVR_CBO', setz_get('epg_cvr_cbo'));

            echo '<select class="btn btn-sm btn-default" id="epg_cvr_cbo" onChange="cbo_submit(this)">'.
                arr2mnu(txarr('arrays', 'epg_cvr_displays'), EPG_CVR_CBO).'</select>';
        }
    }



    // CONTROL buttons

    echo '<div class="pull-right btn-toolbar text-uppercase">';

    if (TYP=='epg' && VIEW_TYP==0) {

        pms_btn( // BTN: List live progs only
            true, ((CHNLTYP==1) ? txarr('arrays', 'epg_mattyp_signs', 1) : $cfg[SCTN]['epg_radio_live_sign']),
            [   'href' => '?'.$_SERVER["QUERY_STRING"].'&epg_list_short='.(1-EPG_LIST_SHORT),
                'title' => $tx[SCTN]['LBL']['epg_list_short'],
                'class' => 'btn btn-default btn-sm js_starter'.((EPG_LIST_SHORT) ? ' on' : '')
            ]
        );

        pms_btn( // BTN: List progs and films only
            true, '<span class="glyphicon glyphicon-list"></span>',
            [   'href' => '?'.$_SERVER["QUERY_STRING"].'&epg_list_progs='.(1-EPG_LIST_PROGS),
                'title' => $tx[SCTN]['LBL']['epg_list_progs'],
                'class' => 'btn btn-default btn-sm js_starter'.((EPG_LIST_PROGS) ? ' on' : '')
            ]
        );
    }

    if (VIEW_TYP==9 || (VIEW_TYP==8 && EPG_MKTVIEW==2)) { // SPICER WRAPCLIPS

        pms_btn( // BTN: SWITCH Opening/Closing CLIPS
            true, '<span class="glyphicon glyphicon-resize-small"></span>',
            [   'href' => '#',
                'onclick' => 'spc_clipz_switch(this);return false;',
                'id' => 'spc_clipz_btn',
                'class' => 'btn btn-default btn-sm js_starter',
                'title' => $tx[SCTN]['LBL']['wrapclips'].': '.$tx[SCTN]['LBL']['show_hide']
            ]
        );
    }

    if (VIEW_TYP==4) { // web

        help_output('button', ['content' => txarr('blocks', 'help_epgweb')]);

        echo '<div class="btn-group" style="margin-right: 10px">';

        pms_btn( // BTN: SHOW XML
            true, 'XML',
            [   'href' => 'epg.php?typ=epg&id='.EPGID.'&view=4&downer=1&output_type=text',
                'class' => 'btn btn-default btn-sm btn-grey'
            ]
        );

        pms_btn( // BTN: DOWNLOAD XML
            true, '<span class="glyphicon glyphicon-save"></span>',
            [   'href' => 'epg.php?typ=epg&id='.EPGID.'&view=4&downer=1&output_type=xml',
                'class' => 'btn btn-default btn-sm btn-grey'
            ]
        );

        echo '</div>';
    }

    if (VIEW_TYP==7) { // prompter
        pms_btn( // BTN: PRINT_CONTINUOUS
            true, '<span class="glyphicon glyphicon-list"></span>',
            [   'href' => 'epg.php?typ=scnr&id='.$x['ID'].'&view=7'.(isset($_GET['print_cont']) ? '' : '&print_cont=1'),
                'title' => $tx[SCTN]['MSG']['print_continuous'],
                'class' => 'btn btn-default btn-sm text-uppercase js_starter'.(isset($_GET['print_cont']) ? ' on' : '')
            ]
        );
    }

    if (in_array(VIEW_TYP, [0,1,2,4,7,9,10]) || (VIEW_TYP==8 && MKTPLAN_TWINVIEW_TYP==0)) {
        modal_output('button', 'printer');
    }

    if (VIEW_TYP==8) { // mkt

        echo '<div class="btn-group btn-group-sm text-uppercase pull-right">';

        $dis = (!EPGID) ? ' disabled' : '';

        $href = 'epg.php?typ=epg&view=8&id='.EPGID;

        echo '<a type="button" class="btn btn-default'.((EPG_MKTVIEW==1) ? ' active' : '').$dis.'" '.
            'href="'.$href.'&epg_mktview=1'.'">'.$tx[SCTN]['LBL']['mkt_plan'].'</a>';

        echo '<a type="button" class="btn btn-default'.((EPG_MKTVIEW==0) ? ' active' : '').$dis.'" '.
            'href="'.$href.'&epg_mktview=0'.'">'.
            $tx[SCTN]['LBL']['mkt_plan'].' : '.$tx[SCTN]['LBL']['mkt_epg'].'</a>';

        echo '<a type="button" class="btn btn-default'.((EPG_MKTVIEW==2) ? ' active' : '').$dis.'" '.
            'href="'.$href.'&epg_mktview=2'.'">'.$tx[SCTN]['LBL']['mkt_epg'].'</a>';

        echo '</div>';
    }

    if (TYP=='scnr' && $x['NativeType']==1 && in_array(VIEW_TYP, [0,1])) { // ROLER MODAL BTN
        modal_output('button', 'poster',
            [
                'name_prefix' => 'roler',
                'pms' => true,
                'button_txt' => '<span class="glyphicon glyphicon-user"></span>',
                'button_css' => 'btn-default js_starter btn-sm',
            ]
        );
    }

    if (TYP=='scnr' && in_array(VIEW_TYP, [0,1])) {
        pms_btn( // BTN: MODIFY DESCRIPTION, i.e. modify SCN properties
            PMS_SCNR_DSC, '<span class="glyphicon glyphicon-cog mdf"></span>'.$tx['LBL']['program'],
            [   'href' => 'epg_modify_single.php?epg='.EPGID.'&id='.$x['ID'].'&ref=scn&view='.VIEW_TYP,
                'class' => 'btn btn-info btn-sm'    ]
        );
    }

    if (in_array(VIEW_TYP, [0,1])) {

        pms_btn( // BTN: MODIFY LIST (MULTI)
            PMS_FULL, '<span class="glyphicon glyphicon-cog mdf"></span>'.$tx['LBL']['content'],
            [   'href' => 'epg_modify_multi.php?typ='.TYP.'&id='.((TYP=='epg' ? EPGID : $x['ID'])),
                'class' => 'btn btn-info btn-sm'    ]
        );

        if (TYP=='scnr') {

            pms_btn( // BTN: IMPORT fragments from existing scn
                (PMS_FULL || pms('dsk/stry', 'new')),
                '<span class="glyphicon glyphicon-import"></span>',
                [   'href' => 'epg_mover.php?act=import&scnelmid='.$x['ID'],
                    'title' => $tx['LBL']['import'],
                    'class' => 'btn btn-info btn-sm'    ]
            );

            if (SCH_BLANK) {

                $prog_tmpl_id = ($x['PRG']['ProgID']) ? $x['PRG']['SETZ']['EPG_TemplateID'] : 0;

                $opt['btn_group_css'] = 'btn-group-sm pull-right';
                $opt['btn_type'] = 'btn-primary';

                $opt['btn_href'] = (($prog_tmpl_id) ? 'epg.php?typ=scnr&id='.$x['ID'].'&scnr_tmpl_dflt=1' : '');
                // If default tmpl dsnt exist, disable just the button, not the caret part. (Empty href will disable)

                $opt['btn_txt'] = '<span class="glyphicon glyphicon-thumbs-up"></span>&nbsp;'.
                                    $tx[SCTN]['LBL']['template'];

                $opt['ul_html'] = '<li><a href="epg_new.php?fromtyp=tmpl&scnelmid='.$x['ID'].'">'.
                                    $tx[SCTN]['LBL']['chose_template'].'</a></li>';

                pms_btngroup(PMS_FULL, $opt); // IMPORT TEMPLATE (DEFAULT / FROM LIST)
            }
        }
    }

    if (VIEW_TYP==2) { // cvr

        $ownertyp = (TYP=='scnr') ? 1 : 4; // ELEMENT-LEVEL OR EPG-LEVEL

        pms_btn( // BTN: Add new CVR on zero-level
            pms('dsk/cvr', 'new',
                ['OwnerID' => ((TYP=='scnr') ? $x['NativeID'] : $x['EPG']['ID']), 'OwnerType' => $ownertyp]
            ),
            '<span class="glyphicon glyphicon-plus-sign new"></span>'.$tx['LBL']['cvr'],
            [   'href' => '#',
                'onclick' => 'ifrm_starter(\'cvr_epg\', this, \'/desk/cvr_modify.php?'.'owner_typ='.$ownertyp
                    .'&owner_id='.((TYP=='scnr') ? $x['ID'] : $x['EPG']['ID']).'&ifrm=1\');return false;',
                'class' => 'btn btn-success btn-sm text-uppercase satrt opcty3 btn_panel_head pull-right'   ]
        );
    }

    if (VIEW_TYP==11) { // exporter

        echo '<div class="btn-group">';

        pms_btn( // BTN: SHOW XML
            true, 'XML',
            [   'href' => 'javascript:epgexp_submit(1)',
                'class' => 'btn btn-default btn-sm btn-grey'
            ]
        );

        pms_btn( // BTN: DOWNLOAD XML
            true, '<span class="glyphicon glyphicon-save"></span>',
            [   'href' => 'javascript:epgexp_submit(2)',
                'class' => 'btn btn-default btn-sm btn-grey'
            ]
        );

        echo '</div>';
    }

    echo '</div>';



    echo '</div>';
}













/* HEADER BAR */

if (TYP=='epg') {


    echo '<div class="well well-sm headbar row">';

    if (!TMPL) { // EPG SCHEDULE

        /* HEADER BAR contains:
         * - WEEKDAY / DATE (+ CHANNEL) cell
         * - PREV & NEXT WEEKDAY links (except for TRASH view)
         */

        echo
        '<div>'.
            '<h2>'.
                '<span class="label label-primary channel">'.channelz(['id' => $x['EPG']['ChannelID']], true).'</span> '.
                '<span class="glyphicon glyphicon-ok ready epg_ready'.
                    (($x['EPG']['IsReady']) ? '' : ' ready_not').'"></span>'.
                $datecpt['TDAY']['wday'].
                ' <small>/'.$datecpt['TDAY']['date'].'/</small>'.
            '</h2>'.
        '</div>';

        if (VIEW_TYP==8) {

            $dtr_chnl = (isset($_GET['dtr_chnl'])) ? intval($_GET['dtr_chnl']) : $x['EPG']['ChannelID'];

            ytdtmr_html($datecpt, 'epg.php?typ=epg&view=8&dtr_chnl='.$dtr_chnl.'&dtr=', 'ymd');

        } elseif (VIEW_TYP!=5) {

            ytdtmr_html($datecpt, 'epg.php?typ='.TYP.'&view='.VIEW_TYP.'&id=');
        }

    } else { // EPG TMPL

        /* HEADER BAR contains:
         * - CAPTION cell
         * - CONTROL button (MODIFY this template)
         */

        echo '<div><h2>'.$x['EPG']['Caption'].' <small>/'.$tx[SCTN]['LBL']['template'].'/</small></h2></div>';

        echo '<div class="pull-right hidden-print">';

        pms_btn(    // BTN: MODIFY this template
            pms('epg', 'tmpl_epg', $x),
            '<span class="glyphicon glyphicon-cog mdf"></span>'.$tx['LBL']['modify'],
            [   'href' => 'epg_modify_multi.php?typ='.TYP.'&id='.EPGID,
                'class' => 'btn btn-info btn-sm text-uppercase'    ]
        );

        echo '</div>';
    }

    echo '</div>';


} else { // scnr


    echo '<div class="row">';

    if (!TMPL) { // SCNR SCHEDULE


        /* CAPTION STRING */

        $cpt_html = '<div class="well well-sm headbar'.((VIEW_TYP==7) ? ' hidden-print' : '').'" '.
            'style="position: relative;"><div><h2>';

        if (!$x['PRG']['IsFilm']) { // PROG

            // IsReady label

            $href = 'epg.php?typ=scnr&id='.$x['ID'].'&scnr_ready=1';

            $cpt_html .=
                '<a class="ready'.((!$x['PRG']['IsReady'] && PMS_SCNR_DSC) ? '' : ' disabled').'" href="'.$href.'">'.
                    '<span class="glyphicon glyphicon-ok ready'.(($x['PRG']['IsReady']) ? '' : ' ready_not').'"></span>'.
                '</a>';

            // CAPTION

            if ($x['PRG']['ProgID']) {

                $href = '/desk/prgm_details.php?id='.$x['PRG']['ProgID'];

                $cpt_html .= '<a class="head_src" href="'.$href.'">'.$x['PRG']['ProgCPT'].'</a>';

            } else {

                $cpt_html .= $x['PRG']['ProgCPT'];
            }

        } else { // FILM: wrap caption into LINK to film details page, and add FILM label

            // FILM label

            $cpt_html .= '<span class="lblfilm">'.txarr('arrays', 'film_types', (($x['NativeType']==12) ? 1 : 3)).'</span>';
            // We display only "film"(1) and "serial"(3) labels. For *mini-serials*(2) the label is same as for serials.

            // CAPTION

            $cpt_html .= '<a class="head_src" href="film_details.php?typ=item&id='.
                ((!$x['FILM']['FilmParentID']) ? $x['FILM']['FilmID'] : $x['FILM']['FilmParentID']).'">'.
                film_caption($x['FILM'], ['typ' => $x['NativeType']]).
                '</a>';
        }

        // MAT-TYPE label
        if ($x['PRG']['MatType']!=2) { // for RECORDED(2) program we don't display label. We do for LIVE(1) and RERUN(3).
            $cpt_html .= ' <span class="lbl lblprog'.$x['PRG']['MatType'].'">'.$epg_mattyp_signs[$x['PRG']['MatType']].'</span>';
        }

        // For progs we add TERM in HH:MM format, because the same program can be repeated in different terms (e.g.NEWS)
        if (!$x['PRG']['IsFilm']) {
            $cpt_html .= '<small> /'.term2timehm($x['TimeAir']).'/</small>';
        }

        // WebLIVE and WebVOD signs
        if (VIEW_TYP==0) {
            $cpt_html .= '<span class="glyphicon glyphicon-play-circle lblweb weblive'.intval($x['WebLIVE']).'"></span>'.
                '<span class="glyphicon glyphicon-download lblweb webvod'.intval($x['WebVOD']).'"></span>';
        }

        // SUBCAPTION
        if (!$x['PRG']['IsFilm']) {
            if ($x['PRG']['Caption']) {
                $cpt_html .= '<small> '.$x['PRG']['Caption'].'</small>';
            }
        }

        $cpt_html .= '</h2></div>';

        $cpt_html .= '</div>';


        /* HEADER BAR contains:
        * - CLOCKS (2rows x 3cells)
        * - CAPTION cell
        * - DATE (2rows x 1cell)
        */

        echo '
        <table style="width:100%" class="scnheadtbl">
            <tr>
                <td class="timer term '.((isset($css_tbox['term'])) ? implode(' ', $css_tbox['term']) : '').'">'.
                    date('H:i:s', strtotime($x['TimeAir'])).'</td>
                <td class="timer dur '.$x['_Dur']['winner']['typ'].'">'.$x['_Dur']['winner']['dur'].'</td>
                <td class="timer finito">'.date('H:i:s', strtotime($x['_TimeFinito'])).'</td>
                <td rowspan="2" class="cpt" '.($x['PRG']['IsFilm'] ? 'style="background-color:#fcecfc"' : '').'>'.
                    $cpt_html.'</td>
            </tr>
            <tr>
                <td class="timer" id="epg_prg_start"></td>
                <td class="timer dur '.$x['_Dur']['loser']['typ'].'">'.$x['_Dur']['loser']['dur'].'</td>
                <td class="timer" id="epg_prg_end"></td>
            </tr>
        </table>
        ';

    } else { // SCNR TMPL

        /* HEADER BAR contains:
         * - CAPTION cell
         * - TMPL IsDefault (label or button)
         * - CONTROL button (MODIFY this template)
         */

        echo '<div class="well well-sm headbar">';

        echo '<div><h2>'.$x['PRG']['ProgCPT'].' ('.$x['PRG']['Caption'].')'.
            ' <small>/'.$tx[SCTN]['LBL']['template'].'/</small></h2></div>';

        echo '<div class="pull-right hidden-print btn-toolbar">';

        if ($x['TMPL']['IsDefault']) {  // LBL: TMPL Default

            echo '<span class="glyphicon glyphicon-thumbs-up" title="'.$tx['LBL']['default'].'"'.
                ' style="margin-right:15px; font-size:130%;"></span>';

        } elseif ($x['PRG']['ProgID']) {

            pms_btn(   // BTN: TMPL Default
                PMS_TMPL_SCNR, '<span class="glyphicon glyphicon-thumbs-up"></span>',
                [   'href' => 'epg.php?typ=scnr&id='.$x['ID'].'&tmpl_dflt=1',
                    'style' => 'margin-right:10px;',
                    'class' => 'btn btn-default btn-sm'    ]
            );
        }

        pms_btn(   // BTN: MODIFY this template
            PMS_TMPL_SCNR, '<span class="glyphicon glyphicon-cog mdf"></span>'.$tx['LBL']['modify'],
            [   'href' => 'epg_modify_multi.php?typ='.TYP.'&id='.$x['ID'],
                'class' => 'btn btn-info btn-sm text-uppercase'    ]
        );

        echo '</div>';

        echo '</div>';
    }


    echo '</div>';
}








/* STUDIO SPEAKERS & EDITORS BAR */

if (VIEW_TYP==3 && ($speakerz || $editors)) {

    echo
        '<div class="row btnbar studio">'.
            '<span class="pull-right">'.speaker_box($speakerz, 'speaker_rs', ['scnrid' => SCNRID]).'</span>'.
            '<span>'.speaker_box($editors, 'editor').'</span>'.
        '</div>';
}




/* CVR IFRM (for adding *new* cvr) + ZERO-LEVEL CVRZ */
// Note: Even BLANK schedule can have CVRZ! This is why we have to put this here, before schedule table
// (because it is skiped for BLANK schedule)

if (VIEW_TYP==2) {

    $owner_typ = (TYP=='epg') ? 4 : 1;
    $owner_id = (TYP=='epg') ? EPGID : $x['ID'];

    $cvr_cnt = cnt_sql('epg_coverz', 'OwnerType='.$owner_typ.' AND OwnerID='.$owner_id);

    echo '<div class="row">';

    if ($cvr_cnt) {
        echo '<div style="height:20px;"></div>';
    } else {
        if ($owner_typ==1) {
            echo '<div style="height:12px;"></div>';
        }
    }

    ctrl_ifrm('multi_div');

    echo '<div id="wraper"></div>'; // WRAPER div (placeholder for IFRM), for ifrm JS

    echo '</div>';

    if ($cvr_cnt) {

        echo '<div class="row">';

        if (TYP=='epg') {
            if (EPG_CVR_CBO!=2) epg_cvr_html_zerolevel(EPGID, 4);
        } else {
            epg_cvr_html_zerolevel($x['ID'], 1);
        }

        echo '</div>';
    }
}




// CRW for print version of SCNR

if (TYP=='scnr' && !TMPL && !$x['PRG']['IsFilm'] && in_array(VIEW_TYP, [0,1])) {

    echo '<div class="row visible-print-block">';
    crw_output('dtl_print', $x['CRW'], [12,13,1,2,3]);
    echo '</div>';
}



if (VIEW_TYP==8 && EPG_MKTVIEW!=2) {

    if (PMS_MKT_SYNC) form_tag_output('open', '?typ=epg&view=8&id='.EPGID, false);

    mktplan_epg_list($x['EPG']);

    if (PMS_MKT_SYNC) form_tag_output('close');
}



// Here we decide whether to show table or not

$show_schedule = false;

if (!SCH_BLANK) {
    $show_schedule = true; // If the schedule is not blank, we will show table
}

if (SCH_BLANK && TYP=='scnr' && VIEW_TYP==0) {
    $show_schedule = true; // We want to show *squizrow* for SCNR even if schedule is blank
}

if (!$show_schedule && TYP=='scnr' && VIEW_TYP!=5) {

    // If it is FILM then there will certainly be MOS line, so we should show table even if the schedule is empty
    if (in_array($x['NativeType'], [12,13])) {
        $show_schedule = true;
    }

    // Also, if it is PROG (except LIVEAIR type) then there will certainly be MOS line, so we should show table
    if ($x['NativeType']==1 && $x['PRG']['MatType']!=1) {
        $show_schedule = true;
    }
}





/* SCHEDULE TABLE */

// Here we display the schedule, i.e. call the functions for schedule assemble and output (THE MAIN THING)

if ($show_schedule) {

    echo '<div class="row">';

    $table_tag_skip = (in_array(VIEW_TYP, [2,6,7,11])) ? true : false;

    if (!$table_tag_skip) {

        $viewtyp_arr = [0 => 'list', 1 => 'tree', 3 => 'studio', 4 => 'web', 5 => 'recycle',
            8 => 'spicer', 9 => 'spicer', 10 => 'film'];

        $tmp_css = ' '.$viewtyp_arr[VIEW_TYP];

        if (TYP=='scnr' && VIEW_TYP==0 && $x['PRG']['MatType']!=1) {
            $tmp_css = ' record';
        }

        if (TYP=='epg' && VIEW_TYP==0 && (EPG_LIST_SHORT || EPG_LIST_PROGS)) {
            $tmp_css = ' list_shorty';
        }

        echo '<table id="epg_table" class="schtable'.$tmp_css.' '.TYP.'"><tbody>';
    }


    if (TYP=='epg') {

        switch (VIEW_TYP) {

            case 0:
            case 1:
            case 5:

                if (VIEW_TYP==0 && (EPG_LIST_SHORT || EPG_LIST_PROGS)) {
                    epg_list_short(EPGID, (EPG_LIST_SHORT ? 'live_only' : 'progs_films'));
                } else {
                    $col_sum_noghost = epg_dtl_html(EPGID, 'epg', 'normal');
                }
                break;

            case 2:
                if (EPG_CVR_CBO!=1) epg_cvr_html(EPGID, TYP);
                break;

            case 4:
                epg_web_html(EPGID);
                break;

            case 6:
                epg_recs_html(EPGID, 'epg');
                break;

            case 8:
                if (EPG_MKTVIEW==2) {
                    epg_dtl_html(EPGID, 'epg', 'bcasts');
                }
                break;

            case 9:
            case 10:
                epg_dtl_html(EPGID, 'epg', 'bcasts');
                break;

            case 11:
                epg_exp_html('epg', EPGID, $sch_data);
                break;

            default:
                exit; // Error-catcher: this should never happen
        }

    } else { // scn

        switch (VIEW_TYP) {

            case 0:
            case 1:
            case 3:
            case 5:
                $col_sum_noghost = epg_dtl_html(SCNRID, 'scnr', ((VIEW_TYP!=3) ? 'normal' : 'normal_studio'),
                    ['zero_term' => $x['TimeAir'], 'parent_dur' => $x['_Dur']['winner']['dur'], 'epgfilmid' => @$x['FILM']['ID']]
                );
                break;

            case 2:
                epg_cvr_html(SCNRID, TYP);
                break;

            case 6:
                epg_recs_html(SCNRID, TYP);
                break;

            case 7:
                epg_prompter_html(SCNRID, TYP);
                break;

            default:
                exit; // Error-catcher: this should never happen
        }

    }


    if (in_array(VIEW_TYP, [0,1]) && !EPG_LIST_SHORT && !EPG_LIST_PROGS) {

        // In LIST and TREE view, we will have "squizrow", i.e. bar with buttons for adding/inserting new element

        // This is code for row which will be used for cloning. This row is invisible (display:none).
        // This row will be the last one in the schedule table.

        epg_squizrow_output('epg_details',
            ['col_sum_noghost' => $col_sum_noghost, 'parent_id' => ((TYP=='epg') ? EPGID : SCNRID)]);
    }

    if (!$table_tag_skip) {
        echo '</tbody></table>';
    }

    echo '</div>';

    
} else {
    if (VIEW_TYP!=8) {
        echo '<div style="padding:30px 0">'.$tx['LBL']['noth'].'</div>';
    }
}



if (TYP=='scnr' && in_array(VIEW_TYP, [0,1])) {

    $stryz1 = rdr_cln('stryz', 'ID', 'ScnrID='.SCNRID);
    $stryz2 = rdr_cln('epg_scnr_fragments', 'NativeID', 'NativeType=2 AND ScnrID='.SCNRID, null, -1);

    $stryz_pending = array_diff($stryz1, $stryz2);

    if ($stryz_pending) {

        echo '<div class="row btnbar scnr_pending">';

        foreach ($stryz_pending as $v) {

            $stry = rdr_row('stryz', 'ID, Caption, UID, TermAdd, Phase', $v);

            echo '<div>';

            $href = '?typ='.TYP.'&id='.$x['ID'].'&view='.VIEW_TYP;

            $btn = '<a class="ctrl text-%s%s" href="'.$href.'&pending_act=%s&pending_id=%u">'.
                '<span class="glyphicon glyphicon-%s-circle"></span></a>';

            $editor = pms_scnr(SCNRID, 1);

            printf($btn, 'success', (($editor) ? '' : ' disabled'), 'accept', $stry['ID'], 'ok');
            printf($btn, 'danger', (($editor || $stry['UID']==UZID) ? '' : ' disabled'), 'reject', $stry['ID'], 'remove');

            echo
                phase_sign(['phase' => $stry['Phase']]).
                '<a class="cpt lbl_left" href="/desk/stry_details.php?id='.$stry['ID'].'">'.$stry['Caption'].'</a>'.
                '<span class="stry_author lbl_left">'.uid2name($stry['UID'], ['n1_typ'=>'init']).'</span>'.
                '<small class="lbl_right">'.$stry['TermAdd'].'</small>';

            echo '</div>';
        }

        echo '</div>';
    }

}








/* BOTTOM CONTROL BAR */

if (TMPL || (in_array(VIEW_TYP, [0,1]) && !EPG_LIST_SHORT && !EPG_LIST_PROGS) || (VIEW_TYP==5 && !SCH_BLANK)) {

    echo '<div class="row btnbar">';

    if (!TMPL){

        if (in_array(VIEW_TYP, [0,1])) {   // LIST and TREE view

            if (TYP=='epg'){

                echo '<div class="btn-toolbar pull-right">';

                pms_btn( // BTN: MAKE TEMPLATE from this EPG schedule
                    PMS_FULL, $tx['LBL']['create'].' '.$tx[SCTN]['LBL']['template'],
                    [   'href' => 'epg_modify_multi.php?typ='.TYP.'&id='.EPGID.'&tmpl=1',
                        'class' => 'btn btn-xs btn-success text-uppercase opcty3 satrt'     ]
                );

                // BTN: DELETE this EPG schedule
                $deleter_argz = [
                    'pms' => pms('epg', 'del_epg', $x),
                    'button_css' => 'btn-xs opcty3 satrt',
                    'txt_body_itemtyp' => $tx['LBL']['epg'],
                    'txt_body_itemcpt' => $datecpt['TDAY']['date'],
                    'submiter_href' => 'epg.php?filter=all&delscope=descend&typ=epg&del='.EPGID
                ];
                modal_output('button', 'deleter', $deleter_argz);

                echo '</div>';


            } elseif (TYP=='scnr' && $x['NativeType']==1) { // scnr, but not film/serial

                echo '<div class="btn-toolbar pull-right">';

                pms_btn( // BTN: NEW STORY
                    (PMS_FULL || pms('dsk/stry', 'new')),
                    '<span class="glyphicon glyphicon-plus-sign new"></span>'.$tx['LBL']['stry'],
                    [   'href' => '/desk/stry_modify.php?typ=mdf_'.(($cfg['strynew_2in1']) ? 'atom' : 'dsc').
                            '&scnid='.SCNRID.((PMS_FULL) ? '' : '&pending=1'),
                        'class' => 'btn btn-success btn-xs text-uppercase pull-right'    ]
                );

                pms_btn( // BTN: NEW TASK
                    PMS_FULL, '<span class="glyphicon glyphicon-plus-sign new"></span>'.$tx['LBL']['task'],
                    [   'href' => '/desk/stry_modify.php?typ=mdf_task&scnid='.SCNRID,
                        'class' => 'btn btn-success btn-xs text-uppercase pull-right'    ]
                );

                echo '</div>';
            }
        }


        if (VIEW_TYP==5 && !SCH_BLANK) { // TRASH view, and it is not empty

            pms_btn( // BTN: DELETE ALL from trash
                PMS_FULL, $tx[SCTN]['LBL']['delete_all'],
                [   'href' => 'epg.php?delscope=descend&typ='.TYP.'&del='.
                        ((TYP=='epg') ? EPGID : SCNRID.'&id='.$x['ID']),
                    'class' => 'btn btn-xs btn-danger text-uppercase'   ]
            );
        }


    } else { // TMPL

        echo '<div class="pull-right">';

        if (TYP=='epg') {

            // BTN: DELETE this EPG template
            $deleter_argz = [
                'pms' => pms('epg', 'tmpl_epg', $x),
                'button_css' => 'btn-xs opcty3 satrt',
                'txt_body_itemtyp' => $tx['LBL']['epg'].' '.$tx[SCTN]['LBL']['template'],
                'txt_body_itemcpt' => $x['EPG']['Caption'],
                'submiter_href' => 'epg.php?tmpl=1&delscope=descend&typ=epg&del='.EPGID
            ];
            modal_output('button', 'deleter', $deleter_argz);

        } else { // scn

            // BTN: DELETE this SCNR template
            $deleter_argz = [
                'pms' => PMS_TMPL_SCNR,
                'button_css' => 'btn-xs opcty3 satrt',
                'txt_body_itemtyp' => $tx[SCTN]['LBL']['template'],
                'txt_body_itemcpt' => '',
                'submiter_href' => 'epg.php?tmpl=1&delscope=target&typ=epg&del='.$x['ID']
            ];
            modal_output('button', 'deleter', $deleter_argz);
        }

        echo '</div>';

    }


    if (!SCH_BLANK && in_array(VIEW_TYP, [0,1]) && !LOG) {
        echo '<div class="pull-left">'.logzer_output('btn', null).'</div>';
    }


    echo '</div>';
}




/* LOG LIST */

if (!SCH_BLANK && in_array(VIEW_TYP, [0,1]) && LOG) {

    echo '<div class="row well" id="logz_text"><a name="log"></a>';

    if (TYP=='epg') {
        logzer_output('tbl-epg-get', ['obj_id' => EPGID, 'obj_typ' => 'epg']);
    } else { // scn
        logzer_output('tbl-epg-get', ['obj_id' => $x['ID'], 'obj_typ' => 'element']);
    }

    echo logzer_output('tbl-epg-put', $x);

    echo '</div>';
}






/* MOS & CRW BOX */

// For SCNR (not tmpl), unless it is a film
if (!TMPL && TYP!='epg' && in_array(VIEW_TYP, [0,1]) && !isset($x['FILM'])) {

    echo '<div class="row hidden-print">';

    if ($x['CRW']) {

        echo '<div class="col-md-12">';
        crw_output('dtl', $x['CRW'], [12,13,1,2,3,6,7,8,9,10,11], ['collapse'=>(VIEW_TYP==0)]);
        echo '</div>';
    }

    if ($x['PRG']['MatType']!=1 && !SCH_BLANK) { // If LIVE then skip MOS

        echo '<div class="col-md-12">';
        mos_output('dtl', $x['MOS'], false);
        echo '</div>';
    }

    echo '</div>';
}



/* ROLER MODAL */

if (TYP=='scnr' && $x['NativeType']==1 && in_array(VIEW_TYP, [0,1])) {

    $my_roles = [];

    foreach ($x['CRW'] as $k => $v) {
        if ($v['CrewUID']==UZID) {
            $my_roles[$v['CrewType']] = true;
        }
    }

    $cpt_roles = txarr('arrays', 'epg_crew_types');

    $epg_roles = (CHNLTYP==1) ? [1,2,3] : [1,2];

    foreach ($epg_roles as $v) {

        if (@$x['PRG']['SETZ']['SecurityStrict']) {

            $pms = pms_prgm_editor($x['PRG']['ProgID']);

        } else {

            switch ($v) {

                case 1:
                    $pms = pms_prgm_editor($x['PRG']['ProgID']);
                    break;

                case 2:
                    $pms = pms_roler('descendant', 'Journo');
                    break;

                case 3:
                    $pms = pms_roler('group', 'Realztor');
                    break;
            }
        }

        $html_modal_body[] =
            ctrl('modal', 'chk', $cpt_roles[$v], 'role['.$v.']', isset($my_roles[$v]), (($pms) ? '' : 'disabled'));

        // Disabled checkbox is lost on submit, as though it was unchecked. The problem occurs with *disabled but checked*
        // checkbox, because it's value would be set to unchecked on submit. Therefore we shadow it with hidden ctrl..
        if (!$pms && isset($my_roles[$v])) {
            $html_modal_body[] = form_ctrl_hidden('role['.$v.']', 1, false);
        }
    }

    modal_output('modal', 'poster',
        [
            'name_prefix' => 'roler',
            'pms' => true,
            'submiter_href' => '?'.$_SERVER["QUERY_STRING"].'&roler=1',
            'modal_size' => 'modal-sm',
            'cpt_header' => $tx['LBL']['crew'],
            'txt_body' => implode('', $html_modal_body)
        ]
    );
}


/* MKTPLANITEM MDF MODAL */

if (VIEW_TYP==8 && EPG_MKTVIEW==1) {

    mktplan_item_modalmdf($x['EPG']['ChannelID']);
}



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
