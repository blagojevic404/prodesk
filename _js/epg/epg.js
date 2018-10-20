
/* EPG functions */


// MDFSINGLE


/**
 * Switches MOS box ON if MatType is not LIVE(1), or OFF if it is.
 *
 * Called on page load and on MatType button group click.
 */
function mosbox_switch() {

    var mos = document.getElementById("mos_field"); // find mos box
    var alrt = document.getElementById("mos_alert"); // find mos alert
    var shdw = document.getElementById("MatType"); // find shadow submitter for MatType

    mos.disabled = (shdw.value==1);
    alrt.style.display = (shdw.value==1) ? "block" : "none";
}








// MDFMULTI



/**
 * Squizer for *epg_modify_multi* page. For each element opens row with appropriate controls within the same page.
 *
 * @param {object} z - Anchor object, i.e. "this" object for the squizer button
 * @return void
 */
function squizer_mdfmulti(z) {

    var pos_normal = 0;
    var pos_lifted = -22;

    var loaded = (parseInt(z.style.top,10) < pos_normal) ? 1 : 0;
    // We later move z up and down by changing its style.top. Thus we can also use that property to check current
    // state of the z, i.e. whether it is loaded or not.

    var tr = z.parentNode.parentNode.parentNode.parentNode;

    if (!loaded) {

        squizrow_cleaner(tr.parentNode.parentNode.parentNode.parentNode.parentNode, pos_normal);
        // Check and cleanup other active squizer rows, before we add new one..

        var clone_tmp = document.getElementById('squizrow');
        var clone = clone_tmp.cloneNode(true);
        clone.style.display = '';

        tr.parentNode.insertBefore(clone, tr); // Insert block with squizer controls

        z.style.top = pos_lifted + 'px'; // Move squizer button upwards

    } else {

        var tr_prev = tr.previousSibling;

        tr_prev.parentNode.removeChild(tr_prev); // remove block with squizer controls

        z.style.top = pos_normal + 'px'; // Move squizer button downwards, back to its normal position
    }

    window.focus();
}





/**
 * Switches active/inactive state of row in multi-modify list
 *
 * @param {object} btn - Anchor object, i.e. "this" object for the switch button
 * @return void
 */
function element_switch(btn) {

    var ctrlz, i, j;

    var actve = btn_sign_switch(btn);


    var parentTR = btn.parentNode.parentNode.parentNode.parentNode;

    // Loop INPUT controls and set *display*
    ctrlz = parentTR.getElementsByTagName ('input');
    for (i = 0; i < ctrlz.length; i++)	{
        ctrlz[i].style.display = (actve) ? '' : 'none';
    }

    // Loop SELECT controls and set *display*
    ctrlz = parentTR.getElementsByTagName ('select');
    for (i = 0; i < ctrlz.length; i++) {
        ctrlz[i].style.display = (actve) ? '' : 'none';
    }


    // Loop through TABLE CELLS but exclude ghost cells
    var tdz = parentTR.getElementsByTagName ('td');
    for (i = 0; i < tdz.length; i++) {
        if (tdz[i].className != 'ghost') {

            // CSS class which will blend text and background to grey
            tdz[i].id = (actve) ? '' : 'switchedoff';

            // Check DIVs (we put this inside cell loop because we want to exclude divs withing ghost cells)
            ctrlz = tdz[i].getElementsByTagName ('div');
            for (j = 0; j < ctrlz.length; j++) {
                ctrlz[j].style.display = (actve) ?  '' : 'none';
            }
        }
    }


    // Parent DND cell.. that's where DEL shadow submitter is located (we have to find it and set it's value)

    var parentDNDtd = parentTR.parentNode.parentNode.parentNode;

    setHider (parentDNDtd, 'del', (actve ? 0 : 1)); // we use ternary operator to convert bool to int

    window.focus();
}





/**
 * Copies clone row of specified type and pastes into epg_modify_multi list
 *
 * Called by new_element btn click in modify_multy
 *
 * @param {object} z - Anchor object, i.e. "this" object for the squizer button
 * @param {int} typ - Epg line type
 * @return void
 */
function element_clone(z, typ) {

    var pos_normal = 0;

    var clone_tmp = document.getElementById('clone['+ typ + ']');
    var clone = clone_tmp.cloneNode(true);
    clone.style.display = '';


    var table = document.getElementById('dndtable');
    var rowCount = table.rows.length;

    var new_row;

    if (z==0) { // called from the bottom of the table

        new_row = table.insertRow(rowCount);

        new_row.insertCell(0);
        new_row.cells[0].innerHTML = clone.outerHTML;

    } else { // called from some table-row

        var uprow = z.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode;
        new_row = uprow.parentNode.insertBefore(clone, uprow);

        var trz = uprow.getElementsByTagName('tr');
        var tr_ctrl = trz[0];
        var tr_btn = trz[1];
        var btnz = tr_btn.getElementsByTagName('a');

        tr_ctrl.parentNode.removeChild(tr_ctrl); // remove block with squizer controls

        btnz[0].style.top = pos_normal; // move squizer button downwards, back to its normal position
    }

    new_row.id = rowCount+1;

    setHider (new_row, 'cnt', (rowCount+1));

    // THIS IS USED IF CFG:mattype_use_cbo==1
    /*if (typ==1 || typ==8) {
     cbo_selcopy(clone_tmp, new_row);
     }*/

    // THIS IS USED IF CFG:mattype_use_cbo==0
    // fixing MatType radio buttons & hidden field
    var inz = new_row.getElementsByTagName('input');
    for (var i = 0; i < inz.length; i++) {
        if (inz[i].name.substr(0,9) == 'tmp_clone') {
            inz[i].name = 'tmp_normal[' + zero_pad(typ,2) + zero_pad(new_row.id,3) + ']';
        }
    }


    // reinitialize tableDND
    tableDnD1.init(table1);
}






/**
 * Duplicates an element in MULTI mdf element list
 *
 * @param {object} z - Anchor object, i.e. "this" object for the *duplicate* button
 * @param {int} typ - Epg line type
 * @return void
 */
function element_double(z, typ) {

    var i;

    var row1 = z.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode;

    var doubler = row1.cloneNode(true);

    //var row2 = insert_after(doubler, row1); // for some reason, this won't do.. doubled element won't DND..
    var row2 = row1.parentNode.insertBefore(doubler, row1.nextSibling);

    // read the row_count, and then set the properties in new row..
    var table = document.getElementById('dndtable');
    var rowCount = table.rows.length;

    row2.id = rowCount;

    setHider (row2, 'cnt', (rowCount));
    setHider (row2, 'id', 0);


    // THIS IS USED IF CFG:mattype_use_cbo = 1
    /*if (typ==1 || typ==8) {
     cbo_selcopy(row1, row2);
     }*/

    // THIS IS USED IF CFG:mattype_use_cbo = 0
    // fixing MatType radio buttons & hidden field
    var inz = row2.getElementsByTagName('input');
    for (i = 0; i < inz.length; i++) {
        if (inz[i].name.substr(0,4) == 'tmp_') {
            inz[i].name = 'tmp_normal[' + zero_pad(typ,2) + zero_pad(row2.id,3) + ']';
        }
    }


    // clear the TERM input fields
    var inputz = row2.getElementsByTagName ('input');
    for (i = 0; i < inputz.length; i++) {
        if (inputz[i].name.substr(0,4) == 'term') {
            inputz[i].value = '';
        }
    }

    // reinitialize tableDND
    tableDnD1.init(table1);
}





















// EPG LIST

/**
 * Displays or hides SHEET. Only for epg/scn in tree view.
 *
 * Function is fired through onclick of the drop-down/up button which is at the right end of the epg line (in tree view)
 *
 * @param {int} id - Element ID
 * @return void
 */
function sheet_switch(id) {

    var sht = document.getElementById ('sht'+id);
    var drp = document.getElementById ('drp'+id);

    if (drp.lang=='down') {
        sht.style.display = 'none';
        drp.lang = 'up';
        drp.className = 'caret';
    } else {
        sht.style.display = 'table-row';
        drp.lang = 'down';
        drp.className = 'caret caret-reversed';
    }
}


/**
 * Display or hide atom txt for specific atom. Only for scnr in tree view.
 *
 * Function is fired through onclick of the atom *type* button.
 *
 * @param {int} id - Element ID
 * @return void
 */
function atomtxt_switch(id) {

    display_swtch(document.getElementById('atomtxt'+id), 'table-row');
}


/**
 * Display or hide atom txt for *all* atoms. Only for scnr in tree view.
 *
 * Function is fired through onclick of the collapse-button which is in table header.
 *
 * @param {object} btn - Anchor object, i.e. "this" object for the collapse-button
 * @return void
 */
function atomtxt_switch_all(btn) {

    if (btn==null) { // Called from page:onload

        var actve = false;

    } else { // Called from btn:onclick

        // Switch button sign
        var actve = btn_sign_switch(btn, ['glyphicon glyphicon-resize-small', 'glyphicon glyphicon-resize-full'], 'small');
    }

    var atomz = document.querySelectorAll('tr.atomtxt_drop');

    for (var i = 0; i < atomz.length; i++) {
        atomz[i].style.display = actve ? 'none' : 'table-row';
    }
}




/**
 * Squizer for *epg* list page. For each element provides links to *element_modify* page (modifier for a SINGLE element).
 *
 * Function is connected with the squizer button at the left end of the element bar
 *
 * @param {object} z - Anchor object, i.e. "this" object for the squizer button
 * @param {int} qu - Queue
 * @return void
 */
function squizer_epglist(z, qu) {

    var pos_normal = 0;
    var pos_lifted = -20;

    var loaded = (parseInt(z.style.top,10) < pos_normal) ? 1 : 0;
    // We later move z up and down by changing its style.top. Thus we can also use that property to check current
    // state of the z, i.e. whether it is loaded or not.

    var tr = z.parentNode.parentNode.parentNode.parentNode;


    if (!loaded) {

        squizrow_cleaner(tr.parentNode.parentNode, pos_normal);
        // Check and cleanup other active squizer rows, before we add new one..

        var clone_tmp = document.getElementById('squizrow');
        var clone = clone_tmp.cloneNode(true);
        clone.style.display = '';

        var linkers = clone.getElementsByTagName ('a');
        for (var i = 0; i < linkers.length; i++) {
            var linker = linkers[i];
            linker.href = linker.pathname + linker.search + '&qu=' + qu; // Add queue attribute to each link
        }

        tr.parentNode.insertBefore(clone, tr); // Insert block with squizer controls

        z.style.top = pos_lifted + 'px'; // Move squizer button upwards


    } else {

        var tr_prev = tr.previousSibling;

        tr_prev.parentNode.removeChild(tr_prev); // Remove block with squizer controls

        z.style.top = pos_normal + 'px'; // Move squizer button downwards, back to its normal position
    }
}



/**
 * Shows/hides specified linetypes in epg list. Triggered by multiselect linetype filter on epg list page.
 */

function epg_line_filter(){

    var z = document.getElementById('LineFilter');


    // This multiselect filter is used in *table rows* situation and in *divs* situation. Normal display property for
    // tables is *table-row*, and for divs it is *block*.

    var is_tbl = document.getElementById('epg_table');
    var display_default = (is_tbl) ? 'table-row' : 'block';


    var lines = null;
    var cssname = null;
    var newvalue = null;

    var i,j;

    var arr_val = [];

    for (i = 0; i < z.options.length; i++) {

        if (z.options[i].selected) {
            arr_val.push(z.options[i].value);
        }

        cssname = 'linetyp' + z.options[i].value;
        newvalue = (z.options[i].selected || z.selectedIndex==-1) ? display_default : 'none';

        lines = document.getElementsByClassName(cssname);

        for(j=0; j<lines.length; j++) {
            lines[j].style.display = newvalue;
        }
    }


    // When line-filter is used for TEAMS, then we mark non-program lines (i.e. mkt, prm, films, etc) with 0..
    // We want to hide them if any team is selected, and we want to show them again when filter is unused (i.e. none selected).

    lines = document.getElementsByClassName('linetyp0');

    for(j=0; j<lines.length; j++) {
        lines[j].style.display = (arr_val.length) ? 'none' : display_default;
    }


    sessionStorage.setItem(g_line_filter_name, arr_val);
}

















// COMMON


/**
 * Cleanup all active SQUIZROWs
 *
 * @param {object} TBLcontainer - Container
 * @param {int} pos_normal - Default position of squiz button
 * @return void
 */
function squizrow_cleaner(TBLcontainer, pos_normal) {

    var trz = TBLcontainer.getElementsByTagName ('tr');

    for (var i = 0; i < trz.length; i++) {

        if (trz[i].id=='squizrow' && trz[i].style.display!='none') {
            // avoid CLONE squizrow (which has *display* style property set to *none*)

            // Take care of the squiz button (move it to its normal position), which is located in the row under
            var tr_next = trz[i].nextSibling;
            var tr_next_a = tr_next.getElementsByTagName ('a');
            tr_next_a[0].style.top = pos_normal + 'px';

            // remove squizrow itself
            trz[i].parentNode.removeChild(trz[i]);
        }
    }
}



/**
 * When copying CBOs, their selection is lost, thus we have to loop and copy selectedIndex
 *
 * Used only if CFG:mattype_use_cbo = 1
 */
function cbo_selcopy(z1, z2) {
    var cboz1 = z1.getElementsByTagName ('select');
    var cboz2 = z2.getElementsByTagName ('select');
    for (var i = 0; i < cboz1.length; i++) {
        cboz2[i].selectedIndex = cboz1[i].selectedIndex;
    }
}



/**
 * Adds zero-padding of specified length to specified number
 *
 * @param {int} x - Number which has to be padded
 * @param {int} n - Padding length
 * @return {*}
 */
function zero_pad(x, n) {

    if (n==2) {
        if (x>9) return x;
        return '0' + parseFloat(x);
    }

    if (n==3) {
        if (x>99) return x;
        if (x>9) return '0' + parseFloat(x);
        return '00' + parseFloat(x);
    }
}











































/**
 * Implements -EMPHASIZE- formatting for the EPG, i.e. formats broadcasted programs differently than current program and
 * following programs - draws a divider line in epg just above the program which is currently on air, and changes the
 * background opacity of already finished programs to pale.
 * (This function is not related to *uptodate* functions and to *warney* bar.)
 *
 * @param {int} prev_now_row Previous CURRENT row (this is only used in order to avoid unnecessary looping,
 we want to loop only potential changers)
 * @return {void}
 */
function epg_emph(prev_now_row) {

    var z = document.getElementById('epg_table');
    var trz = z.querySelectorAll('tr[lang]');
    var i, j, tdz;


    var timenow = new Date();
    timenow = timenow.getTime();    // We'll need to lose the milliseconds because PHP timestamp doesn't use them
    timenow = Math.round(timenow / 1000);


    var now_row = 0;

    var start_row = (prev_now_row) ? prev_now_row : 0;

    var rowcnt = trz.length;


    // Determine now_row
    for (i = start_row; i < rowcnt; i++) {

        // We use LANG attribute to pass TermEmit (in timestamp format) to JS. This is set in epg_dtl_html().
        if (trz[i].lang < timenow) {
            now_row = i; // *now_row* will hold the index of the row which contains the program which is currently on air.
        } else {
            break;
        }
    }


    if (now_row!=prev_now_row) { // If something changed since the previous check, proceed..

        // PREV NOW ROW FORMATTING: reset its top-border to normal color
        if (prev_now_row) {
            tdz = trz[prev_now_row].getElementsByTagName('td');
            for (i = 0; i < tdz.length; i++) {
                tdz[i].style.borderTopColor = '#fff';
            }
        }

        // NOW ROW FORMATTING: change its top-border to special color
        tdz = trz[now_row].getElementsByTagName('td');
        for (i = 0; i < tdz.length; i++) {
            tdz[i].style.borderTopColor = '#f00';
        }

        // PREVIOUS ROWS FORMATTING: change background-opacity for all rows to pale
        for (i = start_row; i < now_row; i++) {
            tdz = trz[i].getElementsByTagName('td');
            for (j = 0; j < tdz.length; j++) {
                tdz[j].style.opacity = '0.7';
                //tdz[j].style.webkitFilter = "saturate(0%)";
            }

            if (trz[i].nextElementSibling.className.trim()=='drop') {
                trz[i].nextElementSibling.style.opacity = '0.7';
            }
        }

    }

    setTimeout(function(){epg_emph(now_row);}, 60000);
    // This is a proper way to pass a function as an argument to setTimeout().
    // You need to feed an anonymous function as an argument instead of a string.
}






/**
 * Loops (periodically calls) the *uptodate* procedure - which starts with ajaxer().
 */
function epguptd_init() {

    // This is for testing - if we want to fire just once, on the page load..
    //setTimeout(function(){ajaxer('GET', epguptd_ajax_url, epguptd_ajax_data);}, 1000);

    epguptd_intrval_ajax = setInterval(
        function(){ajaxer('GET', epguptd_ajax_url, epguptd_ajax_data, 'uptd', null);},
        epguptd_ajax_cnt
    );

    uptd_snap_original();
}


/**
 * The MAIN *uptodate* function
 *
 * @param {string} str_ajax Ajax string (from php page which checks current state)
 * @return {void}
 */
function uptd(str_ajax) {

    var epguptd_arrcur_new = epg_ajax_decode(str_ajax);  // Convert ajax string to array

    if (!epguptd_arrcur_new) {
        return; // This would mean ajax didn't work
    } else {
        epg_ajax_label(); // Update the label in upper part of the page, which holds the term of last update
    }


    // Now we check if there is a FAUL, i.e. a change.
    // Faul #1 means difference in IDs, faul #2 means difference in TERMS.

    var faul = [];
    faul[1] = false;
    faul[2] = false;

    // Save NEW current list into global array *epguptd_arrcur*.
    // But before that, locally save previous version, so we can later compare it.
    var epguptd_arrcur_old = epguptd_arrcur;
    epguptd_arrcur = epguptd_arrcur_new;

    // If the CURRENT list hasn't been changed since the last loop then no need to compare it against
    // the ORIGINAL list, because they certainly are identical.

    if (epg_type=='epg' && !arrz_identical(epguptd_arrcur_old[0], epguptd_arrcur[0])) {

        // CURRENT list (for epg) will actually hold only values which are yet to be broadcasted, it will not hold
        // values for elements which are already in the past.
        // CURRENT list can thus change because the term for next program has come up, and then this program is removed
        // from the current list, but that does not mean there has been a change in IDs or TERMS. We just have to update
        // ORIGINAL list, i.e. remove the lines which are no more in the CURRENT list (i.e. PAST elements).
        // Find the key in ORIGINAL that corresponds to *first* item in CURRENT and then remove items below that point.

        var n = epguptd_arrorig[0].indexOf(epguptd_arrcur[0][0]);

        if (n) {
            epguptd_arrorig[0].splice(0, n);
            epguptd_arrorig[1].splice(0, n);
        }
    }

    if (!arrz_identical(epguptd_arrorig[0], epguptd_arrcur[0])) { // [0] is for IDs, [1] is for terms;
        faul[1] = true;
    }

    if (!arrz_identical(epguptd_arrorig[1], epguptd_arrcur[1])) {
        faul[2] = true;
        uptd_termz_marking();
    }

    //alert(epguptd_arrorig[1]);        alert(epguptd_arrcur[1]);return;

    //faul[1] = true;

    //alert('faul!');

    if (faul[1] || faul[2]) {

        warney_display();								// Display warney bar

        clearInterval(epguptd_intrval_ajax);					// Switch off the *uptodate* loop
    }
}





/**
 * Gets ORIGINAL list (ID and TERM of each element in the epg) to *epguptd_arrorig* global array
 * The ORIGINAL list is then later used for comparison, on each *uptodate* loop.
 */
function uptd_snap_original() {

    var z = document.getElementById('epg_table');
    var trz = z.getElementsByTagName('tr');

    var rowcnt = trz.length - 2;
    // Minus 2 is for two squizrows at the bottom - one is *last-line*, the other one is *clone*. We want to omit those.


    if (epg_type=='epg') {
        var timenow = new Date();
        timenow = timenow.getTime();    // we'll need to lose the milliseconds because PHP timestamp doesn't use them
        timenow = Math.round(timenow / 1000);
    }


    var r = [[], []]; // [0] - IDs, [1] - terms
    var tr_id, t, t_hms;
    var cnt = 0;

    for (var i = 1; i < rowcnt; i++) {	// Start at #1 is because we want to skip the header tr (which is #0)

        tr_id = trz[i].id;

        // TR IDs intentionally have 'tr' prefix, so we could use it as a filter.
        // Then we remove the prefix and save ID to return array
        if (tr_id.substr(0,2)=='tr') {

            if (epg_type=='epg' && trz[i].lang < timenow) {
                continue;
            }

            r[0][cnt] = tr_id.substr(2);

            r[1][cnt] = trz[i].lang;    // Terms are passed through *lang* attribute
            if (r[1][cnt] === undefined) {
                r[1][cnt] = '';
            }

            if (r[1][cnt]) {
                t = new Date(parseInt(r[1][cnt] + '000'));
                t_hms = hms(t,0);
                r[1][cnt] = t_hms;
            }

            cnt++;
        }
    }

    // Save return array to global array holding ORIGINAL values (IDs and terms)
    // The return array is a 2-dim array which looks like: [[2533,2534..], [103500,110000..]]
    epguptd_arrorig = r;
}





/**
 * Compares two arrays, to see if they are completely identical.
 *
 * @param {Array} a
 * @param {Array} b
 * @return {boolean}
 */
function arrz_identical(a, b) {

    if (a.length != b.length) {
        return false;
    }
    for (var i = 0; i < a.length; i++) {
        if (a[i] !== b[i]) {
            return false;
        }
    }
    return true;
}



/**
 * Marks the terms which have changed (changes their formatting)
 */
function uptd_termz_marking() {

    var tr, tdz;

    for (var i = 0; i < epguptd_arrcur[1].length; i++) {

        if (epguptd_arrcur[1][i] !== epguptd_arrorig[1][i]) {

            tr = document.getElementById('tr'+epguptd_arrcur[0][i]);
            tdz = tr.getElementsByTagName('td');

            // Change color for the second column (i.e. TERM)
            tdz[1].style.backgroundColor = '#f00';
        }
    }
}












/**
 * Converts string returned from php script via ajax, into an array
 *
 * @param {string} s String returned from php script via ajax
 * @return {Array} arr Data from php script
 */
function epg_ajax_decode(s) {

    // Input string looks like: 2533:103500,2534:110000..
    // That represents ID and TermEmit data for each element in epg. Format is: '(ID):hhmmss'.

    var r = [[], []]; // [0] - IDs, [1] - terms

    var a1, a2; // tmp arrays

    a1 = s.split(',');

    for (var i=0; i<a1.length; i++) {

        a2 = a1[i].split(':');

        r[0][i] = a2[0];
        r[1][i] = a2[1];
    }

    return r;

    // The return array is a 2-dim array which looks like: [[2533,2534..], [103500,110000..]]
}



/**
 * Update the label in upper part of the page, which holds the term of last update in HH:MM:SS format
 */
function epg_ajax_label() {

    var t = server_time();
    document.getElementById('ajax_uptodate').innerHTML = hms(t,1);
}








/**
 * Switches on the warney bar
 */
function warney_display() {

    document.getElementById('warney').style.display = 'block';
    document.body.style.paddingTop = '35px';

    warney_cntdown(epguptd_warney_cnt);
}

/**
 * Switches off the warney bar
 */
function warney_cancel() {

    clearInterval(intrval_warney);

    document.getElementById('warney').style.display = 'none';
    document.body.style.paddingTop = 0;
}

/**
 * COUNTDOWN (until restart) handler for the warney bar
 *
 * @param {int} cnt Starting value, in seconds
 */
function warney_cntdown(cnt) {

    if (cnt>0) {

        document.getElementById('warney_cntdown').innerHTML = cnt.toString();
        intrval_warney = setTimeout(function(){warney_cntdown(cnt);}, 1000);
        cnt--;

    } else {

        document.getElementById('warney_cntdown').innerHTML = '0';
        location.reload(true);
    }
}





/**
 * Moves SCNR *emph* line up or down, and scrolls to line. Called by keydown event.
 */
function scnr_emph(e) {

    // Abort if the focus is within some control, (i.e. in hms_editable)
    var ctrlz = ['INPUT', 'SELECT', 'TEXTAREA'];
    if (ctrlz.indexOf(document.activeElement.tagName) != -1) return;


    var keyCode = ('which' in event) ? event.which : event.keyCode;

    var case_onload = (keyCode) ? false : true;

    if (!case_onload) { // When called from scnr_emph_init(), keyCode is undefined but we need to go through

        var keyz = [107,109,102];

        if (keyz.indexOf(keyCode) === -1) {
            return;
        }
    }


    var z = document.getElementById('epg_table');
    var trz = z.querySelectorAll('tr[lang]');
    var i, j, tdz;

    var rowcnt = trz.length;


    if (keyCode==107 || keyCode==102) { // down (+,6)
        if (g_now_row<rowcnt) {
            g_now_row++;
        }
    } else if (keyCode==109) { // up (-)
        if (g_now_row>0) {
            g_now_row--;
        }
    }


    if (g_now_row==rowcnt) { // Below last row: reset
        sessionStorage.removeItem('stg_now_row');
        g_now_row = -1;
    }


    for (i = 0; i < rowcnt; i++) {

        if (!case_onload) {

            if (i==g_now_row) {

                location.hash = "#" + trz[i].id; // Scroll to NOW_ROW

                sessionStorage.setItem('stg_now_row', g_now_row);

                if (keyCode==102) { // down + termnow (6)
                    get_submit('termnow', trz[i].id.substring(2));
                }
            }
        }

        tdz = trz[i].getElementsByTagName('td');

        for (j = 0; j < tdz.length; j++) {
            tdz[j].style.borderTopColor = (i==g_now_row) ? '#f00' : '#fff';
        }
    }

}


/**
 * Page OnLoad init for scnr_emph().
 */
function scnr_emph_init() {

    g_now_row = sessionStorage.getItem("stg_now_row");

    scnr_emph();
}





/**
 * Page OnLoad init for CVR phase filter
 */
function cvr_phzfilter_init() {

    var i, phz;

    // Preventing messing with and thus breaking controls before init
    document.getElementById('cvr_phz_filter').style.display = 'inline-block';

    for (i = 0; i < 3; i++) { // Phases are: 0, 1, 2. Each phase has its checkbox.

        phz = sessionStorage.getItem('cvr_phz'+i);

        if (phz==1) {

            display_switch_all('phz'+i,'block'); // Switch off all CVR blocks of that phase

            document.getElementById('cvr_phz_filter'+i).className = 'btn btn-default'; // Set chk-btn to un-active state
        }
    }
}


/**
 * SPICER clipz switch.
 */
function spc_clipz_switch(btn) {

    var trz = document.querySelectorAll('tr.spicer_clipz');

    if (trz.length) {

        // We use first tr to get *current* state, as all of them have the same state.
        var actve = (trz[0].style.display!='table-row') ? false : true;

        for (var i = 0; i < trz.length; i++) {
            trz[i].style.display = actve ? 'none' : 'table-row';
        }
    }

    btn_sign_switch(btn, ['glyphicon glyphicon-resize-full', 'glyphicon glyphicon-resize-small'], 'full');

    sessionStorage.setItem('spc_clipz_state', (actve ? 'small' : 'full'));
    // We turn *actve* state around (invert), because we have just performed the switch..
    // Note: Local storage stores only in *string* type. That's why we don't save *actve* (boolean) value.
}


/**
 * Page OnLoad init for SPICER clipz switch
 */
function spc_clipz_switch_init() {

    var actve = sessionStorage.getItem('spc_clipz_state');

    if (actve=='full') {
        spc_clipz_switch(document.querySelector('#spc_clipz_btn'));
    }
}




/**
 * Submit for epg export
 */
function epgexp_submit(output_type) {

    document.querySelector('form').action += '&output_type=' + ((output_type==1) ? 'text' : 'file');

    klk_submit('epgexp');
}



/**
 * Master checkbox - switch on/off all checkboxes
 */
function chk_all(wraper, source) {

    var items = document.getElementById(wraper).querySelectorAll('input[type="checkbox"]');

    for (var i = 0; i < items.length; i++) {
        items[i].checked = source.checked;
    }
}
