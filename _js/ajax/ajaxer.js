
/**
 * Opens php script through ajax and then sends result to specified js function on success.
 *
 * @param {string} typ Type: (POST, GET). Use GET for SELECT, use POST for MDF!
 * @param {string} url Url to php page
 * @param {string} data Data for submit
 * @param {string} success_fn Name of the js function to call on success.
 * @param {mixed} arg_init Argument to pass along with the js function on success. (Optional)
 *
 * @return {void}
 */
function ajaxer(typ, url, data, success_fn, arg_init) {

    if (typ!='POST') {
        typ = 'GET';
    }

    if (typ=='GET') {

        url += '?' + data; // GET sends data via url

        // To avoid caching, we add current timestamp as an argument to url
        var t = new Date().getTime();
        url += '&' + t;
    }

    var ajax = new XMLHttpRequest;
    ajax.onreadystatechange = function() {
        if (ajax.readyState==4 && ajax.status==200) {
            var arg_ajax = ajax.responseText;
            if (arg_ajax && success_fn) {               // Note: success fn will not trigger if response is null/false/zero
                window[success_fn](arg_ajax, arg_init);
            }
        }
    };

    ajax.open(typ, url, true);

    if (typ=='POST') {

        ajax.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        ajax.send(data);

    } else {
        ajax.send();
    }
}








/**
 * Disables button while waiting for ajax. Called together with ajax function.
 *
 * @param {object} z Button object
 * @return {void}
 */

function ajax_btn_disable(z) {

    // We display this for the case ajax gets slow or interrupts
    z.innerHTML = '<span class="glyphicon glyphicon-hourglass ready ready_not"></span>';
    z.onclick = '';
}
