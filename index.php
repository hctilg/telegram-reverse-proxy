<?php

$ch = curl_init("https://api.telegram.org" . $_SERVER['REQUEST_URI']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

$request_headers = [];
$content_type = "";

foreach (getallheaders() as $key => $value) {
  if (strtolower($key) === "host") continue;
  if (strtolower($key) === "content-type") $content_type = strtolower($value);
  $request_headers[] = "$key: $value";
}

if (
  in_array($_SERVER["REQUEST_METHOD"], ["POST", "PUT", "PATCH", "DELETE"])
) {
  if (strpos($content_type, "multipart/form-data") !== false) {
    $post_fields = $_POST;

    foreach ($_FILES as $name => $file) {
      if ($file["error"] === UPLOAD_ERR_OK) {
        $post_fields[$name] = new CURLFile(
          $file["tmp_name"],
          $file["type"],
          $file["name"]
        );
      }
    }

    $request_headers = array_filter(
      $request_headers,
      fn($header) => stripos($header, "Content-Type:") !== 0
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
  } elseif (strpos($content_type, "application/json") !== false) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
  } elseif (
    strpos($content_type, "application/x-www-form-urlencoded") !== false
  ) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
  } else {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
  }
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
$response = curl_exec($ch);

if ($response === false) {
  http_response_code(500);
  echo curl_error($ch);
  curl_close($ch);
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
