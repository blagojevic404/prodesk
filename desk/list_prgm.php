<?php
require '../../__ssn/ssn_boot.php';

define('TYP', 'prgm');

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

define('TBL', 'prgm');
define('SHOW_CALENDAR', false);
define('BTNZ_CTRLZ_UNI', true);

define('CHNL_CBO', true);
define('CHNL_FILTER', false);
$cndz['spec'][''] = 'TeamID IN (SELECT ID FROM prgm_teams WHERE ChannelID='.CHNL.')';
$cndz['spec']['active'] = 'IsActive=1';

$join[1] = 'prgm_settings';

$query['join'] = ' LEFT JOIN prgm_settings ON '.TBL.'.ID = prgm_settings.ID';

$query['clnz'] = ['Caption', 'TeamID', 'ProdType',
    $join[1].'.DurDesc', $join[1].'.TermDesc', $join[1].'.DscTitle', $join[1].'.Note'];

$lsetz['global'] = ['rpp' => 1, 'sort_cln' => 1, 'sort_typ' => 1]; // Change default values for memory setz


$btnz['typ'] = ['new'];
$btnz['sub'] = [''];
$btnz['pms'] = [pms('dsk/prgm', 'new')];
$btnz['hrf'] = [TYP.'_modify.php'];
$btnz['cpt'] = [$tx['LBL']['program']];

$cndz['cpt'] = [$tx['LBL']['team'],$tx['LBL']['production'],$tx['LBL']['archive'],$tx['LBL']['search']];
$cndz['typ'] = ['int-db','int-txt','bln','str-ggl'];
$cndz['nme'] = ['TEAM','PROD','CHK_ARCHIVE','GGL_FULLTEXT'];
$cndz['cln'] = ['TeamID','ProdType','',['Caption']];
$cndz['opt'] = [['prgm_teams','Caption','ChannelID='.CHNL],txarr('arrays','prod_types'),'',''];

$clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx['LBL']['duration'],$tx['LBL']['term'],$tx['LBL']['team'],
    $tx['LBL']['crew'],$tx['LBL']['website']];
$clnz['typ'] = ['id-1','cpt-prgm','txt-1','txt-1','ctg-db','crew-1','prgm-web'];
$clnz['css'] = ['','lst_tdcpt','','lst_tdprgmterm','','','lst_tdweb'];
$clnz['cln'] = ['ID','Caption','DurDesc','TermDesc','TeamID','',''];
$clnz['opt'] = ['','','','',['prgm_teams','Caption'],'',''];

$clnz['no-sort'] = [0,0,1,1,1,1,1];

$clnz['act'][4] = (@$_GET['TEAM']) ? false : true;
$clnz['act'][5] = (@$_GET['TEAM']) ? true : false;

require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';

