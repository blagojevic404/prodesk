<?php



/**
 * Clean input (for GET and POST) variables, before using it in MySQL
 *
 * @param string $typ Type of cleanup operation
 * @param string $str
 * @param string|array $pattern Pattern. Used for: ymd, arr_assoc
 * @param string $def Default value. Used for: arr_assoc
 *
 * @return string $str
 */
function wash($typ, $str, $pattern='', $def = null){

	switch ($typ) {

        case 'cpt':	// standard string cleaner + removing new lines
        case 'txt':	// standard string cleaner
        case 'ggl':	// search box cleaning

            if ($typ=='cpt') {
                $wicked = [chr(13), chr(10)];
            } elseif ($typ=='ggl') {
                $wicked = [chr(13), chr(10), '[', ']', ';', '+', '(', ')'];
            } else { // $typ=='txt'
                $wicked = null;
            }

            if ($wicked) {
                $str = str_replace($wicked, chr(32), $str);
            }

            $str = htmlspecialchars($str, ENT_QUOTES);
            $str = trim($str);
            //$str = trim($str, '\\');

            $str = mysqli_real_escape_string($GLOBALS["db"], $str);
            break;


        case 'int': // integer forcing

            $str = intval($str);
            break;

        case 'bln': // boolean forcing

            $str = (intval($str)) ? 1 : 0;
            break;

        case 'ymd':	// ymd checking

            if (!$pattern) {
                $pattern = 'Y-m-d';
            }
            $stamp = strtotime($str);
            $str = ($stamp) ? date($pattern, $stamp) : null;
            break;

        case 'arr_assoc':

            if (!in_array($str, $pattern)) {
                $str = $def;
            }
            break;

        case 'ad_account':

            if (preg_match("/[^a-zA-Z0-9_.-]+/", $str)) {
                $str = null;
            }
            break;


        default:
            $str = null;

/*
        case 'tmce':			// tinymce rich textbox
        case 'tmce_img':	// with image support

            $str = str_ireplace(array('<h2','<h3','<h4','<h5','<h6'), '<h1', $str); // turn all headers to H1
            do $str = str_ireplace('&nbsp;&nbsp;', '&nbsp;', $str, $cnt); while ($cnt);


            $allowed_tags = '<p><br><h1><samp><li><ol><ul><a><strong><em><b><i><u><sub><sup><blockquote>'; //<div><span>
            if ($typ=='tmce_img') $allowed_tags = '<img>'.$allowed_tags;

            $str = strip_tags(stripslashes($str),$allowed_tags);


            $tmp = $str;
            while (1) {

                // Try and replace an occurrence of 'javascript:' and 'onmouseover:' and 'onclick:'

                $str = preg_replace('/(<[^>]*)javascript:([^>]*>)/i',   '$1$2', $str);
                $str = preg_replace('/(<[^>]*)onmouseover:([^>]*>)/i',  '$1$2', $str);
                $str = preg_replace('/(<[^>]*)onclick:([^>]*>)/i',      '$1$2', $str);

                // If nothing changed this iteration then break the loop
                if ($str == $tmp) break;

                $tmp = $str;
            }


            $str = htmlspecialchars($str, ENT_QUOTES);
            $str = trim($str);
            $str = trim($str, '\\');

            break;

        case 'tme':	// time checking

            $stamp = strtotime($str);
            $str = ($stamp) ? date('Y-m-d H:i:s',$stamp) : null;
            break;

        case 'phn':	// phone number cleaning

			$str = str_replace(array('-', ' ', '/'), '', $str);
			$str = intval($str);
			break;
			
		case 'hex':	// validate hex color code
			
			$len = strlen($str);
			if (!in_array($len, array(3,6))) {
				$str = '';
			} else{
				if (!preg_match("/([0-9]|[A-F]){".$len."}/i", $str)) $str = '';
			}
			break;

		case 'hh':	// validate hh

			$hh_int = intval($str);
			if ($str=='' || $hh_int>23 ) return '';
			$str = sprintf('%02s', $hh_int);
			break;

        case 'mm':	// validate mm

            $hh_int = intval($str);
            if ($str=='' || $hh_int>59 ) return '';
            $str = sprintf('%02s', $hh_int);
            break;
*/
    }
	

	return $str;
}	



/**
 * Clean output, i.e. prepare string from db to be displayed in browser
 *
 * @param string $typ Type of cleanup operation
 * @param string $str String
 * @return string
 */
function c_output($typ, $str) {

	switch ($typ) {

		case 'cpt':

			$wicked = [chr(13), chr(10)];
			$str = str_replace($wicked, chr(32), $str);
			$str = str_replace('&#039;', '&acute;', $str);
			$str = trim($str);
			break;			
			
		case 'html':
			$str = trim($str);
			$str = str_replace(chr(13).chr(10), '<br>', $str);
			break;
			
		case 'tmce':
			$str = htmlspecialchars_decode($str);
			break;
	}

	return $str;
}



/**
 * Text alphabet converter
 *
 * @param string $str Text
 * @param string $typ1 Input alphabet
 * @param string $typ2 Output alphabet
 *
 * @return string $str Converted text
 */
function text_convert($str, $typ1, $typ2) {

    $cyr = 'љ њ џ Љ Њ Џ Џ '.
        'а б в г д ђ е ж з и ј к л љ м н њ о п р с т ћ у ф х ц ч џ ш '.
        'А Б В Г Д Ђ Е Ж З И Ј К Л Љ М Н Њ О П Р С Т Ћ У Ф Х Ц Ч Џ Ш';

    $lat = 'lj nj dž LJ NJ DŽ Dž '.
        'a b v g d đ e ž z i j k l lj m n nj o p r s t ć u f h c č dž š '.
        'A B V G D Đ E Ž Z I J K L LJ M N NJ O P R S T Ć U F H C Č DŽ Š';

    $lateng = 'lj nj dz LJ NJ DZ Dz '.
        'a b v g d dj e z z i j k l lj m n nj o p r s t c u f h c c dz s '.
        'A B V G D DJ E Z Z I J K L LJ M N NJ O P R S T C U F H C C DZ S';

    $arr1 = explode(' ', $$typ1);
    $arr2 = explode(' ', $$typ2);

    $str = str_replace($arr1, $arr2, $str);

    return $str;
}



/**
 * Limit text to specified character length by cutting off words which cannot fit in
 *
 * @param string $str Text
 * @param int $max Max number of characters
 * @param array $opt Options data
 *  - typ (string) - (word, letter)
 *  - trail (string) - Trailing characters to add at the end, if cutting occurs. Set to null if you want none.
 *
 * @return string $str Cut text
 */
function txt_cutter($str, $max, $opt=null) {

    if (!isset($opt['typ']))            $opt['typ'] = 'word';
    if (!isset($opt['trail']))          $opt['trail'] = '..';

    $r = '';
	$str = trim(str_replace(chr(13).chr(10),chr(32),$str));

	mb_internal_encoding('UTF-8');

    if ($opt['typ']=='word') {

        $words = explode(' ', $str);
        $count = count($words);

        for ($i=0; $i<$count; $i++) {

            if (isset($words[$i]))	{
                if (mb_strlen($r.' '.$words[$i]) <= $max) {
                    if ($r) {
                        $r .= ' ';
                    }
                    $r .= $words[$i];
                } else {
                    $cut = true;
                    break;
                }
            }
        }

    } else { // letter

        $r = $str;

        if (mb_strlen($str) > $max) {

            $r = mb_substr($str, 0, $max);
            $cut = true;
        }
    }

	$r = trim($r,".,-:;!? \n\r\t");

    if ($opt['trail'] && !empty($cut)) {
        $r .= $opt['trail'];
    }

	return $r;
}

