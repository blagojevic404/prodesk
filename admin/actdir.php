<?php
/**
 * Handle Active Directory (AD) operations
 *
 * Note: Will show nothing on DEV side.
 *
 * You can use $_GET['all'] to show ALL attributes fetched for the AD account
 *
 * For install tips, see:
 * http://pig.made-it.com/pig-adusers.html
 * http://us2.php.net/manual/en/function.ldap-connect.php#36156
 */


require '../../__ssn/ssn_boot.php';

pms('admin', null, null, true);

/***************************************************************/





define('AD_HTTPS', true);
// To be able to make modifications to Active Directory via the LDAP connector you must bind to the LDAP service over SSL.
// Otherwise Active Directory provides a mostly readonly connection. You cannot add objects or modify certain properties
// without LDAPS, e.g. passwords can only be changed using LDAPS connections to Active Directory.


if (SERVER_TYPE!='dev') {

    $ldap = ad_connect();
    define('LDAP_CON', $ldap['con']);
}


/* You can use $_GET['all'] to show ALL attributes fetched for the AD account */
define('ALL_ATTRZ', ((empty($_GET['all'])) ? false : true));


$abv_arr = explode(' ', 'A B C D E F G H I J K L M N O P Q R S T U V W X Y Z');
define('ABV', ((!empty($_GET['abv'])) ? wash('arr_assoc', @$_GET['abv'], $abv_arr) : ''));


define('GGL', ((!empty($_GET['ggl'])) ? wash('ad_account', @$_GET['ggl']) : ''));


$dn = '';

if (empty($_GET['sam']) || SERVER_TYPE=='dev') {

    $sam = '';

} else {

    $sam = wash('ad_account', $_GET['sam']);

    if ($sam===null) { // WASH will return NULL on fail

        omg_put('error',  sprintf($tx[SCTN]['MSG']['err_sec_format'],
            $tx['NAV'][83].' '.$tx['LBL']['account'].': '.$tx['LBL']['name']));
    }

    if ($sam) { // On success, we get DN

        $dn = ad_fetch_attr($sam, 'distinguishedname');
    }
}

define('SAM', $sam);




$ou_arr = ad_ou_list();
// vbdo: ctrl combo next to search ctrl.. button to refresh ou_list, otherwise we save it somewhere on the db,
// so we don't have to call this function each time.. ok?




// RECEIVER

if ($dn) {

    if (!empty($_POST['mdf'])) {


        // GET CURRENT AND MODIFIED DATA

        $cur = ad_entries_search('(samaccountname='.SAM.')', ['givenname', 'sn', 'cn']);

        $cur = [
            'givenname' => $cur[0]['givenname'][0],
            'sn'        => $cur[0]['sn'][0],
            'cn'        => $cur[0]['cn'][0],
        ];

        $mdf['givenname']      = wash('cpt', $_POST['givenname']);
        $mdf['sn']             = wash('cpt', $_POST['sn']);
        $mdf['samaccountname'] = wash('ad_account', $_POST['samaccountname']);

        $cur_ou = ad_dn_short($dn, 'ou_single');

        $mdf_ou = ($_POST['ou']) ? $ou_arr[$_POST['ou']] : 'TEST';


        // CHANGE OU (+ department)

        if ($cur_ou!=$mdf_ou) {

            $r = ldap_rename(LDAP_CON, $dn, 'CN='.$cur['cn'],
                'OU='.$mdf_ou.','.$ldap['ou_root'].','.$ldap['domain']['dn'], true);

            $cur_ou = $mdf_ou; // $cur_ou variable is used below, to construct DN, so we must update..

            $dn = ad_fetch_attr(SAM, 'distinguishedname'); // DN has changed, so we must update it right away

            ad_account_mdf('department', $mdf_ou);

            log2file('actdir', ['ACT' => 'MDF_OU ('.$mdf_ou.')', 'SAM' => SAM]);
        }


        // CHANGE samaccountname (+ userPrincipalName)

        if (SAM!=$mdf['samaccountname']) {

            $r = ad_account_mdf('samaccountname', $mdf['samaccountname']);

            if ($r) {

                ad_account_mdf('userPrincipalName', $mdf['samaccountname'].'@'. $ldap['domain']['dns']);

                log2file('actdir', ['ACT' => 'MDF_SAM ('.$mdf['samaccountname'].')', 'SAM' => SAM]);

            } else {

                omg_put('danger', '"'.ldap_error(LDAP_CON).'"');

                $mdf['samaccountname'] = SAM; // We use $mdf['samaccountname'] in header() below, so we must change to SAM on fail..
            }
        }


        // CHANGE givenname/sn (+ cn)

        if ($cur['sn']!=$mdf['sn'] || $cur['givenname']!=$mdf['givenname']) {

            if ($cur['sn']!=$mdf['sn']) {
                ad_account_mdf('sn', $mdf['sn']);
            }

            if ($cur['givenname']!=$mdf['givenname']) {
                ad_account_mdf('givenname', $mdf['givenname']);
            }

            $r = ldap_rename(LDAP_CON, $dn, 'CN='.$mdf['givenname'].' '.$mdf['sn'],
                'OU='.$cur_ou.','.$ldap['ou_root'].','.$ldap['domain']['dn'], true);
            // DN has changed, but we don't have to update because there is nothing below

            log2file('actdir', ['ACT' => 'MDF_NAME ('.$mdf['givenname'].' '.$mdf['sn'].')', 'SAM' => SAM]);
        }


        hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?sam='.$mdf['samaccountname']);
    }


    if (isset($_POST['pass'])) {
        pass_receiver();
    }


    if (!empty($_GET['del'])) {

        $r = ldap_delete(LDAP_CON, $dn);

        if ($r) {

            omg_put('info', $tx['MSG']['deleted']);

            log2file('actdir', ['ACT' => 'DELETE', 'SAM' => SAM]);

        } else {

            omg_put('danger', '"'.ldap_error(LDAP_CON).'"');
        }

        hop($pathz['www_root'].$_SERVER['SCRIPT_NAME']);
    }


    if (!empty($_GET['switch'])) {

        $new_condition = ad_account_switch();

        log2file('actdir', ['ACT' => 'SWITCH ('.(($new_condition) ? 'on' : 'off').')', 'SAM' => SAM]);

        hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?sam='.SAM);
    }
}



if (!empty($_POST['new'])) {


    // Predefined values

    $mdf['objectClass'] = ['top', 'person', 'organizationalPerson', 'user'];
    $mdf['instanceType'] = '4';
    $mdf['userAccountControl'] = '66048';
    $mdf['lockoutTime'] = '0';
    $mdf['accountExpires'] = '9223372036854775807'; // LDAP time for *never*


    // Input (post) values

    $ou = ($_POST['ou']) ? $ou_arr[$_POST['ou']] : 'TEST';

    $mdf['givenname']       = wash('cpt', $_POST['givenname']);
    $mdf['sn']              = wash('cpt', $_POST['sn']);
    $mdf['samaccountname']  = wash('ad_account', $_POST['samaccountname']);


    $fullname = $mdf['givenname'].' '.$mdf['sn'];

    $mdf['cn'] = $mdf['displayName'] = $mdf['name'] = $fullname;

    $mdf['userPrincipalName'] = $mdf['samaccountname'].'@'. $ldap['domain']['dns'];
    // Note: *samaccountname* has to be <= *20* chars, or AD returns error

    $mdf['department'] = $ou;

    $dn = 'CN='.$fullname.',OU='.$ou.','.$ldap['ou_root'].','.$ldap['domain']['dn'];


    // PASSWORD
    // It has to be enclosed in quotes, and in *utf-16* encoding. Also, to setup a pass, AD requires *HTTPS* connection.
    // If pass is *too simple* we'll get an error: "ldap_add(): Add: Server is unwilling to perform"..

    $pass = pass_create();

    $mdf['unicodePwd'] = ad_pass_format($pass);


    $r = ldap_add(LDAP_CON, $dn, $mdf);

    if ($r) {

        omg_put('success', $tx['LBL']['pass'].': '.$pass);
        // vbdo: when we connect mail/sms notes, we can stop showing pass to admin here

        log2file('actdir', ['ACT' => 'NEW ('.$fullname.', '.$ou.')', 'SAM' => $mdf['samaccountname']]);

    } else {

        omg_put('danger', '"'.ldap_error(LDAP_CON).'"');
    }


    hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?sam='.$mdf['samaccountname']);
}






/*************************** CONFIG ****************************/

$header_cfg['subscn'] = 'ad';
$header_cfg['bsgrid_typ'] = 'admin_ad';

if (SAM) {
    $footer_cfg['modal'][] = ['deleter'];
}

/*CSS*/
$header_cfg['css'][] = 'details.css';

require '../../__inc/_1header.php';
/***************************************************************/


// CRUMBS
crumbs_output('open');
crumbs_output('item', $tx['NAV'][1]);
crumbs_output('item', $tx['NAV'][209], 'report.php');
crumbs_output('item', $nav_subz[$header_cfg['subscn']]['ttl']);
crumbs_output('close');






// CTRL BAR

echo '<div class="row"><div class="btnbar '.$bs_css['panel_w'].'">';

// SEARCH & SAM

echo '<form method="get" target="_self" name="f" id="f" autocomplete="off">';

echo '<div class="form-group has-feedback search pull-left">'.
        '<input name="ggl" type="text" class="form-control" maxlength="60" onkeypress="submit13(event);" '.
            'placeholder="'.mb_strtoupper($tx['LBL']['search']).'" value="">'.
        '<span class="glyphicon glyphicon-search form-control-feedback"></span>'.
    '</div>';

echo '<div class="form-group has-feedback search pull-left">'.
        '<input name="sam" type="text" class="form-control" maxlength="60" onkeypress="submit13(event);" '.
            'placeholder="samaccountname" value="">'.
        '<span class="glyphicon glyphicon-search form-control-feedback"></span>'.
    '</div>';

echo '</form>';

// BUTTONS

echo '<div class="pull-right btn-toolbar">';

modal_output('button', 'poster',
    [
        'name_prefix' => 'adnew',
        'pms' => true,
        'button_txt' => '<span class="glyphicon glyphicon-plus-sign new"></span>'.$tx['NAV'][83].' '.$tx['LBL']['account'],
        'button_css' => 'btn-success btn-sm text-uppercase',
    ]
);

echo '</div>';

echo '</div></div>';



// ABV BAR

echo '<div class="row"><div class="well well-sm text-center '.$bs_css['panel_w'].'" id="abv">';
foreach ($abv_arr as $k => $v) {
    echo '<a'.((ABV===$v) ? ' class="disabled"' : '').' href="?abv='.$v.'">'.$v.'</a>';
}
echo '</div></div>';



if (SERVER_TYPE=='dev') {
    exit;
}







// LDAP SEARCH

if (ABV || GGL) {

    if (GGL) {
        $needle = '*'.strtolower(GGL).'*'; // GGL: all SAMs which contain the GGL
    } else {
        $needle = strtolower(ABV).'*'; // ABV: all SAMs starting with specified letter
    }

    $z = ad_entries_search('(samaccountname='.$needle.')', ['cn', 'samaccountname', 'dn']);

    if ($z['count']) {

        $z = ad_entries_clean('sam', $z);

        echo '<div class="row">';
        echo '<table class="table table-hover table-condensed" id="tbl_ad">';

        echo
            '<tr>'.
            '<th class="col-xs-1">#</th>'.
            '<th class="col-xs-3">samaccountname</th>'.
            '<th class="col-xs-3">cn</th>'.
            '<th>ou</th>'.
            '</tr>';

        $i = 0;

        foreach($z as $k => $v) {

            $uzr = rdr_row('hrm_users', 'ID, IsActive', 'ADuser=\''.$k.'\'');

            $cn = $v['cn'];

            if ($uzr) {
                $cn = '<a href="/hrm/uzr_details.php?id='.$uzr['ID'].'"'.
                    (($uzr['IsActive']) ? '' : ' class="text-danger"').'>'.$cn.'</a>';
            }

            echo
                '<tr>'.
                '<td>'.++$i.'</td>'.
                '<td><a href="?sam='.$k.'">'.$k.'</a></td>'.
                '<td>'.$cn.'</td>'.
                '<td>'.ad_dn_short($v['dn']).'</td>'.
                '</tr>';
        }

        echo '</table>';
        echo '</div>';

    } else {

        echo $tx['LBL']['noth'];
    }
}





if (SAM) {


    // LDAP SEARCH: specific SAM

    $attrz = ['cn', 'dn', 'memberof', 'useraccountcontrol', 'lastlogon', 'pwdlastset', 'whencreated', 'whenchanged',
        'givenname', 'sn', 'department', 'mobile', 'userprincipalname', 'samaccountname'];

    if (ALL_ATTRZ) {
        $attrz = [];
    }

    $z = ad_entries_search('(samaccountname='.SAM.')', $attrz);

    //echo '<pre>'; print_r($z);exit;


    // OUTPUT

    if ($z['count']) {


        $uac_const = $z[0]['useraccountcontrol'][0];

        $is_disabled = ad_uac_is_disabled($uac_const);


        $ad_account = ad_info_format($z[0]);

        $ad_account['ou'] = ad_dn_short($ad_account['dn'], 'ou_single');

        $ad_account['ou_index'] = array_search($ad_account['ou'], $ou_arr);

        if (ALL_ATTRZ) {
            $attrz = array_keys($ad_account);
            sort($attrz);
        }


        /* HEADER BAR */

        $opt = ['title' => $ad_account['samaccountname'], 'toolbar' => true];

        headbar_output('open', $opt);

        $uid = rdr_cell('hrm_users', 'ID', 'ADuser=\''.$ad_account['samaccountname'].'\'');

        if ($uid) {

            // BTN: APP-ACCOUNT
            pms_btn(
                true, $tx['NAV'][1].' '.$tx['LBL']['account'],
                [   'href' => '/hrm/uzr_details.php?id='.$uid,
                    'style' => 'margin-right:5px',
                    'class' => 'btn btn-default btn-sm text-uppercase'    ]
            );
        }

        // BTN: MODIFY
        modal_output('button', 'poster',
            [
                'name_prefix' => 'admdf',
                'pms' => true,
                'button_txt' => '<span class="glyphicon glyphicon-cog"></span>',
                'button_css' => 'btn-info btn-sm',
                'button_title' => $tx['LBL']['modify']
            ]
        );

        // BTN: CHANGE PASSWORD
        pass_modal_output('button');

        // BTN: SWITCH ON/OFF
        pms_btn(true, '<span class="glyphicon glyphicon-off"></span>',
            [
                'href' => '?sam='.SAM.'&switch=1',
                'class' => 'btn btn-'.(($is_disabled) ? 'success' : 'danger').' btn-sm',
                'title' => $tx['LBL']['switch']
            ]);

        // BTN: DELETE
        $deleter_argz = btn_output('del',
            [
                'itemtyp' => $tx['NAV'][83].' '.$tx['LBL']['account'],
                'itemcpt' => SAM,
                'target' => '?sam='.SAM.'&del=1',
                'pms' => pms('admin', 'spec')
            ]);

        headbar_output('close', $opt);


        /* AD ACCOUNT DATA OUTPUT */

        form_panel_output('head-dtlform');

        foreach($attrz as $v) {

            detail_output(['lbl' => $v, 'txt' => ((isset($ad_account[$v])) ? str_replace(',', ', ', $ad_account[$v]) : '')]);
        }

        form_panel_output('foot');


    } else {

        echo $tx['LBL']['noth'];

        $new['samaccountname'] = SAM;

        // If sam doesn't exist in AD but it exists in APP, then we want to use APP account to populate NEW-ACC modal

        $uzr = rdr_row('hrm_users', 'Name1st, Name2nd', 'ADuser=\''.SAM.'\'');

        if ($uzr) {

            $new['givenname'] = text_convert($uzr['Name1st'], 'cyr', 'lateng');

            $new['sn'] = text_convert($uzr['Name2nd'], 'cyr', 'lateng');
        }
    }
}

ldap_close(LDAP_CON);







modal_output('modal', 'poster',
    [
        'name_prefix' => 'adnew',
        'pms' => true,
        'submiter_href' => '',
        'modal_size' => 'modal-sm',
        'cpt_header' => $tx[SCTN]['MSG']['new_ad_acc'],
        'txt_body' =>
            form_ctrl_hidden('new', 1, false).
            ctrl('modal', 'textbox', $tx['LBL']['name1'], 'givenname', @$new['givenname'], 'required').
            ctrl('modal', 'textbox', $tx['LBL']['name2'], 'sn', @$new['sn']).
            ctrl('modal', 'textbox', $tx['LBL']['account'], 'samaccountname', @$new['samaccountname'], 'maxlength="20"').
            ctrl('modal', 'select', $tx['LBL']['group'], 'ou', arr2mnu($ou_arr))
    ]
);


if (isset($ad_account)) {

    modal_output('modal', 'poster',
        [
            'name_prefix' => 'admdf',
            'pms' => true,
            'submiter_href' => '',
            'modal_size' => 'modal-sm',
            'cpt_header' => $tx['LBL']['modify'],
            'txt_body' =>
                form_ctrl_hidden('mdf', 1, false).
                ctrl('modal', 'textbox', $tx['LBL']['name1'], 'givenname', $ad_account['givenname'], 'required').
                ctrl('modal', 'textbox', $tx['LBL']['name2'], 'sn', @$ad_account['sn']). // some ad accounts have no surname
                ctrl('modal', 'textbox', $tx['LBL']['account'], 'samaccountname', $ad_account['samaccountname'], 'maxlength="20"').
                ctrl('modal', 'select', $tx['LBL']['group'], 'ou', arr2mnu($ou_arr, $ad_account['ou_index']))
        ]
    );

    pass_modal_output('poster');
}




/********************************** FOOTER ***********************************/
require '../../__inc/_2footer.php';



