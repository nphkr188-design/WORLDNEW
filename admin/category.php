<?php

require_once __DIR__ . "/require-admin.php";
require_once "../config/database.php";
require_once __DIR__ . "/../includes/categories.php";

// รับ ID หมวดหมู่
$categoryId = filter_input(
    INPUT_GET,
    "category",
    FILTER_VALIDATE_INT
);

if (!$categoryId) {
    die("ไม่พบหมวดหมู่");
}

// ดึงข้อมูลหมวดหมู่
$stmt = $pdo->prepare("
    SELECT *
    FROM categories
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ":id" => $categoryId
]);

$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    die("ไม่พบหมวดหมู่นี้");
}

// ดึงข่าวในหมวดหมู่
$stmt = $pdo->prepare("
    SELECT
        news.*,
        categories.name AS category_name,
        users.name AS author_name
    FROM news
    LEFT JOIN categories
        ON news.category_id = categories.id
    LEFT JOIN users
        ON news.author_id = users.id
    WHERE news.category_id = :category_id
    AND news.status = 'published'
    ORDER BY news.created_at DESC
");

$stmt->execute([
    ":category_id" => $categoryId
]);

$newsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        - ข่าวสารภายในประเทศ
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

<header class="header">

    <nav class="navbar">

        <div class="logo">
            ข่าวสาร<span>ภายในประเทศ</span>
        </div>

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


<main class="container">

    <!-- หัวข้อหมวดหมู่ -->

    <section class="hero">

        <h1>
            ข่าว<?php echo htmlspecialchars($category["name"]); ?>
        </h1>

        <p>
            ข่าวสารล่าสุดในหมวด
            <?php echo htmlspecialchars($category["name"]); ?>
        </p>

    </section>


    <!-- รายการข่าว -->

    <section>

        <h2 class="section-title">
            📰 ข่าวในหมวดนี้
        </h2>


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
                                    alt="<?php echo htmlspecialchars($news["title"]); ?>"
                                >

                            <?php else: ?>

                                <div
                                    class="news-image"
                                    style="
                                        background:#ddd;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
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

                                    <?php
                                    echo htmlspecialchars(
                                        mb_substr(
                                            $news["content"],
                                            0,
                                            120
                                        )
                                    );
                                    ?>

                                    ...

                                </p>

                            </div>

                        </article>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div
                style="
                    background:white;
                    padding:40px;
                    text-align:center;
                    border-radius:15px;
                "
            >

                <h3>
                    ยังไม่มีข่าวในหมวดนี้
                </h3>

                <p style="color:#777; margin-top:10px;">
                    กรุณากลับมาใหม่ภายหลัง
                </p>

            </div>

        <?php endif; ?>

    </section>

</main>


<footer class="footer">

    <p>
        © 2026 ข่าวสารภายในประเทศ
    </p>

    <p>
        สงวนลิขสิทธิ์ © โครงงานจบเรื่องประชาสัมพันธ์ข่าวต่างประเทศ
    </p>

</footer>

    <script src="admin-transition.js?v=<?php echo (int) filemtime(__DIR__ . "/admin-transition.js"); ?>" defer></script>
</body>

</html>