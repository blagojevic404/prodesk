<?php

require '../../__fn/fn_xml.php';




/**
 * Morpheus: Prints XML for epg in EXPORTER view
 *
 * @param string $listtyp Type: (epg, scnr, spice)
 * @param int $id
 * @param array $sch_data Morpheus schedule data
 * @param object $xml XML object (not used in *epg* list-type)
 * @param array $z XML nodes array (not used in *epg* list-type)
 * @param array $parent Parent data (not used in *epg* list-type)
 * - typ (string) - Type (mkt, prm)
 * - term (string) - Parent Term
 * - id (int) - Parent ID
 *
 * @return void
 */
function morpheus_epgexp($listtyp, $id, $sch_data, $xml=null, $z=null, $parent=null) {

    if ($listtyp!='spice') {

        $xml = new DOMDocument('1.0', 'utf-8');

        $z = morpheus_start($xml, $sch_data);
    }

    $result = qry(epg_exp_sql($listtyp, $id));

    while (@list($xid) = mysqli_fetch_row($result)) {

        $x = epg_exp_element($listtyp, $xid);
        if (!$x) continue;

        if ($listtyp!='spice' && $x['NativeType']==4 && $x['_CNT_Frag']) { // prm block: skip block, show fragments

            $parent = ['typ' => 'prm', 'term' => $x['TermEmit'], 'id' => $x['ID']];

            morpheus_epgexp('spice', $x['NativeID'], $sch_data, $xml, $z, $parent);

        } else {

            if ($listtyp=='epg' || ($listtyp=='spice' && $parent['typ']=='prm')) {
                // Avoid for scnr (doesnot have html/form page) and for mkt block fragments (not displayed in html/form page)

                $chk_name = ($listtyp=='epg') ? 'chk'.$xid : 'chk'.$parent['id'].'_'.$xid;

                if (empty($_POST[$chk_name])) {
                    continue;
                }
            }

            if ($listtyp=='spice') {

                $x['TermEmit'] = $parent['term'];
                $parent['term'] = add_dur2term($parent['term'], $x['_Dur']['winner']['dur']);

                $x['parent'] = $parent;
            }

            morpheus_event($listtyp, $xml, $z['events'], $x, $sch_data);
        }

        if ($listtyp!='spice' && $x['NativeType']==3 && $x['_CNT_Frag']) { // mkt block: show block, then also fragments

            $parent = ['typ' => 'mkt', 'term' => $x['TermEmit'], 'id' => $x['ID']];

            morpheus_epgexp('spice', $x['NativeID'], $sch_data, $xml, $z, $parent);
        }
    }

    if ($listtyp!='spice') {

        morpheus_finito($xml, $z);
    }
}



/**
 * Morpheus: Fetch schedule data
 *
 * @param array $x Element
 *
 * @return array $sch_data
 */
function morpheus_data($x) {

    global $datecpt;


    $sch_data['NME']['ymd'] = substr(str_replace('-', '', $x['EPG']['DateAir']), 2);
    $sch_data['NME']['wday'] = $datecpt['TDAY']['wday'];
    $sch_data['NME']['channel'] = channelz(['id' => $x['EPG']['ChannelID']], true);

    $sch_data['NME']['name'] = $sch_data['NME']['ymd'].'_'.$sch_data['NME']['wday'].' '.$sch_data['NME']['channel'];
    $sch_data['NME']['name'] = strtoupper(text_convert($sch_data['NME']['name'], 'cyr', 'lateng'));
    if (!empty($_GET['scnrexp'])) {
        $sch_data['NME']['name'] .= '_SCNR'.intval($_GET['scnrexp']);
    }

    $sch_data['SCH']['Channel'] =
        (empty($_POST['SCH_Channel'])) ? (($x['EPG']['ChannelID']==1) ? 'TX02' : 'TX03') : $_POST['SCH_Channel'];

    $sch_data['SCH']['Name'] = (empty($_POST['SCH_Name'])) ? 'Default Schedule' : $_POST['SCH_Name'];

    $sch_data['SCH']['ExternalId'] = (empty($_POST['SCH_ExternalId'])) ? time().rand(1000, 9999) : $_POST['SCH_ExternalId'];

    $sch_data['SCH']['SvrRecs'] = (empty($_POST['SCH_SvrRecs'])) ? 'SVR' : $_POST['SCH_SvrRecs'];

    $sch_data['SCH']['SvrLive'] = (empty($_POST['SCH_SvrLive'])) ? '' : $_POST['SCH_SvrLive'];

    if (SERVER_TYPE=='dev') {
        $sch_data['SCH']['Channel'] = 'CH1';
        $sch_data['SCH']['SvrRecs'] = '';
    }

    $sch_data['SCH']['MainEvString'] = $sch_data['SCH']['Channel'].' Main Event';

    return $sch_data;
}



/**
 * Morpheus: add an EVENT node with all data to EVENTS node
 *
 * @param string $listtyp Type: (epg, spice)
 * @param object $xml XML object
 * @param object $eventz *Events* node
 * @param array $x Element
 * @param array $sch_data Morpheus schedule data
 *
 * @return void
 */
function morpheus_event($listtyp, $xml, $eventz, $x, $sch_data) {

    static $epg_ord;

    if (!isset($ord)) {
        static $ord = 0;
    }

    $is_mkt_block = ($listtyp!='spice' && $x['NativeType']==3) ? true : false;
    $is_mkt_frag = ($listtyp=='spice' && $x['parent']['typ']=='mkt') ? true : false; // mkt frag can be mkt or clp

    if ($is_mkt_frag) {
        $prev_uid = (!$x['Queue']) ? '-1' : $ord;
    } else {
        $prev_uid = (!$ord) ? '-1' : $epg_ord;
        // For epg elements, PreviousUid can be only another epg element (i.e. mkt item cannot)
    }

    $ord++;

    if ($listtyp!='spice') {
        $epg_ord = $ord;
    }

    $sch_data['EV']['x'] = $x; // This way we pass it to morpheus_fields()
    //$sch_data['EV']['StartTime'] = strtoupper(date('d-M-Y H:i:s', strtotime($x['TermEmit']))).':00';
    $sch_data['EV']['Duration'] = $x['Duration'].':00';

    $event_attrz = [
        'Uid' => $ord,
        'FullyQualifiedType' => ((!$is_mkt_block) ? $sch_data['SCH']['MainEvString'] : 'System Default - Break Header'),
        //'NotionalStartTime' => $sch_data['EV']['StartTime'],
        //'NotionalDuration' => $sch_data['EV']['Duration'],
    ];

    $event_tagz = [
        //'ScheduleName' => $sch_data['SCH']['Name'],
        'ScheduleExternalId' => $sch_data['SCH']['ExternalId'],
        'PreviousUid' => $prev_uid,
        'OwnerUid' => (($is_mkt_frag) ? $epg_ord : '-1'),
        'IsFixed' => ((!empty($x['TimeAir']) || $is_mkt_frag) ? 'True' : 'False'),
        'EventKind' => ((!$is_mkt_block) ? 'MainEvent' : 'BreakHeader'),
    ];

    $z['event'] = xml_element_add($xml, 'Event', ['tagz' => $event_tagz, 'attrz' => $event_attrz]);

    $z['fields'] = morpheus_fields($listtyp, $xml, $sch_data);
    $z['event']->appendChild($z['fields']);
    unset($z['fields']);

    $eventz->appendChild($z['event']);
}



/**
 * Morpheus: add FIELDS node to EVENT node
 *
 * @param string $listtyp Type: (epg, spice)
 * @param object $xml XML object
 * @param array $sch_data Morpheus schedule data
 *
 * @return object $f FIELDS node
 */
function morpheus_fields($listtyp, $xml, $sch_data) {

    $x = $sch_data['EV']['x'];

    $sch_data['EV']['eng'] = morpheus_id($sch_data['EV']['x'], $sch_data, 'arr');

    $is_mkt_item = ($listtyp=='spice' && $x['NativeType']==3) ? true : false;
    $is_mkt_block = ($listtyp!='spice' && $x['NativeType']==3) ? true : false;


    $material_type = 'Programme';
    $server_src = $sch_data['SCH']['SvrRecs'];

    if ($is_mkt_item) {

        $material_type = 'Commercial';

    } elseif (in_array($x['NativeType'], [4,5])) { // prm, clp

        $material_type = 'Junction';

    } elseif ($x['NativeType']==1 && $x['PRG']['MatType']==1) { // PROG LIVE

        $material_type = 'Live';
        $server_src = $sch_data['SCH']['SvrLive'];
    }


    if (!$is_mkt_block) {

        $fields = [
            ['Name' => 'AudioMode', 'Value' => '33825'],
            ['Name' => 'AudioSource', 'Value' => $server_src],
            ['Name' => 'Duration', 'Value' => $sch_data['EV']['Duration']],
            ['Name' => 'DurationMode', 'Value' => 'Specified'],
            ['Name' => 'EventMaterialType', 'Value' => $material_type],
            ['Name' => 'EventName', 'Value' => $sch_data['EV']['eng']['EventName']],
            ['Name' => 'MainAudioSource', 'Value' => $server_src],
            ['Name' => 'MainVideoSource', 'Value' => $server_src],
            ['Name' => 'MaterialId', 'Value' => $sch_data['EV']['eng']['MaterialId']],
            //['Name' => 'StartMode', 'Value' => (($listtyp=='spice') ? 'ReferenceToParentsBeginning' : 'ReferenceToParentsEnd')], // vb2do
            ['Name' => 'StartMode', 'Value' => 'ReferenceToParentsEnd'],
            ['Name' => 'StartTimeOffset', 'Value' => '00:00:00:00'],
            ['Name' => 'VideoSource', 'Value' => $server_src],
        ];

    } else { // Mkt block

        $fields = [
            ['Name' => 'Duration', 'Value' => $sch_data['EV']['Duration']],
            ['Name' => 'DurationMode', 'Value' => 'UseChildren'],
            ['Name' => 'EventName', 'Value' => $sch_data['EV']['eng']['EventName']],
        ];
    }


    $f = $xml->createElement('Fields');

    foreach($fields as $attrz) {

        $p = $xml->createElement('Parameter');

        foreach($attrz as $k => $v) {
            $p->setAttribute($k, $v);
        }

        $f->appendChild($p);
    }

    return $f;
}



/**
 * Morpheus: get ID data
 *
 * @param array $x Element
 * @param string $rtyp Return type (id, arr)
 *
 * @return string|array
 */
function morpheus_id($x, $sch_data, $rtyp='id') {

    switch ($x['NativeType']) {

        case 1: // prog

            $x['PRG']['TeamID'] = rdr_cell('prgm', 'TeamID', $x['PRG']['ProgID']);

            if ($x['PRG']['TeamID']<99 && $x['AttrA']) {
                $x['PRG']['TeamID'] = $x['AttrA'];
            }

            switch ($x['PRG']['TeamID']) {

                case 101:   $z['team'] = 'EIP'; break;
                case 102:   $z['team'] = 'ESP'; break;
                case 103:   $z['team'] = 'EZP'; break;
                case 104:   $z['team'] = 'EDK'; break;
                case 105:   $z['team'] = 'EDO'; break;
                case 106:   $z['team'] = 'EDO'; break;
                case 107:   $z['team'] = 'EDE'; break;
                case 108:   $z['team'] = 'EDE'; break;
                case 109:   $z['team'] = 'EDE'; break;
                case 120:   //$z['team'] = 'EST'; break;

                case 1:
                default:    $z['team'] = 'EIP'; break;
            }

            $z['ymd'] = $sch_data['NME']['ymd'];
            $z['title'] = $name = $x['PRG']['ProgCPT'];
            break;

        case 12: // film

            $z['team'] = 'EFL';
            $z['title'] = $name = $x['FILM']['Title'];
            break;

        case 13: // serial

            $z['team'] = 'ESR';
            $z['title'] = $name = $x['FILM']['Title'].' '.$x['FILM']['Ordinal'].'-'.$x['FILM']['EpisodeCount'];
            break;

        case 3: // mkt

            if (isset($x['BLC'])) { // block

                $name = 'Marketing'.((!empty($x['BLC']['Caption'])) ? ': '.$x['BLC']['Caption'] : ' - '.$x['Duration']);

            } else { // item

                $z['title'] = 'MKT_'.$x['NativeID'];

                $name = $x['Caption'];
            }

            break;

        case 4: // prm

            $ctg_name = txarr('arrays', 'epg_prm_ctgz', $x['AttrA']);

            $z['team'] = 'T'.$x['EPG']['ChannelID'].((!in_array($x['AttrA'], [6,7,8])) ? 'P' : 'O');

            $z['title'] = (!empty($x['Caption'])) ? $x['Caption'] : $ctg_name;

            $name = $ctg_name.((!empty($x['Caption'])) ? ': '.$x['Caption'] : '');

            break;

        case 5: // klip

            $z['team'] = 'T'.$x['EPG']['ChannelID'].'J';
            $z['title'] = $x['CLP']['Caption'];

            $name = 'Klip: '.$x['CLP']['Caption'];

            break;
    }

    if (!empty($z['title'])) {
        $z['title'] = strtoupper(text_convert($z['title'], 'cyr', 'lateng'));
        $z['title'] = str_replace([',', '.', ':', '!', '(', ')', '&quot;', '&#039;'], '', $z['title']);
    }

    $r['MaterialId'] = (!empty($z)) ? implode('_', $z) : '';

    if (!empty($_POST['tr'.$x['ID']])) {
        $r['MaterialId'] = $_POST['tr'.$x['ID']];
    }


    if ($rtyp=='id') {

        return $r['MaterialId'];

    } else {

        $r['EventName'] = '('.((@$x['OnHold']) ? '* * *' : date('H:i:s', strtotime($x['TermEmit']))).') '.
            ((!empty($name)) ? text_convert($name, 'cyr', 'lateng') : '');

        return $r;
    }
}



/**
 * Morpheus: add header nodes
 *
 * @param object $xml XML object
 * @param array $sch_data Morpheus schedule data
 *
 * @return object $z XML nodes array
 */
function morpheus_start($xml, $sch_data) {

    $z['sch'] = xml_element_add($xml, 'Schedule',
        ['attrz' => ['Name' => $sch_data['NME']['name'], 'ProdeskUser' => UZID, 'ProdeskTime' => TIMENOW]]);

    $z['events'] = xml_element_add($xml, 'Events',
        ['attrz' => ['Channel' => ((SERVER_TYPE=='dev') ? 'Channel 1' : $sch_data['SCH']['Channel'])]]);

    $z['schinfolist'] = xml_element_add($xml, 'ScheduleInformationList');
    $z['schinfo'] = xml_element_add($xml, 'ScheduleInformation',
        ['attrz' => ['Name' => $sch_data['SCH']['Name'], 'ExternalId' => $sch_data['SCH']['ExternalId']]]);

    $z['schinfolist']->appendChild($z['schinfo']);
    $z['events']->appendChild($z['schinfolist']);

    return $z;
}

/**
 * Morpheus: finish-up
 *
 * @param object $xml XML object
 * @param object $z XML nodes array
 *
 * @return void
 */
function morpheus_finito($xml, $z) {

    $z['sch']->appendChild($z['events']);

    $xml->appendChild($z['sch']);

    $xml->formatOutput = true;

    print $xml->saveXML();
}
