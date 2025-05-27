<?php

/**
 * @file
 * Proxy for JavaScript cross-domain requests.
 *
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

$url = $_GET['url'];

// URL must be http.
if (!preg_match('/^http(s)?:\/\//', $url)) {
  die('Invalid URL requested ' . $url);
}
/*$whiteList = [
  'https://maps.googleapis.com/maps/api/place/textsearch/json',
];
$allowed = FALSE;
foreach ($whiteList as $allowedUrl) {
  if (strpos($url, $allowedUrl) === 0) {
    $allowed = TRUE;
    break;
  }
}
if (!$allowed) {
  die('URL not allowed');
}*/

if (strpos($url, "?") !== FALSE) {
  $url = $url . "&";
}
else {
  $url = $url . "?";
}

$found = FALSE;
foreach ($_GET as $key => $value) {
  // Do not copy the url param, only everything after it. Must include blanks
  // in this so that reports know when they get passed a blank param.
  if ($found) {
    $value = str_replace('\"', '"', $value);
    $value = urlencode($value);
    $url = "$url$key=$value&";
  }
  if ($key == "url") {
    $found = TRUE;
  }
}
$url = str_replace('\"', '"', $url);
$url = str_replace(' ', '%20', $url);
$session = curl_init($url);
// Set the POST options.
$httpHeader = [];
$postData = file_get_contents('php://input');
if (empty($postData)) {
  $postData = $_POST;
}
if (!empty($postData)) {
  curl_setopt($session, CURLOPT_POST, 1);
  curl_setopt($session, CURLOPT_POSTFIELDS, $postData);
  // Post contains a raw XML document?
  if (is_string($postData) && substr($postData, 0, 1) === '<') {
    $httpHeader[] = 'Content-Type: text/xml';
  }
}
if (count($httpHeader) > 0) {
  curl_setopt($session, CURLOPT_HTTPHEADER, $httpHeader);
}

curl_setopt($session, CURLOPT_HEADER, TRUE);
curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);


// Do the POST and then close the session.
$response = curl_exec($session);
if (curl_errno($session)) {
  echo "cUrl POST request failed. Please check cUrl is installed on the server.\n";
  echo 'Error number: ' . curl_errno($session) . "\n";
  echo "Server response ";
  echo $response;
}
else {
  $headers = curl_getinfo($session);

  if (strpos($headers['content_type'], '/') !== FALSE) {
    $arr = explode('/', $headers['content_type']);
    $fileType = array_pop($arr);
    if (strpos($fileType, ';') !== FALSE) {
      $arr = explode(';', $fileType);
      $fileType = $arr[0];
    }
    if ($fileType === 'comma-separated-values') {
      $fileType = 'csv';
    }
    // If a 'filename' is specified in the original URL params, then the client
    // is expecting the attachement to have this name.
    header('Content-Disposition: attachment; filename="' . (isset($_GET['filename']) ? $_GET['filename'] : 'download') . '.' . $fileType . '"');

    if ($fileType === 'csv') {
      // Output a byte order mark for proper CSV UTF-8.
      echo chr(239) . chr(187) . chr(191);
    }
  }
  if (array_key_exists('charset', $headers)) {
    $headers['content_type'] .= '; ' . $headers['charset'];
  }
  header('Content-type: ' . $headers['content_type']);
  // Last part of response is the actual data.
  $arr = explode("\r\n\r\n", $response);
  echo array_pop($arr);
}
curl_close($session);
