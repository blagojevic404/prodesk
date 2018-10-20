
/* started via HEADER, i.e. every page includes this js */





/**
 * Change base size css rule and then initialize printing
 *
 * @param {int} siz - Base element size. Other sizes are calculated from this one.
 * @return void
 */
function printer(siz) {

    $('#printerModal').modal('hide'); // If we don't hide it this way, it will get caught-up in printing

    var style_tag = document.getElementById('printer');

    style_tag.innerHTML = 'table#epg_table td, table.mktplan td, div.prompter_print, div.atom_dtl ' +
        '{ font-size: ' + siz + 'pt !important; }';

    window.print();
}





/**
 * BS Replacement for alert() fuction
 */
function alerter(msg) {

    $('#alerterModal').modal(); // show modal

    document.querySelector("#alerterModalLabel").innerHTML = msg; // update the msg
}






/**
 * Prevents double form submit (i.e. double-click submit), by disabling submit button for a moment
 */
function prevent_double_submit(typ) {

    var i, btnz;

    btnz = document.querySelectorAll('input[type="submit"]');


    if (btnz[0].getAttribute('disabled')=='disabled') {

        for (i = 0; i < btnz.length; i++) {
            btnz[i].removeAttribute('disabled');
        }

    } else {

        for (i = 0; i < btnz.length; i++) {
            btnz[i].setAttribute('disabled', 'disabled');
        }

        var pause = (typ=='long') ? 20000 : 2000;

        setTimeout(function(){
            prevent_double_submit();
        }, pause); // Wait for couple of seconds and then re-enable the buttons
    }

    return true;
}



/**
 * Prevents double page request (i.e. double-click on the link), by disabling the link
 */
function link_disarm(z) {

    if (z.onclick) {
        z.onclick = null;
    }

    z.classList.add('disabled');
}



/**
 * Updates minHeight of the content area in order to position footer properly for short pages
 */
function footer_pos() {

    var h = 155;
    if (!document.querySelector("div#z-navbar-btm div")) { // index page has an empty btm navbar (blue)
        h -= 33;
    }
    document.querySelector(".content.not-tunel").style.minHeight = (window.innerHeight-h) + "px";
}



function show_clock(epg_now, epg_prg) {

    var timenow = server_time();
    var timenow_str = hms(timenow,1);

    write_layer("nowtime", timenow_str);

    if (epg_now) {
        write_layer("epg_now", timenow_str);
    }

    if (epg_prg) {
        write_layer("epg_prg_start", milli2hms(time_epg_start - timenow));
        write_layer("epg_prg_end", milli2hms(time_epg_end - timenow));
    }

    setTimeout(function(){show_clock(epg_now, epg_prg);}, 1000);
}

function hms(t,typ) {

    var b;

    if (typ==1) {b = ':'} else {b = ''};
    return fd(t.getHours()) + b + fd(t.getMinutes()) + b + fd(t.getSeconds());
}

function server_time() {

    var t = new Date();
    t.setMilliseconds(t.getMilliseconds() + clockdif); // we need to display SERVER time, not local browser time
    return t;
}

function milli2hms(z) {

    var signer = '';

    if (z<0){
        z = Math.abs(z);
        //signer = '-';
        z += 1000; // for some reason one second is missing if the value is negative
    }

    var dd = Math.floor(z/1000/60/60/24);		z -= dd*1000*60*60*24;		//if (dd) return '>24h' ;
    var hh = Math.floor(z/1000/60/60);			z -= hh*1000*60*60;
    var mm = Math.floor(z/1000/60);				z -= mm*1000*60;
    var ss = Math.floor(z/1000);

    return signer + fd(hh) + ':' + fd(mm) + ':' + fd(ss);
}

function fd(nd) {
    return (nd < 10 ? '0' : '') + parseFloat(nd);
}

function write_layer(layer_name, txt) {
    if (document.all) {
        document.all[layer_name].innerHTML = txt;
    } else {
        var xx = document.getElementById(layer_name);
        xx.innerHTML = txt;
    }
}





/**
 * Switch cvr text on/off
 */
function cvr_drop_swtch(btn) {

    var wrapdiv = btn.parentNode.parentNode;

    var tarea = wrapdiv.querySelector('textarea');

    display_swtch(tarea, 'block');

    // Adjust textarea height
    tarea.rows = 1;
    while (tarea.scrollHeight > tarea.offsetHeight) {
        tarea.rows++;
    }
}




/**
 * For DEL button: Varying modal submit-target based on trigger button
 */
function modal_del_onshow(event) {

    var modal = $(this);
    var button = $(event.relatedTarget);

    var vary_modaltext = button.data('vary_modaltext');
    var vary_submiter_href = button.data('vary_submiter_href');

    modal.find('.modal-body').text(vary_modaltext);
    modal.find('#del_submit').prop('href', vary_submiter_href);
}




/**
 * Adjust height for each textarea (with *no_vert_scroll* css) on the page
 */
function textarea_height() {

    var tarea;

    var ctrlz = document.querySelectorAll('textarea.no_vert_scroll');

    for (var i = 0; i < ctrlz.length; i++) {

        tarea = ctrlz[i];

        tarea.rows = 1;

        while (tarea.scrollHeight > tarea.offsetHeight) {
            tarea.rows++;
        }
    }
}

function expandTxtarea(tarea, rowz_min) {

    rowz_min = parseFloat(rowz_min);
    if (rowz_min<2) rowz_min = 2;

    while (tarea.scrollHeight > tarea.offsetHeight) {
        tarea.rows++;
    }

    while (tarea.scrollHeight < tarea.offsetHeight && tarea.rows >= rowz_min) {
        tarea.rows--;
    }

    tarea.rows++;
    tarea.rows++;
}















/**
 * Prettify HMS textboxes
 *
 * Used only in MDFMULTI, because we cannot use *autotab.js*
 *
 * @param {object} z *this* object
 * @param {string} hmstyp HMS type: (term, dur). We pass this to hms_reset(), as emptyval depends on it.
 * @return {bool} For unallowed characters we do not return *true* and they are thus ignored
 */
function hmsbox(z, hmstyp) {

    var boxz, i;

    var keyCode = ('which' in event) ? event.which : event.keyCode;

    //alert(keyCode);

    // Normal allowed characters (they simply go through the function and return back)
    var isNumeric   = (keyCode>=48 && keyCode<=57) || (keyCode>=96 && keyCode<=105); // 0-9 (+NUMPAD)
    var isDeleter   = (keyCode==8 || keyCode==46); // backspace, del
    var isTab 		= (keyCode==9); // TAB

    // Shortcut allowed characters (they invoke actions but they don't return, i.e. they don't print)
    var isHolder    = (keyCode==106 || (keyCode==56 && event.shiftKey)); // '*' both SHIFT+8 and NUMPAD
    var isDash	    = (keyCode==189 || keyCode==109); // '-' (+NUMPAD)
    var isTabkey 	= (keyCode==188 || keyCode==110 || keyCode==190); // '.' (+NUMPAD), ','

    //var modifiers   = (event.altKey || event.ctrlKey || event.shiftKey);


    if (isHolder) { // put '*' to all textboxes

        boxz = z.parentNode.getElementsByTagName('input');
        for (i = 0; i < boxz.length; i++)	{
            boxz[i].value = '*';
        }

        isNumeric = false; // Otherwise, SHIFT+8 (keyCode==56 && event.shiftKey) would be treated as NUMERIC..
    }

    if (isDash) { // reset (clear) all textboxes
        hms_reset(z, hmstyp);
    }

    if (isTabkey) { // focus next textbox

        var focus_next = false; // Switch will will trigger focusing
        boxz = z.parentNode.getElementsByTagName('input');
        for (i = 0; i < boxz.length; i++)	{
            if (focus_next) {
                boxz[i].select();
            }
            focus_next = (boxz[i]==z);
            // Whether THIS is the currently selected textbox (which means the next one should get focus)
        }
    }

    return isNumeric || isDeleter || isTab;
}





/**
 * Handle focus and blur for DURATION HMS textbox
 *
 * Used only in MDFMULTI, because we cannot use *autotab.js*
 *
 * @param {object} z *this* object
 * @param {int} etyp Event type: (0 - blur, 1 - focus)
 * @return void
 */
function hmsdur_focus(z, etyp) {

    if (etyp==0 && z.value=='') { // On blur, turn empty textbox to '00'
        z.value= '00';
    }

    if (etyp==1 && z.value=='00') {  // On focus, turn '00' to empty textbox
        z.value= '';
    }
}



/**
 * Reset HMS textboxes
 *
 * @param {object} z *this* object
 * @param {string} hmstyp HMS type: (term, dur). We pass this to hms_reset(), as emptyval depends on it.
 * @return void
 */
function hms_reset(z, hmstyp) {

    var emptyval;

    if (hmstyp=='dur') {
        emptyval = '00';
    } else {
        emptyval = '';
    }

    var boxz = z.parentNode.getElementsByTagName ('input');
    for (var i = 0; i < boxz.length; i++) {
        boxz[i].value = emptyval;
    }
}







/**
 * Equals insertAFTER (function which doesn't exist in JS)
 *
 * @param {object} newNode - New node
 * @param {object} referenceNode - Reference node
 * @return void
 */
function insert_after(newNode, referenceNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}



/**
 * Imitates PHP function of the same name
 */
function in_array(needle, haystack) {

    var length = haystack.length;

    for (var i = 0; i < length; i++) {
        if (haystack[i] == needle) {
            return true;
        }
    }
    return false;
}



/**
 * Save selected index of (radio) button group to shadow submitter (hidden input field).
 *
 * Used in MODIFY pages.
 *
 * @param {object} z - Label (around input radio button) object, i.e. "this" object for the label which was clicked
 * @return void
 */
function btngroup_shadow(z) {

    // Get the INDEX of the clicked button
    var index = z.querySelector('input').value;

    // Save the index to shadow submitter
    z.parentNode.parentNode.querySelector('input[type=hidden]').value = index; // Container must be two levels up
}



/**
 * Save selected index of (radio) button group to shadow submitter (hidden input field).
 *
 * @param {object} ctrl - Shadow submitter, i.e. input hidden control which actually submits the value of the btngroup
 * @param {int} index - Index of the selected button in the button group
 * @return void
 */
function btngroup_setter(ctrl, index) {

    var radio, i;

    ctrl.value = index;

    radio = ctrl.parentNode.querySelectorAll('input[type=radio]');

    for (i = 0; i < radio.length; i++) {

        if (radio[i].value==index) {
            radio[i].click();
            break;
        }
    }
}



/**
 * Get upstream element (i.e. ancestor) of specified atrribute type and value
 *
 * @param {object} z - Reference element, usually "this" object for the element which was clicked on
 * @param {string} attr_type - Attribute to use in the search
 * @param {string} attr_value - Value to use in the search
 * @param {int} level_limit - How many levels to check, i.e. how many ancestors to loop maximum
 * @return {object|void}
 */
function ancestor_finder(z, attr_type, attr_value, level_limit) {

    if (!level_limit) {
        level_limit = 5;
    }

    var attr;

    for (var i=0; i < level_limit; i++) {

        z = z.parentNode;

        attr = z.getAttribute(attr_type);

        if (attr==attr_value) {
            return z;
        }
    }
}



/**
 * Switches button for active/inactive state of an item
 *
 * @param {object} btn - Switch button. Contains only plus/minus sign.
 * @param {Array} signs - Classnames for active and inactive state sign
 * @param {string} needle - Needle which we will search for in the sign classname in order to determine the current state
 *
 * @return {boolean} actve - Active state (true or false)
 */
function btn_sign_switch(btn, signs, needle) {

    if (!signs) {
        signs = ['glyphicon glyphicon-ok-circle', 'glyphicon glyphicon-ban-circle'];
    }

    if (!needle) {
        needle = 'ok';
    }

    var span_glyph = btn.childNodes[0]; // button contains nothing but glyphicon span

    // If the classname contains needle, it is ACTIVE row. (Having *deactivate* button means we are in *active* state.)
    var actve = (span_glyph.className.indexOf(needle) > 0);

    // Switch the state and the sign on the button
    actve = !actve;
    span_glyph.className = (actve) ? signs[0] : signs[1];

    return actve;
}


/**
 * Turns display of the specified (by css class) element(s) on or off, by changing its display property.
 * (Developed for switching BC MODIFY buttons in FILM ITEM php script)
 *
 * @param {string} css_class - CSS class, to use for filtering elements
 * @param {string} display_property - Value of *display* property, to be set.
 * @return void
 */
function display_set(css_class, display_property) {

    var a = document.getElementsByClassName(css_class);

    for (var i = 0; i < a.length; i++) {
        a[i].style.display = display_property;
    }
}



/**
 * Alternate display of the specified (by css class) element(s)
 *
 * @param {string} css_class - CSS class, to use for filtering elements
 * @param {string} attr_on Attribute to be used for *ON* state
 * @return void
 */
function display_switch_all(css_class, attr_on) {

    var a = document.getElementsByClassName(css_class);

    for (var i = 0; i < a.length; i++) {
        display_swtch(a[i], attr_on);
    }
}




/**
 * Switch *display* property on/off
 *
 * @param {Element} ctrl Target control
 * @param {string} attr_on Attribute to be used for *ON* state
 *
 * @return {void}
 */
function display_swtch(ctrl, attr_on) {

    var real_display = window.getComputedStyle(ctrl, null).getPropertyValue('display');
    // Must use getComputedStyle(z), because simple z.style.display would ignore css declarations set through <style> tag etc..

    if (real_display=='none') {
        ctrl.style.display = attr_on;
    } else {
        ctrl.style.display = 'none';
    }
}




/**
 * Simple switch
 */
function swtch(z) {

    z.className = (z.className=='on') ? '' : 'on';
}




/**
 * Set value of input field (usually hidden input, i.e. shadow submitter)
 *
 * @param {object} container - Container element
 * @param {string} xname - Name (or only beginning part of it) of the target control
 * @param {*} xset - Value to be set
 * @return void
 */
function setHider(container, xname, xset) {

    var hiderz = container.getElementsByTagName('input');
    for (var i = 0; i < hiderz.length; i++)	{
        if (hiderz[i].name.substr(0, xname.length) == xname) {
            hiderz[i].value = xset;
        }
    }
}





/**
 * Cleans CLONE rows from DND form. Initiated on submit.
 *
 * @param {int} arr_start - Linetyp to start with (normally it is *1*)
 * @param {int} arr_end - Linetyp to end with
 * @return void
 */
function clone_cleaner(arr_start, arr_end) {

    var clone;

    for(var i = arr_start; i <= arr_end; i++) {
        clone = document.getElementById('clone['+i+']');
        if (clone) {
            clone.parentNode.removeChild(clone);
        }
    }
}





/**
 * Submits cbo control through $_GET. (This way we don't have to add FORM tag just in order to submit the cbo control.)
 *
 * @param {object} ctrl - CBO control
 * @return void
 */
function cbo_submit(ctrl) {

    var name = (ctrl.name) ? ctrl.name : ctrl.id;

    get_submit(name, ctrl.value);
}



/**
 * Submits name-value pair by sending it as $_GET variable while reloading current page
 *
 * @param {string} name - Attribute name, or name=value pair (if *vlu* is empty string)
 * @param {string} vlu - Attribute value
 * @return void
 */
function get_submit(name, vlu) {

    var gt = (vlu!=='') ? name + '=' + vlu : name;

    window.location.href = window.location.origin + window.location.pathname + window.location.search + '&' + gt;

    // We have to construct HREF this way, instead of just adding the new attributes to window.location.href,
    // because we want to exclude the anchor part of the url (location.hash), e.g. "#tr345"
    // (which would ruin the submit)
}



/**
 * Submits form when ENTER is pressed in a connected textbox.
 *
 * @param {object} e - keypress event
 * @return void
 */
function submit13(e){

    if(e.keyCode === 13){
        document.f.submit();
    }
}



// alert(JSON.stringify(obj));
