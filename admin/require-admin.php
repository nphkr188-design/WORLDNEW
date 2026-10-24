<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (
    !isset($_SESSION["user_id"])
    || ($_SESSION["user_role"] ?? null) !== "admin"
) {
    header("Location: login.php");
    exit;
}
