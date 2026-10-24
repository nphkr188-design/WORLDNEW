<?php

require_once __DIR__ . "/require-admin.php";

require_once "../config/database.php";

$search = trim($_GET["search"] ?? "");
$status = $_GET["status"] ?? "";

$sql = "
    SELECT
        news.*,
        categories.name AS category_name,
        users.name AS author_name
    FROM news

    LEFT JOIN categories
        ON news.category_id = categories.id

    LEFT JOIN users
        ON news.author_id = users.id

    WHERE 1=1
";

$params = [];

if ($search !== "") {

    $sql .= "
        AND (
            news.title LIKE :search
            OR news.content LIKE :search
        )
    ";

    $params[":search"] = "%" . $search . "%";
}

if (
    $status === "published" ||
    $status === "draft"
) {

    $sql .= "
        AND news.status = :status
    ";

    $params[":status"] = $status;
}

$sql .= "
    ORDER BY news.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$newsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!isset($_SESSION["delete_news_token"])) {
    $_SESSION["delete_news_token"] = bin2hex(random_bytes(32));
}
$deleteToken = $_SESSION["delete_news_token"];

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
        จัดการข่าว | ข่าวสารภายในประเทศ CMS
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

<body class="admin-body admin-news-page">


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
                <a href="dashboard.php">
                    Dashboard
                </a>
            </li>

            <li>
                <a href="news.php">
                    จัดการข่าว
                </a>
            </li>

            <li>
                <a href="users.php">
                    จัดการผู้ใช้
                </a>
            </li>

            <li>
                <a href="news_create.php">
                    + เพิ่มข่าว
                </a>
            </li>

        </ul>

        <div class="admin-news-user">
            <span class="admin-role-label">ADMIN</span>
            <a class="admin-logout-link" href="logout.php">
                ออกจากระบบ
            </a>

        </div>

    </nav>

</header>


<main class="admin-container">


    <div
        class="admin-news-heading"
    >

        <div>

            <h1 class="admin-title">
                จัดการข่าว
            </h1>

            <p class="admin-description">
                เพิ่ม แก้ไข ลบ และจัดการสถานะข่าว
            </p>

        </div>


        <a
            href="news_create.php"
            class="admin-button admin-button-primary"
            style="
                padding:12px 18px;
                font-size:14px;
            "
        >
            ＋ เพิ่มข่าวใหม่
        </a>

    </div>


    <div
        class="admin-form-card admin-news-filter-card"
    >

        <form
            method="GET"
            class="admin-news-filter"
        >

            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="🔎 ค้นหาหัวข้อหรือเนื้อข่าว..."
            >


            <select
                name="status"
            >

                <option value="">
                    ทุกสถานะ
                </option>

                <option
                    value="published"
                    <?php
                    echo $status === "published"
                        ? "selected"
                        : "";
                    ?>
                >
                    เผยแพร่
                </option>

                <option
                    value="draft"
                    <?php
                    echo $status === "draft"
                        ? "selected"
                        : "";
                    ?>
                >
                    ฉบับร่าง
                </option>

            </select>


            <button
                type="submit"
                class="admin-button admin-button-primary"
            >
                ค้นหา
            </button>

        </form>

    </div>


    <?php if (($_GET["deleted"] ?? "") === "1"): ?>
        <div class="admin-form-card admin-news-notice admin-news-notice-success" role="status">
            ลบข่าวเรียบร้อยแล้ว
        </div>
    <?php elseif (($_GET["error"] ?? "") === "delete"): ?>
        <div class="admin-form-card admin-news-notice admin-news-notice-error" role="alert">
            ลบข่าวไม่สำเร็จ กรุณาลองใหม่อีกครั้ง
        </div>
    <?php endif; ?>

    <div class="admin-news-count">

        พบข่าว
        <strong>
            <?php echo count($newsList); ?>
        </strong>
        รายการ

    </div>


    <div class="admin-table-wrapper admin-news-table-wrapper">

        <table class="admin-table">

            <thead>

                <tr>

                    <th>
                        ข่าว
                    </th>

                    <th>
                        หมวดหมู่
                    </th>

                    <th>
                        ผู้เขียน
                    </th>

                    <th>
                        สถานะ
                    </th>

                    <th>
                        เข้าชม
                    </th>

                    <th>
                        จัดการ
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php if (count($newsList) > 0): ?>

                <?php foreach ($newsList as $news): ?>

                    <tr>


                        <td class="admin-news-title-cell">

                            <div
                                style="
                                    display:flex;
                                    gap:12px;
                                    align-items:center;
                                "
                            >

                                <?php if (
                                    !empty($news["image"])
                                ): ?>

                                    <img
                                        src="../assets/images/<?php echo htmlspecialchars($news["image"]); ?>"
                                        alt=""
                                        style="
                                            width:70px;
                                            height:50px;
                                            object-fit:cover;
                                            border-radius:8px;
                                        "
                                    >

                                <?php else: ?>

                                    <div
                                        style="
                                            width:70px;
                                            height:50px;
                                            background:#eee;
                                            border-radius:8px;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            font-size:11px;
                                            color:#888;
                                        "
                                    >
                                        ไม่มีรูป
                                    </div>

                                <?php endif; ?>


                                <div>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $news["title"]
                                        );
                                        ?>

                                    </strong>


                                    <div
                                        style="
                                            color:#999;
                                            font-size:12px;
                                            margin-top:3px;
                                        "
                                    >

                                        <?php
                                        echo date(
                                            "d/m/Y H:i",
                                            strtotime(
                                                $news["created_at"]
                                            )
                                        );
                                        ?>

                                    </div>

                                </div>

                            </div>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $news["category_name"] ?? "-"
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $news["author_name"] ?? "-"
                            );
                            ?>

                        </td>


                        <td>

                            <?php if (
                                $news["status"]
                                === "published"
                            ): ?>

                                <span
                                    style="
                                        display:inline-block;
                                        background:#e9f8ef;
                                        color:#237a43;
                                        padding:5px 10px;
                                        border-radius:20px;
                                        font-size:12px;
                                        font-weight:600;
                                    "
                                >
                                    ● เผยแพร่
                                </span>

                            <?php else: ?>

                                <span
                                    style="
                                        display:inline-block;
                                        background:#fff7df;
                                        color:#9a7000;
                                        padding:5px 10px;
                                        border-radius:20px;
                                        font-size:12px;
                                        font-weight:600;
                                    "
                                >
                                    ● ฉบับร่าง
                                </span>

                            <?php endif; ?>

                        </td>


                        <td>

                            👁️
                            <?php
                            echo number_format(
                                (int)$news["views"]
                            );
                            ?>

                        </td>


                        <td>

                            <div
                                style="
                                    display:flex;
                                    gap:6px;
                                    flex-wrap:wrap;
                                "
                            >

                                <a
                                    href="news_edit.php?id=<?php echo (int)$news["id"]; ?>"
                                    class="admin-button admin-button-edit"
                                >
                                    แก้ไข
                                </a>


                                <form
                                    method="POST"
                                    action="delete-news.php"
                                    onsubmit="return confirm('คุณต้องการลบข่าวนี้หรือไม่?');"
                                >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?php echo (int)$news["id"]; ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo htmlspecialchars($deleteToken, ENT_QUOTES, "UTF-8"); ?>"
                                    >
                                    <button
                                        type="submit"
                                        class="admin-button admin-button-delete"
                                    >
                                        ลบ
                                    </button>
                                </form>

                            </div>

                        </td>


                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td
                        colspan="6"
                        style="
                            text-align:center;
                            padding:60px;
                            color:#888;
                        "
                    >

                        📰

                        <div style="margin-top:10px;">
                            ไม่พบข่าว
                        </div>

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


</main>


<footer class="footer">

    <p>
        © 2026 ข่าวสารภายในประเทศ CMS
    </p>

</footer>


    <script src="admin-transition.js?v=<?php echo (int) filemtime(__DIR__ . "/admin-transition.js"); ?>" defer></script>
</body>

</html>
