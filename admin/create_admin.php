<?php

if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit;
}

require_once "../config/database.php";

$name = "ผู้ดูแลระบบข่าวสารภายในประเทศ";
$email = "admin@worldnews.com";
$password = "123456";

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$sql = "
    INSERT INTO users
    (
        name,
        email,
        password,
        role
    )
    VALUES
    (
        :name,
        :email,
        :password,
        'admin'
    )
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":name" => $name,
    ":email" => $email,
    ":password" => $hashedPassword
]);

echo "สร้าง Admin สำเร็จ";