<?php

/**
 * tgtinygate -  A tiny PHP gateway for receiving and forwarding Telegram bot webhooks to a private backend.  
 */

$botHookUrl = 'http://yourhost.com/billing/?module=claptrapbot&auth=changeme';
$connectTimeout = 5;
$timeout = 10;

/**
 * End of config section
 */

$rawBody = file_get_contents('php://input');
$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'POST';

$incomingHeaders = array();
if (function_exists('getallheaders')) {
    $incomingHeaders = getallheaders();
} else {
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) == 'HTTP_') {
            $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $incomingHeaders[$headerName] = $value;
        } elseif (in_array($key, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
            $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
            $incomingHeaders[$headerName] = $value;
        }
    }
}

$forwardHeaders = array();
foreach ($incomingHeaders as $hName => $hValue) {
    if (strtolower($hName) === 'host' or strtolower($hName) === 'content-length' or strtolower($hName)=='x-real-ip' or strtolower($hName)=='x-forwarded-for')  continue;
    $forwardHeaders[] = $hName . ': ' . $hValue;
}

$hasContentType = false;
foreach ($forwardHeaders as $fh) {
    if (stripos($fh, 'Content-Type:') === 0) {
        $hasContentType = true;
        break;
    }
}
if (!$hasContentType) $forwardHeaders[] = 'Content-Type: application/octet-stream';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $botHookUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);

if ($response === false) {
    $errorMsg = 'cURL error: ' . curl_error($ch);
    error_log($errorMsg);
    header('HTTP/1.1 502 Bad Gateway');
    header('Content-Type: text/plain; charset=utf-8');
    print($errorMsg);
    curl_close($ch);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$responseHeaderRaw = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$lines = preg_split("/\r\n|\n|\r/", trim($responseHeaderRaw));
$skipFirstLine = true;
foreach ($lines as $line) {
    if ($skipFirstLine) {
        $skipFirstLine = false;
        continue;
    }
    if ($line === '') continue;
    $pos = strpos($line, ':');
    if ($pos !== false) {
        $name = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if (in_array(strtolower($name), array('transfer-encoding', 'content-length', 'connection'))) continue;
        header($name . ': ' . $value, false);
    }
}

$reasonPhrases = array(
    200 => 'OK', 201 => 'Created', 202 => 'Accepted', 204 => 'No Content',
    301 => 'Moved Permanently', 302 => 'Found', 400 => 'Bad Request',
    401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found',
    405 => 'Method Not Allowed', 409 => 'Conflict', 410 => 'Gone',
    422 => 'Unprocessable Entity', 500 => 'Internal Server Error',
    502 => 'Bad Gateway', 503 => 'Service Unavailable'
);
$reason = isset($reasonPhrases[(int)$httpCode]) ? $reasonPhrases[(int)$httpCode] : '';
header(sprintf('HTTP/1.1 %d %s', (int)$httpCode, $reason));

print($responseBody);
exit;

