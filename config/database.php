<?php

$host = getenv("WORLDNEWS_DB_HOST") ?: "localhost";
$port = getenv("WORLDNEWS_DB_PORT") ?: "3306";
$dbname = getenv("WORLDNEWS_DB_NAME") ?: "worldnews";
$username = getenv("WORLDNEWS_DB_USER") ?: "root";
$password = getenv("WORLDNEWS_DB_PASSWORD");

if ($password === false) {
    $password = "";
}

if (!extension_loaded("pdo_mysql")) {
    http_response_code(500);
    error_log("ข่าวสารภายในประเทศ requires the PHP pdo_mysql extension.");
    exit("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาเปิดใช้งานส่วนเสริม pdo_mysql ใน XAMPP");
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    error_log("ข่าวสารภายในประเทศ database connection failed: " . $exception->getMessage());
    exit(
        "เชื่อมต่อฐานข้อมูลไม่สำเร็จ กรุณาเปิด MySQL ใน XAMPP และตรวจสอบ "
        . "การตั้งค่าฐานข้อมูลใน config/database.php"
    );
}