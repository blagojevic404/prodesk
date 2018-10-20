<?php
require '../../__ssn/ssn_boot.php';

define('TYP', 'trash');

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

define('TBL', 'stry_trash');
define('LST_DATECLN', 'DelTerm');

define('CHNL_CBO', true);


$query['clnz'] = ['ItemID', 'ItemType', 'DelUID', 'DelTerm', 'ChannelID'];

define('SHOW_BTNZ', false);
define('SHOW_CNDZ', false);
define('SHOW_CALENDAR', false);

$clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx[SCTN]['LBL']['item'],$tx['LST']['deleted_by'],$tx['LBL']['time'],''];
$clnz['typ'] = ['id-1','cpt-trash','ctg-txt','uid-1','time-1','ctrl-trash'];
$clnz['css'] = ['','lst_tdcpt','','','','lst_tdctrl'];
$clnz['cln'] = ['ID','','ItemType','DelUID','DelTerm',''];
$clnz['opt'] = ['','',txarr('arrays','epg_line_types'),['n1'=>'init'],'',''];

//$clnz['act'][0] = false;
// We have a problem with sorting columns when this column is inactive:
// we can not return to normal sorting after we sort by some other column, i.e. *time*

$clnz['act'][2] = false;

require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';

