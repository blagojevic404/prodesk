<?php


// time handlers





/**
 * Get SHORT (3-letter uppercase) months
 */
function months_short() {

    global $tx;

    foreach ($tx['DAYS']['months'] as $k => $v) {
        $r[$k] = mb_strtoupper(mb_substr($v,0,3));
    }

    return $r;
}





/**
 * For specified EPG date get strings (weekday, shortdate, html..) to be used for caption
 *
 * @param string $datex Date (Y-m-d)
 *
 * @param string $rtyp Type: (date, date_wday, 3day, 3day_member)
 * - date: returns (string) date in "dd MMM yyyy"
 * - date_wday: returns (array) date in "dd MMM yyyy" + wday
 * - 3day: returns (array) - 3 cptdate: today, yesterday, tomorrow
 * - 3day_member: returns (array) date in "dd MMM"(no year) + wday, HTML, ymd, epgid
 *
 * @return string|array
 */
function epg_cptdate($datex, $rtyp) {

    global $tx;

    $t = strtotime($datex);

    if ($rtyp=='3day') {

        $x['TDAY'] 	= epg_cptdate($datex, 'date_wday');
        $x['YTD'] = epg_cptdate(date('Y-m-d', strtotime('-1 day', $t)), '3day_member');
        $x['TMR'] = epg_cptdate(date('Y-m-d', strtotime('+1 day', $t)), '3day_member');

        return $x;
    }


    $months_short = months_short();

    $x['date'] = date('d', $t).'&nbsp;'.$months_short[intval(date('n', $t))];               // '04 JAN'

    if (in_array($rtyp, ['date', 'date_wday'])) { // add year

        $x['date'] .= '&nbsp;'.date('Y', $t);                                               // '04 JAN 2014'
    }

    $x['wday'] = $tx['DAYS']['wdays'][intval(date('N', $t))];                               // 'Tuesday'

    if ($rtyp=='3day_member') {

        $x['HTML'] = $x['wday'].'<br>'.$x['date']; // wday+date, with a BR tag between them

        $x['ymd'] = date('Y-m-d', $t);

        // Check whether there is an epg for the specified date
        $x['epgid']	= epgid_from_date($x['ymd'], CHNL); // epgid is used for links in epg.php
    }


    if ($rtyp=='date') {
        return $x['date'];
    } else {
        return $x;
    }
}







/**
 * Converts time in DATETIME format to time in JS-DATE format
 *
 * @param string $t Time term in DATETIME format (YYYY-mm-dd hh:mm:ss)
 * @return string $r Time in JS-DATE format, e.g. Date(2013,7,12,07,01,15)
 */
function time2jstime($t) {

	$t = strtotime($t);
	
	$r = date('Y,', $t).(date('n', $t)-1).date(',j,H,i,s', $t);
	// Month has to be adjusted because of silly flaw in JS Date() function

	return $r;
}






/**
 * Converts HH:MM:SS time to MM:SS time
 *
 * @param string $t HH:MM:SS time
 * @param bool $h2m Whether to convert hh to mm (even though it will be 60+ value for mm)
 *
 * @return string $t MM:SS time
 */
function hms2ms($t, $h2m=true) {

	$hh = intval(substr($t, 0, 2));
	$t = substr($t, 3, 5);
	
	if ($hh) {

	    if ($h2m) {

            $mm = intval(substr($t, 0, 2));
            $ss = substr($t, 3);
            $mm_new = $mm + $hh*60;
            $t = $mm_new.':'.$ss;

        } else {

            $t = $hh.':'.$t;
        }
	}

	return $t;
}


/**
 * Converts HH:MM:SS time to HH:MM time
 *
 * @param string $t HH:MM:SS time
 * @return string $t HH:MM time
 */
function hms2hm($t) {

    if ($t) {
        $t = substr($t, 0, 5);
    }

    return $t;
}



/**
 * Convert (int)HHMMSS time to DateTimeInterface object
 *
 * @param int $hmsint HHMMSS integer
 * @param string $ymd_tday Date:today
 * @param string $ymd_tmrw Date:tomorrow
 * @param string $typ Type (hms, hm)
 *
 * @return DateTime $dti DateTimeInterface object
 */
function timeint2dti($hmsint, $ymd_tday, $ymd_tmrw, $typ='hms') {

    $midnight = ($typ=='hms') ? 240000 : 2400;


    $d = ($hmsint>=$midnight) ? $ymd_tmrw : $ymd_tday;

    $hms = timeint2timehms($hmsint, $typ);

    $dti = date_create($d.' '.$hms);

    return $dti;
}



/**
 * Calculate difference between timeint terms
 *
 * @param int $t1 TimeINT (HHMMSS integer)
 * @param int $t2 TimeINT (HHMMSS integer)
 * @param string $dater Date
 *
 * @return string $r Time (duration), in TIME format (hh:mm:ss)
 */
function timeint_diff($t1, $t2, $dater) {

    $ymd_tday = $dater;
    $ymd_tmrw = date('Y-m-d', strtotime('+1 day', strtotime($ymd_tday)));

    $dti1 = timeint2dti($t1, $ymd_tday, $ymd_tmrw);
    $dti2 = timeint2dti($t2, $ymd_tday, $ymd_tmrw);

    $interval = date_diff($dti1, $dti2);

    $r = $interval->format('%H:%I:%S'); // http://php.net/manual/en/dateinterval.format.php

    return $r;
}



/**
 * Convert (int)HHMMSS time to HH:MM:SS time
 *
 * @param int $n TimeINT (HHMMSS integer)
 * @param string $typ Type (hms, hm)
 * @param bool $epg_adjust Whether to do epg adjusting, i.e. substracting midnight value (240000) from after-midnight terms
 *
 * @return string $t TimeHMS (HH:MM:SS time)
 */
function timeint2timehms($n, $typ='hms', $epg_adjust=true) {

    $midnight = ($typ=='hms') ? 240000 : 2400;
    $minute_to_midnight = ($typ=='hms') ? 235900 : 2359;


    if ($epg_adjust) {

        if ($n>=$midnight) {
            $n -= $midnight;
        }

    } else {

        if (!$n || $n>$minute_to_midnight) {
            return null;
        }
    }

    $n = sprintf('%0'.(($typ=='hms') ? 6 : 4).'s', $n);

    $t = (($typ=='hms') ? substr($n, -6, -4).':' : '').substr($n, -4, -2).':'.substr($n, -2);

    return $t;
}



/**
 * Convert HH:MM:SS time to (int)HHMMSS time
 *
 * @param string $t HH:MM:SS time, (HHMMSS would also work)
 * @param string $typ Type (hms, hm)
 * @param bool $epg_adjust Whether to do epg adjusting, i.e. adding midnight value (240000) to after-midnight terms
 *
 * @return int $n TimeINT (HHMMSS integer)
 */
function timehms2timeint($t, $typ='hms', $epg_adjust=true) {

    global $cfg;

    $midnight = ($typ=='hms') ? 240000 : 2400;


    // Leave only the necessary part of the input string
    $len_in = ($typ=='hms') ? 8 : 5;
    if (strlen($t)>$len_in) {
        $t = substr($t, 0, $len_in);
    }


    // We cut the surplus, therefore if we would use *hm* type and use hh:mm:ss (instead hh:mm) for input, it would still work
    $len_out = ($typ=='hms') ? 6 : 4;
    $n = intval(substr(str_replace(':', '', $t), 0, $len_out));

    if ($epg_adjust) {

        $n_0 = timehms2timeint($cfg['zerotime'], $typ, false);

        if ($n<$n_0) {
            $n += $midnight;
        }
    }

    return $n;
}



/**
 * Convert YMD-HMS term to (int)HHMMSS time
 *
 * @param string $term Time term in DATETIME format (YYYY-mm-dd hh:mm:ss)
 *
 * @return int TimeINT (HHMMSS integer)
 */
function term2timeint($term) {

    return timehms2timeint(date('His', strtotime($term)));
}



/**
 * Convert YMD-HMS term to HH:MM time
 *
 * @param string $term Time term in DATETIME format (YYYY-mm-dd hh:mm:ss)
 *
 * @return string HH:MM time
 */
function term2timehm($term) {

    return date('H:i', strtotime($term));
}



/**
 * Calculations with (int)HHMMSS time - add or substract duration from (int)HHMMSS time
 *
 * @param int $n TimeINT (HHMMSS integer)
 * @param int $dur Duration in minutes
 * @param string $operation (+, -) Whether to add or substract duration to TimeINT
 * @param string $typ Type (hms, hm)
 *
 * @return int $r Resulting TimeINT
 */
function timeint_calc($n, $dur, $operation, $typ='hms') {

    if ($dur>60) {
        exit('Max 60!');
    }

    if ($typ=='hms') {
        $dur *= 100;
    }

    $len = ($typ=='hms') ? 6 : 4;


    $n = sprintf('%0'.$len.'s', $n);
    $a = str_split($n, 2);

    if ($operation=='-') $dur = 0 - $dur;

    $r = intval($n) + $dur;
    $r = sprintf('%0'.$len.'s', $r);
    $b = str_split($r, 2);


    if ($operation=='+') { // add

        if ($b[1]>59) {
            $b[0] += 1;
            $b[1] -= 60;
        }

    } else { // '-', substract

        if ($b[0]<$a[0]) {
            $b[1] -= 40;
        }
    }

    if ($typ=='hms') {
        $r = sprintf('%02s%02s%02s', $b[0], $b[1], $b[2]);
    } else {
        $r = sprintf('%02s%02s', $b[0], $b[1]);
    }


    return intval($r);
}






/**
 * Calculates difference of the two specified terms. Used only in epg_dtl_html();
 *
 * @param string $base Base term
 * @param string $minus Term to be deducted
 * @param string $r_typ Return type: ms | hms
 *
 * @return mixed If there is no difference, or if either base time or minus time are not set then returns 0.
 *               Otherwise returns string depending on $r_typ (return format)
 */
function terms_diff($base, $minus, $r_typ='ms') { 

	if (!$base || !$minus) {
        return;
    }
	
	if (strlen($base)<=5) {
        $base = '00:'.$base;  // Base must be in xx:xx:xx format
    }

	$r = strtotime($base) - strtotime($minus);
	
	if (!$r) {
        return;
    }
	
	$sign = ($r<0) ? '+' : '-';
	
	$r = secs2dur(abs($r));
	
	
	if ($r_typ=='hms') {
        return $r;
    }
	
	return $sign.hms2ms($r);
}
	
	







/**
 * Add time duration in TIME format (hh:mm:ss) to base time term in DATETIME format (YYYY-mm-dd hh:mm:ss)
 *
 * @param string $term Base time term, in DATETIME format (YYYY-mm-dd hh:mm:ss)
 * @param string $dur Time duration to be added, in TIME format (hh:mm:ss)
 * @return string $sum Sum time
 */
function add_dur2term($term, $dur) { 

	if (!$dur || $dur=='00:00:00') return $term;
	
	//// old function which did the same ////////////
	//$secs = strtotime($dur) - strtotime('00:00:00');
	//$sum = date('Y-m-d H:i:s', strtotime($term) + $secs);

	$dur_tmp = explode(':', $dur);
	$dur_tmp = sprintf('PT%dH%dM%dS', $dur_tmp[0], $dur_tmp[1], $dur_tmp[2]);
	
	$x = new DateTime($term);				// 		term START
	$x->add(new DateInterval($dur_tmp));	// 	+ 	dur
	$sum = $x->format('Y-m-d H:i:s');		// 	= 	term FINITO

	return $sum;
}




/**
 * Perform substract operation on the array, i.e. substract other members from the *first* array member
 *
 * @param array $arr Array with durations in TIME format
 * @return string $sum Summary duration in TIME format
 */
function substract_durs($arr) {

    foreach($arr as $v) {

        if (!isset($r)) {
            $r = dur2secs($v);
            continue;
        }

        if ($v) $r -= dur2secs($v);
    }

    return secs2dur($r);
}



/**
 * Calculates sum of array with specified times (durations) in TIME format (hh:mm:ss)
 *
 * @param array $arr Array with durations in TIME format
 * @return string $sum Summary duration in TIME format
 */
function sum_durs($arr) {

	$sum = 0;

	foreach($arr as $v) {
	    if ($v) $sum += dur2secs($v);
    }

    if (is_float($sum)) {
        $sum = round($sum);
    }

	return secs2dur($sum);
}



/**
 * Converts time to seconds
 *
 * @param string $t Time (duration), in TIME format (hh:mm:ss or hh:mm:ss.xxx)
 * @return int|float Time in seconds
 */
function dur2secs($t) {

    list($t, $ms) = milli_check($t);

    list($h, $m, $s) = explode(':', $t);

    $r = intval($s) + intval($m*60) + intval($h*3600);

    if ($ms) {
        $r += $ms/1000;
    }

    return $r;
}



/**
 * Converts seconds to time
 *
 * @param int $t Time in seconds
 * @return string Time (duration), in TIME format (hh:mm:ss)
 */
function secs2dur($t) {

    return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60);
}



/**
 * Converts minutes to time
 *
 * @param int $t Time in minutes
 * @return string Time (duration), in TIME format (hh:mm:ss)
 */
function mins2dur($t) {

    return sprintf('%02d:%02d:%02d', ($t/60%60), $t%60, 0);
}



/**
 * Converts tv frames to milliseconds
 *
 * @param int $ff Frames
 * @return int Milliseconds
 */
function ff2milli($ff) {
    return $ff * 40;
}

/**
 * Converts milliseconds to tv frames
 *
 * @param int $ms Milliseconds
 * @return int Frames
 */
function milli2ff($ms) {
    $r = round($ms / 40);
    $r = sprintf('%02s', $r);
    return $r;
}


/**
 * Check whether time contains milliseconds
 *
 * @param int $t Time
 * @param string $rtyp (arr, hms, ms)
 *
 * @return array|int $r Either both hms and ms part (hms-ms), or just hms (hms), or ms part (ms)
 */
function milli_check($t, $rtyp='hms-ms') {

    $ms = strpos($t, '.');

    if ($ms) {
        list($t, $ms) = explode('.', $t);
    }

    switch ($rtyp) {
        case 'hms-ms': $r = [$t, $ms]; break;
        case 'hms': $r = $t; break;
        case 'ms': $r = $ms; break;
    }

    return $r;
}




/**
 * Convert time from HMS single textbox input control into an HMS array
 *
 * @param string $t Time string (HMS)
 * @param string $input_typ (mmss, hhmm)
 *
 * @return array $arr_hms Time array (HMS)
 */
function rcv_hms($t, $input_typ='mmss') {

    $cnt_div = substr_count($t, ':');

    switch ($cnt_div) {

        case 0: // Dividers are omitted

            if ($input_typ=='mmss') {

                $arr_hms['ss'] = substr($t, -2);
                $arr_hms['mm'] = substr($t, -4, -2);
                $arr_hms['hh'] = substr($t, -6, -4);

            } else { // hhmm

                $arr_hms['ss'] = '00';
                $arr_hms['mm'] = substr($t, -2);
                $arr_hms['hh'] = substr($t, -4, -2);
            }

            break;

        case 1:

            if ($input_typ=='mmss') { // If (mm:ss), add one divider at the beginning to turn it into (hh:mm:ss)

                $t = ':'.$t;

            } else { // hhmm   // If (hh:mm), add one divider at the end to turn it into (hh:mm:ss)

                $t = $t.':';
            }

            break;

        case 2: // (hh:mm:ss)
            break;

        default: // >2  - time string is faulty
            $arr_hms = null;
    }

    if (!isset($arr_hms)) {
        $arr_hms = array_combine(['hh', 'mm', 'ss'], explode(':', $t));
    }

    return $arr_hms;
}






/**
 * Receives (usually from POST) time in HMS array, and optionaly date in YMD string, and formats it for DB input
 *
 * @param string $rtyp Return type: (ymdhms, hms, hms_nozeroz (means '00:00:00' will be treated as empty), int)
 * @param array $a_time HMS array
 * @param string $s_date YMD date (only for *ymdhms* return type) - EPG DateAir
 * @return string $r Formatted time or datetime
 */
function rcv_datetime($rtyp, $a_time, $s_date='') {

	global $cfg;

    if (!isset($a_time['ff']) || intval($a_time['ff'])>=25) {
        $a_time['ff'] = '';
    }


    if (($a_time['hh']==='') && ($a_time['mm']==='') && ($a_time['ss']==='') && ($a_time['ff']==='')) {
        return null;
    }
	
	if (($rtyp=='hms_nozeroz') &&
        (!intval($a_time['hh'])) && (!intval($a_time['mm'])) && (!intval($a_time['ss'])) && (!intval($a_time['ff']))) {
        return null;  // '00:00:00' will be treated as empty
    }


    $r = date('H:i:s', mktime(intval($a_time['hh']), intval($a_time['mm']), intval($a_time['ss'])));
    // TIME in 'H:i:s' format.
    // For hms, hms_nozeros - we return this.
    // For ymdhms, we use this later to compare it with zerotime and decide whether to add 1 day before we return.

    if ($cfg['dur_use_milli'] && $a_time['ff']) {
        $r .= '.'.ff2milli(sprintf('%03s', $a_time['ff']));
    }

    // For ymdhms: return DATETIME in 'Y-m-d H:i:s' format
	if ($rtyp=='ymdhms') {
		
		$zerotime = $cfg['zerotime'];
		
        $s_date = strtotime($s_date);
        $z_year  = date('Y', $s_date);
        $z_month = date('n', $s_date);
        $z_day 	 = date('j', $s_date);

        $x_day = (strtotime($r) < strtotime($zerotime)) ? $z_day+1 : $z_day;

        $r = date('Y-m-d H:i:s',
            mktime(intval($a_time['hh']), intval($a_time['mm']), intval($a_time['ss']), $z_month, $x_day, $z_year));
    }

    if ($rtyp=='int') {
        return intval(str_replace(':', '', $r));
    }

    return $r;
}








/**
 * Handles forced and calculated duration for epg lists: which one to return, which css to use
 *
 * @param string $forc Forced duration (user input), in TIME format (hh:mm:ss)
 * @param string $calc Calculated duration, in TIME format (hh:mm:ss)
 * @param string $r_type Return type: (time, css, null)
                 - time: Winner time (either calc or forc), i.e. term to be displayed
                 - css: (durok, durerr, null)
                 - null (default): Array which contains:
                    - CASE
                    - CSS
                    - TIME (which is actually timestamp)
                    - OTHER - in case we need the other term (i.e. loser time) to be displayed in brackets
 * @param string $precedence We can switch precedence (e.g. for story in phase 4)
 *
 * @return array|string $r Depending on return type ($r_type)
 */
function dur_handler($forc, $calc, $r_type='', $precedence='forc') {

	if ($forc=='00:00:00') $forc = '';
	if ($calc=='00:00:00') $calc = '';

	if (!$forc && !$calc) 				{ $r['case'] = 0; 	$r['css'] = 'durerr'; 	$r['time'] = '00:00:00'; 				}
	if (!$forc && $calc) 				{ $r['case'] = 1; 	$r['css'] = 'durok'; 	$r['time'] = $calc; 					}
	if ($forc && !$calc)				{ $r['case'] = 2; 	$r['css'] = '';			$r['time'] = $forc; 					}
	if ($forc && $calc && $forc!=$calc)	{ $r['case'] = 3; 	$r['css'] = 'durerr'; 	$r['time'] = $forc; $r['other'] = $calc;}
	if ($forc && $calc && $forc==$calc)	{ $r['case'] = 4; 	$r['css'] = 'durok'; 	$r['time'] = $forc; 					}

    /* Ideal is to have CALC time, because that is supposedly the most accurate.
     * Having FORC instead of CALC is actually a short-circuit, a user special intervention, an extraordinary situation.
     * That's why FORC has precedence over CALC (case 3).
     * (But we also return "other", there are cases when we want to display it just for info.)
     */


    // We can switch precedence (e.g. for story in phase 4)
    if ($precedence=='calc' && $r['case']==3) {
        $r['time'] = $calc;
        $r['other'] = $forc;
    }


    // format time to timestamp

    $r['time'] = strtotime($r['time']);

    if (isset($r['other'])) {
        $r['other'] = strtotime($r['other']);
    }


	if ($r_type=='css') 	return $r['css'];
	if ($r_type=='time') 	return $r['time'];

	return $r;
}







/**
 * Explodes time value to an array.
 *
 * Usually used when you have separate boxes for ymd, hh, mm values, and you need to populate them.
 *
 * @param string $t_str Time in str format
 * @param string $typ Type: (time, date, null). If omitted then both.
 * @param string $def Default value for time (Note: '00' is ok for duration and date, but use '' for term)
 *
 * @return array $r
 */
function t2boxz($t_str='', $typ='', $def='00') {

    global $cfg;

	$t = strtotime($t_str);
	
	$r = [];

    if ($t>0) {
		
		if ($typ!='time') {
			
			$r['t'] 		= date('Y-m-d H:i:s',   $t);
			$r['ymd'] 		= date('Y-m-d', 	    $t);
			$r['dmy'] 		= date('d.m.Y', 		$t);
		}
		
		if ($typ!='date') {
			
			$r['hh'] 		= date('H', 			$t);
			$r['mm']		= date('i', 			$t);
			$r['ss']		= date('s', 			$t);
			$r['mmss']		= date('i:s', 			$t);
			$r['hhmmss']	= date('H:i:s', 		$t);

            if ($cfg['dur_use_milli']) {
                $r['ms'] = milli_check($t_str, 'ms');
                $r['ff'] = milli2ff($r['ms']);
            }
		}
		
	} else { // if null, we return the array with same members, but fill it with empty (or default) values
		
		if ($typ!='time') {
		
			$r['t'] 		= '';
			$r['ymd'] 		= '';
			$r['dmy'] 		= '';
		}
		
		if ($typ!='date') {
			
			$r['hh'] 		= $def;
			$r['mm']		= $def;
			$r['ss']		= $def;
			$r['mmss']		= ($def=='') ? $def : $def.':'.$def;					// e.g. 00:00
			$r['hhmmss']	= ($def=='') ? $def : $def.':'.$def.':'.$def;			// e.g. 00:00:00

            if ($cfg['dur_use_milli']) {
                $r['ms']    = '';
                $r['ff']    = '';
            }
        }
	}
	
	return $r;
}



