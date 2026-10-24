<?php

require_once __DIR__ . "/require-admin.php";

header("Content-Type: application/json; charset=UTF-8");

function videoUploadError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(
        ["message" => $message],
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    videoUploadError(405, "ไม่อนุญาตให้ส่งคำขอแบบนี้");
}

$sessionToken = $_SESSION["inline_image_csrf"] ?? "";
$token = $_POST["csrf_token"] ?? "";
if (
    !is_string($sessionToken)
    || $sessionToken === ""
    || !is_string($token)
    || !hash_equals($sessionToken, $token)
) {
    videoUploadError(400, "คำขอไม่ถูกต้อง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง");
}

if (
    !isset($_FILES["inline_video"])
    || !is_array($_FILES["inline_video"])
    || $_FILES["inline_video"]["error"] !== UPLOAD_ERR_OK
) {
    $uploadError = $_FILES["inline_video"]["error"] ?? UPLOAD_ERR_NO_FILE;
    $message = $uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
        ? "วิดีโอมีขนาดใหญ่เกินกำหนดของเซิร์ฟเวอร์"
        : "อัปโหลดวิดีโอไม่สำเร็จ กรุณาลองใหม่";
    videoUploadError(400, $message);
}

$file = $_FILES["inline_video"];
if ($file["size"] <= 0 || $file["size"] > 50 * 1024 * 1024) {
    videoUploadError(413, "วิดีโอต้องมีขนาดไม่เกิน 50 MB");
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
if ($finfo === false) {
    videoUploadError(500, "ระบบตรวจสอบชนิดวิดีโอไม่พร้อมใช้งาน");
}

$mimeType = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo);

$allowedTypes = [
    "video/mp4" => "mp4",
    "video/webm" => "webm",
    "video/ogg" => "ogv",
];

if (!isset($allowedTypes[$mimeType])) {
    videoUploadError(415, "รองรับเฉพาะวิดีโอ MP4, WebM และ OGG");
}

$uploadDirectory = __DIR__ . "/../assets/videos";
if (
    !is_dir($uploadDirectory)
    && !mkdir($uploadDirectory, 0755, true)
    && !is_dir($uploadDirectory)
) {
    videoUploadError(500, "ไม่สามารถเตรียมโฟลเดอร์จัดเก็บวิดีโอได้");
}

$fileName = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];
$destination = $uploadDirectory . "/" . $fileName;
if (!move_uploaded_file($file["tmp_name"], $destination)) {
    videoUploadError(500, "บันทึกวิดีโอไม่สำเร็จ");
}

echo json_encode(
    ["file" => $fileName],
    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
);
