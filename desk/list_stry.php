<?php
require '../../__ssn/ssn_boot.php';

define('TYP', 'stry');
define('EPG_SCT_ID', 2);

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

define('TBL', 'stryz');
define('LST_DATECLN', 'TermAdd');
define('BTNZ_CTRLZ_UNI', true);

define('CHNL_CBO', true);

define('GGL_CPTONLY', true); // I had to exclude atoms text from search because it hangs..

if (GGL_CPTONLY) {

    $ggl_clnz = ['Caption'];
    $ggl_tblz = [TBL];

} else {

    $ggl_clnz = ['Caption','Texter'];
    $ggl_tblz = [TBL,'stry_atoms_text'];
    $query['join_ggl'] = ' LEFT JOIN stry_atoms ON '.TBL.'.ID = stry_atoms.StoryID';
    $query['join_ggl'] .= ' LEFT JOIN stry_atoms_text ON stry_atoms.ID = stry_atoms_text.ID';
}

$lsetz['memory']['cndz'][] = 'PHASE';
$lsetz['memory']['cndz'][] = 'CHK_TODAY_EPG';

$cndz['spec'][] = 'IsDeleted IS NULL';

$query['clnz'] = ['Caption', 'DurForc', 'UID', 'TermAdd', 'Phase', 'ScnrID'];

$btnz['typ'] = ['new'];
$btnz['sub'] = [''];
$btnz['pms'] = [pms('dsk/stry','new')];
$btnz['hrf'] = [TYP.'_modify.php?typ=mdf_'.(($cfg['strynew_2in1']) ? 'atom' : 'dsc')];
$btnz['cpt'] = [$tx['LBL'][TYP]];

$cndz['cpt'] = [$tx['LBL']['author'],$tx['LBL']['program'],$tx['LST']['period'],$tx['LBL']['rundown'],
    $tx[SCTN]['LBL']['phase'],$tx['NAV'][71],$tx['LBL']['task'],$tx['LBL']['search']];
$cndz['typ'] = ['int-usr','int-swz','str-tme','int-txt','int-txt','bln','bln','str-ggl'];
$cndz['nme'] = ['UID','PROGID','TME','EPGCONN','PHASE','CHK_TODAY_EPG','CHK_TASKS','GGL_FULLTEXT'];
$cndz['cln'] = ['UID','ProgID',LST_DATECLN,'ScnrID','Phase','','',$ggl_clnz];
$cndz['opt'] = ['',['prgm','Caption'],'',txarr('arrays','status_stry'),txarr('arrays','dsk_nwz_phases'),'','',$ggl_tblz];
$cndz['siz'] = [12,12,'',12];

$clnz['cpt'] = [$tx['LBL']['id'],$tx['LBL']['title'],$tx['LBL']['rundown'],$tx['LBL']['duration'],$tx['LBL']['author'],$tx['LBL']['time']];
$clnz['typ'] = ['id-1','cpt-stry','cln-scnr','dur-epg','uid-1','time-1'];
$clnz['css'] = ['','lst_tdcpt','','','',''];
$clnz['cln'] = [TBL.'.ID','Caption','ScnrID','','UID','TermAdd'];
$clnz['opt'] = ['','','','',['n1'=>'init'],''];

$clnz['no-sort'] = [0,1,1,1,1,1];

define('STRY_LST_SCNR', setz_get('stry_lst_scnr'));

$clnz['act'][2] = (STRY_LST_SCNR && @$_GET['EPGCONN']!=2) ? true : false;
// EPGCONN==2 means 'list only stories which are not connected to epg/scn'. In that case showing epg/scn cln would be useless.


require '../../__inc/inc_list.php';
require '../../__inc/inc_list_htm.php';

