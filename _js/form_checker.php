<?php

/**
 * 'typ' - checker type..
 * 'cpt' - caption to be used in alert message
 */


foreach($header_cfg['form_checker'] as $v) {

    switch ($v['typ']) {

	    /*
	     * Note: TXT and INT are DISCONTINUED
	     * For textbox checks we can now use built-in *required* attribute, and we do.
	     *
        case 'txt':
		case 'int':
			$arr_conds[] = 'document.form1.'.$v['element'].'.value=='.($v['typ']=='txt') ? '\'\'' : '0';
			break;*/

        case 'chk_group':
            $checker['conds'][] = 'checker_chk_group([\''.implode('\', \'', $v['element']).'\'])==0';
            break;

        case 'epg_multi':
            $checker['conds'][] = 'checker_epg_multi()';
            break;

        case 'ifrm':
            $checker['conds'][] = 'checker_ifrm()';
            break;

        case 'mktplan_item_replace':
            $checker['conds'][] = 'checker_mktplan_item_replace(\''.$v['cur'].'\', \''.$v['msg'].'\')';
            break;
	}

	if (!empty($v['cpt'])) {
        $checker['alerts'][] = $v['cpt'];
    }
}


$checker['conds'] = (!empty($checker['conds'])) ? implode('||', $checker['conds']) : null;
$checker['alerts'] = (!empty($checker['alerts'])) ? implode(', ', $checker['alerts']) : null;

if ($checker['alerts']) {
    $checker['alerts'] = 'alerter(\''.$tx['MSG']['data_required'].': '.$checker['alerts'].'\');';
}


echo PHP_EOL.'<script type="text/JavaScript">'.PHP_EOL;
echo 'function checker() {'.
    (($checker['conds']) ? 'if ('.$checker['conds'].') {'.$checker['alerts'].'return false;}' : '').
    'return true;}';
echo PHP_EOL.'</script>';


unset($checker);

