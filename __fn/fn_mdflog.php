<?php


// MDF LOG




/**
 * Save MDFLOG and handle conflicts and return ID (row id in log_mdf table)
 *
 * @return int MDFLOG ID
 */
function mdflog() {

    $mdflog['itemtyp'] = mdflog_itemtyp();

    if (!$mdflog['itemtyp']) {

        log2file('srpriz', ['type' => 'mdflog_itemtype']);

        return null;
    }

    $mdflog['itemid'] = mdflog_uri_itemid();

    $mdflog['id'] = mdflog_put($mdflog['itemtyp'], $mdflog['itemid']); // Write log

    mdflog_conflict($mdflog['itemtyp'], $mdflog['itemid']);

    return $mdflog['id'];
}


/**
 * Check for MDFLOG conflict.
 *
 * @param int $mdftyp ItemType
 * @param int $mdfid ItemID
 * @param string $rtyp Return type (omg, msg)
 *
 * @return void|string
 */
function mdflog_conflict($mdftyp, $mdfid, $rtyp='omg') {

    global $tx;

    $mdflog['conflicts'] = mdflog_get($mdftyp, $mdfid); // Get conflicting logs

    if ($mdflog['conflicts']) { // On conflict, display omg

        foreach ($mdflog['conflicts'] as $k => $v) {
            $mdflog['conflicts'][$k] = uid2name($v);
        }

        $msg = sprintf($tx['MSG']['mdflog_conflict'], implode(', ', $mdflog['conflicts']));

        if ($rtyp=='omg') {

            omg_put('warning', $msg);

        } else {

            return $msg;
        }
    }
}


/**
 * Get MDFLOG ItemType. (Helper function for mdflog())
 *
 * By default, we use TBLID (from *tablez*) as the item_type. There are several exceptions, when table-id cannot be used.
 * For these cases, we use numbers starting from 255 descending.. It doesnot actually matter which number we chose, it is
 * only important that the *same* number is always used for the same mdf page..
 *
 * @return int $itemtyp ItemType
 */
function mdflog_itemtyp() {

    global $x, $pathz;

    $itemtyp = 0;

    if (isset($x['TBL'])) {

        switch ($x['TBL']) {

            case 'stryz':
                $itemtyp = ($_GET['typ']=='mdf_atom') ? 250 : 249; break; // mdf_dsc OR mdf_atom

            case 'film':
                $itemtyp = (@$_GET['typ']=='item') ? 248 : 247; break; // item OR item_episodes (film_modify_episodes)

            case 'epg_blocks':
                $itemtyp = ($x['EPG_SCT']=='mkt') ? 246 : 245; break; // mkt OR prm

            default:
                $itemtyp = (isset($x['TBLID'])) ? $x['TBLID'] : tablez('id', $x['TBL']);
                // By default, we use Table ID as the item_type
        }

    } else {

        $script = $pathz['dir_1st'].'/'.$pathz['filename'];

        if ($script=='epg/epg_modify_single') {

            $itemtyp = (isset($_GET['epg'])) ? 255 : 254; // epg OR scnr

        } elseif ($script=='epg/epg_modify_multi') {

            $itemtyp = ($_GET['typ']=='epg') ? 253 : 252; // epg OR scnr
        }
    }


    return $itemtyp;
}


/**
 * Get MDFLOG ItemID from URI. (Helper function for mdflog())
 *
 * @return int $mdfid ItemID
 */
function mdflog_uri_itemid() {

    $mdfid = (isset($_GET['id'])) ? intval($_GET['id']) : 0;

    return $mdfid;
}


/**
 * Write MDFLOG. (Helper function for mdflog())
 *
 * @param int $mdftyp ItemType
 * @param int $mdfid ItemID
 *
 * @return int $r MDFLOG ID
 */
function mdflog_put($mdftyp, $mdfid) {

    qry('DELETE FROM log_mdf WHERE UID='.UZID.' AND ItemType='.$mdftyp.' AND ItemID='.$mdfid);

    $sql = 'INSERT INTO log_mdf (ItemType, ItemID, UID, TermAccess) '.
        'VALUES ('.$mdftyp.', '.$mdfid.', '.UZID.', now())';
    $r = qry($sql, LOGSKIP);

    return $r;
}


/**
 * Get conflicting MDFLOGs. (Helper function for mdflog())
 *
 * @param int $mdftyp ItemType
 * @param int $mdfid ItemID
 *
 * @return array $r UIDs
 */
function mdflog_get($mdftyp, $mdfid) {

    global $cfg;

    // Get UIDs where this same item has been mdf_logged within specified interval (e.g. last two minutes)

    $sql = 'SELECT UID FROM log_mdf WHERE ItemType='.$mdftyp.' AND ItemID='.$mdfid.' AND UID!='.UZID.
        ' AND TermAccess > date_sub(NOW(), interval '.$cfg['mdflog_expire'].' second)';
    $r = qry_numer_arr($sql);

    return $r;
}

