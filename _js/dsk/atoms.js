
/**
 * Copy atom clone of specified type and paste into atoms table (dnd)
 *
 * @param {int} typ - Atom type ID
 * @return void
 */
function atom_clone(typ) {

    var clone_tmp = document.getElementById('clone['+ typ + ']');
    var clone = clone_tmp.cloneNode(true);

    clone.id = '';  // clear the id property of the cloned div
    clone.style.display = '';

    var table = document.getElementById('dndtable');
    var rowCount = table.rows.length;

    var new_row = table.insertRow(rowCount);
    new_row.insertCell(0);

    var td = new_row.cells[0];
    td.innerHTML = clone.outerHTML;

    td.innerHTML = '<div class="row">' + td.innerHTML + '</div>';
    td.className = table.rows[0].cells[0].className;

    new_row.id = rowCount + 1;

    var numero = new_row.getElementsByClassName('label'); numero = numero[0];
    numero.innerHTML = new_row.id;

    setHider (new_row, 'cnt', new_row.id);

    tableDnD1.init(table1); // reinitialize tableDND
}



/**
 * Switches atom on or off
 *
 * @param {object} btn - Anchor object, i.e. "this" object for the switch button
 * @return void
 */
function atom_switch(btn) {

    var wrapdiv = ancestor_finder(btn, 'name', 'atom', 3);
    var headdivz = wrapdiv.getElementsByClassName('atom_header');
    var textdiv = wrapdiv.getElementsByClassName('atom_texter'); textdiv = textdiv[0];
    var dscdiv = wrapdiv.getElementsByClassName('atom_dsc'); dscdiv = dscdiv[0];


    // Switch button sign and get current state
    var actve = btn_sign_switch(btn, ['glyphicon glyphicon-minus', 'glyphicon glyphicon-plus'], 'minus');


    // Adjust css for the header

    for (var i = 0; i < headdivz.length; i++) {

        var headcss = headdivz[i].className;
        var css_switch = ' atom_off';
        if (actve) {
            var n = headcss.indexOf(css_switch);
            if (n > 0) {
                headdivz[i].className = headcss.substring(0,n);
            }
        } else {
            headdivz[i].className = headcss + css_switch;
        }
    }


    // Switch display on or off for texter and dsc div

    textdiv.style.display = (actve) ? '' : 'none';
    dscdiv.style.display = (actve) ? '' : 'none';


    // Update del shadow.. It is located within wrap div.

    setHider (wrapdiv, 'del', (actve ? 0 : 1)); // we use ternary operator to convert bool to int
}



/**
 * Switches DSC textarea in atom
 *
 * @param {object} chk - Checkbox label object, i.e. "this" object for the DSC checkbox label
 * @return void
 */
/* ATOMBACK (discontinued)
function atom_dsc_switch(chk) {

    var container = chk.parentNode.parentNode;

    // Show or hide DSC textbox
    for (var i = 0; i < container.childNodes.length; i++) {
        var child = container.childNodes[i];
        if (child.nodeType == 1 && child.tagName=='TEXTAREA') {
            child.style.display = (chk.checked) ? '' : 'none';
        }
    }

    // Update shadow control for the checkbox.
    // We use only the beggining ot the control name ("shd"), because we actually have an array of the similar controls,
    // so using each control's full name would be problem..
    setHider (container, 'shd', ((chk.checked) ? 1 : 0));
}

 */











/**
 * Estimate how much time will it take to read specified text
 *
 * NOTE: This function must be identical in PHP (atom_txtdur) and JS (js_atom_txtdur)
 *
 * @param {object} btn - Anchor object, i.e. "this" object for the init button
 * @param {int} speed - Speed
 *
 * @return void
 */
function js_atom_txtdur(btn, speed) {

    var wrapdiv = ancestor_finder(btn, 'name', 'atom', 4);

    var tarea = wrapdiv.getElementsByTagName('textarea');
    tarea = tarea[0];

    var txt = tarea.value;

    if (!txt) {
        alerter('00:00:00');
    }

    var txtlen = js_atom_txtlen(txt);

    var s = Math.round(txtlen * speed);

    var dur = new Date(s * 1000).toISOString().substr(11, 8); // secs to hms

    alerter(dur);
}




/**
 * Calculate atom text length
 *
 * NOTE: This function must be identical in PHP (atom_txtlen) and JS (js_atom_txtlen)
 *
 * @param {string} txt - Text
 * @return {int} txtlen - Length in characters
 */
function js_atom_txtlen(txt) {

    txt = txt.trim();

    var txtlen = txt.length;

    var cnt_digits = txt.replace(/[^0-9]/g,'').length;

    if (cnt_digits) {
        txtlen += cnt_digits * 5; // Add 5 characters for each digit
    }

    return txtlen;
}


