<?php
require '../../__ssn/ssn_boot.php';


// Max and min velocity. These will be used in php validity check, and they will also be picked up by js..
define('RS_MAX', 35);
define('RS_MIN', 10);



$setz['R_SPD']['title'] = $tx['LBL']['read_speed'];
$setz['R_CLR']['title'] = $tx[SCTN]['LBL']['reader_colors'];


$setz['LST'] = [
    'name'  => 'LST',
    'type'  => 'chk_list',
    'lines' => ['stry_lst_scnr', 'dsk_cvr_collapse', 'dsk_cndz_author'],
    'title' => $tx['NAV'][27],
];


$setz['FLW'] = [
    'name'  => 'FLW',
    'type'  => 'chk_list',
    'lines' => ['flw_author', 'flw_task', 'flw_prog'],
    'title' => $tx[SCTN]['LBL']['followz'].' ('.$tx['NAV'][29].')',
];









// RECEIVERS


if (isset($_POST['Submit_RS'])) {

    $tbl = 'stry_readspeed';

    $mdf['Velocity']    = wash('int', @$_POST['v_val'] * 100) / 100; // a float value
    $mdf['Name']        = wash('txt', @$_POST['v_name']);

    // If there is no existing default value then set this one as default
    $default_id = rdr_cell($tbl, 'ID', 'IsDefault=1');
    if (!$default_id) {
        $mdf['IsDefault'] = 1;
    }

    $mdf['UID'] = UZID;


    if ($mdf['Velocity'] <= RS_MAX || $mdf['Velocity'] >= RS_MIN) { // Ok

        // We multiply by 100 so we could save it as integer instead as float, for simplicity in handling..
        $mdf['Velocity'] *= 100;

        if ($_POST['v_id']) {

            $id = wash('int', $_POST['v_id']);

            receiver_upd_short($tbl, $mdf, $id, LOGSKIP);

        } else {

            receiver_ins($tbl, $mdf, LOGSKIP);
        }

        omg_put('success',  $tx['MSG']['set_saved'].' &mdash; '.$setz['R_SPD']['title'].' ('.$mdf['Name'].')');

        speakerUID_termemit(UZID);

    } else { // Invalid value for velocity

        omg_put('danger',
            $tx['MSG']['not_saved'].': '.(($mdf['Velocity'] > RS_MAX) ? 'Max='.RS_MAX : 'Min='.RS_MIN));
    }

}


if ($_SERVER['REQUEST_METHOD']=='POST') {

    foreach($setz as $k => $v) {
        if (@$v['type']=='chk_list' && isset($_POST['Submit_'.$k])) {
            setz_receiver($v);
        }
    }

    hop($pathz['www_root'].$_SERVER['SCRIPT_NAME']);
}






/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'setz';
$header_cfg['bsgrid_typ'] = 'halfer';
$header_cfg['ajax'] = true;

$footer_cfg['modal'][] = 'alerter';

$header_cfg['css'][] = '/_pkg/jquery-ui/jquery-ui.min.css';
$header_cfg['js'][] = '/_pkg/jquery-ui/jquery-ui.min.js';

$header_cfg['js'][] = 'dsk/read_speed.js';
$header_cfg['js'][] = 'dsk/atoms.js';

$header_cfg['js_lines'][]  = '

    var rs_phz = 0;
    var rs_max = '.RS_MAX.';
    var rs_min = '.RS_MIN.';
    var rs_t_start;
    var rs_phzarr = [\''.$tx[SCTN]['LBL']['start'].'\', \''.$tx[SCTN]['LBL']['finito'].'\', \''.$tx[SCTN]['LBL']['clear'].'\'];
    var rs_txtarr = [\''.$tx[SCTN]['LBL']['text_len'].'\', \''.$tx[SCTN]['LBL']['time'].'\','.
    ' \''.$tx[SCTN]['LBL']['chars_per_sec'].'\'];

;';

$header_cfg['js_lines'][]  = '

    $(function() {
        $( "#sortable" ).disableSelection();
        $( "#sortable" ).sortable({
            cursor: "move",
            update: function( event, ui ) {
                var data = $(this).sortable("serialize");
                ajaxer("POST", "_ajax/_aj_setz.php", "typ=clr&" + data);
            }
        });
    });

;';

// BS js is initialized at the *bottom* of the script, thus we always must put BS js function settings into footer
$footer_cfg['js_lines'][]  = help_output('popover', ['name' => 'rs_helper', 'placement' => 'right']);
$footer_cfg['js_lines'][]  = help_output('popover', ['name' => 'rs_info', 'placement' => 'auto left', 'trigger' => 'hover']);


require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', $tx['NAV'][2]);
crumbs_output('item', $tx['NAV'][205]);
crumbs_output('close');



echo '<div class="row" style="padding-top:20px;">';










/* READ SPEED */

form_tag_output('open', '', true, 'form_rs');

$txt_readspeed = txarr('blocks', 'readspeed');

$txt_instruction = txarr('blocks', 'rs_instruction');


$html = [];

$result = qry('SELECT ROUND(Velocity/100, 1) AS Velocity, ID, Name, IsDefault FROM stry_readspeed '.
    'WHERE UID='.UZID.' ORDER BY ID DESC');

while ($line = mysqli_fetch_assoc($result)) {

    $btn_dft =
        '<a class="'.(($line['IsDefault']) ? 'disabled' : 'opcty3').' is_default" href="#" title="'.$tx['LBL']['default'].'"'.
            ajax_onclick(['url'=>'_ajax/_aj_setz.php', 'data'=>'typ=dft&id='.$line['ID'], 'fn'=>'rs_dft_ajax_success']).'>'.
            '<span class="glyphicon glyphicon-thumbs-up"></span></a>';

    $btn_mdf =
        '<a class="opcty3" href="#" title="'.$tx['LBL']['modify'].'" '.
            'onclick="rs_btnmdf_click('.$line['ID'].', \''.$line['Name'].'\', this); return false;">'.
            '<span class="glyphicon glyphicon-cog"></span></a>';

    $btn_del =
        '<a class="opcty3 text-danger" href="#" title="'.$tx['LBL']['delete'].'"'.
            ajax_onclick(['url'=>'_ajax/_aj_setz.php', 'data'=>'typ=del&id='.$line['ID'], 'fn'=>'rs_del_ajax_success']).'>'.
            '<span class="glyphicon glyphicon-remove"></span></a>';

    $html[] =
        '<li class="list-group-item">'.
            $line['Velocity'].
            '<span class="name">'.$line['Name'].'</span>'.
            '<div class="pull-right ctrlz">'.$btn_dft.$btn_mdf.$btn_del.'</div>'.
        '</li>';
}

?>

<div class="<?=$bs_css['half']?>">
    <div class="panel panel-default setz read_speed">

        <div class="panel-heading">

            <?=help_output('button', ['name' => 'rs_helper', 'content' => $txt_instruction])?>

            <h3 class="panel-title"><?=$setz['R_SPD']['title']?></h3>
        </div>

        <ul class="list-group"><?=implode('', $html)?></ul>

        <div class="panel-body">

            <textarea name="v_text" id="v_text" class="form-control no_vert_scroll" rows="9"
                      onkeyup="expandTxtarea(this,2);" onclick="expandTxtarea(this,2);"><?=$txt_readspeed?></textarea>

            <button type="button" id="v_phz" class="btn btn-success text-uppercase"
                    onclick="rs_phz_change()"><?=$tx[SCTN]['LBL']['start']?></button>

            <button type="submit" id="v_submit" name="Submit_RS"
                    class="btn btn-primary text-uppercase pull-right disabled"><?=$tx['LBL']['save']?></button>

            <div class="pull-right v_name">
                <input type="text" name="v_name" id="v_name" maxlength="20" class="form-control" required
                       placeholder="<?=$tx['LBL']['name']?>" value=""></div>

            <div class="pull-right v_val">
                <input type="text" name="v_val" id="v_val" maxlength="4" class="form-control" required
                       placeholder="{v}" value=""></div>

            <?=help_output('button',
                ['name' => 'rs_info', 'title' => $tx[SCTN]['LBL']['formula'], 'css' => 'info', 'sign' => 'info'])?>

        </div>

    </div>
</div>

<?php

form_ctrl_hidden('v_id', 0);

form_tag_output('close');

// We must have separate forms, otherwise we wouldn't be able to submit FLW values before we fill in
// the *required* fields in RS box.









/* READER COLOR */

$arr_clrz = cfg_global('arrz','dsk_reader_clr');

$clrz = setz_get('reader_color');

$len = strlen($clrz);

$html = [];

for ($i=0; $i<$len; $i++) {

    $html[] = '<li id="c_'.$clrz[$i].'" style="background-color:#'.$arr_clrz[$clrz[$i]].'"></li>';
}

echo
    '<div class="'.$bs_css['half'].'"><div class="panel panel-default setz">'.
        '<div class="panel-heading"><h3 class="panel-title">'.$setz['R_CLR']['title'].'</h3></div>'.
        '<div class="panel-body">'.
            '<ul id="sortable">'.implode('', $html).'</ul>'.
        '</div>'.
    '</div></div>';

//jqueryui.com/sortable/#display-grid









/* BLOCKS */

foreach($setz as $k => $v) {
    if (@$v['type']=='chk_list') {
        setz_block_chk($setz[$k]);
    }
}





echo '</div>';

/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
