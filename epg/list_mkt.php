<?php
require '../../__ssn/ssn_boot.php';

$tx[SCTN]['LBL']['agent'] = $tx[SCTN]['LBL']['client'];
$tx[SCTN]['LBL']['agents'] = $tx[SCTN]['LBL']['clients'];


define('TYP', wash('arr_assoc', @$_GET['typ'], ['item', 'block', 'agent'], 'item'));

define('EPG_SCT_ID', 3);
define('EPG_SCT', 'mkt');

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

$btnz['typ'] = [null, null, null, 'sole', 'new'];
$btnz['sub'] = ['item', 'block', 'agent', '', ''];
$btnz['pms'] = [1, 1, 1, 1, pms('epg/'.EPG_SCT, 'mdf', ['TYP'=>TYP])];
$btnz['hrf'] = ['?typ=item','?typ=block','?typ=agent','epgs.php?typ=real&view=8','spice_modify.php?sct='.EPG_SCT.'&typ='.TYP];
$btnz['cpt'] = [$tx[SCTN]['LBL']['items'],$tx[SCTN]['LBL']['blocks'],$tx[SCTN]['LBL']['agents'],
                $tx['LBL']['epg'],$tx[SCTN]['LBL'][TYP]];

define('SHOW_CALENDAR', false);



switch (TYP) {

	case 'agent':

		define('TBL', 'epg_market_agencies');
		define('SHOW_CNDZ', false);
		define('LST_DATECLN', 'TermAdd');

        define('CHNL_CBO', true);
        $header_cfg['chnl_referer'] = true;

        $query['clnz'] = ['Caption', 'TermAdd', 'UID'];

        $clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx[SCTN]['LBL']['sum'],$tx['LBL']['author'],$tx['LBL']['time']];
        $clnz['typ'] = ['id-1','cpt-spice','sum-rows','uid-1','time-1'];
        $clnz['css'] = ['','lst_tdcpt','','',''];
        $clnz['cln'] = ['ID','Caption','','UID','TermAdd'];
		$clnz['opt'] = ['','',['epg_market', 'AgencyID'],['n2'=>'init'],['DateAdd', 'DateMod']];

		break;


	case 'block':

        define('TBL', 'epg_blocks');
        define('LST_DATECLN', 'TermAdd');

        define('CHNL_CBO', true);
        $header_cfg['chnl_referer'] = true;

        $query['clnz'] = ['Caption', 'DurForc', 'CtgID', 'TermAdd', 'UID'];


        $cndz['cpt'] = [$tx['LBL']['author'],$tx['LST']['period'],$tx['LBL']['search']];
        $cndz['typ'] = ['int-usr','str-tme','str-ggl'];
        $cndz['nme'] = ['UID','TME','GGL_REGEXP'];
        $cndz['cln'] = ['UID',LST_DATECLN,['Caption']];
        $cndz['opt'] = ['','',''];

        $cndz['spec'][] = 'BlockType='.EPG_SCT_ID;


        $clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx['LBL']['duration'],$tx['LBL']['author'],$tx['LBL']['time']];
        $clnz['typ'] = ['id-1','cpt-spice','dur-epg','uid-1','time-1'];
        $clnz['css'] = ['','lst_tdcpt','','',''];
        $clnz['cln'] = ['ID','Caption','','UID','TermAdd'];
        $clnz['opt'] = ['','','',['n2'=>'init'],''];

        break;


	case 'item':

		define('TBL', 'epg_market');
		define('LST_DATECLN', 'TermAdd');

        define('CHNL_CBO', true);

        define('GGL_FOCUS', true);

        $ggl_clnz = ['epg_market.Caption','epg_market_agencies.Caption'];
        $ggl_tblz = [TBL,'epg_market_agencies'];
        $query['join_ggl'] = ' LEFT JOIN epg_market_agencies ON '.TBL.'.AgencyID = epg_market_agencies.ID';

        $query['clnz'] = ['epg_market.Caption', 'DurForc', 'AgencyID', 'DateStart', 'DateExpire',
            'epg_market.TermAdd', 'epg_market.UID'];

        if ($cfg[SCTN]['use_mktitem_video_id']) {
            $query['clnz'][] = 'VideoID';
            $ggl_clnz[] = 'VideoID';
        }

        $cndz['cpt'] = [$tx['LBL']['id'], $tx['LBL']['duration'], $tx[SCTN]['LBL']['agent'], $tx['LBL']['author'],
                        $tx['LST']['period'], $tx['LBL']['search']];
        $cndz['typ'] = ['int-id','int-dur','int-db','int-usr','str-tme','str-ggl'];
        $cndz['nme'] = ['ID','DUR','CTG','UID','TME','GGL_REGEXP'];
        $cndz['cln'] = ['ID','DurForc','AgencyID','UID',LST_DATECLN,$ggl_clnz];
        $cndz['opt'] = ['spice_details.php?sct=mkt&typ=item&ifrm=1&&id=',[10,65,5,'ss'],['epg_market_agencies','Caption']
            ,'','',''];
        $cndz['siz'][0] = 9;

        $clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx['LBL']['duration'],$tx[SCTN]['LBL']['agent'],
                        $tx['LST']['period'], $tx['LBL']['author'],$tx['LBL']['time']];
		$clnz['typ'] = ['id-1','cpt-spice','time-frmt','ctg-db','date-range','uid-1','time-1'];
        $clnz['css'] = ['','lst_tdcpt','','','lst_td_daterange','',''];
        $clnz['cln'] = ['ID','Caption','DurForc','AgencyID',['DateStart','DateExpire'],'UID','TermAdd'];
		$clnz['opt'] = ['','','i:s',['epg_market_agencies','Caption'],'',['n2'=>'init'],''];

        $clnz['act'][3] = ($cfg[SCTN]['mktitemlist_show_agency_cln']) ? true : false;

        break;
}


require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';


