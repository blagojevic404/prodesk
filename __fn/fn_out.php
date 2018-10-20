<?php


// OUTPUT




/**
 * Assembles class strings for bs grid
 *
 * @param array $dim_arr Dimensions array with class prefixes (lg, md, sm, xs) as possible keys. Each of those contains
 *  array of values for use-cases (panel_w, cln_l, cln_r). Eg:
 *  [   [md] => [   [panel_w] => 11 [cln_l] => 3 [cln_r] => 7   ]
 *      [lg] => [   [panel_w] => 12 [cln_l] => 3 [cln_r] => 9   ]   ]
 *
 * @param string $r_key Return key, if we want to return a string for a *single* use-case, instead of array.
 *
 * @return array $r Array with use-cases (panel_w, cln_l, cln_r) as keys and class strings as values
 *  [   [panel_w] => col-md-11 col-lg-12        // panel width
 *      [cln_l]   => col-md-3 col-lg-3          // form left column (labels)
 *      [cln_r]   => col-md-7 col-lg-9 ]        // form right column (controls)
 */
function bs_grid_css($dim_arr, $r_key=null) {

    $r = [];

    foreach ($dim_arr as $dim_k => $dim_v) {

        foreach ($dim_v as $k => $v) {

            if ($v) {
                $r[$k][] = 'col-'.$dim_k.'-'.(($k=='offset') ? 'offset-' : '').$v;
            }
        }
    }

    foreach ($r as $k => $v) {

        $r[$k] = implode(' ', $r[$k]);
    }

    return (($r_key) ? $r[$r_key] : $r);
}








/**
 * Prints BS MODAL button (trigger) or field (window)
 *
 * @param string $htmltyp HTML type (button, modal)
 * @param string $casetyp Case type (deleter, poster, alerter, alphconv, printer)
 * @param array $opt Options data
 *  - name_prefix (optional)
 *  - data_varz
 *  - pms (permission)
 *  - button_txt (for init button)
 *  - button_title (for init button) optional
 *  - button_css (for init button) optional
 *  - button_css_not_btn - Whether button is not BS btn, but a simple href
 *  - cpt_header
 *  - txt_header - html for the header
 *  - txt_body (in *deleter* case, if null this is replaced by: txt_body_itemcpt, txt_body_itemtyp, txt_body_note)
 *  - txt_footer - Actually a submit button html. We can omit it and use following args instead:
 *      - submiter_href (href for submit button)
 *      - submiter_css (additional css for submit button) optional
 *  - cpt_cancel - Text for the Cancel button (default is *Cancel*)
 *  - submiter_href Deleter: href for submit button; Poster: href for the *form* tag.
 *        (We can omit, if we are going to set it via onshow_js, or if we want to point submit to the same script.)
 *  - modal_size - (modal-sm, modal-lg) - default is none, i.e. medium
 *  - onshow_js - Whether to use onshow JS (to change some attributes etc)
 * @param bool $output Whether to output (only for *button* htmltyp)
 *
 * @return void|string
 */
function modal_output($htmltyp, $casetyp, $opt=null, $output=true) {

    global $tx;


    if (in_array($casetyp, ['alerter', 'alphconv', 'printer'])) {
        $opt['pms'] = true;
    }

    if (!isset($opt['pms'])) {
        $opt['pms'] = false;
    }

    if (in_array($casetyp, ['alerter', 'alphconv', 'printer', 'deleter'])) {
        $opt['name_prefix'] = $casetyp;
    }

    if (!empty($opt['onshow_js'])) {

        $opt['name_prefix'] .= '_ONSHOW_JS_'; // We change the name in order to separate from *normal* modal

        if ($htmltyp=='modal') {
            $opt['pms'] = true; // pms is checked on the modal *button* level
        }
    }

    if ($casetyp=='alerter') {
        $opt['cpt_cancel'] = $tx['LBL']['close'];
    }



    // BUTTON

    if ($htmltyp=='button') {

        if (!isset($opt['button_txt'])) {

            switch ($casetyp) {

                case 'printer':  $opt['button_txt'] = '<span class="glyphicon glyphicon-print"></span>'; break;
                case 'deleter':  $opt['button_txt'] = '<span class="glyphicon glyphicon-remove"></span>'; break;
                case 'alphconv': $opt['button_txt'] = '<span class="glyphicon glyphicon-retweet"></span>'; break;
            }
        }

        $opt['button_css'] = (isset($opt['button_css'])) ? [$opt['button_css']] : [];

        if ($casetyp=='printer') {
            $opt['button_css'][] = 'btn-default btn-sm js_starter';
        }

        if ($casetyp=='deleter') {

            if (empty($opt['button_css_not_btn'])) {

                if (empty($opt['button_css'])) {
                    $opt['button_css'][] = 'btn-sm';
                }

                $opt['button_css'][] = 'btn-danger';
            }

            $opt['button_css'][] = 'dlt';
        }

        if (empty($opt['button_css_not_btn'])) {
            $opt['button_css'][] = 'btn';
        }

        if (!$opt['pms']) {
            @$opt['button_css'][] = 'disabled';
        }
    }



    // MODAL BODY

    if ($htmltyp=='modal') {

        switch ($casetyp) {

            case 'printer':

                $opt['cpt_header'] = $tx['LBL']['printer'];
                $opt['cpt_cancel'] = $tx['LBL']['close'];

                if ($_SESSION['BROWSER_TYPE']=='PRINT_1') {
                    $print_sizes = [7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17];
                    $def = 11;
                } else {
                    $print_sizes = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
                    $def = 16;
                }

                foreach ($print_sizes as $v) {
                    $opt['txt_body'][] = '<a type="button" href="#" class="btn btn-primary printer_btn'.(($v==$def) ? ' def' : '').'" '.
                        'data-dismiss="modal" onclick="printer('.$v.')">'.$v.'</a>';
                }

                if (@$opt['ctrl_cam_only']) {
                    $opt['txt_body'][] = '<div class="ctrl_cam_only"><a href="#" onclick="print_cam_only(this)">'.
                        $tx[SCTN]['LBL']['ctrl_cam_only'].'</a></div>';
                }

                $opt['txt_body'] = implode('', $opt['txt_body']);

                break;

            case 'deleter':

                $opt['cpt_header'] = $tx['MSG']['del_confirm'];

                if (empty($opt['txt_body'])) {
                    $opt['txt_body'] = $tx['MSG']['del_quest'].': ';
                }

                if (@$opt['txt_body_itemtyp']) {
                    $opt['txt_body'] .= '<span class="text-lowercase">'.$opt['txt_body_itemtyp'].'</span>';
                }

                if (@$opt['txt_body_itemcpt']) {
                    $opt['txt_body'] .= ' "'.$opt['txt_body_itemcpt'].'"'.'!';
                }

                if (isset($opt['txt_body_note'])) {
                    $opt['txt_body'] .= '<br><br>'.$opt['txt_body_note'];
                }

                break;

            case 'alphconv':

                $opt['cpt_header'] = $tx['LBL']['alphconv'];
                $opt['cpt_cancel'] = $tx['LBL']['close'];

                $righter = '<span class="glyphicon glyphicon-arrow-right"></span>';

                $opt['txt_body'] =
                    '<div class="btn-toolbar">'.
                    '<a type="button" href="#" class="btn btn-primary btn-sm" onclick="alphconv(\'up2low\')">АБВ '.
                        $righter.' Абв</a>'.
                    '<a type="button" href="#" class="btn btn-primary btn-sm" onclick="alphconv(\'lat2cyr\')">ABV '.
                        $righter.' АБВ</a>'.
                    '</div>'.
                    '<textarea id="alphconv" rows="7" class="form-control no_vert_scroll alphconv"'.expandTxtarea(6).'></textarea>';

                break;
        }
    }


    // MODAL FOOTER

    if ($htmltyp=='modal' && !isset($opt['txt_footer'])) {

        $opt['submiter_css'] = (isset($opt['submiter_css'])) ? [$opt['submiter_css']] : [];

        if (!$opt['pms']) {
            $opt['submiter_css'][] = 'disabled';
        }

        if ($casetyp=='deleter') {
            $opt['submiter_css'][] = 'btn-danger';
        }

        if (empty($opt['submiter_css'])) {
            $opt['submiter_css'][] = 'btn-primary';
        }

        $opt['submiter_css'][] = 'btn btn-sm text-uppercase';

        $opt['submiter_css'] = implode(' ',$opt['submiter_css']);

        if ($casetyp=='poster') {

            $opt['txt_footer'] = '<button type="submit"
                        class="'.$opt['submiter_css'].'">'.$tx['LBL']['continue'].'</button>';

        } elseif ($casetyp=='deleter') {

            $opt['txt_footer'] = '<a type="button" id="del_submit" href="'.@$opt['submiter_href'].'"
                                    class="'.$opt['submiter_css'].'">'.$tx['LBL']['continue'].'</a>';
        } else {

            $opt['txt_footer'] = '';
        }
    }


    // OUTPUT

    if ($htmltyp=='button') {


        $button_attr = 'data-toggle="modal" data-target="#'.$opt['name_prefix'].'Modal"';

        if (isset($opt['data_varz']) && is_array($opt['data_varz'])) {

            foreach ($opt['data_varz'] as $k => $v) {
                $varz[] = 'data-'.$k.'="'.$v.'"';
            }

            $data_attr = implode(' ', $varz);

        } else {
            $data_attr = '';
        }

        if ($casetyp=='alphconv') {

            $r = '<a '.$button_attr.' '.$data_attr.' class="flw">'.$opt['button_txt'].'</a>';

        } else {

            $r = '<a type="button" '.(isset($opt['button_title']) ? 'title="'.$opt['button_title'].'" ' : '').
                $button_attr.' '.$data_attr.' class="'.implode(' ', $opt['button_css']).'">'.$opt['button_txt'].'</a>';
        }

        if ($casetyp=='printer') {

            $r .= '<style type="text/css" media="print" id="printer">';

            // For old versions of Chromium, set base print size to 10pt (instead of default 16pt)
            if ($_SESSION['BROWSER_TYPE']=='PRINT_1') {
                $r .= 'table#epg_table td, table.mktplan td, div.prompter_print, div.atom_dtl '.
                    '{ font-size: 10pt !important; }';
            }

            $r .= '</style>';
        }


        if ($output) {
            echo $r;
        } else {
            return $r;
        }


    } else { // modal


        if ($casetyp=='poster') {
            form_tag_output('open', @$opt['submiter_href'], false);
        }

        $fade = ($casetyp!='printer') ? 'fade' : '';
        // If printer modal uses fade out, then printing is triggered before the modal is completely hidden
        // and then it leaves a small right margin on printed page. Therefore we dont fade the printer modal.

        ?>
        <div class="modal <?=$fade?>" id="<?=$opt['name_prefix']?>Modal" tabindex="-1" role="dialog">
        <div class="modal-dialog<?=((isset($opt['modal_size'])) ? ' '.$opt['modal_size'] : '')?>">
        <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <?=@$opt['txt_header']?>
            <h4 class="modal-title" id="<?=$opt['name_prefix']?>ModalLabel"><?=@$opt['cpt_header']?></h4>
        </div>
        <?=(!empty($opt['txt_body']) ? '<div class="modal-body">'.$opt['txt_body'].'</div>' : '')?>
        <div class="modal-footer">
            <button type="button" class="btn btn-default btn-sm text-uppercase"
             data-dismiss="modal"><?=(empty($opt['cpt_cancel']) ? $tx['LBL']['cancel'] : $opt['cpt_cancel'])?></button>
            <?=$opt['txt_footer']?>
        </div>
        </div></div></div>
        <?php

        if ($casetyp=='poster') {
            form_tag_output('close');
        }
    }
}





/**
 * Outputs a specific detail for details page
 *
 * @param array $opt Options data
 *  - lbl (string) - Label
 *  - txt (string) - Text
 *  - css (string) - Additional css class
 *  - layout (string) - Form layout: horizontal, vertical
 *  - tag (string) - Details tag: div, pre
 *  - val (bool) - Value for SETTINGS case
 *
 * @return void
 */
function detail_output($opt) {

    if (isset($opt['val'])) {

        $opt['val'] = $opt['val'] ? 'ok' : 'ban';

        $opt['txt'] = '<span class="glyphicon glyphicon-'.$opt['val'].'-circle"></span>'.$opt['lbl'];
        $opt['lbl'] = '&nbsp;';
    }

    detail_output_broken('open', $opt);

    echo $opt['txt'];

    detail_output_broken('close', $opt);
}



/**
 * Outputs a specific detail for details page, broken into two parts: before VALUE and AFTER value. This is necessary
 * when VALUE contains some larger programming logic etc.
 *
 * @param string $typ
 * @param array $opt Options data
 *  - lbl (string) - Label
 *  - txt (string) - Text
 *  - css (string) - Additional css class
 *  - layout (string) - Form layout: horizontal, vertical
 *  - tag (string) - Details tag: div, pre
 *  - attr (string) - Additional attributes
 *
 * @return void
 */
function detail_output_broken($typ, $opt=null) {

    global $bs_css; // Dimensions array for BS column classes (return array from bs_grid_css())

    if (!isset($opt['css']))        $opt['css'] = '';
    if (!isset($opt['attr']))       $opt['attr'] = '';
    if (!isset($opt['layout']))     $opt['layout'] = 'horizontal';
    if (!isset($opt['tag']))        $opt['tag'] = 'div';


    if ($typ=='open') {

        echo '<div class="form-group">'.PHP_EOL;

        if ($opt['lbl']) {
            echo '<label'.(($opt['layout']=='horizontal') ? ' class="control-label '.$bs_css['cln_l'].'"' : '').'>'.
                $opt['lbl'].'</label>'.PHP_EOL;
        }

        if ($opt['layout']=='horizontal') {
            echo '<div class="'.$bs_css['cln_r'].'">';
        }

        echo '<'.$opt['tag'].' class="detaildiv'.(($opt['css']) ? ' '.$opt['css'] : '').'" '.$opt['attr'].'>';

    } else { // $typ=='close'

        echo ((empty($opt['txt'])) ? '&nbsp;' : '').'</'.$opt['tag'].'>';

        if ($opt['layout']=='horizontal') {
            echo '</div>';
        }

        echo PHP_EOL.'</div>'.PHP_EOL;
    }
}






/**
 * Outputs form control
 *
 * @param string $case Case (form, modal)
 * @param string $typ Control type: (textbox, textarea, textarea_no_js, select, select-txt, select-db, select-prg,
 *                    block, radio, radio-vert, hms, chk, chk-list, textbox-list, number, password)
 * @param string $caption Caption (used only if $wrap is set to TRUE, except for 'select-prg' type)
 * @param string $name Control Name/ID
 * @param string $value Value (for block: a html block)
 *
 * @param string|array|int $attr Additional attributes
 *  - for textbox, block: simple *insert* string
 *  - for textarea, textarea_no_js: int rowcount
 *  - for select-txt: array:
 *                      ctg_name - name of the array to fetch from txt file;
 *                      allow_none - whether it is allowed to not select any value
 *                      src_type - ['arrays', 'CFGARR_GLOBAL', 'CFGARR_LOCAL'] - txt file source
 *  - for select-db: sql query
 *  - for select: txt (e.g. previously fetched array and converted to html)
 *  - for select-prg: int ChannelID
 *  - for hms: null
 *  - for radio, radio-vert: array (buttons)
 *  - for chk-list, textbox-list: array
 *
 * @param array $opt Optional additional data
 *  - nowrap (bool) - Whether to omit *wrap* html
 *  - txt_nolabel (bool) - Whether to print textbox caption in placeholder instead of in label
 *  - vertical (bool) - Use vertical layout
 *  - prg-mktplan (bool) - Whether to use mktplan-specific *select-prg*
 *
 * @return void|string $r If *$wrap* is false, the result is returned as string instead of being printed
 */
function ctrl($case, $typ, $caption, $name, $value=null, $attr='', $opt=null) {

    global $tx;
    global $bs_css; // Dimensions array for BS column classes (return array from bs_grid_css())


    switch ($typ) {

        case 'textbox':
        case 'number':
        case 'password':

            if (!empty($opt['txt_nolabel'])) {
                $nolabel = true;
                $attr .= ' placeholder="'.$caption.'"';
            }

            $z = '<input type="'.(($typ=='textbox') ? 'text' : $typ).'" name="'.$name.'" id="'.$name.'" '.
                'value="'.$value.'" '.$attr.' class="form-control">';

            break;

        case 'textarea':
            $z = '<textarea name="'.$name.'" id="'.$name.'" class="form-control no_vert_scroll" rows="'.$attr.'"'.
                expandTxtarea($attr-1).'>'.$value.'</textarea>';
            break;

        case 'textarea_no_js': // without expandTxtarea JS
            $z = '<textarea name="'.$name.'" id="'.$name.'" class="form-control" rows="'.$attr.'">'.$value.'</textarea>';
            break;

        case 'select':
            $z = '<select name="'.$name.'" id="'.$name.'" '.$attr.' class="form-control">'.$value.'</select>';
            break;

        case 'select-txt':

            if (empty($attr['src_type'])) {
                $arr_src = 'arrays';
            } else {
                $arr_src = wash('arr_assoc', $attr['src_type'], ['CFGARR_GLOBAL', 'CFGARR_LOCAL', 'arrays'], 'arrays');
            }

            if ($arr_src=='CFGARR_GLOBAL') {
                $arr = cfg_global('arrz', $attr['ctg_name']);
            } elseif ($arr_src=='CFGARR_LOCAL') {
                $arr = cfg_local('arrz', $attr['ctg_name']);
            } elseif ($arr_src=='arrays') {
                $arr = txarr('arrays', $attr['ctg_name']);
            }

            $z = '<select name="'.$name.'" id="'.$name.'" class="form-control">'.
                arr2mnu($arr, $value, ((@$attr['allow_none']) ? $tx['LBL']['undefined'] : '')).
                '</select>';

            break;

        case 'select-db':
            $z = '<select name="'.$name.'" id="'.$name.'" class="form-control">'.
                arr2mnu(qry_numer_arr($attr['sql']), $value, @$attr['zero_txt']).
                '</select>';
            break;

        case 'select-prg':

            $z = ctrl_prg($name, $value, ['cpt' => $caption, 'chnl' => $attr,
                'typ' => ((empty($opt['prg-mktplan'])) ? null : 'mktplan')]);

            if ($case=='modal') {
                $nolabel = (!empty($opt['prg-mktplan'])) ? true : false;
            }

            break;

        case 'block':
            $z = $value;
            break;

        case 'radio':

            $z = btngroup_builder($name, $attr, $value);

            if ($case=='modal') {
                $nolabel = (empty($caption)) ? true : false;
            }

            break;

        case 'radio-vert':
            $z = btngroup_builder($name, $attr, $value, 'btn-group-vertical');
            break;

        case 'hms':
            $z = form_hms_output('normal', $name, $value);
            break;

        case 'chk':

            $label = $caption;
            $caption = '&nbsp;';

            $checked = ($value) ? ' checked' : '';

            $z = '<div class="checkbox chkpretty '.$checked.'"><label '.$attr.' class="form-control">'.
                '<input type="checkbox" name="'.$name.'" id="'.$name.'" value="1"'.$attr.$checked.'>'.$label.
                '</label></div>';

            if ($case=='modal') {
                $nolabel = true;
            }

            break;

        case 'chk-list':

            $z = [];
            foreach($attr as $k => $v) {
                $checked = (in_array($k, $value)) ? ' checked' : '';
                $z[] =
                    '<div class="checkbox"><label>
                    <input name="'.$name.'['.$k.']" id="'.$name.'['.$k.']" value="1" type="checkbox" title=""'.$checked.'>'.
                    $v.'</label></div>';
            }
            $z = implode('',$z);
            break;

        case 'textbox-list':

            $z = [];
            foreach($attr as $k => $v) {
                $z[] = '<div class="bc_mdf form-inline"><input type="text" name="'.$name.'['.$k.']" id="'.$name.'['.$k.']" '.
                    'value="'.@$value[$k].'" class="form-control">'.$v.'</div>';
            }
            $z = implode('',$z);
            break;

    }


    if (empty($opt['nowrap'])) {

        // WRAP CODE

        $r = '<div class="form-group">';

        if ($case=='form' && $caption) {
            $r .= '<label'.
                ((!empty($name)) ? ' for="'.$name.(($typ=='hms') ? '-hh' : '').'"' : '').
                ((empty($opt['vertical'])) ? ' class="control-label '.$bs_css['cln_l'].'"' : '').
                '>'.$caption.'</label>';
        }

        if (empty($opt['vertical'])) {

            if ($case=='form') {

                switch ($typ) {

                    case 'block':
                        $r .= '<div class="'.$bs_css['cln_r'].' '.$attr.'">';
                        break;

                    case 'hms':
                        $r .= '<div class="'.$bs_css['cln_r'].' form-inline">';
                        break;

                    default:
                        $r .= '<div class="'.$bs_css['cln_r'].'">';
                }

            } else { // modal

                if (empty($nolabel)) {
                    $r .= '<label for="'.$name.'" class="control-label">'.$caption.'</label>';
                }

                $r .= '<div>';
            }
        }

        $r .= $z;

        if (empty($opt['vertical'])) {
            $r .= '</div>';
        }

        $r .= '</div>'.PHP_EOL;

    } else {

        $r = $z;
    }


    // OUTPUT/RETURN

    if (!isset($opt['rtyp'])) {
        if ($case=='form') {
            $opt['rtyp'] = 'output';
        } else { // modal
            $opt['rtyp'] = 'return';
        }
    }

    if ($opt['rtyp']=='output') {
        echo $r;
    } else {
        return $r;
    }
}




/**
 * Assembles html for HMS textboxes
 *
 * @param string $typ Type: (normal, spice, spice-label, film, film_episodes, film_episodes_master,
 *                          desk, desk-label, desk-atom, cover)
 * @param string $name Name base for the element (for submit)
 * @param array $arr_values Array with values
 * @param string $btn_reset_typ RESET button type (none, term, dur)
 * @param bool $ff Add frames textbox
 *
 * @return string $html Html for the output
 */
function form_hms_output($typ, $name, $arr_values, $btn_reset_typ='', $ff=false) {

    global $cfg;

    if (in_array($typ, ['desk', 'desk-label', 'desk-atom', 'spice', 'spice-label'])) {
        $hms = ['mm', 'ss'];
    } else {
        $hms = ['hh', 'mm', 'ss'];
    }

    if ($cfg['dur_use_milli'] && $ff) {
        $hms[] = 'ff';
    }

    $html = '';
    $tboxz = [];


    if ($typ=='film_episodes') {

        static $episode_cnt = 0;
        // We have to keep the episode count, and add it to the control name. I used static variables to achieve that.

        if ($name=='DurApprox') $episode_cnt++;
        // The function will be called for DurApprox and for DurReal controls. Each call would increment episode count,
        // so I had to add this quirk, to increment only once in a row.
    }


    foreach ($hms as $v) {

        $css = ($v=='ff') ? ' hms_ff' : null;

        switch ($typ) {

            case 'normal':
                $tboxz[] =
                    '<input type="text" name="'.$name.'-'.$v.'" id="'.$name.'-'.$v.'" '.
                    'value="'.$arr_values[$v].'" size="1" maxlength="2" class="form-control">';
                break;

            case 'film':
            case 'spice':
            case 'desk':
            case 'cover':
                $tboxz[] =
                    '<input type="text" name="'.$name.strtoupper($v).'" id="'.$name.strtoupper($v).'" '.
                    'value="'.$arr_values[$v].'" size="1" maxlength="2" class="form-control'.$css.'">';
                break;

            case 'desk-atom':
                $tboxz[] =
                    '<input type="text" name="'.sprintf($name, strtoupper($v)).'" id="'.sprintf($name, strtoupper($v)).'" '.
                    'value="'.$arr_values[$v].'" size="1" maxlength="2" class="form-control">';
                break;

            case 'spice-label':
            case 'desk-label':
                $tboxz[] =
                    '<input type="text" name="'.$name.strtoupper($v).'" id="'.$name.strtoupper($v).'" '.
                    'value="'.$arr_values[$v].'" size="1" maxlength="2" class="form-control durcalc-lbl" disabled>';
                break;

            case 'film_episodes':
                $tboxz[] =
                    '<input type="text" name="'.$name.strtoupper($v).'['.$episode_cnt.']" id="'.$name.strtoupper($v).'" '.
                    'value="'.$arr_values[$v].'" size="1" maxlength="2" class="form-control">';
                break;

            case 'film_episodes_master':
                $tboxz[] =
                    '<input type="text" name="m-'.$name.strtoupper($v).'" id="'.$name.strtoupper($v).'" '.
                    'value="00" size="1" maxlength="2" class="form-control">';
                break;
        }
    }

    if ($btn_reset_typ && $btn_reset_typ!='none') {

        $html .=
            '<a class="text-muted opcty2 resetbtn hms" href="#" '.
            'onClick="hms_reset(this, \''.(($btn_reset_typ=='term') ? 'term' : 'dur').'\'); return false;">'.
            '<span class="glyphicon glyphicon-remove"></span></a>';
    }

    $html .= implode(':', $tboxz);

    return $html;
}





/**
 * Outputs panel html
 *
 * @param string $typ Position: (head, head-dtlform, foot)
 *  - head - Normal head, i.e. top part of the panel code
 *  - head-dtlform - Add "form-horizontal" css to PANEL div in order to imitate BS form-horizontal layout
 *  - foot - Normal foot, i.e. bottom part of the panel code
 * @param string $css Optional css classname
 * @return void
 */
function form_panel_output($typ, $css='') {

    global $bs_css; // Dimensions array for BS column classes (return array from bs_grid_css())


    switch ($typ) {

        case 'head':

            echo '<div class="row">';
            echo '<div class="panel panel-default '.$bs_css['panel_w'].'">'.
                '<div class="panel-body '.$css.'">';
            break;

        case 'head-dtlform':

            echo '<div class="row">';
            echo '<div class="panel panel-default '.$bs_css['panel_w'].' form-horizontal">'.
                '<div class="panel-body '.$css.'">';
            break;

        case 'foot':

            echo '</div></div>';
            echo '</div>';
            break;
    }
}







/**
 * Outputs accordion html
 *
 * @param string $pos Position: (head, foot)
 * @param string $caption Caption (only for HEAD)
 * @param string $name Name prefix used for id attributes (only for HEAD)
 * @param array $opt Options data
 *  - type (string):
 *      - normal - Normal accordion
 *      - dtlform - Add "form-horizontal" css to PANEL div in order to imitate BS form-horizontal layout
 *      - tbl - Skip adding *panel-body* because we are going to put table in place of panel body
 *  - collapse (bool) - Whether to initially collapse or not (only for HEAD)
 *  - css (string) - Optional additional css class for the panel *frame*
 *  - css_heading (string) - Optional additional css class for the panel *heading*
 *  - css_body (string) - Optional additional css class for the panel *body*
 *  - righty (string) - Optional code block which goes to right corner of the heading
 *
 * @return void
 */
function form_accordion_output($pos, $caption='', $name='', $opt=null) {

    global $bs_css; // Dimensions array for BS column classes (return array from bs_grid_css())

    if (!$bs_css) {

        $dim_arr = [
            'xs' => ['panel_w' => 12, 'cln_l' => 3, 'cln_r' => 9],
        ];
        // Turn dimensions into classname strings
        $bs_css = bs_grid_css($dim_arr);
    }

    if (!isset($opt['type']))        $opt['type'] = 'normal';
    if (!isset($opt['collapse']))    $opt['collapse'] = true;
    if (!isset($opt['css']))         $opt['css'] = '';
    if (!isset($opt['css_heading'])) $opt['css_heading'] = '';
    if (!isset($opt['css_body']))    $opt['css_body'] = '';
    if (!isset($opt['righty']))      $opt['righty'] = '';


    if ($pos=='head') {

        echo // Panel FRAME OPEN
        '<div class="row panel-group '.$opt['css'].'" id="'.$name.'_accordion" role="tablist">
            <div class="'.$bs_css['panel_w'].' panel panel-default'.(($opt['type']=='dtlform') ? ' form-horizontal' : '').'">';

        echo // Panel HEADING
        '<div class="panel-heading '.$opt['css_heading'].'" role="tab" id="'.$name.'_heading">
            <h4 class="panel-title">
                <a data-toggle="collapse" data-parent="#'.$name.'_accordion" href="#'.$name.'_collapse">'.$caption.'</a>'.
                ((@$opt['versions']) ? logzer_output('btn', null, ['btn_type' => 'versions']) : '').
                (($opt['righty']) ? '<div class="pull-right">'.$opt['righty'].'</div>' : '').'
            </h4>
        </div>';

        echo // Panel BODY OPEN
        '<div id="'.$name.'_collapse" class="panel-collapse collapse '.$opt['css_body'].(($opt['collapse']) ? ' in' : '').'" '.
            'role="tabpanel">';

        if ($opt['type']!='tbl') {
            echo '<div class="panel-body">';
        }

    } else { // FOOT

        // Panel BODY CLOSE
        if ($opt['type']!='tbl') {
            echo '</div>';
        }

        echo '</div>';

        // Panel FRAME CLOSE
        echo '</div></div>';
    }
}







/**
 * Outputs form tag html
 *
 * @param string $typ Tag type: (open, close)
 * @param string $href_submit Submit url (only for *open* tag)
 * @param bool $horizontal Whether to use .form-horizontal BS CSS
 * @param string $name Name
 *
 * @return void
 */
function form_tag_output($typ, $href_submit='', $horizontal=true, $name='form1') {

    global $header_cfg;

    // $header_cfg['form_checker'] determines whether to add submit check procedure
    // $header_cfg['js_onsubmit'] determines whether to add js_onsubmit() call

    if ($typ=='open') {

        // FORM start
        echo PHP_EOL.'<form action="'.$href_submit.'" id="'.$name.'" name="'.$name.'" autocomplete="off" '.
            'method="post" enctype="multipart/form-data" onSubmit="'.
            ((isset($header_cfg['js_onsubmit']) && is_array($header_cfg['js_onsubmit'])) ? 'js_onsubmit();' : '').
            ((isset($header_cfg['form_checker']) && is_array($header_cfg['form_checker'])) ? 'return checker();' : '').'"'.
            (($horizontal) ? ' class="form-horizontal"' : '').'>'.PHP_EOL;

    } else { // CLOSE

        echo PHP_EOL.'</form>'.PHP_EOL;
    }
}





/**
 * Outputs HIDDEN form control
 *
 * @param string $name Name
 * @param string $value Value
 * @param bool $output Whether to output
 *
 * @return void|string
 */
function form_ctrl_hidden($name, $value, $output=true) {

    $r = '<input type="hidden" name="'.$name.'" id="'.$name.'" value="'.$value.'">';

    if ($output) {
        echo $r;
        return null;
    } else {
        return $r;
    }
}





/**
 * Outputs submit button html
 *
 * @param string $name Name attribute
 * @param array $opt Options data
 *  - type (string):
 *      - 'row' - button in a BS wide row
 *      - 'btn_only' - button only
 *  - css (string) - Optional css class
 *  - btn_txt (string) - Button text
 *  - sec (bool) - Whether this is *second* submit button (and therefore needs onclick event for its shadow)
 * @param bool $output Whether to output (only for *button* htmltyp)
 *
 * @return void|string
 */
function form_btnsubmit_output($name='Submiter', $opt=null, $output=true) {

    global $bs_css; // Dimensions array for BS column classes (return array from bs_grid_css())
    global $tx;

    if (!isset($opt['type']))        $opt['type'] = 'row';
    if (!isset($opt['css']))         $opt['css'] = '';
    if (!isset($opt['btn_txt']))     $opt['btn_txt'] = $tx['LBL']['save'];
    if (!isset($opt['sec']))         $opt['sec'] = false;


    switch ($opt['type']) {

        case 'row':
            $r = '<div class="row">'.
                    '<input type="submit" class="btn btn-primary btn-lg text-uppercase '.$bs_css['panel_w'].'" '.
                        'name="'.$name.'" value="'.$opt['btn_txt'].'">'.
                '</div>';
            break;

        case 'btn_only':
            $r = '<input type="submit" class="btn btn-primary text-uppercase '.$opt['css'].'" '.
                (($opt['sec']) ? 'onclick="this.parentNode.querySelector(\'input[type=hidden]\').name=this.name" ' : '').
                'name="'.$name.'" value="'.$opt['btn_txt'].'">';
    }


    $r .= '<input type="hidden" name="'.((!$opt['sec']) ? $name : '').'" value="1">';

    // Submit button will be DISABLED on form submit by prevent_double_submit() JS function, and by that it will be
    // DELETED from POST variables. Yet, RCV php scripts count on this variable in order to be triggered.
    // Thus, we must add identical hidden control, i.e. shadow.
    //
    // Note: When TWO submit buttons are used, their shadows would both send a switch to rcv script.
    // To avoid that, we omit name from the second shadow, and we write it via onclick event on that button,
    // i.e. we turn on the second shadow ony when the second button is clicked.
    // Otherwise the rcv script would always think that the form was submitted via second button.


    if ($output) {
        echo $r;
        return null;
    } else {
        return $r;
    }
}








/**
 * Assembles html for BS button group
 *
 * @param string $name Name for the element (for submit)
 * @param array $arr_btnz Array with values
 * @param int $k_sel Selected index/key
 * @param string $css CSS class, if we want to change the default one
 * @param array $arr_disabled Array with disabled values, if any
 * @param array $js_onclick Additional JS-ONCLICK function, if any
 *
 * @return string $html Html for the output
 */
function btngroup_builder($name, $arr_btnz, $k_sel, $css=null, $arr_disabled=null, $js_onclick=null) {

    if ($css===null){
        $css = 'btn-group-justified';
    }

    $disabl = '';

    // Shadow submitter
    $html = '<input type="hidden" name="'.$name.'" id="'.$name.'" value="'.$k_sel.'">';

    $html .= '<div class="btn-group '.$css.'" data-toggle="buttons">';

    foreach ($arr_btnz as $k => $v) {

        if ($arr_disabled) {
            $disabl = (@$arr_disabled[$k]) ? ' disabled="disabled"' : '';
        }

        $html .=
            '<label onclick="btngroup_shadow(this);'.$js_onclick.'" class="btn btn-default'.
            (($k_sel==$k) ? ' active' : '').'"'.$disabl.'>'.
            '<input type="radio" role="button" name="'.$name.$k.'" id="'.$name.$k.'" value="'.$k.'">'.
            $v.
            '</label>';
    }

    $html .= '</div>';

    return $html;
}






/**
 * Outputs html for CRUMBS
 *
 * @param string $typ Type: (open, close, item)
 * @param string $txt Text (only for type *item*)
 * @param string $href Url (only for type *item*)
 * @param string $css CSS classname (only for type *item*)
 *  note: Use "pull-right righty" for items which you want to pull right (*righty* is to lose the divider).
 *
 * @return void
 */
function crumbs_output($typ, $txt='', $href='', $css='') {

    global $bs_css; // Dimensions array for BS column classes (return array from bs_grid_css())

    switch ($typ) {

        case 'open':

            echo '<div class="row hidden-print"><ol class="breadcrumb '.$bs_css['panel_w'].'">'.PHP_EOL;
            break;

        case 'close':

            echo '</ol></div>';
            break;

        case 'item':

            echo '<li'.(($css) ? ' class="'.$css.'"' : '').'>';

            if ($href) {
                echo '<a href="'.$href.'">';
            }

            echo $txt;

            if ($href) {
                echo '</a>';
            }

            echo '</li>'.PHP_EOL;

            break;
    }
}







/**
 * Outputs html for HEADBAR
 *
 * @param string $typ Type: (open, close)
 * @param array $opt Options array (only for type *open*): title(str), subtitle(str), toolbar(bool), toolbar_doprint(bool)
 * @return void
 */
function headbar_output($typ, $opt=null) {

    global $bs_css; // Dimensions array for BS column classes (return array from bs_grid_css())

    switch ($typ) {

        case 'open':

            echo '<div class="row"><div class="well well-sm headbar '.$bs_css['panel_w'].'">';

            echo '<div><h2>'.$opt['title'];

            if (isset($opt['subtitle']) && $opt['subtitle']) {
                echo ' <small>/'.$opt['subtitle'].'/</small>';
            }

            echo '</h2></div>';

            if (isset($opt['toolbar']) && $opt['toolbar']) {
                echo '<div class="pull-right btn-toolbar'.(isset($opt['toolbar_doprint']) ? '' : ' hidden-print').'">';
            }

            break;

        case 'close':

            if (isset($opt['toolbar']) && $opt['toolbar']) {
                echo '</div>';
            }

            echo '</div></div>';

            break;
    }
}




/**
 * Outputs html for LOGS
 *
 * @param string $typ Type: (btn, box, js, tbl, tbl-epg-get, tbl-epg-put)
 * - btn: Button which reloads page, with *log* $_GET variable, which will then turn on the logs accordion.
 *        Used only in EPG details, because logs data for epg has to be fetched through complicated procedure,
 *        and we want to avoid it if it is not specificaly needed.
 * - box: Accordion, with a placeholder where the logs data will be displayed through ajax on *collapse* event
 * - js: JS data with goes into header config on each page which displays logs
 * - tbl: Table with logs data. Used only in logs ajax script.
 * - tbl-epg-get: Reads logs for specified epg or scenario or fragment and adds them to logs session array
 * - tbl-epg-put: Table with logs data for epg. Used only in epg details script. (Prints content of $_SESSION['epglog'])
 *
 * @param array $x Item array
 * @param array $opt Options array:
 * - righty_skip (bool) - Only for *box* type
 * - btn_type (string) - Only for *btn* type
 *
 * @return void|string
 */
function logzer_output($typ, $x, $opt=null) {

    global $tx;


    switch ($typ) {

        case 'btn':

            $log_href = ($opt['btn_type']=='versions') ? '&versions=1#versions' : '&log=1#log';

            $log_text = ($opt['btn_type']=='versions') ? $tx[SCTN]['LBL']['versions'] : $tx['LBL']['logs'];

            $log_css = ($opt['btn_type']=='versions') ? 'btn_versions' : 'pull-left';

            $logzer_btn =
                '<a type="button" href="?'.$_SERVER["QUERY_STRING"].$log_href.'" '.
                'class="'.$log_css.' btn btn-xs btn-primary opcty3 satrt text-uppercase">'.
                $log_text.'</a>';

            return $logzer_btn;


        case 'box':

            if (!empty($opt['righty_skip'])) {

                $righty = '';

            } else {

                $righty = '<span class="text-uppercase">'.uid2name($x['UID']).'</span>'.
                    '<span class="glyphicon glyphicon-option-vertical"></span>'.
                    '<span>'.$x['TermAdd'].'</span>';
            }

            form_accordion_output('head', $tx['LBL']['logs'], 'logs',
                ['type'=>'tbl', 'collapse' => false, 'righty' => $righty, 'versions' => @$opt['versions']]);

            echo '<div id="logz_text"><span class="glyphicon glyphicon-hourglass"></span></div>';

            form_accordion_output('foot');

            break;


        case 'js':

            $ajax['type'] = 'GET';

            $ajax['url'] = '/_ajax/_aj_logz.php';   // ajax url

            $ajax['data'] = 'xid='.$x['ID'].'&tblid='.tablez('id', $x['TBL']).'&limit=50';

            $ajax['fn'] = 'placeholder_filler';             // js function to be called on ajax success

            $ajax['fn_arg'] = 'logz_text';                   // argument to pass along with the js function on ajax success


            $logzer_js = "$('#logs_accordion').on('show.bs.collapse', function() {".
                "ajaxer('{$ajax['type']}', '{$ajax['url']}', '{$ajax['data']}', '{$ajax['fn']}', '{$ajax['fn_arg']}');".
                "});";

            return $logzer_js;


        case 'tbl':

            $actions = txarr('arrays', 'log_actions');

            $x['tbl'] = tablez('name', $x['tblid']);

            if ($x['tbl']=='stryz') {
                $phases	= txarr('arrays', 'dsk_nwz_phases');
            }


            $sql = 'SELECT ID, ActionID, UID, TermAdd FROM log_qry '.
                'WHERE TableID='.$x['tblid'].' AND XID='.$x['xid'].' ORDER BY ID DESC LIMIT '.$x['limit'];

            $result = qry($sql);

            $html = [];

            while ($line = mysqli_fetch_assoc($result)) {

                if (UZID==UZID_ALFA) {
                    $line['SQL'] = rdr_cell('log_sql', 'qrySQL', $line['ID']);       // Read SQL from log
                }


                // TXT-ARR [log_actions] keys 100-110 are reserved for phases

                if ($line['ActionID']>=100 && $line['ActionID']<=110) {
                    $actn = '<span class="glyphicon glyphicon-hand-right"></span>'.$phases[$line['ActionID']-100];
                } else {
                    $actn = $actions[$line['ActionID']];
                }


                $html[] = '<tr>'.
                    '<td class="col-xs-1 action text-uppercase">'.$actn.'</td>'.
                    '<td class="col-xs-2 term">'.$line['TermAdd'].'</td>'.
                    '<td class="col-xs-2">'.uid2name($line['UID']).'</td>'.
                    ((UZID==UZID_ALFA) ? '<td class="col-xs-7 sql">'.$line['SQL'].'</td>' : '').
                    '</tr>';
            }

            if (!$html) {

                return '<div>-----</div>';

            } else {

                $html =
                    '<table class="table table-hover listingz">'.
                    '<tr><th>'.$tx['LBL']['type'].'</th><th>'.$tx['LBL']['time'].'</th><th>'.$tx['LBL']['author'].'</th>'.
                        ((UZID==UZID_ALFA) ? '<th>SQL</th>' : '').'</tr>'.
                    implode(PHP_EOL, $html).
                    '</table>';

                return $html;
            }


        case 'tbl-epg-get':

            switch ($x['obj_typ']) {
                case 'epg':			    $x['tbl'] = 'epgz';                 break;
                case 'element':		    $x['tbl'] = 'epg_elements';         break;
                case 'fragment':	    $x['tbl'] = 'epg_scnr_fragments';   break;
                default: return null; // Error-catcher: this should never happen
            }

            $x['tblid'] = tablez('id', $x['tbl']);

            $sql = 'SELECT ID, XID, TableID, UID, ActionID, TermAdd FROM log_qry '.
                'WHERE XID='.$x['obj_id'].' AND TableID='.$x['tblid'].' ORDER BY ID DESC';

            $result = qry($sql);

            while ($line = mysqli_fetch_assoc($result)) {
                $_SESSION['epglog'][] = $line;
            }

            // We use SESSION variable because this function will be called multiple times from inside epg_dtl_html()
            // in order to get logs for each element/fragment,
            // and then later finally it will also be called from epg.php to get logs for epg/scn generally

            // $_SESSION['epglog'] is initialized at the beginning of epg.php

            break;


        case 'tbl-epg-put':

            $actions = txarr('arrays', 'log_actions');


            // This is for *sorting* purposes

            $log_queue = [];

            foreach ($_SESSION['epglog'] as $k => $v) {
                $log_queue[strtotime($v['TermAdd']).$v['TableID']] = $k;
            }

            if (!$log_queue) {
                return '<div>-----</div>';
            }

            krsort($log_queue); // Sort an array by key in reverse order


            $tblid['epg'] = tablez('id', 'epgz');
            $tblid['element'] = tablez('id', 'epg_elements');
            $tblid['fragment'] = tablez('id', 'epg_scnr_fragments');


            foreach ($log_queue as $k) {

                $v = $_SESSION['epglog'][$k];

                if (UZID==UZID_ALFA) {
                    $v['SQL'] = rdr_cell('log_sql', 'qrySQL', $v['ID']);       // Read SQL from log
                }

                switch ($v['TableID']) {
                    case $tblid['epg']:		    $typ = 'epg';           break; 	// epgz
                    case $tblid['element']:		$typ = 'element';       break; 	// epg_elements
                    case $tblid['fragment']:	$typ = 'fragment';      break; 	// epg_scnr_fragments
                    default: exit('Log_Output_Error'); // Error-catcher: this should never happen
                }

                $html[] = '<tr>'.
                    '<td class="col-xs-1">'.(($typ=='epg' || (TYP!='epg' && $typ=='element')) ? '-' : $v['XID']).'</td>'.
                    '<td class="col-xs-1 text-uppercase">'.$actions[$v['ActionID']].'</td>'.
                    '<td class="col-xs-2 term">'.$v['TermAdd'].'</td>'.
                    '<td class="col-xs-2">'.uid2name($v['UID']).'</td>'.
                    ((UZID==UZID_ALFA) ? '<td class="col-xs-7 sql">'.$v['SQL'].'</td>' : '').
                    '</tr>';
            }

            $html =
                '<table class="table table-hover listingz">'.
                '<tr><th>ID</th><th>'.$tx['LBL']['type'].'</th><th>'.$tx['LBL']['time'].'</th>'.
                '<th>'.$tx['LBL']['author'].'</th>'.
                ((UZID==UZID_ALFA) ? '<th>SQL</th>' : '').'</tr>'.
                implode(PHP_EOL, $html).
                '</table>';

            unset($_SESSION['epglog']);

            return $html;
    }

    return null;
}






/**
 * Outputs html for IsReady tick, with ajax button for *false* state
 *
 * @param bool $is_ready IsReady value
 * @param bool $pms Permission for MDF operation
 * @param string $ajax_url Ajax url
 * @param string $ajax_data Ajax data
 * @param array $opt Options data
 * - css_span (string) - Optional CSS classname for SPAN
 * - css_a (string) - Optional CSS classname for A
 *
 * @return string $html
 */
function isready_output($is_ready, $pms, $ajax_url, $ajax_data, $opt=null) {

    $css = [];
    if (!empty($opt['css_span']))   $css[] = $opt['css_span'];
    if (!$is_ready)                 $css[] = 'ready_not';
    $css = ($css) ? ' '.implode(' ', $css) : '';

    $html = '<span class="glyphicon glyphicon-ok ready'.$css.'"></span>';

    if (!$is_ready && $pms) {

        $ajax['url'] = $ajax_url;
        $ajax['data'] = $ajax_data;
        $ajax['fn'] = 'ready_ajax_success';
        $ajax['btn_disable'] = true;

        $html = '<a class="ready'.((!empty($opt['css_a'])) ? ' '.$opt['css_a'] : '').
            '" href="#" '.ajax_onclick($ajax).'>'.$html.'</a>';
    }

    return $html;
}




/**
 * Outputs html for FLW ajax button
 *
 * @param bool $sw Switch value (Whether FLW exists or not)
 * @param int $item_id Item ID
 * @param int $item_typ Item type
 *
 * @return string $html
 */
function flw_output($sw, $item_id, $item_typ) {

    $ajax['url'] = '/desk/_ajax/_aj_flw.php';
    $ajax['data'] = 'item_typ='.$item_typ.'&item_id='.$item_id.'&sw='.(($sw) ? 0 : 1);
    $ajax['fn'] = 'flw_ajax_success';
    $ajax['btn_disable'] = true;

    $html = '<a class="flw'.(($sw) ? ' on' : '').'" href="#" '.ajax_onclick($ajax).'>'.
        '<span class="glyphicon glyphicon-eye-open"></span></a>';

    return $html;
}



/**
 * Outputs help button and popover.
 *
 * Call twice: in CFG HEADER with type *popover* - add result to $footer_cfg['js_lines']; and within HTML with button type.
 *
 * @param string $typ Type: (button, popover)
 * @param array $opt Options data
 * - name (string) - Name prefix. (Can be omitted if there is only one helper on the page)
 * - title (string) - Popover title
 * - content (string) - Popover content, i.e. text.
 *
 * @return void|string
 */
function help_output($typ, $opt=null) {

    global $tx;

    if (!isset($opt['name']))       $opt['name'] = 'helper';
    if (!isset($opt['content']))    $opt['content'] = '';

    if ($typ=='button') {

        if (!isset($opt['title']))      $opt['title'] = $tx['LBL']['instruction'];
        if (!isset($opt['css']))        $opt['css'] = 'help';
        if (!isset($opt['sign']))       $opt['sign'] = 'question';
        if (!isset($opt['output']))     $opt['output'] = true;
        if (!isset($opt['pull']))       $opt['pull'] = 'pull-right ';

        $r =
            '<a data-toggle="popover" class="popbtn '.$opt['pull'].$opt['css'].'" role="button" tabindex="0" '.
            'id="'.$opt['name'].'"'.
            (($opt['title']) ? ' title="'.$opt['title'].'"' : '').
            (($opt['content']) ? ' data-content="'.$opt['content'].'"' : '').
            '><span class="glyphicon glyphicon-'.$opt['sign'].'-sign"></span></a>';

        if ($opt['output']) {
            echo $r;
            return null;
        } else {
            return $r;
        }

    }

    if ($typ=='popover') {

        if (!isset($opt['title']))      $opt['title'] = '';
        if (!isset($opt['trigger']))    $opt['trigger'] = 'focus';
        if (!isset($opt['placement']))  $opt['placement'] = 'left';

        $r = '
            $(\'#'.$opt['name'].'\').popover({
                '.(($opt['title']) ? '"title": "'.$opt['title'].'",' : '').'
                '.(($opt['content']) ? '"content": "'.$opt['content'].'",' : '').'
                "trigger": "'.$opt['trigger'].'",
                "placement": "'.$opt['placement'].'",
                "container": "body",
                "html": "true"
            });';

        return $r;
    }
}









/**
 * Prints button-group with a permission check which decides whether to disable it
 *
 * @param bool $pms Permission
 * @param array $opt Options data
 * - btn_group_css
 * - btn_type
 * - btn_txt
 * - btn_href
 * - btn_css
 * - caret_css
 * - ul_html
 * @param bool $output Whether to output or return string
 * @return void|string
 */
function pms_btngroup($pms, $opt, $output=true) {

    if (!$pms) {
        $opt['btn_href'] = '';
        $opt['ul_html'] = '';
    }

    if (!$opt['btn_href']) {
        @$opt['btn_css'] .= ' disabled';
    }

    if (!$opt['ul_html']) {
        @$opt['caret_css'] .= ' disabled';
    }

    $r =
    '<div class="btn-group '.$opt['btn_group_css'].'">'.
      '<a type="button" class="btn '.$opt['btn_type'].' '.@$opt['btn_css'].'" href="'.$opt['btn_href'].'">'.
        $opt['btn_txt'].'</a>'.
      '<a type="button" class="btn '.$opt['btn_type'].' '.@$opt['caret_css'].' dropdown-toggle" data-toggle="dropdown">'.
        '<span class="caret"></span>'.
        '<span class="sr-only">Toggle Dropdown</span>'.
      '</a>'.
      '<ul class="dropdown-menu" style="text-transform: none;">'.$opt['ul_html'].'</ul>'.
    '</div>';

    if ($output) {
        echo $r;
        return null;
    } else {
        return $r;
    }
}



/**
 * Prints button with a permission check which decides whether to disable it
 *
 * @param bool $pms Permission
 * @param string $txt Text
 * @param array $attr Attributes
 * @param bool $output Whether to output or return string
 * @return void|string
 */
function pms_btn($pms, $txt, $attr, $output=true) {

    $a = [];

    if (!$pms) {
        @$attr['class'] .= ' disabled';
        $attr['href'] = '';
    }

    foreach ($attr as $k => $v) {
        $a[] = $k.'="'.$v.'"';
    }

    $r = '<a type="button" '.implode(' ', $a).'>'.$txt.'</a>';

    if ($output) {
        echo $r;
        return null;
    } else {
        return $r;
    }

}



/**
 * Shortcut printing for common buttons on *details* pages (prints new or mdf or del button)
 *
 * @param string $btntyp Button type: (new, mdf, del)
 * @param array $opt Options data
 * - itemtyp (string) - Item type (only for NEW and DEL btn)
 * - itemcpt (string) - Item caption, optional (only for DEL btn).. If omitted, $x['Caption'] (global) will be used instead.
 * - pms (bool) - PMS (optional)
 * - css (string) - Css classname (optional)
 * - target (string) - Target href, if we need to avoid default target (optional)
 *
 * @return void|array (returns array only for DEL btn)
 */
function btn_output($btntyp, $opt=null) {

    global $tx, $x;

    if (empty($opt['css'])) {
        $opt['css'] = 'btn-sm';
    }

    if (isset($opt['pms'])) {

        $pms = $opt['pms'];

    } elseif (defined('PMS')) {

        $pms = PMS;

    } elseif (isset($x['TYP'])) {

        $pms = pms(SCTN.'/'.$x['TYP'], $btntyp, $x);

    } else {

        $pms = false;
    }


    switch ($btntyp) {

        case 'mdf':

            pms_btn($pms, '<span class="glyphicon glyphicon-cog mdf"></span>'.$tx['LBL']['modify'],
                ['href' => $x['TYP'].'_modify.php?id='.$x['ID'], 'class' => 'btn btn-info text-uppercase '.$opt['css']]);
            break;

        case 'new':

            pms_btn($pms, '<span class="glyphicon glyphicon-plus-sign new"></span>'.$opt['itemtyp'],
                ['href' => $x['TYP'].'_modify.php', 'class' => 'btn btn-success text-uppercase pull-right '.$opt['css']]);
            break;

        case 'del':

            $deleter_argz = [
                'pms' => $pms,
                'txt_body_itemtyp' => $opt['itemtyp'],
                'txt_body_itemcpt' => ((isset($opt['itemcpt'])) ? $opt['itemcpt'] : $x['Caption']),
                'submiter_href' => ((isset($opt['target'])) ? $opt['target'] : 'delete.php?typ='.$x['TYP'].'&id='.$x['ID'])
            ];
            modal_output('button', 'deleter', $deleter_argz);

            return $deleter_argz;
    }

    return null;
}


