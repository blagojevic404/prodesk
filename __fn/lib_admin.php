<?php



// SETZ


/**
 * Saves *local* admin cfg setting to DB:cfg_varz. (Only if it differs from the *global* setting.)
 *
 * @param string $name Setting name
 * @param int $vlu Setting value
 *
 * @return void
 */
function admin_cfg_put($name, $vlu) {

    $sct = 'admin';

    $where = 'Section=\''.$sct.'\' AND Name=\''.$name.'\'';

    $vlu_global = txt_rdr('cfg', 'varz', $sct, $name, null, ['cfg_skip_local' => true]);

    $vlu_local = cfg_local('varz', $sct, $name);


    if ($vlu) {

        if ($vlu==$vlu_global) {

            if ($vlu_local) { // delete it
                qry('DELETE FROM cfg_varz WHERE '.$where);
            }

        } else {

            if ($vlu!=$vlu_local) {

                if ($vlu_local) { // update

                    qry('UPDATE cfg_varz SET Value='.$vlu.' WHERE '.$where);

                } else { // insert

                    qry('INSERT INTO cfg_varz (Section, Name, Value) VALUES (\''.$sct.'\', \''.$name.'\', '.$vlu.')', LOGSKIP);
                }
            }
        }

    } else {

        if ($vlu_local) { // delete it
            qry('DELETE FROM cfg_varz WHERE '.$where);
        }
    }
}



/**
 * SETTINGS: Output a specific config block with TEXTBOX controls
 *
 * @param array $blocks Config blocks
 * @return void
 */
function admin_cfg_block($blocks) {

    global $tx, $cfg;

    $ajaxdata = [
        'tblid' => '54',
        'itemid' => '1', // just a placeholder because int value must exist
        'cln' => '%s', // we send the cfg name through cln attribute
        'pms' => 'admin.spec',
        'valtyp' => 'int',
    ];

    $tr = '<tr><td>%s</td><td class="col-xs-1"><div class="detaildiv text-right" '.
        'onclick="editdivable_line(this); return false;" data-ajax="'.implode(',', $ajaxdata).'" data-type="admin_cfg">'.
        '%s</td></tr>';

    foreach($blocks as $block_name => $block) {

        form_accordion_output('head', $block['title'], $block_name);

        $html = [];

        foreach($block['lines'] as $v) {

            $html[] = sprintf($tr, $tx[SCTN]['MSG']['set_'.$v], $v, $cfg[SCTN][$v]);
        }

        echo '<table class="table table-hover setz" id="mdf_tbl">'.implode('', $html).'</table>';

        form_accordion_output('foot');
    }
}







// TOOLS







// LOG

/**
 * Get human filesize
 * http://php.net/manual/en/function.filesize.php#106569
 */
function human_filesize($bytes, $decimals = 2) {
    $sz = 'BKMGTP';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}





// FTP

/**
 * FTP: Turn permissions octal to more readable permissions string e.g. "33206" to "-rw-rw-rw- (666)"
 *
 * @param int $perms Permissions decimal value, as returned from fileperms() function
 *
 * @return string $info Permissions in readable format + octal value (r=4, w=2, x=1)
 */
function ftp_perms_prettify($perms) {

    // Type
    switch ($perms & 0xF000) {
        case 0xC000: // socket
            $info = 's';
            break;
        case 0xA000: // symbolic link
            $info = 'l';
            break;
        case 0x8000: // regular file
            $info = '-';
            break;
        case 0x6000: // block special
            $info = 'b';
            break;
        case 0x4000: // directory
            $info = 'd';
            break;
        case 0x2000: // character special
            $info = 'c';
            break;
        case 0x1000: // FIFO pipe
            $info = 'p';
            break;
        default: // unknown
            $info = 'u';
    }

    // Owner pms
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
        (($perms & 0x0800) ? 's' : 'x' ) :
        (($perms & 0x0800) ? 'S' : '-'));

    // Group pms
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
        (($perms & 0x0400) ? 's' : 'x' ) :
        (($perms & 0x0400) ? 'S' : '-'));

    // World pms
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
        (($perms & 0x0200) ? 't' : 'x' ) :
        (($perms & 0x0200) ? 'T' : '-'));


    $info = decoct($perms & 0777).' ('.$info.')';
    //$info .= ' ('.substr(decoct($perms), 3).')'; // the other way to do the same, apparently


    return $info;
}








// AD




/**
 * AD: LDAP connect
 *
 * @return array $ldap
 */
function ad_connect() {

    $cfg_boot = cfg_global('boot', 'cfg');

    $ad = [
        'DC' => ((AD_HTTPS) ? 'ldaps' : 'ldap').'://'.cfg_global('boot', 'dc', 0),
        'Admin' => cfg_global('boot', 'dc:admin'),
        'Domain' => $cfg_boot['AD_Domain'],
    ];


    $ldap['con'] = ldap_connect($ad['DC']);

    ldap_set_option($ldap['con'], LDAP_OPT_PROTOCOL_VERSION, 3); // If I skip this, ldap_rename doesn't work
    ldap_set_option($ldap['con'], LDAP_OPT_REFERRALS, 0);

    $ldap['bind'] = ldap_bind(
        $ldap['con'],
        $ad['Admin']['u'].'@'.$ad['Domain'],
        descrambler($ad['Admin']['p'])
    );


    $dn = explode('.', $ad['Domain']);

    foreach ($dn as $k => $v) {
        $dn[$k] = 'DC='.$v;
    }

    $ldap['domain']['dn'] = implode(',', $dn);

    $ldap['domain']['dns'] = $ad['Domain'];


    $ldap['ou_root'] = 'OU=Users,OU=Accounts';


    return $ldap;
}



/**
 * AD: Get OU list from USERS ou (for OU combo)
 *
 * @return array $z OU list
 */
function ad_ou_list() {

    global $tx;

    if (SERVER_TYPE=='dev') {
        return [];
    }


    $z = ad_entries_search('(OU=*)', ['ou']);
    $z = ad_entries_clean('ou', $z);
    array_unshift($z, $tx['LBL']['undefined']);

    return $z;
}



/**
 * AD: Get AD entries by search filter
 *
 * @param string $filter Filter, i.e. '(OU=*)'
 * @param array $attrz AD attributes to be fetched
 *
 * @return array $r AD search results
 */
function ad_entries_search($filter, $attrz) {

    global $ldap;

    $dn = $ldap['ou_root'].','.$ldap['domain']['dn'];

    $r = ldap_search($ldap['con'], $dn, $filter, $attrz);

    $r = ldap_get_entries($ldap['con'], $r);

    return $r;
}



/**
 * AD: Sort and clean entries data returned from the filter search
 *
 * @param string $typ Type ('sam', 'ou')
 * @param array $z Entries data array
 *
 * @return array $r Sorted and cleaned entries array
 */
function ad_entries_clean($typ, $z) {

    $r = [];

    foreach($z as $k => $v) {

        if ($k==='count') {
            // Must use *===* instead of simply *==*, because DATA TYPE of $k is set to boolean on first loop,
            // and then on second loop any string value returns TRUE..
            continue;
        }

        if ($typ=='sam') {

            $r[$v['samaccountname'][0]] = ['cn' => $v['cn'][0], 'dn' => $v['dn']];
        }

        if ($typ=='ou') {

            if ($v['ou'][0]=='Users') {
                continue;
            }

            $r[] = $v['ou'][0];
        }
    }

    if ($r) {
        ksort($r);
    }

    return $r;
}



/**
 * AD: Format AD account data
 *
 * @param array $info AD account data in raw array format, as returned from LDAP search
 *
 * @return string $r['str']
 */
function ad_info_format($info) {

    foreach($info as $k => $v) {

        if (is_integer($k)) {

            //$r['int'][$k] = $v;

        } else {

            if (isset($v['count']) && $v['count']==1) {
                $v = $v[0];
            }

            $r['str'][$k] = $v;

            if ($k=='useraccountcontrol') {

                $r['str'][$k] .=
                    ' ('.implode(ad_uac_flags($v), ', ').')'.
                    '<a target="_blank" class="helper pull-right" href="https://support.microsoft.com/en-us/kb/305144">'.
                    '<span class="glyphicon glyphicon-question-sign"></span></a>';

            } elseif ($k=='memberof' && isset($r['str'][$k]['count'])) {

                unset($r['str'][$k]['count']);

            } elseif (in_array($k, ['badpasswordtime', 'lastlogon', 'pwdlastset', 'accountexpires', 'lastlogontimestamp'])) {

                $r['str'][$k] = ($r['str'][$k]) ? date("Y-m-d H:i:s", $r['str'][$k]/10000000-11644473600) : '-';
                // 18-digit LDAP/FILETIME, i.e. Windows NT time format (http://www.epochconverter.com/ldap)
            }

            if (is_array($r['str'][$k])) {

                $r['str'][$k] = '<ul><li>'.implode('</li><li>', $r['str'][$k]).'</li></ul>';
            }
        }
    }

    return $r['str'];
}



/**
 * AD: Convert UserAccountControl numerical FLAGS constant to readable attribute flags array
 *
 * @param int $uac_const UserAccountControl numerical FLAGS constant
 * @param string $rtyp Return type (str, num)
 *
 * @return array $flags Readable attribute flags
 */
function ad_uac_flags($uac_const, $rtyp='str') {

    $flags = [];

    for ($i=0; $i<=26; $i++) {
        if ($uac_const & (1 << $i)) {
            array_push($flags, 1 << $i);
        }
    }

    $flag_consts = [                                        // https://support.microsoft.com/en-us/kb/305144

        1 => 'SCRIPT',
        2 => 'ACCOUNTDISABLE',
        8 => 'HOMEDIR_REQUIRED',
        16 => 'LOCKOUT',
        32 => 'PASSWD_NOTREQD',
        64 => 'PASSWD_CANT_CHANGE',
        128 => 'ENCRYPTED_TEXT_PWD_ALLOWED',
        256 => 'TEMP_DUPLICATE_ACCOUNT',
        512 => 'NORMAL_ACCOUNT',
        2048 => 'INTERDOMAIN_TRUST_ACCOUNT',
        4096 => 'WORKSTATION_TRUST_ACCOUNT',
        8192 => 'SERVER_TRUST_ACCOUNT',
        65536 => 'DONT_EXPIRE_PASSWORD',
        131072 => 'MNS_LOGON_ACCOUNT',
        262144 => 'SMARTCARD_REQUIRED',
        524288 => 'TRUSTED_FOR_DELEGATION',
        1048576 => 'NOT_DELEGATED',
        2097152 => 'USE_DES_KEY_ONLY',
        4194304 => 'DONT_REQ_PREAUTH',
        8388608 => 'PASSWORD_EXPIRED',
        16777216 => 'TRUSTED_TO_AUTH_FOR_DELEGATION',
        67108864 => 'PARTIAL_SECRETS_ACCOUNT',
    ];


    if ($rtyp=='str') {

        foreach($flags as $k => $v) {
            $flags[$k] = $flag_consts[$v];
        }
    }

    return $flags;
}



/**
 * AD: Check whether account is disabled (which is done by UserAccountControl flag)
 *
 * @param int $uac_const UserAccountControl numerical FLAGS constant
 * @return bool $is_disabled
 */
function ad_uac_is_disabled($uac_const) {

    $flags = ad_uac_flags($uac_const, 'num');

    $is_disabled = (in_array(2, $flags)) ? true : false;  // 2 => 'ACCOUNTDISABLE'

    return $is_disabled;
}



/**
 * AD: Convert *dn* to short path string
 *
 * @param string $dn dn (distinguished name)
 * @param string $rtyp Return type: ('path', 'ou', 'ou_single')
 *
 * @return string $r Shortened dn in path format
 */
function ad_dn_short($dn, $rtyp='ou') {

    $arr = explode(',', $dn);

    krsort($arr);

    $r = [];

    foreach($arr as $v) {

        $rdn = strtoupper(substr($v, 0, 2));

        if (in_array($rdn, ['CN', 'DC'])) { // skip them
            continue;
        }

        if ($rtyp!='path' && in_array($v, ['OU=Users', 'OU=Accounts'])) { // skip them
            continue;
        }

        $ou = substr($v,3);

        if ($rtyp=='ou_single') {

            return $ou;

        } else {

            $r[] = $ou;
        }
    }

    return implode(' &rsaquo; ', $r);
}



/**
 * AD: Create password (seven consonants (with lead capital) and vowels alternately, and then two numbers)
 */
function pass_create() {

    $pool[1] = 'aeiou';
    $pool[2] = 'bcdfghjklmnprstvz';
    $pool[3] = '0123456789';

    $p = '';

    for ($i=0; $i<9; $i++) {

        if ($i > 6) {

            $pool_k = 3;

        } elseif ($i % 2 == 0) {

            $pool_k = 2;

        } else {

            $pool_k = 1;
        }

        $n = rand(0, strlen($pool[$pool_k])-1);

        $char = $pool[$pool_k][$n];

        if ($i==0) {
            $char = strtoupper($char);
        }

        $p .= $char;
    }

    return $p;
}



/**
 * AD: Convert password to format required by AD
 *
 * Password has to be enclosed in quotes, and in *utf-16* encoding.
 *
 * @param string $pass Password
 *
 * @return string $pass Password formatted for AD
 */
function ad_pass_format($pass) {

    $pass = mb_convert_encoding('"'.$pass.'"', 'utf-16le');

    // This would probably work too: iconv('UTF-8', 'UTF-16LE', $pass)

    return $pass;
}


/**
 * Output modal for password change (used in admin and hrm/uzr_details)
 *
 * @param string $typ Type (button, poster)
 *
 * @return void
 */
function pass_modal_output($typ) {

    global $tx;

    if ($typ=='button') {

        modal_output('button', 'poster',
            [
                'name_prefix' => 'adpass',
                'pms' => true,
                'button_txt' => '<span class="glyphicon glyphicon-lock"></span>',
                'button_css' => 'btn-info btn-sm',
                'button_title' => $tx['LBL']['pass']
            ]
        );

    } else { // poster

        modal_output('modal', 'poster',
            [
                'name_prefix' => 'adpass',
                'pms' => true,
                'submiter_href' => '',
                'modal_size' => 'modal-sm',
                'cpt_header' => $tx['LBL']['modify'],
                'txt_body' =>
                    ctrl('modal', 'password', $tx['LBL']['pass'], 'pass').
                    '<p><small>('.$tx['admin']['MSG']['ad_pass_rules'].')</small><p>'.
                    '<small>* '.$tx['admin']['MSG']['ad_pass_blank'].'</small>'
            ]
        );
    }
}



/**
 * Receiver for password change (used in admin and hrm/uzr_details)
 */
function pass_receiver() {

    global $tx;

    if ($_POST['pass']) {

        $pass = wash('ad_account', $_POST['pass']);

        if ($pass!=$_POST['pass']) {
            omg_put('error', sprintf($tx['admin']['MSG']['err_sec_format'], $tx['LBL']['pass']));
            hop($_SERVER['HTTP_REFERER']); // Reload the requesting page
        }

    } else {

        $pass = pass_create();
    }

    $r = ad_account_mdf('unicodePwd', ad_pass_format($pass));

    if ($r) {

        omg_put('success', $tx['LBL']['pass'].': '.$pass);
        // vbdo: when we connect mail/sms notes, we can stop showing pass to admin here

        log2file('actdir', ['ACT' => ((SCTN=='admin') ? 'PASS' : 'PASS_SELF'), 'SAM' => SAM]);

    } else {

        omg_put('danger', '"'.ldap_error(LDAP_CON).'" <small>('.$tx['admin']['MSG']['err_pass_req'].')</small>');
    }

    hop($_SERVER['HTTP_REFERER']); // Reload the requesting page
}




/**
 * AD: Fetch specific attribute for the specified *samaccountname*
 *
 * @param string $sam samaccountname
 * @param string $attr Attribute name
 *
 * @return string|null ATTRIBUTE on success, NULL if *samaccountname* doesnot exist
 */
function ad_fetch_attr($sam, $attr) {

    $z = ad_entries_search('(samaccountname='.$sam.')', [$attr]);

    $attr = ($z['count']) ? $z[0][$attr][0] : null;

    return $attr;
}



/**
 * AD: Switch account off/on
 *
 * @return bool New condition (true=enabled, false=disabled)
 */
function ad_account_switch() {

    $uac_const = ad_fetch_attr(SAM, 'useraccountcontrol');

    $is_disabled = ad_uac_is_disabled($uac_const);

    $uac_const_new = ($is_disabled) ? $uac_const-2 : $uac_const+2;

    ad_account_mdf('useraccountcontrol', $uac_const_new);

    return $is_disabled;
}



/**
 * AD: Account attribute modify
 *
 * @param string $attr Attribute name
 * @param string $vlu Attribute value
 *
 * @return bool Success/Fail
 */
function ad_account_mdf($attr, $vlu) {

    global $dn;

    $r = ldap_mod_replace(LDAP_CON, $dn, [$attr => $vlu]);

    return $r;
}


