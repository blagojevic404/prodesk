<?php
/**
 * This script is loaded when film-serial list is loaded through IFRAME (in epg_modify_single.php) and then a film is
 * selected, which means the user is supposed to select a specific episode from a selected film-serial.
 * If it is the NEW SERIAL situation, then film items list will be shown at first, so the user can select the film item,
 * and only after that, this episodes list will be shown, so the user can select the specific episode too.
 * If it is the MODIFY situation, i.e. film-serial (and one of its episodes) was already selected but the user now
 * wants to change it, then this sript is loaded straight away, i.e. we suppose that user wants to change only the episode.
 * Of course, the user can change the film altogether, by first clicking on the FILM-ITEMS link in the crumbs bar, etc.
 */

require '../../__ssn/ssn_boot.php';


$id = (isset($_GET['id'])) 	? wash('int', $_GET['id']) : 0;

$x = film_reader($id, 'item');

$x['Episodes'] = rdr_cln('film_episodes', 'Ordinal', 'ParentID='.$x['ID']);

if (!$x['ID']) {
    require '../../__inc/inc_404.php';
}



/*************************** HEADER ****************************/

/*TITLE FOR SUBSECTION*/
$header_cfg['subscn'] = ''; // no need to specify navigation subsection, because this page will display only inside iframe.

/*CSS*/
$header_cfg['css'] = ['epg/epg_film.css'];

/*CONFIG*/
$header_cfg['ifrmtunel'] = true;


require '../../__inc/_1header.php';
/***************************************************************/


$bs_css['panel_w'] = 'col-sm-12'; // used in crumbs


// CRUMBS
echo '<div class="crumbsmarginfix">';
crumbs_output('open');

crumbs_output(
    'item',
    $tx['NAV'][77].': '.$tx[SCTN]['LBL'][$x['TYP'].'s'],
    'list_film.php?typ='.$x['TYP'].'&cluster[1]='.$x['TypeID'].'&cluster[2]='.$x['SectionID'].'&ifrm=1',
    'text-uppercase'
);
crumbs_output('item', $x['Title']);
crumbs_output('item', $tx[SCTN]['LBL']['episodes'], '', 'active');

crumbs_output('close');
echo '</div>';



if ($x['TYP']=='item' && $x['TypeID']!=1) {

    film_episodes_listing('frame', $x);
}



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
