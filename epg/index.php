<?php

$www_root = ((@$_SERVER["HTTPS"]=='on') ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'];

header('Location: '.$www_root.'/index.php');
exit;
