<?php
require '../../__ssn/ssn_boot.php';

define('IFRM', (intval(@$_GET['ifrm'])));
// Have to define IFRM here because it is used below, within this same script


define('TYP', wash('arr_assoc', @$_GET['typ'], ['item', 'contract', 'agent'], 'item'));

define('EPG_SCT_ID', 12); // although it can be 12 or 13 (for serial), but it doesn't matter
define('EPG_SCT', 'film');

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

switch (TYP) {

	case 'agent':
	
		define('TBL', 'film_agencies');
		define('SHOW_CALENDAR', false);
		define('SHOW_CNDZ', false);
		define('LST_DATECLN', 'TermAdd');
		$query['clnz'] = ['Caption', 'TermAdd', 'UID'];

        $clnz['cpt'] = [$tx['LBL']['id'], $tx['LBL']['title'], $tx[SCTN]['LBL']['sum'], $tx['LBL']['author'], $tx['LBL']['time']];
		$clnz['typ'] = ['id-1','cpt-film','sum-rows','uid-1','time-1'];
        $clnz['css'] = ['','lst_tdcpt','','',''];
		$clnz['cln'] = ['ID','Caption','','UID','TermAdd'];
		$clnz['opt'] = [
                            '',
                            'film_details.php?typ='.TYP.'&id=',
                            ['film_contracts', 'AgencyID'],
                            ['n2'=>'init'],
                            ['DateAdd', 'DateMod']
                        ];
		break;


	case 'contract':

		define('TBL', 'film_contracts');
		define('SHOW_CALENDAR', false);
		define('LST_DATECLN', 'DateContract');
		$query['clnz'] = ['CodeLabel', 'AgencyID', 'DateContract', 'LicenceType', 'PriceSum', 'PriceCurrencyID', 'TermAdd'];

        $cndz['cpt'] = [$tx[SCTN]['LBL']['agent'], $tx['LST']['period'], $tx['LBL']['search']];
        $cndz['typ'] = ['int-db','str-tme','str-ggl'];
		$cndz['nme'] = ['CTG','TME','GGL_FULLTEXT'];
		$cndz['cln'] = ['AgencyID', LST_DATECLN, ['CodeLabel']];
		$cndz['opt'] = [['film_agencies', 'Caption'],'',''];

        $clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['label'],$tx[SCTN]['LBL']['agent'],$tx['LBL']['type'],$tx['LBL']['date']];
		$clnz['typ'] = ['id-1','cpt-film','ctg-db','txt-1','time-frmt'];
        $clnz['css'] = ['','lst_tdcpt','','',''];
		$clnz['cln'] = ['ID','CodeLabel','AgencyID','LicenceType','DateContract'];
		$clnz['opt'] = ['','film_details.php?typ='.TYP.'&id=',['film_agencies','Caption'],'','Y-m-d'];

		break;


	case 'item':

		define('TBL', 'film');
		define('SHOW_CALENDAR', false);
        define('LST_DATECLN', 'TermAdd');

        define('CHNL_CBO', true);

        // Channel will by default be added to filters, i.e. CNDZ, whenever channel cbo is used, but in this specific case
        // we want to avoid that, because channel info is not in main table but in special table
        define('CHNL_FILTER', false);
        $cndz['spec'][] = 'film_cn_channel.ChannelID='.CHNL;

        define('SHOW_BTNZ', true);
        // We define it here, although the default value is also 1, because we want to show buttons bar even if it is IFRM


        $join[1] = 'film_description';
        $join[2] = 'film_cn_bcasts';
        $join[3] = 'film_cn_contracts';

        $query['join'] = ' LEFT JOIN film_description ON '.TBL.'.ID = film_description.ID';
        $query['join'] .= ' LEFT JOIN film_cn_channel ON '.TBL.'.ID = film_cn_channel.FilmID';
        $query['join'] .= ' LEFT JOIN film_cn_bcasts ON '.TBL.'.ID = film_cn_bcasts.FilmID';
        $query['join'] .= ' LEFT JOIN film_cn_contracts ON '.TBL.'.ID = film_cn_contracts.FilmID';

        $query['clnz'] = [$join[1].'.Title', $join[1].'.LanguageID',
            TBL.'.TypeID', TBL.'.SectionID', TBL.'.DurApprox', TBL.'.DurReal', TBL.'.DurDesc', TBL.'.LicenceStart',
            TBL.'.LicenceExpire', $join[2].'.BCmax', TBL.'.EpisodeCount', TBL.'.IsDelivered', TBL.'.ProdType'];


        if (setz_get('film_list_bc_show_all_channels')) {

            // We fetch channels list here, to avoid fetching it from inside the loop

            $bc_channels = channelz(['typ' => 1], true); // only tv type

            unset($bc_channels[CHNL]); // delete current channel

            foreach ($bc_channels as $k => $v) { // we will make one-letter labels (using last letter in chnl caption)
                $bc_channels[$k] = mb_substr($v, -1);
            }
        }


        /*
        if ($cfg[SCTN]['bcast_cnt_separate']) {
            $cndz['spec'][] = 'film_cn_bcasts.ChannelID='.CHNL;
        } else {
            $cndz['spec'][] = 'film_cn_bcasts.ChannelID IS NULL';
            // In film_cn_bcasts table, ChannelID will be null if separate bcast count is off.
            // Thus it is not necessary to add this filter (ChannelID IS NULL).
            // It could be useful only in case we are migrating from one type of bcast count to other, and we want to test
            // so we have mixed migrated and unmigrated rows together, etc..
        }*/


        $cndz['cpt'] = [$tx[SCTN]['LBL']['contract'], $tx['LBL']['duration'], $tx['LBL']['language'], $tx['LBL']['state'],
                        $tx[SCTN]['LBL']['recent'], $tx['LBL']['archive'], $tx['LST']['period'], $tx['LBL']['search']];
        $cndz['typ'] = ['int-db','int-dur','int-txt','int-txt','bln','bln','str-tme','str-ggl'];
        $cndz['nme'] = ['FLM_CONTRACT','DUR','LANG','FULFIL','CHK_FLM_RECENT','CHK_EXPIRED','TME','GGL_FULLTEXT'];
		$cndz['cln'] = [$join[3].'.ContractID', 'DurReal', $join[1].'.LanguageID', $join[2].'.BCmax='.$join[2].'.BCcur',
                        LST_DATECLN, 'LicenceExpire', '', ['Title', 'OriginalTitle', 'Director', 'Actors']];
		$cndz['opt'] = [['film_contracts', 'CodeLabel'], [30,120,10,'mm'], txarr('arrays', 'film_languages'),
                        lng2arr($tx[SCTN]['LBL']['opp_fulfill'],null,['start_key'=>1]), '', '', '', $join[1]];
        $cndz['siz'] = [0=>11, 1=>11, 2=>11, 3=>11, 4=>11, 5=>11, 6=>18, 7=>18];

        $clnz['cpt'] = [$tx['LBL']['id'], $tx['LBL']['title'], $tx[SCTN]['LBL']['contract'],
                        $tx['LBL']['duration'], $tx[SCTN]['LBL']['licence'], $tx[SCTN]['LBL']['bcasts']];
		$clnz['typ'] = ['id-film','cpt-film','ctg-db2','dur-film','date-range','film-bcasts'];
        $clnz['css'] = ['','lst_tdcpt','','','lst_td_daterange','lst_td_flmbc'];
		$clnz['cln'] = [TBL.'.ID',$join[1].'.Title','ContractID','',['LicenceStart','LicenceExpire'],''];
		$clnz['opt'] = ['','film_details.php?typ='.TYP.'&id=',
                        ['film_contracts', 'CodeLabel', 'film_details.php?typ=contract&id='],'',
                        '',''];


        if (setz_get('film_list_show_contracts')) {
            $query['clnz'][] = $join[3].'.ContractID';
            $clnz['act'][2] = true;
        } else {
            $clnz['act'][2] = false;
        }


        // Arrays for caption labels
        $film_gnrz = txarr('arrays', 'film_genres');
        $film_lngz = txarr('arrays', 'film_languages');
        $film_typz = txarr('arrays', 'film_types');
        $film_sctz = txarr('arrays', 'film_sections');
        foreach ($film_lngz as $k => $v) $film_lngz[$k] = mb_substr($v, 0, 3);
        foreach ($film_typz as $k => $v) $film_typz[$k] = mb_substr($v, 0, 3);
        foreach ($film_sctz as $k => $v) $film_sctz[$k] = mb_substr($v, 0, 3);


        /* BUTTON CLUSTER */

        /* If cluster allows zero, that means it is possible to deselect all clusters (by another click on the currently
         * selected cluster's buttom) thus turning off the cluster filter and displaying ALL cluster types at once
         * (a label is added to caption cell, to mark the cluster type of each row).
         *
         * Disable for IFRM, because it would enable user to display types which we want removed (see below).
         */
		define('CLUSTER_ALLOW_ZERO', ((IFRM) ? false : true));

		$clusterz[1] = [
            'arr' => txarr('arrays', 'film_types'),
            'cln' => TBL.'.TypeID',
        ];

		if (IFRM)	{ // If IFRAME, display only cluster for *selected* type
			if ($_GET['cluster'][1]==1) {       // if type *movie* is selected, then remove *serial* and *mini-serial*
				unset($clusterz[1]['arr'][2]);
				unset($clusterz[1]['arr'][3]);
            } else {                            // if type *serial* or *mini-serial* is selected, remove *movie*
				unset($clusterz[1]['arr'][1]);
			}
		}

		$clusterz[2] = [
            'arr' => txarr('arrays', 'film_sections'),
            'cln' => TBL.'.SectionID',
        ];

        // Add info about currently selected cluster. Default is 1.
		foreach ($clusterz as $k => $v) {
            $clusterz[$k]['sel'] = (isset($_GET['cluster'][$k])) ? wash('int', $_GET['cluster'][$k]) : 1;
        }
}


$btnz['typ'] = [null, null, null, 'sole', 'new'];
$btnz['sub'] = ['item', 'contract', 'agent', 'bcast', ''];
$btnz['pms'] = [1, 1, 1, 1, pms('epg/'.EPG_SCT, 'mdf', ['TYP'=>TYP])];
$btnz['hrf'] = [
                    '?typ=item',
                    '?typ=contract',
                    '?typ=agent',
                    'epgs.php?typ=real&view=10',
                    'film_modify.php?typ='.TYP.((TYP!='item') ? '' :
                        '&filmtyp='.(($clusterz[1]['sel']) ? $clusterz[1]['sel'] : 1).
                        '&filmsct='.(($clusterz[2]['sel']) ? $clusterz[2]['sel'] : 1))
                ];
$btnz['cpt'] = [
                    $tx[SCTN]['LBL']['items'],
                    $tx[SCTN]['LBL']['contracts'],
                    $tx[SCTN]['LBL']['agents'],
                    $tx['LBL']['epg'],
                    ((TYP!='item') ? $tx[SCTN]['LBL'][TYP] :
                        mb_strtoupper($clusterz[1]['arr'][(($clusterz[1]['sel']) ? $clusterz[1]['sel'] : 1)]))
                ];

if (IFRM){
	$btnz = '';
}

if (TYP=='item' && $clusterz[1]['sel']!=1) {
    $cndz['act'][1] = false; // DURZ control is used only in *movies*
}


unset($join);

require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';

