<?php


// IFRAME










/**
 * Gets IFrame SETTINGS
 *
 * This function is called prior to other two IFrame functions, and it provides settings for them
 *
 * @param array $x Element/Fragment array
 * @param bool $opt
 * - 'type' - Type (film_contract, film_blctyp (DISCONTINUED), mktplan_block, mktplan_item)
 *
 * @return array $r Settings for the IFrame
 */
function ifrm_setting($x, $opt=null) {

    global $tx;
    $txt_maxlen = 67;

    if (!isset($x['NativeType'])) {
        $x['NativeType'] = $x['EPG_SCT_ID'];
    }


    switch ($x['NativeType']) {


        case 1:		// prog

            $r['name'] = 'ProgID';      // CONTROL: name for the hidden field shadow
            $r['href'] = '';            // DIV-TITLE: href (for prog it is empty because the prog list is loaded via js)

            $r['cur'] = [
                'value' 		=> @$x['PRG']['ProgID'], // CONTROL: value for the hidden field shadow
                'label_bold' 	=> (@$x['PRG']['ProgID']) ? @$x['PRG']['ProgCPT'] : '', // DIV-TITLE: caption
                'label_normal' 	=> '' // DIV-TITLE: subcaption
            ];

            $r['div_col_span'] = 6;            // DIV-TITLE: containing bs div col span
            $r['div_col_css'] = 'pad_r5';      // DIV-TITLE: containing bs div additional css class (if any)
            break;


        case 14:		// linker

            $r['cpt'] = txarr('arrays', 'epg_line_types', 14);
            $r['href'] = 'epg_mover.php?act=linker&epgid='.@$x['EpgID'];

            $r['cur'] = [
                'value' 		=> @$x['LINK']['ID'],
                'label_bold' 	=> '<span class="glyphicon glyphicon-link"></span> '.
                                    ((@$x['LINK']['PRG']['ProgID']) ? @$x['LINK']['PRG']['ProgCPT'] : ''),
                'label_normal' 	=> ''
            ];

            $r['div_col_span'] = 12;
            break;


        case 2:		// stry

            $precedence = (@$x['STRY']['Phase']==4) ? 'calc' : '';

            $r['cpt'] = txarr('arrays', 'epg_line_types', 2);
            $r['href'] = '../desk/list_stry.php?ifrm=1&EPGCONN=2&sort_cln=0';

            $r['cur'] = [
                'value' 		=> @$x['STRY']['ID'],
                'label_bold' 	=> @$x['STRY']['Caption'],
                'label_normal' 	=> date('H:i:s',
                    dur_handler(@$x['STRY']['DurForc'], @$x['STRY']['DurCalc'], 'time', $precedence)),
            ];

            if ($r['cur']['label_normal']=='00:00:00') {
                $r['cur']['label_normal'] = '';
            }

            $r['div_col_span'] = 12;
            break;


        case 3:		// mkt
        case 4:		// prm

            if ($x['NativeType']==3 && in_array($opt['typ'], ['mktplan_item', 'mktplan_block'])) { // MKT ITEM

                $r['cpt'] = $tx[SCTN]['LBL']['item'];
                $r['href'] = 'list_mkt.php?typ=item&ifrm=1';

                if ($opt['typ']=='mktplan_item') {

                    $r['cur'] = [
                        'value' 		=> @$x['ID'],
                        'label_bold' 	=> @$x['Caption'],
                        'label_normal' 	=> @$x['DurForc'],
                    ];

                } else { // mktplan_block

                    $r['cur'] = null;
                }


            } else { // MKT/PRM BLOCK

                $r['cpt'] = $tx[SCTN]['LBL']['block'];
                $r['href'] = 'list_'.(($x['NativeType']==3) ? 'mkt' : 'prm').'.php?typ=block&ifrm=1';

                $r['cur'] = [
                    'value' 		=> @$x['BLC']['ID'],
                    'label_bold' 	=> @$x['BLC']['Caption'],
                    'label_normal' 	=> ((@$x['NativeID']) ?
                        date('H:i:s', dur_handler(@$x['BLC']['DurForc'], @$x['BLC']['DurCalc'], 'time')) : ''),
                ];

                if ($x['NativeType']==3) { // MKT

                    $r['div_col_span'] = 12; // full width

                } else { // PRM (cbo type)

                    $r['div_col_span'] = 8; // there is a cbo on right side, therefore ifrm leaves one third of the width
                    $r['div_col_css'] = 'pad_r0';
                }
            }

            break;


        case 5:		// clp

            $r['cpt'] = txarr('arrays', 'epg_line_types', 5);
            $r['href'] = 'list_clp.php?ifrm=1&TYPX='.((TYP=='scnr') ? 3 : 4);

            $r['cur'] = [
                'value' 		=> @$x['CLP']['ID'],
                'label_bold' 	=> @$x['CLP']['Caption'],
                'label_normal' 	=> @$x['CLP']['DurForc'],
            ];

            if (isset($x['CLP']['CtgID']) && $x['CLP']['CtgID']==3 && $x['CLP']['Placing']) {
                $r['cur']['label_bold'] .= ' ('.txarr('arrays', 'epg_clp_place', $x['CLP']['Placing']).')';
            }

            $r['div_col_span'] = 12;
            break;


        case 12:		// film
        case 13:		// film-serial

            if ($opt['typ']=='film_contract') {

                $r['name'] 	= 'ContractID';
                $r['cpt'] 	= $tx[SCTN]['LBL']['contract'];
                $r['href'] 	= 'list_film.php?typ=contract&ifrm=1';
                $r['cur'] 	= [
                    'value' 		=> @$x['Contract']['ID'],
                    'label_bold' 	=> @$x['Contract']['AgencyTXT'],
                    'label_normal' 	=> @$x['Contract']['CodeLabel'],
                ];

            } else {

                $film_typ = ($x['NativeType']==12) ? 1 : ((@$x['FILM']['TypeID']) ? $x['FILM']['TypeID'] : 3);

                $r['cpt'] 	= txarr('arrays', 'film_types', $film_typ);


                if ($opt['typ']=='film_blctyp') { // MKT-BLCTYP - film serials for mkt blctyp.. (i.e. we need serials not episodes)

                    $r['href'] = 'list_serial.php';

                } else {

                    if (@$x['FILM']['FilmParentID']) {

                        $r['href'] = 'film_episode_list_frm.php?id='.$x['FILM']['FilmParentID'];

                    } else {

                        $r['href'] = 'list_film.php?typ=item&cluster[1]='.$film_typ.'&cluster[2]=1&ifrm=1&sort_cln=1&sort_typ=1';

                        if ($x['NativeType']==13) {
                            $r['href'] .= '&CHK_FLM_RECENT=1';
                        }
                    }
                }


                $r['cur']['value'] 		  = @$x['FILM']['FilmID'];
                $r['cur']['label_normal'] = @$x['FILM']['Duration'];

                $r['cur']['label_bold'] = @$x['FILM']['Title'];
                if ($x['NativeType']==13)	{
                    $r['cur']['label_bold'] .=	(@$x['FILM']['Ordinal'] ? ' ('.@$x['FILM']['Ordinal'].')' : '').
                        (@$x['FILM']['EpiTitle'] ? ' - '.$x['FILM']['EpiTitle'] : '');
                }
                $r['div_col_span'] = 10;
                $r['div_col_css'] = 'pad_r0';
            }
            break;

    }


    if (!isset($r['name'])) {
        $r['name'] = 'NativeID';
    }

    $r['ID'] 		 = @$x['ID']; 							// we need this for multi
    $r['NativeType'] = $x['NativeType']; 					// we need this for multi

    if ($r['cur']['label_bold'] && (mb_strlen($r['cur']['label_bold']) > $txt_maxlen)) {
        $r['cur']['label_bold'] = txt_cutter($r['cur']['label_bold'], ($txt_maxlen-2));
    }

    // Prevent opening ifrm with item list for another channel (which can happen if channel session is changed by
    // switching to some different channel in another tab)
    if (in_array($x['NativeType'], [2,3,4,5,6]) && !empty($r['href']) && !empty($x['EPG']['ChannelID'])) {
        $r['href'] .= '&listCHNL='.$x['EPG']['ChannelID'];
    }


    return $r;
}








/**
 * IFrame LABEL output
 *
 * @param string $typ Page type: (single, multi)
 * @param array $ifrm IFrame data
 * @return void
 */
function ifrm_output_lbl($typ, $ifrm) {

    global $bs_css; // Dimensions array for BS column classes (return array from bs_grid_css())

    $wrap = ($typ=='multi') ? false : true; // Whether to print *wrap* html


    if ($wrap) {
        echo '
        <div class="form-group">
            <label class="'.$bs_css['cln_l'].' control-label">'.$ifrm['cpt'].'</label>
            <div class="'.$bs_css['cln_r'].'">';
    }

    $clicker = 'onClick="ifrm_starter(\'normal\', '.(($typ=='single') ? '\'ifrmtunel\'' : 'this').
        ', \''.$ifrm['href'].'\'); return false;"';

    if (@$ifrm['disabled']) {
        $clicker = '';
    }

    echo
        '<div id="ifrm_label" '.$clicker.'>'.
        '<span id="label_bold">'.$ifrm['cur']['label_bold'].'</span>&nbsp;'.
        '<span id="label_normal">'.(($ifrm['cur']['label_normal']) ? $ifrm['cur']['label_normal'] : '...').'</span>'.
        '</div>';

    echo
        '<a class="text-muted opcty2 resetbtn ifrm'.((@$ifrm['disabled']) ? ' disabled' : '').'" href="#" onClick="'.
        (($typ=='single') ? 'SNGifrm_reset();' : 'MLTifrm_reset(this, '.(($ifrm['NativeType']!=1) ? 0 : 1).');').
        ' return false;">'.
        '<span class="glyphicon glyphicon-remove"></span>'.
        '</a>';

    if ($typ=='multi') {

        $name = $ifrm['name'].'['.$ifrm['NativeType'].'][]';

        echo '<input type="hidden" name="'.$name.'" id="'.$name.'" value="'.$ifrm['cur']['value'].'">';
    }


    if ($wrap) {
        echo '</div></div>';
    }

}





/**
 * IFrame CONTROL output
 *
 * @param string $typ Page type: (single, single_js, multi_tbl, multi_div)
 *  single - add shadow submiter
 *  single_js - add nothing
 *  multi_tbl - add entire clone table around iframe
 *  multi_div - add clone div around iframe
 * @param array|void $ifrm IFrame data
 * @return void
 */
function ctrl_ifrm($typ, $ifrm=null) {

    global $bs_css; // Dimensions array for BS column classes (return array from bs_grid_css())


    switch ($typ) {

        case 'single':
        case 'single_js':
            echo '<div class="row"><div class="ifrm_wrap '.$bs_css['panel_w'].'">';
            break;

        case 'multi_tbl':
            echo
                '<table id="ifrmctrl" style="display:none;"><tr>'.
                    '<td class="ghost"></td>'.
                    '<td colspan="4" class="ifrm_td">';
            break;

        case 'multi_div':
            echo
                '<div id="ifrmctrl" style="display:none;">'.
                    '<div class="row"><div class="ifrm_wrap '.$bs_css['panel_w'].'">';
            break;
    }


    echo
        '<iframe id="ifrmtunel" name="ifrmtunel" src="about:blank" scrolling="no" '.
            'onload="setIframeHeight(this)"></iframe>';
    // JS:setIframeHeight() will refresh iframe's height, and it is called when the iframe's src url is changed thus
    // the new page is loaded.


    if ($typ=='single') {
        echo '<input type="hidden" name="'.$ifrm['name'].'" id="'.$ifrm['name'].'" value="'.$ifrm['cur']['value'].'">';
    }


    switch ($typ) {

        case 'single':
        case 'single_js':
            echo '</div></div>';
            break;

        case 'multi_tbl':
            echo
                    '</td>'.
                    '<td class="ghost"></td>'.
                '</tr></table>';
            break;

        case 'multi_div':
            echo '</div></div></div>';
            break;
    }
}








