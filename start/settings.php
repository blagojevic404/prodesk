<?php
require '../../__ssn/ssn_boot.php';

// PMS check not necessary because MDF procedure targets settings by UZID, i.e. user can change only his own settings.


$tbl[1] = 'hrm_users_data';

$x['ChannelID'] = rdr_cell($tbl[1], 'ChannelID', UZID);

if (MULTI_LNG) {
    $x['LanguageID'] = rdr_cell($tbl[1], 'LanguageID', UZID);
}



// RECEIVER

if (isset($_POST['Submit_SETZ'])) {

    $mdf[1]['ChannelID'] = wash('int', @$_POST['ChannelID']);

    if (MULTI_LNG) {
        $mdf[1]['LanguageID'] = wash('int', @$_POST['LanguageID']);
    }

    receiver_mdf($tbl[1], ['ID' => UZID], $mdf[1]);

    setz_put_post('atom_jargon_1', 1);
    setz_put_post('atom_jargon_2', 2);

    setz_put_post('multi_login');

    omg_put('success',  $tx['MSG']['set_saved']);

    hop($pathz['www_root'].$_SERVER['SCRIPT_NAME']);
}




/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'setz';
$header_cfg['bsgrid_typ'] = 'regular';

/*CSS*/
$header_cfg['css'][] = 'modify.css';

require '../../__inc/_1header.php';
/***************************************************************/



// CRUMBS
crumbs_output('open');
crumbs_output('item', $tx['NAV'][1]);
crumbs_output('item', $tx['NAV'][205]);
crumbs_output('close');



// FORM start
form_tag_output('open', $pathz['www_root'].$_SERVER['SCRIPT_NAME']);



/* MAIN FIELD */

form_panel_output('head');

// Channel
ctrl('form', 'select-db', $tx[SCTN]['LBL']['channel'], 'ChannelID', $x['ChannelID'],
    ['sql' => 'SELECT ID, Caption FROM channels ORDER BY ID ASC']);

// Language
if (MULTI_LNG) {
    ctrl('form', 'select', $tx['LBL']['language'], 'LanguageID', arr2mnu(languages_act(), LNG));
}

// Multi login
ctrl('form', 'chk', $tx[SCTN]['MSG']['set_multi_login'], 'multi_login', setz_get('multi_login'));

form_panel_output('foot');



/* JARGON FIELD (for story atoms) */

form_accordion_output('head', $tx[SCTN]['LBL']['atom_jargon'], 'jargon');

$channel_types = txarr('arrays', 'channel_types');

$jargons = txarr('arrays', 'atom_jargons');

foreach($jargons as $k => $v) {
    $jargons[$k] = $v.' ('.implode(', ', txarr('arrays', 'atom_jargons.'.$k)).')';
}

ctrl('form', 'select', $channel_types[1], 'atom_jargon_1', arr2mnu($jargons, setz_get('atom_jargon_1')));

ctrl('form', 'select', $channel_types[2], 'atom_jargon_2', arr2mnu($jargons, setz_get('atom_jargon_2')));

form_accordion_output('foot');



// SUBMIT BUTTON
form_btnsubmit_output('Submit_SETZ');



// FORM close
form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
