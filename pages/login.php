<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";
require_once __DIR__ . "/../includes/validate-user-session.php";
require_once __DIR__ . "/../includes/categories.php";

$message = !empty($_SESSION["account_blocked"])
    ? "บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ"
    : "";
unset($_SESSION["account_blocked"]);


/* =========================================
   LOGIN
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim(
        $_POST["email"] ?? ""
    );

    $password = $_POST["password"] ?? "";


    if ($email === "" || $password === "") {

        $message = "กรุณากรอกอีเมลและรหัสผ่าน";

    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = :email
            AND role = 'user'
            LIMIT 1
        ");

        $stmt->execute([
            ":email" => $email
        ]);

        $user = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (
            $user &&
            password_verify(
                $password,
                $user["password"]
            )
        ) {

            if (($user["account_status"] ?? "active") !== "active") {
                $message = "บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ";
            } else {
                session_regenerate_id(true);

                $_SESSION["user_id"] =
                    $user["id"];

                $_SESSION["user_name"] =
                    $user["name"];

                $_SESSION["user_email"] =
                    $user["email"];

                $_SESSION["user_role"] =
                    $user["role"];

                header("Location: ../index.php");
                exit;
            }

        } else {

            $message =
                "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
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

    <title>
        เข้าสู่ระบบ | ข่าวสารภายในประเทศ
    </title>

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


<!-- =========================================
     HEADER
========================================= -->

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


<!-- =========================================
     LOGIN
========================================= -->

<main class="auth-page">

    <div class="auth-card">


        <div class="auth-logo">
            ข่าวสาร<span>ภายในประเทศ</span>
        </div>


        <h1 class="auth-title">
            เข้าสู่ระบบ
        </h1>


        <p class="auth-description">
            เข้าสู่ระบบเพื่อแสดงความคิดเห็น
            และใช้งานระบบสมาชิก
        </p>


        <?php if ($message !== ""): ?>

            <div class="auth-message">

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            class="auth-form"
        >


            <label for="email">
                อีเมล
            </label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="กรอกอีเมล"
                required
            >


            <label for="password">
                รหัสผ่าน
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="กรอกรหัสผ่าน"
                required
            >


            <button
                type="submit"
                class="auth-button"
            >
                เข้าสู่ระบบ
            </button>


        </form>

        <div class="auth-link">
            <a href="forgot-password.php">ลืมรหัสผ่าน?</a>
        </div>

        <div class="auth-link">

            ยังไม่มีบัญชี?

            <a href="register.php">
                สมัครสมาชิก
            </a>

        </div>


        <div class="auth-link">

            <a href="../index.php">
                ← กลับหน้าแรก
            </a>

        </div>


    </div>

</main>


<!-- =========================================
     FOOTER
========================================= -->

<footer class="footer">

    <p>
        © 2026 ข่าวสารภายในประเทศ
    </p>

    <p style="margin-top:8px; opacity:0.8;">
        ระบบประชาสัมพันธ์ข่าวสารต่างประเทศผ่านเว็บไซต์ด้วยระบบ CMS
    </p>

</footer>


<script src="../js/script.js?v=<?php echo (int) filemtime(__DIR__ . "/../js/script.js"); ?>" defer></script>
</body>

</html>