


function checker_mktplan_item_replace(cur, msg) {

    var id = document.querySelector('input[type=hidden]#' + ifrm_id);

    var ifrm_vlu = parseInt(id.value);

    if (ifrm_vlu==0) { // ifrm control is empty: ask user whether that means he wants to *delete* the selected mpi's

        $('#deleterModal').modal(); // show modal

        return 1;

    } else if (ifrm_vlu==cur) { // ifrm control is unchanged: tell user

        alerter(msg);

        return 2;
    }
}



/**
 * Make sure that ifrm control got an ID from the ifrm
 */
function checker_ifrm() {

    var id = document.querySelector('input[type=hidden]#' + ifrm_id);

    return (parseInt(id.value)) ? false : true;
}



function checker_chk_group(cboxes) {

    var r = 0;
    var len = cboxes.length;
    for (var i=0; i<len; i++) {
        if (document.getElementById(cboxes[i]).checked) {
            r = 1;
        }
    }

    return r;
}



function checker_epg_multi() {

    var ctrlz = document.querySelectorAll('input[name^="ProgID"]');

    for (var i = 0; i < ctrlz.length; i++) {

        if (ctrlz[i].value==0) { // If there is at least one Prog cbo which has no selected value, abort.

            var tblwrap = ancestor_finder(ctrlz[i], 'id', 'tblwrap', 6);
            var td = tblwrap.parentNode;
            var del = td.querySelector('input[name^="del"]'); // We want to skip rows which are inactive
            var dsc = td.querySelector('input[name^="dsc"]'); // We want to skip rows which have dsc

            if (!del.value && !dsc.value) {
                return true;
            }
        }
    }

    return false;
}

