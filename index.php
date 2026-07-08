<?php

$ch = curl_init("https://api.telegram.org" . $_SERVER['REQUEST_URI']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$method = $_SERVER['REQUEST_METHOD'];
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

$headers = [];
foreach (getallheaders() as $key => $value) {
  if (strtolower($key) === 'host') continue;
  $headers[] = "$key: $value";
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

if (in_array($method, ['POST', 'PUT', 'PATCH']))
  curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));

$response = curl_exec($ch);

if ($response === false) {
  http_response_code(500);
  echo curl_error($ch);
  exit;
}

$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$response_headers = substr($response, 0, $header_size);
$response_body = substr($response, $header_size);

http_response_code(curl_getinfo($ch, CURLINFO_HTTP_CODE));

foreach (explode("\r\n", $response_headers) as $header) {
  if (
    stripos($header, "Transfer-Encoding:") === 0 ||
    stripos($header, "Content-Length:") === 0 ||
    trim($header) === ""
  ) continue;
  header($header, false);
}

echo $response_body;

curl_close($ch);
