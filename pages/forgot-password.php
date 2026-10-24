<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/validate-user-session.php";
require_once __DIR__ . "/../includes/password-reset.php";

if (isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

[$email, $message, $messageType, $completed] = processPasswordReset($pdo, "user");

function resetEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน | ข่าวสารภายในประเทศ</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= (int) filemtime(__DIR__ . "/../assets/css/style.css") ?>">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card">
            <div class="auth-logo">ข่าวสาร<span>ภายในประเทศ</span></div>
            <h1 class="auth-title">ตั้งรหัสผ่านใหม่</h1>
            <p class="auth-description">กรอกอีเมลที่ใช้สมัคร แล้วตั้งรหัสผ่านใหม่</p>
            <p class="auth-reset-note">ระบบตรวจสอบจากอีเมลที่บันทึกไว้เท่านั้น และไม่ได้ส่งรหัสยืนยัน ผู้ที่ทราบอีเมลนี้อาจเปลี่ยนรหัสผ่านได้</p>

            <?php if ($message !== ""): ?>
                <div class="auth-message<?= $messageType === "error" ? " auth-message-error" : ($messageType === "success" ? " auth-message-success" : "") ?>" role="<?= $messageType === "error" ? "alert" : "status" ?>"><?= resetEscape($message) ?></div>
            <?php endif; ?>

            <?php if (!$completed): ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= resetEscape($_SESSION["password_reset_csrf"]) ?>">
                    <label for="email">อีเมลที่ใช้สมัครสมาชิก</label>
                    <input id="email" type="email" name="email" value="<?= resetEscape($email) ?>" autocomplete="email" required>

                    <label for="password">รหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)</label>
                    <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required>

                    <label for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
                    <input id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>

                    <button type="submit" class="auth-button">เปลี่ยนรหัสผ่าน</button>
                </form>
            <?php endif; ?>

            <div class="auth-link"><a href="login.php">กลับหน้าเข้าสู่ระบบ</a></div>
        </section>
    </main>
</body>
</html>
