<?php
require '../../__ssn/ssn_boot.php';

define('TYP', 'flw');

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


define('TBL', 'stry_followz');
define('LST_DATECLN', 'MarkTerm');

$query['clnz'] = ['ItemID', 'ItemType', 'UID', 'MarkTerm', 'MarkType'];

define('SHOW_BTNZ', false);
define('SHOW_CALENDAR', false);

$cndz['spec'][] = 'UID='.UZID;


$arr_linetypz = array_intersect_key(txarr('arrays', 'epg_line_types'), array_flip([1,2,6]));
krsort($arr_linetypz);

$cndz['cpt'] = [$tx[SCTN]['LBL']['item'],$tx['LST']['period'],$tx['LBL']['type']];
$cndz['typ'] = ['int-txt','str-tme','int-txt'];
$cndz['nme'] = ['FLW_TYP','TME','FLW_MARK'];
$cndz['cln'] = ['ItemType',LST_DATECLN,'MarkType'];
$cndz['opt'] = [$arr_linetypz,'',txarr('arrays','dsk_flw_types')];

$clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx[SCTN]['LBL']['item'],$tx['LBL']['rundown'],$tx['LBL']['time'],
    $tx['LBL']['type'],''];
$clnz['typ'] = ['id-1','cpt-flw','ctg-txt','flw-scnr','time-1','ctg-txt','ctrl-flw'];
$clnz['css'] = ['','lst_tdcpt','','','','','lst_tdctrl'];
$clnz['cln'] = ['ID','','ItemType','','MarkTerm','MarkType',''];
$clnz['opt'] = ['','',$arr_linetypz,'','',txarr('arrays','dsk_flw_types'),''];

$clnz['no-sort'] = [0,1,1,1,1,1];

//$clnz['act'][0] = false;
// We have a problem with sorting columns when this column is inactive:
// we can not return to normal sorting after we sort by some other column, i.e. *time*

$clnz['act'][2] = false;


require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';
