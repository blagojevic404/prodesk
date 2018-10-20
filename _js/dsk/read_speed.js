


/**
 * Handle MODIFY for a readspeed item. Called by MDF button click.
 *
 * @param {int} id Item ID
 * @param {string} name Item name
 * @param {object} z Button object
 * @return {void}
 */
function rs_btnmdf_click(id, name, z) {

    document.getElementById('v_name').value = name; // set name textbox
    document.getElementById('v_id').value = id;     // set hidden field

    // Set all MDF buttons to *normal*, and then set this one to *active*

    var boxz = document.getElementsByClassName ('btnmdf disabled');

    for (var i = 0; i < boxz.length; i++) {
        boxz[i].className = 'btnmdf opcty3';
    }

    z.className = 'btnmdf disabled';
}



/**
 * Handle PHASE changing. Called by PHZ button click.
 *
 * @return {void}
 */
function rs_phz_change() {

    var now, msg, rs_s, rs_t, rs_v;


    if (rs_phz<2) {  // global
        rs_phz++;
    } else {
        rs_phz = 0;
    }

    var btn_phz = document.getElementById('v_phz');

    // Set button text
    btn_phz.innerHTML = rs_phzarr[rs_phz];  // global


    if (rs_phz==1) { // After FIRST click (START): start the TIMER.

        now = new Date();
        rs_t_start = now.getTime(); // global

        btn_phz.style.backgroundColor = btn_phz.style.borderColor = '#ec971f'; // PHZ button goes orange
    }


    if (rs_phz==2) { // After SECOND click (FINITO): do calculations, enable submit button, show INFO popover button


        btn_phz.style.backgroundColor = btn_phz.style.borderColor = '#d9534f'; // PHZ button goes red


        // Get text length

        var v_txt = document.getElementById('v_text').value;

        rs_s = js_atom_txtlen(v_txt);


        // Calculate the TIME

        now = new Date();
        var rs_t_finito = now.getTime();

        rs_t = (rs_t_finito - rs_t_start)/1000; // Convert milliseconds to seconds


        // Calculate the VELOCITY

        rs_v = rs_s/rs_t;
        rs_v = Math.round(rs_v*10)/10; // Round to one decimal point


        // Text for INFO popover
        var info = 's = ' + rs_s + ' (' + rs_txtarr[0] + ')<br>' +
                   't = ' + rs_t + 's (' + rs_txtarr[1] + ')<br>' +
                   'v = s/t<br><br>' +
                   '<b>v = ' + rs_v + '</b> (' + rs_txtarr[2] + ')';


        // Write velocity to V textbox and change its background color

        var v_val = document.getElementById('v_val');

        if (rs_v <= rs_max && rs_v >= rs_min) { // global

            v_val.value = rs_v;
            v_val.style.backgroundColor = '#ff0'; // yellow

        } else { // ERROR: V value not in range specified by MIN and MAX

            if (rs_v > rs_max) {
                v_val.value = rs_max;
                msg = 'Max = ' + rs_max;
            } else {
                v_val.value = rs_min;
                msg = 'Min = ' + rs_min;
            }

            alerter(msg);

            info += '<br><br><span style="color:red;font-weight:bold">' + msg + '</span>';
        }


        // Show INFO popover button

        document.getElementById('rs_info').style.display = 'block';
        document.getElementById('rs_info').setAttribute('data-content', info);


        // Enable SUBMIT button

        var submiter = document.getElementById('v_submit');
        submiter.className = submiter.className.substring(0, submiter.className.indexOf(' disabled'));

    }


    if (rs_phz==0) { // After THIRD click (CLEAR): reset

        btn_phz.style.backgroundColor = btn_phz.style.borderColor  = ''; // Reset PHZ button color

        document.getElementById('v_val').style.backgroundColor = ''; // Reset V textbox color and erase the value

        document.getElementById('v_val').value = '';

        document.getElementById('rs_info').style.display = ''; // Remove INFO popover button

        document.getElementById('v_submit').className += ' disabled';   // Disable SUBMIT button
    }

}
