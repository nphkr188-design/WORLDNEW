<?php

require_once __DIR__ . "/require-admin.php";
require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION["manage_users_csrf"])) {
    $_SESSION["manage_users_csrf"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";
    if (
        !is_string($csrfToken)
        || !hash_equals($_SESSION["manage_users_csrf"], $csrfToken)
    ) {
        http_response_code(400);
        $message = "คำขอไม่ถูกต้อง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง";
        $messageType = "error";
    } else {
        $action = $_POST["action"] ?? "";
        $userId = filter_var($_POST["user_id"] ?? null, FILTER_VALIDATE_INT);

        if (!$userId || $userId <= 0) {
            $message = "ไม่พบบัญชีผู้ใช้ที่ต้องการจัดการ";
            $messageType = "error";
        } elseif ($action === "update") {
            $name = trim((string) ($_POST["name"] ?? ""));
            $email = strtolower(trim((string) ($_POST["email"] ?? "")));

            if ($name === "" || mb_strlen($name, "UTF-8") > 100) {
                $message = "กรุณากรอกชื่อไม่เกิน 100 ตัวอักษร";
                $messageType = "error";
            } elseif (
                !filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($email) > 150
            ) {
                $message = "กรุณากรอกอีเมลให้ถูกต้อง";
                $messageType = "error";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET name = :name, email = :email
                    WHERE id = :id AND role = 'user'
                ");
                try {
                    $stmt->execute([
                        ":name" => $name,
                        ":email" => $email,
                        ":id" => $userId,
                    ]);
                    $message = $stmt->rowCount() > 0
                        ? "บันทึกข้อมูลผู้ใช้เรียบร้อยแล้ว"
                        : "ข้อมูลไม่มีการเปลี่ยนแปลง";
                } catch (PDOException $exception) {
                    if ($exception->getCode() !== "23000") {
                        throw $exception;
                    }
                    $message = "อีเมลนี้ถูกใช้งานแล้ว";
                    $messageType = "error";
                }
            }
        } elseif ($action === "ban") {
            $reason = trim((string) ($_POST["ban_reason"] ?? ""));
            if ($reason === "") {
                $reason = "ระงับบัญชีโดยผู้ดูแลระบบ";
            }
            $reason = mb_substr($reason, 0, 500, "UTF-8");

            $stmt = $pdo->prepare("
                UPDATE users
                SET account_status = 'banned',
                    banned_at = CURRENT_TIMESTAMP,
                    ban_reason = :reason
                WHERE id = :id
                    AND role = 'user'
                    AND account_status = 'active'
            ");
            $stmt->execute([":reason" => $reason, ":id" => $userId]);
            $message = $stmt->rowCount() === 1
                ? "ระงับบัญชีผู้ใช้เรียบร้อยแล้ว"
                : "ไม่พบบัญชีที่ใช้งานอยู่";
            $messageType = $stmt->rowCount() === 1 ? "success" : "error";
        } elseif ($action === "delete") {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE id = :id AND role = 'user'
                    FOR UPDATE
                ");
                $stmt->execute([":id" => $userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $pdo->rollBack();
                    $message = "ไม่พบบัญชีผู้ใช้ที่ต้องการลบ";
                    $messageType = "error";
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE news
                        SET author_id = :admin_id
                        WHERE author_id = :user_id
                    ");
                    $stmt->execute([
                        ":admin_id" => (int) $_SESSION["user_id"],
                        ":user_id" => $userId,
                    ]);

                    $stmt = $pdo->prepare("
                        DELETE FROM users
                        WHERE id = :id AND role = 'user'
                    ");
                    $stmt->execute([":id" => $userId]);
                    $pdo->commit();
                    $message = "ลบบัญชีเรียบร้อยแล้ว ข่าวที่ผู้ใช้เขียนยังคงอยู่และถูกกำหนดเป็นข่าวของแอดมิน";
                }
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
        } else {
            $message = "ไม่รู้จักคำสั่งจัดการบัญชี";
            $messageType = "error";
        }
    }
}

$search = trim((string) ($_GET["search"] ?? ""));
$status = (string) ($_GET["status"] ?? "");
$where = ["role = 'user'"];
$params = [];

if ($search !== "") {
    $where[] = "(name LIKE :name_search OR email LIKE :email_search)";
    $params[":name_search"] = "%" . $search . "%";
    $params[":email_search"] = "%" . $search . "%";
}

if (in_array($status, ["active", "banned"], true)) {
    $where[] = "account_status = :status";
    $params[":status"] = $status;
}

$stmt = $pdo->prepare("
    SELECT id, name, email, account_status, banned_at, ban_reason, created_at
    FROM users
    WHERE " . implode(" AND ", $where) . "
    ORDER BY created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$activeUsers = (int) $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'user' AND account_status = 'active'
")->fetchColumn();
$bannedUsers = $totalUsers - $activeUsers;

function usersEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบัญชีผู้ใช้ | ข่าวสารภายในประเทศ</title>
    <link rel="stylesheet" href="admin-transition.css?v=<?= (int) filemtime(__DIR__ . "/admin-transition.css") ?>">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f6f7f9; color: #202733; font-family: "Segoe UI", Tahoma, Arial, sans-serif; }
        a { color: inherit; text-decoration: none; }
        .topbar { min-height: 68px; display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 12px max(20px, calc((100vw - 1180px) / 2)); border-bottom: 1px solid #e9ebef; background: #fff; }
        .brand { font-size: 15px; font-weight: 750; }
        .top-links { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; color: #687181; font-size: 13px; }
        .top-links a:hover { color: #b4232c; }
        .admin-role { padding: 6px 10px; border: 1px solid #eceef1; border-radius: 8px; color: #4e5663; font-size: 11px; font-weight: 750; letter-spacing: .06em; }
        .top-links .logout-link { display: inline-flex; min-height: 34px; align-items: center; padding: 0 12px; border: 1px solid #e2e5ea; border-radius: 9px; background: #fff; color: #555e6b; font-weight: 650; transition: color .15s ease, border-color .15s ease, background .15s ease; }
        .top-links .logout-link:hover { border-color: #f0c8cb; background: #fff7f7; color: #a52222; }
        main { width: min(1180px, calc(100% - 40px)); margin: 34px auto 56px; }
        .heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 22px; }
        .eyebrow { margin: 0 0 7px; color: #b4232c; font-size: 12px; font-weight: 700; letter-spacing: .06em; }
        h1 { margin: 0; font-size: clamp(25px, 4vw, 32px); letter-spacing: -.04em; }
        .muted { color: #78818e; font-size: 13px; }
        .stats { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 14px; margin-bottom: 20px; }
        .stat,.panel { border: 1px solid #eceef1; border-radius: 14px; background: #fff; box-shadow: 0 5px 18px rgba(23,31,43,.035); }
        .stat { padding: 18px 20px; }
        .stat span { display: block; color: #737b86; font-size: 12px; font-weight: 650; }
        .stat strong { display: block; margin-top: 8px; font-size: 26px; }
        .toolbar { display: flex; gap: 10px; padding: 16px; border-bottom: 1px solid #f0f1f3; }
        .toolbar input,.toolbar select,.edit-form input,.ban-form input { min-height: 40px; padding: 8px 11px; border: 1px solid #e1e4e9; border-radius: 9px; background: #fff; color: #303744; font: inherit; font-size: 13px; }
        .toolbar input { flex: 1; min-width: 0; }
        button,.button { display: inline-flex; min-height: 36px; align-items: center; justify-content: center; padding: 0 12px; border: 0; border-radius: 8px; background: #b4232c; color: #fff; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }
        button:hover,.button:hover { background: #991b24; }
        .button-muted { border: 1px solid #e1e4e9; background: #fff; color: #4e5663; }
        .button-muted:hover { background: #f6f7f9; color: #202733; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th,td { padding: 13px 16px; border-bottom: 1px solid #f0f1f3; text-align: left; vertical-align: middle; font-size: 12px; }
        th { background: #fcfcfd; color: #89919d; font-size: 10px; letter-spacing: .04em; text-transform: uppercase; }
        tr:last-child td { border-bottom: 0; }
        .user-name { min-width: 165px; font-weight: 700; }
        .email { min-width: 190px; color: #707987; }
        .edit-form { display: grid; grid-template-columns: minmax(115px,1fr) minmax(155px,1.2fr) auto; gap: 6px; }
        .badge { display: inline-flex; padding: 4px 9px; border-radius: 999px; background: #eaf7ef; color: #22834a; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .badge.banned { background: #fff0f0; color: #a52222; }
        .actions { min-width: 210px; }
        .ban-form { display: flex; gap: 5px; margin-bottom: 6px; }
        .ban-form input { min-width: 120px; width: 100%; }
        .button-danger { background: #fff0f0; color: #a52222; }
        .button-danger:hover { background: #fce0e0; color: #861b23; }
        .reason { max-width: 240px; margin-top: 5px; color: #a52222; font-size: 11px; white-space: normal; }
        .notice { margin-bottom: 16px; padding: 12px 14px; border: 1px solid #c9e8d2; border-radius: 10px; background: #effaf2; color: #23663a; font-size: 13px; }
        .notice.error { border-color: #f0cccc; background: #fff0f0; color: #a52222; }
        .empty { padding: 32px; color: #78818e; text-align: center; }
        @media(max-width:760px) { main { width: calc(100% - 28px); margin-top: 24px; } .topbar { padding: 12px 14px; } .top-links { gap: 10px; font-size: 12px; } .stats { gap: 9px; } .stat { padding: 14px; } .heading { align-items: flex-start; flex-direction: column; } }
        @media(max-width:480px) { .stats { grid-template-columns: 1fr 1fr; } .toolbar { flex-wrap: wrap; } .toolbar input { flex-basis: 100%; } }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="dashboard.php">ข่าวสารภายในประเทศ / จัดการผู้ใช้</a>
        <nav class="top-links" aria-label="เมนูผู้ดูแล">
            <a href="dashboard.php">แดชบอร์ด</a>
            <a href="news.php">จัดการข่าว</a>
            <span class="admin-role">ADMIN</span>
            <a class="logout-link" href="logout.php">ออกจากระบบ</a>
        </nav>
    </header>
    <main>
        <section class="heading">
            <div>
                <p class="eyebrow">การจัดการสมาชิก</p>
                <h1>บัญชีผู้ใช้</h1>
                <p class="muted">แก้ไขข้อมูล ระงับบัญชีถาวร หรือลบบัญชีผู้ใช้</p>
            </div>
        </section>

        <?php if ($message !== ""): ?>
            <div class="notice<?= $messageType === "error" ? " error" : "" ?>" role="<?= $messageType === "error" ? "alert" : "status" ?>"><?= usersEscape($message) ?></div>
        <?php endif; ?>

        <section class="stats" aria-label="สถิติผู้ใช้">
            <article class="stat"><span>สมาชิกทั้งหมด</span><strong><?= number_format($totalUsers) ?></strong></article>
            <article class="stat"><span>บัญชีปกติ</span><strong><?= number_format($activeUsers) ?></strong></article>
            <article class="stat"><span>ถูกระงับถาวร</span><strong><?= number_format($bannedUsers) ?></strong></article>
        </section>

        <section class="panel">
            <form class="toolbar" method="GET">
                <input type="search" name="search" value="<?= usersEscape($search) ?>" placeholder="ค้นหาชื่อหรืออีเมล" aria-label="ค้นหาชื่อหรืออีเมล">
                <select name="status" aria-label="กรองสถานะ">
                    <option value="">ทุกสถานะ</option>
                    <option value="active"<?= $status === "active" ? " selected" : "" ?>>ใช้งาน</option>
                    <option value="banned"<?= $status === "banned" ? " selected" : "" ?>>ถูกระงับ</option>
                </select>
                <button type="submit">ค้นหา</button>
            </form>
            <?php if ($users): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>บัญชี</th><th>ข้อมูลผู้ใช้</th><th>สถานะ</th><th>สมัครเมื่อ</th><th>จัดการ</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="user-name">#<?= (int) $user["id"] ?></td>
                                    <td>
                                        <form class="edit-form" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= usersEscape($_SESSION["manage_users_csrf"]) ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="user_id" value="<?= (int) $user["id"] ?>">
                                            <input type="text" name="name" value="<?= usersEscape($user["name"]) ?>" aria-label="ชื่อผู้ใช้" maxlength="100" required>
                                            <input type="email" name="email" value="<?= usersEscape($user["email"]) ?>" aria-label="อีเมล" maxlength="150" required>
                                            <button type="submit">บันทึก</button>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge<?= $user["account_status"] === "banned" ? " banned" : "" ?>">
                                            <?= $user["account_status"] === "banned" ? "แบนถาวร" : "ใช้งาน" ?>
                                        </span>
                                        <?php if ($user["account_status"] === "banned" && $user["ban_reason"]): ?>
                                            <div class="reason"><?= usersEscape($user["ban_reason"]) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= usersEscape(date("d/m/Y", strtotime($user["created_at"]))) ?></td>
                                    <td class="actions">
                                        <?php if ($user["account_status"] === "active"): ?>
                                            <form class="ban-form" method="POST" onsubmit="return confirm('ยืนยันการแบนบัญชีนี้อย่างถาวรหรือไม่?');">
                                                <input type="hidden" name="csrf_token" value="<?= usersEscape($_SESSION["manage_users_csrf"]) ?>">
                                                <input type="hidden" name="action" value="ban">
                                                <input type="hidden" name="user_id" value="<?= (int) $user["id"] ?>">
                                                <input type="text" name="ban_reason" maxlength="500" placeholder="เหตุผลการแบน" aria-label="เหตุผลการแบน">
                                                <button type="submit" class="button-danger">แบนถาวร</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" onsubmit="return confirm('ลบบัญชีนี้ถาวรหรือไม่? ความคิดเห็นของผู้ใช้จะถูกลบ แต่ข่าวที่เขียนจะยังคงอยู่');">
                                            <input type="hidden" name="csrf_token" value="<?= usersEscape($_SESSION["manage_users_csrf"]) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= (int) $user["id"] ?>">
                                            <button type="submit" class="button-danger">ลบบัญชี</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty">ไม่พบบัญชีผู้ใช้ตามเงื่อนไขที่เลือก</div>
            <?php endif; ?>
        </section>
    </main>
    <script src="admin-transition.js?v=<?= (int) filemtime(__DIR__ . "/admin-transition.js") ?>" defer></script>
</body>
</html>
