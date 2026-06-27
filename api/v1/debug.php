<?php
echo "API Route Debug:<br>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'N/A') . "<br>";
?>
