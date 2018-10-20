
/**
 * Start TEST_INC script synchronisation: reads TEST_INC from DEV side
 *
 * @return {void}
 */
function testsync_init() {

    var url = 'https://' + g_dev_server + '/admin/tester_sync.php'; // SYNC script on DEV side

    ajaxer('GET', url, '', 'testsync_read_ajax_success', null);
}


/**
 * Called on successful reading TEST_INC from DEV side: writes contents to TEST_INC on PROD side, and reloads
 *
 * @param {string} arg_ajax Fetched contents of TEST_INC from DEV side
 * @return {void}
 */
function testsync_read_ajax_success(arg_ajax) {

    var url = 'tester_sync.php'; // SYNC script on PROD side

    var data = 'src=' + encodeURIComponent(arg_ajax);

    ajaxer('POST', url, data, 'reloader', null);
}

