

/**
 * Editable TERM: switch to editing state - display div with textbox and links.
 *
 * @param {object} span *this* object
 * @param {string} typ (term, dur, dur_wide)
 * @param {int} onblur_submit Whether to submit on BLUR
 * @return {void}
 */
function hms_editable(span, typ, onblur_submit) {

    var tmp, inp, div, anchor, glyph;
    var td = span.parentNode;

    if (!onblur_submit) {
        onblur_submit = 0;
    }

    // Check if mdf-div already exists..

    tmp = td.getElementsByTagName('div');

    if (!tmp[0]) { // Does not exist - this is FIRST time: CREATE it

        div = document.createElement('div');
        div.className = 'bg-primary hmsedit';

        inp = document.createElement('input');
        inp.value = span.getAttribute('data-hms');
        inp.className = typ;
        inp.setAttribute('type', 'text');
        inp.setAttribute('maxlength', ((typ=='dur') ? '5' : '8')); // dur is mm:ss, term and dur_wide are hh:mm:ss
        inp.setAttribute('onkeydown', 'keyhandler(this)');
        inp.setAttribute('onblur', 'z=this; setTimeout(function(){hms_editable_finito(z,0,' + onblur_submit + ');}, 250)');
        // We put a timeout delay in order to give browser the chance to react on button click (buttons in epg term field)
        div.appendChild(inp);

        if (typ=='term') {

            anchor = document.createElement('a');
            anchor.href = 'epg.php?' + span.getAttribute('data-query') + '&termnow=' + span.getAttribute('data-id');
            glyph = document.createElement('span');
            glyph.className = 'glyphicon glyphicon-bell';
            anchor.appendChild(glyph);
            div.appendChild(anchor);

            anchor = document.createElement('a');
            anchor.href = 'epg.php?' + span.getAttribute('data-query') + '&termdel=' + span.getAttribute('data-id');
            glyph = document.createElement('span');
            glyph.className = 'glyphicon glyphicon-remove';
            anchor.appendChild(glyph);
            if (span.getAttribute('data-termtyp')=='zero') {
                anchor.className = 'disabled';
            }
            div.appendChild(anchor);

            if (span.getAttribute('data-epgtyp')=='epg') {
                anchor = document.createElement('a');
                anchor.href = 'epg.php?' + span.getAttribute('data-query') + '&holder=' + span.getAttribute('data-id');
                glyph = document.createElement('span');
                glyph.className = 'glyphicon glyphicon-asterisk';
                anchor.appendChild(glyph);
                if (span.getAttribute('data-termtyp')=='hold') {
                    anchor.className = 'disabled';
                }
                div.appendChild(anchor);
            }
        }

        td.appendChild(div);

    } else { // Exists - This is NOT FIRST time: simply activate it

        div = tmp[0];
        div.style.display = '';

        tmp = div.getElementsByTagName('input');
        inp = tmp[0];
    }

    inp.focus();

    if (typ=='dur' && inp.value=='00:00') {
        inp.select();
    }

    if (typ=='dur_wide' && inp.value=='00:00:00') {
        inp.select();
    }

}





/**
 * Editable TERM: switch back to normal state - hide editing div.
 *
 * @param {object} inp *this* object
 * @param {int} key Key (0-blur, 13-enter, 27-cancel)
 * @param {int} onblur_submit Whether to submit on BLUR
 * @return {void}
 */
function hms_editable_finito(inp, key, onblur_submit) {

    var wrap, div, span;

    wrap = inp.parentNode.parentNode;
    div = wrap.querySelector('div.hmsedit');
    span = wrap.querySelector('span.hmsedit');

    // When onblur_submit is on and CANCEL was pressed, then this fn is called twice - first on the CANCEL press,
    // then the onblur is triggered too. And the second time would trigger submit, which we dont want..
    if (onblur_submit && div.style.display=='none') {
        return;
    }


    if (key==27) { // (ESCAPE) Cancel: Reset textbox

        inp.value = span.getAttribute('data-hms');

    } else if (key==13 || onblur_submit) { // (ENTER) Submit: Update SPAN and go to php

        if (inp.value!=span.getAttribute('data-hms')) { // Submit only if the value changed at all..

            span.innerHTML = inp.value;

            get_submit('&mdfid=' + span.getAttribute('data-id') + '&mdfhms=' + inp.value, '');
        }
    }


    // Hide MDF-DIV
    div.style.display = 'none';
}






/**
 * Handle key press
 *
 * @param {object} z *this* object
 * @return {void}
 */
function keyhandler(z) {

    var key = event.which;

    if (key!=27 && key!=13) { // Ignore all keystrokes except: esc, enter
        return;
    }

    hms_editable_finito(z, key);
}



