<?php
require '../../__ssn/ssn_boot.php';

define('TYP', 'item');

define('EPG_SCT', 'clp');
define('EPG_SCT_ID', 5);

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

$arr_ctgz = txarr('arrays','epg_clp_ctgz');
$arr_placez = txarr('arrays','epg_clp_place');


define('TBL', 'epg_clips');
define('LST_DATECLN', 'TermAdd');
define('BTNZ_CTRLZ_UNI', true);

define('CHNL_CBO', true);

$query['clnz'] = ['Caption', 'DurForc', 'CtgID', 'Placing', 'TermAdd', 'UID'];

$btnz['typ'] = ['new'];
$btnz['sub'] = [''];
$btnz['pms'] = [pms('epg/clp', 'mdf', ['TYP'=>TYP])];
$btnz['hrf'] = ['spice_modify.php?sct=clp'];
$btnz['cpt'] = [$tx[SCTN]['LBL']['clp']];

$cndz['cpt'] = [$tx[SCTN]['LBL']['target'],$tx[SCTN]['LBL']['place'],$tx['LBL']['author'],$tx['LST']['period'],$tx['LBL']['search']];
$cndz['typ'] = ['int-txt','int-txt','int-usr','str-tme','str-ggl'];
$cndz['nme'] = ['TYPX','CLP_PLC','UID','TME','GGL_REGEXP'];
$cndz['cln'] = ['CtgID','Placing','UID',LST_DATECLN,['Caption']];
$cndz['opt'] = [$arr_ctgz,$arr_placez,'','',''];

$clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx['LBL']['duration'],$tx[SCTN]['LBL']['target'],
                $tx[SCTN]['LBL']['place'],$tx['LBL']['author'],$tx['LBL']['time']];
$clnz['typ'] = ['id-1','cpt-spice','time-frmt','ctg-txt','ctg-txt','uid-1','time-1'];
$clnz['css'] = ['','lst_tdcpt','','','','',''];
$clnz['cln'] = ['ID','Caption','DurForc','CtgID','Placing','UID','TermAdd'];
$clnz['opt'] = ['','','i:s',$arr_ctgz,$arr_placez,['n2'=>'init'],''];

$clnz['act'][3] = (@$_GET['TYPX']) ? false : true;


require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';

