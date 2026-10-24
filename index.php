<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";
require_once __DIR__ . "/includes/validate-user-session.php";
require_once __DIR__ . "/includes/categories.php";

$stmtHero = $pdo->query("
    SELECT
        news.*,
        categories.name AS category_name
    FROM news
    LEFT JOIN categories
        ON news.category_id = categories.id
    WHERE news.status = 'published'
        AND news.image IS NOT NULL
        AND news.image <> ''
    ORDER BY RAND()
    LIMIT 5
");

$heroSlides = $stmtHero->fetchAll(PDO::FETCH_ASSOC);

if (!$heroSlides) {
    $stmtHero = $pdo->query("
        SELECT
            news.*,
            categories.name AS category_name
        FROM news
        LEFT JOIN categories
            ON news.category_id = categories.id
        WHERE news.status = 'published'
        ORDER BY news.created_at DESC
        LIMIT 1
    ");

    $heroSlides = $stmtHero->fetchAll(PDO::FETCH_ASSOC);
}

$stmtHeadlines = $pdo->query("
    SELECT id, title
    FROM news
    WHERE status = 'published'
    ORDER BY created_at DESC
    LIMIT 10
");

$headlines = $stmtHeadlines->fetchAll(PDO::FETCH_ASSOC);

$stmtLatest = $pdo->query("
    SELECT
        news.*,
        categories.name AS category_name
    FROM news
    LEFT JOIN categories
        ON news.category_id = categories.id
    WHERE news.status = 'published'
    ORDER BY news.created_at DESC
    LIMIT 6
");

$latestNews = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);

$stmtPopular = $pdo->query("
    SELECT
        news.*,
        categories.name AS category_name
    FROM news
    LEFT JOIN categories
        ON news.category_id = categories.id
    WHERE news.status = 'published'
        AND news.views >= 20
    ORDER BY news.views DESC, news.created_at DESC
    LIMIT 5
");

$popularNews = $stmtPopular->fetchAll(PDO::FETCH_ASSOC);

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function newsExcerpt($content, $length = 120)
{
    return e(mb_substr(trim(strip_tags((string) $content)), 0, $length, "UTF-8"));
}

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ติดตามข่าวสารภายในประเทศ ข่าวเด่น ข่าวล่าสุด และประเด็นสำคัญ">
    <title>ข่าวสารภายในประเทศ | ข่าวเด่น ข่าวล่าสุด</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) filemtime(__DIR__ . "/assets/css/style.css") ?>">
</head>

<body class="home-page news-sidebar-layout news-sidebar-collapsed">
<?php
$sidebarBase = "";
require __DIR__ . "/includes/news-sidebar.php";
?>
<header class="header">
    <nav class="navbar" aria-label="เมนูหลัก">
        <a href="index.php" class="logo" aria-label="ข่าวสารภายในประเทศ หน้าแรก">
            ข่าวสาร<span>ภายในประเทศ</span>
        </a>

        <form class="search-box home-search" action="pages/search.php" method="GET" role="search">
            <input type="search" name="search" placeholder="ค้นหาข่าว..." aria-label="ค้นหาข่าว">
            <button type="submit" aria-label="ค้นหา">ค้นหา</button>
        </form>

        <ul class="menu">
            <li><a href="index.php" aria-current="page">หน้าแรก</a></li>
            <?php foreach ($siteCategories as $category): ?>
                <li><a href="pages/category.php?category=<?= (int) $category["id"] ?>"><?= e($category["name"]) ?></a></li>
            <?php endforeach; ?>
            <?php if (isset($_SESSION["user_id"])): ?>
                <li class="account-link">
                    <span>👤 <?= e($_SESSION["user_name"] ?? "สมาชิก") ?></span>
                </li>
                <li><a href="pages/logout.php">ออกจากระบบ</a></li>
            <?php else: ?>
                <li><a href="pages/login.php">เข้าสู่ระบบ</a></li>
                <li><a href="pages/register.php">สมัครสมาชิก</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<?php if ($headlines): ?>
<section class="headline-ticker" aria-label="ข่าวเด่น">
    <div class="headline-ticker-inner">
        <span class="headline-label"><span aria-hidden="true">●</span> ข่าวเด่น</span>
        <div class="headline-track-window">
            <div class="headline-track">
                <div class="headline-group">
                    <?php foreach ($headlines as $headline): ?>
                        <a href="pages/detail.php?id=<?= (int) $headline["id"] ?>">
                            <?= e($headline["title"]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="headline-group" aria-hidden="true">
                    <?php foreach ($headlines as $headline): ?>
                        <a href="pages/detail.php?id=<?= (int) $headline["id"] ?>" tabindex="-1">
                            <?= e($headline["title"]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<main class="container home-container">
    <?php if ($heroSlides): ?>
        <section class="hero-section" aria-label="ข่าวเด่นประจำวัน">
            <div class="hero-slider" data-hero-slider aria-roledescription="carousel">
                <?php foreach ($heroSlides as $index => $hero): ?>
                    <article
                        class="hero-slide<?= $index === 0 ? " is-active" : "" ?>"
                        aria-roledescription="สไลด์"
                        aria-label="<?= ($index + 1) . " จาก " . count($heroSlides) ?>"
                        <?= $index === 0 ? "" : "aria-hidden=\"true\"" ?>
                    >
                        <?php if (!empty($hero["image"])): ?>
                            <img
                                src="assets/images/<?= e($hero["image"]) ?>"
                                class="hero-image"
                                alt=""
                                <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                            >
                        <?php endif; ?>
                        <div class="hero-overlay"></div>
                        <div class="hero-content">
                            <span class="hero-category"><?= e($hero["category_name"] ?? "ข่าวเด่น") ?></span>
                            <h1><?= e($hero["title"]) ?></h1>
                            <p><?= newsExcerpt($hero["content"], 170) ?><?= mb_strlen(strip_tags((string) $hero["content"]), "UTF-8") > 170 ? "..." : "" ?></p>
                            <a href="pages/detail.php?id=<?= (int) $hero["id"] ?>" class="hero-button">
                                อ่านข่าวฉบับเต็ม <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if (count($heroSlides) > 1): ?>
                    <div class="hero-controls" aria-label="ควบคุมสไลด์ข่าว">
                        <button type="button" class="hero-control hero-previous" data-hero-previous aria-label="ข่าวก่อนหน้า">‹</button>
                        <div class="hero-dots" role="group" aria-label="เลือกสไลด์ข่าว">
                            <?php foreach ($heroSlides as $index => $hero): ?>
                                <button
                                    type="button"
                                    class="hero-dot<?= $index === 0 ? " is-active" : "" ?>"
                                    data-hero-dot="<?= $index ?>"
                                    aria-label="แสดงข่าวที่ <?= $index + 1 ?>"
                                    aria-current="<?= $index === 0 ? "true" : "false" ?>"
                                ></button>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="hero-control hero-next" data-hero-next aria-label="ข่าวถัดไป">›</button>
                    </div>
                    <span class="hero-autoplay-label"><span aria-hidden="true">◷</span> ข่าวเด่น</span>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="home-empty-state">
            <span class="home-empty-icon" aria-hidden="true">📰</span>
            <h1>ยินดีต้อนรับสู่ข่าวสารภายในประเทศ</h1>
            <p>ข่าวใหม่กำลังจะมา โปรดกลับมาติดตามอีกครั้ง</p>
        </section>
    <?php endif; ?>

    <?php if ($latestNews): ?>
        <section class="news-section">
            <div class="section-heading">
                <div>
                    <span class="section-eyebrow">อัปเดตล่าสุด</span>
                    <h2 class="section-title">ข่าวล่าสุด</h2>
                </div>
            </div>

            <div class="news-grid">
                <?php foreach ($latestNews as $news): ?>
                    <a class="news-card-link" href="pages/detail.php?id=<?= (int) $news["id"] ?>">
                        <article class="news-card">
                            <?php if (!empty($news["image"])): ?>
                                <img
                                    src="assets/images/<?= e($news["image"]) ?>"
                                    class="news-image"
                                    alt="<?= e($news["title"]) ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <div class="news-image news-image-placeholder" aria-label="ไม่มีรูปภาพ">WORLD NEWS</div>
                            <?php endif; ?>
                            <div class="news-content">
                                <span class="category"><?= e($news["category_name"] ?? "ข่าวทั่วไป") ?></span>
                                <h3><?= e($news["title"]) ?></h3>
                                <p><?= newsExcerpt($news["content"], 110) ?>...</p>
                                <span class="news-read-more">อ่านต่อ <span aria-hidden="true">→</span></span>
                            </div>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($popularNews): ?>
        <section class="news-section popular-section">
            <div class="section-heading">
                <div>
                    <span class="section-eyebrow">ผู้อ่านกำลังสนใจ</span>
                    <h2 class="section-title">ข่าวยอดนิยม</h2>
                </div>
            </div>

            <div class="news-grid">
                <?php foreach ($popularNews as $popular): ?>
                    <a class="news-card-link" href="pages/detail.php?id=<?= (int) $popular["id"] ?>">
                        <article class="news-card popular-card">
                            <div class="popular-image-wrap">
                                <?php if (!empty($popular["image"])): ?>
                                    <img
                                        src="assets/images/<?= e($popular["image"]) ?>"
                                        class="news-image"
                                        alt="<?= e($popular["title"]) ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="news-image news-image-placeholder" aria-label="ไม่มีรูปภาพ">WORLD NEWS</div>
                                <?php endif; ?>
                                <span class="popular-rank" aria-label="ข่าวยอดนิยม">ยอดนิยม</span>
                            </div>
                            <div class="news-content">
                                <span class="category"><?= e($popular["category_name"] ?? "ข่าวทั่วไป") ?></span>
                                <h3><?= e($popular["title"]) ?></h3>
                                <p class="popular-views">👁 <?= number_format((int) $popular["views"]) ?> ครั้ง</p>
                            </div>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<footer class="footer">
    <p class="footer-brand">ข่าวสาร<span>ภายในประเทศ</span></p>
    <p>ติดตามทุกประเด็นสำคัญ รู้ทันข่าวสารภายในประเทศ</p>
    <p class="footer-copyright">© 2026 ข่าวสารภายในประเทศ · สงวนลิขสิทธิ์</p>
</footer>

<script src="js/script.js?v=<?= (int) filemtime(__DIR__ . "/js/script.js") ?>" defer></script>
</body>
</html>
