<?php

require_once __DIR__ . "/require-admin.php";

require_once "../config/database.php";
require_once __DIR__ . "/../includes/categories.php";

$totalNews = (int) $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();
$publishedNews = (int) $pdo->query("
    SELECT COUNT(*)
    FROM news
    WHERE status = 'published'
")->fetchColumn();
$draftNews = (int) $pdo->query("
    SELECT COUNT(*)
    FROM news
    WHERE status = 'draft'
")->fetchColumn();
$categoryCount = count($siteCategories);

$recentNews = $pdo->query("
    SELECT news.id, news.title, news.status, news.created_at, categories.name AS category_name
    FROM news
    LEFT JOIN categories ON categories.id = news.category_id
    ORDER BY news.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

function dashboardEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด | ข่าวสารภายในประเทศ</title>
    <link rel="stylesheet" href="admin-transition.css?v=<?= (int) filemtime(__DIR__ . "/admin-transition.css") ?>">
    <style>
        :root {
            color-scheme: light;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: #202733;
            background: #f6f7f9;
            font-synthesis: none;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-width: 320px;
            min-height: 100vh;
            margin: 0;
            background: #f6f7f9;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .admin-header {
            display: flex;
            min-height: 72px;
            align-items: center;
            justify-content: space-between;
            padding: 0 max(24px, calc((100vw - 1180px) / 2));
            border-bottom: 1px solid #e9ebef;
            background: #fff;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
            font-weight: 750;
            letter-spacing: -0.02em;
        }

        .admin-brand-mark {
            display: grid;
            width: 36px;
            height: 36px;
            place-items: center;
            border-radius: 11px;
            background: #fae9eb;
            color: #b4232c;
            font-size: 18px;
        }

        .admin-header-actions {
            display: flex;
            align-items: center;
            gap: 18px;
            color: #687181;
            font-size: 13px;
        }

        .admin-logout {
            padding: 8px 13px;
            border: 1px solid #e6e8ed;
            border-radius: 9px;
            color: #4e5663;
            transition: color 0.15s ease, border-color 0.15s ease, background 0.15s ease;
        }

        .admin-logout:hover {
            border-color: #f1c8cc;
            background: #fff7f7;
            color: #b4232c;
        }

        .admin-main {
            width: min(1180px, calc(100% - 48px));
            margin: 0 auto;
            padding: 42px 0 56px;
        }

        .dashboard-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 26px;
        }

        .eyebrow {
            margin: 0 0 7px;
            color: #b4232c;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            color: #1e2530;
            font-size: clamp(25px, 4vw, 32px);
            letter-spacing: -0.04em;
        }

        .welcome-copy {
            margin: 8px 0 0;
            color: #747d8a;
            font-size: 14px;
        }

        .primary-action {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 16px;
            border-radius: 10px;
            background: #b4232c;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            transition: background 0.15s ease, transform 0.15s ease;
        }

        .primary-action:hover {
            transform: translateY(-1px);
            background: #991b24;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .stat-card {
            min-height: 142px;
            padding: 21px;
            border: 1px solid #eceef1;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 5px 18px rgba(23, 31, 43, 0.035);
        }

        .stat-label {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #707987;
            font-size: 13px;
            font-weight: 600;
        }

        .stat-icon {
            display: grid;
            width: 30px;
            height: 30px;
            place-items: center;
            border-radius: 9px;
            background: #f3f4f6;
            color: #586273;
            font-size: 15px;
        }

        .stat-card.is-published .stat-icon {
            background: #eaf7ef;
            color: #22834a;
        }

        .stat-card.is-draft .stat-icon {
            background: #fff5e6;
            color: #a96408;
        }

        .stat-value {
            margin-top: 14px;
            color: #202733;
            font-size: 32px;
            font-weight: 750;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }

        .dashboard-panel {
            margin-top: 24px;
            overflow: hidden;
            border: 1px solid #eceef1;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 5px 18px rgba(23, 31, 43, 0.035);
        }

        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px;
            border-bottom: 1px solid #f0f1f3;
        }

        .panel-heading h2 {
            margin: 0;
            font-size: 16px;
            letter-spacing: -0.02em;
        }

        .text-link {
            color: #b4232c;
            font-size: 13px;
            font-weight: 650;
        }

        .text-link:hover {
            text-decoration: underline;
        }

        .news-table-wrap {
            overflow-x: auto;
        }

        .news-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .news-table th,
        .news-table td {
            padding: 14px 22px;
            border-bottom: 1px solid #f1f2f4;
            font-size: 13px;
            white-space: nowrap;
        }

        .news-table th {
            background: #fcfcfd;
            color: #89919d;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .news-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .news-title {
            display: block;
            max-width: 560px;
            overflow: hidden;
            color: #303744;
            font-weight: 650;
            text-overflow: ellipsis;
        }

        .news-table td {
            color: #697382;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 9px;
            border-radius: 999px;
            background: #f1f2f4;
            color: #606977;
            font-size: 11px;
            font-weight: 700;
        }

        .status-badge.is-published {
            background: #eaf7ef;
            color: #22834a;
        }

        .empty-state {
            padding: 36px 20px;
            color: #858d99;
            font-size: 13px;
            text-align: center;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 20px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 17px;
            border: 1px solid #eceef1;
            border-radius: 13px;
            background: #fff;
            transition: border-color 0.15s ease, transform 0.15s ease;
        }

        .quick-link:hover {
            transform: translateY(-1px);
            border-color: #e5b7bb;
        }

        .quick-link-icon {
            display: grid;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            place-items: center;
            border-radius: 11px;
            background: #fcebed;
            color: #b4232c;
        }

        .quick-link strong {
            display: block;
            font-size: 13px;
        }

        .quick-link small {
            display: block;
            margin-top: 3px;
            color: #89919d;
            font-size: 11px;
        }

        @media (max-width: 820px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .admin-header {
                min-height: 64px;
                padding: 0 16px;
            }

            .admin-header-actions {
                gap: 10px;
            }

            .admin-user {
                display: none;
            }

            .admin-main {
                width: calc(100% - 32px);
                padding-top: 28px;
            }

            .dashboard-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .stats-grid {
                gap: 11px;
            }

            .stat-card {
                min-height: 124px;
                padding: 16px;
            }

            .stat-value {
                font-size: 28px;
            }

            .panel-heading,
            .news-table th,
            .news-table td {
                padding-right: 16px;
                padding-left: 16px;
            }

            .quick-links {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <a class="admin-brand" href="dashboard.php">
            <span class="admin-brand-mark" aria-hidden="true">ข</span>
            <span>ข่าวสารภายในประเทศ <span style="color:#89919d;font-weight:500">/ ผู้ดูแลระบบ</span></span>
        </a>
        <div class="admin-header-actions">
            <span class="admin-user">ADMIN</span>
            <a class="admin-logout" href="logout.php">ออกจากระบบ</a>
        </div>
    </header>

    <main class="admin-main">
        <section class="dashboard-heading" aria-labelledby="dashboardTitle">
            <div>
                <p class="eyebrow">ภาพรวมเว็บไซต์</p>
                <h1 id="dashboardTitle">แดชบอร์ด</h1>
                <p class="welcome-copy">ยินดีต้อนรับ <?= dashboardEscape($_SESSION["user_name"] ?? "ผู้ดูแลระบบ") ?> ดูสถานะข่าวและจัดการเว็บไซต์ได้จากที่นี่</p>
            </div>
            <a class="primary-action" href="news_create.php"><span aria-hidden="true">＋</span> เพิ่มข่าวใหม่</a>
        </section>

        <section class="stats-grid" aria-label="สถิติข่าว">
            <article class="stat-card">
                <div class="stat-label"><span class="stat-icon" aria-hidden="true">▤</span> ข่าวทั้งหมด</div>
                <div class="stat-value"><?= number_format($totalNews) ?></div>
            </article>
            <article class="stat-card is-published">
                <div class="stat-label"><span class="stat-icon" aria-hidden="true">✓</span> เผยแพร่แล้ว</div>
                <div class="stat-value"><?= number_format($publishedNews) ?></div>
            </article>
            <article class="stat-card is-draft">
                <div class="stat-label"><span class="stat-icon" aria-hidden="true">◷</span> ฉบับร่าง</div>
                <div class="stat-value"><?= number_format($draftNews) ?></div>
            </article>
            <article class="stat-card">
                <div class="stat-label"><span class="stat-icon" aria-hidden="true">▦</span> หมวดหมู่</div>
                <div class="stat-value"><?= number_format($categoryCount) ?></div>
            </article>
        </section>

        <section class="dashboard-panel" aria-labelledby="recentNewsTitle">
            <div class="panel-heading">
                <h2 id="recentNewsTitle">ข่าวที่เพิ่มล่าสุด</h2>
                <a class="text-link" href="news.php">จัดการข่าวทั้งหมด →</a>
            </div>
            <?php if ($recentNews): ?>
                <div class="news-table-wrap">
                    <table class="news-table">
                        <thead>
                            <tr>
                                <th scope="col">หัวข้อข่าว</th>
                                <th scope="col">หมวดหมู่</th>
                                <th scope="col">สถานะ</th>
                                <th scope="col">วันที่เพิ่ม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentNews as $item): ?>
                                <tr>
                                    <td><a class="news-title" href="news_edit.php?id=<?= (int) $item["id"] ?>"><?= dashboardEscape($item["title"]) ?></a></td>
                                    <td><?= dashboardEscape($item["category_name"] ?? "ไม่ระบุ") ?></td>
                                    <td><span class="status-badge<?= $item["status"] === "published" ? " is-published" : "" ?>"><?= $item["status"] === "published" ? "เผยแพร่แล้ว" : "ฉบับร่าง" ?></span></td>
                                    <td><?= dashboardEscape(date("d/m/Y H:i", strtotime($item["created_at"]))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">ยังไม่มีข่าว เริ่มต้นด้วยการเพิ่มข่าวเรื่องแรกของคุณ</p>
            <?php endif; ?>
        </section>

        <nav class="quick-links" aria-label="เมนูจัดการเว็บไซต์">
            <a class="quick-link" href="news_create.php">
                <span class="quick-link-icon" aria-hidden="true">＋</span>
                <span><strong>เขียนข่าวใหม่</strong><small>เพิ่มข่าวพร้อมรูปภาพและหมวดหมู่</small></span>
            </a>
            <a class="quick-link" href="news.php">
                <span class="quick-link-icon" aria-hidden="true">▤</span>
                <span><strong>จัดการข่าว</strong><small>แก้ไข เผยแพร่ หรือจัดการฉบับร่าง</small></span>
            </a>
            <a class="quick-link" href="users.php">
                <span class="quick-link-icon" aria-hidden="true">♙</span>
                <span><strong>จัดการผู้ใช้</strong><small>แก้ไขข้อมูลและดูแลบัญชีสมาชิก</small></span>
            </a>
        </nav>
    </main>
    <script src="admin-transition.js?v=<?= (int) filemtime(__DIR__ . "/admin-transition.js") ?>" defer></script>
</body>
</html>
