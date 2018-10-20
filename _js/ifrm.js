
/* IFRM LABEL-CONTROL functions */











// COMMON

/**
 * Receives and handles result from iframe
 *
 * @param {Array} s - Result array
 * @return void
 */
function ifrm_result(s){

    switch (ifrm_result_typ){  // Type (multi, single, spice)

        case 'spice':

            SPICE_item_add(s);
            break;

        case 'single':

            SNGifrm_hide();
            SNGifrm_reset(s[0], s[3], s[2]);
            break;

        case 'multi':

            // Get Iframe object
            var ifrmz = document.getElementsByTagName('iframe');
            for (var i = 0; i < ifrmz.length; i++)	{
                if (ifrmz[i].style.display == 'block') {
                    var ifrm = ifrmz[i];
                    break;
                }
            }

            // Get TR_FORM
            var tr_ifrm = ifrm.parentNode.parentNode;
            var parentTB = tr_ifrm.parentNode;
            var tr_form = parentTB.childNodes[0];

            MLTifrm_result_updater(tr_form, s);

            MLTifrm_remove_all();

            break;
    }

}







/**
 * Displays iframe. Triggered by iframe label *onclick* event
 *
 * @param {string} typ - Caller type: (normal, cvr_box, cvr_epg)
 * @param {string|object} z - Either id of the iframe control (for mdfsingle), or iframe label object (for mdfmulti)
 * @param {string} url - Url to open in iframe
 * @return void
 */
function ifrm_starter(typ, z, url) {


    if(typeof(z)=='string') { // MDF_SINGLE - sends id of the control

        ifrm = document.getElementById(z);

        if (ifrm.style.display=='block') {  // If the iframe is already open

            // Check whether we are sending signal with the same url or with a different. If it is the same url, then we
            // should just close the iframe. If it is the different url, then we should not close iframe but open new url.
            // (This will happen on MDF_SPICE_BLOCK page, where we have TWO buttons (items, clips) to start the same iframe)

            var pos_end, pos_start, pos_url, url_new;

            pos_end = url.indexOf('.php') + 4;  // *url* is NEW url

            pos_start = (url.substr(0, 3)=='../') ? 3 : 0; // Had to add this so TASK ifrm would close correctly..

            url_new = url.substr(pos_start, pos_end);

            pos_url = ifrm.src.indexOf(url_new);    // *ifrm.src* is previous url

            if (pos_url != -1) { // if new url is on the same page as previous url, then HIDE, otherwise skip this and do OPEN

                SNGifrm_hide(ifrm);
                return;
            }

        }

    } else { // MDF_MULTI - sends *this* object (for the iframe label)

        var clone_id = 'ifrmctrl';  // id of the iframe CLONE table
        var ifrmz, ifrm, wrapdiv, clone;


        if (typ=='cvr_box' || typ=='cvr_epg') {


            if (typ=='cvr_box') {
                wrapdiv = ancestor_finder(z, 'name', 'wraper', 6);
            } else { // cvr_epg
                wrapdiv = document.querySelector('div#wraper');
            }

            ifrmz = wrapdiv.getElementsByTagName('iframe');


            // If this iframe is already open, then the click should close it
            if (ifrmz.length) {

                ifrm = ifrmz[0];

                if (typ=='cvr_box') {
                    ifrm.parentNode.parentNode.parentNode.removeChild(ifrm.parentNode.parentNode);
                } else { // cvr_epg
                    ifrm.parentNode.parentNode.removeChild(ifrm.parentNode);
                }

                return;
            }


            // Check whether there are other open iframes and close them
            CVRifrm_remove_all();


            if (typ=='cvr_box') {
                clone = document.getElementById(clone_id).childNodes[0];
            } else { // cvr_epg
                clone = document.getElementById(clone_id).childNodes[0].childNodes[0];
            }

            var doubler = clone.cloneNode(true);
            wrapdiv.insertBefore(doubler, null);
            // If referenceNode is null, then newNode is inserted at the end of the list of child nodes.


            // Get iframe object
            ifrmz = wrapdiv.getElementsByTagName('iframe');
            ifrm = ifrmz[0];


        } else { // normal


            var tr_form = z.parentNode.parentNode.parentNode.parentNode;

            var parentTB = tr_form.parentNode;  // parent TBODY

            // Remove squizrow before proceeding to the ifrm row removing procedure.
            // This is a fix for a bug, when opening a squizrow and then clicking on ifrm starter label would result in
            // removing of the control line itself..
            squizrow_cleaner(parentTB.parentNode.parentNode.parentNode, 0);

            // If this iframe is already open, then the click should close it
            if (parentTB.rows.length > 1) { // if ifrm tr is present then rowcount is 2
                MLTifrm_remove(parentTB);
                return;
            }

            // Check whether there are other open iframes and close them
            MLTifrm_remove_all();


            // Add iframe row

            // Get to (first and the only) row inside clone table (which is inside tbody, therefore TWO levels down)
            var tr_clone = document.getElementById(clone_id).childNodes[0].childNodes[0];

            // Prepare TARGET tr
            var tr_ifrm = parentTB.insertRow(1);

            // Copy CONTENTS of clone tr to target tr, therefore no need to adjust *display* css of the container
            tr_ifrm.innerHTML = tr_clone.innerHTML;

            // Parent table body now contains two rows: normal row with form controls and row where we load iframe


            // Get iframe object
            ifrmz = tr_ifrm.getElementsByTagName('iframe');
            ifrm = ifrmz[0];
        }

    }



    // Activate our IFRM

    if (url) { // All linetypes except PROG

        ifrm.src = url;
        ifrm.style.display = 'block';
        setIframeHeight(ifrm);

    } else { // PROG doesn't have url

        // Reset iframe window content, in case it was already open.
        ifrm.contentWindow.document.body.innerHTML = '';

        // CSS for the iframe window
        var css =   'td {vertical-align:top;font-size:15px;padding:3px 5px;font-family:verdana}'+
                    'div {margin-bottom:5px;}'+
                    'div.title {font-weight:bold;margin:5px 0 10px 0;font-variant:small-caps;}'+
                    'a {color:#009;text-decoration:none;}'+
                    'a:hover {background-color:#00F;color:#FFF;}'+
                    'body {margin:0;}'+
                    'td.even {background-color:#f8f8f8;}'+
                    'td.odd {background-color:#fff;}'+
                    'div:first-letter {font-weight:bold;}';
        ifrm_page_css(ifrm.contentWindow, css); // adds css to iframe window

        // Get program list clone. We previously prepared this clone by php.
        var prglist = document.getElementById('prglist').cloneNode(true);
        prglist.style.display = 'block';
        prglist.style.visibility = 'visible';

        // Paste clone to iframe window
        ifrm.contentWindow.document.body.appendChild(prglist);

        ifrm.style.display = 'block';
        setIframeHeight(ifrm);
        ifrm.style.visibility = 'visible';


        // Mark the currently selected prog, if any

        var prgid = 0;

        // Get the prog id (read it from shadow submitter)
        var inz = tr_form.getElementsByTagName('input');
        for (var i = 0; i < inz.length; i++) {
            if (inz[i].id=='ProgID[1][]') {
                prgid = inz[i].value;
            }
        }

        // Locate the prog in the proglist and mark it
        if (prgid) {
            var prg_selected = ifrm.contentWindow.document.getElementById('prgid'+prgid);
            prg_selected.style.backgroundColor = 'yellow';
        }

    }

}







/**
 * Refresh iframe's height.
 *
 * Should be called each time after the iframe's src url is changed, i.e. on each page load..
 * That's why we put it in iframe's *onload* event.
 *
 * @param {object} ifrm - Iframe object
 * @return void
 */
function setIframeHeight(ifrm) {
    var doc = ifrm.contentDocument ? ifrm.contentDocument : ifrm.contentWindow.document;
    ifrm.style.visibility = 'hidden';
    ifrm.style.height = "10px"; // reset to minimal height in case going from longer to shorter doc
    ifrm.style.height = getDocHeight(doc) + "px";
    if (ifrm.src!='about:blank') {
        ifrm.style.visibility = 'visible';
    }
}

/**
 * Get height of entire document.
 *
 * from http://stackoverflow.com/questions/1145850/get-height-of-entire-document-with-javascript
 *
 * @param {object} doc - Document object
 * @return {int} height
 */
function getDocHeight(doc) {
    // from http://stackoverflow.com/questions/1145850/get-height-of-entire-document-with-javascript
    doc = doc || document;
    var body = doc.body, html = doc.documentElement;
    var height = Math.max( body.scrollHeight, body.offsetHeight,
        html.clientHeight, html.scrollHeight, html.offsetHeight );
    return height;
}



/**
 * Add css to the iframe window head.
 *
 * Currently used only when building page to display PROG list in the iframe.
 *
 * @param {string} ifrm_w - Iframe window object
 * @param {string} css - CSS rules
 * @return void
 */
function ifrm_page_css(ifrm_w, css) {

    var head = ifrm_w.document.head || ifrm_w.document.getElementsByTagName('head')[0];
    var style = ifrm_w.document.createElement('style');

    style.type = 'text/css';
    if (style.styleSheet){
        style.styleSheet.cssText = css;
    } else {
        style.appendChild(ifrm_w.document.createTextNode(css));
    }

    head.appendChild(style);
}












// MDFSINGLE



/**
 * Hide iframe
 *
 * @param {object} ifrm - IFRAME object.
 * @return void
 */
function SNGifrm_hide(ifrm) {

    // When the function is called from ifrm_result(), ifrm object is omitted, thus we find it by ID instead
    if (!ifrm) {
        ifrm = document.getElementById('ifrmtunel');
    }

    ifrm.style.visibility = 'hidden';
    ifrm.style.display = 'none';
}



/**
 * Reset iframe label
 */
function SNGifrm_reset(id, label_bold, label_normal) {

    if (id==undefined) id = 0;
    if (label_bold==undefined) label_bold = '';
    if (label_normal==undefined) label_normal = '';

    document.getElementById(ifrm_id).value				= id; // ifrm_id has to be pre-defined on global level
    document.getElementById('label_bold').innerHTML 	= label_bold;
    document.getElementById('label_normal').innerHTML 	= label_normal;
}

















// MDFMULTI


/**
 * Update iframe label and shadow submitter
 *
 * Called from ifrm_result(), to update the labels and the shadow submitter with new values, but also called from
 * MLTifrm_reset(), with *empty* values, to reset the labels and the shadow.
 *
 * @param {object} tr_form - TR_FORM object, which is the container of labels and shadow
 * @param {Array} s - Result array
 0 - Value, i.e. ProgID or NativeID
 1 - Whether it is ProgID (1) or NativeID (0)
 2 - Text for subcaption label (normal)
 3 - Text for caption label (bold)
 * @return void
 */
function MLTifrm_result_updater(tr_form, s){

    // Find and update labels
    var divz = tr_form.getElementsByTagName('div');
    for (var i = 0; i < divz.length; i++)	{

        if (divz[i].id == 'ifrm_label') {

            var spanz = divz[i].getElementsByTagName('span');
            spanz[0].innerHTML = s[3];
            spanz[1].innerHTML = s[2];

            if (s[1]==14) { // linker linetyp
                spanz[0].innerHTML = '<span class="glyphicon glyphicon-link"></span> ' + spanz[0].innerHTML;
            }
        }
    }

    // Update shadow submitter (input hidden field)
    var h_name = (s[1]==1) ? 'ProgID' : 'NativeID';
    setHider(tr_form, h_name, s[0]);
}


/**
 * Reset for iframe label and shadow submitter. Sets everything to null values.
 *
 * @param {object} btn - Anchor object, i.e. "this" object for the reset button
 * @param {bool} is_prog - Whether this is prog (this is important because the name of the shadow control
 differs for prog and for other linetypes)
 * @return void
 */

function MLTifrm_reset(btn, is_prog){

    var tr_form = btn.parentNode.parentNode.parentNode.parentNode;

    var s = [0, is_prog, '', ''];

    // call MLTifrm_result_updater() with *empty* values, to reset the labels and the shadow
    MLTifrm_result_updater(tr_form, s);
}


/**
 * Remove specified iframe, with tr container.
 *
 * @param {object} parentTB - Parent TBODY object
 * @return void
 */
function MLTifrm_remove(parentTB){
    parentTB.removeChild(parentTB.childNodes[1]);
}


/**
 * Remove all iframes (except the clone), with their container TRs. Used in order to clear all open iframes.
 */
function MLTifrm_remove_all() {

    var parentTB;

    var clone_id = 'ifrmctrl';  // id of the iframe CLONE wrap table

    var ifrmz = document.getElementsByTagName('iframe');

    if (ifrmz.length > 1) { // if there is only one iframe, then it is the CLONE, so skip checking

        for (var i = 0; i < ifrmz.length; i++) {

            parentTB = ifrmz[i].parentNode.parentNode.parentNode; // Parent TBODY

            if (parentTB.parentNode.id != clone_id) {
                MLTifrm_remove(parentTB);
            }
        }
    }
}











// CVR


/**
 * Remove all iframes (except the clone), with their container DIV. Used in order to clear all open iframes.
 */
function CVRifrm_remove_all() {

    var wraper;

    var clone_id = 'ifrmctrl';  // id of the iframe CLONE wrap div

    var ifrmz = document.getElementsByTagName('iframe');

    if (ifrmz.length > 1) { // if there is only one iframe, then it is the CLONE, so skip checking

        for (var i = 0; i < ifrmz.length; i++) {

            wraper = ifrmz[i].parentNode.parentNode;

            if (wraper.parentNode.id != clone_id) {
                wraper.parentNode.removeChild(wraper);
                return;
            }
        }
    }
}


