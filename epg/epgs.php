<?php
/**
 * This script shows EPGS lists.
 *
 * Types: PLAN (Default), REAL (FWD ), ARCHIVE, TMPL (TEMPLATE)
 * Note: REAL is actually just a *forward* to EPG with current date
 */



require '../../__ssn/ssn_boot.php';


define('PMS', pms('epg', 'mdf_epg_plan'));


// NOT_PUBLIC switch. Turns specified epg public or hidden (i.e. not displayed on the web).
if (PMS && isset($_GET['sw_ready'])) {

    $id = intval($_GET['sw_ready']);

    qry('UPDATE epgz SET IsReady = IF(IsReady=1,0,1) WHERE ID='.$id, ['x_id' => $id]);

	hop($pathz['www_root'].$_SERVER['SCRIPT_NAME']);
}


// Type: plan (default), real (FWD), archive, tmpl (TEMPLATE)
if (empty($_GET['typ']) || !in_array($_GET['typ'], ['real', 'plan', 'tmpl', 'archive'])) {
    define('TYP', 'plan');
} else {
    define('TYP', $_GET['typ']);
}

if (TYP=='real') {
	
    // url for REAL is actually just a redirector to script with TODAY'S epg, because that's what REAL practically means.

    $real_epgid = get_real_epgid();

    if ($real_epgid) {

        $href = '/epg.php?typ=epg&id='.$real_epgid.((!empty($_GET['view'])) ? '&view='.intval($_GET['view']) : '');

        // redirect to TODAY'S epg
        hop($pathz['www_root'].dirname($_SERVER['PHP_SELF']).$href);
    }
}

if (TYP=='tmpl') {

    /**
     * Template type: epg, scn.
     * Other types (plan, real, archive) can be only EPG, but this type (tmpl) can be either EPG or SCN (scenario)
     */
    define('TMPL_TYP', (@$_GET['tmpltyp']=='scn') ? 'scn' : 'epg');

    if (TMPL_TYP=='scn') {
        receiver_post('opt', 'tmpl_prg'); // PROGS combo which filters the template listing to a specified prog
    }
}




/*************************** HEADER ****************************/
$header_cfg['subscn'] = TYP;
$header_cfg['chnl_cbo'] = true;
$header_cfg['chnl_referer'] = true;

if (TYP=='plan') {

    $header_cfg['css'][] = 'epg/ulgrid.css';

    // Tooltip on info glyph
    $footer_cfg['js_lines'][]  = '$(\'[data-toggle="tooltip"]\').tooltip({html: true});';
}

if (TYP=='archive') {
    $header_cfg['mover'] = true;
}


require '../../__inc/_1header.php';
/***************************************************************/





// CRUMBS
crumbs_output('open');

crumbs_output('item', '<span class="label label-primary channel">'.channelz(['id' => CHNL], true).'</span> ');

crumbs_output('item', $tx['NAV'][7]);

crumbs_output('item', $nav_subz[$header_cfg['subscn']]['ttl']);

if (TYP=='tmpl') {
    crumbs_output('item', ((TMPL_TYP=='epg') ? $tx['LBL']['epg'] : $tx['LBL']['rundown']));
}

crumbs_output('close');







switch (TYP) {

    case 'real':

        echo $tx['LBL']['noth'];
        break;

    case 'plan':

        echo '<div class="row"><div class="col-xs-12">';
        epgz_plan_html();
        echo '</div></div>';

        break;

    case 'archive':

        echo '<div class="row"><div class="col-xs-12 col-sm-10 col-md-9 col-lg-8">';
        epg_calendar_list('epg_archive');
        echo '</div></div>';

        break;

    case 'tmpl':

        /*
         * There is no way to make an EMPTY new epg template, we can only make a COPY of an existing normal epg.
         * The way to make a new EPG template, thus, is to open an existing epg, and press button "MAKE TEMPLATE"
         * at the bottom, and that will take us directly to ADD/MDF page.
         *
         * Conversely, there is no way to make a new SCN template based on an existing prog scenario (SCN), we can only
         * make an EMPTY new scn template.
         * The way to make a new SCN template, thus, is to open SCN TEMPLATES listing page, and press "NEW TEMPLATE" button
         * at the top, and that will take us directly to ADD/MDF page.
         */

        /*
         * EPG template is saved in *epgz* table, just as any ordinary epg, but it doesn't have DateAir, and it is
         * positive in IsTMPL column.
         *
         * SCNR template is saved in *epg_elements* table, just as any ordinary scnr, but the only data it has is
         * NativeType (which is of course 1 - for progs) and NativeID. We can use EpgID column to catch it, because other
         * elements *must* have some value there.
         */

        echo
        '<nav class="navbar navbar-default" role="navigation">'.'<div class="container-fluid">'.
          '<div class="btn-group navbar-btn text-uppercase">'.
            '<a type="button" href="?typ=tmpl&tmpltyp=epg" class="btn btn-primary'.((TMPL_TYP=='epg') ? ' active' : '').'">'.
                $tx['LBL']['epg'].'</a>'.
            '<a type="button" href="?typ=tmpl&tmpltyp=scn" class="btn btn-primary'.((TMPL_TYP=='scn') ? ' active' : '').'">'.
                $tx['LBL']['rundown'].'</a>'.
          '</div>';


        if (TMPL_TYP=='scn') { // Print controls specific for SCN TMPL (PROGS combo and NEW button)

            $pms = true;
            if (!$_SESSION['tmpl_prg']) { // None selected in the prog cbo
                $pms = false;
            } else {
                $x['PRG']['ProgID'] = $_SESSION['tmpl_prg'];
                $pms = pms('epg', 'tmpl_scnr', $x);
            }

            // PROGS combo. Name is "tmpl_prg". It will be checked for at the beginning of the script and saved into
            // session variable.
            echo '<form class="navbar-form navbar-right" role="form" method="post" name="former" autocomplete="off">';

            echo '<div class="form-group">';
            ctrl_prg('tmpl_prg', $_SESSION['tmpl_prg'], ['submit' => true, 'output' => true]);
            echo '</div>';

            pms_btn( // BTN: NEW SCN TMPL
                $pms, '<span class="glyphicon glyphicon-plus-sign new"></span>'.$tx[SCTN]['LBL']['template'],
                [   'href' => 'epg_modify_multi.php?typ=scnr&tmpl_prg='.$_SESSION['tmpl_prg'],
                    'class' => 'btn btn-success text-uppercase pull-right',
                    'style' => 'margin-left:15px;'    ]);

            echo '</form>';
        }


        echo '</div></nav>';


        echo '<div class="row"><div class="col-xs-12 col-sm-10 col-md-10 col-lg-9" style="margin-top:40px;">';
        epgz_tmpl_html(TMPL_TYP);
        echo '</div></div>';

        break;
}








/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
