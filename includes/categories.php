<?php

$siteCategories = $pdo->query("
    SELECT id, name, slug
    FROM categories
    ORDER BY id
")->fetchAll(PDO::FETCH_ASSOC);
