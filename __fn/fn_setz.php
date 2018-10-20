<?php


// SETTINGS PAGES




/**
 * Get Setting value from *global* scope
 *
 * @param string $name Name
 *
 * @return string $r Value
 */
function stz($name) {

    global $stz;

    $r = $stz[$name];

    return $r;
}




/**
 * Reads a settings from DB:settingz. First it tries *custom* (i.e. user-defined) setz, then it tries *global* setz
 *
 * @param string $name Setting name
 * @param int $uid User ID
 *
 * @return int Setting value
 */
function setz_get($name, $uid=UZID) {

    $sql = 'SELECT SettingValue FROM settingz WHERE SettingName=\''.$name.'\' AND UID=';

    $line = qry_assoc_row($sql.$uid);           // Read from the *custom* level

    if (!isset($line['SettingValue'])) {
        $line['SettingValue'] = stz($name);     // Read from the *global* level
        //$line = qry_assoc_row($sql.'0'); // how it worked when we used 0-UID in db table instead of txt file
    }

    return $line['SettingValue'];
}






/**
 * Saves *custom* setting to DB:settingz. (Only if it differs from the *global* setting.)
 *
 * Uses UZID constant to specify USER
 *
 * @param string $name Setting name
 * @param int $vlu Setting value
 *
 * @return void
 */
function setz_put($name, $vlu) {

    $sql = 'SELECT SettingValue FROM settingz WHERE SettingName=\''.$name.'\' AND UID=';

    $line_custom = qry_assoc_row($sql.UZID);       // Read from the *custom* level

    $line_global['SettingValue'] = stz($name);        // Read from the *global* level
    //$line_global = qry_assoc_row($sql.'0'); // how it worked when we used 0-UID in db table instead of txt file

    // If we have the same setz defined on the *global* level and with the same value,
    // there is no need to also have it on the *custom* level.

    if (isset($line_global['SettingValue']) && $line_global['SettingValue']==$vlu) {

        if (isset($line_custom['SettingValue'])) {

            qry('DELETE FROM settingz WHERE UID='.UZID.' AND SettingName=\''.$name.'\'');
        }

        return;
    }


    if (isset($line_custom['SettingValue'])) {      // setz already exists at the *custom* level

        if ($line_custom['SettingValue']==$vlu) {   // IGNORE setz which are already saved to *custom* level with same value

            return;

        } else { // Otherwise UPDATE it

            qry('UPDATE settingz SET SettingValue='.$vlu.' WHERE UID='.UZID.' AND SettingName=\''.$name.'\'');
        }

    } else { // setz doesnot exist at the *custom* level, so INSERT it

        qry('INSERT INTO settingz (UID, SettingName, SettingValue) VALUES ('.UZID.', \''.$name.'\', '.$vlu.')', LOGSKIP);
    }

}



/**
 * Shortcut for setz_put(). Reads POST value for specified name and saves it to DB:settingz.
 *
 * @param int $name Setting name
 * @param int $def Default value
 *
 * @return void
 */
function setz_put_post($name, $def=0) {

    setz_put($name, ((isset($_POST[$name])) ? intval($_POST[$name]) : $def));
}



/**
 * Receiver for *settings* pages
 *
 * @param array $setz Settings array
 * @return void
 */
function setz_receiver($setz) {

    global $tx;

    foreach($setz['lines'] as $v) {
        setz_put($v, ((isset($_POST[$v])) ? intval($_POST[$v]) : 0));
    }

    omg_put('success',  $tx['MSG']['set_saved'].' &mdash; '.$setz['title']);
}






/**
 * Output a specific settings block with CHK controls, used on *settings* pages
 *
 * @param array $setz Settings array
 * @return void
 */
function setz_block_chk($setz) {

    global $tx, $bs_css;

    form_tag_output('open', '', true, 'form_'.$setz['name']);

    $li = '<li class="list-group-item"><div class="checkbox"><label>'.
        '<input name="%s" value="1" type="checkbox" %s>%s</label></div></li>';

    $html = [];

    foreach($setz['lines'] as $v) {
        $html[] = sprintf($li, $v, ((setz_get($v)) ? 'checked' : ''), $tx[SCTN]['MSG']['set_'.$v]);
    }

    echo
        '<div class="'.$bs_css['half'].'"><div class="panel panel-default setz">'.
        '<div class="panel-heading"><h3 class="panel-title">'.$setz['title'].'</h3></div>'.
        '<ul class="list-group">'.implode('', $html).'</ul>'.
        '<div class="panel-footer text-right">'.
            form_btnsubmit_output('Submit_'.$setz['name'], ['type' => 'btn_only', 'css' => 'btn-xs'], false).
        '</div>'.
        '</div></div>';

    form_tag_output('close');
}





/**
 * Switch on/off the specified constant, using setz and session
 *
 * @param string $name Name used in $_GET variable, $_SESSION variable, and for const name and setz name
 * @param bool $do_switch Whether to do switch, or just set the const
 * @param bool $do_reload Whether to reload the page after the setz switch
 *
 * @return void
 */
function setz_switcher($name, $do_switch=true, $do_reload=true) {

    $name_caps = strtoupper($name);

    if ($do_switch) {

        if (isset($_GET[$name])) {

            $z = intval($_GET[$name]);

            define($name_caps, $z);

            $_SESSION[$name_caps] = $z;

            setz_put($name, $z);

            if ($do_reload) {
                hop($_SERVER['HTTP_REFERER']); // Reload the requesting page
            }

        } else {

            define($name_caps, (isset($_SESSION[$name_caps]) ? $_SESSION[$name_caps] : setz_get($name)));
        }

    } else {

        define($name_caps, null); // This is only to avoid errors when using this const in conditionals
    }
}
