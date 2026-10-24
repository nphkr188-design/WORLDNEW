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

require_once "../config/database.php";

$message = isset($_GET["logged_out"])
    ? "ออกจากระบบเรียบร้อยแล้ว กรุณาเข้าสู่ระบบอีกครั้งเพื่อจัดการเว็บไซต์"
    : "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim(
        $_POST["email"] ?? ""
    );

    $password =
        $_POST["password"] ?? "";


    if (
        $email === "" ||
        $password === ""
    ) {

        $message =
            "กรุณากรอกอีเมลและรหัสผ่าน";

    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = :email
            AND role = 'admin'
            AND account_status = 'active'
            LIMIT 1
        ");

        $stmt->execute([
            ":email" => $email
        ]);

        $user =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (
            $user &&
            password_verify(
                $password,
                $user["password"]
            )
        ) {

            session_regenerate_id(true);

            $_SESSION["user_id"] =
                $user["id"];

            $_SESSION["user_name"] =
                $user["name"];

            $_SESSION["user_email"] =
                $user["email"];

            $_SESSION["user_role"] =
                $user["role"];


            header(
                "Location: dashboard.php"
            );

            exit;

        } else {

            $message =
                "ข้อมูลผู้ดูแลระบบไม่ถูกต้อง";

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
        Admin Login | ข่าวสารภายในประเทศ
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=<?php echo (int) filemtime(__DIR__ . "/../assets/css/style.css"); ?>"
    >
    <link
        rel="stylesheet"
        href="admin-transition.css?v=<?php echo (int) filemtime(__DIR__ . "/admin-transition.css"); ?>"
    >

</head>

<body>


<main class="auth-page">


    <div class="auth-card">


        <div class="auth-logo">

            ข่าวสาร<span>ภายในประเทศ</span>

        </div>


        <div
            style="
                text-align:center;
                font-size:40px;
                margin:10px 0;
            "
        >
            🔐
        </div>


        <h1 class="auth-title">
            ADMIN
        </h1>


        <p class="auth-description">
            เข้าสู่ระบบสำหรับผู้ดูแลเว็บไซต์
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
            autocomplete="off"
        >


            <label>
                อีเมลผู้ดูแลระบบ
            </label>


            <input
                type="email"
                name="email"
                placeholder="admin@worldnews.com"
                autocomplete="username"
                autocapitalize="none"
                spellcheck="false"
                required
            >


            <label>
                รหัสผ่าน
            </label>


            <input
                type="password"
                name="password"
                placeholder="กรอกรหัสผ่าน"
                autocomplete="new-password"
                required
            >


            <button
                type="submit"
                class="auth-button"
            >
                เข้าสู่ระบบ Admin
            </button>


        </form>

        <div class="auth-link">
            <a href="forgot-password.php">ลืมรหัสผ่านแอดมิน?</a>
        </div>

        <div class="auth-link">

            <a href="../index.php">
                ← กลับหน้าเว็บไซต์
            </a>

        </div>


    </div>


</main>


    <script src="admin-transition.js?v=<?php echo (int) filemtime(__DIR__ . "/admin-transition.js"); ?>" defer></script>
</body>

</html>