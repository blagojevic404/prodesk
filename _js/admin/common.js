
/**
 * Wrap/NoWrap switch for PRE element which outputs log files
 */
function nowrap_switch() {

    var z = document.querySelector("pre.log");

    z.className = (z.className == "row log") ? "row log nowrap" : "row log";
}



/**
 * Scrambler submiter - reads the value of the input control and adds it to the submit href
 */
function scrambler(z, typ) {

    var wraper = z.parentNode.parentNode;
    var txt = wraper.querySelector("input");

    if (txt.value) {
        z.href = '?scrambler=' + typ + '&txt=' + txt.value;
    }
}

