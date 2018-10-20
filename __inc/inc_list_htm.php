<?php


/*************************** HEADER ****************************/

/* TITLE FOR SUBSECTION */
if (TBLID>=30 && TBLID<=38) { // spices and films (epg)

    $header_cfg['subscn'] = EPG_SCT;

} elseif ((TBLID>=40 && TBLID<=45) || TBLID==51) { // DSK & HRM section

    $header_cfg['subscn'] = TYP;
}


/*CONFIG*/
if (IFRM) {
    $header_cfg['ifrmtunel'] = true;
}

$header_cfg['ajax'] = true;


/* CSS */

$header_cfg['css'][] = 'list.css';

if (TBLID==38) { // 38=film  (film items)
    $header_cfg['css'][] = 'epg/epg_bcast.css';
}


/* JS */

$header_cfg['js'][] = '_list/list.js';

// calendar bar
if (SHOW_CALENDAR) {
    $header_cfg['calendar'] = calendar_data();
}

// bs_daterange
$header_cfg['bs_daterange'] = [];

// Tooltip
if (TBLID==45) { // 45=prgm
    $footer_cfg['js_lines'][]  = '$(\'[data-toggle="tooltip"]\').tooltip();';
}

// Onload focus on GGL textbox
if (GGL_FOCUS) {
    $header_cfg['js_onload'][]  = 'document.getElementById(\'lst_search\').focus()';
}


require '../../__inc/_1header.php';
/***************************************************************/








echo '<form method="get" target="_self" name="f" id="f" class="form-horizontal" autocomplete="off">';

/* Take note: submit method for the form is GET, which means all control variables will be stringed into submit url
 *
 * It also means that all values which we want to pass into submit, have to come FROM A CONTROL.
 * Because of that, we have to add a shadow submitter for every value which doesnot come from a control, such as:
 * *typ* and *ifrm*, and also every value which comes from a LINK CLICK, such as *dtr*, *cluster*, *sort_cln*, *sort_typ*.
 *
 * We also add a shadow submitter for every CHECKBOX which we defined for memorizing into db. We do that so that
 * the checkbox control would hold a value of 0 when it is submited unchecked (by default it would simply be *undefined*
 * when unchecked). We need it to hold a 0 value instead of undefined, because we want it to be *undefined* only when
 * the page is loaded *without* submit, i.e. when the page is opened from a link - because only *undefined* value will
 * trigger reading the value from the db!
 */

if (SHOW_CALENDAR) {
    echo '<input type="hidden" name="dtr" id="dtr" value="">';
}

if (SHOW_ABV) {
    echo '<input type="hidden" name="ABV" id="ABV" value="">';
}

echo '<input type="hidden" name="sort_cln" id="sort_cln" value="'.@$dspl['sort_cln']['val'].'">';
echo '<input type="hidden" name="sort_typ" id="sort_typ" value="'.@$dspl['sort_typ']['val'].'">';
echo '<input type="hidden" name="ifrm" id="ifrm" value="'.IFRM.'">';

if (defined('TYP')) {
    echo '<input type="hidden" name="typ" value="'.TYP.'">'."\n";
}

if (isset($clusterz) && is_array($clusterz)) {
    foreach ($clusterz as $k => $v) {
        echo '<input type="hidden" name="cluster['.$k.']" value="'.$v['sel'].'">'."\n";
    }
}

foreach($cndz['typ'] as $k => $v) {
    if (@$cndz['mmry'][$k]) {
        echo '<input type="hidden" name="'.$cndz['nme'][$k].'" id="'.$cndz['nme'][$k].'" value="'.$cndz['val'][$k].'">';
    }
}



/* BUTTONS BAR */

if (SHOW_BTNZ && !BTNZ_CTRLZ_UNI) {

    echo '<div class="well well-sm headnav clearfix">';

    list_buttons_html($btnz, $clusterz);

    echo '</div>';
}



/* ALPHABET BAR */

if (SHOW_ABV) {

    echo '<div class="well well-sm text-center" id="abv">';

    $abv = $cndz['opt'][$abv_key];

    $abv_sel = $cndz['val'][$abv_key];

    foreach ($abv as $v) {

        echo '<a'.(($abv_sel===$v) ? ' class="disabled"' : '').' href="#" onclick="tunel(\'ABV\', \''.$v.'\');">'.$v.'</a>';
    }

    echo '</div>';
}



/* CONDITIONS BAR */

if (SHOW_CNDZ) {

    echo '<div id="tools"><table class="table"><tr>';

    list_conditions_html($cndz);


    if (SHOW_BTNZ && BTNZ_CTRLZ_UNI) {

        echo '<td class="btnz text-right">';

        list_buttons_html($btnz, $clusterz);

        echo '</td>';
    }


    echo '</tr></table></div>';
}



/* CALENDAR BAR */

if (SHOW_CALENDAR) {
    echo '<div class="well well-sm clndar">
        <span class="glyphicon glyphicon-calendar"></span>
        <div id="clndar" class="text-center"></div>
        </div>';
}



/* FILTER BAR */

list_filter_html($cndz);



/* RESULTS REPORT BAR */

echo '<div class="report">';

echo '<span class="rpp pull-right">
	<select onChange="submit()" name="rpp" class="form-control input-sm">'.
    arr2mnu($dspl['rpp']['options'], $dspl['rpp']['val']).
    '</select>
</span>';

list_result_cnt($query['limit_start'], $query['limit_len']);

echo '</div>';



// no form controls below this point
echo '</form>';



/* LIST TABLE */

echo $html;



/* PAGINATION BAR */

echo '<div class="text-center">';

list_pgz_html($query['limit_start'], $query['limit_len']);

echo '</div>';





/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';

