<?php

$sidebarBase = $sidebarBase ?? "";
require_once __DIR__ . "/categories.php";

$stmtSidebarNews = $pdo->query("
    SELECT id, title, created_at
    FROM news
    WHERE status = 'published'
    ORDER BY created_at DESC
    LIMIT 20
");

$sidebarNews = $stmtSidebarNews->fetchAll(PDO::FETCH_ASSOC);

$sidebarCategoryIcons = [
    "politics" => "🏛️",
    "economy" => "📈",
    "technology" => "💻",
    "sports" => "⚽",
    "society" => "👥",
    "entertainment" => "🎬",
];

?>
<aside class="news-sidebar" id="newsSidebar" aria-label="เมนูและหัวข้อข่าว">
    <div class="news-sidebar-top">
        <button
            class="news-sidebar-toggle"
            type="button"
            aria-label="เปิดเมนูข่าว"
            aria-expanded="false"
            aria-controls="newsSidebarContent"
        >
            <span aria-hidden="true">☰</span>
        </button>
        <a class="news-sidebar-brand" href="<?= htmlspecialchars($sidebarBase, ENT_QUOTES, "UTF-8") ?>index.php">
            <span class="news-sidebar-brand-mark" aria-hidden="true">ข</span>
            <span class="news-sidebar-brand-text">ข่าวสาร<span>ภายในประเทศ</span></span>
        </a>
    </div>

    <div class="news-sidebar-content" id="newsSidebarContent">
        <nav aria-label="เมนูข่าว">
            <p class="news-sidebar-label">เมนูหลัก</p>
            <a class="news-sidebar-link news-sidebar-home" href="<?= htmlspecialchars($sidebarBase, ENT_QUOTES, "UTF-8") ?>index.php">
                <span class="news-sidebar-icon" aria-hidden="true">⌂</span>
                <span class="news-sidebar-link-text">หน้าแรก</span>
            </a>

            <p class="news-sidebar-label news-sidebar-section-label">หมวดหมู่ข่าว</p>
            <?php foreach ($siteCategories as $sidebarCategory): ?>
                <a class="news-sidebar-link" href="<?= htmlspecialchars($sidebarBase, ENT_QUOTES, "UTF-8") ?>pages/category.php?category=<?= (int) $sidebarCategory["id"] ?>">
                    <span class="news-sidebar-icon" aria-hidden="true"><?= $sidebarCategoryIcons[$sidebarCategory["slug"]] ?? "📰" ?></span>
                    <span class="news-sidebar-link-text"><?= htmlspecialchars($sidebarCategory["name"], ENT_QUOTES, "UTF-8") ?></span>
                </a>
            <?php endforeach; ?>

            <button
                class="news-sidebar-headline-heading"
                type="button"
                aria-expanded="false"
                aria-controls="newsSidebarHeadlines"
            >
                <p class="news-sidebar-label">หัวข้อข่าวล่าสุด</p>
                <span class="news-sidebar-count"><?= count($sidebarNews) ?></span>
            </button>

            <div class="news-sidebar-headlines-panel" id="newsSidebarHeadlines" hidden>
                <?php if ($sidebarNews): ?>
                    <ol class="news-sidebar-headlines">
                        <?php foreach ($sidebarNews as $index => $item): ?>
                            <li>
                                <a class="news-sidebar-headline" href="<?= htmlspecialchars($sidebarBase, ENT_QUOTES, "UTF-8") ?>pages/detail.php?id=<?= (int) $item["id"] ?>">
                                    <span class="news-sidebar-headline-number"><?= str_pad((string) ($index + 1), 2, "0", STR_PAD_LEFT) ?></span>
                                    <span class="news-sidebar-headline-title"><?= htmlspecialchars($item["title"], ENT_QUOTES, "UTF-8") ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <p class="news-sidebar-empty">ยังไม่มีข่าวที่เผยแพร่</p>
                <?php endif; ?>
            </div>
        </nav>

        <div class="news-sidebar-footer">
            <a class="news-sidebar-link" href="<?= htmlspecialchars($sidebarBase, ENT_QUOTES, "UTF-8") ?>pages/search.php">
                <span class="news-sidebar-icon" aria-hidden="true">⌕</span>
                <span class="news-sidebar-link-text">ค้นหาข่าว</span>
            </a>
            <span class="news-sidebar-copyright">ข่าวสารภายในประเทศ</span>
        </div>
    </div>
</aside>
<button class="news-sidebar-backdrop" type="button" aria-label="ปิดเมนูข่าว" tabindex="-1"></button>
