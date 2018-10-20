<?php
/**
 * This is common script which builds and displays HTML following contents area, for every page.
 * Use $footer_cfg array to pass configuration from page to this script:
 * - 'foot_hide'    bool    do not display footer div
 * - 'js_lines'     array   add js lines to be added within <script> tag
 * - 'js'           array   add specified js file(s) to footer
 * - 'modal'        array   print bs-modal html (alerter, alphconv, printer)
 */



if (isset($footer_cfg['modal']) && is_array($footer_cfg['modal'])) {

    foreach($footer_cfg['modal'] as $v) {

        if (is_array($v)) {
            $opt = (isset($v[1]) && is_array($v[1])) ? $v[1] : null;
            $v = $v[0];
        } else {
            $opt = null;
        }

        if ($v=='deleter' && empty($opt['onshow_js']) && isset($deleter_argz)) {
            $opt = $deleter_argz;
        }

        if (in_array($v, ['alerter', 'alphconv', 'printer', 'deleter'])) {
            modal_output('modal', $v, $opt);
        }

        if ($v=='alphconv') {
            $footer_cfg['js'][] = 'alphconv.js';
        }

        if (!empty($opt['onshow_js'])) {

            if (empty($opt['name_prefix'])) {
                $opt['name_prefix'] = $v;
            }

            $footer_cfg['js_lines'][] =
                '$(\'#'.$opt['name_prefix'].'_ONSHOW_JS_Modal\').on(\'show.bs.modal\', '.$opt['onshow_js'].');';
        }
    }
}






?>

    <!-- /container -->
    </div>
<!-- /content -->
</div>


<?php
if (empty($footer_cfg['foot_hide'])) :
?>
<script>
    footer_pos();
    window.addEventListener("resize", footer_pos);
</script>
<div id="footer">
    <div class="container">
        <h5 class="pull-left"><span class="glyphicon glyphicon-ok"></span> <?=$tx['NAV'][1].' '.$cfg['app_version']?></h5>
        <?php if (UZID==UZID_ALFA): echo '<p class="pull-right script_time">(t_exec = '.t_exec().')</p>'; endif;?>
    </div>
</div>
<?php endif;?>


<script src="/_pkg/bootstrap/js/bootstrap.min.js"></script>

<?php

if (isset($footer_cfg['js']) && is_array($footer_cfg['js'])) {
    foreach($footer_cfg['js'] as $v) {
        echo '<script src="'.(($v[0]=='/') ? '' : '/_js/' ).$v.'"></script>'.PHP_EOL;
    }
}

if (isset($footer_cfg['js_lines']) && is_array($footer_cfg['js_lines'])) {
    echo PHP_EOL.'<script>'.PHP_EOL;
    echo implode(PHP_EOL, $footer_cfg['js_lines']).PHP_EOL;
    echo '</script>'.PHP_EOL;
}

?>


</body>
</html>
<?php
if (isset($GLOBALS["db"])) mysqli_close($GLOBALS["db"]);

