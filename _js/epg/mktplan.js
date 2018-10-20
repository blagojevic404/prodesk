



/**
 * Copy master control value to all normal controls of the same type
 *
 * @param {object} z - Ctrl object, i.e. "this" object for the input control
 */
function mdfplan_master(z) {

    var typ = z.getAttribute('data-ctrltyp');
    var ctrlz, i, index, chk;



    if (z.tagName=='LABEL') {

        index = z.querySelector('input').value; // Get the INDEX of the clicked button

        ctrlz = document.querySelectorAll('input[data-ctrltyp='+typ+']');

    } else {

        ctrlz = document.querySelectorAll(z.tagName+'[data-ctrltyp='+typ+']');
    }


    for (i = 0; i < ctrlz.length; i++) {

        chk = ctrlz[i].parentNode.parentNode.querySelector('input[type="checkbox"]').checked;

        if (!chk) continue;

        switch (z.tagName) {

            case 'INPUT':
                if (ctrlz[i]!=z) ctrlz[i].value = z.value;
                break;

            case 'SELECT':
                if (ctrlz[i]!=z) ctrlz[i].selectedIndex = z.selectedIndex;
                break;

            case 'LABEL':
                btngroup_setter(ctrlz[i], index);
                break;
        }
    }
}


/**
 * Prevent form submit when pressing ENTER key within BlockTermEPG control
 *
 * @param {object} z - Ctrl object, i.e. "this" object for the text input control
 */
function mdfplan_keyhandler(z) {

    var key = event.which;

    if (key==13) {

        z.blur();
        return false;
    }
}


/**
 * Delete a row in the mdfplan_multi table
 *
 * @param {object} z - Anchor object, i.e. "this" object for the button
 */
function mdfplan_del(z) {

    var tr = z.parentNode.parentNode;

    tr.outerHTML = '';
}


/**
 * Duplicates a row in the mdfplan_multi table
 *
 * @param {object} z - Anchor object, i.e. "this" object for the button
 */
function mdfplan_double(z) {

    var row1 = z.parentNode.parentNode;

    var doubler = row1.cloneNode(true);

    bs_dater_late_init(doubler);

    var row2 = row1.parentNode.insertBefore(doubler, row1.nextSibling);

    row2.querySelector('input[name="ID[]"]').value='';
    row2.querySelector('td.ordinal').innerHTML='';
    row2.querySelector('td.blc_label input').style.visibility = 'visible';
    row2.querySelector('td.dateepg input').required = true;
    row2.querySelector('td.termepg input').required = true;

    // cbo values hold on to *initial* (instead *current*) values, for some reason.. so we copy current values this way.
    row2.querySelector('select[name="BlockPos[]"]').selectedIndex =
        row1.querySelector('select[name="BlockPos[]"]').selectedIndex;
    row2.querySelector('select[name="BlockProgID[]"]').selectedIndex =
        row1.querySelector('select[name="BlockProgID[]"]').selectedIndex;
}


/**
 * Add new row in the mdfplan_multi table
 *
 * @param {object} z - Anchor object, i.e. "this" object for the button
 */
function mdfplan_add(z) {

    var clone_tmp = document.getElementById('clone[0]').querySelector('tr');
    var clone = clone_tmp.cloneNode(true);

    bs_dater_late_init(clone);
    clone.querySelector('td.dateepg input').required = true;
    clone.querySelector('td.termepg input').required = true;

    var tr = z.parentNode.parentNode;

    tr.parentNode.insertBefore(clone, tr);
}




/**
 * Initialize bs_dater control in new row
 *
 * @param {object} wraper - Wraper object, i.e. new row
 */
function bs_dater_late_init(wraper) {

    var dater = wraper.querySelector('input[name="DateEPG[]"]');
    $(dater).daterangepicker(g_bsdater_options);
}




/**
 * MKTplan: mdf-single modal
 */
function mktplan_modal_poster_onshow(event) {

    var modal = $(this);
    var button = $(event.relatedTarget);

    var modal_dom = document.getElementById('mdfplan_ONSHOW_JS_Modal');

    var ctrl_position = modal_dom.querySelector('#Position');
    var ctrl_blockpos = modal_dom.querySelector('#BlockPos');
    var typ;

    if (button.length) { // Modal was opened by clicking on the button


        if (!button.data('vary_dateepg')) {

            typ = 'new-item'; // NEW ITEM (called from spice_details.php; mktplanitem new)

        } else if (button.data('vary_id').toString().length==17){ // (mktplancode is 17 chars long)

            typ = 'mdf-all'; // MDF ALL in block (called from epg.php; mktplanitem mdf-all)

        } else {

            typ = 'mdf-item'; // MDF ITEM (called from epg.php; mktplanitem mdf)
        }


        if (typ=='mdf-all') { // Change background color for MDF-ALL modal
            modal_dom.querySelector('div.modal-content').classList.add('modal_mdf_all');
        } else {
            modal_dom.querySelector('div.modal-content').classList.remove('modal_mdf_all');
        }


        if (typ=='new-item' || typ=='mdf-item') {
            modal.find('#div_switcher_mdfall').hide(); // Hide controls which are used only by mdf-all
        }

        if (typ=='new-item' || typ=='mdf-all') {
            modal.find('#new_window').hide();
        }

        if (typ=='mdf-all' || typ=='mdf-item') {

            // Pre-set controls which are COMMON to both mdf-all and mdf-item

            modal.find('#MKTPLAN_MDFID').val(button.data('vary_id')); // for mdf-all it actually holds mktplancode
            modal.find('#DateEPG').val(button.data('vary_dateepg'));

            modal.find('#BlockTermEPG').val(button.data('vary_blocktermepg'));
            btngroup_setter(ctrl_blockpos, button.data('vary_blockpos'));
            modal.find('#BlockProgID').val(button.data('vary_blockprogid'));

            modal.find('#BLC_Label_new').hide();
        }

        if (typ=='new-item') {

            modal.find('#BLC_Label').show();

            // Reset controls to default (zero) values, because they keep values when modal is opened and then canceled
            // (either MDF or NEW modal could have been previously opened and then canceled)
            modal.find('#MKTPLAN_MDFID').val(0);
            modal.find('#DateEPG').val('');
            modal.find('#BlockTermEPG').val('');
            btngroup_setter(ctrl_blockpos, 0);
            modal.find('#BlockProgID').val(0);
            modal.find('#BLC_Label_new').val('');
            btngroup_setter(ctrl_position, g_mktplan_zero_pos);
            modal.find('#Note').val('');

        } else if (typ=='mdf-all') {

            // Hide the controls which are not used in mdf-all
            modal.find('#div_switcher_item').hide();

            // Show and pre-set specific mdf-all controls
            modal.find('#div_switcher_mdfall').show();
            modal.find('#BLC_Label').val(button.data('vary_block_label'));
            modal.find('#BLC_Wrapclips').val(button.data('vary_block_wrapclips'));

        } else if (typ=='mdf-item') {

            modal.find('#new_window').show();
            document.getElementById('new_window').href = 'mktplan_modify_single.php?source_id=' + button.data('vary_id')
                + '&case=item';

            // Show and pre-set specific mdf-item controls (because they might previously been hidden by a canceled mdf-all)
            modal.find('#div_switcher_item').show();
            btngroup_setter(ctrl_position, button.data('vary_position'));
            modal.find('#Note').val(button.data('vary_note'));
        }
/*
    } else { // DISCONTINUED: Open MDF modal for specified mktplanitem (onload trigger)


        var btnz = document.querySelectorAll('td.td_listing_ctrl a');

        for (var i = 0; i < btnz.length; i++) {

            if (btnz[i].getAttribute('data-vary_id')==g_mktplanitem) {

                var btn = btnz[i];

                modal.find('#MKTPLAN_MDFID').val(btn.getAttribute('data-vary_id'));
                modal.find('#DateEPG').val(btn.getAttribute('data-vary_dateepg'));
                modal.find('#BlockDSC_TermEPG').val(btn.getAttribute('data-vary_blocktermepg'));
                modal.find('#BlockDSC_Title').val(btn.getAttribute('data-vary_block_title'));
                btngroup_setter(ctrl_position, btn.getAttribute('data-vary_position'));

                modal.find('#Note').val(btn.getAttribute('data-vary_note'));
            }
        }*/
    }
}





/**
 * MKTplan: Link MKT SIBLINGS, i.e. link mktplan with mktepg
 */
function mkt_sibling_modal_poster_onshow(event) {

    var modal = $(this);
    var button = $(event.relatedTarget);

    modal.find('#mktplan_link_code').val(button.data('vary_id'));
}



/**
 * MKTsync: When single submit button is clicked, change some submit data
 */
function mkt_sync_submit_single(blockcode) {

    document.querySelector('input[name=mkt_sync_code]').value = blockcode;
    document.querySelector('form').action += '#tr' + blockcode; // We want to scroll down to same tr after submit/rcv
}



/**
 * Call mktplan search function when ENTER is pressed in search textbox.
 *
 * @param {object} e - keypress event
 * @return void
 */
function mktplan_search_submit13(e) {

    if(e.keyCode === 13){
        mktplan_search();
    }
}


/**
 * Mktplan search function
 *
 * @param {String} typ - (null, 'reset')
 * @return void
 */
function mktplan_search(typ) {

    var i, rowz, span, cpt, bingo, cnt=0;


    // Reset
    if (typ=='reset') {
        document.getElementById('mktplan_search').value = '';
    }


    var txt = document.getElementById('mktplan_search').value.trim().toLowerCase();

    if (!txt) {

        // Show all

        rowz = document.querySelectorAll('table.mktplan tr');

        for (i = 0; i < rowz.length; i++) {
            rowz[i].style.display = 'table-row';
        }

        document.getElementById('mktplan_search_reset').style.visibility = 'hidden';
        document.getElementById('mktplan_search_result').style.visibility = 'hidden';

        mktplan_search_blockcpt_sw(0);

        return;

    } else {

        document.getElementById('mktplan_search_reset').style.visibility = 'visible';
        document.getElementById('mktplan_search_result').style.visibility = 'visible';

        mktplan_search_blockcpt_sw(1);
    }


    // Hide all

    rowz = document.querySelectorAll('table.mktplan tr');

    for (i = 0; i < rowz.length; i++) {
        rowz[i].style.display = 'none';
    }


    // Show bingoz

    for (i = 0; i < rowz.length; i++) {

        span = rowz[i].querySelector('td.cpt a.cpt');

        if (span) {

            cpt = span.innerHTML.toLowerCase();

            bingo = cpt.search(txt);

            if (bingo!=-1) {
                rowz[i].style.display = 'table-row';
                cnt++;
            }
        }
    }

    document.getElementById('mktplan_search_result').innerHTML = cnt.toString();
}


/**
 * Mktplan search helper: switch on/off block-captions
 */
function mktplan_search_blockcpt_sw(sw) {

    var spanz, i;

    spanz = document.querySelectorAll('span.mktplan_search_blockcpt');

    for (i = 0; i < spanz.length; i++) {
        spanz[i].style.display = (sw) ? 'inline' : 'none';
    }
}





/**
 * Mktplan epg-sync switch: switch on/off del or ins button in epg-sync field
 */
function mktplan_epgsync_sw(z) {

    z.classList.toggle('on');

    document.getElementById('mkt_sync_epg_submit').style.display = 'inline';
}


/**
 * Mktplan epg-sync submit: submit epg-sync field
 */
function mktplan_epgsync_submit(z) {

    link_disarm(z); // prevent double-submit

    var del_arr=[], ins_arr=[], i;

    var del_nodes = document.querySelectorAll('div#mkt_sync_epg_field a.epgsync_del.on');
    var ins_nodes = document.querySelectorAll('div#mkt_sync_epg_field a.epgsync_ins.on');

    for (i = 0; i < del_nodes.length; i++) {
        del_arr.push(del_nodes[i].parentNode.id.substring(2));
    }

    for (i = 0; i < ins_nodes.length; i++) {
        ins_arr.push(ins_nodes[i].parentNode.id.substring(2));
    }

    if (del_arr.length) {
        document.querySelector('input[name=mkt_sync_delz]').value = del_arr.join();
    }

    if (ins_arr.length) {
        document.querySelector('input[name=mkt_sync_insz]').value = ins_arr.join();
    }

    if (del_arr.length || ins_arr.length) {
        document.querySelector('form').submit();
    }
}



/**
 * Mktplan hourly switch: show/hide either mktplan or mkt_hourly table
 */
function mkt_hourly_sw(z) {

    z.classList.toggle('on');

    document.querySelector('table.mktplan').classList.toggle('hidden');
    document.querySelector('table.mkt_hourly').classList.toggle('hidden');
}
