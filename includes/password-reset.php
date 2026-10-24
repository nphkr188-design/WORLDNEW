<?php

function processPasswordReset(PDO $pdo, string $requiredRole): array
{
    if (!isset($_SESSION["password_reset_csrf"])) {
        $_SESSION["password_reset_csrf"] = bin2hex(random_bytes(32));
    }

    $email = "";
    $message = "";
    $messageType = "info";
    $completed = false;

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return [$email, $message, $messageType, $completed];
    }

    $csrfToken = $_POST["csrf_token"] ?? "";
    if (
        !is_string($csrfToken)
        || !hash_equals($_SESSION["password_reset_csrf"], $csrfToken)
    ) {
        http_response_code(400);
        return [$email, "คำขอไม่ถูกต้อง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง", "error", $completed];
    }

    $email = strtolower(trim((string) ($_POST["email"] ?? "")));
    $password = (string) ($_POST["password"] ?? "");
    $confirmPassword = (string) ($_POST["confirm_password"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [$email, "ไม่พบอีเมลนี้ในระบบ กรุณาตรวจสอบอีเมลที่ใช้สมัคร", "error", $completed];
    }

    if (strlen($password) < 8) {
        return [$email, "รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร", "error", $completed];
    }

    if ($password !== $confirmPassword) {
        return [$email, "รหัสผ่านใหม่และการยืนยันไม่ตรงกัน", "error", $completed];
    }

    $roleCondition = $requiredRole === "admin"
        ? "role = 'admin'"
        : "role = 'user'";
    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = :email
            AND {$roleCondition}
        LIMIT 1
    ");
    $stmt->execute([":email" => $email]);
    $userId = $stmt->fetchColumn();

    if ($userId === false) {
        return [$email, "ไม่พบอีเมลนี้ในระบบ กรุณาตรวจสอบอีเมลที่ใช้สมัคร", "error", $completed];
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET password = :password
        WHERE id = :id
            AND email = :email
            AND {$roleCondition}
    ");
    $stmt->execute([
        ":password" => password_hash($password, PASSWORD_DEFAULT),
        ":id" => (int) $userId,
        ":email" => $email,
    ]);

    if ($stmt->rowCount() !== 1) {
        return [$email, "เปลี่ยนรหัสผ่านไม่สำเร็จ กรุณาลองอีกครั้ง", "error", $completed];
    }

    session_regenerate_id(true);
    unset($_SESSION["password_reset_csrf"]);

    return [
        $email,
        "เปลี่ยนรหัสผ่านสำเร็จแล้ว กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่",
        "success",
        true,
    ];
}
