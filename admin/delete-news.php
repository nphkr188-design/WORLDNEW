<?php

require_once __DIR__ . "/require-admin.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: news.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$token = $_POST["csrf_token"] ?? "";
$sessionToken = $_SESSION["delete_news_token"] ?? "";

if (
    !$id ||
    !is_string($token) ||
    !is_string($sessionToken) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $token)
) {
    header("Location: news.php?error=delete");
    exit;
}

require_once "../config/database.php";

$stmt = $pdo->prepare("
    SELECT image
    FROM news
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([":id" => $id]);
$news = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$news) {
    header("Location: news.php?error=delete");
    exit;
}

$stmt = $pdo->prepare("
    DELETE FROM news
    WHERE id = :id
");
$stmt->execute([":id" => $id]);

if (!empty($news["image"])) {
    $imageName = basename($news["image"]);
    $imagePath = "../assets/images/" . $imageName;

    if (is_file($imagePath)) {
        unlink($imagePath);
    }
}

unset($_SESSION["delete_news_token"]);

header("Location: news.php?deleted=1");
exit;