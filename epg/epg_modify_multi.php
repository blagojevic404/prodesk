<?php
/**
 * Multiple-lines modify page, for epg or scn
 */

require '../../__ssn/ssn_boot.php';


/** Schedule type: epg, scnr */
define('TYP', ($_GET['typ']=='epg') ? 'epg' : 'scnr');

/** Whether this is NEW EPG situation. If DATE ($_GET['d'] is set then it is new epg, otherwise not. )*/
define('EPG_NEW', (isset($_GET['d']) ? wash('ymd' ,$_GET['d']) : ''));


if (TYP=='epg') {

	$id = (isset($_GET['id'])) ? wash('int', $_GET['id']) : 0;

	$x['EPG'] = epg_reader($id);

	if (EPG_NEW) {
        $x['EPG']['IsReady'] = intval(cfg_local('arrz', 'chnl_'.$x['EPG']['ChannelID'], 'EPG_isready_init'));
        // Initial value for IsReady column, determines whether epgs are initialy (not) public on the web
    }

    /** Channel type: 1=tv, 2=radio */
    define('CHNLTYP', rdr_cell('channels', 'TypeX', $x['EPG']['ChannelID']));

    define('TMPL_NEW_FROM_EPG', (@$_GET['tmpl'])					? 1 : 0); // NEW TMPL FROM EPG situation
	define('TMPL_MDF', (!EPG_NEW && $x['EPG']['IsTMPL']) 		    ? 1 : 0); // TMPL MDF situation
	define('TMPL', (TMPL_MDF || TMPL_NEW_FROM_EPG) 					? 1 : 0);
	define('EPG_MDF', (!TMPL && !EPG_NEW) 							? 1 : 0); // EPG MDF situation

    define('PMS_FULL', pms('epg', 'mdf_full', $x, true));

    $href_submit = 'epg.php?typ=epg';
	
	if (EPG_MDF || TMPL_MDF) {
        $href_submit .= '&id='.$x['EPG']['ID'];
    }

	if (EPG_NEW) {
        $href_submit .= '&d='.EPG_NEW;
        // If we have EPG_NEW date then we can disregard ID on submit, as it belongs to copy-from EPG/TMPL
    }


    // List of line types for this schedule type (epg). See LNG-ARRAYS: epg_line_types.
    // Used for interface: squiz buttons (order dictates the order of buttons!), filter cbo.
    $linetypz = (CHNLTYP==1) ? [1,12,13,3,4,5,8,9,14] : [1,3,4,5,8,9,14]; // Radio doesn't use film/serial


} else { // scnr


	$x = element_reader_front();

	define('SCN_NEW_FROM_TMPL', (@$_GET['tmpl'])				? 1 : 0); // NEW SCN FROM TMPL situation
	define('TMPL_NEW', (!$x['ID'])								? 1 : 0); // NEW TMPL situation
	define('TMPL_MDF', (!TMPL_NEW &&!$x['EPG']['ID']) 			? 1 : 0); // TMPL MDF situation
	define('TMPL', (TMPL_NEW || TMPL_MDF) 						? 1 : 0);

    if (TMPL_NEW) {
        define('PMS_FULL', true); // I have set PMS to true, because I have no idea what users to filter for this
    } else {
        define('PMS_FULL', pms('epg', 'mdf_full', $x, true));
    }

    if (!TMPL) {
		$href_submit = 'epg.php?typ=scnr'.(($x['ID']) ? '&id='.$x['ID'] : '&qu='.$x['Queue'].'&epg='.$x['EpgID']);
	} else {
		$href_submit = 'epg.php?typ=scnr'.(($x['ID']) ? '&id='.$x['ID'] : '');
	}


    define('SCN_IMPORT_FROM_SCN', (isset($_GET['import_id'])) ? 1 : 0); // SCN IMPORT FROM SCN situation

    if (SCN_IMPORT_FROM_SCN) {

        $import_id = wash('int', $_GET['import_id']);

        $href_submit .= '&import_id='.$import_id;
    }


    // List of line types for this schedule type (scn). See LNG-ARRAYS: epg_line_types.
    // Used for interface: squiz buttons (order dictates the order of buttons!), filter cbo.
	$linetypz = [2,7,3,4,5,8,9,10];
}




if (!defined('SCN_NEW_FROM_TMPL')) {
    // We need this const to be defined (even if it is zero) in order not to throw error later in the code
    define('SCN_NEW_FROM_TMPL', 0);
}

// For SCNRs except those of LIVEAIR(1) type, terms (TimeAir column) represent RELATIVE values instead absolute,
// i.e. they are relative to the scnr start.. Otherwise, terms are always absolute.
define('TERM_TYP', (TYP=='scnr' && @$x['PRG']['MatType']>1) ? 'rel' : 'fixed');

if (!TMPL) {
    $datecpt = epg_cptdate(((!EPG_NEW) ? $x['EPG']['DateAir'] : EPG_NEW), 'date_wday');
}












/*************************** CONFIG ****************************/

/*TITLE FOR SUBSECTION*/
$header_cfg['subscn'] = epg_get_subscn_title($x);


/*CSS*/

$header_cfg['css'][] = 'floater.css';

$header_cfg['css'][] = 'epg/epg_modify_multi.css';


/*JS*/

$header_cfg['js'][]  = 'epg/epg.js';

$footer_cfg['js_lines'][]  = help_output('popover');


// ifrm
$header_cfg['js'][]  = 'ifrm.js';
$header_cfg['js_lines'][]  = 'var ifrm_result_typ = "multi";';


// tablednd
$header_cfg['js'][]  = 'tablednd/tablednd.js';
$header_cfg['js'][]  = 'tablednd/tablednd_custom.js';
$header_cfg['js_onload'][]  = 'tableDnDOnLoad()';
$header_cfg['js_onsubmit'][]  = 'tablednd_queuer()';

$header_cfg['js_onsubmit'][]  = 'clone_cleaner(1, '.max($linetypz).')';


// submit check
$header_cfg['form_checker'][] = ['typ'=>'epg_multi', 'cpt'=>$tx[SCTN]['MSG']['err_empty_prog']];
$footer_cfg['modal'][] = 'alerter';


require '../../__inc/_1header.php';
/***************************************************************/




// FORM start
form_tag_output('open', $href_submit, false);





/* HEADER BAR */


$html_helper = help_output('button',
    ['content' => txarr('blocks', 'help_mdfmulti').((TYP=='epg') ? '<hr>'.txarr('blocks', 'help_mdfprg') : ''),
     'output' => false,
     'css' => 'help mdfmulti']);


if (TYP=='epg') {

    if (!TMPL) {

        echo '<div class="well well-sm headbar">';

        echo '<div><h2>'.$datecpt['wday'].' <small>/'.$datecpt['date'].'/</small></h2></div>';

        echo '<div class="pull-right">'.$html_helper.'</div>';

        $arr_status = lng2arr($tx['LBL']['opp_ready']);

        // IsReady control for epg
        $arr_ready = [
            '<span class="glyphicon glyphicon-ban-circle" title="'.$arr_status[0].'"></span>',
            '<span class="glyphicon glyphicon-ok" title="'.$arr_status[1].'"></span>'
        ];
        echo '<div class="pull-right" style="margin-right:10px;">'.
            btngroup_builder('IsReady', $arr_ready, @$x['EPG']['IsReady'], 'form-inline').'</div>';

        echo '</div>';

    } else { // TMPL

        echo '<div class="well well-sm row headbar">';

        echo '<div class="col-sm-3"><h2>'.$tx[SCTN]['LBL']['template'].'</h2></div>';

        echo
            '<div class="col-sm-9"><input type="text" name="Caption" id="Caption" class="form-control input-lg" '.
              'value="'.@$x['EPG']['Caption'].'" placeholder="'.$tx['LBL']['title'].'" required autofocus></div>';

        echo '</div>';
    }

} else {    // scn

    if (!TMPL) {

        echo '<div class="well well-sm headbar">';

        if (!isset($x['FILM'])) { // prog

            echo '<div><h2>'.$x['PRG']['ProgCPT'];

            if ($x['PRG']['Caption']) {
                echo ' <small>'.$x['PRG']['Caption'].'</small>';
            }

            echo '</h2></div>';

        } else { // film

            echo '<div><h2>'.$x['FILM']['Title'];

            if ($x['FILM']['DscTitle']) {
                echo ' <small>'.$x['FILM']['DscTitle'].'</small>';
            }

            echo '</h2></div>';
        }

        echo '<div class="pull-right">'.$html_helper.'</div>';

        echo '</div>';

    } else {  // TMPL

        echo '<div class="well well-sm row headbar">';

        echo '<div class="col-sm-3"><h2>'.$tx[SCTN]['LBL']['template'].'</h2></div>';

        echo
            '<div class="col-sm-9"><input type="text" name="Tmpl_Caption" id="Tmpl_Caption" class="form-control input-lg" '.
            'value="'.@$x['PRG']['Caption'].'" placeholder="'.$tx['LBL']['title'].'" required autofocus></div>';

        if (TMPL_NEW) {
            echo '<input type="hidden" name="Tmpl_ProgID" id="Tmpl_ProgID" value="'.wash('int', $_GET['tmpl_prg']).'">';
        }

        echo '</div>';
    }
}






/* MDF_MULTI SCHEDULE TABLE - The Main Thing */

echo '<table id="dndtable" class="schtable epg_mdf">';

if (TYP=='epg') {

    if (@$x['EPG']['ID']) {
        epg_mdf_html($x['EPG']['ID'], TYP);
    }

} else { // scnr

    if (SCN_NEW_FROM_TMPL) { // NEW SCN FROM TMPL situation
        $x = element_reader(wash('int', $_GET['tmpl']));
    }

    if (@$x['SCNRID']) {
        epg_mdf_html($x['SCNRID'], TYP);
    }

    if (SCN_IMPORT_FROM_SCN) {
        epg_mdf_html($x['SCNRID'], TYP, scnrid_prog($import_id));
    }
}

echo '</table>';





/* ELEMENT ROW CLONES */

echo '<table>';

foreach (array_intersect([1,2,3,4,5,7,9,12,13,14], $linetypz) as $v) {

    item_mdf_output(
        [												// YES DUR&TERM
            'NativeType' 	=> $v,
            'DurForc' 		=> '00:00:00',
            'DurForcTXT' 	=> t2boxz('', 'time'),
            'Queue' 		=> 0,
            'EpgID' 		=> $x['EPG']['ID'], // *linker* typ will need this for the *href* value (in ifrm_setting())
            'EPG' 		    => ['ChannelID' => $x['EPG']['ChannelID']],
        ],
        'clone'
    );
}

foreach (array_intersect([8,10], $linetypz) as $v) {

    item_mdf_output(
        [												// NO DUR&TERM
            'NativeType' 	=> $v,
            'Queue' 		=> 0,
        ],
        'clone'
    );
}

echo '</table>';




/* SQUIZER ROW CLONE - to be *inserted* between table rows via JS, squizer_mdfmulti() */

echo '<table>';
epg_squizrow_output('epgmdf_clone', ['col_sum_noghost' => 4]);
echo '</table>';


/* BOTTOM SQUIZER ROW - *adds* rows to the bottom of table */

echo '<div class="squiztyp text-center">';
epg_squizrow_output('epgmdf_bottom');
echo '</div>';




// SUBMIT BUTTON
form_btnsubmit_output('Submit_MULT', ['type' => 'btn_only', 'css' => 'btn-lg btn-block']);






/* PROG LIST CLONE - to be *inserted* into prog frame via JS, ifrm_starter() */
if (TYP=='epg') {
    echo '<div id="prglist" style="display:none;">';
    epg_prg_list($x['EPG']['ChannelID']);
    echo '</div>';
}


/* IFRAME ROW CLONE - to be *inserted* into table via JS, ifrm_starter() */
ctrl_ifrm('multi_tbl');




// FORM close
form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
