

/**
 * Set event triggers for LNG page. Used in page *onload*.
 */
function lngedit_page_eventz() {

    document.addEventListener("keydown", lngedit_finitor);

    klk_page_eventz();
}


/**
 * Check whether *homer* key was pressed, and then call submit function (unless some textbox is active).
 */
function lngedit_finitor() {

    var key = event.which;

    if (key!=36) return; // !homer

    if (document.querySelectorAll('input').length) return; // some textbox is active

    klk_submit('lng');
}


/**
 * Delete specific TR. Triggered by DEL link/button.
 */
function lngedit_delete(btn) {

    var tr = ancestor_finder(btn, 'name', 'tr', 5);

    tr.parentNode.removeChild(tr);
}


/**
 * Insert new TR. Triggered by INSERT link/button.
 */
function lngedit_insert(btn) {

    var clone_tmp = document.querySelector('table#cloner tr');
    var clone = clone_tmp.cloneNode(true);

    klk_page_eventz(clone); // Set events on clone tr.


    var tr = ancestor_finder(btn, 'name', 'tr', 5);

    tr.parentNode.insertBefore(clone, tr);
}

