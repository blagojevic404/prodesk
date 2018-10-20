<?php

// Default settings (tweaked for usage in LST)

if (!isset($header_cfg['bs_daterange']['single'])) {
    $header_cfg['bs_daterange']['single'] = false;
}

if (!isset($header_cfg['bs_daterange']['name'])) {
    $header_cfg['bs_daterange']['name'] = 'TME';
}

if (!isset($header_cfg['bs_daterange']['submit'])) {
    $header_cfg['bs_daterange']['submit'] = true;
}


if (!empty($header_cfg['bs_daterange']['selector'])) {

    $header_cfg['bs_daterange']['name'] = [];

} else {

    if (!is_array($header_cfg['bs_daterange']['name'])) {
        $header_cfg['bs_daterange']['name'] = [$header_cfg['bs_daterange']['name']];
    }
}


?>


<script src="../_js/bs_daterange/moment.min.js"></script>
<script src="../_js/bs_daterange/daterangepicker.js"></script>

<script>

    function bs_dater_selector(selector) {

        var ctrlz = document.querySelectorAll(selector);

        for (var i = 0; i < ctrlz.length; i++) {
            $(ctrlz[i]).daterangepicker(g_bsdater_options);
        }
    }

</script>




<script>

    var g_bsdater_options =

    {
        format: 'YYYY/MM/DD',
        //showDropdowns: true, // Show year and month select boxes above calendars to jump to a specific month and year

        <?php if ($header_cfg['bs_daterange']['single']):?>
        singleDatePicker: true,
        <?php endif;?>


        <?php if (LNG==4): // if ENG?>

        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
            'Last 7 Days': [moment().subtract('days', 6), moment()],
            'Last 30 Days': [moment().subtract('days', 29), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
        },

        locale: {
            'firstDay': 1 // Set monday as the first day
        }

        <?php else: // if NOT ENG

        $months_short = months_short();

        $wdays_tiny = txarr('common.days', 'wdays_tiny');
        array_unshift($wdays_tiny, array_pop($wdays_tiny));

        $tx['range'] = txarr('common.days', 'range');

        ?>

        ranges: {
            '<?=$tx['range']['today']?>': [moment(), moment()],
            '<?=$tx['range']['yesterday']?>': [moment().subtract('days', 1), moment().subtract('days', 1)],
            '<?=$tx['range']['last7days']?>': [moment().subtract('days', 6), moment()],
            '<?=$tx['range']['last30days']?>': [moment().subtract('days', 29), moment()],
            '<?=$tx['range']['this_month']?>': [moment().startOf('month'), moment().endOf('month')],
            '<?=$tx['range']['last_month']?>': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
        },

        locale: {
            'applyLabel': '<?=$tx['range']['applyLabel']?>',
            'cancelLabel': '<?=$tx['range']['cancelLabel']?>',
            'fromLabel': '<?=$tx['range']['fromLabel']?>',
            'toLabel': '<?=$tx['range']['toLabel']?>',
            'customRangeLabel': '<?=mb_strtoupper($tx['range']['customRangeLabel'])?>',
            'daysOfWeek': ['<?=implode("', '", $wdays_tiny);?>'],
            'monthNames': ['<?=implode("', '", $months_short);?>'],
            'firstDay': 1 // Set monday as the first day
        }

        <?php endif;?>
    };



    <?php


    foreach ($header_cfg['bs_daterange']['name'] as $v) :
    ?>

    $(document).ready(function() {
        $('input[name="<?=$v?>"]').daterangepicker(

            // OPTIONS object
            g_bsdater_options,

            // CALLBACK (onchange) function
            function() {
                <?php
                if ($header_cfg['bs_daterange']['submit']) { // whether to submit on change..
                    echo 'document.f.submit();';
                }
                ?>
            }

        );
    });

    <?php
    endforeach;
    ?>

</script>


