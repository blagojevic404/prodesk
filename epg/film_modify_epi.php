<?php
require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) ? wash('int', $_GET['id']) : 0;

$x = film_reader($id, 'item');

$x['Episodes'] = rdr_cln('film_episodes', 'Ordinal', 'ParentID='.$x['ID']);


pms('epg/film', 'mdf', $x, true);


/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'film';
$header_cfg['bsgrid_typ'] = 'regular';


/*CSS*/

$header_cfg['css'][] = 'modify.css';
$header_cfg['css'][] = 'epg/epg_film.css';


/*JS*/

$header_cfg['autotab'][] = '#DurApproxHH, #DurApproxMM, #DurApproxSS';
$header_cfg['autotab'][] = '#DurRealHH, #DurRealMM, #DurRealSS';

require '../../__inc/_1header.php';
/***************************************************************/




// CRUMBS

crumbs_output('open');

crumbs_output('item', $tx['NAV'][77]);

crumbs_output(
    'item',
    $tx[SCTN]['LBL'][$x['TYP'].'s'],
    'list_film.php?typ='.$x['TYP'].'&cluster[1]='.$x['TypeID'].'&cluster[2]='.$x['SectionID']
);

if (@$x['ID']) {
    crumbs_output('item', sprintf('%04s',$x['ID']), 'film_details.php?typ='.$x['TYP'].'&id='.$x['ID']);
}

crumbs_output('close');




// FORM start
form_tag_output('open', 'film_details.php?typ='.$x['TYP'].'&id='.$x['ID']);



/* TABLE FIELD */
form_panel_output('head');

film_episodes_listing_mdf($x);

form_panel_output('foot');



// SUBMIT BUTTON
form_btnsubmit_output('Submit_FILM_EP');



// FORM close
form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';

