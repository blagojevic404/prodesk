


/**
 * Set onclick event triggers on css-filtered (.klk) elements. Used in page *onload*, and in cloner insert.
 *
 * @param {object} obj - Reference element. Should be omitted in *onload*, so the *document* will be used.
 * @return {void}
 */
function klk_page_eventz(obj) {

    if (!obj) { // onload
        obj = document;
    }

    var klkerz = obj.querySelectorAll('.klk');

    for (var i = 0; i < klkerz.length; i++) {

        klkerz[i].addEventListener("click", klk_mdf);
    }
}



/**
 * Replace text node with control node, i.e. textbox.
 */
function klk_mdf() {

    var ctrl;

    if (this.querySelectorAll('input').length) { // Chech whether another textbox is active (should not be possible, but..)
        return;
    }

    // Get text
    var texter = this.innerHTML.trim();

    // Create
    ctrl = document.createElement("input");
    ctrl.setAttribute('type', 'text');
    ctrl.setAttribute('value', texter);
    ctrl.setAttribute('data-backer', texter); // Create text backup, which we will need if mdf is canceled by ESC.

    // Set attributes
    ctrl.className = 'form-control';
    ctrl.setAttribute('onkeydown', 'klk_key_watch(this)');
    ctrl.setAttribute('onblur', 'klk_update(this)');

    // Append
    this.innerHTML = '';
    this.appendChild(ctrl);

    ctrl.focus();
}


/**
 * Check which key was pressed inside the ctrl i.e. textbox. React on ENTER and ESC.
 *
 * @param {object} ctrl Text control, i.e. *this* object
 * @return {void}
 */
function klk_key_watch(ctrl) {

    var key = event.which;

    if (key==13 && ctrl.tagName=='INPUT') { // enter + textbox, i.e. one-line ctrl.. textarea excluded..
        klk_update(ctrl);
    }

    if (key==27) { // esc
        klk_cancel(ctrl);
    }

}


/**
 * On successful MDF, replace control node (i.e. textbox) back to text node with *new* text value.
 * (triggered by onblur event or *enter* keypress)
 *
 * @param {object} ctrl Text control, i.e. *this* object
 * @return {void}
 */
function klk_update(ctrl) {

    var td = ctrl.parentNode;

    td.innerHTML = ctrl.value;

    td.className = 'klk bg-success';
}


/**
 * On canceled MDF, replace control node (i.e. textbox) back to text node with *old* text value (from backup).
 * (triggered by *ESC* keypress)
 *
 * @param {object} ctrl Text control, i.e. *this* object
 * @return {void}
 */
function klk_cancel(ctrl) {

    ctrl.value = ctrl.getAttribute('data-backer');
    // First update ctrl value, because following code line which replaces the ctrl triggers onblur event,
    // which would make a problem.

    ctrl.parentNode.innerHTML = ctrl.getAttribute('data-backer');
}


/**
 * Submit. Before that, replace all text elements with controls, so that text would be passed in POST variable.
 */
function klk_submit(typ) {

    var tbl, trz, tdz, i, j, ctrl, td_wraper, ctrl_prev;
    var tr_start = 0, ctrl_name;


    if (typ=='lng') {
        tr_start = 1;
    }


    tbl = document.querySelector('table.klikle');

    trz = tbl.getElementsByTagName('tr');

    for (i = tr_start; i < trz.length; i++) {

        tdz = trz[i].getElementsByTagName('td');

        for (j = 1; j < tdz.length; j++) {

            if (tdz[j].getAttribute('name')=='klk') {

                ctrl_name = (tdz[j].id) ? tdz[j].id : 'ctrl' + '_' + i + '_' + j;

                ctrl = document.createElement("input");
                ctrl.setAttribute('type', 'hidden');
                ctrl.setAttribute('value', tdz[j].innerHTML.trim());
                ctrl.setAttribute('name', ctrl_name);
                ctrl.style.display = 'none';

                td_wraper = (typ=='lng') ? tdz[j] : tdz[j].previousSibling;

                ctrl_prev = td_wraper.querySelector('input[type="hidden"]');
                if (ctrl_prev) {
                    ctrl_prev.parentNode.removeChild(ctrl_prev);
                }

                td_wraper.appendChild(ctrl);
            }
        }
    }

    document.querySelector('form').submit();
}
