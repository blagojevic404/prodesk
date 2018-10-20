
/**
 * Deletes CRW line
 *
 * @param {object} z - Anchor object, i.e. "this" object for the del-button
 * @param {string} id_wrap - id/name of the wraper element
 * @return void
 */
function CRW_delete(z, id_wrap) {

    var n = 0;

    var srcTable = ancestor_finder(z, 'id', id_wrap, 5);


    // Get CRW TYPE of the line which is to be deleted

    // The only INPUT element in table is hidden field which holds crw_typ value
    var crwtyp = srcTable.getElementsByTagName('input');
    crwtyp = crwtyp[0].value;


    // Count how many lines of the specified CRW TYPE there is in the whole document

    var crwtyp_elements = document.getElementsByName("crw_typ[]");

    for (var i=0; i < crwtyp_elements.length; i++) {
        if (crwtyp_elements[i].value == crwtyp) {
            n++; // Count CRW lines of the specified type.
        }
    }

    if (n>1) { // If there is more than ONE line then go ahead and delete.

        srcTable.parentNode.removeChild(srcTable);

    } else { // If there is only one line, then RESET it, instead of deleting.

        var cbo = srcTable.getElementsByTagName('select');
        cbo = cbo[0];
        cbo.value = ''; // Using '0' instead of '' would produce an error, as it wouldn't select the default value..
    }
}




/**
 * Duplicates CRW line
 *
 * @param {object} z - Anchor object, i.e. "this" object for the add-button
 * @param {string} id_wrap - id/name of the wraper element
 * @return void
 */
function CRW_duplicate(z, id_wrap) {

    var srcTable = ancestor_finder(z, 'id', id_wrap, 5);

    var clonedTable = srcTable.cloneNode(true);

    // Reset selected uid to 0.
    var cbo = clonedTable.getElementsByTagName('select'); cbo = cbo[0];
    cbo.value = 0;

    insert_after(clonedTable, srcTable);
}






