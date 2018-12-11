<?php
# Output of block (for Ajax, Rss etc.)
Request::set();
if (isset($_GET['src']))
    $regularId = Sql::sanitizeInteger($_GET['src']);
elseif (isset($_GET['block']))
    $regularId = Sql::sanitizeInteger($_GET['block']);
$blockHtm = Blox::getBlockHtm($regularId);
echo $blockHtm;
