<?php
require '../../__ssn/ssn_boot.php';

require '../../__fn/fn_del.php';


$id  = (isset($_GET['id'])) ? intval($_GET['id']) : 0;
$typ = (isset($_GET['typ'])) ? wash('cpt', $_GET['typ']) : ''; // e.g. epg_mkt_item, epg_film_item..

if (!$id || !$typ) redirector('args');



$typ_arr = explode('_', $typ);

$typ = $typ_arr[0].'_'.$typ_arr[1];

$opt['zsct'] = @$typ_arr[1];
$opt['ztyp'] = @$typ_arr[2];


if ($opt['zsct']=='film' && $opt['ztyp']=='item') {
	
	$opt['filmtyp'] = (isset($_GET['filmtyp'])) ? wash('cpt', $_GET['filmtyp']) : '';
	$opt['filmsct'] = (isset($_GET['filmsct'])) ? wash('cpt', $_GET['filmsct']) : '';
}


deleter($typ, $id, $opt);






