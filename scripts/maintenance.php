<?php
header('HTTP/1.1 503 Service Temporarily Unavailable',true,503);
header('Status: 503 Service Temporarily Unavailable');
header('Retry-After: 172800');
$noBar = true;
include Blox::info('cms','dir')."/includes/display.php";
