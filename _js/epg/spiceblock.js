

/* koristi se samo u epg/spice_modify.php */



/**
 * Add a new item to spice block
 *
 * @param {Array} row_arr - Data for the new item. It is passed from the iframe.
 * @return void
 */
function SPICE_item_add(row_arr) {

    var pos, txt, dur;


    // Insert new row at the bottom of DND table, and set its attributes

    var rowCount = table1.rows.length;  // *table1* is dndtable, and it is defined on global level
    var row = table1.insertRow(rowCount);

    row.id = row_arr[0];
    row.lang = row_arr[1];

    // Always use setAttribute()/getAttribute() to set/get attributes which are not part of the standard for the element.

    row.setAttribute('name', 'wraptr'); // *name* attribute is standard only for FORM CONTROLS, not for divs, tables, etc.
    row.setAttribute('active', '1');

    if (row_arr[1]=='5') row.className = 'clp';
    if (row_arr[1]=='4') row.className = 'prm';
    if (row_arr[1]=='3') row.className = 'mkt';


    // Insert a cell which will hold the new item, and copy the item clone into it

    row.insertCell(0);

    var new_line = row.cells[0];

    var clone_block_item = document.getElementById('clone_block_item').childNodes[0];

    var clone = clone_block_item.cloneNode(true);

    new_line.appendChild(clone);


    // We have to loop DIVs in order to set attributes for new item

    var divz = new_line.getElementsByTagName ('div');

    for (var j = 0; j < divz.length; j++) {

        if (divz[j].id=='numero') {
            divz[j].innerHTML = (rowCount+1);
        }

        if (divz[j].id=='dur') {

            dur = ((row_arr[2]) ? row_arr[2].substring(3,8) : '00:00');

            pos = row_arr[2].indexOf('*'); // delimiter for ff part

            if (pos>0) {
                dur += '<span class="hms_ff">.' + row_arr[2].substr(pos+1) + '</span>';
            }

            divz[j].innerHTML = dur;
        }

        if (divz[j].id=='cpt') {

            txt = divz[j].innerHTML;

            // We have to set ROW ID in the deleter function call. It is the native ID of the item.

            pos = txt.indexOf('item_del(')+9;

            divz[j].innerHTML = row_arr[3] + txt.substr(0,pos) + row_arr[0] + txt.slice(pos+1);
        }
    }

    // Reinitialize DND table
    tableDnD1.init(table1);

    // Recalculate duration
    SPICE_dur_sum();
}








/**
 * Deletes an item in spice block
 *
 * @param {int} rowid - Anchor object, i.e. "this" object for the deleter button
 * @param {object} z - Anchor object, i.e. "this" object for the deleter button
 * @return void
 */
function SPICE_item_del(rowid, z) {

    // Loop up the element tree until we get to WRAP TR in DND table.
    // We identify the WRAP TR by id attribute, which has the value of Item native ID
    while (z.id!=rowid)	{
        z = z.parentNode;
    }

    // Remove WRAP TR
    z.parentNode.removeChild(z);

    // Recalculate duration
    SPICE_dur_sum();
}





/**
 * Calculates summary duration of block and updates the value in DurCalc textboxes
 *
 * @return void
 */
function SPICE_dur_sum() {

    var wraptr, t;
    var pos, ff;

    var s = 0; // sum (in seconds)
    var s_ff = 0; // sum (frames)


    var xtable = document.getElementById('dndtable');

    var divz = xtable.getElementsByTagName('div');

    for (var i = 0; i < divz.length; i ++) {

        if (divz[i].getAttribute('name') != 'dur') continue; // Ignore divs which are not DUR

        wraptr = ancestor_finder(divz[i], 'name', 'wraptr', 5); // ACTIVE attribute is set in the WRAP TR

        if (wraptr.getAttribute('active') == 0) continue; // Ignore DUR divs which are not ACTIVE

        // Read item duration, which is in mm:ss format, and convert it to seconds; add it to sum.

        t = divz[i].innerHTML.split(':');

        s += parseFloat(t[0])*60 + parseFloat(t[1]); // sum in seconds
        // First I tried to use parseInt, but it seems to have a bug: parseInt('07')==7; but: parseInt('08')==0;

        pos = divz[i].innerHTML.indexOf('.'); // in this case, delimiter for ff part
        if (pos>0) {
            ff = divz[i].innerHTML.substr(pos+1, 2);
            s_ff += parseFloat(ff);
        }
    }

    if (s_ff) {
        s += Math.floor(s_ff / 25); // 25 frames make one second
    }

    // Convert *seconds* back to *mm:ss* format
    var mm = Math.floor(s / 60);
    s = s - mm * 60;
    var ss = s;

    // Update the value in DurCalc textboxes
    document.getElementById('DurCalcMM').value = fd(mm);
    document.getElementById('DurCalcSS').value = fd(ss);
}




/**
 * Switches active/inactive state of an item (i.e. row) in spice block
 *
 * @param {object} btn - Anchor object, i.e. "this" object for the switch button
 * @return void
 */
function SPICE_item_switch(btn) {


    // Switch button sign and get current state

    var actve = btn_sign_switch(btn);


    // Find WRAP DIV and set its class

    var wrapdiv = ancestor_finder(btn, 'name', 'wrapdiv', 2);

    wrapdiv.className = (actve) ? '' : 'switchedoff';


    // Find WRAP TR (in DND table) and set its *active* attribute, which will pass data to submit

    var wraptr = ancestor_finder(wrapdiv, 'name', 'wraptr', 2);

    wraptr.setAttribute('active', (actve ? '1' : '0'));


    // Recalculate duration
    SPICE_dur_sum();

    window.focus();
}


