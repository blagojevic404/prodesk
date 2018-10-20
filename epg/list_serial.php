<?php
require '../../__ssn/ssn_boot.php';

// DISCONTINUED: Was used only for MKT-BLCTYP

define('IFRM', true); // Used only in IFRM

define('TYP', '');
define('EPG_SCT', 'film');
define('EPG_SCT_ID', 13);

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

define('TBL', 'film');
define('LST_DATECLN', 'TermAdd');
define('SHOW_BTNZ', false);
define('SHOW_CALENDAR', false);

$cndz['spec'][] = 'film_cn_channel.ChannelID='.CHNL;
$cndz['spec'][] = 'TypeID<>1';


$join[1] = 'film_description';

$query['join'] = ' LEFT JOIN film_description ON '.TBL.'.ID = film_description.ID';
$query['join'] .= ' LEFT JOIN film_cn_channel ON '.TBL.'.ID = film_cn_channel.FilmID';

$query['clnz'] = [$join[1].'.Title', TBL.'.DurApprox', TBL.'.DurReal', TBL.'.DurDesc', TBL.'.LicenceStart', TBL.'.LicenceExpire'];


$cndz['cpt'] = [$tx[SCTN]['LBL']['recent'], $tx['LBL']['archive'], $tx['LST']['period'], $tx['LBL']['search']];
$cndz['typ'] = ['bln','bln','str-tme','str-ggl'];
$cndz['nme'] = ['CHK_FLM_RECENT','CHK_EXPIRED','TME','GGL_FULLTEXT'];
$cndz['cln'] = [LST_DATECLN, 'LicenceExpire', '', ['Title', 'OriginalTitle', 'Director', 'Actors']];
$cndz['opt'] = ['', '', '', $join[1]];

$clnz['cpt'] = [$tx['LBL']['id'], $tx['LBL']['title'], $tx['LBL']['duration'], $tx[SCTN]['LBL']['licence']];
$clnz['typ'] = ['id-1','cpt-serial','dur-film','date-range'];
$clnz['css'] = ['','lst_tdcpt','','lst_td_daterange'];
$clnz['cln'] = [TBL.'.ID',$join[1].'.Title','',['LicenceStart','LicenceExpire']];
$clnz['opt'] = ['','','',''];

require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';
