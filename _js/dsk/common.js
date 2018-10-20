
/**
 * Toggle CVR accordions on a story_details page
 *
 * @param {object} btn - Anchor object, i.e. "this" object for the collapse-button
 * @param {int} atom_cnt - Atom count
 * @return void
 */
function cvr_collapse(btn, atom_cnt) {

    var name, cvr_cnt;

    if (!btn) {
        btn = document.querySelector('a.cvr_collapser');
    }


    // Switch button sign
    var actve = btn_sign_switch(btn, ['glyphicon glyphicon-resize-small', 'glyphicon glyphicon-resize-full'], 'small');

    for (var i = 0; i <= atom_cnt; i++) {

        // We use 0 for STORY CVR accordion. (Others are ATOM CVR accordions)
        name = (i==0) ? '_stry' : i;

        // Read CVR count for each atom from its badge
        cvr_cnt = document.getElementById('badge_cvr'+name).innerHTML;

        // If CVR accordion is not empty, toggle it, otherwise hide it.
        $('#cvr'+name+'_collapse').collapse((cvr_cnt!='0') ? ((actve ? 'hide' : 'show')) : 'hide');
    }
}






/**
 * Prepare for printing only story elements of CAM type
 */
function print_cam_only(z) {

    z.style.color = 'green';

    var typ2 = document.querySelectorAll('div.atom_dtl.typ2');
    for (var i = 0; i < typ2.length; i++) {
        typ2[i].parentNode.parentNode.className += ' hidden-print';
    }

    var typ3 = document.querySelectorAll('div.atom_dtl.typ3');
    for (var i = 0; i < typ3.length; i++) {
        typ3[i].parentNode.parentNode.className += ' hidden-print';
    }
}


