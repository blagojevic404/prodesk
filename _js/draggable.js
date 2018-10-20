
/**
 * ondragstart: Put ID attribute of the dragged element (source) to event memory
 *
 * @param {Event} event (Contains SOURCE element)
 */
function drg_start(event) {

    // Set the data for transfer: Data type is "Text" and the value is the ID attribute of the dragged element (SOURCE)
    event.dataTransfer.setData("Text", event.target.id);
}


/**
 * ondragover: change the bottom-border color of the dragged-over element (potential target) to red
 *
 * Note: While dragging an element, the ondragover event fires every 350 milliseconds.
 */
function drg_over(event) {

    event.preventDefault();

    drg_border_switch(event, 'over');
}

/**
 * ondragleave: change back the bottom-border color to initial
 */
function drg_leave(event) {

    drg_border_switch(event, 'out');
}

/**
 * Change bottom-border color
 *
 * @param {Event} event
 * @param {string} typ Type of the bottom-border change (over, out) - see functions above
 *
 * @return {void}
 */
function drg_border_switch(event, typ) {

    var orig_border_width;

    var tgt = event.target;

    var td = (tgt.tagName=='SPAN') ? tgt.parentNode : tgt;


    if (td.getAttribute('orig_border_width')==null) { // If not previously set

        var real_css = window.getComputedStyle(td, null);

        orig_border_width = real_css.getPropertyValue('border-bottom-width'); // Note: Uses CSS, not JS/DOM values!

        td.setAttribute('orig_border_width', orig_border_width);

        if (orig_border_width!='0px') {

            td.setAttribute('orig_border_color', real_css.getPropertyValue('border-bottom-color'));
        }

    } else {

        orig_border_width = td.getAttribute('orig_border_width');
    }


    if (typ=='over') {

        if (orig_border_width=='0px') {
            td.style.borderBottomWidth = '1px';
        }

        td.style.borderBottomStyle = 'solid';
        td.style.borderBottomColor = '#ff0000';

    } else { // out

        if (orig_border_width=='0px') {
            td.style.borderBottomStyle = 'none';
        } else {
            td.style.borderBottomColor = td.getAttribute('orig_border_color');
        }
    }

}


/**
 * ondrop: The element is dropped to the target
 *
 * @param {Event} event (Contains TARGET element)
 */
function drg_drop(event) {

    event.preventDefault(); // Prevent the browser default handling of the data (default is open as link on drop)


    // Get the transfer data (that was set on ondragstart): ID attribute of the dragged element (SOURCE)
    var item_data = event.dataTransfer.getData("Text");

    var item_typ = item_data.substring(0, 4); // We use first 4 chars of id to pass type (bloc/item)
    var item_id = item_data.substring(4); // The rest is ID from the db table
    var target_id = event.target.id.substring(4); // (TARGET)
    var case_type = event.target.getAttribute("CaseType"); // (spicer, mktepg)


    // Ignore dropping elements other than we intended - they will not have ID starting with "bloc"/"item"
    if (item_typ!='bloc' && item_typ!='item') {
        drg_leave(event);
        return;
    }


    if (case_type=='mktepg') {

        // We added block numero to DRG ID, to prevent dropping to other (than parent) block

        var item_block = item_id.substring(0, item_id.indexOf('.'));
        var target_block = (target_id!=0) ? target_id.substring(0, target_id.indexOf('.')) : item_block;
        if (item_block!=target_block) { // Not parent block, abort..
            return;
        }

        item_id = item_id.substring(item_id.indexOf('.')+1);
        target_id = (target_id!=0) ? target_id.substring(target_id.indexOf('.')+1) : 0;
    }


    if (case_type=='spicer') {


        var target_typ = event.target.id.substring(0, 4);

        // When dropping on *block* row while clipz are hidden, we stop and show alert and switch on the clipz.
        // (Item would be dropped on *first* place, i.e. before clipz, and that's probably not what user wants.)
        if (target_typ=='bloc') {

            var actve = sessionStorage.getItem('spc_clipz_state');

            if (actve!='full') {

                drg_leave(event);

                alerter(g_alerter_spc_hidden_wrapclips);

                spc_clipz_switch(document.querySelector('#spc_clipz_btn'));

                return;
            }
        }

        var url = '_ajax/_aj_epg_spc_sw.php';

        var data = 'itemid=' + item_id + '&target_typ=' + target_typ + '&target_id=' + target_id;


    } else if (case_type=='mktepg') {


        if (item_id==target_id) { // No changes
            drg_leave(event);
            return;
        }

        var url = '_ajax/_aj_mktplan.php';

        var data = 'typ=' + case_type + '&source_id=' + item_id + '&target_id=' + target_id;


    } else { // error
        alert('Oi!');
        return;
    }

    ajaxer('POST', url, data, 'reloader', null);
}


