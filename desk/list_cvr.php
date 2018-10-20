<?php
require '../../__ssn/ssn_boot.php';

define('TYP', 'cvr');
define('EPG_SCT_ID', 11);

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

define('TBL', 'epg_coverz');
define('LST_DATECLN', 'TermAdd');

define('SHOW_BTNZ', false);

define('CHNL_CBO', true);

$query['clnz'] = ['TypeX', 'IsReady', 'ProoferUID', 'UID', 'TermAdd', 'OwnerType', 'OwnerID'];

$arr_ctgz = txarr('arrays','epg_cover_types');

$cndz['cpt'] = [$tx['LBL']['type'],$tx['LBL']['author'],$tx[SCTN]['LBL']['unproofed'],$tx['LST']['period'],$tx['LBL']['search']];
$cndz['typ'] = ['int-txt','int-usr','bln','str-tme','str-ggl'];
$cndz['nme'] = ['CVR_TYP','UID','CHK_CVR_UNPROOFED','TME','GGL_FULLTEXT'];
$cndz['cln'] = ['TypeX','UID','',LST_DATECLN,['Texter']];
$cndz['opt'] = [$arr_ctgz,'','','',''];

$clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx['LBL']['type'],$tx['LBL']['author'],$tx['LBL']['time']];
$clnz['typ'] = ['id-1','cpt-cvr','ctg-txt','uid-1','time-1'];
$clnz['css'] = ['','lst_tdcpt','','',''];
$clnz['cln'] = ['ID','','TypeX','UID','TermAdd'];
$clnz['opt'] = ['','',$arr_ctgz,['n1'=>'init'],''];

require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';

