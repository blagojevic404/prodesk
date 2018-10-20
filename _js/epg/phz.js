
/* EPG Story phase uptodating functions */






/**
 * Loop (periodically call) the procedure of phz uptodating - which starts with ajaxer().
 */
function phzuptd_init() {

    // This is for testing - if we want to fire just once, on the page load..
    //setTimeout(function(){ajaxer('GET', '_ajax/_aj_phzuptd.php', 'idz=' + phzuptd_snaper('id'), 'phzuptd', null);}, 1000);

    phzuptd_intrval_ajax = setInterval(
        function(){ajaxer('GET', '_ajax/_aj_phzuptd.php', 'idz=' + phzuptd_snaper('id'), 'phzuptd', null);},
        phzuptd_ajax_cnt
    );
}



/**
 * Read current state of affairs for phases - get either StoryIDs or Phases array
 *
 * @param {string} typ (id, phz) - Whether to fetch StoryIDs or Phases
 * @param {string} rtyp (str, arr) - return type
 *
 * @return {string|Array} List of StoryIDs or Phases
 */
function phzuptd_snaper(typ, rtyp) {

    if (!rtyp) rtyp = 'str';

    var r = [];

    var dotz = document.querySelectorAll('span.phz');

    for (var i = 0; i < dotz.length; i++) {
        r[i] = dotz[i].getAttribute('data-' + typ);
    }

    return (rtyp=='str') ? r.toString() : r;
}



/**
 * Compare old values with new (fetched via ajax), and replace when they differ. Called on ajax success.
 *
 * @param {string} phzstr_new List of phases, fetched via ajax
 *
 * @return {void}
 */
function phzuptd(phzstr_new) {

    var dot;

    var phzstr_old = phzuptd_snaper('phz');

    if (!phzstr_new || phzstr_old==phzstr_new) {
        return;
    }

    var idzarr = phzuptd_snaper('id', 'arr');

    var phzarr_new = phzstr_new.split(',');
    var phzarr_old = phzstr_old.split(',');

    for (var i = 0; i < idzarr.length; i++) {

        if (phzarr_old[i]!=phzarr_new[i]) {

            dot = document.querySelector('span#phz' + idzarr[i]);

            phzuptd_dot(dot, phzarr_new[i]);
        }
    }
}



/**
 * Update the *dot*, i.e. the phase span html
 *
 * @param {object} dot SPAN element
 * @param {string} phz New phase
 *
 * @return {void}
 */
function phzuptd_dot(dot, phz) {

    dot.setAttribute('data-phz', phz);

    // js arrays start from 0
    var js_phz = phz-1;

    dot.style.color = '#' + phz_clrarr[js_phz];
    dot.setAttribute('title', phz_ttlarr[js_phz]);
}








/**
 * Change the story phase (increment or decrement)
 *
 * @param {object} e Event from *onmousedown*
 *
 * @return {void}
 */
function phzclick(e) {

    //console.log(e);
    //console.log('Item: ', e);

    var src = e.srcElement;

    if (src.localName=='span') { // It can be <a> element, but also its child <span> element, in which case we go up to <a>.
        src = src.parentNode;
    }

    var id = src.getAttribute('data-id');

    var typ = (e.which==1) ? 'up' : 'down'; // Left or right click.. left=1, right=3

    ajaxer('POST', '/desk/_ajax/_aj_phz.php', 'typ='+typ+'&id='+id, 'phz_ajax_success', src);
}



/**
 * Puts *onmousedown* event on every story phz <a> tag. We have to do it that way because this is the only way to make
 * event listening for WHICH button was clicked (left or right) work.
 * I.e. it wouldn't work if you put it as an attribute in element.
 */
function phzclick_init() {

    var dotz = document.querySelectorAll('a.phz.lift');

    for (var i = 0; i < dotz.length; i++) {
        dotz[i].onmousedown = phzclick;
    }
}

