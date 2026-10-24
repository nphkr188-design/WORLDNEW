<?php

if (
    !isset($_SESSION["user_id"])
    || ($_SESSION["user_role"] ?? null) !== "user"
) {
    return;
}

$stmtActiveUser = $pdo->prepare("
    SELECT name, email
    FROM users
    WHERE id = :id
        AND role = 'user'
        AND account_status = 'active'
    LIMIT 1
");
$stmtActiveUser->execute([":id" => (int) $_SESSION["user_id"]]);
$activeUser = $stmtActiveUser->fetch(PDO::FETCH_ASSOC);

if (!$activeUser) {
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION["account_blocked"] = true;
    return;
}

$_SESSION["user_name"] = $activeUser["name"];
$_SESSION["user_email"] = $activeUser["email"];
