
/* EPG STUDIO functions */




/**
 * Epg studio: Speaker change.
 *
 * @param {object} z *this* object
 * @param {int} speakerx SpeakerX
 * @param {int} atomid Atom ID
 * @return {void}
 */
function speaker_change(z, speakerx, atomid) {

    var i, namer, spk_sel;

    var atom = document.getElementById('spk_atom'+atomid);

    var content = atom.getAttribute('data-content');

    var pms = atom.getAttribute('data-pms');

    var new_content = '';


    // Change the text in popover

    var spkrz = z.parentNode.getElementsByTagName('a');

    for (i = 0; i < spkrz.length; i++) {

        spk_sel = (spkrz[i].id=='spk_url'+speakerx);    // selected

        spkrz[i].className = 'spk_line';
        if (spk_sel || !pms) spkrz[i].className += ' disabled';
        if (spk_sel) spkrz[i].className += ' sel';

        if (spk_sel) {
            namer = spkrz[i].innerHTML;
            namer = namer.substr(4, namer.length)
        }

        new_content += spkrz[i].outerHTML + '<br>';
    }

    atom.setAttribute('data-content', new_content);


    // Change the text in speaker box (numero and name)

    var spanz = atom.getElementsByTagName('span');

    for (i = 0; i < spanz.length; i++) {

        if (spanz[i].className=='numero') {
            spanz[i].innerHTML = speakerx;
        }

        if (spanz[i].className=='speaker') {
            spanz[i].innerHTML = namer;
        }
    }


    // Change the color of the atom row

    var atom_wraper = atom.parentNode.parentNode;

    atom_wraper.className = 'row atom_wraper spkr' + speakerx;

}





/**
 * Epg studio: Display the label with NEW DURATION
 *
 * @param {int} new_dur New atom duration
 * @param {int} atomid Atom ID
 * @return {void}
 */
function atomdur_change(new_dur, atomid) {

    if (!new_dur) {
        return;
    }

    var divz = document.getElementById('spk_atom' + atomid).parentNode.getElementsByTagName('div');

    for (var i = 0; i < divz.length; i++) {

        if (divz[i].className=='new_dur') {

            divz[i].innerHTML = '<span>' + new_dur + '</span>';

            divz[i].style.display = (divz[i].lang!=new_dur) ? 'block' : 'none';
            // We use *lang* attribute to pass OLD DURATION value
        }
    }

}

