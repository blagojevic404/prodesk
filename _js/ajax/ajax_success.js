

/*
 All *success* functions have two arguments:
 @param {string} arg_ajax Text returned from ajax php script. (Usually unnecessary.)
   Beware: if php script returns "0", it is string, not integer. You cannot use it for boolean check, e.g "if (!arg_ajax)".
 @param {object} arg_init Argument passed from the initial call. (By default: *this* object.)
 */








// EDITDIVABLE

/**
 * Editable-div text-ctrl modify: Front for ajaxer() call.
 *
 * We have to use this, because the data which is to be sent to ajax (text-ctrl value) should be fetched at the moment
 * of sending, not at the moment when we set event (onblur).
 *
 * @param {object} z *this* object (text-ctrl)
 * @return {void}
 */
function editdivable_ajax_front(z) {


    // Get DIV text for comparison

    var tmp = z.parentNode.getElementsByTagName('div');
    var div = tmp[0];
    var div_txt = editdivable_conv(div.innerHTML);


    // If no changes were made, skip ajax

    if (div_txt == z.value) {

        editdivable_ajax_success(0, z); // send *0*

    } else {

        editdivable_ajaxer(z, 'editdivable_ajax_success');
    }
}


/**
 * Editable-div text-ctrl modify: success function call
 *
 * @param {int} arg_ajax Affected rows count.. Used for success check.
 * @param {object} z *this* object (text-ctrl)
 * @return {void}
 */
function editdivable_ajax_success(arg_ajax, z) {

    if (arg_ajax==1) { // Ajax success

        editdivable_finito(z, 0);

        if (z.getAttribute('data-type')=='hrm_uname') {

            var btn_ad = z.parentNode.parentNode.parentNode.querySelector('#btn_ad_account');

            btn_ad.href = btn_ad.href.substr(0, btn_ad.href.indexOf('=')+1) + z.value;
        }

    } else if (arg_ajax===0) { // Ajax skipped, because there are no changes

        editdivable_finito(z, 27); // Send *cancel* key

    } else if (arg_ajax==-1) { // Ajax sent the error key

        if (z.getAttribute('data-type')=='hrm_uname') {
            alerter(g_alerter_uname);
        }
    }

    // Undefined ajax fail - we just fall through, we leave textbox, we don't go back to div.
    // This way the user will know something went wrong..
}


function editdivable_ajaxer(z, fn_success) {

    var url = '/_ajax/_aj_editdivable.php';

    var data = 'ajax=' + z.getAttribute('data-ajax') + '&val=' + encodeURIComponent(z.value);

    ajaxer('POST', url, data, fn_success, z);
}










// EDITCTRLABLE

/**
 * Editable-ctrl text modify: Front for ajaxer() call.
 *
 * @param {object} z *this* object (text-ctrl)
 * @return {void}
 */
function editctrlable_ajax_front(z) {

    if (z.value==z.getAttribute('data-mirror')) { // If no changes were made, skip ajax

        editctrlable_ajax_success(0, z); // send *0*

    } else {

        editdivable_ajaxer(z, 'editctrlable_ajax_success');
    }
}




/**
 * Editable-ctrl text modify: success function call
 *
 * @param {int} arg_ajax Affected rows count.. Used for success check.
 * @param {object} z *this* object (text-ctrl)
 * @return {void}
 */
function editctrlable_ajax_success(arg_ajax, z) {

    if (arg_ajax==1) { // Ajax success

        editctrlable_finito(z, 0);

    } else if (arg_ajax===0) { // Ajax was skipped, because there were no changes

        editctrlable_finito(z, 27); // Send *cancel* key
    }

    // Ajax fails - we just fall through, we don't do finito cleanup.
    // This way the user will know something went wrong..
}












// EPG STUDIO TEXTAREA


/**
 * Checking PMS for story MDF via editdivable textarea in EPG STUDIO view.
 *
 * @param {string} arg_ajax - AtomID, or error (-1 for PMS-fail)
 * @param {object} z *this* object - Textarea ctrl
 * @return {void}
 */
function studio_txt_pms_ajax_success(arg_ajax, z) {

    if (arg_ajax>0) {

        editdivable_block(z, arg_ajax);

    } else if (arg_ajax==-1) {

        alerter(g_alerter_pms_fail);
    }
}


/**
 * EPG Studio atom text modify: Front for ajaxer() call.
 *
 * We have to use this, because the data which is to be sent to ajax (textarea value) should be fetched at the moment
 * of sending, not at the moment when we set event (onblur).
 *
 * @param {object} z *this* object (textarea)
 * @param {int} atomid Atom ID
 * @return {void}
 */
function studio_txt_ajax_front(z, atomid) {


    // Get DIV text for comparison

    var tmp = z.parentNode.getElementsByTagName('div');
    var div = tmp[0];
    var div_txt = editdivable_conv(div.innerHTML);


    // If no changes were made, skip ajax

    if (div_txt == z.value) {

        studio_txt_ajax_success(null, z);

    } else {

        var url = '_ajax/_aj_studio_txt.php';

        var data = 'atomid=' + atomid + '&texter=' + encodeURIComponent(z.value);

        ajaxer('POST', url, data, 'studio_txt_ajax_success', z);
    }
}

/**
 * EPG Studio atom text modify: success function call
 *
 * @param {string|void} arg_ajax Data in CSV format: AtomID, NewDuration. OR: null when no changes are made.
 * @param {object} z *this* object (textarea)
 * @return {void}
 */
function studio_txt_ajax_success(arg_ajax, z) {

    if (arg_ajax==-1) { // ajax pms fail

        alerter(g_alerter_pms_fail);

        editdivable_finito(z, 27); // Send *cancel* key

    } else if (arg_ajax==-2) { // ATOM is not of READING type

        editdivable_finito(z, 0);

    } else if (arg_ajax==-404) { // Ajax exited (should never happen)

        alerter(g_alerter_item_fail);

        editdivable_finito(z, 27); // Send *cancel* key

    } else if (!arg_ajax) { // ajax was skipped, because no changes were made

        editdivable_finito(z, 27); // Send *cancel* key

    } else if (arg_ajax) { // ajax ok

        var a = arg_ajax.split(','); // Convert comma-separated-values string into array

        var atomid = a[0];

        var new_dur = a[1];

        atomdur_change(new_dur, atomid);

        editdivable_finito(z, 0);
    }
}






// EPG STUDIO SPEAKER


/**
 * EPG Studio speaker change: Front for ajaxer() call.
 *
 * The call code is within POPOVER field, so it is a problem to use both single quotes and double quotes. Thus we cannot
 * call JS ajaxer() normally. Instead, we have to assemble ajax url this way - within JS procedure.
 *
 * @param {object} z *this* object
 * @param {int} speakerx SpeakerX
 * @param {int} atomid Atom ID
 * @return {void}
 */
function studio_spkr_ajax_front(z, speakerx, atomid) {

    var url = '_ajax/_aj_studio_spk.php';

    var data = 'atomid=' + atomid + '&speakerx=' + speakerx;

    ajaxer('POST', url, data, 'studio_spkr_ajax_success', z);
}

/**
 * Epg studio speaker change: success function call
 *
 * @param {string} arg_ajax Data in CSV format: SpeakerX, AtomID, NewDuration
 * @param {object} z *this* object
 * @return {void}
 */
function studio_spkr_ajax_success(arg_ajax, z) {

    var a = arg_ajax.split(','); // Convert comma-separated-values string into array

    var speakerx = a[0];

    var atomid = a[1];

    var new_dur = a[2];


    speaker_change(z, speakerx, atomid);

    atomdur_change(new_dur, atomid);
}







// EPG STORY PHASE

/**
 * Story phase incrementing.
 *
 * @param {string} arg_ajax - New phase (1,2,3,4), or error (-1 for PHZ-top, -2 for PMS-fail)
 * @param {object} z *this* object - PHZ link
 * @return {void}
 */
function phz_ajax_success(arg_ajax, z) {

    if (arg_ajax>0) {

        phzuptd_dot(z.children[0], arg_ajax);

        ajaxer('GET', '/_ajax/_aj_mdflog.php', 'checker=1&id='+z.getAttribute('data-id'), 'alerter', null);

    } else if (arg_ajax==-1) {

        alerter(g_alerter_phz_err_top);

    } else if (arg_ajax==-2) {

        alerter(g_alerter_phz_err_pms);

    } else if (arg_ajax==-404) {

        alerter(g_alerter_item_fail);
    }
}












// *UNIQUE FULLNAME* CHECK

/**
 * Check whether user with the same fullname aready exists (triggered when adding new user)
 *
 * @param {object} z *this* object (unnecessary)
 * @return {void}
 */

function unique_fullname_front(z) {

    var name1 = document.getElementById('Name1st').value;
    var name2 = document.getElementById('Name2nd').value;

    if (name1 && name2) {

        var url = '_ajax/_aj_fullname.php';

        var data = 'name1=' + name1 + '&name2=' + name2;

        ajaxer('POST', url, data, 'unique_fullname_success', z);
    }
}

/**
 * Show *conflict* modal alerter with a link to conflicted user account
 *
 * @param {int} arg_ajax UID of the conflicted user account
 * @param {object} z *this* object (unnecessary)
 * @return {void}
 */
function unique_fullname_success(arg_ajax, z) {

    if (!parseInt(arg_ajax)) {
        return;
    }

    alerter(g_alerter_fullname);

    // update the link to conflicted user account
    document.querySelector("#alerterModalLabel a").href = 'uzr_details.php?id=' + arg_ajax;
}










// DSK settingz: ReadSpeed


/**
 * DSK settingz - ReadSpeed: Update DEFAULT buttons. (Disable the selected, i.e. new default, enable the others.)
 *
 * @param {string} arg_ajax Unnecessary.
 * @param {object} z *this* object
 * @return {void}
 */
function rs_dft_ajax_success(arg_ajax, z) {

    var boxz = document.getElementsByClassName ('disabled is_default');

    for (var i = 0; i < boxz.length; i++) {
        boxz[i].className = 'opcty3 is_default';
    }

    z.className = 'disabled is_default';
}



/**
 * DSK settingz - ReadSpeed: Handle deleted RS row.
 *
 * @param {string} arg_ajax Deleted ID.
 * @param {object} z *this* object
 * @return {void}
 */
function rs_del_ajax_success(arg_ajax, z) {

    var li = z.parentNode.parentNode;

    li.style.backgroundColor = '#faa';
    li.style.opacity = '0.3';
    li.style.borderTop = '1px solid #c55';

    if (document.getElementById('v_id').value = arg_ajax) {
        document.getElementById('v_id').value = 0;
    }

    z.parentNode.innerHTML = '';
}









// FLW BTN


/**
 * Switch FLW button on/off.
 *
 * @param {string} arg_ajax Unnecessary.
 * @param {object} z *this* object
 * @return {void}
 */
function flw_ajax_success(arg_ajax, z) {

    var clicker = z.getAttribute('onclick');

    var pos = clicker.indexOf('&sw=');

    var sw = clicker.substr(pos+4, 1);

    sw = (sw=='1') ? 0 : 1;

    var clicker_new = clicker.substr(0, pos+4) + sw +clicker.substr(pos+5);

    z.setAttribute('onclick', clicker_new);

    z.innerHTML = '<span class="glyphicon glyphicon-eye-open"></span>';

    z.className = (z.className=='flw') ? 'flw on' : 'flw';

    if (z.parentNode.tagName=='TD') { // For MyDesk list
        var tr = z.parentNode.parentNode;
        tr.style.opacity = (sw=='1') ? '0.3' : '1';
    }
}







// IsReady BTN


/**
 * Replace button with OK sign.
 *
 * @param {string} arg_ajax (1,2) 1-IsReady, 2-Proofed.
 * @param {object} z *this* object
 * @return {void}
 */
function ready_ajax_success(arg_ajax, z) {

    z.outerHTML = '<span class="glyphicon glyphicon-ok ready'
        + ((arg_ajax==2) ? ' ready_not cvr_proofed' : '')
        + '"></span>';
}






// LOGZER accordion


/**
 * Fill placeholder div with result-text (e.g. logs table html).
 *
 * @param {string} arg_ajax Result-text (e.g. logs table html).
 * @param {string} arg_init ID of the element which will display result
 *
 * @return {void}
 */
function placeholder_filler(arg_ajax, arg_init) {

    document.getElementById(arg_init).innerHTML = arg_ajax;
}










// MOVER


/**
 * Handle changing of div-frames html for mover
 *
 * @param {string} arg_ajax Result-text, i.e. div-frame html
 * @param {object} z *this* object (link that was used to initiate the ajax)
 * @return {void}
 */
function mover_success(arg_ajax, z) {

    var typ = z.parentNode.getAttribute('data-type');


    if (typ=='calendar') {

        document.getElementById('wrap_movercalendar').innerHTML = arg_ajax;
        mover_cleaner('moverepg');
        mover_cleaner('moverscnr');

    } else if (typ=='epg') {

        document.getElementById('wrap_moverepg').innerHTML = arg_ajax;
        mover_cleaner('moverscnr');
        mover_sel('table#epgcalendar a.sel', z);

        var ifrm = window.parent.document.querySelector('iframe');
        setIframeHeight(ifrm);

    } else if (typ=='scnr') {

        document.getElementById('wrap_moverscnr').innerHTML = arg_ajax;
        mover_sel('div#moverepg a.sel', z);
    }
}

function mover_cleaner(id) {

    var z = document.getElementById(id);
    if (z) {
        z.innerHTML = '';
        z.style.height = document.querySelector('table#epgcalendar').clientHeight+'px';
    }
}

function mover_sel(css, z) {

    var sel = document.querySelector(css);
    if (sel) {
        sel.className = '';
    }

    z.className = 'sel';
}











/**
 * Reload the window, i.e. refresh the current page in browser
 */
function reloader() {

    location.reload();
}



