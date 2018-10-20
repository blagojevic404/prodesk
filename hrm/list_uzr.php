<?php
require '../../__ssn/ssn_boot.php';

define('TYP', 'uzr');

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

define('TBL', 'hrm_users');
define('SHOW_CALENDAR', false);
define('SHOW_ABV', true);
define('BTNZ_CTRLZ_UNI', true);

$cndz['spec']['active'] = 'IsActive=1';
$cndz['spec']['not_hidden'] = '(IsHidden IS NULL OR IsHidden=0)';

$query['clnz'] = ['Name1st', 'Name2nd', 'GroupID'];

$btnz['typ'] = [null, null, 'new'];
$btnz['sub'] = ['uzr', '', ''];
$btnz['pms'] = [1, 1, pms('hrm/uzr','new')];
$btnz['hrf'] = ['', 'uzr.php', TYP.'_modify.php'];
$btnz['cpt'] = [$tx[SCTN]['LBL']['list'], $tx[SCTN]['LBL']['tree'], $tx[SCTN]['LBL']['worker']];

$cndz['cpt'] = [$tx['LBL']['search'],$tx['LBL']['archive']];
$cndz['typ'] = ['str-ggl','bln'];
$cndz['nme'] = ['GGL_FULLTEXT','CHK_ARCHIVE'];
$cndz['cln'] = [['Name1st', 'Name2nd'],''];
$cndz['opt'] = ['',''];

$clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['name2'],$tx['LBL']['name1'],$tx['LBL']['group']];
$clnz['typ'] = ['id-1','cpt-simple','cpt-simple','uzr-group'];
$clnz['css'] = ['','lst_tdcpt','lst_tdcpt',''];
$clnz['cln'] = ['ID','Name2nd','Name1st','GroupID'];
$clnz['opt'] = ['','',''];

require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';

