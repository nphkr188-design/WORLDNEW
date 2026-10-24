<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";
require_once __DIR__ . "/../includes/validate-user-session.php";
require_once __DIR__ . "/../includes/categories.php";

$message = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $name === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $message = "กรุณากรอกข้อมูลให้ครบทุกช่อง";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "รูปแบบอีเมลไม่ถูกต้อง";

    } elseif (strlen($password) < 6) {

        $message = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";

    } elseif ($password !== $confirm_password) {

        $message = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";

    } else {

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ":email" => $email
        ]);

        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {

            $message = "อีเมลนี้ถูกใช้งานแล้ว";

        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
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
                    'user'
                )
            ");

            try {
                $stmt->execute([
                    ":name" => $name,
                    ":email" => $email,
                    ":password" => $hashedPassword
                ]);

                $success = "สมัครสมาชิกสำเร็จ สามารถเข้าสู่ระบบได้แล้ว";
            } catch (PDOException $exception) {
                if ($exception->getCode() !== "23000") {
                    throw $exception;
                }

                $message = "อีเมลนี้ถูกใช้งานแล้ว";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>สมัครสมาชิก | ข่าวสารภายในประเทศ</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=<?php echo (int) filemtime(__DIR__ . "/../assets/css/style.css"); ?>"
    >

</head>

<body class="news-sidebar-layout news-sidebar-collapsed">
<?php
$sidebarBase = "../";
require __DIR__ . "/../includes/news-sidebar.php";
?>

<header class="header">

    <nav class="navbar">

        <a
            href="../index.php"
            class="logo"
        >
            ข่าวสาร<span>ภายในประเทศ</span>
        </a>

        <ul class="menu">

            <li>
                <a href="../index.php">
                    หน้าแรก
                </a>
            </li>

            <?php foreach ($siteCategories as $menuCategory): ?>
                <li>
                    <a href="category.php?category=<?= (int) $menuCategory["id"] ?>">
                        <?= htmlspecialchars($menuCategory["name"], ENT_QUOTES, "UTF-8") ?>
                    </a>
                </li>
            <?php endforeach; ?>

        </ul>

    </nav>

</header>


<main class="auth-page">

    <div class="auth-card">

        <div class="auth-logo">
            ข่าวสาร<span>ภายในประเทศ</span>
        </div>


        <h1 class="auth-title">
            สมัครสมาชิก
        </h1>


        <p class="auth-description">
            สมัครสมาชิกเพื่อแสดงความคิดเห็น
            และใช้งานระบบสมาชิกของข่าวสารภายในประเทศ
        </p>


        <?php if ($message !== ""): ?>

            <div class="auth-message">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div
                class="auth-message"
                style="
                    background:#e8f7ed;
                    color:#24733d;
                    border-color:#b9e2c5;
                "
            >

                <?php
                echo htmlspecialchars($success);
                ?>

            </div>

        <?php endif; ?>


        <?php if ($success === ""): ?>

            <form
                method="POST"
                class="auth-form"
                autocomplete="off"
            >

                <label for="name">
                    ชื่อผู้ใช้งาน
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="กรอกชื่อของคุณ"
                    autocomplete="name"
                    required
                >


                <label for="email">
                    อีเมล
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="กรอกอีเมล"
                    autocomplete="email"
                    required
                >


                <label for="password">
                    รหัสผ่าน
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="อย่างน้อย 6 ตัวอักษร"
                    autocomplete="new-password"
                    required
                >


                <label for="confirm_password">
                    ยืนยันรหัสผ่าน
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="กรอกรหัสผ่านอีกครั้ง"
                    autocomplete="new-password"
                    required
                >


                <button
                    type="submit"
                    class="auth-button"
                >
                    สมัครสมาชิก
                </button>

            </form>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div class="auth-link">

                <a href="login.php">
                    เข้าสู่ระบบ
                </a>

            </div>

        <?php else: ?>

            <div class="auth-link">

                มีบัญชีอยู่แล้ว?
                <a href="login.php">
                    เข้าสู่ระบบ
                </a>

            </div>

        <?php endif; ?>


        <div class="auth-link">

            <a href="../index.php">
                ← กลับหน้าแรก
            </a>

        </div>

    </div>

</main>


<footer class="footer">

    <p>
        © 2026 ข่าวสารภายในประเทศ
    </p>

    <p style="margin-top:8px; opacity:0.8;">

        ระบบประชาสัมพันธ์ข่าวสารต่างประเทศ
        ผ่านเว็บไซต์ด้วยระบบ CMS

    </p>

</footer>

<script src="../js/script.js?v=<?php echo (int) filemtime(__DIR__ . "/../js/script.js"); ?>" defer></script>
</body>

</html>