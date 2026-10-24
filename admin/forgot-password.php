<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    isset($_SESSION["user_id"])
    && ($_SESSION["user_role"] ?? null) === "admin"
) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/password-reset.php";

[$email, $message, $messageType, $completed] = processPasswordReset($pdo, "admin");

function adminResetEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่านแอดมิน | ข่าวสารภายในประเทศ</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= (int) filemtime(__DIR__ . "/../assets/css/style.css") ?>">
    <link rel="stylesheet" href="admin-transition.css?v=<?= (int) filemtime(__DIR__ . "/admin-transition.css") ?>">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card">
            <div class="auth-logo">ข่าวสาร<span>ภายในประเทศ</span></div>
            <div style="text-align:center;font-size:36px;margin:12px 0" aria-hidden="true">🔐</div>
            <h1 class="auth-title">ตั้งรหัสผ่านแอดมินใหม่</h1>
            <p class="auth-description">กรอกอีเมลแอดมินที่ใช้สมัคร แล้วตั้งรหัสผ่านใหม่</p>
            <p class="auth-reset-note">ระบบตรวจสอบจากอีเมลที่บันทึกไว้เท่านั้น และไม่ได้ส่งรหัสยืนยัน ผู้ที่ทราบอีเมลนี้อาจเปลี่ยนรหัสผ่านผู้ดูแลระบบได้</p>

            <?php if ($message !== ""): ?>
                <div class="auth-message<?= $messageType === "error" ? " auth-message-error" : ($messageType === "success" ? " auth-message-success" : "") ?>" role="<?= $messageType === "error" ? "alert" : "status" ?>"><?= adminResetEscape($message) ?></div>
            <?php endif; ?>

            <?php if (!$completed): ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= adminResetEscape($_SESSION["password_reset_csrf"]) ?>">
                    <label for="email">อีเมลแอดมินที่ใช้สมัคร</label>
                    <input id="email" type="email" name="email" value="<?= adminResetEscape($email) ?>" autocomplete="email" required>

                    <label for="password">รหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)</label>
                    <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required>

                    <label for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
                    <input id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>

                    <button type="submit" class="auth-button">เปลี่ยนรหัสผ่าน</button>
                </form>
            <?php endif; ?>

            <div class="auth-link"><a href="login.php">กลับหน้าเข้าสู่ระบบแอดมิน</a></div>
        </section>
    </main>
    <script src="admin-transition.js?v=<?= (int) filemtime(__DIR__ . "/admin-transition.js") ?>" defer></script>
</body>
</html>
