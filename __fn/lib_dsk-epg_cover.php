<?php

// epg/dsk covers






/**
 * COVERS reader: fetch all covers for specified scnr/fragment/atom/epg
 *
 * @param int $owner_id Owner ID
 * @param int $owner_typ Owner TYPE: 1-scnr, 2-fragment, 3-atom, 4-epg
 * @param int $cvr_typ Cover type
 *
 * @return array $x	Array of COVER objects (data arrays)
 */
function coverz_reader($owner_id, $owner_typ, $cvr_typ=null) {

    $tbl = 'epg_coverz';

    $where = [];
    $where[] = 'OwnerType='.$owner_typ;
    $where[] = 'OwnerID='.$owner_id;

    if ($cvr_typ) {
        $where[] = 'TypeX='.$cvr_typ;
    }

    $result = qry('SELECT ID FROM '.$tbl.' WHERE '.implode(' AND ', $where).' ORDER BY ID');

    while (list($id) = mysqli_fetch_row($result)) {

        $arr[$id] = cover_reader($id);
    }

    return @$arr;
}






/**
 * COVER reader
 *
 * @param int $id ID
 * @param array $opt Options data
 * - new_ownertyp (int) - OwnerType for NEW cvr (we need it, so we could fetch some data for that owner type, etc)
 * - new_ownerid (int) - OwnerID for NEW cvr
 * - get_ownerdata (bool) - Whether to fetch *owner data*
 *
 * @return array $x
 */
function cover_reader($id, $opt=null) {

    global $tx;


    $tbl = 'epg_coverz';

    if ($id) {
        $x = qry_assoc_row('SELECT * FROM '.$tbl.' WHERE ID='.$id);
    }

    $x['ID']  = intval(@$x['ID']);
    $x['TBL'] = $tbl;
    $x['TYP'] = 'cvr';
    $x['EPG_SCT_ID'] = 11;


    if (!$x['ID']) { // set default values and return

        $x['OwnerType'] = @$opt['new_ownertyp']; // We will fetch data array for this type and ID before return
        $x['OwnerID'] 	= @$opt['new_ownerid'];
        $x['IsReady'] 	= false;

        $x['TCin'] = $x['TCout'] = t2boxz('', 'time');


        //Owner TYPE: 1-scnr, 2-fragment, 3-atom, 4-epg

        if ($x['OwnerType']==1) { // scnr (prog/film)

            $epgid = rdr_cell('epg_elements', 'EpgID', $x['OwnerID']);

            $x['ChannelID'] = rdr_cell('epgz', 'ChannelID', $epgid);

        } elseif ($x['OwnerType']==4) { // epg

            $x['ChannelID'] = rdr_cell('epgz', 'ChannelID', $x['OwnerID']);

        } elseif (in_array($x['OwnerType'], [2,3])) { // fragment (story), atom

            $stry_id = ($x['OwnerType']==2) ? $x['OwnerID'] : rdr_cell('stry_atoms', 'StoryID', $x['OwnerID']);

            $x['ChannelID'] = rdr_cell('stryz', 'ChannelID', $stry_id);
        }


        return $x;
    }


    //Owner TYPE: 1-scnr, 2-fragment, 3-atom, 4-epg

    if ($x['OwnerType']==1) { // scnr (prog/film)

        $x['PRG'] = scnr_cpt_get($x['OwnerID'], 'arr');

    } elseif ($x['OwnerType']==4) { // epg

    } elseif (in_array($x['OwnerType'], [2,3])) { // fragment (story), atom

        $x['STRY']['ID'] = ($x['OwnerType']==2) ? $x['OwnerID'] : rdr_cell('stry_atoms', 'StoryID', $x['OwnerID']);

        $x['STRY'] = rdr_row('stryz', 'ID, Caption, Phase', $x['STRY']['ID']);
    }


    if (!empty($opt['get_ownerdata'])) {

        if ($x['OwnerType']==1) { // scnr (prog/film)

            $x['Caption'] = $x['PRG']['Caption'];

            $x['CaptionSub'] = $x['PRG']['TermEmit'];

        } elseif ($x['OwnerType']==4) { // epg

            $x['Caption'] = rdr_cell('epgz', 'DateAir', $x['OwnerID']);

            $x['CaptionSub'] = '';

        } elseif (in_array($x['OwnerType'], [2,3])) { // fragment (story), atom

            $x['Caption'] = $x['STRY']['Caption'];

            $x['CaptionSub'] = '';
        }

        if ($x['OwnerType'] == 1) { // scnr (prog/film)

            $x['OwnerTypeTXT'] = txarr('arrays', 'epg_line_types', $x['PRG']['NativeType']);

            $x['OwnerHREF'] = '/epg/epg.php?typ=scnr&view=2&id='.$x['PRG']['ID'];

        } elseif ($x['OwnerType'] == 4) { // epg

            $x['OwnerTypeTXT'] = $tx['LBL']['epg'];

            $x['OwnerHREF'] = '/epg/epg.php?typ=epg&view=2&id='.$x['OwnerID'];

        } elseif (in_array($x['OwnerType'], [2,3])) { // fragment (story), atom

            $x['OwnerTypeTXT'] = $tx['LBL']['stry'];

            $x['OwnerHREF'] = 'stry_details.php?id='.$x['STRY']['ID'];
        }
    }


    if (@$x['TypeX']) {
        $x['TypeXTXT'] = txarr('arrays', 'epg_cover_types', $x['TypeX']);
    }

    $x['TCinTXT']  = t2boxz($x['TCin'], 'time');
    $x['TCoutTXT'] = t2boxz($x['TCout'], 'time');


    return $x;
}





/**
 * COVERS output: print all covers for specified scnr/fragment/atom/epg
 *
 * @param int $owner_id Owner ID
 * @param int $owner_typ Owner TYPE: 1-scnr, 2-fragment, 3-atom, 4-epg
 * @param array $opt Options data
 * - cvr_typ (int) - Cover type
 * - wraper (bool) - Whether to wrap
 * - output (bool) - Whether to output
 * - txt_only (bool) - *Text only* case
 * - cln_offset (bool) - Column offset (Used only for *txt_only*)
 *
 * @return void|string
 */
function coverz_output($owner_id, $owner_typ, $opt=null) {

    global $bs_css, $cfg, $tx;

    if (!isset($opt['cvr_typ']))   $opt['cvr_typ'] = null;
    if (!isset($opt['wraper']))    $opt['wraper'] = true;
    if (!isset($opt['output']))    $opt['output'] = true;

    $coverz = coverz_reader($owner_id, $owner_typ, $opt['cvr_typ']);

    if (!$coverz) {
        return null;
    }


    if (!empty($opt['txt_only'])) {

        if (!$opt['output']) {
            return $coverz;
        }

        $cnt = 1;

        foreach ($coverz as $v) {

            $css = '';

            if ($owner_typ==2) {

                if (!empty($opt['cnt']['atomz']) && $cnt==1) {
                    $css[] = 'separ';
                };

                if ($opt['cnt']['story']==$cnt) {
                    $css[] = 'fini';
                };

                if (!empty($css)) {
                    $css = implode(' ', $css);
                }
            }

            echo '<tr class="cvr_clps '.$css.'">'.
                '<td colspan="'.$opt['cln_offset'].'" class="tblsheet cvrtyp">'.$v['TypeXTXT'].'</td>'.
                '<td class="tblsheet cvrbody"><div class="cgtxt">';

            $cnt++;

            echo nl2br($v['Texter']);

            echo '</div></td></tr>';
        }

        return null;
    }


    // These pms do not depend on the specific cover, thus we check them outside of the loop.
    $pms_kargen = pms('dsk/cvr', 'kargen');
    $pms_proofer = pms('dsk/cvr', 'proofer');


    foreach ($coverz as $v) {

        if ($v['IsReady']) {

            // We put this here by intent, in order to prevent cg-operator's normal mdf pms
            // (this applies only for *editdivable* control, i.e. ajax).
            // They sometimes accidentaly modify cvr when they convert and copy it, so they asked to disable ajax mdf for them.
            $pms_mdf = false;

        } else {

            // We have to put this *inside* the loop, because mdf-pms is item-specific
            $pms_mdf = pms('dsk/cvr', 'mdf',
                ['OwnerID' => $owner_id, 'OwnerType' => $owner_typ, 'IsReady' => $v['IsReady'], 'UID' => $v['UID']]);
        }

        if ($pms_proofer && !$pms_kargen && !$v['IsReady'] && !$v['ProoferUID']) {

            $isready = isready_output(
                false, true, '/desk/_ajax/_aj_cvr.php', 'id='.$v['ID'].'&proofer=1',
                ['css_a' => 'cvr_proofed']
            );

        } else {

            $isready = isready_output(
                $v['IsReady'], $pms_kargen, '/desk/_ajax/_aj_cvr.php', 'id='.$v['ID'],
                (($v['ProoferUID']) ? ['css_span' => 'cvr_proofed'] : null)
            );
        }

        if ($pms_kargen) {

            $editdivable_pagelabel =
                '<div class="lblpage">'.
                    '<div'.
                    ' onclick="editdivable_line(this); return false;"'.
                    ' data-ctrlcss="" data-ctrlmax="'.$cfg['cvr_pagelbl_maxlen'].'"'.
                    ' data-ajax="42,'.$v['ID'].',PageLabel,dsk/cvr.kargen,cpt"'.
                    '>'.$v['PageLabel'].'</div>'.
                '</div>';

        } else {

            $editdivable_pagelabel = '';
        }

        $editctrlable = ($pms_mdf) ?
            ' onfocus="editctrlable_mirror(this)" onblur="editctrlable_ajax_front(this)"'.
            ' onkeydown="editctrlable_canceler(this)"'.
            ' data-ajax="42,'.$v['ID'].',Texter,,txt"' : '';

        $cg_ctrlz = (($pms_kargen) ?

            '<a title="'.$tx['LBL']['cg_copy_text'].'" class="satrt opcty3 copy" onclick="cg_selcopy(this);">'.
                '<span class="glyphicon glyphicon-duplicate"></span></a>' : '').

            ((LNG==1) ?
                '<a title="'.$tx['LBL']['cg_alphconv'].'" class="satrt opcty3 conv" '.
                    'onclick="alphconv(\'cyr2cyrdos\', \'cg\', this);">'.
                    '<span class="glyphicon glyphicon-retweet"></span></a>' : '');


        $r[$v['ID']] =

            '<div class="cover linetyp'.$v['TypeX'].' phz'.(($v['IsReady']) ? 2 : (($v['ProoferUID']) ? 1 : 0)).'">'.
                // *linetyp* css is for LINE FILTER (multiselect cbo in header), *phz* css is for PHASE FILTER

            '<div class="header">'.


            '<span class="pull-left" style="margin-right: 15px;">'.
            $isready.
            $editdivable_pagelabel.
            '<a class="text-uppercase cpt" href="/desk/cvr_details.php?id='.$v['ID'].'">'.$v['TypeXTXT'].'</a>'.
            '<span class="tc">'.
                (($v['TCin'] || $v['TCout']) ? $v['TCin'] : '').(($v['TCout']) ? ' &mdash; '.$v['TCout'] : '').
            '</span>'.
            '</span>'.


            '<span class="hidden-print pull-right" style="margin-left: 15px;">'.

            $cg_ctrlz.

            ((!$pms_mdf && $v['IsReady']) ? '<span title="'.$tx['MSG']['cvr_pms'].'">' : '').

            pms_btn( // BTN: MDF
                $pms_mdf, '<span class="glyphicon glyphicon-cog"></span>',
                ['href' => '/desk/cvr_modify.php?id='.$v['ID'], 'class' => 'text-info satrt opcty3 mdf'], false).

            modal_output('button', 'deleter',
                [
                    'pms' => $pms_mdf,
                    'button_css' => 'text-danger satrt opcty3',
                    'onshow_js' => true,
                    'button_css_not_btn' => true,
                    'data_varz' => [ // will be picked-up by JS:modal_del_onshow()
                        'vary_modaltext' => $tx['MSG']['del_quest'].': '.$v['TypeXTXT'],
                        'vary_submiter_href' => '/desk/delete.php?typ=cvr&id='.$v['ID']
                    ]
                ],
                false).

            ((!$pms_mdf && $v['IsReady']) ?  '</span>' : '').

            '</span>'.


            '<span class="cvr_drop" onmousedown="cvr_drop_swtch(this); return false;">&nbsp;</span>'.


            '</div>'.

            '<textarea class="form-control no_vert_scroll"'.$editctrlable.'>'.$v['Texter'].'</textarea>'.

            '</div>';
    }

    $r = implode('', $r);

    if ($r && $opt['wraper']) {

        $r = '<div class="epg_cvr body typ'.$owner_typ.' col-xs-12">'.
            '<div class="row"><div class="'.$bs_css['offset'].' '.$bs_css['cln_r'].'">'.
            $r.'</div></div></div>';
    }


    if ($opt['output']) {
        echo $r;
        return null;
    } else {
        return $r;
    }
}




/**
 * COVERS accordion output
 *
 * @param int $owner_id Owner ID
 * @param int $owner_typ Owner TYPE: 1-scnr (i.e.prog/film), 2-fragment (i.e. story), 3-atom, 4-epg
 * @param string $name Accordion element name
 * @param bool $collapse Collapse or not
 *
 * @return void
 */
function coverz_accordion_output($owner_id, $owner_typ, $name, $collapse=false) {

    global $tx;

    $cvr_cnt = cnt_sql('epg_coverz', 'OwnerType='.$owner_typ.' AND OwnerID='.$owner_id);

    $caption = $tx['LBL']['cvr'].'<span class="caret"></span>'.
        '<span id="badge_'.$name.'" class="badge">'.$cvr_cnt.'</span>';

    $opt = [
        'type' => 'dtlform',
        'collapse' => $collapse,    //'collapse'=>($cvr_cnt),
        'righty' => pms_btn( // BTN: MDF
            PMS_CVR_NEW, '<span class="glyphicon glyphicon-plus-sign new"></span>'.$tx['LBL']['cvr'],
            [   'href' => '#',
                'onclick' => 'ifrm_starter(\'cvr_box\', this, \'/desk/cvr_modify.php?'.
                    'owner_typ='.$owner_typ.'&owner_id='.$owner_id.'&ifrm=1\');return false;',
                'class' => 'btn btn-success btn-xs text-uppercase satrt opcty3 btn_panel_head'   ],
            false)
    ];

    if ($owner_typ==3) { // atom
        $opt['css'] = 'cvr';
        $opt['css_heading'] = $opt['css_body'] = 'col-xs-9';
    }


    form_accordion_output('head', $caption, $name, $opt);

    if ($cvr_cnt) {

        $do_wrap = ($owner_typ==1) ? true : false;

        coverz_output($owner_id, $owner_typ, ['wraper' => $do_wrap]);
    }

    form_accordion_output('foot');
}





/**
 * Copy COVERZ for specified Owner ID (and give them new Owner ID)
 *
 * @param int $src_owner_id Original (source) owner ID
 * @param int $dst_owner_id Copy (destination) owner ID
 * @param int $owner_typ Owner type
 *
 * @return void
 */

function cvr_copy($src_owner_id, $dst_owner_id, $owner_typ) {

    $sql = 'SELECT TCin, TCout, TypeX, Texter FROM epg_coverz WHERE OwnerType='.$owner_typ.' AND OwnerID='.$src_owner_id;
    $coverz = qry_assoc_arr($sql);

    if (!$coverz) {
        return;
    }

    foreach ($coverz as $cvr) {

        $cvr['OwnerID'] = $dst_owner_id;
        $cvr['OwnerType'] = $owner_typ;
        $cvr['IsReady'] = 0;
        $cvr['TermAdd'] = TIMENOW;
        $cvr['UID'] = UZID;
        $cvr['ChannelID'] = CHNL;

        receiver_ins('epg_coverz', $cvr);
    }
}





/**
 * Count covers in a story, both on story level and on atom level
 *
 * @param int $id Owner ID
 * @param int $typ Type: 2-fragment, 3-atom, (not used: 1-scnr, 4-epg)
 *
 * @return array $cvr_cnt
 *  - story (int): count, on story level
 *  - atomz (int): count, on atom level
 *  - sum (int): summary count
 *  - lbl (str): summary count
 */
function cvr_cnt($id, $typ) {


    if ($typ==3) {

        $cvr_cnt['sum'] = $cvr_cnt['lbl'] = cnt_sql('epg_coverz', 'OwnerType=3 AND OwnerID='.$id);
    }


    if ($typ==2) {

        $cvr_cnt['story'] = cnt_sql('epg_coverz', 'OwnerType=2 AND OwnerID='.$id);

        $atom_idz = rdr_cln('stry_atoms', 'ID', 'StoryID='.$id);

        $cvr_cnt['atomz'] = 0;

        if ($atom_idz) {

            foreach ($atom_idz as $v) {

                $cvr_cnt['atomz'] += cnt_sql('epg_coverz', 'OwnerType=3 AND OwnerID='.$v);
            }
        }

        $cvr_cnt['sum'] = $cvr_cnt['story'] + $cvr_cnt['atomz'];
        $cvr_cnt['lbl'] = ($cvr_cnt['story'] && $cvr_cnt['atomz']) ? $cvr_cnt['atomz'].'+'.$cvr_cnt['story'] : $cvr_cnt['sum'];
    }


    return $cvr_cnt;
}




/**
 * Get referer data for the CVR modify/delete pages.
 *
 * CVR modify/delete pages can be accessed from several pages, and user should be returned to the calling page after submit.
 *
 * @param string $rtyp Return type: (str, arr). MDF page needs a stringed url query, DEL page needs an array.
 *
 * @return string|array $r Referer data
 */
function cvr_referer($rtyp='str') {

    $s = $_SERVER['HTTP_REFERER'];

    if (strpos($s, '/stry_details.php')) {

        $r['rfrscr'] = 'stry_details';

    } elseif (strpos($s, '/epg.php')) {

        $r['rfrscr'] = 'epg';
        $r['rfrtyp'] = scrape_url_attribute($s, 'typ');

    } else { // referer script is cvr_details

        return null;
    }

    $r['rfrid'] = scrape_url_attribute($s, 'id');


    if ($rtyp=='str') {

        foreach ($r as $k => $v) {
            $r[$k] = $k.'='.$v;
        }

        $r = '&'.implode('&', $r);
    }


    return $r;
}



/**
 * Extracts value of specified attribute from the specified url
 *
 * Helper function for cvr_referer().
 *
 * @param string $s Url
 * @param string $attr Name of the attribute which we want to extract from url
 *
 * @return string $r Attribute value
 */
function scrape_url_attribute($s, $attr) {

    $r = substr($s, strpos($s, $attr.'=') + strlen($attr) + 1);
    $y = strpos($r, '&');
    if ($y) {
        $r = substr($r, 0, $y);
    }

    return $r;
}




