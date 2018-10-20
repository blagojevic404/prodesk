
/* JS for LIST page */




/**
 * Set value od the element with specified ID and then submit form. This is used in LIST pages, to make links send $_POST
 * variables (they normally send $_GET variables).
 *
 * @param {string} id - ID of the target element
 * @param {string} n - New value for the target element
 * @return void
 */
function tunel(id, n){

    document.getElementById(id).value=n;
    document.f.submit();
}





