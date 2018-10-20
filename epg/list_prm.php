<?php
require '../../__ssn/ssn_boot.php';

define('TYP', wash('arr_assoc', @$_GET['typ'], ['item', 'block'], 'item'));

define('EPG_SCT_ID', 4);
define('EPG_SCT', 'prm');


//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

$btnz['typ'] = [null, null, 'sole', 'new'];
$btnz['sub'] = ['item', 'block', 'bcast', ''];
$btnz['pms'] = [1, 1, 1, pms('epg/'.EPG_SCT, 'mdf', ['TYP'=>TYP])];
$btnz['hrf'] = ['?typ=item','?typ=block','epgs.php?typ=real&view=9','spice_modify.php?sct='.EPG_SCT.'&typ='.TYP];
$btnz['cpt'] = [$tx[SCTN]['LBL']['items'],$tx[SCTN]['LBL']['blocks'],$tx['LBL']['epg'],$tx[SCTN]['LBL'][TYP]];


$arr_ctgz = txarr('arrays','epg_prm_ctgz');

$lsetz['memory']['cndz'][] = 'TYPX';


switch (TYP) {

	case 'block':

        define('TBL', 'epg_blocks');
        define('SHOW_CALENDAR', false);
        define('LST_DATECLN', 'TermAdd');

        define('CHNL_CBO', true);
        $header_cfg['chnl_referer'] = true;

        $query['clnz'] = ['Caption', 'DurForc', 'CtgID', 'TermAdd', 'UID'];


        $cndz['cpt'] = [$tx['LBL']['type'],$tx['LBL']['author'],$tx['LST']['period'],$tx['LBL']['search']];
        $cndz['typ'] = ['int-txt','int-usr','str-tme','str-ggl'];
        $cndz['nme'] = ['TYPX','UID','TME','GGL_REGEXP'];
        $cndz['cln'] = ['CtgID','UID',LST_DATECLN,['Caption']];
        $cndz['opt'] = [$arr_ctgz,'','',''];

        $cndz['spec'][] = 'BlockType='.EPG_SCT_ID;


        $clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx['LBL']['duration'],$tx['LBL']['type'],
            $tx['LBL']['author'],$tx['LBL']['time']];
        $clnz['typ'] = ['id-1','cpt-spice','dur-epg','ctg-txt','uid-1','time-1'];
        $clnz['css'] = ['','lst_tdcpt','','','',''];
        $clnz['cln'] = ['ID','Caption','','CtgID','UID','TermAdd'];
        $clnz['opt'] = ['','','',$arr_ctgz,['n2'=>'init'],''];

        $clnz['act'][3] = (@$_GET['TYPX']) ? false : true;

        break;


	case 'item':

		define('TBL', 'epg_promo');
        define('SHOW_CALENDAR', false);
        define('LST_DATECLN', 'TermAdd');

        define('CHNL_CBO', true);

        $query['clnz'] = ['Caption', 'DurForc', 'CtgID', 'DateStart', 'DateExpire', 'TermAdd', 'UID'];


        $cndz['cpt'] = [$tx['LBL']['type'],$tx['LBL']['author'],$tx['LBL']['archive'],$tx['LST']['period'],$tx['LBL']['search']];
        $cndz['typ'] = ['int-txt','int-usr','bln','str-tme','str-ggl'];
        $cndz['nme'] = ['TYPX','UID','CHK_EXPIRED','TME','GGL_REGEXP'];
		$cndz['cln'] = ['CtgID','UID','DateExpire',LST_DATECLN,['Caption']];
		$cndz['opt'] = [$arr_ctgz,'','','',''];

        $clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx['LBL']['duration'],$tx['LBL']['type'],
                        $tx['LBL']['author'],$tx['LBL']['time'],$tx['LST']['period']];
		$clnz['typ'] = ['id-1','cpt-spice','time-frmt','ctg-txt','uid-1','time-1','date-range'];
        $clnz['css'] = ['','lst_tdcpt','','','','','lst_td_daterange'];
		$clnz['cln'] = ['ID','Caption','DurForc','CtgID','UID','TermAdd',['DateStart','DateExpire']];
        $clnz['opt'] = ['','','i:s',$arr_ctgz,['n2'=>'init'],'',''];

        $clnz['act'][3] = (@$_GET['TYPX']) ? false : true;

        $clnz['act'][4] = false;
        $clnz['act'][5] = false;
}


require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';


