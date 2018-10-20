
<script src="../_js/bs_multiselect/bootstrap-multiselect.js"></script>

<script>

$(document).ready(function() {

    $('#LineFilter').multiselect({

        inheritClass: true,

        buttonWidth: '110px',

        nonSelectedText: '<?=mb_strtoupper($tx[SCTN]['LBL']['filter'])?>',

        numberDisplayed: 1,

        nSelectedText: ' - <?=mb_strtoupper($tx[SCTN]['LBL']['filter'])?>',

        onChange: function(event) { // could use onDropdownHide instead..
            epg_line_filter();
        }

    });

    var sel = sessionStorage.getItem(g_line_filter_name);
    // We use global var for *name* of session var, so we could save session var separately for each case, i.e. epg/scnr
    // and viewtype..

    if (sel) {
        $('#LineFilter').multiselect('select', sel, true);
    }
});

</script>