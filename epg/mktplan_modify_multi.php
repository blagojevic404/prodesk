<?php
require '../../__ssn/ssn_boot.php';


$x = spice_reader(null, 'item', 'mkt');


pms('epg/mktplan', 'mdf', null, true);



if (isset($_POST['Submit_MKTPLAN'])) {
    require '_rcv/_rcv_mktplan_item.php';
}



/*************************** CONFIG ****************************/

$header_cfg['subscn'] = $x['EPG_SCT'];
$header_cfg['bsgrid_typ'] = 'full-w';

/*JS*/
$header_cfg['js'][]  = 'epg/mktplan.js';
$header_cfg['js'][]  = 'epg/epg.js';

$header_cfg['js_onsubmit'][]  = 'clone_cleaner(0,0)';
$header_cfg['bs_daterange'] = ['single' => true, 'submit' => false, 'selector' => 'input[name="DateEPG[]"]'];

/*CSS*/
$header_cfg['css'][] = 'epg/epg_spice.css';

require '../../__inc/_1header.php';
/***************************************************************/


crumbs_output('open');
crumbs_output('item', $tx['NAV'][74]);
crumbs_output('item', $tx[SCTN]['LBL'][$x['TYP']]);
crumbs_output('item', $x['Caption']);
crumbs_output('close');



form_tag_output('open', 'mktplan_modify_multi.php?id='.$x['ID']);

form_panel_output('head');

mktplan_item_mdf($x);

form_panel_output('foot');

form_btnsubmit_output('Submit_MKTPLAN');

form_tag_output('close');



/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';
