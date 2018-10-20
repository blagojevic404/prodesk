
var tableDnD1;
var table1;


function tableDnDOnLoad() {

    table1 = document.getElementById('dndtable');
    tableDnD1 = new TableDnD();
    tableDnD1.init(table1);

}


/**
 * Reads row order from DND table, and then saves it as a string into shadow submiters in order to be submited by the form
 *
 * Also saves linetype for each row. And for *spiceblock* also saves active state for each row.
 *
 * @param {string} typ - (none, spiceblock)
 * @return void
 */

function tablednd_queuer(typ) {

    var quStr = '';
    var quTypStr = '';

    if (typ=='spiceblock')	{
        var quActive = '';
    }


    if (!tableDnD1.table.tBodies[0]) {
        return; // empty
    }


    // Read rows data from table

    var rows = tableDnD1.table.tBodies[0].rows;

    for (var i=0; i<rows.length; i++) {

        if (rows[i].id) {

            quStr+=rows[i].id + ' ';          // read IDs (for ID order string)
            quTypStr+=rows[i].lang + ' ';     // read linetype from *lang* attribute (for linetype string)

            if (typ=='spiceblock') {
                quActive+=rows[i].getAttribute('active') + ' ';     // read active/inactive state (for active state string)
            }
        }
    }


    // Add shadow submiters

    var new_el;

    var container = document.getElementById('form1');

    new_el = document.createElement("input");
    new_el.type = 'hidden';
    new_el.id = 'dndtable_qu';
    new_el.name = 'qu';
    container.insertBefore(new_el, container.firstChild);

    new_el = document.createElement("input");
    new_el.type = 'hidden';
    new_el.id = 'dndtable_qu_typ';
    new_el.name = 'qu_typ';
    container.insertBefore(new_el, container.firstChild);

    if (typ=='spiceblock') {

        new_el = document.createElement("input");
        new_el.type = 'hidden';
        new_el.id = 'dndtable_qu_active';
        new_el.name = 'qu_active';
        container.insertBefore(new_el, container.firstChild);
    }


    // Save rows data to shadow submiters

    document.getElementById('dndtable_qu').value = quStr;
    document.getElementById('dndtable_qu_typ').value = quTypStr;

    if (typ=='spiceblock') {
        document.getElementById('dndtable_qu_active').value = quActive;
    }

}

