<?php

require_once __DIR__ . "/require-admin.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "method_not_allowed"]);
    exit;
}

$sessionToken = $_SESSION["inline_image_csrf"] ?? "";
$token = $_POST["csrf_token"] ?? "";
if (
    !is_string($sessionToken)
    || $sessionToken === ""
    || !is_string($token)
    || !hash_equals($sessionToken, $token)
) {
    http_response_code(400);
    echo json_encode(["error" => "invalid_request"]);
    exit;
}

if (
    !isset($_FILES["inline_image"])
    || $_FILES["inline_image"]["error"] !== UPLOAD_ERR_OK
) {
    http_response_code(400);
    echo json_encode(["error" => "upload_failed"]);
    exit;
}

$file = $_FILES["inline_image"];
if ($file["size"] <= 0 || $file["size"] > 5 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(["error" => "invalid_file_size"]);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
if ($finfo === false) {
    http_response_code(500);
    echo json_encode(["error" => "image_validation_unavailable"]);
    exit;
}

$mimeType = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo);

$allowedTypes = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp",
];

if (
    !isset($allowedTypes[$mimeType])
    || @getimagesize($file["tmp_name"]) === false
) {
    http_response_code(415);
    echo json_encode(["error" => "unsupported_image_type"]);
    exit;
}

$uploadDirectory = __DIR__ . "/../assets/images";
if (
    !is_dir($uploadDirectory)
    && !mkdir($uploadDirectory, 0755, true)
    && !is_dir($uploadDirectory)
) {
    http_response_code(500);
    echo json_encode(["error" => "upload_directory_unavailable"]);
    exit;
}

$fileName = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];
$destination = $uploadDirectory . "/" . $fileName;
if (!move_uploaded_file($file["tmp_name"], $destination)) {
    http_response_code(500);
    echo json_encode(["error" => "image_save_failed"]);
    exit;
}

echo json_encode(
    ["file" => $fileName],
    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
);
