
/* EDITABLE DIV & EDITABLE CTRL functions */






/**
 * Editable DIV: switch DIV with TEXTBOX
 *
 * @param {object} div *this* object
 * @return {void}
 */
function editdivable_line(div) {

    var ctrl;

    var sel = window.getSelection();
    var pos = sel.anchorOffset;   // cursor position


    // Hide DIV
    div.style.display = 'none';


    // Check if text-ctrl already exists..

    var tmp = div.parentNode.getElementsByTagName('input');

    if (!tmp[0]) { // Does not exist - this is FIRST time: CREATE text-ctrl

        // Get text
        var texter = editdivable_conv(div.innerHTML);

        // Create
        ctrl = document.createElement("input");
        ctrl.setAttribute('type', 'text');
        ctrl.setAttribute('value', texter);
        ctrl.setAttribute('data-ajax', div.getAttribute('data-ajax'));
        ctrl.setAttribute('data-type', div.getAttribute('data-type')); // This might be used on success, as a trigger

        // Set attributes

        // Use *data-ctrlcss* to pass css to ctrl, or set empty to simply avoid using default ('form-control')
        var ctrl_css = div.getAttribute('data-ctrlcss');
        ctrl.className = (ctrl_css===null) ? 'form-control' : ctrl_css;

        // Use *data-ctrlmax* to set *maxlength* for ctrl
        var ctrl_max = div.getAttribute('data-ctrlmax');
        if (ctrl_max) {
            ctrl.setAttribute('maxlength', ctrl_max);
        }

        ctrl.setAttribute('onkeydown', 'editdivable_canceler(this)');
        ctrl.setAttribute('onblur', 'editdivable_ajax_front(this)');

        // Append
        div.parentNode.appendChild(ctrl);

    } else { // Exists - This is NOT FIRST time: simply activate text-ctrl

        ctrl = tmp[0];
        ctrl.style.display = '';
    }


    // Set cursor position
    ctrl.setSelectionRange(pos, pos);
    ctrl.focus();
}






/**
 * Editable DIV: switch back TEXT-CTRL with DIV.
 *
 * This is called after ajax success.
 *
 * @param {object} ctrl *this* object
 * @param {object} key Key (0-blur, 27-cancel)
 *
 * @return {void}
 */
function editdivable_finito(ctrl, key) {

    // Get DIV
    var tmp = ctrl.parentNode.getElementsByTagName('div');
    var div = tmp[0];

    if (key==27) { // 27 - cancel: Reset text-ctrl

        ctrl.value = editdivable_conv(div.innerHTML);

    } else { // 0 - blur: Update DIV

        div.innerHTML = ctrl.value.replace(new RegExp('\n', 'g'), '<br>\n');
    }

    // Show DIV, hide TEXT-CTRL
    div.style.display = '';
    ctrl.style.display = 'none';
}





/**
 * Convert DIV text (which is HTML) to text for form control (which is simple TXT format).
 *
 * @param {string} txt DIV text
 * @return {string} txt FORM-control text
 */
function editdivable_conv(txt) {

    txt = txt.replace(new RegExp('<br>', 'g'), ''); // BR to NL
    txt = txt.replace(new RegExp('&amp;', 'g'), '&'); // Fix '&'
    txt = txt.replace(new RegExp('&nbsp;', 'g'), ' ');

    txt = txt.trim();

    return txt;
}






/**
 * Cancels current DIV editing
 *
 * @param {object} ctrl Text control, i.e. *this* object
 * @return {void}
 */
function editdivable_canceler(ctrl) {

    var key = event.which;

    if (key==13 && ctrl.tagName=='INPUT') { // enter + textbox, i.e. one-line ctrl.. textarea excluded..
        editdivable_ajax_front(ctrl);
    }

    if (key==27) { // esc
        editdivable_finito(ctrl, key);
    }
}





















/**
 * Editable DIV: switch DIV with TEXTAREA
 *
 * @param {object} div *this* object
 * @param {int} atomid Atom ID
 * @return {void}
 */
function editdivable_block(div, atomid) {

    var i, tarea;

    var sel = window.getSelection();
    var start = sel.anchorOffset;   // Relative selection start position (relative to *anchorNode* beginning).
    var node = sel.anchorNode;      // Node which has the selection. Each text chunk (separated by BR) is one node.


    // Loop all nodes within DIV, in order to calculate the cursor position relative to DIV beginning.

    var pos = 0; // cursor position

    var nodz = div.childNodes;

    for (i = 0; i < nodz.length; i++) {

        if (nodz[i] == node) {  // If this is the anchorNode, add up the *relative selection start* and it is finished.

            pos += start;
            break;

        } else { // If this is not the node which has the selection (anchorNode), add up its text length and continue.

            if (nodz[i].data) {
                pos += nodz[i].data.length;
            }
        }
    }


    // Hide DIV
    div.style.display = 'none';


    // Check if textarea already exists..

    var tmp = div.parentNode.getElementsByTagName('textarea');

    if (!tmp[0]) { // Does not exist - this is FIRST time: CREATE textarea

        // Get text
        var texter = editdivable_conv(div.innerHTML);

        // Create
        tarea = document.createElement("textarea");
        var tarea_txt = document.createTextNode(texter);
        tarea.appendChild(tarea_txt);

        // Set attributes
        tarea.className = 'form-control no_vert_scroll';
        tarea.setAttribute('onkeyup', 'expandTxtarea(this,3)');
        tarea.setAttribute('onfocus', 'expandTxtarea(this,3)');
        tarea.setAttribute('onkeydown', 'editdivable_canceler(this)');
        tarea.setAttribute('onblur', 'studio_txt_ajax_front(this, ' + atomid + ')');

        // Append
        div.parentNode.appendChild(tarea);

    } else { // Exists - This is NOT FIRST time: simply activate textarea

        tarea = tmp[0];
        tarea.style.display = '';
    }

    // tarea.style.width = div.offsetWidth + 'px';
    // This is unnecessary as BS seems to be taking care of the width for controls with *form-control* class..


    // Set cursor position
    tarea.setSelectionRange(pos, pos);
    tarea.focus();
}

















/**
 * Editable CTRL: Make a copy of current ctrl value (i.e. text content), so we can later compare it to see
 * if anything changed (because if it didn't then we can skip calling ajax).
 *
 * This is called when ctrl gets focus
 *
 * @param {object} ctrl *this* object
 * @return {void}
 */
function editctrlable_mirror(ctrl) {

    ctrl.setAttribute('data-mirror', ctrl.value);

    ctrl.style.backgroundColor = '#ffb';
}


/**
 * Editable CTRL: finito cleanup
 *
 * This is called after ajax success.
 *
 * @param {object} ctrl *this* object
 * @param {object} key Key (0-blur, 27-cancel)
 *
 * @return {void}
 */
function editctrlable_finito(ctrl, key) {

    if (key==27) { // 27 - cancel: Reset text-ctrl

        ctrl.value = ctrl.getAttribute('data-mirror');
    }

    ctrl.style.backgroundColor = '#fff';
}



/**
 * Cancels current CTRL editing
 *
 * @param {object} ctrl Text control, i.e. *this* object
 * @return {void}
 */
function editctrlable_canceler(ctrl) {

    var key = event.which;

    if (key==13 && ctrl.tagName=='INPUT') { // enter + textbox, i.e. one-line ctrl.. textarea excluded..
        editctrlable_ajax_front(ctrl);
    }

    if (key==27) { // esc
        editctrlable_finito(ctrl, key);
    }
}





