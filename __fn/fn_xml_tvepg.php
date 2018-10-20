<?php


// TV-EPG



/**
 * Output epg in xml format for tv-epg service
 *
 * @param int $id EPG ID
 * @return void
 */
function xmlepg($id) {

    $xml = new DOMDocument('1.0', 'utf-8');

    $lang[0] = ['lang' => 'scc', 'encoding' => '01']; // cyr

    if (empty($_GET['lng'])) {
        $lang[1] = ['lang' => 'scr', 'encoding' => '05']; // lat
    }

    $z['psi'] = xml_element_add($xml, 'PSI', ['attrz' => ['lang' => $lang[0]['lang']]]);
    $z['network'] = xml_element_add($xml, 'NETWORK');
    $z['transport'] = xml_element_add($xml, 'TRANSPORT_STREAM',
        ['attrz' => ['id' => '3354', 'on_id' => '8262', 'sdt' => 'false', 'create_service_list' => 'false']]);
    $z['service'] = xml_element_add($xml, 'SERVICE',
        ['attrz' => ['id' => '3', 'type' => 'digital television', 'dvb_type' => '0x01', 'ca' => 'false']]);

    $xml->appendChild($z['psi']);
    $z['psi']->appendChild($z['network']);
    $z['network']->appendChild($z['transport']);
    $z['transport']->appendChild($z['service']);


    $arr = xmlepg_arr($id);
    $arr = xmlepg_durz($arr, $id);

    $cnt = 0;

    foreach ($arr as $k => $v) {

        $cnt++;

        $v['TermEmit'] = date('Y-m-d\TH:i:s.0000000P', strtotime($v['TermEmit'])); // 2017-10-13T06:10:48.0000000+02:00

        $z['event'] = xml_element_add($xml, 'EVENT',
            ['attrz' => ['id' => $cnt, 'time' => $v['TermEmit'], 'duration' => $v['Duration'], 'ca' => 'false',
            'type' => 'schedule', 'running_status' => 'running']]);

        $z['service']->appendChild($z['event']);

        foreach ($lang as $lng_k => $lng_attrz) {

            $txt = ($lng_k==0) ? $v['Caption'] : text_convert($v['Caption'], 'cyr', 'lat');

            $z['event']->appendChild(xml_element_add($xml, 'NAME', ['text' => $txt, 'attrz' => $lng_attrz]));

            if (!empty($v['Theme'])) {

                $txt = ($lng_k==0) ? $v['Theme'] : text_convert($v['Theme'], 'cyr', 'lat');

                $z['event']->appendChild(xml_element_add($xml, 'SHORT_DESCRIPTION', ['text' => $txt, 'attrz' => $lng_attrz]));
            }
        }

        if (!empty($v['Parental'])) {
            $z['event']->appendChild(xml_element_add($xml, 'PARENTAL_RATING',
                ['attrz' => ['country' => 'bih', 'minimal_age' => $v['Parental'],
                    'dvb_rating' => '0x0'.strtoupper(dechex($v['Parental']-3))]]));
        }

        if (!empty($v['Kind'])) {
            $z['event']->appendChild(xml_element_add($xml, 'KIND', ['attrz' => ['category' => $v['Kind']]]));
        }

        $z['event']->appendChild(xml_element_add($xml, 'AUDIO',
            ['attrz' => array_merge($lang[0], ['description' => 'stereo', 'type' => 'stereo', 'ac3' => 'false',
                'component_tag' => '0', 'dvb_type' => '0x03', 'audio_encoding' => 'HE-AAC'])]));

        $z['event']->appendChild(xml_element_add($xml, 'VIDEO',
            ['attrz' => array_merge($lang[0], ['description' => 'video, &gt; 16:9 aspect ratio, 25Hz',
                'type' => 'more than 16:9', 'frequency' => '25', 'high_definition' => 'false', 'component_tag' => '0',
                'dvb_type' => '0x04', 'video_encoding' => 'H264/AVC'])]));
    }


    $xml->formatOutput = true;

    print $xml->saveXML();
}





/**
 * Get schedule data array
 *
 * @param int $id EPG ID
 * @return array $arr Schedule data array
 */
function xmlepg_arr($id) {

    $rerun_sign = txarr('arrays', 'epg_mattyp_signs', 3);


    $sql = 'SELECT ID FROM epg_elements WHERE EpgID='.$id.
        ' AND TermEmit AND IsActive AND NativeType IN (1,12,13,14)'. // We need: progs(1), films(12,13), links(14)
        ' ORDER BY TermEmit ASC, Queue ASC';
    $result = qry($sql);

    while (@list($xid) = mysqli_fetch_row($result)) {

        $x = element_reader($xid);

        if (!$x['ID']) { // To avoid errors on slow connection when an epg element gets deleted in the middle of the query loop
            continue;
        }

        if ($x['NativeType']==1 && $x['PRG']['ProgID']) {
            // For PROGRAMS: If a program has ProgID, then we check whether this program should be hidden on the web
            if ($x['PRG']['SETZ']['WebHide']) continue;
        }

        $z = [];

        $z['ID'] = $x['ID'];
        $z['Duration'] = '';
        $z['TermEmit'] = $x['TermEmit'];
        $z['Parental'] = ($x['Parental']>=4 && $x['Parental']<=18) ? $x['Parental'] : null;
        $z['Theme'] = [];
        $z['Kind'] = xmlepg_kind($x);

        switch ($x['NativeType']) {

            case 1: // prg
                $z['Caption'] = $x['PRG']['ProgCPT'];
                $z['Theme'][] = (($x['PRG']['Caption']) ? $x['PRG']['Caption'] : @$x['PRG']['SETZ']['DscTitle']);
                break;

            case 5: // clip
                $z['Caption'] = $x['CLP']['Caption'];
                break;

            case 12: // film
            case 13: // film-serial
                $z['Caption'] = @$x['FILM']['Title'].
                    ((@$x['FILM']['EpiTitle']) ? ': '.$x['FILM']['EpiTitle'] : '');
                $z['Theme'][] = @$x['FILM']['DscTitle'];
                break;

            case 14: // link
                $z['Caption'] = $x['LINK']['PRG']['ProgCPT'];
                $z['Theme'][] = $x['LINK']['PRG']['Caption'];
                break;
        }


        if ($x['NativeType']==13 && @$x['FILM']['Ordinal']) {
            $z['Theme'][] = '('.$x['FILM']['Ordinal'].(($x['FILM']['EpisodeCount']) ? '/'.$x['FILM']['EpisodeCount'] : '').')';;
        }

        if (in_array($x['NativeType'], [1,12,13,14])) {
            if ($x['PRG']['MatType']==3) {
                $z['Theme'][] = '('.$rerun_sign.')';
            }
        }

        $z['Theme'] = implode(' ', $z['Theme']);
        if (empty($z['Theme'])) {
            unset($z['Theme']);
        }


        $arr[] = $z;
    }


    return $arr;
}



/**
 * Add duration data to schedule data array
 *
 * @param array $arr Schedule data array
 * @return array $arr Schedule data array
 */
function xmlepg_durz($arr, $id) {

    foreach ($arr as $k => $v) {

        $datetime_this = new DateTime($v['TermEmit']);

        if (isset($datetime_prev)) {

            $arr[$k_prev]['Duration'] = xmlepg_duration($datetime_prev, $datetime_this);
        }

        $k_prev = $k;
        $datetime_prev = $datetime_this;
    }

    if (isset($datetime_prev)) {

        $datetime_end = new DateTime(epg_zeroterm(rdr_cell('epgz', 'DateAir', $id)).' +1 days');

        end($arr);
        $last_key = key($arr);
        $arr[$last_key]['Duration'] = xmlepg_duration($datetime_prev, $datetime_end);
    }

    return $arr;
}



/**
 * Compute duration in tvepg format
 *
 * @param string $datetime_start Start datetime
 * @param string $datetime_end End datetime
 *
 * @return string $dur Duration, i.e. difference between start and end datetime
 */
function xmlepg_duration($datetime_start, $datetime_end) {

    $dur_obj = $datetime_start->diff($datetime_end);

    $dur = 'PT'; //PT1H28M

    if ($dur_obj->h) {
        $dur .= $dur_obj->h.'H';
    }

    $dur .= $dur_obj->i.'M';

    return $dur;
}



/**
 * Determine programme kind (type) in tvepg
 *
 * @param array $x Element array
 * @return string $r Programme kind
 */
function xmlepg_kind($x) {

    $r = 0;

    $arr_kinds = [
        0 => 'undefined content',
        1 => 'movie/drama',
        2 => 'news/current affairs',
        3 => 'show/game show',
        4 => 'sports',
        5 => 'children\'s/youth programmes',
        6 => 'music/ballet/dance',
        7 => 'arts/culture',
        8 => 'social/political issues/economics',
        9 => 'education/science/factual topics',
        10 => 'leisure hobbies',
        11 => 'special Characteristics',
    ];

    switch ($x['NativeType']) {

        case 1:
            $progid = $x['PRG']['ProgID'];
            break;

        case 5: // clip
            $r = $arr_kinds[0];
            break;

        case 12: // film
        case 13: // film-serial
            $r = $arr_kinds[1];
            break;

        case 14: // link
            $progid = $x['LINK']['PRG']['ProgID'];
            break;
    }

    if (!empty($r)) {
        return $r;
    }


    $teamid = rdr_cell('prgm', 'TeamID', $progid);

    switch ($teamid) {

        case 101: $r = $arr_kinds[2]; break;
        case 102: $r = $arr_kinds[4]; break;
        case 103: $r = $arr_kinds[3]; break;
        case 104: $r = $arr_kinds[9]; break;
        case 105: $r = $arr_kinds[5]; break;
        case 106: $r = $arr_kinds[6]; break;
        case 107: $r = $arr_kinds[7]; break;
        case 108: $r = $arr_kinds[9]; break;
        case 109: $r = $arr_kinds[9]; break;
    }

    return $r;
}



