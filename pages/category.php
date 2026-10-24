<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";
require_once __DIR__ . "/../includes/validate-user-session.php";
require_once __DIR__ . "/../includes/categories.php";

$categoryId = (int)($_GET["category"] ?? 0);

if ($categoryId <= 0) {
    header("Location: ../index.php");
    exit;
}


/* ดึงข้อมูลหมวดหมู่ */

$stmtCategory = $pdo->prepare("
    SELECT *
    FROM categories
    WHERE id = :id
    LIMIT 1
");

$stmtCategory->execute([
    ":id" => $categoryId
]);

$category = $stmtCategory->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header("Location: ../index.php");
    exit;
}


/* ดึงข่าว */

$stmtNews = $pdo->prepare("
    SELECT
        news.*,
        categories.name AS category_name
    FROM news
    LEFT JOIN categories
        ON news.category_id = categories.id
    WHERE news.category_id = :category_id
    AND news.status = 'published'
    ORDER BY news.created_at DESC
");

$stmtNews->execute([
    ":category_id" => $categoryId
]);

$newsList = $stmtNews->fetchAll(PDO::FETCH_ASSOC);

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
        <?php echo htmlspecialchars($category["name"]); ?>
        | ข่าวสารภายในประเทศ
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


<header class="header">

    <nav class="navbar">

        <a
            href="../index.php"
            class="logo"
        >
            ข่าวสาร<span>ภายในประเทศ</span>
        </a>

        <form class="search-box header-search" action="search.php" method="GET" role="search">
            <input type="search" name="search" placeholder="ค้นหาข่าว..." aria-label="ค้นหาข่าว">
            <button type="submit" aria-label="ค้นหา">ค้นหา</button>
        </form>


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


        <div>

            <?php if (isset($_SESSION["user_id"])): ?>

                <span style="margin-right:10px;">

                    👤
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["user_name"]
                    );
                    ?>

                </span>

                <a href="logout.php">
                    ออกจากระบบ
                </a>

            <?php else: ?>

                <a href="login.php">
                    เข้าสู่ระบบ
                </a>

            <?php endif; ?>

        </div>

    </nav>

</header>


<main class="container">


    <section class="category-header">

        <div>

            <p
                style="
                    color:#777;
                    margin-bottom:8px;
                "
            >
                หมวดหมู่ข่าว
            </p>

            <h1
                style="
                    font-size:36px;
                    margin:0;
                "
            >

                <?php
                echo htmlspecialchars(
                    $category["name"]
                );
                ?>

            </h1>

        </div>


        <div
            style="
                color:#777;
                margin-top:10px;
            "
        >

            พบข่าว
            <?php echo count($newsList); ?>
            รายการ

        </div>

    </section>


    <?php if (count($newsList) > 0): ?>

        <div class="news-grid">

            <?php foreach ($newsList as $news): ?>

                <a
                    href="detail.php?id=<?php echo (int)$news["id"]; ?>"
                    style="
                        text-decoration:none;
                        color:inherit;
                    "
                >

                    <article class="news-card">


                        <?php if (!empty($news["image"])): ?>

                            <img
                                src="../assets/images/<?php echo htmlspecialchars($news["image"]); ?>"
                                class="news-image"
                                alt=""
                            >

                        <?php else: ?>

                            <div
                                class="news-image"
                                style="
                                    background:#e5e5e5;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    color:#777;
                                "
                            >
                                ไม่มีรูปภาพ
                            </div>

                        <?php endif; ?>


                        <div class="news-content">


                            <span class="category">

                                <?php
                                echo htmlspecialchars(
                                    $news["category_name"]
                                );
                                ?>

                            </span>


                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $news["title"]
                                );
                                ?>

                            </h3>


                            <p>

                                👁️
                                <?php
                                echo number_format(
                                    (int)$news["views"]
                                );
                                ?>

                                ครั้ง

                            </p>


                            <small
                                style="color:#888;"
                            >

                                <?php
                                echo date(
                                    "d/m/Y",
                                    strtotime(
                                        $news["created_at"]
                                    )
                                );
                                ?>

                            </small>


                        </div>

                    </article>

                </a>

            <?php endforeach; ?>

        </div>


    <?php else: ?>

        <div
            style="
                text-align:center;
                padding:80px 20px;
                color:#777;
            "
        >

            <div
                style="
                    font-size:50px;
                    margin-bottom:15px;
                "
            >
                📰
            </div>

            <h2>
                ยังไม่มีข่าวในหมวดหมู่นี้
            </h2>

            <p>
                กรุณากลับมาใหม่ภายหลัง
            </p>

        </div>

    <?php endif; ?>


</main>


<footer class="footer">

    <p>
        © 2026 ข่าวสารภายในประเทศ
    </p>

    <p
        style="
            margin-top:8px;
            opacity:0.8;
        "
    >
        ระบบประชาสัมพันธ์ข่าวสารต่างประเทศ
        ผ่านเว็บไซต์ด้วยระบบ CMS
    </p>

</footer>


<script src="../js/script.js?v=<?php echo (int) filemtime(__DIR__ . "/../js/script.js"); ?>" defer></script>
</body>

</html>