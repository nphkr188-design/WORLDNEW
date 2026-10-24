<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";
require_once __DIR__ . "/../includes/render-news-content.php";
require_once __DIR__ . "/../includes/validate-user-session.php";
require_once __DIR__ . "/../includes/categories.php";


/* =========================================
   ตรวจสอบ ID ข่าว
========================================= */

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    die("ไม่พบข่าวที่ต้องการ");
}


/* =========================================
   เพิ่มความคิดเห็น
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["add_comment"])
) {

    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }

    $comment = trim(
        $_POST["comment"] ?? ""
    );

    if ($comment !== "") {

        $stmt = $pdo->prepare("
            INSERT INTO comments
            (
                news_id,
                user_id,
                comment
            )
            VALUES
            (
                :news_id,
                :user_id,
                :comment
            )
        ");

        $stmt->execute([
            ":news_id" => $id,
            ":user_id" => $_SESSION["user_id"],
            ":comment" => $comment
        ]);
    }

    header(
        "Location: detail.php?id=" . $id
    );

    exit;
}


/* =========================================
   เพิ่มจำนวนวิว
========================================= */

$stmtView = $pdo->prepare("
    UPDATE news
    SET views = views + 1
    WHERE id = :id
");

$stmtView->execute([
    ":id" => $id
]);


/* =========================================
   ดึงข้อมูลข่าว
========================================= */

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

    WHERE news.id = :id
    AND news.status = 'published'

    LIMIT 1
");

$stmt->execute([
    ":id" => $id
]);

$news = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/* =========================================
   ตรวจสอบข่าว
========================================= */

if (!$news) {
    die("ไม่พบข่าวนี้ หรือข่าวยังไม่ได้เผยแพร่");
}


/* =========================================
   ดึงความคิดเห็น
========================================= */

$stmtComments = $pdo->prepare("
    SELECT
        comments.*,
        users.name AS user_name

    FROM comments

    LEFT JOIN users
        ON comments.user_id = users.id

    WHERE comments.news_id = :news_id

    ORDER BY comments.created_at DESC
");

$stmtComments->execute([
    ":news_id" => $id
]);

$comments = $stmtComments->fetchAll(
    PDO::FETCH_ASSOC
);

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
        <?php
        echo htmlspecialchars(
            $news["title"]
        );
        ?>
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


        <!-- Search -->

        <form
            action="search.php"
            method="GET"
            class="search-box header-search"
            role="search"
        >

            <input
                type="search"
                name="search"
                placeholder="ค้นหาข่าว..."
                aria-label="ค้นหาข่าว"
            >

            <button type="submit" aria-label="ค้นหา">
                ค้นหา
            </button>

        </form>


        <!-- Menu -->

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
     MAIN
========================================= -->

<main class="container">


    <!-- News -->

    <article class="news-detail">

        <div class="news-detail-card">


            <!-- รูปข่าว -->

            <?php if (!empty($news["image"])): ?>

                <img
                    src="../assets/images/<?php
                    echo htmlspecialchars(
                        $news["image"]
                    );
                    ?>"
                    class="news-detail-image"
                    alt="<?php
                    echo htmlspecialchars(
                        $news["title"]
                    );
                    ?>"
                >

            <?php else: ?>

                <div
                    class="news-detail-image"
                    style="
                        background:#e5e7eb;
                        height:300px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        color:#777;
                    "
                >
                    ไม่มีรูปภาพ
                </div>

            <?php endif; ?>


            <!-- เนื้อหาข่าว -->

            <div class="news-detail-content">


                <!-- หมวดหมู่ -->

                <span class="category">

                    <?php
                    echo htmlspecialchars(
                        $news["category_name"]
                    );
                    ?>

                </span>


                <!-- หัวข้อ -->

                <h1 class="news-detail-title">

                    <?php
                    echo htmlspecialchars(
                        $news["title"]
                    );
                    ?>

                </h1>


                <!-- ข้อมูลข่าว -->

                <div class="news-detail-meta">

                    👤 ผู้เขียน:
                    <?php
                    echo htmlspecialchars(
                        $news["author_name"] ?? "ไม่ระบุ"
                    );
                    ?>

                    &nbsp; | &nbsp;

                    📅
                    <?php
                    echo date(
                        "d/m/Y H:i",
                        strtotime(
                            $news["created_at"]
                        )
                    );
                    ?>

                    &nbsp; | &nbsp;

                    👁️
                    <?php
                    echo number_format(
                        (int)$news["views"]
                    );
                    ?>
                    ครั้ง

                </div>


                <!-- เนื้อหาข่าว -->

                <div class="news-detail-text">

                    <?php echo renderNewsContent($news["content"], "../assets/images/", "../assets/videos/"); ?>

                </div>


                <!-- แหล่งข่าว -->

                <?php if (!empty($news["source"])): ?>

                    <div class="news-source">

                        <strong>
                            📰 แหล่งข่าว:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $news["source"]
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <!-- กลับหน้าแรก -->

                <a
                    href="../index.php"
                    class="back-button"
                >
                    ← กลับหน้าแรก
                </a>


            </div>

        </div>

    </article>



    <!-- =====================================
         COMMENT SECTION
    ====================================== -->

    <section class="comment-section">


        <h2>
            💬 ความคิดเห็น
        </h2>


        <!-- Comment Form -->

        <?php if (isset($_SESSION["user_id"])): ?>

            <form
                method="POST"
                class="comment-form"
            >

                <textarea
                    name="comment"
                    placeholder="แสดงความคิดเห็นเกี่ยวกับข่าวนี้..."
                    required
                ></textarea>

                <button
                    type="submit"
                    name="add_comment"
                >
                    ส่งความคิดเห็น
                </button>

            </form>

        <?php else: ?>

            <div class="comment-form">

                <p>
                    🔐 กรุณาเข้าสู่ระบบก่อนแสดงความคิดเห็น
                </p>

                <br>

                <a
                    href="login.php"
                    class="back-button"
                >
                    เข้าสู่ระบบ
                </a>

            </div>

        <?php endif; ?>


        <!-- แสดงความคิดเห็น -->

        <?php if (count($comments) > 0): ?>


            <?php foreach ($comments as $comment): ?>

                <div class="comment-item">


                    <div class="comment-user">

                        👤
                        <?php
                        echo htmlspecialchars(
                            $comment["user_name"]
                        );
                        ?>

                    </div>


                    <div class="comment-text">

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $comment["comment"]
                            )
                        );
                        ?>

                    </div>


                    <div class="comment-date">

                        <?php
                        echo date(
                            "d/m/Y H:i",
                            strtotime(
                                $comment["created_at"]
                            )
                        );
                        ?>

                    </div>


                </div>

            <?php endforeach; ?>


        <?php else: ?>

            <div class="comment-item">

                <div class="comment-text">

                    ยังไม่มีความคิดเห็น
                    เป็นคนแรกที่แสดงความคิดเห็นได้เลย

                </div>

            </div>

        <?php endif; ?>


    </section>


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