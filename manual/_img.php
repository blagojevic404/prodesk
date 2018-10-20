<?php
/**
 * Allows us to print image that is outside of the public folder
 */

$path = '../_local/img/'.$_GET['file'];

$mime_type = mime_content_type($path);

header('Content-Type: '.$mime_type);

readfile($path);
