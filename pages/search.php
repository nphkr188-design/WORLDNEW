<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";
require_once __DIR__ . "/../includes/validate-user-session.php";
require_once __DIR__ . "/../includes/categories.php";

$search = trim($_GET["search"] ?? "");

$newsList = [];


if ($search !== "") {

    $stmt = $pdo->prepare("
        SELECT
            news.*,
            categories.name AS category_name
        FROM news

        LEFT JOIN categories
            ON news.category_id = categories.id

        WHERE news.status = 'published'

        AND (
            news.title LIKE :title_search
            OR news.content LIKE :content_search
        )

        ORDER BY news.created_at DESC
    ");

    $stmt->execute([
        ":title_search" => "%" . $search . "%",
        ":content_search" => "%" . $search . "%"
    ]);

    $newsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        ค้นหาข่าว | ข่าวสารภายในประเทศ
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
            <input type="search" name="search" placeholder="ค้นหาข่าว..." aria-label="ค้นหาข่าว" value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>">
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


        <?php if (isset($_SESSION["user_id"])): ?>

            <div>

                👤
                <?php
                echo htmlspecialchars(
                    $_SESSION["user_name"]
                );
                ?>

                <a
                    href="logout.php"
                    style="margin-left:10px;"
                >
                    ออกจากระบบ
                </a>

            </div>

        <?php else: ?>

            <a href="login.php">
                เข้าสู่ระบบ
            </a>

        <?php endif; ?>

    </nav>

</header>


<main class="container">


    <section
        class="search-page-heading"
    >

        <h1
            class="search-page-title"
        >
            🔎 ค้นหาข่าว
        </h1>


        <form
            method="GET"
            action="search.php"
            class="search-page-form"
        >

            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
                placeholder="ค้นหาชื่อข่าวหรือเนื้อข่าว..."
                aria-label="ค้นหาชื่อข่าวหรือเนื้อข่าว"
            >

            <button type="submit">
                ค้นหา
            </button>

        </form>

    </section>


    <?php if ($search === ""): ?>

        <div
            style="
                text-align:center;
                padding:60px 20px;
                color:#777;
            "
        >

            <div
                style="font-size:50px;"
            >
                🔎
            </div>

            <h2>
                ค้นหาข่าวที่คุณสนใจ
            </h2>

            <p>
                พิมพ์คำค้นหาในช่องด้านบน
            </p>

        </div>


    <?php elseif (count($newsList) > 0): ?>


        <h2
            class="section-title"
        >

            ผลการค้นหา:
            "<?php echo htmlspecialchars($search); ?>"

        </h2>


        <p
            style="
                color:#777;
                margin-bottom:25px;
            "
        >

            พบ
            <?php echo count($newsList); ?>
            ข่าว

        </p>


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
                                    background:#ddd;
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


                            <small style="color:#888;">

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
            "
        >

            <div style="font-size:60px;">
                😕
            </div>

            <h2>
                ไม่พบข่าวที่ค้นหา
            </h2>

            <p style="color:#777;">
                ลองใช้คำค้นหาอื่น
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
        ระบบประชาสัมพันธ์ข่าวสารภายในประเทศ
        ผ่านเว็บไซต์ด้วยระบบ CMS
    </p>

</footer>


<script src="../js/script.js?v=<?php echo (int) filemtime(__DIR__ . "/../js/script.js"); ?>" defer></script>
</body>

</html>