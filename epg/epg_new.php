<?php
/**
 * This script is used:
 * - EPG-NEW: When making a NEW EPG either *from existing epg* or *from template*. (Called from EPG PLAN LISTING page.)
 *            Will display either epgs listing (epg_calendar_list()) or epg templates listing (epgz_tmpl_html()).
 * - SCNR-TMPL: When importing scnr template into blank scnr (template can be imported only into BLANK scnr).
 *              Will display scnr templates listing (epgz_tmpl_html()).
 */



require '../../__ssn/ssn_boot.php';


/** Whether we will make a copy of existing epg/scn (exst), or a copy of epg/scn template (tmpl) */
define('FROM_TYP', (@$_GET['fromtyp']=='exst') ? 'exst' : 'tmpl');

/**
 * EPG: DATE of the EPG to be created, i.e. NEW EPG.
 * We are forced to use date, because we still don't have ID of target epg, because it is not created yet.
 * This is used only for EPG type. Hence, it will also be used to determine whether this is epg or scn type.
 */
define('EPG_NEW', (isset($_GET['d']) ? wash('ymd' ,$_GET['d']) : ''));

/**
 * SCN: ID of the SCNR which requested this page (i.e. the import target scn).
 * This is used only for SCN type.
 */
define('SCNELMID', (isset($_GET['scnelmid']) ? wash('int' ,$_GET['scnelmid']) : 0));

/** Whether this is EPG or SCN. (If a date (EPG_NEW) is defined then it is EPG, otherwise it is SCN.) */
define('TYP', (EPG_NEW) ? 'epg' : 'scn');


// For both epg and scn import, you can change channel (by channel combo), thus importing epg/scn from another channel.
if (FROM_TYP=='exst') {
    $from_chnl = (isset($_POST['from_chnl'])) ? wash('int', $_POST['from_chnl']) : CHNL;
}



/*************************** HEADER ****************************/


/*TITLE FOR SUBSECTION*/

if (TYP=='epg') {

    $header_cfg['subscn'] = 'plan';
    $header_cfg['mover'] = true;

} else { // scn

    $real_epgid = get_real_epgid();

    $epgid = rdr_cell('epg_elements', 'EpgID', SCNELMID); // Epg of the source scenario

    $header_cfg['subscn'] = (($real_epgid==$epgid) ? 'real' : 'plan');
}

require '../../__inc/_1header.php';
/***************************************************************/




if (FROM_TYP=='exst') {

    receiver_post('opt', 'channel', 1);

    echo '<form method="post" name="former" role="form"><div class="form-group">'.
        '<select style="width:auto" class="form-control" name="from_chnl" onChange="submit()">'.
        arr2mnu(channelz(['typ' => [1,2]]), $from_chnl).
        '</select>'.
        '</div></form>';


    if (TYP=='epg') { // NEW EPG from existing epg

        echo '<div class="row"><div class="col-xs-12 col-sm-10 col-md-9 col-lg-8">';
        epg_calendar_list('epg_new', 'horizontal', $from_chnl);
        echo '</div></div>';

    } else { // scnr import
        // (discontinued)
    }


} else { // TMPL (either NEW EPG from template, or importing scn template into blank scn)

    echo '<div class="row"><div class="col-xs-12 col-sm-10 col-md-10 col-lg-9" style="margin-top:40px;">';
    epgz_tmpl_html(TYP, 'new');
    echo '</div></div>';
}




/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
